<?php
/**
 * Mis Cursos - Panel Profesor
 * Academia Ampere Maxwell
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireProfesor();

$user = Auth::getUser();
$pageTitle = 'Mis Cursos';

$db = new Database();
$conn = $db->getConnection();

// Obtener cursos asignados al profesor
$sql = "SELECT c.*, ca.nombre_ciclo, ca.estado as ciclo_estado,
        (SELECT COUNT(*) FROM inscripciones_curso ic 
         INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula 
         WHERE ic.id_curso = c.id_curso AND ic.estado = 'activo') as total_alumnos,
        pc.fecha_asignacion
        FROM profesor_curso pc
        INNER JOIN cursos c ON pc.id_curso = c.id_curso
        INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
        WHERE pc.id_profesor = ? AND pc.estado = 'activo'
        ORDER BY ca.fecha_inicio DESC, c.nombre_curso";

$stmt = $conn->prepare($sql);
$stmt->execute([$user['id_usuario']]);
$cursos = $stmt->fetchAll();

// Contar totales
$totalAlumnos = array_sum(array_column($cursos, 'total_alumnos'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2.0">
    <link rel="icon" href="../../assets/images/AMPEREMAXWELL.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .course-card { background: var(--dash-bg-card); border-radius: 16px; border: 1px solid var(--dash-border); overflow: hidden; transition: all 0.3s; }
        .course-card:hover { box-shadow: var(--dash-shadow-lg); transform: translateY(-4px); }
        .course-header { background: linear-gradient(135deg, var(--dash-primary), var(--dash-accent)); padding: 20px; color: #fff; }
        .course-title { font-size: 1.15rem; font-weight: 600; margin-bottom: 8px; }
        .course-code { font-size: 0.85rem; opacity: 0.9; }
        .course-body { padding: 20px; }
        .course-info { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
        .info-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--dash-text-secondary); }
        .info-item i { color: var(--dash-primary); }
        .course-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; padding-top: 16px; border-top: 1px solid var(--dash-border); }
        .stat { text-align: center; }
        .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--dash-primary); }
        .stat-label { font-size: 0.75rem; color: var(--dash-text-muted); }
        .course-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .empty-state { text-align: center; padding: 60px; background: var(--dash-bg-card); border-radius: 16px; }
        .empty-state i { font-size: 4rem; color: var(--dash-text-muted); margin-bottom: 16px; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card { background: var(--dash-bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--dash-border); display: flex; align-items: center; gap: 16px; }
        .summary-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .summary-icon.primary { background: rgba(79, 70, 229, 0.1); color: var(--dash-primary); }
        .summary-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--dash-success); }
        .summary-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--dash-warning); }
        .summary-value { font-size: 1.5rem; font-weight: 700; }
        .summary-label { font-size: 0.85rem; color: var(--dash-text-muted); }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <h2 style="font-size: 1.3rem; font-weight: 600; margin-bottom: 24px;">Mis Cursos Asignados</h2>
                
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-icon primary">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <div class="summary-value"><?= count($cursos) ?></div>
                            <div class="summary-label">Cursos Activos</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-icon success">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <div class="summary-value"><?= $totalAlumnos ?></div>
                            <div class="summary-label">Total Alumnos</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-icon warning">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <div class="summary-value">0</div>
                            <div class="summary-label">Tareas Pendientes</div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($cursos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3 style="margin-bottom: 10px;">No tienes cursos asignados</h3>
                        <p style="color: var(--dash-text-muted);">Contacta con el administrador para que te asigne cursos.</p>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($cursos as $c): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-title"><?= htmlspecialchars($c['nombre_curso']) ?></div>
                                    <div class="course-code"><?= htmlspecialchars($c['codigo_curso'] ?? 'Sin código') ?></div>
                                </div>
                                <div class="course-body">
                                    <div class="course-info">
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?= htmlspecialchars($c['nombre_ciclo']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?= $c['horas_semanales'] ?>h/semana</span>
                                        </div>
                                    </div>
                                    
                                    <div class="course-stats">
                                        <div class="stat">
                                            <div class="stat-value"><?= $c['total_alumnos'] ?></div>
                                            <div class="stat-label">Alumnos</div>
                                        </div>
                                        <div class="stat">
                                            <div class="stat-value"><?= $c['creditos'] ?></div>
                                            <div class="stat-label">Créditos</div>
                                        </div>
                                        <div class="stat">
                                            <div class="stat-value">
                                                <span class="badge badge-<?= $c['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($c['estado']) ?>
                                                </span>
                                            </div>
                                            <div class="stat-label">Estado</div>
                                        </div>
                                    </div>
                                    
                                    <div class="course-actions">
                                        <a href="calificaciones.php?curso=<?= $c['id_curso'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-star"></i> Calificaciones
                                        </a>
                                        <a href="asistencia.php?curso=<?= $c['id_curso'] ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-clipboard-check"></i> Asistencia
                                        </a>
                                        <a href="materiales.php?curso=<?= $c['id_curso'] ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-folder"></i> Materiales
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
