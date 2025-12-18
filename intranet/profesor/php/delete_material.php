<?php
/**
 * delete_material.php
 * Elimina un material educativo
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
    $user = Auth::getUser();

    $id = (int)$_POST['id'];

    // Verificar que el material pertenezca al profesor
    $stmt = $conn->prepare("SELECT ruta_archivo FROM materiales_educativos WHERE id_material = ? AND id_profesor = ?");
    $stmt->execute([$id, $user['id_usuario']]);
    $material = $stmt->fetch();

    if (!$material) {
        throw new Exception('Material no encontrado o sin permiso');
    }

    // Eliminar archivo físico si existe
    if ($material['ruta_archivo']) {
        $rutaArchivo = __DIR__ . '/../../../uploads/materiales/' . $material['ruta_archivo'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    // Eliminar de BD
    $conn->prepare("DELETE FROM materiales_educativos WHERE id_material = ?")->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Material eliminado']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
