<?php
/**
 * Dashboard del Profesor - Academia Ampere Maxwell
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

// Verificar que sea profesor
requireProfesor();

$user = Auth::getUser();
$pageTitle = 'Mi Dashboard';

// Conexión a base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtener estadísticas del profesor
try {
    $userId = $user['id_usuario'];
    
    // Cursos asignados
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM profesor_curso pc 
        INNER JOIN cursos c ON pc.id_curso = c.id_curso 
        WHERE pc.id_profesor = ? AND pc.estado = 'activo' AND c.estado = 'activo'
    ");
    $stmt->execute([$userId]);
    $cursosAsignados = $stmt->fetch()['total'];
    
    // Total alumnos en mis cursos
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ic.id_matricula) as total 
        FROM profesor_curso pc
        INNER JOIN inscripciones_curso ic ON pc.id_curso = ic.id_curso
        WHERE pc.id_profesor = ? AND pc.estado = 'activo' AND ic.estado = 'activo'
    ");
    $stmt->execute([$userId]);
    $totalAlumnos = $stmt->fetch()['total'];
    
    // Tareas activas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tareas WHERE id_profesor = ? AND estado = 'activo'");
    $stmt->execute([$userId]);
    $tareasActivas = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $cursosAsignados = $totalAlumnos = $tareasActivas = 0;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Welcome -->
                <div class="welcome-banner" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: #fff; padding: 24px 30px; border-radius: 16px; margin-bottom: 24px;">
                    <h2 style="font-size: 1.5rem; margin-bottom: 8px;">¡Hola, Profesor <?= htmlspecialchars($user['nombres']) ?>! 👨‍🏫</h2>
                    <p style="opacity: 0.9;">Gestiona tus cursos, calificaciones y materiales educativos.</p>
                </div>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $cursosAsignados ?></h3>
                            <p>Cursos Asignados</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $totalAlumnos ?></h3>
                            <p>Alumnos Total</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $tareasActivas ?></h3>
                            <p>Tareas Activas</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Acciones Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <a href="mis-cursos.php" class="btn btn-primary" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-book-open"></i> Ver Mis Cursos
                            </a>
                            <a href="calificaciones.php" class="btn btn-success" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-clipboard-check"></i> Registrar Notas
                            </a>
                            <a href="asistencia.php" class="btn btn-outline" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-user-check"></i> Tomar Asistencia
                            </a>
                            <a href="materiales.php" class="btn btn-outline" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-file-upload"></i> Subir Material
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
