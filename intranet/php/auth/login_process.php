<?php
/**
 * Procesar Login - Academia Ampere Maxwell
 * 
 * Recibe credenciales via POST y devuelve respuesta JSON
 */

// Headers para respuesta JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Incluir clase de autenticación
require_once __DIR__ . '/Auth.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener datos del formulario
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && $_POST['remember'] === '1';

// Validar que los campos no estén vacíos
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Por favor completa todos los campos'
    ]);
    exit;
}

// Intentar login
$auth = new Auth();
$result = $auth->login($username, $password, $remember);

// Devolver respuesta
echo json_encode($result);
?>
