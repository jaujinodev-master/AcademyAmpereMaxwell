<?php
/**
 * Middleware de Autenticación - Academia Ampere Maxwell
 * 
 * Protege rutas y verifica permisos de acceso
 */

require_once __DIR__ . '/Auth.php';

/**
 * Verificar que el usuario esté autenticado
 * Redirige al login si no lo está
 */
function requireLogin() {
    if (!Auth::isLoggedIn()) {
        header('Location: ' . APP_URL . '/intranet/login.php?error=session_expired');
        exit;
    }
}

/**
 * Verificar que el usuario tenga un rol específico
 * @param int|array $roles Rol o roles permitidos
 */
function requireRole($roles) {
    requireLogin();
    
    if (!Auth::hasRole($roles)) {
        // Redirigir a su propio dashboard si no tiene permiso
        $userRole = Auth::getUserRole();
        
        switch ($userRole) {
            case Auth::ROLE_ADMIN:
                header('Location: ' . APP_URL . '/intranet/admin/');
                break;
            case Auth::ROLE_PROFESOR:
                header('Location: ' . APP_URL . '/intranet/profesor/');
                break;
            case Auth::ROLE_ALUMNO:
                header('Location: ' . APP_URL . '/intranet/alumno/');
                break;
            default:
                header('Location: ' . APP_URL . '/intranet/login.php');
        }
        exit;
    }
}

/**
 * Verificar que sea administrador
 */
function requireAdmin() {
    requireRole(Auth::ROLE_ADMIN);
}

/**
 * Verificar que sea profesor
 */
function requireProfesor() {
    requireRole(Auth::ROLE_PROFESOR);
}

/**
 * Verificar que sea alumno
 */
function requireAlumno() {
    requireRole(Auth::ROLE_ALUMNO);
}

/**
 * Verificar que sea profesor o admin
 */
function requireProfesorOrAdmin() {
    requireRole([Auth::ROLE_ADMIN, Auth::ROLE_PROFESOR]);
}

/**
 * Obtener mensaje de error de la URL
 * @return string|null
 */
function getLoginError() {
    $errors = [
        'session_expired' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.',
        'unauthorized' => 'No tienes permiso para acceder a esa página.',
        'logout' => 'Has cerrado sesión correctamente.'
    ];
    
    $error = $_GET['error'] ?? null;
    return $errors[$error] ?? null;
}
?>
