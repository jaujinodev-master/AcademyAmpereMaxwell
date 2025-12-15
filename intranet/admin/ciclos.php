<?php
/**
 * Gestión de Ciclos Académicos - Academia Ampere Maxwell
 * CRUD completo de ciclos académicos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAdmin();

$user = Auth::getUser();
$pageTitle = 'Ciclos Académicos';

$db = new Database();
$conn = $db->getConnection();

$message = null;
$messageType = 'info';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $nombre = trim($_POST['nombre_ciclo']);
            $descripcion = trim($_POST['descripcion']);
            $modalidad = $_POST['modalidad'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $costo = (float)$_POST['costo'];
            $cupos = (int)$_POST['cupos_disponibles'];
            
            if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
                throw new Exception('Complete los campos obligatorios');
            }
            
            if (strtotime($fecha_fin) <= strtotime($fecha_inicio)) {
                throw new Exception('La fecha de fin debe ser posterior a la de inicio');
            }
            
            $sql = "INSERT INTO ciclos_academicos (nombre_ciclo, descripcion, modalidad, fecha_inicio, fecha_fin, costo, cupos_disponibles) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $modalidad, $fecha_inicio, $fecha_fin, $costo, $cupos]);
            
            $message = 'Ciclo académico creado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'update') {
        try {
            $id = (int)$_POST['ciclo_id'];
            $nombre = trim($_POST['nombre_ciclo']);
            $descripcion = trim($_POST['descripcion']);
            $modalidad = $_POST['modalidad'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $costo = (float)$_POST['costo'];
            $cupos = (int)$_POST['cupos_disponibles'];
            $estado = $_POST['estado'];
            
            $sql = "UPDATE ciclos_academicos SET nombre_ciclo = ?, descripcion = ?, modalidad = ?, 
                    fecha_inicio = ?, fecha_fin = ?, costo = ?, cupos_disponibles = ?, estado = ? 
                    WHERE id_ciclo = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $modalidad, $fecha_inicio, $fecha_fin, $costo, $cupos, $estado, $id]);
            
            $message = 'Ciclo actualizado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        try {
            $id = (int)$_POST['ciclo_id'];
            
            // Verificar matrículas
            $check = $conn->prepare("SELECT COUNT(*) FROM matriculas WHERE id_ciclo = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar: tiene matrículas asociadas');
            }
            
            $conn->prepare("DELETE FROM ciclos_academicos WHERE id_ciclo = ?")->execute([$id]);
            $message = 'Ciclo eliminado exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Obtener ciclos
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT c.*, (SELECT COUNT(*) FROM matriculas m WHERE m.id_ciclo = c.id_ciclo) as total_matriculas,
        (SELECT COUNT(*) FROM cursos cr WHERE cr.id_ciclo = c.id_ciclo) as total_cursos
        FROM ciclos_academicos c WHERE 1=1";

if ($filter === 'activo') $sql .= " AND c.estado = 'activo'";
elseif ($filter === 'inactivo') $sql .= " AND c.estado = 'inactivo'";
elseif ($filter === 'finalizado') $sql .= " AND c.estado = 'finalizado'";

$sql .= " ORDER BY c.fecha_inicio DESC";
$ciclos = $conn->query($sql)->fetchAll();
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
        .ciclo-card { 
            background: var(--dash-bg-card); 
            border-radius: 16px; 
            padding: 24px;
            border: 1px solid var(--dash-border);
            transition: all var(--transition-normal);
        }
        .ciclo-card:hover { 
            box-shadow: var(--dash-shadow-lg);
            transform: translateY(-4px);
        }
        .ciclo-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; }
        .ciclo-title { font-size: 1.15rem; font-weight: 600; color: var(--dash-text-primary); }
        .ciclo-dates { display: flex; gap: 16px; margin: 12px 0; font-size: 0.9rem; color: var(--dash-text-muted); }
        .ciclo-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px; }
        .stat-item { text-align: center; padding: 12px; background: var(--dash-bg-main); border-radius: 10px; }
        .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--dash-primary); }
        .stat-label { font-size: 0.75rem; color: var(--dash-text-muted); }
        .ciclo-actions { display: flex; gap: 8px; }
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
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 20px; border: 2px solid var(--dash-border); background: transparent; font-size: 0.85rem; cursor: pointer; text-decoration: none; color: var(--dash-text-secondary); transition: all var(--transition-fast); }
        .filter-tab:hover, .filter-tab.active { background: var(--dash-primary); border-color: var(--dash-primary); color: #fff; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .cards-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                    <h2 style="font-size: 1.3rem; font-weight: 600;">Ciclos Académicos</h2>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i> Nuevo Ciclo
                    </button>
                </div>
                
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">Todos</a>
                    <a href="?filter=activo" class="filter-tab <?= $filter === 'activo' ? 'active' : '' ?>">Activos</a>
                    <a href="?filter=inactivo" class="filter-tab <?= $filter === 'inactivo' ? 'active' : '' ?>">Inactivos</a>
                    <a href="?filter=finalizado" class="filter-tab <?= $filter === 'finalizado' ? 'active' : '' ?>">Finalizados</a>
                </div>
                
                <?php if (empty($ciclos)): ?>
                    <div class="card" style="text-align: center; padding: 60px;">
                        <i class="fas fa-calendar-alt" style="font-size: 3rem; color: var(--dash-text-muted); margin-bottom: 16px;"></i>
                        <p style="color: var(--dash-text-muted);">No hay ciclos académicos</p>
                    </div>
                <?php else: ?>
                    <div class="cards-grid">
                        <?php foreach ($ciclos as $c): ?>
                            <div class="ciclo-card">
                                <div class="ciclo-header">
                                    <div>
                                        <div class="ciclo-title"><?= htmlspecialchars($c['nombre_ciclo']) ?></div>
                                        <span class="badge badge-<?= $c['modalidad'] === 'presencial' ? 'primary' : ($c['modalidad'] === 'virtual' ? 'info' : 'warning') ?>">
                                            <?= ucfirst($c['modalidad']) ?>
                                        </span>
                                    </div>
                                    <span class="badge badge-<?= $c['estado'] === 'activo' ? 'success' : ($c['estado'] === 'finalizado' ? 'danger' : 'secondary') ?>">
                                        <?= ucfirst($c['estado']) ?>
                                    </span>
                                </div>
                                
                                <div class="ciclo-dates">
                                    <span><i class="fas fa-calendar-check"></i> <?= date('d/m/Y', strtotime($c['fecha_inicio'])) ?></span>
                                    <span><i class="fas fa-calendar-times"></i> <?= date('d/m/Y', strtotime($c['fecha_fin'])) ?></span>
                                </div>
                                
                                <div class="ciclo-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?= $c['total_matriculas'] ?></div>
                                        <div class="stat-label">Matrículas</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?= $c['total_cursos'] ?></div>
                                        <div class="stat-label">Cursos</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">S/ <?= number_format($c['costo'], 0) ?></div>
                                        <div class="stat-label">Costo</div>
                                    </div>
                                </div>
                                
                                <div class="ciclo-actions" style="margin-top: 16px;">
                                    <button class="btn btn-outline btn-sm" onclick='editCiclo(<?= json_encode($c) ?>)'>
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este ciclo?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ciclo_id" value="<?= $c['id_ciclo'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <!-- Modal -->
    <div class="modal-form" id="cicloModal">
        <div class="modal-content-form">
            <div class="modal-header-form">
                <h3 id="modalTitle" style="font-size: 1.2rem; font-weight: 600;">Nuevo Ciclo</h3>
                <button class="close-modal-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body-form">
                <form method="POST" id="cicloForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="ciclo_id" id="cicloId" value="">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Nombre del Ciclo *</label>
                            <input type="text" name="nombre_ciclo" id="inputNombre" required placeholder="Ej: Ciclo Verano 2025">
                        </div>
                        <div class="form-group">
                            <label>Fecha Inicio *</label>
                            <input type="date" name="fecha_inicio" id="inputFechaInicio" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Fin *</label>
                            <input type="date" name="fecha_fin" id="inputFechaFin" required>
                        </div>
                        <div class="form-group">
                            <label>Modalidad *</label>
                            <select name="modalidad" id="inputModalidad" required>
                                <option value="presencial">Presencial</option>
                                <option value="virtual">Virtual</option>
                                <option value="hibrido">Híbrido</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Costo (S/)</label>
                            <input type="number" name="costo" id="inputCosto" step="0.01" min="0" placeholder="500.00">
                        </div>
                        <div class="form-group">
                            <label>Cupos Disponibles</label>
                            <input type="number" name="cupos_disponibles" id="inputCupos" min="0" placeholder="50">
                        </div>
                        <div class="form-group" id="estadoGroup" style="display: none;">
                            <label>Estado</label>
                            <select name="estado" id="inputEstado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="finalizado">Finalizado</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Descripción</label>
                            <textarea name="descripcion" id="inputDescripcion" rows="3" placeholder="Descripción del ciclo..."></textarea>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> <span id="submitText">Guardar Ciclo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('cicloModal');
        const form = document.getElementById('cicloForm');
        
        function openModal() {
            form.reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('cicloId').value = '';
            document.getElementById('modalTitle').textContent = 'Nuevo Ciclo';
            document.getElementById('submitText').textContent = 'Guardar Ciclo';
            document.getElementById('estadoGroup').style.display = 'none';
            modal.classList.add('show');
        }
        
        function closeModal() {
            modal.classList.remove('show');
            form.reset();
        }
        
        function editCiclo(c) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('cicloId').value = c.id_ciclo;
            document.getElementById('modalTitle').textContent = 'Editar Ciclo';
            document.getElementById('submitText').textContent = 'Actualizar Ciclo';
            document.getElementById('estadoGroup').style.display = 'block';
            
            document.getElementById('inputNombre').value = c.nombre_ciclo || '';
            document.getElementById('inputDescripcion').value = c.descripcion || '';
            document.getElementById('inputModalidad').value = c.modalidad;
            document.getElementById('inputFechaInicio').value = c.fecha_inicio;
            document.getElementById('inputFechaFin').value = c.fecha_fin;
            document.getElementById('inputCosto').value = c.costo || '';
            document.getElementById('inputCupos').value = c.cupos_disponibles || '';
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
