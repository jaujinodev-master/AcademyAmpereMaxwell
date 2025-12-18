<?php
/**
 * Horario - Panel Alumno
 * Visualización del horario semanal de clases.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';
require_once __DIR__ . '/../includes/AcademicHelper.php';

requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Horario de Clases';

$db = new Database();
$conn = $db->getConnection();
$academic = new AcademicHelper($conn);

$horarioPlano = $academic->getHorarioAlumno($user['id_usuario']);

// Estructurar horario por días
$dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
$horarioSemanal = array_fill_keys($dias, []);

foreach ($horarioPlano as $clase) {
    if (in_array(strtolower($clase['dia_semana']), $dias)) {
        $horarioSemanal[strtolower($clase['dia_semana'])][] = $clase;
    }
}
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
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .day-column {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .day-header {
            background: var(--dash-primary);
            color: #fff;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            text-transform: capitalize;
        }
        .class-card {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .class-card:last-child { border-bottom: none; }
        .class-card:hover { background: #f9fafb; }
        .class-time {
            font-size: 0.85rem;
            color: var(--dash-text-muted);
            margin-bottom: 5px;
            display: flex; align-items: center; gap: 5px;
        }
        .class-name { font-weight: 600; color: var(--dash-text-primary); margin-bottom: 4px; }
        .class-prof { font-size: 0.85rem; color: #666; }
        .class-room { 
            display: inline-block; 
            font-size: 0.75rem; 
            padding: 2px 8px; 
            background: #e0e7ff; 
            color: #4338ca; 
            border-radius: 10px; 
            margin-top: 5px; 
        }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <h2 style="margin-bottom: 24px;">Mi Horario Semanal</h2>

                <?php if (empty($horarioPlano)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt" style="font-size: 4rem; color: var(--dash-text-muted);"></i>
                        <p style="margin-top: 20px;">No tienes clases asignadas actualmente.</p>
                    </div>
                <?php else: ?>
                    <div class="schedule-grid">
                        <?php foreach ($dias as $dia): ?>
                            <?php if (!empty($horarioSemanal[$dia])): ?>
                                <div class="day-column">
                                    <div class="day-header"><?= $dia ?></div>
                                    <?php foreach ($horarioSemanal[$dia] as $clase): ?>
                                        <div class="class-card">
                                            <div class="class-time">
                                                <i class="far fa-clock"></i>
                                                <?= date('H:i', strtotime($clase['hora_inicio'])) ?> - <?= date('H:i', strtotime($clase['hora_fin'])) ?>
                                            </div>
                                            <div class="class-name"><?= htmlspecialchars($clase['nombre_curso']) ?></div>
                                            <div class="class-prof">
                                                <i class="fas fa-chalkboard-teacher"></i> 
                                                <?= htmlspecialchars($clase['nom_prof'] ? $clase['nom_prof'] . ' ' . $clase['ape_prof'] : 'Por asignar') ?>
                                            </div>
                                            <?php if ($clase['aula']): ?>
                                                <div class="class-room">Aula <?= htmlspecialchars($clase['aula']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
