<?php
/**
 * Sidebar Component - Academia Ampere Maxwell
 * 
 * Menú lateral dinámico según el rol del usuario
 */

require_once __DIR__ . '/../php/auth/Auth.php';

$user = Auth::getUser();
$userRole = Auth::getUserRole();
$currentPage = basename($_SERVER['PHP_SELF']);

// Definir menús por rol
$menus = [
    Auth::ROLE_ADMIN => [
        ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php', 'file' => 'index.php'],
        ['icon' => 'fa-users', 'label' => 'Usuarios', 'url' => 'usuarios.php', 'file' => 'usuarios.php'],
        ['icon' => 'fa-calendar-alt', 'label' => 'Ciclos', 'url' => 'ciclos.php', 'file' => 'ciclos.php'],
        ['icon' => 'fa-book', 'label' => 'Cursos', 'url' => 'cursos.php', 'file' => 'cursos.php'],
        ['icon' => 'fa-user-graduate', 'label' => 'Matrículas', 'url' => 'matriculas.php', 'file' => 'matriculas.php'],
        ['icon' => 'fa-chalkboard-teacher', 'label' => 'Profesores', 'url' => 'profesores.php', 'file' => 'profesores.php'],
        ['icon' => 'fa-chart-bar', 'label' => 'Reportes', 'url' => 'reportes.php', 'file' => 'reportes.php'],
        ['icon' => 'fa-cog', 'label' => 'Configuración', 'url' => 'configuracion.php', 'file' => 'configuracion.php'],
    ],
    Auth::ROLE_PROFESOR => [
        ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php', 'file' => 'index.php'],
        ['icon' => 'fa-book', 'label' => 'Mis Cursos', 'url' => 'mis-cursos.php', 'file' => 'mis-cursos.php'],
        ['icon' => 'fa-clipboard-check', 'label' => 'Calificaciones', 'url' => 'calificaciones.php', 'file' => 'calificaciones.php'],
        ['icon' => 'fa-user-check', 'label' => 'Asistencia', 'url' => 'asistencia.php', 'file' => 'asistencia.php'],
        ['icon' => 'fa-file-alt', 'label' => 'Materiales', 'url' => 'materiales.php', 'file' => 'materiales.php'],
        ['icon' => 'fa-tasks', 'label' => 'Tareas', 'url' => 'tareas.php', 'file' => 'tareas.php'],
    ],
    Auth::ROLE_ALUMNO => [
        ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php', 'file' => 'index.php'],
        ['icon' => 'fa-book', 'label' => 'Mis Cursos', 'url' => 'mis-cursos.php', 'file' => 'mis-cursos.php'],
        ['icon' => 'fa-star', 'label' => 'Calificaciones', 'url' => 'calificaciones.php', 'file' => 'calificaciones.php'],
        ['icon' => 'fa-calendar-check', 'label' => 'Asistencia', 'url' => 'asistencia.php', 'file' => 'asistencia.php'],
        ['icon' => 'fa-file-download', 'label' => 'Materiales', 'url' => 'materiales.php', 'file' => 'materiales.php'],
        ['icon' => 'fa-tasks', 'label' => 'Tareas', 'url' => 'tareas.php', 'file' => 'tareas.php'],
        ['icon' => 'fa-clock', 'label' => 'Horario', 'url' => 'horario.php', 'file' => 'horario.php'],
    ]
];

$currentMenu = $menus[$userRole] ?? [];

// Determinar título según rol
$roleTitles = [
    Auth::ROLE_ADMIN => 'Panel Administrativo',
    Auth::ROLE_PROFESOR => 'Panel del Profesor',
    Auth::ROLE_ALUMNO => 'Panel del Alumno'
];
$roleTitle = $roleTitles[$userRole] ?? 'Panel';
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= APP_URL ?>/assets/images/AMPEREMAXWELL.jpg" alt="Logo" class="sidebar-logo">
        <h2 class="sidebar-title"><?= $roleTitle ?></h2>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php foreach ($currentMenu as $item): ?>
                <li class="sidebar-item <?= $currentPage === $item['file'] ? 'active' : '' ?>">
                    <a href="<?= $item['url'] ?>" class="sidebar-link">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <span class="sidebar-text"><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>/intranet/logout.php" class="sidebar-link logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sidebar-text">Cerrar Sesión</span>
        </a>
    </div>
</aside>
