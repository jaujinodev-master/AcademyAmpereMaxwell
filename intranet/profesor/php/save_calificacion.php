<?php
/**
 * save_calificacion.php
 * Procesa el guardado masivo de notas
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../php/auth/Auth.php';

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar sesión
if (!Auth::isProfesor()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener datos
    $id_curso = (int)$_POST['id_curso'];
    $tipo = $_POST['tipo'];
    $descripcion = trim($_POST['descripcion']);
    $fecha = $_POST['fecha'];
    $notas = $_POST['notas'] ?? []; // Array [id_inscripcion => nota]

    if (empty($descripcion) || empty($notas)) {
        throw new Exception("Faltan datos requeridos (descripción o notas)");
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO calificaciones (id_inscripcion, tipo_evaluacion, descripcion, nota, fecha_evaluacion) 
        VALUES (:inscripcion, :tipo, :desc, :nota, :fecha)
    ");

    $count = 0;
    foreach ($notas as $inscripcionId => $valorNota) {
        $valor = floatval($valorNota);
        // Solo guardar si se ingresó un valor y es válido
        if ($valorNota !== '' && $valor >= 0 && $valor <= 20) {
            $stmt->execute([
                ':inscripcion' => $inscripcionId,
                ':tipo' => $tipo,
                ':desc' => $descripcion,
                ':nota' => $valor,
                ':fecha' => $fecha
            ]);
            $count++;
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Se registraron $count notas correctamente"]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
