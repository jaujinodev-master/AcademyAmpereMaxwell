<?php
/**
 * Header Component - Academia Ampere Maxwell
 * 
 * Barra superior con usuario, notificaciones y búsqueda
 */

require_once __DIR__ . '/../php/auth/Auth.php';

$user = Auth::getUser();
$userName = $user['nombres'] . ' ' . $user['apellidos'];
$userRole = $user['nombre_rol'];
$userAvatar = $user['foto_perfil'] ?? null;

// Obtener avatar
$avatarUrl = Auth::getAvatarUrl($userAvatar);

// Título de la página (se puede pasar como variable)
$pageTitle = $pageTitle ?? 'Dashboard';
?>

<!-- Header -->
<header class="main-header">
    <div class="header-left">
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
    </div>
    
    <div class="header-center">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Buscar...">
        </div>
    </div>
    
    <div class="header-right">
        <!-- Notificaciones -->
        <div class="header-notifications dropdown">
            <button class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <div class="dropdown-menu notification-dropdown" id="notificationDropdown">
                <div class="dropdown-header">
                    <h4>Notificaciones</h4>
                    <a href="#" class="mark-all-read">Marcar todas como leídas</a>
                </div>
                <div class="dropdown-body">
                    <div class="notification-item unread">
                        <div class="notification-icon bg-primary">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="notification-content">
                            <p>Nuevo alumno registrado</p>
                            <span class="notification-time">Hace 5 min</span>
                        </div>
                    </div>
                    <div class="notification-item unread">
                        <div class="notification-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="notification-content">
                            <p>Pago recibido correctamente</p>
                            <span class="notification-time">Hace 1 hora</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon bg-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="notification-content">
                            <p>Ciclo verano próximo a iniciar</p>
                            <span class="notification-time">Ayer</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-footer">
                    <a href="#">Ver todas las notificaciones</a>
                </div>
            </div>
        </div>
        
        <!-- Usuario -->
        <div class="header-user dropdown">
            <button class="user-btn" id="userBtn">
                <img src="<?= $avatarUrl ?>" alt="Avatar" class="user-avatar" onerror="this.src='<?= APP_URL ?>/intranet/assets/images/default-avatar.svg'">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                    <span class="user-role"><?= htmlspecialchars($userRole) ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu user-dropdown" id="userDropdown">
                <a href="perfil.php" class="dropdown-item">
                    <i class="fas fa-user"></i> Mi Perfil
                </a>
                <a href="configuracion.php" class="dropdown-item">
                    <i class="fas fa-cog"></i> Configuración
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= APP_URL ?>/intranet/logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</header>
