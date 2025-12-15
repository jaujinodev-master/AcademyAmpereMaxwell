<?php
/**
 * Clase Auth - Gestión de Autenticación
 * Academia Ampere Maxwell
 * 
 * Maneja login, logout, sesiones y verificación de roles
 */

// Incluir configuración de base de datos
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Database.php';

class Auth {
    private $db;
    private $conn;
    
    // Roles disponibles
    const ROLE_ADMIN = 1;
    const ROLE_PROFESOR = 2;
    const ROLE_ALUMNO = 3;
    const ROLE_SERVICIOS = 4;
    
    /**
     * Constructor - Inicializa conexión a BD
     */
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Iniciar sesión PHP
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }
    
    /**
     * Intentar login con credenciales
     * @param string $username Usuario o email
     * @param string $password Contraseña
     * @param bool $remember Recordar sesión
     * @return array Resultado del login
     */
    public function login($username, $password, $remember = false) {
        try {
            // Buscar usuario por username o email
            $sql = "SELECT u.*, r.nombre_rol, r.permisos 
                    FROM usuarios u 
                    INNER JOIN roles r ON u.id_rol = r.id_rol 
                    WHERE (u.username = :username OR u.email = :email) 
                    AND u.estado = 'activo'";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado o cuenta inactiva'
                ];
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Contraseña incorrecta'
                ];
            }
            
            // Actualizar último acceso
            $this->updateLastAccess($user['id_usuario']);
            
            // Iniciar sesión
            self::startSession();
            
            // Guardar datos del usuario en sesión (sin la contraseña)
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_role'] = $user['id_rol'];
            $_SESSION['user_name'] = $user['nombres'] . ' ' . $user['apellidos'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Si recordar sesión, extender tiempo de vida
            if ($remember) {
                $_SESSION['remember'] = true;
                ini_set('session.gc_maxlifetime', SESSION_LIFETIME * 24);
            }
            
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            // Determinar redirección según rol
            $redirect = $this->getRedirectByRole($user['id_rol']);
            
            return [
                'success' => true,
                'message' => 'Login exitoso',
                'redirect' => $redirect,
                'user' => [
                    'id' => $user['id_usuario'],
                    'nombre' => $user['nombres'] . ' ' . $user['apellidos'],
                    'rol' => $user['nombre_rol'],
                    'avatar' => $user['foto_perfil']
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error del servidor. Intente más tarde.'
            ];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public static function logout() {
        self::startSession();
        
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    /**
     * Verificar si hay sesión activa
     * @return bool
     */
    public static function isLoggedIn() {
        self::startSession();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Verificar si la sesión ha expirado
        $maxLifetime = isset($_SESSION['remember']) ? SESSION_LIFETIME * 24 : SESSION_LIFETIME;
        
        if (time() - $_SESSION['login_time'] > $maxLifetime) {
            self::logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtener usuario actual
     * @return array|null
     */
    public static function getUser() {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Obtener ID del usuario actual
     * @return int|null
     */
    public static function getUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtener rol del usuario actual
     * @return int|null
     */
    public static function getUserRole() {
        self::startSession();
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     * @param int|array $roles Rol o array de roles permitidos
     * @return bool
     */
    public static function hasRole($roles) {
        $userRole = self::getUserRole();
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole === $roles;
    }
    
    /**
     * Verificar si es administrador
     * @return bool
     */
    public static function isAdmin() {
        return self::hasRole(self::ROLE_ADMIN);
    }
    
    /**
     * Verificar si es profesor
     * @return bool
     */
    public static function isProfesor() {
        return self::hasRole(self::ROLE_PROFESOR);
    }
    
    /**
     * Verificar si es alumno
     * @return bool
     */
    public static function isAlumno() {
        return self::hasRole(self::ROLE_ALUMNO);
    }
    
    /**
     * Obtener URL de redirección según rol
     * @param int $roleId ID del rol
     * @return string URL
     */
    private function getRedirectByRole($roleId) {
        $baseUrl = APP_URL . '/intranet/';
        
        switch ($roleId) {
            case self::ROLE_ADMIN:
                return $baseUrl . 'admin/';
            case self::ROLE_PROFESOR:
                return $baseUrl . 'profesor/';
            case self::ROLE_ALUMNO:
                return $baseUrl . 'alumno/';
            case self::ROLE_SERVICIOS:
                return $baseUrl . 'servicios/';
            default:
                return $baseUrl . 'login.php';
        }
    }
    
    /**
     * Actualizar último acceso del usuario
     * @param int $userId ID del usuario
     */
    private function updateLastAccess($userId) {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Last Access Error: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener avatar del usuario (o avatar por defecto)
     * @param string|null $avatarPath Ruta del avatar
     * @return string URL del avatar
     */
    public static function getAvatarUrl($avatarPath = null) {
        if ($avatarPath && file_exists($_SERVER['DOCUMENT_ROOT'] . '/AcademyAmpereMaxwell/uploads/avatars/' . $avatarPath)) {
            return APP_URL . '/uploads/avatars/' . $avatarPath;
        }
        
        // Avatar por defecto
        return APP_URL . '/intranet/assets/images/default-avatar.svg';
    }
}
?>
