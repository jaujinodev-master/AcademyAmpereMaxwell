<?php
/**
 * Gestión de Matrículas - Academia Ampere Maxwell
 * Registro y gestión de matrículas de alumnos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAdmin();

$user = Auth::getUser();
$pageTitle = 'Gestión de Matrículas';

$db = new Database();
$conn = $db->getConnection();

$message = null;
$messageType = 'info';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $id_alumno = (int)$_POST['id_alumno'];
            $id_ciclo = (int)$_POST['id_ciclo'];
            $fecha = $_POST['fecha_matricula'];
            $monto = (float)$_POST['monto_pagado'];
            $estado_pago = $_POST['estado_pago'];
            $observaciones = trim($_POST['observaciones']);
            
            // Verificar matrícula duplicada
            $check = $conn->prepare("SELECT id_matricula FROM matriculas WHERE id_alumno = ? AND id_ciclo = ?");
            $check->execute([$id_alumno, $id_ciclo]);
            if ($check->fetch()) throw new Exception('El alumno ya está matriculado en este ciclo');
            
            $sql = "INSERT INTO matriculas (id_alumno, id_ciclo, fecha_matricula, monto_pagado, estado_pago, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $conn->prepare($sql)->execute([$id_alumno, $id_ciclo, $fecha, $monto, $estado_pago, $observaciones]);
            
            $message = 'Matrícula registrada exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'update') {
        try {
            $id = (int)$_POST['matricula_id'];
            $monto = (float)$_POST['monto_pagado'];
            $estado_pago = $_POST['estado_pago'];
            $estado_matricula = $_POST['estado_matricula'];
            $observaciones = trim($_POST['observaciones']);
            
            $sql = "UPDATE matriculas SET monto_pagado = ?, estado_pago = ?, estado_matricula = ?, observaciones = ? WHERE id_matricula = ?";
            $conn->prepare($sql)->execute([$monto, $estado_pago, $estado_matricula, $observaciones, $id]);
            
            $message = 'Matrícula actualizada exitosamente';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        try {
            $id = (int)$_POST['matricula_id'];
            $conn->prepare("DELETE FROM matriculas WHERE id_matricula = ?")->execute([$id]);
            $message = 'Matrícula eliminada exitosamente';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'No se puede eliminar: tiene registros asociados';
            $messageType = 'error';
        }
    }
}

// Filtros
$ciclo_filter = $_GET['ciclo'] ?? '';
$estado_filter = $_GET['estado'] ?? '';
$search = $_GET['search'] ?? '';

// Obtener matrículas
$sql = "SELECT m.*, 
        u.nombres, u.apellidos, u.dni, u.email,
        c.nombre_ciclo, c.costo as costo_ciclo
        FROM matriculas m
        INNER JOIN usuarios u ON m.id_alumno = u.id_usuario
        INNER JOIN ciclos_academicos c ON m.id_ciclo = c.id_ciclo
        WHERE 1=1";

$params = [];
if (!empty($ciclo_filter)) {
    $sql .= " AND m.id_ciclo = ?";
    $params[] = $ciclo_filter;
}
if (!empty($estado_filter)) {
    $sql .= " AND m.estado_matricula = ?";
    $params[] = $estado_filter;
}
if (!empty($search)) {
    $sql .= " AND (u.nombres LIKE ? OR u.apellidos LIKE ? OR u.dni LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY m.fecha_matricula DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$matriculas = $stmt->fetchAll();

// Datos para selects
$ciclos = $conn->query("SELECT * FROM ciclos_academicos WHERE estado = 'activo' ORDER BY fecha_inicio DESC")->fetchAll();
$alumnos = $conn->query("SELECT id_usuario, nombres, apellidos, dni FROM usuarios WHERE id_rol = 3 AND estado = 'activo' ORDER BY apellidos")->fetchAll();
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
        .actions-cell { display: flex; gap: 8px; }
        .actions-cell button { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn-edit { background: var(--dash-info); color: #fff; }
        .btn-delete { background: var(--dash-danger); color: #fff; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--dash-bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--dash-border); }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: var(--dash-primary); }
        .stat-card .label { font-size: 0.85rem; color: var(--dash-text-muted); }
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
                    <h2 style="font-size: 1.3rem; font-weight: 600;">Gestión de Matrículas</h2>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-user-plus"></i> Nueva Matrícula
                    </button>
                </div>
                
                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="value"><?= count($matriculas) ?></div>
                        <div class="label">Total Matrículas</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?= count(array_filter($matriculas, fn($m) => $m['estado_pago'] === 'pagado')) ?></div>
                        <div class="label">Pagos Completos</div>
                    </div>
                    <div class="stat-card">
                        <div class="value">S/ <?= number_format(array_sum(array_column($matriculas, 'monto_pagado')), 2) ?></div>
                        <div class="label">Total Recaudado</div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body" style="padding: 16px;">
                        <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <select name="ciclo" style="padding: 8px 12px; border: 2px solid var(--dash-border); border-radius: 8px;">
                                <option value="">Todos los ciclos</option>
                                <?php foreach ($ciclos as $ci): ?>
                                    <option value="<?= $ci['id_ciclo'] ?>" <?= $ciclo_filter == $ci['id_ciclo'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ci['nombre_ciclo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="estado" style="padding: 8px 12px; border: 2px solid var(--dash-border); border-radius: 8px;">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?= $estado_filter === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="retirado" <?= $estado_filter === 'retirado' ? 'selected' : '' ?>>Retirado</option>
                                <option value="finalizado" <?= $estado_filter === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            </select>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar alumno..." 
                                   style="padding: 8px 12px; border: 2px solid var(--dash-border); border-radius: 8px; min-width: 200px;">
                            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                            <a href="matriculas.php" class="btn btn-outline btn-sm">Limpiar</a>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla -->
                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th>Ciclo</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Pago</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($matriculas)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--dash-text-muted);">
                                                <i class="fas fa-clipboard-list" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                                No hay matrículas registradas
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($matriculas as $m): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($m['nombres'] . ' ' . $m['apellidos']) ?></strong>
                                                    <br><small style="color: var(--dash-text-muted);">DNI: <?= htmlspecialchars($m['dni'] ?? '-') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($m['nombre_ciclo']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($m['fecha_matricula'])) ?></td>
                                                <td>S/ <?= number_format($m['monto_pagado'], 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $m['estado_pago'] === 'pagado' ? 'success' : ($m['estado_pago'] === 'parcial' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($m['estado_pago']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $m['estado_matricula'] === 'activo' ? 'success' : ($m['estado_matricula'] === 'retirado' ? 'danger' : 'secondary') ?>">
                                                        <?= ucfirst($m['estado_matricula']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="actions-cell">
                                                        <button class="btn-edit" onclick='editMatricula(<?= json_encode($m) ?>)' title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta matrícula?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="matricula_id" value="<?= $m['id_matricula'] ?>">
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
    
    <!-- Modal Nueva Matrícula -->
    <div class="modal-form" id="matriculaModal">
        <div class="modal-content-form">
            <div class="modal-header-form">
                <h3 id="modalTitle" style="font-size: 1.2rem; font-weight: 600;">Nueva Matrícula</h3>
                <button class="close-modal-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body-form">
                <form method="POST" id="matriculaForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="matricula_id" id="matriculaId" value="">
                    
                    <div class="form-grid">
                        <div class="form-group" id="alumnoGroup">
                            <label>Alumno *</label>
                            <select name="id_alumno" id="inputAlumno" required>
                                <option value="">Seleccionar alumno</option>
                                <?php foreach ($alumnos as $a): ?>
                                    <option value="<?= $a['id_usuario'] ?>">
                                        <?= htmlspecialchars($a['apellidos'] . ', ' . $a['nombres']) ?> - <?= $a['dni'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" id="cicloGroup">
                            <label>Ciclo Académico *</label>
                            <select name="id_ciclo" id="inputCiclo" required>
                                <option value="">Seleccionar ciclo</option>
                                <?php foreach ($ciclos as $ci): ?>
                                    <option value="<?= $ci['id_ciclo'] ?>" data-costo="<?= $ci['costo'] ?>">
                                        <?= htmlspecialchars($ci['nombre_ciclo']) ?> - S/ <?= number_format($ci['costo'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Matrícula *</label>
                            <input type="date" name="fecha_matricula" id="inputFecha" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Monto Pagado (S/)</label>
                            <input type="number" name="monto_pagado" id="inputMonto" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Estado de Pago</label>
                            <select name="estado_pago" id="inputEstadoPago">
                                <option value="pendiente">Pendiente</option>
                                <option value="parcial">Parcial</option>
                                <option value="pagado">Pagado</option>
                            </select>
                        </div>
                        <div class="form-group" id="estadoMatriculaGroup" style="display: none;">
                            <label>Estado de Matrícula</label>
                            <select name="estado_matricula" id="inputEstadoMatricula">
                                <option value="activo">Activo</option>
                                <option value="retirado">Retirado</option>
                                <option value="finalizado">Finalizado</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Observaciones</label>
                            <textarea name="observaciones" id="inputObservaciones" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <span id="submitText">Registrar Matrícula</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('matriculaModal');
        const form = document.getElementById('matriculaForm');
        
        function openModal() {
            form.reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('matriculaId').value = '';
            document.getElementById('modalTitle').textContent = 'Nueva Matrícula';
            document.getElementById('submitText').textContent = 'Registrar Matrícula';
            document.getElementById('estadoMatriculaGroup').style.display = 'none';
            document.getElementById('alumnoGroup').style.display = 'block';
            document.getElementById('cicloGroup').style.display = 'block';
            document.getElementById('inputAlumno').required = true;
            document.getElementById('inputCiclo').required = true;
            document.getElementById('inputFecha').value = new Date().toISOString().split('T')[0];
            modal.classList.add('show');
        }
        
        function closeModal() {
            modal.classList.remove('show');
            form.reset();
        }
        
        function editMatricula(m) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('matriculaId').value = m.id_matricula;
            document.getElementById('modalTitle').textContent = 'Editar Matrícula - ' + m.nombres + ' ' + m.apellidos;
            document.getElementById('submitText').textContent = 'Actualizar Matrícula';
            document.getElementById('estadoMatriculaGroup').style.display = 'block';
            document.getElementById('alumnoGroup').style.display = 'none';
            document.getElementById('cicloGroup').style.display = 'none';
            document.getElementById('inputAlumno').required = false;
            document.getElementById('inputCiclo').required = false;
            
            document.getElementById('inputMonto').value = m.monto_pagado || 0;
            document.getElementById('inputEstadoPago').value = m.estado_pago;
            document.getElementById('inputEstadoMatricula').value = m.estado_matricula;
            document.getElementById('inputObservaciones').value = m.observaciones || '';
            
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
