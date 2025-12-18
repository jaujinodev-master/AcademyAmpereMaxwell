<?php
/**
 * save_material.php
 * Procesa la subida de materiales educativos
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
    $tipo_material = $_POST['tipo_material'];
    $url_enlace = trim($_POST['url_enlace'] ?? '');
    
    if (empty($titulo)) {
        throw new Exception('El título es requerido');
    }

    // Validar que el profesor tenga asignado el curso
    $check = $conn->prepare("SELECT COUNT(*) FROM profesor_curso WHERE id_profesor = ? AND id_curso = ?");
    $check->execute([$user['id_usuario'], $id_curso]);
    if ($check->fetchColumn() == 0) {
        throw new Exception('No tiene permiso para este curso');
    }

    $ruta_archivo = null;
    $tamanio_archivo = null;

    // Procesar archivo si no es enlace
    if ($tipo_material !== 'enlace' && isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo'];
        
        // Validar tamaño (10MB max)
        $maxSize = 10 * 1024 * 1024;
        if ($archivo['size'] > $maxSize) {
            throw new Exception('El archivo excede el tamaño máximo de 10MB');
        }

        // Extensiones permitidas
        $extensionesPermitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'zip', 'jpg', 'png'];
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $extensionesPermitidas)) {
            throw new Exception('Tipo de archivo no permitido');
        }

        // Generar nombre único
        $nombreUnico = uniqid('mat_') . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $archivo['name']);
        $rutaDestino = __DIR__ . '/../../../uploads/materiales/' . $nombreUnico;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new Exception('Error al guardar el archivo');
        }

        $ruta_archivo = $nombreUnico;
        $tamanio_archivo = $archivo['size'];

    } elseif ($tipo_material === 'enlace' && empty($url_enlace)) {
        throw new Exception('Debe proporcionar una URL para el enlace');
    }

    // Insertar en base de datos
    $sql = "INSERT INTO materiales_educativos 
            (id_curso, id_profesor, titulo, descripcion, tipo_material, ruta_archivo, url_enlace, tamanio_archivo, fecha_publicacion, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'activo')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $id_curso,
        $user['id_usuario'],
        $titulo,
        $descripcion,
        $tipo_material,
        $ruta_archivo,
        $tipo_material === 'enlace' ? $url_enlace : null,
        $tamanio_archivo
    ]);

    echo json_encode(['success' => true, 'message' => 'Material guardado correctamente']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
