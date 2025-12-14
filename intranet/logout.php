<?php
/**
 * Logout - Academia Ampere Maxwell
 * 
 * Cierra la sesión y redirige al login
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/php/auth/Auth.php';

// Cerrar sesión
Auth::logout();

// Redirigir al login con mensaje
header('Location: login.php?error=logout');
exit;
?>
