<?php
/**
 * Script para actualizar la contraseña del administrador
 * Academia Ampere Maxwell
 * 
 * Este script corrige el hash de la contraseña del usuario admin
 * Ejecutar una sola vez: php fix_admin_password.php
 */

require_once __DIR__ . '/intranet/config/database.php';
require_once __DIR__ . '/intranet/includes/Database.php';

try {
    echo "=== Actualizando contraseña del administrador ===" . PHP_EOL;
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Nueva contraseña: admin123
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    
    echo "Nuevo hash generado: " . $newHash . PHP_EOL;
    
    // Actualizar en la base de datos
    $sql = "UPDATE usuarios SET password_hash = :hash WHERE username = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hash', $newHash);
    
    if ($stmt->execute()) {
        $rowsAffected = $stmt->rowCount();
        if ($rowsAffected > 0) {
            echo "✅ Contraseña actualizada exitosamente!" . PHP_EOL;
            echo "Usuario: admin" . PHP_EOL;
            echo "Nueva contraseña: admin123" . PHP_EOL;
        } else {
            echo "⚠️ Usuario 'admin' no encontrado en la base de datos." . PHP_EOL;
            echo "Puede que necesites ejecutar primero el schema.sql" . PHP_EOL;
        }
    }
    
    // Verificar que el hash es correcto
    echo PHP_EOL . "=== Verificando contraseña ===" . PHP_EOL;
    
    $sql2 = "SELECT password_hash FROM usuarios WHERE username = 'admin'";
    $stmt2 = $conn->query($sql2);
    $user = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify('admin123', $user['password_hash'])) {
        echo "✅ Verificación exitosa! La contraseña 'admin123' funciona correctamente." . PHP_EOL;
    } else {
        echo "❌ Error en la verificación." . PHP_EOL;
    }
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . PHP_EOL;
    
    // Si la base de datos no existe, dar instrucciones
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo PHP_EOL . "La base de datos no existe. Sigue estos pasos:" . PHP_EOL;
        echo "1. Abre phpMyAdmin: http://localhost/phpmyadmin" . PHP_EOL;
        echo "2. Importa el archivo: database/schema.sql" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>
