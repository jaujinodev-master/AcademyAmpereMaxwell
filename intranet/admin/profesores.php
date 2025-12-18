<?php
/**
 * Gestión de Profesores - Academia Ampere Maxwell
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAdmin();

// Redirigir a vista filtrada de usuarios
header('Location: usuarios.php?filter=profesores');
exit;
?>
