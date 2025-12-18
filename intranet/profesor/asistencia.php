<?php
/**
 * Asistencia - Panel Profesor
 * Registro diario de asistencia por curso.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';
require_once __DIR__ . '/../includes/AcademicHelper.php';

requireProfesor();

$user = Auth::getUser();
$pageTitle = 'Control de Asistencia';

$db = new Database();
$conn = $db->getConnection();
$academic = new AcademicHelper($conn);

// Obtener cursos
$stmt = $conn->prepare("
    SELECT c.id_curso, c.nombre_curso, ca.nombre_ciclo 
    FROM profesor_curso pc
    INNER JOIN cursos c ON pc.id_curso = c.id_curso
    INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
    WHERE pc.id_profesor = :profesor AND pc.estado = 'activo'
");
$stmt->execute([':profesor' => $user['id_usuario']]);
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables de estado
$cursoSeleccionado = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$fechaSeleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$alumnos = [];
$asistenciasPrevias = [];

if ($cursoSeleccionado) {
    // Validar propiedad
    $val = $conn->prepare("SELECT COUNT(*) FROM profesor_curso WHERE id_curso = ? AND id_profesor = ?");
    $val->execute([$cursoSeleccionado, $user['id_usuario']]);
    
    if ($val->fetchColumn() > 0) {
        $alumnos = $academic->getAlumnosCurso($cursoSeleccionado);
        $asistenciasPrevias = $academic->getAsistenciaFecha($cursoSeleccionado, $fechaSeleccionada);
    }
}
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
    <style>
        .attendance-options { display: flex; gap: 10px; }
        .attendance-label { 
            cursor: pointer; 
            padding: 8px 12px; 
            border: 1px solid var(--dash-border); 
            border-radius: 6px; 
            font-size: 0.9rem; 
            transition: all 0.2s;
            display: flex; align-items: center; gap: 6px;
        }
        .attendance-label:hover { background: var(--dash-bg-alt); }
        .attendance-radio:checked + .attendance-label.present { background: #dcfce7; color: #166534; border-color: #86efac; }
        .attendance-radio:checked + .attendance-label.late { background: #fef9c3; color: #854d0e; border-color: #fde047; }
        .attendance-radio:checked + .attendance-label.absent { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .attendance-radio { display: none; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                
                <!-- Filtros -->
                <div class="card mb-4" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <form method="GET" action="" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: end;">
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Curso</label>
                                <select name="curso" class="form-control" onchange="this.form.submit()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c['id_curso'] ?>" <?= $cursoSeleccionado == $c['id_curso'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nombre_curso']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="width: 200px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Fecha</label>
                                <input type="date" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>" onchange="this.form.submit()" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($cursoSeleccionado && !empty($alumnos)): ?>
                    <form id="attendanceForm">
                        <input type="hidden" name="id_curso" value="<?= $cursoSeleccionado ?>">
                        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>">
                        
                        <div class="card">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <h3 class="card-title">Lista de Alumnos - <?= date('d/m/Y', strtotime($fechaSeleccionada)) ?></h3>
                                <button type="button" class="btn btn-outline btn-sm" onclick="markAllPresent()">
                                    <i class="fas fa-check-double"></i> Todos Presentes
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-wrapper">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Alumno</th>
                                                <th>Estado de Asistencia</th>
                                                <th>Observación</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alumnos as $al): 
                                                $inscId = $al['id_inscripcion'];
                                                $estado = $asistenciasPrevias[$inscId]['estado'] ?? null;
                                                $obs = $asistenciasPrevias[$inscId]['observaciones'] ?? '';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div style="display: flex; align-items: center; gap: 10px;">
                                                            <img src="<?= Auth::getAvatarUrl($al['foto_perfil']) ?>" style="width: 32px; height: 32px; border-radius: 50%;">
                                                            <span><?= htmlspecialchars($al['apellidos'] . ' ' . $al['nombres']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="attendance-options">
                                                            <label>
                                                                <input type="radio" name="asistencia[<?= $inscId ?>]" value="presente" class="attendance-radio" <?= ($estado === 'presente' || !$estado) ? 'checked' : '' ?>>
                                                                <span class="attendance-label present"><i class="fas fa-check"></i> P</span>
                                                            </label>
                                                            <label>
                                                                <input type="radio" name="asistencia[<?= $inscId ?>]" value="tardanza" class="attendance-radio" <?= $estado === 'tardanza' ? 'checked' : '' ?>>
                                                                <span class="attendance-label late"><i class="fas fa-clock"></i> T</span>
                                                            </label>
                                                            <label>
                                                                <input type="radio" name="asistencia[<?= $inscId ?>]" value="ausente" class="attendance-radio" <?= $estado === 'ausente' ? 'checked' : '' ?>>
                                                                <span class="attendance-label absent"><i class="fas fa-times"></i> F</span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="observacion[<?= $inscId ?>]" value="<?= htmlspecialchars($obs) ?>" placeholder="Opcional" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 20px; text-align: right;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Guardar Asistencia
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function markAllPresent() {
            document.querySelectorAll('input[value="presente"]').forEach(radio => radio.checked = true);
        }

        document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('php/save_asistencia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Éxito', 'Asistencia guardada correctamente', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
        });
    </script>
</body>
</html>
