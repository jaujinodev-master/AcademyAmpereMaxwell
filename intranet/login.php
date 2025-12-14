<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Plataforma - Academia Ampere Maxwell</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="icon" href="../assets/images/AMPEREMAXWELL.jpg" type="image/x-icon">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <!-- Lado izquierdo - Branding -->
        <div class="login-left">
            <div class="brand-content">
                <img src="../assets/images/AMPEREMAXWELL.jpg" alt="Academia Ampere Maxwell" class="brand-logo">
                <h1>Academia Ampere Maxwell</h1>
                <p>Plataforma Educativa Integral</p>
                <div class="features">
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Seguimiento Académico</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-book-open"></i>
                        <span>Recursos Educativos</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Gestión Completa</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lado derecho - Formulario -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="login-header">
                    <h2>Bienvenido</h2>
                    <p>Ingresa a tu cuenta para continuar</p>
                </div>

                <form id="loginForm" class="login-form" method="POST" action="php/auth/login_process.php">

                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Usuario o Email
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Ingresa tu usuario o email"
                            required
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Contraseña
                        </label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Ingresa tu contraseña"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkmark"></span>
                            Recordar sesión
                        </label>
                        <a href="forgot-password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>

                    <div class="login-footer">
                        <p>¿No tienes una cuenta? <a href="#contacto">Contáctanos</a></p>
                        <a href="../index.html" class="back-link">
                            <i class="fas fa-arrow-left"></i> Volver al inicio
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
