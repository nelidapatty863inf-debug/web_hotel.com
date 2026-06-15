<?php
/**
 * REGISTRO DE NUEVOS USUARIOS - VERSIÓN MEJORADA CON BOOTSTRAP
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address']
    ];
    
    $result = registerUser($conn, $data);
    
    if ($result['success']) {
        $success = '✅ Registro exitoso. Ya puedes iniciar sesión.';
        // Limpiar formulario
        $_POST = [];
    } else {
        $error = '❌ ' . $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .register-container {
            max-width: 550px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .register-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .register-header .icon i {
            font-size: 2rem;
            color: white;
        }

        .register-header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.85rem;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.85rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.3s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            border: none;
        }

        .alert-error {
            background: #fee;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .alert-success {
            background: #e8f8f5;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #ecf0f1;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }

        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #2ecc71; }

        .requirements {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.7rem;
        }

        .requirements p {
            margin-bottom: 0.25rem;
            color: #7f8c8d;
        }

        .requirements i {
            margin-right: 5px;
        }

        .requirements .valid {
            color: #2ecc71;
        }

        .requirements .invalid {
            color: #e74c3c;
        }

        @media (max-width: 576px) {
            .register-card {
                padding: 1.5rem;
            }
            .register-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Crear Cuenta</h1>
                <p>Regístrate para hacer tus reservas y obtener beneficios exclusivos</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <div class="mt-2">
                        <a href="admin/login.php" class="btn btn-sm btn-success rounded-pill px-3">Iniciar Sesión →</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombre *</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required placeholder="Ej: Juan">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Apellido *</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required placeholder="Ej: Pérez">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="tu@email.com">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="Ej: 12345678">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Tu dirección completa"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Contraseña *</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Mínimo 6 caracteres">
                    <div id="passwordStrength" class="password-strength"></div>
                    <div class="requirements" id="passwordRequirements">
                        <p><i class="fas fa-circle"></i> Mínimo 6 caracteres</p>
                        <p><i class="fas fa-circle"></i> Al menos una mayúscula</p>
                        <p><i class="fas fa-circle"></i> Al menos un número</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirmar Contraseña *</label>
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required placeholder="Repite tu contraseña">
                    <small id="confirmMessage" class="text-muted"></small>
                </div>
                
                <button type="submit" class="btn-register" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Registrarse
                </button>
            </form>
            
            <div class="login-link">
                <p>¿Ya tienes cuenta? <a href="admin/login.php"><i class="fas fa-sign-in-alt"></i> Inicia sesión aquí</a></p>
                <p class="mb-0"><a href="index.php"><i class="fas fa-arrow-left"></i> Volver al inicio</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Medidor de fortaleza de contraseña
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        const strengthDiv = document.getElementById('passwordStrength');
        const confirmMessage = document.getElementById('confirmMessage');
        const submitBtn = document.getElementById('submitBtn');
        
        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            let message = '';
            let className = '';
            
            // Requisitos
            const hasMinLength = password.length >= 6;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            if (hasMinLength) strength++;
            if (hasUpperCase) strength++;
            if (hasNumber) strength++;
            
            // Actualizar requisitos visuales
            document.querySelector('#passwordRequirements p:nth-child(1) i').className = hasMinLength ? 'fas fa-check-circle valid' : 'fas fa-circle invalid';
            document.querySelector('#passwordRequirements p:nth-child(2) i').className = hasUpperCase ? 'fas fa-check-circle valid' : 'fas fa-circle invalid';
            document.querySelector('#passwordRequirements p:nth-child(3) i').className = hasNumber ? 'fas fa-check-circle valid' : 'fas fa-circle invalid';
            
            switch(strength) {
                case 0:
                case 1:
                    message = '🔴 Contraseña débil';
                    className = 'strength-weak';
                    break;
                case 2:
                    message = '🟡 Contraseña media';
                    className = 'strength-medium';
                    break;
                case 3:
                    message = '🟢 Contraseña fuerte';
                    className = 'strength-strong';
                    break;
            }
            
            if (password.length > 0) {
                strengthDiv.innerHTML = `<span class="${className}">${message}</span>`;
            } else {
                strengthDiv.innerHTML = '';
            }
            
            // Verificar coincidencia
            checkPasswordMatch();
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    confirmMessage.innerHTML = '<i class="fas fa-check-circle" style="color:#2ecc71"></i> Las contraseñas coinciden';
                    confirmMessage.style.color = '#2ecc71';
                } else {
                    confirmMessage.innerHTML = '<i class="fas fa-times-circle" style="color:#e74c3c"></i> Las contraseñas no coinciden';
                    confirmMessage.style.color = '#e74c3c';
                }
            } else {
                confirmMessage.innerHTML = '';
            }
        }
        
        passwordInput.addEventListener('input', updatePasswordStrength);
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        // Validación del formulario
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const hasMinLength = password.length >= 6;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            if (password !== confirm) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                return false;
            }
            
            if (!hasMinLength || !hasUpperCase || !hasNumber) {
                e.preventDefault();
                alert('❌ La contraseña debe tener al menos 6 caracteres, una mayúscula y un número');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>