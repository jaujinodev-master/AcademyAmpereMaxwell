<?php
/**
 * Dashboard Administrativo - Academia Ampere Maxwell
 * 
 * Panel principal con estadísticas y resumen general
 */

// Configuración y autenticación
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

// Verificar que sea administrador
requireAdmin();

// Obtener datos del usuario
$user = Auth::getUser();
$pageTitle = 'Dashboard';

// Conexión a base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtener estadísticas
try {
    // Total de alumnos activos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 3 AND estado = 'activo'");
    $totalAlumnos = $stmt->fetch()['total'];
    
    // Total de profesores activos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 2 AND estado = 'activo'");
    $totalProfesores = $stmt->fetch()['total'];
    
    // Ciclos activos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ciclos_academicos WHERE estado = 'activo'");
    $ciclosActivos = $stmt->fetch()['total'];
    
    // Cursos activos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM cursos WHERE estado = 'activo'");
    $cursosActivos = $stmt->fetch()['total'];
    
    // Matrículas del mes actual
    $stmt = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE MONTH(fecha_matricula) = MONTH(NOW()) AND YEAR(fecha_matricula) = YEAR(NOW())");
    $matriculasMes = $stmt->fetch()['total'];
    
    // Últimos alumnos registrados
    $stmt = $conn->query("
        SELECT u.*, r.nombre_rol 
        FROM usuarios u 
        INNER JOIN roles r ON u.id_rol = r.id_rol 
        WHERE u.id_rol = 3 
        ORDER BY u.fecha_creacion DESC 
        LIMIT 5
    ");
    $ultimosAlumnos = $stmt->fetchAll();
    
    // Ciclos académicos
    $stmt = $conn->query("
        SELECT ca.*, 
               (SELECT COUNT(*) FROM matriculas m WHERE m.id_ciclo = ca.id_ciclo) as total_matriculas
        FROM ciclos_academicos ca 
        WHERE ca.estado = 'activo'
        ORDER BY ca.fecha_inicio DESC
    ");
    $ciclos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $totalAlumnos = $totalProfesores = $ciclosActivos = $cursosActivos = $matriculasMes = 0;
    $ultimosAlumnos = $ciclos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="icon" href="../../assets/images/AMPEREMAXWELL.jpg" type="image/x-icon">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <!-- Content -->
            <div class="content-wrapper">
                <!-- Welcome Message -->
                <div class="welcome-banner" style="background: linear-gradient(135deg, var(--dash-primary) 0%, var(--dash-accent) 100%); color: #fff; padding: 24px 30px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 1.5rem; margin-bottom: 8px;">¡Bienvenido, <?= htmlspecialchars($user['nombres']) ?>! 👋</h2>
                        <p style="opacity: 0.9;">Aquí tienes un resumen de la actividad de la academia.</p>
                    </div>
                    <div>
                        <span style="font-size: 0.9rem; opacity: 0.8;"><?= date('l, d F Y') ?></span>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalAlumnos ?></h3>
                            <p>Alumnos Activos</p>
                            <span class="stat-change positive"><i class="fas fa-arrow-up"></i> +5% este mes</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalProfesores ?></h3>
                            <p>Profesores</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $cursosActivos ?></h3>
                            <p>Cursos Activos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon accent">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $ciclosActivos ?></h3>
                            <p>Ciclos Académicos</p>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="dashboard-grid">
                    <!-- Chart Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Matrículas por Mes</h3>
                            <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                        <div class="card-body">
                            <canvas id="matriculasChart" height="300"></canvas>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Acciones Rápidas</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; gap: 12px;">
                                <a href="usuarios.php?action=new&type=alumno" class="btn btn-primary" style="justify-content: center;">
                                    <i class="fas fa-user-plus"></i> Nuevo Alumno
                                </a>
                                <a href="usuarios.php?action=new&type=profesor" class="btn btn-success" style="justify-content: center;">
                                    <i class="fas fa-chalkboard-teacher"></i> Nuevo Profesor
                                </a>
                                <a href="ciclos.php?action=new" class="btn btn-outline" style="justify-content: center;">
                                    <i class="fas fa-calendar-plus"></i> Nuevo Ciclo
                                </a>
                                <a href="cursos.php?action=new" class="btn btn-outline" style="justify-content: center;">
                                    <i class="fas fa-book-medical"></i> Nuevo Curso
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tables Section -->
                <div class="dashboard-grid" style="margin-top: 24px;">
                    <!-- Recent Students -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Últimos Alumnos Registrados</h3>
                            <a href="usuarios.php" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;">
                                Ver todos
                            </a>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Alumno</th>
                                            <th>Email</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($ultimosAlumnos)): ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; padding: 30px; color: var(--dash-text-muted);">
                                                    No hay alumnos registrados aún
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($ultimosAlumnos as $alumno): ?>
                                                <tr>
                                                    <td>
                                                        <div style="display: flex; align-items: center; gap: 10px;">
                                                            <img src="<?= Auth::getAvatarUrl($alumno['foto_perfil']) ?>" 
                                                                 alt="" 
                                                                 style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover;"
                                                                 onerror="this.src='../assets/images/default-avatar.svg'">
                                                            <span><?= htmlspecialchars($alumno['nombres'] . ' ' . $alumno['apellidos']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($alumno['fecha_creacion'])) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $alumno['estado'] === 'activo' ? 'success' : 'warning' ?>">
                                                            <?= ucfirst($alumno['estado']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Cycles -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ciclos Activos</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ciclos)): ?>
                                <div style="text-align: center; padding: 30px; color: var(--dash-text-muted);">
                                    <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                    No hay ciclos activos
                                </div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 16px;">
                                    <?php foreach ($ciclos as $ciclo): ?>
                                        <div style="background: var(--dash-bg-main); padding: 16px; border-radius: 12px;">
                                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                                <h4 style="font-size: 1rem; font-weight: 600;"><?= htmlspecialchars($ciclo['nombre_ciclo']) ?></h4>
                                                <span class="badge badge-primary"><?= $ciclo['total_matriculas'] ?> matriculados</span>
                                            </div>
                                            <p style="font-size: 0.85rem; color: var(--dash-text-muted); margin-bottom: 8px;">
                                                <i class="fas fa-calendar"></i> 
                                                <?= date('d/m/Y', strtotime($ciclo['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($ciclo['fecha_fin'])) ?>
                                            </p>
                                            <div style="display: flex; gap: 8px;">
                                                <span class="badge badge-<?= $ciclo['modalidad'] === 'presencial' ? 'success' : ($ciclo['modalidad'] === 'virtual' ? 'info' : 'warning') ?>">
                                                    <?= ucfirst($ciclo['modalidad']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <!-- Overlay for mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Gráfico de matrículas
        const ctx = document.getElementById('matriculasChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Matrículas 2025',
                    data: [12, 19, 15, 25, 22, 30, 28, 35, 40, 45, 50, 55],
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4F46E5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
