<?php
/**
 * Materiales de Clase - Panel Alumno
 * Visualización y descarga de recursos compartidos por profesores.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Materiales de Clase';

$db = new Database();
$conn = $db->getConnection();

// Obtener cursos del alumno
$stmt = $conn->prepare("
    SELECT DISTINCT c.id_curso, c.nombre_curso, ca.nombre_ciclo
    FROM inscripciones_curso ic
    INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
    INNER JOIN cursos c ON ic.id_curso = c.id_curso
    INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
    WHERE m.id_alumno = :alumno AND ic.estado = 'activo'
");
$stmt->execute([':alumno' => $user['id_usuario']]);
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
                        <form method="GET" action="">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Seleccionar Curso</label>
                            <select name="curso" class="form-control" onchange="this.form.submit()" style="width: 100%; max-width: 400px; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                <option value="">-- Seleccione un curso --</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id_curso'] ?>" <?= $cursoSeleccionado == $c['id_curso'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre_curso']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if ($cursoSeleccionado): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Materiales Disponibles</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($materiales)): ?>
                                <div class="empty-state" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc;"></i>
                                    <p style="margin-top: 15px; color: #666;">No hay materiales disponibles para este curso.</p>
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
                                                <i class="fas fa-user"></i> Prof. <?= htmlspecialchars($mat['prof_apellidos']) ?> •
                                                <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($mat['fecha_publicacion'])) ?>
                                                <?php if ($mat['descripcion']): ?>
                                                    <br><small><?= htmlspecialchars($mat['descripcion']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($mat['ruta_archivo']): ?>
                                            <a href="<?= APP_URL ?>/uploads/materiales/<?= $mat['ruta_archivo'] ?>" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-download"></i> Descargar
                                            </a>
                                        <?php elseif ($mat['url_enlace']): ?>
                                            <a href="<?= htmlspecialchars($mat['url_enlace']) ?>" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-external-link-alt"></i> Abrir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif (empty($cursos)): ?>
                    <div class="card">
                        <div class="card-body" style="text-align: center; padding: 60px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #f59e0b;"></i>
                            <p style="margin-top: 15px;">No estás inscrito en ningún curso actualmente.</p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
