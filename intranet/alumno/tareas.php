<?php
/**
 * Mis Tareas - Panel Alumno
 * Ver tareas pendientes y subir entregas.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Mis Tareas';

$db = new Database();
$conn = $db->getConnection();

// Obtener tareas de los cursos inscritos
$stmt = $conn->prepare("
    SELECT t.*, c.nombre_curso,
           u.nombres as prof_nombres, u.apellidos as prof_apellidos,
           et.id_entrega, et.fecha_entrega as fecha_entregado, et.calificacion, et.estado as estado_entrega
    FROM tareas t
    INNER JOIN cursos c ON t.id_curso = c.id_curso
    INNER JOIN usuarios u ON t.id_profesor = u.id_usuario
    INNER JOIN inscripciones_curso ic ON c.id_curso = ic.id_curso
    INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
    LEFT JOIN entregas_tareas et ON t.id_tarea = et.id_tarea AND et.id_alumno = :alumno1
    WHERE m.id_alumno = :alumno2 AND t.estado = 'activo' AND ic.estado = 'activo'
    ORDER BY t.fecha_entrega ASC
");
$stmt->execute([':alumno1' => $user['id_usuario'], ':alumno2' => $user['id_usuario']]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar por estado
$tareasPendientes = [];
$tareasEntregadas = [];
$tareasVencidas = [];

foreach ($tareas as $t) {
    if ($t['id_entrega']) {
        $tareasEntregadas[] = $t;
    } elseif (strtotime($t['fecha_entrega']) < time()) {
        $tareasVencidas[] = $t;
    } else {
        $tareasPendientes[] = $t;
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
        .task-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--dash-primary);
        }
        .task-card.delivered { border-left-color: #10b981; }
        .task-card.expired { border-left-color: #ef4444; }
        .task-header { display: flex; justify-content: space-between; align-items: center; }
        .task-title { font-weight: 600; font-size: 1.1rem; }
        .task-course { font-size: 0.85rem; color: var(--dash-primary); margin-top: 3px; }
        .task-meta { font-size: 0.85rem; color: #666; margin-top: 8px; }
        .badge-pending { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; }
        .badge-delivered { background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; }
        .badge-expired { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; }
        .badge-graded { background: #ede9fe; color: #5b21b6; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .section-title { font-size: 1.2rem; font-weight: 600; margin: 20px 0 15px; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">

                <?php if (empty($tareas)): ?>
                    <div class="card">
                        <div class="card-body" style="text-align: center; padding: 60px;">
                            <i class="fas fa-clipboard-check" style="font-size: 4rem; color: #10b981;"></i>
                            <h3 style="margin-top: 20px;">¡Sin tareas pendientes!</h3>
                            <p style="color: #666;">No tienes tareas asignadas actualmente.</p>
                        </div>
                    </div>
                <?php else: ?>

                    <!-- Tareas Pendientes -->
                    <?php if (!empty($tareasPendientes)): ?>
                        <div class="section-title">
                            <i class="fas fa-clock" style="color: #f59e0b;"></i>
                            Pendientes (<?= count($tareasPendientes) ?>)
                        </div>
                        <?php foreach ($tareasPendientes as $t): ?>
                            <div class="task-card">
                                <div class="task-header">
                                    <div>
                                        <div class="task-title"><?= htmlspecialchars($t['titulo']) ?></div>
                                        <div class="task-course"><?= htmlspecialchars($t['nombre_curso']) ?></div>
                                    </div>
                                    <span class="badge-pending">
                                        <i class="fas fa-hourglass-half"></i> Pendiente
                                    </span>
                                </div>
                                <?php if ($t['descripcion']): ?>
                                    <p style="margin: 10px 0; color: #555;"><?= htmlspecialchars($t['descripcion']) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <i class="fas fa-calendar-alt"></i> Fecha límite: <?= date('d/m/Y', strtotime($t['fecha_entrega'])) ?> •
                                    <i class="fas fa-star"></i> Puntaje: <?= $t['puntaje_maximo'] ?>
                                </div>
                                <div style="margin-top: 15px;">
                                    <button class="btn btn-primary" onclick="openSubmitModal(<?= $t['id_tarea'] ?>, '<?= htmlspecialchars($t['titulo'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-upload"></i> Entregar Tarea
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Tareas Entregadas -->
                    <?php if (!empty($tareasEntregadas)): ?>
                        <div class="section-title">
                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                            Entregadas (<?= count($tareasEntregadas) ?>)
                        </div>
                        <?php foreach ($tareasEntregadas as $t): ?>
                            <div class="task-card delivered">
                                <div class="task-header">
                                    <div>
                                        <div class="task-title"><?= htmlspecialchars($t['titulo']) ?></div>
                                        <div class="task-course"><?= htmlspecialchars($t['nombre_curso']) ?></div>
                                    </div>
                                    <?php if ($t['estado_entrega'] === 'calificado'): ?>
                                        <span class="badge-graded">
                                            <i class="fas fa-star"></i> Nota: <?= $t['calificacion'] ?>/<?= $t['puntaje_maximo'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-delivered">
                                            <i class="fas fa-check"></i> Entregado
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="task-meta">
                                    Entregado el: <?= date('d/m/Y H:i', strtotime($t['fecha_entregado'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Tareas Vencidas -->
                    <?php if (!empty($tareasVencidas)): ?>
                        <div class="section-title">
                            <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                            Vencidas (<?= count($tareasVencidas) ?>)
                        </div>
                        <?php foreach ($tareasVencidas as $t): ?>
                            <div class="task-card expired">
                                <div class="task-header">
                                    <div>
                                        <div class="task-title"><?= htmlspecialchars($t['titulo']) ?></div>
                                        <div class="task-course"><?= htmlspecialchars($t['nombre_curso']) ?></div>
                                    </div>
                                    <span class="badge-expired">
                                        <i class="fas fa-exclamation-triangle"></i> Vencida
                                    </span>
                                </div>
                                <div class="task-meta">
                                    Venció el: <?= date('d/m/Y', strtotime($t['fecha_entrega'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Modal de Entrega -->
        <div id="submitModal" class="modal-form">
            <div class="modal-content-form">
                <div class="modal-header-form">
                    <h3 id="modalTitle">Entregar Tarea</h3>
                    <button class="close-modal-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body-form">
                    <form id="submitForm" enctype="multipart/form-data">
                        <input type="hidden" name="id_tarea" id="idTareaInput">
                        
                        <div class="form-group">
                            <label>Archivo de Entrega *</label>
                            <input type="file" name="archivo" required accept=".pdf,.doc,.docx,.zip,.rar">
                            <small style="color: #666;">Máx. 10MB. Formatos: PDF, DOC, ZIP</small>
                        </div>

                        <div class="form-group">
                            <label>Comentario (opcional)</label>
                            <textarea name="comentario" rows="2" placeholder="Mensaje para el profesor"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Enviar Entrega
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('submitModal');

        function openSubmitModal(tareaId, titulo) {
            document.getElementById('idTareaInput').value = tareaId;
            document.getElementById('modalTitle').textContent = 'Entregar: ' + titulo;
            modal.classList.add('show');
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        document.getElementById('submitForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({ title: 'Subiendo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('php/submit_tarea.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire('¡Enviado!', 'Tu tarea ha sido entregada correctamente', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Error al enviar', 'error');
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        };
    </script>
</body>
</html>
