<?php
/**
 * Asistencia - Panel Alumno
 * Visualización de estadísticas de asistencia
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';
require_once __DIR__ . '/../includes/AcademicHelper.php';

requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Mi Asistencia';

$db = new Database();
$conn = $db->getConnection();
$academic = new AcademicHelper($conn);

$asistencias = $academic->getAsistenciaAlumno($user['id_usuario']);
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
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-value { font-size: 2rem; font-weight: 700; margin: 10px 0; }
        .stat-label { color: #666; font-size: 0.9rem; }
        .attendance-bar { height: 8px; background: #eee; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .attendance-fill { height: 100%; transition: width 0.3s ease; }
        .bg-success { background-color: #10b981; }
        .bg-warning { background-color: #f59e0b; }
        .bg-danger { background-color: #ef4444; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <h2 style="margin-bottom: 24px;">Reporte de Asistencia</h2>

                <?php if (empty($asistencias)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check" style="font-size: 4rem; color: var(--dash-text-muted);"></i>
                        <p style="margin-top: 20px;">No hay registros de asistencia disponibles.</p>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($asistencias as $asist): 
                            $total = $asist['total_clases'];
                            $presentes = $asist['presentes'];
                            $pct = $total > 0 ? round(($presentes / $total) * 100) : 0;
                            
                            $colorClass = 'bg-success';
                            if ($pct < 85) $colorClass = 'bg-warning';
                            if ($pct < 70) $colorClass = 'bg-danger';
                        ?>
                            <div class="card">
                                <div class="card-body">
                                    <h3 style="font-size: 1.1rem; margin-bottom: 15px;"><?= htmlspecialchars($asist['nombre_curso']) ?></h3>
                                    
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span>Asistencia:</span>
                                        <span style="font-weight: 700;"><?= $pct ?>%</span>
                                    </div>
                                    
                                    <div class="attendance-bar">
                                        <div class="attendance-fill <?= $colorClass ?>" style="width: <?= $pct ?>%"></div>
                                    </div>

                                    <div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; text-align: center;">
                                        <div style="background: #f0fdf4; padding: 10px; border-radius: 8px;">
                                            <div style="font-weight: 700; color: #166534;"><?= $presentes ?></div>
                                            <div style="font-size: 0.8rem; color: #166534;">Presente</div>
                                        </div>
                                        <div style="background: #fefce8; padding: 10px; border-radius: 8px;">
                                            <div style="font-weight: 700; color: #854d0e;"><?= $asist['tardanzas'] ?></div>
                                            <div style="font-size: 0.8rem; color: #854d0e;">Tardanza</div>
                                        </div>
                                        <div style="background: #fef2f2; padding: 10px; border-radius: 8px;">
                                            <div style="font-weight: 700; color: #991b1b;"><?= $asist['faltas'] ?></div>
                                            <div style="font-size: 0.8rem; color: #991b1b;">Falta</div>
                                        </div>
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
