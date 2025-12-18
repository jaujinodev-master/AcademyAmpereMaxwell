<?php
/**
 * Calificaciones - Panel Profesor
 * Permite gestionar las notas de los alumnos por curso.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';
require_once __DIR__ . '/../includes/AcademicHelper.php';

requireProfesor();

$user = Auth::getUser();
$pageTitle = 'Gestión de Calificaciones';

$db = new Database();
$conn = $db->getConnection();
$academic = new AcademicHelper($conn);

// Obtener cursos del profesor
$stmt = $conn->prepare("
    SELECT c.id_curso, c.nombre_curso, ca.nombre_ciclo 
    FROM profesor_curso pc
    INNER JOIN cursos c ON pc.id_curso = c.id_curso
    INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
    WHERE pc.id_profesor = :profesor AND pc.estado = 'activo'
");
$stmt->execute([':profesor' => $user['id_usuario']]);
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si hay curso seleccionado en GET, cargar datos
$cursoSeleccionado = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$alumnos = [];
$calificaciones = [];

if ($cursoSeleccionado) {
    // Validar que el curso pertenezca al profesor
    $val = $conn->prepare("SELECT COUNT(*) FROM profesor_curso WHERE id_curso = ? AND id_profesor = ?");
    $val->execute([$cursoSeleccionado, $user['id_usuario']]);
    
    if ($val->fetchColumn() > 0) {
        $alumnos = $academic->getAlumnosCurso($cursoSeleccionado);
        $calificaciones = $academic->getCalificacionesCurso($cursoSeleccionado);
    } else {
        $cursoSeleccionado = null; // Acceso no autorizado
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
        .grades-table input[type="number"] {
            width: 70px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .grades-table th { white-space: nowrap; }
        .course-selector { margin-bottom: 25px; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                
                <!-- Selector de Cursos -->
                <div class="card course-selector">
                    <div class="card-body">
                        <form method="GET" action="" style="display: flex; gap: 15px; align-items: center;">
                            <label style="font-weight: 600;">Seleccionar Curso:</label>
                            <select name="curso" class="form-control" onchange="this.form.submit()" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; min-width: 300px;">
                                <option value="">-- Seleccione un curso --</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id_curso'] ?>" <?= $cursoSeleccionado == $c['id_curso'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre_curso'] . ' - ' . $c['nombre_ciclo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if ($cursoSeleccionado): ?>
                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Registro de Notas</h3>
                            <button class="btn btn-primary" onclick="openAddGradeModal()">
                                <i class="fas fa-plus"></i> Nueva Evaluación
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($alumnos)): ?>
                                <div class="empty-state">No hay alumnos inscritos en este curso.</div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="data-table grades-table">
                                        <thead>
                                            <tr>
                                                <th>Alumno</th>
                                                <!-- Las columnas de evaluaciones se generarán dinámicamente -->
                                                <!-- Por simplicidad en versión 1, mostramos historial -->
                                                <th>Historial de Notas</th>
                                                <th>Promedio (Ref)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alumnos as $alumno): ?>
                                                <?php 
                                                    $misNotas = $calificaciones[$alumno['id_inscripcion']] ?? [];
                                                    $suma = 0;
                                                    $count = 0;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div style="display: flex; align-items: center; gap: 10px;">
                                                            <img src="<?= Auth::getAvatarUrl($alumno['foto_perfil']) ?>" style="width: 32px; height: 32px; border-radius: 50%;">
                                                            <span><?= htmlspecialchars($alumno['apellidos'] . ', ' . $alumno['nombres']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                            <?php foreach ($misNotas as $nota): 
                                                                $suma += $nota['nota']; 
                                                                $count++;
                                                            ?>
                                                                <span class="badge badge-info" title="<?= $nota['descripcion'] ?>">
                                                                    <?= $nota['nota'] ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                            <?php if (empty($misNotas)) echo '<span class="text-muted">Sin notas</span>'; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong><?= $count > 0 ? number_format($suma / $count, 2) : '-' ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif (!empty($cursos)): ?>
                    <div class="alert alert-info" style="text-align: center; padding: 40px;">
                        <i class="fas fa-arrow-up" style="font-size: 2rem;"></i>
                        <p style="margin-top: 10px;">Por favor seleccione un curso para comenzar.</p>
                    </div>
                <?php endif; ?>

            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Modal Nueva Nota -->
        <div id="gradeModal" class="modal-form">
            <div class="modal-content-form">
                <div class="modal-header-form">
                    <h3>Registrar Nueva Nota</h3>
                    <button class="close-modal-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body-form">
                    <form id="gradeForm">
                        <input type="hidden" name="id_curso" value="<?= $cursoSeleccionado ?>">
                        <div class="form-group">
                            <label>Nombre Evaluación</label>
                            <input type="text" name="descripcion" placeholder="Ej. Examen Parcial" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo" style="width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #eee;">
                                <option value="examen">Examen</option>
                                <option value="practica">Práctica</option>
                                <option value="tarea">Tarea</option>
                                <option value="proyecto">Proyecto</option>
                                <option value="participacion">Participación</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div style="max-height: 300px; overflow-y: auto; margin: 20px 0; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600;">Ingreso de Notas:</label>
                            <?php if ($cursoSeleccionado && !empty($alumnos)): ?>
                                <?php foreach ($alumnos as $al): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #f9f9f9;">
                                        <span><?= htmlspecialchars($al['apellidos'] . ' ' . $al['nombres']) ?></span>
                                        <input type="number" name="notas[<?= $al['id_inscripcion'] ?>]" min="0" max="20" step="0.5" placeholder="0-20" style="width: 80px; padding: 5px; text-align: center;">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Todo</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('gradeModal');
        
        function openAddGradeModal() {
            modal.classList.add('show');
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        document.getElementById('gradeForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('php/save_calificacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Éxito', 'Notas registradas correctamente', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Ocurrió un error', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
        };
    </script>
</body>
</html>
