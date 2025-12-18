<?php
/**
 * Gestión de Tareas - Panel Profesor
 * CRUD de tareas y revisión de entregas.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireProfesor();

$user = Auth::getUser();
$pageTitle = 'Gestión de Tareas';

$db = new Database();
$conn = $db->getConnection();

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

$cursoSeleccionado = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$tareas = [];

if ($cursoSeleccionado) {
    $stmt = $conn->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM entregas_tareas et WHERE et.id_tarea = t.id_tarea) as total_entregas,
               (SELECT COUNT(*) FROM entregas_tareas et WHERE et.id_tarea = t.id_tarea AND et.estado = 'calificado') as calificadas
        FROM tareas t
        WHERE t.id_curso = :curso AND t.id_profesor = :profesor
        ORDER BY t.fecha_entrega DESC
    ");
    $stmt->execute([':curso' => $cursoSeleccionado, ':profesor' => $user['id_usuario']]);
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .task-card.expired { border-left-color: #ef4444; }
        .task-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; }
        .task-title { font-weight: 600; font-size: 1.1rem; }
        .task-meta { font-size: 0.85rem; color: #666; margin-top: 5px; }
        .task-stats { display: flex; gap: 15px; margin-top: 10px; }
        .stat-item { display: flex; align-items: center; gap: 5px; font-size: 0.9rem; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                
                <!-- Selector y Acción -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <form method="GET" action="" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Seleccionar Curso</label>
                                <select name="curso" class="form-control" onchange="this.form.submit()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c['id_curso'] ?>" <?= $cursoSeleccionado == $c['id_curso'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nombre_curso']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($cursoSeleccionado): ?>
                                <button type="button" class="btn btn-primary" onclick="openTaskModal()">
                                    <i class="fas fa-plus"></i> Nueva Tarea
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <?php if ($cursoSeleccionado): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tareas del Curso</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tareas)): ?>
                                <div class="empty-state" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #ccc;"></i>
                                    <p style="margin-top: 15px; color: #666;">No hay tareas creadas aún.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tareas as $t): 
                                    $vencida = strtotime($t['fecha_entrega']) < time();
                                ?>
                                    <div class="task-card <?= $vencida ? 'expired' : '' ?>">
                                        <div class="task-header">
                                            <div>
                                                <div class="task-title"><?= htmlspecialchars($t['titulo']) ?></div>
                                                <div class="task-meta">
                                                    <i class="fas fa-calendar"></i> Entrega: <?= date('d/m/Y', strtotime($t['fecha_entrega'])) ?>
                                                    <?= $vencida ? '<span style="color: #ef4444; font-weight: 500;"> (Vencida)</span>' : '' ?>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="ver_entregas.php?tarea=<?= $t['id_tarea'] ?>" class="btn btn-sm btn-outline">
                                                    <i class="fas fa-eye"></i> Ver Entregas
                                                </a>
                                            </div>
                                        </div>
                                        <?php if ($t['descripcion']): ?>
                                            <p style="margin: 10px 0; color: #555;"><?= htmlspecialchars($t['descripcion']) ?></p>
                                        <?php endif; ?>
                                        <div class="task-stats">
                                            <div class="stat-item">
                                                <i class="fas fa-file-upload" style="color: var(--dash-primary);"></i>
                                                <span><?= $t['total_entregas'] ?> entregas</span>
                                            </div>
                                            <div class="stat-item">
                                                <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                                <span><?= $t['calificadas'] ?> calificadas</span>
                                            </div>
                                            <div class="stat-item">
                                                <i class="fas fa-star" style="color: #f59e0b;"></i>
                                                <span>Puntaje: <?= $t['puntaje_maximo'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Modal Nueva Tarea -->
        <div id="taskModal" class="modal-form">
            <div class="modal-content-form">
                <div class="modal-header-form">
                    <h3>Crear Nueva Tarea</h3>
                    <button class="close-modal-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body-form">
                    <form id="taskForm">
                        <input type="hidden" name="id_curso" value="<?= $cursoSeleccionado ?>">
                        
                        <div class="form-group">
                            <label>Título *</label>
                            <input type="text" name="titulo" required placeholder="Ej. Práctica Calificada 1">
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" rows="3" placeholder="Instrucciones para los alumnos"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Fecha de Entrega *</label>
                                <input type="date" name="fecha_entrega" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label>Puntaje Máximo</label>
                                <input type="number" name="puntaje_maximo" value="20" min="1" max="100">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Crear Tarea
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('taskModal');

        function openTaskModal() {
            modal.classList.add('show');
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        document.getElementById('taskForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('php/save_tarea.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Éxito', 'Tarea creada correctamente', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Error al crear', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
        };
    </script>
</body>
</html>
