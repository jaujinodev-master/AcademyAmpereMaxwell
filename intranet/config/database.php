<?php
/**
 * Configuración de Base de Datos
 * Academia Ampere Maxwell
 * 
 * Este archivo contiene las credenciales y configuración para la conexión a MySQL
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'academia_ampere_maxwell');
define('DB_USER', 'root');
define('DB_PASS', ''); // Por defecto XAMPP no tiene contraseña para root
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Academia Ampere Maxwell');
define('APP_URL', 'http://localhost/AcademyAmpereMaxwell');
define('APP_ENV', 'development'); // development, production

// Configuración de sesiones
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('SESSION_NAME', 'AMPERE_SESSION');

// Configuración de seguridad
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);

// Zona horaria
date_default_timezone_set('America/Lima');

// Mostrar errores solo en desarrollo
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
