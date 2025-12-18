<?php
/**
 * save_tarea.php
 * Crea una nueva tarea
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

    $id_curso = (int)$_POST['id_curso'];
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_entrega = $_POST['fecha_entrega'];
    $puntaje_maximo = (float)($_POST['puntaje_maximo'] ?? 20);

    if (empty($titulo) || empty($fecha_entrega)) {
        throw new Exception('Título y fecha de entrega son requeridos');
    }

    // Validar que el profesor tenga el curso
    $check = $conn->prepare("SELECT COUNT(*) FROM profesor_curso WHERE id_profesor = ? AND id_curso = ?");
    $check->execute([$user['id_usuario'], $id_curso]);
    if ($check->fetchColumn() == 0) {
        throw new Exception('No tiene permiso para este curso');
    }

    $sql = "INSERT INTO tareas (id_curso, id_profesor, titulo, descripcion, fecha_asignacion, fecha_entrega, puntaje_maximo, estado)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'activo')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $id_curso,
        $user['id_usuario'],
        $titulo,
        $descripcion,
        $fecha_entrega,
        $puntaje_maximo
    ]);

    echo json_encode(['success' => true, 'message' => 'Tarea creada correctamente']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
