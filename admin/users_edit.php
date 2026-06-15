<?php
/**
 * EDITAR / CREAR / ELIMINAR USUARIO - VERSIÓN MEJORADA CON BOOTSTRAP
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Eliminar usuario
if (isset($_GET['delete']) && $_GET['delete'] != $_SESSION['user_id']) {
    $delete_id = intval($_GET['delete']);
    
    // Verificar que no sea el último admin
    $sql = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
    $result = $conn->query($sql);
    $admin_count = $result->fetch_assoc()['admin_count'];
    
    if ($admin_count <= 1) {
        $error = '❌ No se puede eliminar al último administrador del sistema';
    } else {
        $sql = "DELETE FROM users WHERE id = ? AND role != 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['admin_success'] = '✅ Usuario eliminado correctamente';
            header('Location: users.php');
            exit;
        } else {
            $error = '❌ Error al eliminar el usuario';
        }
    }
}

// Obtener usuario si es edición
$user = null;
if ($id > 0) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        header('Location: users.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'] ?? '';
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Email inválido';
    } elseif ($id > 0) {
        // Actualizar usuario existente
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET 
                    email = ?, first_name = ?, last_name = ?, phone = ?, 
                    address = ?, role = ?, status = ?, password = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $email, $first_name, $last_name, $phone, $address, $role, $status, $hashed_password, $id);
        } else {
            $sql = "UPDATE users SET 
                    email = ?, first_name = ?, last_name = ?, phone = ?, 
                    address = ?, role = ?, status = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $email, $first_name, $last_name, $phone, $address, $role, $status, $id);
        }
        
        if ($stmt->execute()) {
            $success = '✅ Usuario actualizado correctamente';
            // Recargar datos
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = '❌ Error al actualizar el usuario';
        }
    } else {
        // Crear nuevo usuario
        if (empty($password)) {
            $error = '❌ La contraseña es requerida para nuevos usuarios';
        } elseif (strlen($password) < 6) {
            $error = '❌ La contraseña debe tener al menos 6 caracteres';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password, first_name, last_name, phone, address, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $email, $hashed_password, $first_name, $last_name, $phone, $address, $role, $status);
            
            if ($stmt->execute()) {
                $success = '✅ Usuario creado correctamente';
                $id = $conn->insert_id;
                header('Location: users_edit.php?id=' . $id . '&saved=1');
                exit;
            } else {
                $error = '❌ Error al crear el usuario. El email puede estar duplicado.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Editar' : 'Nuevo' ?> Usuario - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }

        .admin-sidebar h3 {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid #3d566e;
            font-size: 1.3rem;
            background: rgba(0,0,0,0.2);
        }

        .admin-sidebar ul {
            list-style: none;
            margin-top: 1rem;
            padding: 0;
        }

        .admin-sidebar li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.85rem 1.5rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .admin-sidebar li a i {
            width: 25px;
        }

        .admin-sidebar li a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #e74c3c;
        }

        .admin-sidebar li a.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #e74c3c;
        }

        /* Contenido principal */
        .admin-content {
            margin-left: 280px;
            padding: 2rem;
        }

        /* Header */
        .admin-header {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .admin-header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin: 0;
        }

        /* Formulario */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Botones */
        .btn-save {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
            color: white;
        }

        .btn-back {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            color: white;
        }

        /* Alertas */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Password strength */
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }
        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #2ecc71; }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
            }
            
            .admin-sidebar h3,
            .admin-sidebar li a span {
                display: none;
            }
            
            .admin-sidebar li a {
                justify-content: center;
                padding: 1rem;
            }
            
            .admin-sidebar li a i {
                margin: 0;
            }
            
            .admin-content {
                margin-left: 70px;
                padding: 1rem;
            }
            
            .form-card {
                padding: 1rem;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #2c3e50;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #1a252f;
        }
    </style>
</head>
<body>
    <div class="admin-container d-flex">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <h3>🏨 <?= SITE_NAME ?></h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="rooms.php"><i class="fas fa-bed"></i> <span>Habitaciones</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                <li><a href="availability.php"><i class="fas fa-calendar-check"></i> <span>Disponibilidad</span></a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="admin-content w-100">
            <div class="admin-header">
                <h1><i class="fas fa-<?= $id ? 'edit' : 'user-plus' ?>"></i> <?= $id ? 'Editar' : 'Nuevo' ?> Usuario</h1>
                <a href="users.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success alert-custom">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Contraseña <?= $id ? '<small class="text-muted">(dejar en blanco para no cambiar)</small>' : '*' ?></label>
                            <input type="password" name="password" id="password" class="form-control" <?= !$id ? 'required' : '' ?>>
                            <div id="passwordStrength" class="password-strength"></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombre</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Apellido</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Rol *</label>
                            <select name="role" class="form-select" required>
                                <option value="user" <?= (($user['role'] ?? '') == 'user') ? 'selected' : '' ?>>👤 Usuario</option>
                                <option value="manager" <?= (($user['role'] ?? '') == 'manager') ? 'selected' : '' ?>>📋 Manager</option>
                                <option value="admin" <?= (($user['role'] ?? '') == 'admin') ? 'selected' : '' ?>>👑 Administrador</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?= (($user['status'] ?? '') == 'active') ? 'selected' : '' ?>>🟢 Activo</option>
                                <option value="inactive" <?= (($user['status'] ?? '') == 'inactive') ? 'selected' : '' ?>>⚪ Inactivo</option>
                                <option value="banned" <?= (($user['status'] ?? '') == 'banned') ? 'selected' : '' ?>>🔴 Bloqueado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                            <textarea name="address" rows="3" class="form-control"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> <?= $id ? 'Actualizar' : 'Crear' ?> Usuario
                    </button>
                    <?php if($id && $id != $_SESSION['user_id']): ?>
                        <a href="users_edit.php?delete=<?= $id ?>" class="btn-delete" onclick="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')">
                            <i class="fas fa-trash-alt"></i> Eliminar Usuario
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Medidor de fortaleza de contraseña
        const passwordInput = document.getElementById('password');
        const strengthDiv = document.getElementById('passwordStrength');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let message = '';
                let className = '';
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;
                
                switch(strength) {
                    case 0:
                    case 1:
                        message = '🔴 Contraseña débil';
                        className = 'strength-weak';
                        break;
                    case 2:
                    case 3:
                        message = '🟡 Contraseña media';
                        className = 'strength-medium';
                        break;
                    case 4:
                    case 5:
                        message = '🟢 Contraseña fuerte';
                        className = 'strength-strong';
                        break;
                }
                
                if (password.length > 0) {
                    strengthDiv.innerHTML = `<span class="${className}">${message}</span>`;
                } else {
                    strengthDiv.innerHTML = '';
                }
            });
        }
    </script>
</body>
</html>