<?php
/**
 * Gestión de Horarios - Panel Admin
 * CRUD para asignar horarios a cursos.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAdmin();

$user = Auth::getUser();
$pageTitle = 'Gestión de Horarios';

$db = new Database();
$conn = $db->getConnection();

// Mensajes
$message = null;
$messageType = 'info';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $id_curso = (int)$_POST['id_curso'];
            $dia_semana = $_POST['dia_semana'];
            $hora_inicio = $_POST['hora_inicio'];
            $hora_fin = $_POST['hora_fin'];
            $aula = trim($_POST['aula'] ?? '');
            $modalidad = $_POST['modalidad'];
            $enlace_virtual = trim($_POST['enlace_virtual'] ?? '');
            
            $sql = "INSERT INTO horarios (id_curso, dia_semana, hora_inicio, hora_fin, aula, modalidad, enlace_virtual, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id_curso, $dia_semana, $hora_inicio, $hora_fin, $aula, $modalidad, $enlace_virtual]);
            
            $message = 'Horario creado exitosamente';
            $messageType = 'success';
        }
        
        if ($action === 'update') {
            $id = (int)$_POST['id_horario'];
            $dia_semana = $_POST['dia_semana'];
            $hora_inicio = $_POST['hora_inicio'];
            $hora_fin = $_POST['hora_fin'];
            $aula = trim($_POST['aula'] ?? '');
            $modalidad = $_POST['modalidad'];
            $enlace_virtual = trim($_POST['enlace_virtual'] ?? '');
            
            $sql = "UPDATE horarios SET dia_semana=?, hora_inicio=?, hora_fin=?, aula=?, modalidad=?, enlace_virtual=? WHERE id_horario=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$dia_semana, $hora_inicio, $hora_fin, $aula, $modalidad, $enlace_virtual, $id]);
            
            $message = 'Horario actualizado exitosamente';
            $messageType = 'success';
        }
        
        if ($action === 'delete') {
            $id = (int)$_POST['id_horario'];
            $conn->prepare("DELETE FROM horarios WHERE id_horario = ?")->execute([$id]);
            $message = 'Horario eliminado';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener cursos activos
$cursos = $conn->query("
    SELECT c.id_curso, c.nombre_curso, c.codigo_curso, ca.nombre_ciclo
    FROM cursos c
    INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
    WHERE c.estado = 'activo'
    ORDER BY ca.nombre_ciclo, c.nombre_curso
")->fetchAll(PDO::FETCH_ASSOC);

// Filtrar por curso si está seleccionado
$cursoFiltro = isset($_GET['curso']) ? (int)$_GET['curso'] : null;

$sqlHorarios = "
    SELECT h.*, c.nombre_curso, c.codigo_curso, ca.nombre_ciclo
    FROM horarios h
    INNER JOIN cursos c ON h.id_curso = c.id_curso
    INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
";
if ($cursoFiltro) {
    $sqlHorarios .= " WHERE h.id_curso = $cursoFiltro";
}
$sqlHorarios .= " ORDER BY c.nombre_curso, FIELD(h.dia_semana, 'lunes','martes','miercoles','jueves','viernes','sabado','domingo'), h.hora_inicio";

$horarios = $conn->query($sqlHorarios)->fetchAll(PDO::FETCH_ASSOC);

$dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                
                <?php if ($message): ?>
                    <script>
                        Swal.fire({
                            icon: '<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>',
                            title: '<?= $messageType === 'success' ? 'Éxito' : 'Aviso' ?>',
                            text: '<?= $message ?>',
                            timer: 3000
                        });
                    </script>
                <?php endif; ?>

                <!-- Filtros y Acciones -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <form method="GET" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Filtrar por Curso</label>
                                <select name="curso" class="form-control" onchange="this.form.submit()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                    <option value="">-- Todos los cursos --</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c['id_curso'] ?>" <?= $cursoFiltro == $c['id_curso'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nombre_curso']) ?> (<?= $c['nombre_ciclo'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus"></i> Nuevo Horario
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Horarios -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Horarios Registrados</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($horarios)): ?>
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc;"></i>
                                <p style="margin-top: 15px; color: #666;">No hay horarios registrados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Curso</th>
                                            <th>Día</th>
                                            <th>Horario</th>
                                            <th>Aula</th>
                                            <th>Modalidad</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($horarios as $h): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($h['nombre_curso']) ?></strong>
                                                    <br><small style="color: #666;"><?= $h['nombre_ciclo'] ?></small>
                                                </td>
                                                <td style="text-transform: capitalize;"><?= $h['dia_semana'] ?></td>
                                                <td><?= date('H:i', strtotime($h['hora_inicio'])) ?> - <?= date('H:i', strtotime($h['hora_fin'])) ?></td>
                                                <td><?= htmlspecialchars($h['aula'] ?: '-') ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $h['modalidad'] === 'virtual' ? 'info' : 'success' ?>">
                                                        <?= ucfirst($h['modalidad']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline" onclick="editHorario(<?= htmlspecialchars(json_encode($h)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este horario?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_horario" value="<?= $h['id_horario'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Modal -->
        <div id="horarioModal" class="modal-form">
            <div class="modal-content-form">
                <div class="modal-header-form">
                    <h3 id="modalTitle">Nuevo Horario</h3>
                    <button class="close-modal-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body-form">
                    <form id="horarioForm" method="POST">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id_horario" id="idHorario">
                        
                        <div class="form-group">
                            <label>Curso *</label>
                            <select name="id_curso" id="selectCurso" required style="width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #eee;">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id_curso'] ?>"><?= htmlspecialchars($c['nombre_curso']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Día de la Semana *</label>
                            <select name="dia_semana" id="selectDia" required style="width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #eee;">
                                <?php foreach ($dias as $d): ?>
                                    <option value="<?= $d ?>"><?= ucfirst($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Hora Inicio *</label>
                                <input type="time" name="hora_inicio" id="horaInicio" required>
                            </div>
                            <div class="form-group">
                                <label>Hora Fin *</label>
                                <input type="time" name="hora_fin" id="horaFin" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Aula</label>
                            <input type="text" name="aula" id="inputAula" placeholder="Ej. A-101">
                        </div>

                        <div class="form-group">
                            <label>Modalidad *</label>
                            <select name="modalidad" id="selectModalidad" required onchange="toggleEnlace()" style="width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #eee;">
                                <option value="presencial">Presencial</option>
                                <option value="virtual">Virtual</option>
                            </select>
                        </div>

                        <div class="form-group" id="enlaceGroup" style="display: none;">
                            <label>Enlace Virtual</label>
                            <input type="url" name="enlace_virtual" id="inputEnlace" placeholder="https://zoom.us/...">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('horarioModal');

        function openModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Horario';
            document.getElementById('formAction').value = 'create';
            document.getElementById('horarioForm').reset();
            modal.classList.add('show');
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        function editHorario(data) {
            document.getElementById('modalTitle').textContent = 'Editar Horario';
            document.getElementById('formAction').value = 'update';
            document.getElementById('idHorario').value = data.id_horario;
            document.getElementById('selectCurso').value = data.id_curso;
            document.getElementById('selectDia').value = data.dia_semana;
            document.getElementById('horaInicio').value = data.hora_inicio;
            document.getElementById('horaFin').value = data.hora_fin;
            document.getElementById('inputAula').value = data.aula || '';
            document.getElementById('selectModalidad').value = data.modalidad;
            document.getElementById('inputEnlace').value = data.enlace_virtual || '';
            toggleEnlace();
            modal.classList.add('show');
        }

        function toggleEnlace() {
            const modalidad = document.getElementById('selectModalidad').value;
            document.getElementById('enlaceGroup').style.display = modalidad === 'virtual' ? 'block' : 'none';
        }
    </script>
</body>
</html>
