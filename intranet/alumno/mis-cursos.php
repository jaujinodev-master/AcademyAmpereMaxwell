<?php
/**
 * Mis Cursos - Panel Alumno
 * Academia Ampere Maxwell
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Mis Cursos';

$db = new Database();
$conn = $db->getConnection();

// Obtener cursos del alumno a través de su matrícula
$sql = "SELECT c.*, ca.nombre_ciclo, ca.estado as ciclo_estado,
        (SELECT CONCAT(u.nombres, ' ', u.apellidos) FROM profesor_curso pc 
         INNER JOIN usuarios u ON pc.id_profesor = u.id_usuario 
         WHERE pc.id_curso = c.id_curso AND pc.estado = 'activo' LIMIT 1) as profesor,
        ic.fecha_inscripcion
        FROM inscripciones_curso ic
        INNER JOIN cursos c ON ic.id_curso = c.id_curso
        INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
        INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
        WHERE m.id_alumno = ? AND ic.estado = 'activo'
        ORDER BY ca.fecha_inicio DESC, c.nombre_curso";

$stmt = $conn->prepare($sql);
$stmt->execute([$user['id_usuario']]);
$cursos = $stmt->fetchAll();

// Obtener matrícula activa
$matricula = $conn->prepare("SELECT m.*, ca.nombre_ciclo FROM matriculas m 
    INNER JOIN ciclos_academicos ca ON m.id_ciclo = ca.id_ciclo 
    WHERE m.id_alumno = ? AND m.estado_matricula = 'activo' ORDER BY m.fecha_matricula DESC LIMIT 1");
$matricula->execute([$user['id_usuario']]);
$matriculaActiva = $matricula->fetch();
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
        .course-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--dash-border); }
        .empty-state { text-align: center; padding: 60px; background: var(--dash-bg-card); border-radius: 16px; }
        .empty-state i { font-size: 4rem; color: var(--dash-text-muted); margin-bottom: 16px; }
        .matricula-banner { background: linear-gradient(135deg, var(--dash-success), #059669); color: #fff; padding: 20px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .matricula-info h3 { font-size: 1.1rem; margin-bottom: 4px; }
        .matricula-info p { opacity: 0.9; font-size: 0.9rem; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <h2 style="font-size: 1.3rem; font-weight: 600; margin-bottom: 24px;">Mis Cursos</h2>
                
                <?php if ($matriculaActiva): ?>
                    <div class="matricula-banner">
                        <div class="matricula-info">
                            <h3><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($matriculaActiva['nombre_ciclo']) ?></h3>
                            <p>Matriculado desde <?= date('d/m/Y', strtotime($matriculaActiva['fecha_matricula'])) ?></p>
                        </div>
                        <span class="badge badge-light" style="background: rgba(255,255,255,0.2); color: #fff; padding: 8px 16px;">
                            <?= count($cursos) ?> cursos inscritos
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($cursos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3 style="margin-bottom: 10px;">No tienes cursos inscritos</h3>
                        <p style="color: var(--dash-text-muted);">Contacta con administración para inscribirte en cursos.</p>
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
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <span><?= htmlspecialchars($c['profesor'] ?? 'Sin asignar') ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?= $c['horas_semanales'] ?>h/semana</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-star"></i>
                                            <span><?= $c['creditos'] ?> créditos</span>
                                        </div>
                                    </div>
                                    
                                    <div class="course-actions">
                                        <a href="calificaciones.php?curso=<?= $c['id_curso'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-chart-line"></i> Mis Notas
                                        </a>
                                        <a href="materiales.php?curso=<?= $c['id_curso'] ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-folder-open"></i> Materiales
                                        </a>
                                        <a href="tareas.php?curso=<?= $c['id_curso'] ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-tasks"></i> Tareas
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
