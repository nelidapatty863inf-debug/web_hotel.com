<?php
/**
 * EDITAR / CREAR HABITACIÓN - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Configurar moneda en Bolivianos
define('CURRENCY_SYMBOL', 'Bs. ');
define('CURRENCY_CODE', 'BOB');

$id = $_GET['id'] ?? 0;
$room = null;
$error = '';
$success = '';

// Obtener habitación si es edición
if ($id > 0) {
    $sql = "SELECT * FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        header('Location: rooms.php');
        exit;
    }
}

// Obtener categorías
$categories = $conn->query("SELECT * FROM room_categories ORDER BY sort_order");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $room_number = sanitizeInput($_POST['room_number']);
    $category_id = intval($_POST['category_id']);
    $description = sanitizeInput($_POST['description']);
    $amenities = sanitizeInput($_POST['amenities']);
    $capacity = intval($_POST['capacity']);
    $bed_type = sanitizeInput($_POST['bed_type']);
    $size_sqm = intval($_POST['size_sqm']);
    $price_per_night = floatval($_POST['price_per_night']);
    $weekend_price = !empty($_POST['weekend_price']) ? floatval($_POST['weekend_price']) : null;
    $total_units = intval($_POST['total_units']);
    $status = $_POST['status'];
    
    if ($id > 0) {
        // Actualizar
        $sql = "UPDATE rooms SET 
                name = ?, room_number = ?, category_id = ?, description = ?, 
                amenities = ?, capacity = ?, bed_type = ?, size_sqm = ?, 
                price_per_night = ?, weekend_price = ?, total_units = ?, status = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissssiddisi", 
            $name, $room_number, $category_id, $description,
            $amenities, $capacity, $bed_type, $size_sqm,
            $price_per_night, $weekend_price, $total_units, $status, $id
        );
    } else {
        // Insertar nueva
        $sql = "INSERT INTO rooms (name, room_number, category_id, description, amenities, 
                capacity, bed_type, size_sqm, price_per_night, weekend_price, total_units, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissssiddis", 
            $name, $room_number, $category_id, $description,
            $amenities, $capacity, $bed_type, $size_sqm,
            $price_per_night, $weekend_price, $total_units, $status
        );
    }
    
    if ($stmt->execute()) {
        $success = '✅ Habitación guardada correctamente';
        if ($id == 0) {
            $id = $conn->insert_id;
        }
        // Recargar datos
        $sql = "SELECT * FROM rooms WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
    } else {
        $error = '❌ Error al guardar la habitación';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Editar' : 'Nueva' ?> Habitación - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
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
            min-height: 100px;
        }

        /* Select2 personalizado */
        .select2-container--bootstrap-5 .select2-selection {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.5rem;
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

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
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

        /* Precio input */
        .price-input-group {
            position: relative;
        }

        .price-input-group::before {
            content: '<?= CURRENCY_SYMBOL ?>';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-weight: 600;
        }

        .price-input-group input {
            padding-left: 40px;
        }

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
                <li><a href="rooms.php" class="active"><i class="fas fa-bed"></i> <span>Habitaciones</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                <li><a href="availability.php"><i class="fas fa-calendar-check"></i> <span>Disponibilidad</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="admin-content w-100">
            <div class="admin-header">
                <h1><i class="fas fa-<?= $id ? 'edit' : 'plus-circle' ?>"></i> <?= $id ? 'Editar' : 'Nueva' ?> Habitación</h1>
                <a href="rooms.php" class="btn-back">
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
                            <label><i class="fas fa-tag"></i> Nombre de la habitación *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($room['name'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Número de habitación</label>
                            <input type="text" name="room_number" class="form-control" value="<?= htmlspecialchars($room['room_number'] ?? '') ?>" placeholder="Ej: 101">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> Categoría *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">📌 Seleccionar categoría</option>
                                <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= (($room['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= $cat['name'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-bed"></i> Tipo de cama *</label>
                            <select name="bed_type" class="form-select" required>
                                <option value="">🛏️ Seleccionar tipo de cama</option>
                                <option value="1 King Bed" <?= (($room['bed_type'] ?? '') == '1 King Bed') ? 'selected' : '' ?>>👑 1 King Bed</option>
                                <option value="2 Queen Beds" <?= (($room['bed_type'] ?? '') == '2 Queen Beds') ? 'selected' : '' ?>>👑👑 2 Queen Beds</option>
                                <option value="2 King Beds" <?= (($room['bed_type'] ?? '') == '2 King Beds') ? 'selected' : '' ?>>👑👑 2 King Beds</option>
                                <option value="1 Queen Bed" <?= (($room['bed_type'] ?? '') == '1 Queen Bed') ? 'selected' : '' ?>>👑 1 Queen Bed</option>
                                <option value="2 Single Beds" <?= (($room['bed_type'] ?? '') == '2 Single Beds') ? 'selected' : '' ?>>🛏️🛏️ 2 Single Beds</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Capacidad (personas) *</label>
                            <input type="number" name="capacity" class="form-control" value="<?= $room['capacity'] ?? 2 ?>" min="1" max="10" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-expand"></i> Tamaño (m²)</label>
                            <input type="number" name="size_sqm" class="form-control" value="<?= $room['size_sqm'] ?? '' ?>" placeholder="Ej: 45">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-cubes"></i> Unidades disponibles *</label>
                            <input type="number" name="total_units" class="form-control" value="<?= $room['total_units'] ?? 5 ?>" min="1" max="20" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group price-input-group">
                            <label><i class="fas fa-dollar-sign"></i> Precio por noche (<?= CURRENCY_SYMBOL ?>) *</label>
                            <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $room['price_per_night'] ?? '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group price-input-group">
                            <label><i class="fas fa-calendar-week"></i> Precio fin de semana (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="weekend_price" class="form-control" value="<?= $room['weekend_price'] ?? '' ?>" placeholder="Opcional">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?= (($room['status'] ?? '') == 'active') ? 'selected' : '' ?>>🟢 Activo</option>
                                <option value="maintenance" <?= (($room['status'] ?? '') == 'maintenance') ? 'selected' : '' ?>>🟡 Mantenimiento</option>
                                <option value="inactive" <?= (($room['status'] ?? '') == 'inactive') ? 'selected' : '' ?>>🔴 Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <label><i class="fas fa-align-left"></i> Descripción</label>
                    <textarea name="description" rows="4" class="form-control" placeholder="Descripción detallada de la habitación..."><?= htmlspecialchars($room['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-concierge-bell"></i> Servicios (separados por coma)</label>
                    <textarea name="amenities" rows="3" class="form-control" placeholder="Ej: WiFi, TV 50\", Mini Bar, Jacuzzi, Aire Acondicionado"><?= htmlspecialchars($room['amenities'] ?? '') ?></textarea>
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Ingresa los servicios separados por coma</small>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> <?= $id ? 'Actualizar' : 'Guardar' ?> Habitación
                    </button>
                    <a href="rooms.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inicializar Select2
        $(document).ready(function() {
            $('.form-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
        
        // Validación de precios
        const priceInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
        priceInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 0) this.value = 0;
                if (this.value > 10000) this.value = 10000;
            });
        });
        
        // Confirmación antes de cancelar
        document.querySelector('.btn-cancel')?.addEventListener('click', function(e) {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, select, textarea');
            let hasChanges = false;
            
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.value !== '') {
                    hasChanges = true;
                }
            });
            
            if (hasChanges && !confirm('¿Estás seguro de que deseas cancelar? Los cambios no guardados se perderán.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>