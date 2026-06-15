<?php
/**
 * PANEL DE LOGIN PARA ADMINISTRADORES
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa tu email y contraseña';
    } else {
        $result = loginUser($conn, $email, $password);
        
        if ($result['success']) {
            // Recordar sesión
            if ($remember) {
                ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 días
            }
            
            // Redirigir según rol
            if (isAdmin()) {
                header('Location: dashboard.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Panel Admin <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h2 {
            color: #2c3e50;
            margin-top: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fee;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        .alert-success {
            background: #efe;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .login-footer a {
            color: #667eea;
            text-decoration: none;
        }
        .back-to-site {
            text-align: center;
            margin-top: 1rem;
        }
        .back-to-site a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div style="font-size: 3rem;">🏨</div>
                <h2><?= SITE_NAME ?></h2>
                <p>Panel de Administración</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>📧 Correo Electrónico</label>
                    <input type="email" name="email" required autofocus placeholder="admin@hotel.com">
                </div>
                
                <div class="form-group">
                    <label>🔒 Contraseña</label>
                    <input type="password" name="password" required placeholder="••••••">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="remember"> Recordarme
                    </label>
                    <a href="forgot_password.php" style="color: #667eea;">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn-login">Iniciar Sesión</button>
            </form>
            
            <div class="login-footer">
                <p>Credenciales de prueba:</p>
                <p style="font-size: 0.85rem; color: #666;">admin@hotel.com / admin123</p>
            </div>
        </div>
        
        <div class="back-to-site">
            <a href="../index.php">← Volver al sitio web</a>
        </div>
    </div>
</body>
</html>