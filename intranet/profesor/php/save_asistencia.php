<?php
/**
 * save_asistencia.php
 * Procesa el guardado de asistencia
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../php/auth/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!Auth::isProfesor()) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $fecha = $_POST['fecha'];
    $asistencias = $_POST['asistencia'] ?? []; // [id_inscripcion => estado]
    $observaciones = $_POST['observacion'] ?? [];

    if (empty($asistencias)) {
        throw new Exception("No hay datos de asistencia para guardar");
    }

    $conn->beginTransaction();

    // Utilizamos INSERT ... ON DUPLICATE KEY UPDATE para manejar insertado o actualización
    $sql = "INSERT INTO asistencias (id_inscripcion, fecha, estado, observaciones) 
            VALUES (:id, :fecha, :estado, :obs)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), observaciones = VALUES(observaciones)";
    
    $stmt = $conn->prepare($sql);

    foreach ($asistencias as $inscId => $estado) {
        $obs = $observaciones[$inscId] ?? '';
        $stmt->execute([
            ':id' => $inscId,
            ':fecha' => $fecha,
            ':estado' => $estado,
            ':obs' => $obs
        ]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Asistencia guardada correctamente']);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
