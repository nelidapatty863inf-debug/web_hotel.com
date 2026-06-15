<?php
/**
 * SISTEMA DE AUTENTICACIÓN - COMPLETO Y CORREGIDO
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// =====================================================
// VERIFICAR SESIÓN ACTIVA
// =====================================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// =====================================================
// VERIFICAR SI ES ADMINISTRADOR
// =====================================================
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// =====================================================
// VERIFICAR SI ES MANAGER
// =====================================================
function isManager() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'manager';
}

// =====================================================
// REQUERIR LOGIN
// =====================================================
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . 'admin/login.php');
        exit;
    }
}

// =====================================================
// REQUERIR ADMIN
// =====================================================
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
}

// =====================================================
// REQUERIR MANAGER O ADMIN
// =====================================================
function requireManager() {
    if (!isAdmin() && !isManager()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
}

// =====================================================
// LOGIN DE USUARIO
// =====================================================
function loginUser($conn, $email, $password) {
    // Limpiar email
    $email = trim($email);
    
    // Buscar usuario por email
    $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['user_phone'] = $user['phone'] ?? '';
            
            // Actualizar último login
            $update = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
            $conn->query($update);
            
            // Registrar log
            writeLog("Login exitoso: $email", 'info');
            
            return ['success' => true, 'user' => $user];
        } else {
            writeLog("Contraseña incorrecta para: $email", 'warning');
        }
    } else {
        writeLog("Usuario no encontrado: $email", 'warning');
    }
    
    return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
}

// =====================================================
// REGISTRO DE USUARIO
// =====================================================
function registerUser($conn, $data) {
    // Validar email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    
    // Validar contraseña
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
    }
    
    // Verificar si el email ya existe
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'El email ya está registrado'];
    }
    
    // Crear usuario
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (email, password, first_name, last_name, phone, address, role, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'user', 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", 
        $data['email'], 
        $hashed_password, 
        $data['first_name'], 
        $data['last_name'], 
        $data['phone'], 
        $data['address']
    );
    
    if ($stmt->execute()) {
        writeLog("Nuevo usuario registrado: " . $data['email'], 'info');
        return ['success' => true, 'user_id' => $conn->insert_id];
    }
    
    return ['success' => false, 'message' => 'Error al registrar usuario'];
}

// =====================================================
// CERRAR SESIÓN
// =====================================================
function logoutUser() {
    if (isLoggedIn()) {
        writeLog("Usuario cerró sesión: " . $_SESSION['user_email'], 'info');
    }
    
    $_SESSION = array();
    
    // Destruir cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// =====================================================
// OBTENER USUARIO ACTUAL
// =====================================================
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT id, email, first_name, last_name, phone, address, role, status, created_at, last_login 
            FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// =====================================================
// ACTUALIZAR PERFIL DE USUARIO
// =====================================================
function updateUserProfile($conn, $user_id, $data) {
    $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", 
        $data['first_name'], 
        $data['last_name'], 
        $data['phone'], 
        $data['address'], 
        $user_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
        $_SESSION['user_first_name'] = $data['first_name'];
        $_SESSION['user_last_name'] = $data['last_name'];
        $_SESSION['user_phone'] = $data['phone'];
        
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Error al actualizar perfil'];
}

// =====================================================
// CAMBIAR CONTRASEÑA
// =====================================================
function changePassword($conn, $user_id, $current_password, $new_password, $confirm_password) {
    // Verificar contraseña actual
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
    }
    
    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
    }
    
    if ($new_password !== $confirm_password) {
        return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        writeLog("Usuario cambió contraseña: ID $user_id", 'info');
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Error al cambiar contraseña'];
}

// =====================================================
// OBTENER REDIRECT DESPUÉS DE LOGIN
// =====================================================
function getLoginRedirect() {
    if (isset($_SESSION['login_redirect'])) {
        $redirect = $_SESSION['login_redirect'];
        unset($_SESSION['login_redirect']);
        return $redirect;
    }
    
    if (isAdmin()) {
        return SITE_URL . 'admin/dashboard.php';
    }
    
    return SITE_URL . 'index.php';
}
?>