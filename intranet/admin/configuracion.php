<?php
/**
 * Configuración - Academia Ampere Maxwell
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAdmin();

$user = Auth::getUser();
$pageTitle = 'Configuración del Sistema';
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
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 60px;">
                        <i class="fas fa-cogs" style="font-size: 4rem; color: var(--dash-text-muted); margin-bottom: 20px;"></i>
                        <h3>Configuración en Construcción</h3>
                        <p style="color: var(--dash-text-muted);">Próximamente podrás modificar los parámetros globales del sistema.</p>
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
