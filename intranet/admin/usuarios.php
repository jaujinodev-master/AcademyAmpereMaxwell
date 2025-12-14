<?php
/**
 * Gestión de Usuarios - Academia Ampere Maxwell
 * 
 * CRUD completo de usuarios (alumnos, profesores)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

// Verificar que sea administrador
requireAdmin();

$user = Auth::getUser();
$pageTitle = 'Gestión de Usuarios';

// Conexión a base de datos
$db = new Database();
$conn = $db->getConnection();

// Procesar acciones POST
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $nombres = trim($_POST['nombres']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $dni = trim($_POST['dni']);
            $telefono = trim($_POST['telefono']);
            $id_rol = (int)$_POST['id_rol'];
            
            // Validar campos requeridos
            if (empty($nombres) || empty($apellidos) || empty($email) || empty($username) || empty($password)) {
                throw new Exception('Por favor complete todos los campos obligatorios');
            }
            
            // Verificar email único
            $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está registrado');
            }
            
            // Verificar username único
            $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception('El nombre de usuario ya existe');
            }
            
            // Hash de contraseña
            $passwordHash = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
            
            // Insertar usuario
            $sql = "INSERT INTO usuarios (id_rol, username, email, password_hash, nombres, apellidos, dni, telefono, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id_rol, $username, $email, $passwordHash, $nombres, $apellidos, $dni, $telefono]);
            
            $message = 'Usuario creado exitosamente';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'toggle_status') {
        try {
            $userId = (int)$_POST['user_id'];
            $newStatus = $_POST['new_status'];
            
            $stmt = $conn->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
            $stmt->execute([$newStatus, $userId]);
            
            $message = 'Estado actualizado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        try {
            $userId = (int)$_POST['user_id'];
            
            // No permitir eliminar al propio usuario
            if ($userId === Auth::getUserId()) {
                throw new Exception('No puede eliminar su propia cuenta');
            }
            
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$userId]);
            
            $message = 'Usuario eliminado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Obtener lista de usuarios
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT u.*, r.nombre_rol FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol WHERE 1=1";
$params = [];

if ($filter === 'alumnos') {
    $sql .= " AND u.id_rol = 3";
} elseif ($filter === 'profesores') {
    $sql .= " AND u.id_rol = 2";
} elseif ($filter === 'admins') {
    $sql .= " AND u.id_rol = 1";
}

if (!empty($search)) {
    $sql .= " AND (u.nombres LIKE ? OR u.apellidos LIKE ? OR u.email LIKE ? OR u.dni LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

$sql .= " ORDER BY u.fecha_creacion DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Obtener roles para el select
$roles = $conn->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();

// Ver si hay acción de nuevo usuario
$showModal = isset($_GET['action']) && $_GET['action'] === 'new';
$presetRole = $_GET['type'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="icon" href="../../assets/images/AMPEREMAXWELL.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .modal-form {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
        }
        .modal-form.show { display: flex; }
        .modal-content-form {
            background: var(--dash-bg-card);
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--dash-shadow-xl);
        }
        .modal-header-form {
            padding: 20px 24px;
            border-bottom: 1px solid var(--dash-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body-form { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; color: var(--dash-text-secondary); }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--dash-border);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all var(--transition-fast);
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--dash-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dash-text-muted);
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid var(--dash-border);
            background: transparent;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            color: var(--dash-text-secondary);
        }
        .filter-tab:hover, .filter-tab.active {
            background: var(--dash-primary);
            border-color: var(--dash-primary);
            color: #fff;
        }
        .user-actions { display: flex; gap: 8px; }
        .user-actions button {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all var(--transition-fast);
        }
        .btn-edit { background: var(--dash-info); color: #fff; }
        .btn-delete { background: var(--dash-danger); color: #fff; }
        .btn-toggle { background: var(--dash-warning); color: #fff; }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Action Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                    <h2 style="font-size: 1.3rem; font-weight: 600;">Gestión de Usuarios</h2>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">Todos</a>
                    <a href="?filter=alumnos" class="filter-tab <?= $filter === 'alumnos' ? 'active' : '' ?>">Alumnos</a>
                    <a href="?filter=profesores" class="filter-tab <?= $filter === 'profesores' ? 'active' : '' ?>">Profesores</a>
                    <a href="?filter=admins" class="filter-tab <?= $filter === 'admins' ? 'active' : '' ?>">Administradores</a>
                </div>
                
                <!-- Search -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body" style="padding: 16px;">
                        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <div style="flex: 1; position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--dash-text-muted);"></i>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Buscar por nombre, email o DNI..." 
                                       style="width: 100%; padding: 10px 14px 10px 40px; border: 2px solid var(--dash-border); border-radius: 10px;">
                            </div>
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            <?php if ($search): ?>
                                <a href="?filter=<?= $filter ?>" class="btn btn-outline">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>DNI</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--dash-text-muted);">
                                                <i class="fas fa-users" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                                No se encontraron usuarios
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios as $u): ?>
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <img src="<?= Auth::getAvatarUrl($u['foto_perfil']) ?>" 
                                                             alt="" 
                                                             style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover;"
                                                             onerror="this.src='../assets/images/default-avatar.svg'">
                                                        <div>
                                                            <strong><?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?></strong>
                                                            <br><small style="color: var(--dash-text-muted);">@<?= htmlspecialchars($u['username']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td><?= htmlspecialchars($u['dni'] ?? '-') ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $u['id_rol'] == 1 ? 'danger' : ($u['id_rol'] == 2 ? 'info' : 'primary') ?>">
                                                        <?= htmlspecialchars($u['nombre_rol']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $u['estado'] === 'activo' ? 'success' : ($u['estado'] === 'suspendido' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($u['estado']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></td>
                                                <td>
                                                    <div class="user-actions">
                                                        <button class="btn-edit" onclick="editUser(<?= $u['id_usuario'] ?>)" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($u['id_usuario'] !== Auth::getUserId()): ?>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Cambiar estado del usuario?')">
                                                                <input type="hidden" name="action" value="toggle_status">
                                                                <input type="hidden" name="user_id" value="<?= $u['id_usuario'] ?>">
                                                                <input type="hidden" name="new_status" value="<?= $u['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                                                                <button type="submit" class="btn-toggle" title="<?= $u['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?>">
                                                                    <i class="fas fa-<?= $u['estado'] === 'activo' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="user_id" value="<?= $u['id_usuario'] ?>">
                                                                <button type="submit" class="btn-delete" title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <!-- Modal Nuevo Usuario -->
    <div class="modal-form <?= $showModal ? 'show' : '' ?>" id="userModal">
        <div class="modal-content-form">
            <div class="modal-header-form">
                <h3 style="font-size: 1.2rem; font-weight: 600;">Nuevo Usuario</h3>
                <button class="close-modal-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body-form">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombres *</label>
                            <input type="text" name="nombres" required placeholder="Nombres completos">
                        </div>
                        <div class="form-group">
                            <label>Apellidos *</label>
                            <input type="text" name="apellidos" required placeholder="Apellidos completos">
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required placeholder="correo@ejemplo.com">
                        </div>
                        <div class="form-group">
                            <label>Usuario *</label>
                            <input type="text" name="username" required placeholder="nombre.usuario">
                        </div>
                        <div class="form-group">
                            <label>Contraseña *</label>
                            <input type="password" name="password" required placeholder="Mínimo 6 caracteres" minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Rol *</label>
                            <select name="id_rol" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id_rol'] ?>" <?= ($presetRole === 'alumno' && $rol['id_rol'] == 3) || ($presetRole === 'profesor' && $rol['id_rol'] == 2) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rol['nombre_rol']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>DNI</label>
                            <input type="text" name="dni" placeholder="12345678" maxlength="8">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" placeholder="+51 999 999 999">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('userModal');
        
        function openModal() {
            modal.classList.add('show');
        }
        
        function closeModal() {
            modal.classList.remove('show');
            window.history.replaceState({}, '', 'usuarios.php<?= $filter !== 'all' ? '?filter=' . $filter : '' ?>');
        }
        
        function editUser(id) {
            // TODO: Implementar edición
            showToast('Funcionalidad de edición próximamente', 'info');
        }
        
        // Mostrar mensaje si existe
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
