<?php
/**
 * Gestión de Cursos - Academia Ampere Maxwell
 * CRUD completo de cursos por ciclo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAdmin();

$user = Auth::getUser();
$pageTitle = 'Gestión de Cursos';

$db = new Database();
$conn = $db->getConnection();

$message = null;
$messageType = 'info';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $nombre = trim($_POST['nombre_curso']);
            $codigo = trim($_POST['codigo_curso']);
            $id_ciclo = (int)$_POST['id_ciclo'];
            $descripcion = trim($_POST['descripcion']);
            $creditos = (int)$_POST['creditos'];
            $horas = (int)$_POST['horas_semanales'];
            
            if (empty($nombre) || empty($id_ciclo)) {
                throw new Exception('Complete los campos obligatorios');
            }
            
            // Verificar código único
            if (!empty($codigo)) {
                $check = $conn->prepare("SELECT id_curso FROM cursos WHERE codigo_curso = ?");
                $check->execute([$codigo]);
                if ($check->fetch()) throw new Exception('El código de curso ya existe');
            }
            
            $sql = "INSERT INTO cursos (id_ciclo, nombre_curso, codigo_curso, descripcion, creditos, horas_semanales) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $conn->prepare($sql)->execute([$id_ciclo, $nombre, $codigo ?: null, $descripcion, $creditos, $horas]);
            
            $message = 'Curso creado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'update') {
        try {
            $id = (int)$_POST['curso_id'];
            $nombre = trim($_POST['nombre_curso']);
            $codigo = trim($_POST['codigo_curso']);
            $id_ciclo = (int)$_POST['id_ciclo'];
            $descripcion = trim($_POST['descripcion']);
            $creditos = (int)$_POST['creditos'];
            $horas = (int)$_POST['horas_semanales'];
            $estado = $_POST['estado'];
            
            $sql = "UPDATE cursos SET id_ciclo = ?, nombre_curso = ?, codigo_curso = ?, 
                    descripcion = ?, creditos = ?, horas_semanales = ?, estado = ? WHERE id_curso = ?";
            $conn->prepare($sql)->execute([$id_ciclo, $nombre, $codigo ?: null, $descripcion, $creditos, $horas, $estado, $id]);
            
            $message = 'Curso actualizado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        try {
            $id = (int)$_POST['curso_id'];
            $conn->prepare("DELETE FROM cursos WHERE id_curso = ?")->execute([$id]);
            $message = 'Curso eliminado exitosamente';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'No se puede eliminar: tiene registros asociados';
            $messageType = 'error';
        }
    }
}

// Obtener cursos con info del ciclo
$ciclo_filter = $_GET['ciclo'] ?? '';
$sql = "SELECT c.*, ca.nombre_ciclo, ca.estado as ciclo_estado,
        (SELECT COUNT(*) FROM inscripciones_curso ic WHERE ic.id_curso = c.id_curso) as total_inscritos,
        (SELECT COUNT(*) FROM profesor_curso pc WHERE pc.id_curso = c.id_curso) as total_profesores
        FROM cursos c
        INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
        WHERE 1=1";

if (!empty($ciclo_filter)) {
    $sql .= " AND c.id_ciclo = " . (int)$ciclo_filter;
}
$sql .= " ORDER BY ca.fecha_inicio DESC, c.nombre_curso";
$cursos = $conn->query($sql)->fetchAll();

// Obtener ciclos para el select
$ciclos = $conn->query("SELECT * FROM ciclos_academicos ORDER BY fecha_inicio DESC")->fetchAll();
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
        .modal-form { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-form.show { display: flex; }
        .modal-content-form { background: var(--dash-bg-card); border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .modal-header-form { padding: 20px 24px; border-bottom: 1px solid var(--dash-border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body-form { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 2px solid var(--dash-border); border-radius: 10px; font-size: 0.95rem; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--dash-primary); }
        .close-modal-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--dash-text-muted); }
        .curso-actions { display: flex; gap: 8px; }
        .curso-actions button { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn-edit { background: var(--dash-info); color: #fff; }
        .btn-delete { background: var(--dash-danger); color: #fff; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                    <h2 style="font-size: 1.3rem; font-weight: 600;">Gestión de Cursos</h2>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i> Nuevo Curso
                    </button>
                </div>
                
                <!-- Filtro por ciclo -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body" style="padding: 16px;">
                        <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <label style="font-weight: 500;">Filtrar por Ciclo:</label>
                            <select name="ciclo" onchange="this.form.submit()" style="padding: 8px 12px; border: 2px solid var(--dash-border); border-radius: 8px; min-width: 250px;">
                                <option value="">Todos los ciclos</option>
                                <?php foreach ($ciclos as $ci): ?>
                                    <option value="<?= $ci['id_ciclo'] ?>" <?= $ciclo_filter == $ci['id_ciclo'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ci['nombre_ciclo']) ?> (<?= $ci['estado'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($ciclo_filter): ?>
                                <a href="cursos.php" class="btn btn-outline btn-sm">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de cursos -->
                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Curso</th>
                                        <th>Ciclo</th>
                                        <th>Créditos</th>
                                        <th>Horas/Sem</th>
                                        <th>Inscritos</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cursos)): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--dash-text-muted);">
                                                <i class="fas fa-book" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                                No hay cursos registrados
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($cursos as $c): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars($c['codigo_curso'] ?? '-') ?></code></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($c['nombre_curso']) ?></strong>
                                                    <?php if ($c['descripcion']): ?>
                                                        <br><small style="color: var(--dash-text-muted);"><?= htmlspecialchars(substr($c['descripcion'], 0, 50)) ?>...</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $c['ciclo_estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                                        <?= htmlspecialchars($c['nombre_ciclo']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $c['creditos'] ?></td>
                                                <td><?= $c['horas_semanales'] ?>h</td>
                                                <td><?= $c['total_inscritos'] ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $c['estado'] === 'activo' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($c['estado']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="curso-actions">
                                                        <button class="btn-edit" onclick='editCurso(<?= json_encode($c) ?>)' title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este curso?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="curso_id" value="<?= $c['id_curso'] ?>">
                                                            <button type="submit" class="btn-delete" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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
    
    <!-- Modal -->
    <div class="modal-form" id="cursoModal">
        <div class="modal-content-form">
            <div class="modal-header-form">
                <h3 id="modalTitle" style="font-size: 1.2rem; font-weight: 600;">Nuevo Curso</h3>
                <button class="close-modal-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body-form">
                <form method="POST" id="cursoForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="curso_id" id="cursoId" value="">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Nombre del Curso *</label>
                            <input type="text" name="nombre_curso" id="inputNombre" required placeholder="Ej: Matemática Avanzada">
                        </div>
                        <div class="form-group">
                            <label>Código del Curso</label>
                            <input type="text" name="codigo_curso" id="inputCodigo" placeholder="MAT-101">
                        </div>
                        <div class="form-group">
                            <label>Ciclo Académico *</label>
                            <select name="id_ciclo" id="inputCiclo" required>
                                <option value="">Seleccionar ciclo</option>
                                <?php foreach ($ciclos as $ci): ?>
                                    <option value="<?= $ci['id_ciclo'] ?>"><?= htmlspecialchars($ci['nombre_ciclo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Créditos</label>
                            <input type="number" name="creditos" id="inputCreditos" min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label>Horas Semanales</label>
                            <input type="number" name="horas_semanales" id="inputHoras" min="1" placeholder="4">
                        </div>
                        <div class="form-group" id="estadoGroup" style="display: none;">
                            <label>Estado</label>
                            <select name="estado" id="inputEstado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Descripción</label>
                            <textarea name="descripcion" id="inputDescripcion" rows="3" placeholder="Descripción del curso..."></textarea>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <span id="submitText">Guardar Curso</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('cursoModal');
        const form = document.getElementById('cursoForm');
        
        function openModal() {
            form.reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('cursoId').value = '';
            document.getElementById('modalTitle').textContent = 'Nuevo Curso';
            document.getElementById('submitText').textContent = 'Guardar Curso';
            document.getElementById('estadoGroup').style.display = 'none';
            modal.classList.add('show');
        }
        
        function closeModal() {
            modal.classList.remove('show');
            form.reset();
        }
        
        function editCurso(c) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('cursoId').value = c.id_curso;
            document.getElementById('modalTitle').textContent = 'Editar Curso';
            document.getElementById('submitText').textContent = 'Actualizar Curso';
            document.getElementById('estadoGroup').style.display = 'block';
            
            document.getElementById('inputNombre').value = c.nombre_curso || '';
            document.getElementById('inputCodigo').value = c.codigo_curso || '';
            document.getElementById('inputCiclo').value = c.id_ciclo;
            document.getElementById('inputCreditos').value = c.creditos || 1;
            document.getElementById('inputHoras').value = c.horas_semanales || '';
            document.getElementById('inputDescripcion').value = c.descripcion || '';
            document.getElementById('inputEstado').value = c.estado;
            
            modal.classList.add('show');
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
