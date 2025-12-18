<?php
/**
 * Materiales Educativos - Panel Profesor
 * Gestión de recursos compartidos con alumnos.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireProfesor();

$user = Auth::getUser();
$pageTitle = 'Materiales Educativos';

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

// Curso seleccionado
$cursoSeleccionado = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$materiales = [];

if ($cursoSeleccionado) {
    $stmt = $conn->prepare("
        SELECT m.*, u.nombres as prof_nombres, u.apellidos as prof_apellidos
        FROM materiales_educativos m
        INNER JOIN usuarios u ON m.id_profesor = u.id_usuario
        WHERE m.id_curso = :curso AND m.estado = 'activo'
        ORDER BY m.fecha_publicacion DESC
    ");
    $stmt->execute([':curso' => $cursoSeleccionado]);
    $materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mapeo de tipos a iconos
$tipoIconos = [
    'pdf' => 'fa-file-pdf text-danger',
    'video' => 'fa-file-video text-primary',
    'presentacion' => 'fa-file-powerpoint text-warning',
    'documento' => 'fa-file-word text-info',
    'enlace' => 'fa-link text-secondary',
    'otro' => 'fa-file text-muted'
];
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
        .material-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            margin-bottom: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .material-card:hover { transform: translateX(5px); }
        .material-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border-radius: 10px;
            font-size: 1.5rem;
        }
        .material-info { flex: 1; }
        .material-title { font-weight: 600; margin-bottom: 4px; }
        .material-meta { font-size: 0.85rem; color: #666; }
        .material-actions { display: flex; gap: 8px; }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/header.php'; ?>
            
            <div class="content-wrapper">
                
                <!-- Selector de Cursos -->
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
                                <button type="button" class="btn btn-primary" onclick="openUploadModal()">
                                    <i class="fas fa-upload"></i> Subir Material
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <?php if ($cursoSeleccionado): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Materiales del Curso</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($materiales)): ?>
                                <div class="empty-state" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc;"></i>
                                    <p style="margin-top: 15px; color: #666;">No hay materiales subidos aún.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($materiales as $mat): ?>
                                    <div class="material-card">
                                        <div class="material-icon">
                                            <i class="fas <?= $tipoIconos[$mat['tipo_material']] ?? 'fa-file' ?>"></i>
                                        </div>
                                        <div class="material-info">
                                            <div class="material-title"><?= htmlspecialchars($mat['titulo']) ?></div>
                                            <div class="material-meta">
                                                <?= ucfirst($mat['tipo_material']) ?> • 
                                                <?= date('d/m/Y', strtotime($mat['fecha_publicacion'])) ?>
                                                <?php if ($mat['descripcion']): ?>
                                                    • <?= htmlspecialchars(substr($mat['descripcion'], 0, 50)) ?>...
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="material-actions">
                                            <?php if ($mat['ruta_archivo']): ?>
                                                <a href="<?= APP_URL ?>/uploads/materiales/<?= $mat['ruta_archivo'] ?>" target="_blank" class="btn btn-sm btn-outline">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php elseif ($mat['url_enlace']): ?>
                                                <a href="<?= htmlspecialchars($mat['url_enlace']) ?>" target="_blank" class="btn btn-sm btn-outline">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteMaterial(<?= $mat['id_material'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

        <!-- Modal de Subida -->
        <div id="uploadModal" class="modal-form">
            <div class="modal-content-form">
                <div class="modal-header-form">
                    <h3>Subir Material Educativo</h3>
                    <button class="close-modal-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body-form">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="id_curso" value="<?= $cursoSeleccionado ?>">
                        
                        <div class="form-group">
                            <label>Título *</label>
                            <input type="text" name="titulo" required placeholder="Ej. Clase 1 - Introducción">
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" rows="2" placeholder="Breve descripción del material"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Material *</label>
                            <select name="tipo_material" id="tipoMaterial" onchange="toggleUploadType()" style="width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #eee;">
                                <option value="pdf">PDF</option>
                                <option value="documento">Documento (Word, etc.)</option>
                                <option value="presentacion">Presentación (PPT)</option>
                                <option value="video">Video</option>
                                <option value="enlace">Enlace Externo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div id="fileUploadSection" class="form-group">
                            <label>Archivo *</label>
                            <input type="file" name="archivo" id="archivoInput" accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.zip">
                            <small style="color: #666;">Máx. 10MB. Formatos: PDF, DOC, PPT, MP4, ZIP</small>
                        </div>

                        <div id="linkSection" class="form-group" style="display: none;">
                            <label>URL del Enlace *</label>
                            <input type="url" name="url_enlace" placeholder="https://...">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-upload"></i> Subir Material
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script>
        const modal = document.getElementById('uploadModal');

        function openUploadModal() {
            modal.classList.add('show');
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        function toggleUploadType() {
            const tipo = document.getElementById('tipoMaterial').value;
            const fileSection = document.getElementById('fileUploadSection');
            const linkSection = document.getElementById('linkSection');
            
            if (tipo === 'enlace') {
                fileSection.style.display = 'none';
                linkSection.style.display = 'block';
            } else {
                fileSection.style.display = 'block';
                linkSection.style.display = 'none';
            }
        }

        document.getElementById('uploadForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({ title: 'Subiendo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('php/save_material.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire('Éxito', 'Material subido correctamente', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Error al subir', 'error');
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        };

        function deleteMaterial(id) {
            Swal.fire({
                title: '¿Eliminar material?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('php/delete_material.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id=' + id
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Eliminado', 'Material eliminado', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
