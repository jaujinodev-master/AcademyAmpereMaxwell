<?php
/**
 * Dashboard del Alumno - Academia Ampere Maxwell
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../php/auth/Auth.php';
require_once __DIR__ . '/../php/auth/middleware.php';

// Verificar que sea alumno
requireAlumno();

$user = Auth::getUser();
$pageTitle = 'Mi Dashboard';

// Conexión a base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtener estadísticas del alumno
try {
    $userId = $user['id_usuario'];
    
    // Cursos inscritos
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM inscripciones_curso ic
        INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
        WHERE m.id_alumno = ? AND ic.estado = 'activo'
    ");
    $stmt->execute([$userId]);
    $cursosInscritos = $stmt->fetch()['total'];
    
    // Promedio general
    $stmt = $conn->prepare("
        SELECT AVG(c.nota) as promedio 
        FROM calificaciones c
        INNER JOIN inscripciones_curso ic ON c.id_inscripcion = ic.id_inscripcion
        INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
        WHERE m.id_alumno = ?
    ");
    $stmt->execute([$userId]);
    $promedio = round($stmt->fetch()['promedio'] ?? 0, 2);
    
    // Tareas pendientes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM tareas t
        INNER JOIN cursos cu ON t.id_curso = cu.id_curso
        INNER JOIN inscripciones_curso ic ON cu.id_curso = ic.id_curso
        INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
        WHERE m.id_alumno = ? AND t.estado = 'activo'
        AND t.id_tarea NOT IN (SELECT id_tarea FROM entregas_tareas WHERE id_alumno = ?)
    ");
    $stmt->execute([$userId, $userId]);
    $tareasPendientes = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $cursosInscritos = 0;
    $promedio = 0;
    $tareasPendientes = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="icon" href="../../assets/images/AMPEREMAXWELL.jpg" type="image/x-icon">
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
                <!-- Welcome -->
                <div class="welcome-banner" style="background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%); color: #fff; padding: 24px 30px; border-radius: 16px; margin-bottom: 24px;">
                    <h2 style="font-size: 1.5rem; margin-bottom: 8px;">¡Hola, <?= htmlspecialchars($user['nombres']) ?>! 📚</h2>
                    <p style="opacity: 0.9;">Continúa aprendiendo y alcanzando tus metas académicas.</p>
                </div>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon accent">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $cursosInscritos ?></h3>
                            <p>Cursos Inscritos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $promedio ?></h3>
                            <p>Promedio General</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $tareasPendientes ?></h3>
                            <p>Tareas Pendientes</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Acceso Rápido</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <a href="mis-cursos.php" class="btn btn-primary" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-book"></i> Mis Cursos
                            </a>
                            <a href="calificaciones.php" class="btn btn-success" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-star"></i> Ver Calificaciones
                            </a>
                            <a href="materiales.php" class="btn btn-outline" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-download"></i> Materiales
                            </a>
                            <a href="tareas.php" class="btn btn-outline" style="justify-content: center; padding: 20px;">
                                <i class="fas fa-tasks"></i> Entregar Tareas
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Carnet estudiantil -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Mi Carnet Estudiantil</h3>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <div style="display: inline-block; background: linear-gradient(135deg, #1E293B 0%, #334155 100%); padding: 30px; border-radius: 16px; color: #fff; max-width: 350px;">
                            <img src="<?= Auth::getAvatarUrl($user['foto_perfil']) ?>" 
                                 alt="Foto" 
                                 style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid #fff; margin-bottom: 16px; object-fit: cover;"
                                 onerror="this.src='../assets/images/default-avatar.svg'">
                            <h4 style="font-size: 1.2rem; margin-bottom: 4px;"><?= htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']) ?></h4>
                            <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 16px;">Alumno - Academia Ampere Maxwell</p>
                            <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 8px;">
                                <p style="font-size: 0.85rem;">DNI: <?= htmlspecialchars($user['dni'] ?? 'No registrado') ?></p>
                                <p style="font-size: 0.85rem;">ID: AM-<?= str_pad($user['id_usuario'], 5, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </div>
                        <div style="margin-top: 16px;">
                            <a href="perfil.php" class="btn btn-outline">
                                <i class="fas fa-camera"></i> Actualizar Foto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../components/footer.php'; ?>
        </div>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
