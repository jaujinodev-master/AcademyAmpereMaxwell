<?php
/**
 * submit_tarea.php
 * Procesa la entrega de tareas de alumnos
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../php/auth/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!Auth::isAlumno()) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $user = Auth::getUser();

    $id_tarea = (int)$_POST['id_tarea'];
    $comentario = trim($_POST['comentario'] ?? '');

    // Verificar que la tarea exista y no esté vencida
    $stmt = $conn->prepare("SELECT * FROM tareas WHERE id_tarea = ? AND estado = 'activo'");
    $stmt->execute([$id_tarea]);
    $tarea = $stmt->fetch();

    if (!$tarea) {
        throw new Exception('Tarea no encontrada');
    }

    // Verificar que no haya entregado ya
    $check = $conn->prepare("SELECT COUNT(*) FROM entregas_tareas WHERE id_tarea = ? AND id_alumno = ?");
    $check->execute([$id_tarea, $user['id_usuario']]);
    if ($check->fetchColumn() > 0) {
        throw new Exception('Ya has entregado esta tarea anteriormente');
    }

    // Procesar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debe adjuntar un archivo');
    }

    $archivo = $_FILES['archivo'];
    
    // Validar tamaño (10MB)
    if ($archivo['size'] > 10 * 1024 * 1024) {
        throw new Exception('El archivo excede 10MB');
    }

    // Validar extensión
    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'zip', 'rar'];
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extensionesPermitidas)) {
        throw new Exception('Tipo de archivo no permitido');
    }

    // Guardar archivo
    $nombreArchivo = 'tarea_' . $id_tarea . '_' . $user['id_usuario'] . '_' . time() . '.' . $ext;
    $rutaDestino = __DIR__ . '/../../../uploads/tareas/' . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new Exception('Error al guardar el archivo');
    }

    // Determinar si está retrasado
    $estado = 'entregado';
    if (strtotime($tarea['fecha_entrega']) < time()) {
        $estado = 'retrasado';
    }

    // Insertar entrega
    $sql = "INSERT INTO entregas_tareas (id_tarea, id_alumno, ruta_archivo, comentario, estado)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_tarea, $user['id_usuario'], $nombreArchivo, $comentario, $estado]);

    echo json_encode(['success' => true, 'message' => 'Tarea entregada correctamente']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
