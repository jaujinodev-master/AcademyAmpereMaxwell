<?php
/**
 * Calificaciones - Panel Alumno
 * Muestra las notas agrupadas por curso.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';
require_once __DIR__ . '/../includes/AcademicHelper.php';

requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Mis Calificaciones';

$db = new Database();
$conn = $db->getConnection();
$academic = new AcademicHelper($conn);

$notasPorCurso = $academic->getNotasAlumno($user['id_usuario']);

// Calcular promedios
foreach ($notasPorCurso as $key => &$data) {
    if (empty($data['notas'])) {
        $data['promedio'] = 0;
        continue;
    }
    $suma = 0;
    $count = 0;
    foreach ($data['notas'] as $n) {
        $suma += $n['nota'];
        $count++;
    }
    $data['promedio'] = $count > 0 ? round($suma / $count, 2) : 0;
}
unset($data);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .grade-card { background: var(--dash-bg-card); border-radius: 12px; margin-bottom: 20px; box-shadow: var(--dash-shadow); overflow: hidden; }
        .grade-header { background: var(--dash-bg-alt); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--dash-border); }
        .grade-body { padding: 20px; }
        .grade-table { width: 100%; border-collapse: collapse; }
        .grade-table th, .grade-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--dash-border); }
        .grade-table th { color: var(--dash-text-secondary); font-weight: 600; font-size: 0.9rem; }
        .average-badge { background: var(--dash-primary); color: #fff; padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .average-badge.low { background: var(--dash-danger); }
        .average-badge.medium { background: var(--dash-warning); }
        .average-badge.high { background: var(--dash-success); }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <h2 style="margin-bottom: 24px;">Mis Calificaciones</h2>

                <?php if (empty($notasPorCurso)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar" style="font-size: 4rem; color: var(--dash-text-muted);"></i>
                        <p style="margin-top: 20px;">Aún no tienes calificaciones registradas.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notasPorCurso as $cursoNombre => $datos): ?>
                        <div class="grade-card">
                            <div class="grade-header">
                                <div>
                                    <h3 style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($datos['info']['nombre']) ?></h3>
                                    <span style="font-size: 0.85rem; color: var(--dash-text-muted);"><?= htmlspecialchars($datos['info']['ciclo']) ?></span>
                                </div>
                                <?php 
                                    $prom = $datos['promedio'];
                                    $claseProm = 'medium';
                                    if ($prom < 11) $claseProm = 'low';
                                    if ($prom > 16) $claseProm = 'high';
                                ?>
                                <div class="average-badge <?= $claseProm ?>">
                                    Promedio: <?= $prom ?>
                                </div>
                            </div>
                            <div class="grade-body">
                                <table class="grade-table">
                                    <thead>
                                        <tr>
                                            <th>Evaluación</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th style="text-align: right;">Nota</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($datos['notas'] as $nota): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($nota['descripcion']) ?></td>
                                                <td><span class="badge badge-light"><?= ucfirst($nota['tipo_evaluacion']) ?></span></td>
                                                <td><?= date('d/m/Y', strtotime($nota['fecha_evaluacion'])) ?></td>
                                                <td style="text-align: right; font-weight: 600; color: <?= $nota['nota'] < 11 ? 'var(--dash-danger)' : 'var(--dash-text-primary)' ?>">
                                                    <?= $nota['nota'] ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
