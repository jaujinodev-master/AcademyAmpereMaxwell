<?php
/**
 * Perfil de Usuario - Academia Ampere Maxwell
 * Página común para todos los roles
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireLogin();

$user = Auth::getUser();
$pageTitle = 'Mi Perfil';

$db = new Database();
$conn = $db->getConnection();

$message = null;
$messageType = 'info';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        try {
            $nombres = trim($_POST['nombres']);
            $apellidos = trim($_POST['apellidos']);
            $telefono = trim($_POST['telefono']);
            $direccion = trim($_POST['direccion']);
            $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
            
            if (empty($nombres) || empty($apellidos)) {
                throw new Exception('Nombres y apellidos son obligatorios');
            }
            
            $sql = "UPDATE usuarios SET nombres = ?, apellidos = ?, telefono = ?, direccion = ?, fecha_nacimiento = ? WHERE id_usuario = ?";
            $conn->prepare($sql)->execute([$nombres, $apellidos, $telefono, $direccion, $fecha_nacimiento, $user['id_usuario']]);
            
            // Actualizar sesión
            $_SESSION['user']['nombres'] = $nombres;
            $_SESSION['user']['apellidos'] = $apellidos;
            $_SESSION['user_name'] = $nombres . ' ' . $apellidos;
            
            $message = 'Perfil actualizado exitosamente';
            $messageType = 'success';
            
            // Recargar datos
            $user = Auth::getUser();
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'change_password') {
        try {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];
            
            if (empty($current) || empty($new) || empty($confirm)) {
                throw new Exception('Complete todos los campos');
            }
            
            if ($new !== $confirm) {
                throw new Exception('Las contraseñas no coinciden');
            }
            
            if (strlen($new) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            // Verificar contraseña actual
            $stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$user['id_usuario']]);
            $userData = $stmt->fetch();
            
            if (!password_verify($current, $userData['password_hash'])) {
                throw new Exception('La contraseña actual es incorrecta');
            }
            
            // Actualizar contraseña
            $newHash = password_hash($new, HASH_ALGO, ['cost' => HASH_COST]);
            $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?")->execute([$newHash, $user['id_usuario']]);
            
            $message = 'Contraseña actualizada exitosamente';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'upload_avatar') {
        try {
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir la imagen');
            }
            
            $file = $_FILES['avatar'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowed)) {
                throw new Exception('Formato no permitido. Use JPG, PNG, GIF o WebP');
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('La imagen no debe superar 2MB');
            }
            
            // Crear directorio si no existe
            $uploadDir = __DIR__ . '/../../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user['id_usuario'] . '_' . time() . '.' . $extension;
            
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                // Eliminar avatar anterior si existe
                if ($user['foto_perfil'] && file_exists($uploadDir . $user['foto_perfil'])) {
                    unlink($uploadDir . $user['foto_perfil']);
                }
                
                // Actualizar BD
                $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?")->execute([$filename, $user['id_usuario']]);
                $_SESSION['user']['foto_perfil'] = $filename;
                
                $message = 'Foto de perfil actualizada';
                $messageType = 'success';
                $user = Auth::getUser();
            } else {
                throw new Exception('Error al guardar la imagen');
            }
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Obtener datos frescos del usuario
$stmt = $conn->prepare("SELECT u.*, r.nombre_rol FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol WHERE u.id_usuario = ?");
$stmt->execute([$user['id_usuario']]);
$userData = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2.0">
    <link rel="icon" href="../../assets/images/AMPEREMAXWELL.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .profile-container { display: grid; grid-template-columns: 300px 1fr; gap: 24px; }
        .profile-sidebar { background: var(--dash-bg-card); border-radius: 16px; padding: 30px; text-align: center; border: 1px solid var(--dash-border); height: fit-content; }
        .profile-avatar { position: relative; display: inline-block; margin-bottom: 20px; }
        .profile-avatar img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--dash-primary); }
        .avatar-upload { position: absolute; bottom: 5px; right: 5px; background: var(--dash-primary); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; }
        .avatar-upload:hover { background: var(--dash-accent); transform: scale(1.1); }
        .avatar-upload input { display: none; }
        .profile-name { font-size: 1.4rem; font-weight: 700; margin-bottom: 5px; }
        .profile-role { color: var(--dash-text-muted); margin-bottom: 15px; }
        .profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; }
        .stat { background: var(--dash-bg-main); padding: 12px; border-radius: 10px; }
        .stat-value { font-size: 1.2rem; font-weight: 700; color: var(--dash-primary); }
        .stat-label { font-size: 0.75rem; color: var(--dash-text-muted); }
        .profile-content { display: flex; flex-direction: column; gap: 24px; }
        .profile-section { background: var(--dash-bg-card); border-radius: 16px; padding: 24px; border: 1px solid var(--dash-border); }
        .section-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--dash-primary); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; border: 2px solid var(--dash-border); border-radius: 10px; font-size: 0.95rem; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--dash-primary); }
        .form-group input:disabled { background: var(--dash-bg-main); cursor: not-allowed; }
        @media (max-width: 992px) { .profile-container { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <h2 style="font-size: 1.3rem; font-weight: 600; margin-bottom: 24px;">Mi Perfil</h2>
                
                <div class="profile-container">
                    <!-- Sidebar -->
                    <div class="profile-sidebar">
                        <div class="profile-avatar">
                            <img src="<?= Auth::getAvatarUrl($userData['foto_perfil']) ?>" alt="Avatar" id="avatarPreview"
                                 onerror="this.src='../assets/images/default-avatar.svg'">
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <input type="hidden" name="action" value="upload_avatar">
                                <label class="avatar-upload" title="Cambiar foto">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" name="avatar" accept="image/*" onchange="previewAndUpload(this)">
                                </label>
                            </form>
                        </div>
                        <div class="profile-name"><?= htmlspecialchars($userData['nombres'] . ' ' . $userData['apellidos']) ?></div>
                        <div class="profile-role">
                            <span class="badge badge-primary"><?= htmlspecialchars($userData['nombre_rol']) ?></span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--dash-text-muted);">@<?= htmlspecialchars($userData['username']) ?></p>
                        
                        <div class="profile-stats">
                            <div class="stat">
                                <div class="stat-value"><?= date('d/m/Y', strtotime($userData['fecha_creacion'])) ?></div>
                                <div class="stat-label">Registro</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $userData['ultimo_acceso'] ? date('d/m/Y', strtotime($userData['ultimo_acceso'])) : 'Hoy' ?></div>
                                <div class="stat-label">Último acceso</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="profile-content">
                        <!-- Información Personal -->
                        <div class="profile-section">
                            <h3 class="section-title"><i class="fas fa-user"></i> Información Personal</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Nombres *</label>
                                        <input type="text" name="nombres" value="<?= htmlspecialchars($userData['nombres']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Apellidos *</label>
                                        <input type="text" name="apellidos" value="<?= htmlspecialchars($userData['apellidos']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" value="<?= htmlspecialchars($userData['email']) ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>DNI</label>
                                        <input type="text" value="<?= htmlspecialchars($userData['dni'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="text" name="telefono" value="<?= htmlspecialchars($userData['telefono'] ?? '') ?>" placeholder="+51 999 999 999">
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha de Nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" value="<?= $userData['fecha_nacimiento'] ?? '' ?>">
                                    </div>
                                    <div class="form-group full-width">
                                        <label>Dirección</label>
                                        <textarea name="direccion" rows="2" placeholder="Dirección completa"><?= htmlspecialchars($userData['direccion'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div style="text-align: right; margin-top: 12px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Cambiar Contraseña -->
                        <div class="profile-section">
                            <h3 class="section-title"><i class="fas fa-lock"></i> Cambiar Contraseña</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Contraseña Actual *</label>
                                        <input type="password" name="current_password" required placeholder="••••••••">
                                    </div>
                                    <div class="form-group">
                                        <label>Nueva Contraseña *</label>
                                        <input type="password" name="new_password" required minlength="6" placeholder="Mínimo 6 caracteres">
                                    </div>
                                    <div class="form-group">
                                        <label>Confirmar Nueva Contraseña *</label>
                                        <input type="password" name="confirm_password" required minlength="6" placeholder="Repite la contraseña">
                                    </div>
                                </div>
                                <div style="text-align: right; margin-top: 12px;">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function previewAndUpload(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
                
                // Auto submit
                document.getElementById('avatarForm').submit();
            }
        }
        
        <?php if ($message): ?>
            <?php if ($messageType === 'success'): ?>
                showSuccess('<?= addslashes($message) ?>');
            <?php else: ?>
                showError('<?= addslashes($message) ?>');
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>
