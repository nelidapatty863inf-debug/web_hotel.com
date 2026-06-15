<?php
/**
 * ACTUALIZAR ESTADO DE RESERVA - VERSIÓN MEJORADA CON BOOTSTRAP
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
$error = '';
$success = '';

// Obtener reserva
$sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header('Location: reservations.php');
    exit;
}

// Obtener items de la reserva
$sql_items = "SELECT ri.*, DATEDIFF(r.check_out, r.check_in) as nights 
              FROM reservation_items ri 
              JOIN reservations r ON ri.reservation_id = r.id 
              WHERE ri.reservation_id = ?";
$stmt = $conn->prepare($sql_items);
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result();

$subtotal = 0;
while($item = $items->fetch_assoc()) {
    $subtotal += $item['subtotal'];
}
$items->data_seek(0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    $payment_method = $_POST['payment_method'];
    $special_requests = sanitizeInput($_POST['special_requests']);
    
    $sql = "UPDATE reservations SET 
            status = ?, payment_status = ?, payment_method = ?, special_requests = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $status, $payment_status, $payment_method, $special_requests, $id);
    
    if ($stmt->execute()) {
        $success = '✅ Reserva actualizada correctamente';
        // Recargar datos
        $sql = "SELECT * FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();
    } else {
        $error = '❌ Error al actualizar la reserva';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Reserva - Admin <?= SITE_NAME ?></title>
    
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

        /* Tarjetas */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #3498db;
        }

        .info-card h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .info-card p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .items-table {
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .items-table th {
            background: #2c3e50;
            color: white;
            padding: 0.75rem;
        }

        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #ecf0f1;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        .btn-save {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }

        .btn-back {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            color: white;
        }

        /* Estados */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending { background: #f39c12; color: white; }
        .status-confirmed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        .status-checked_in { background: #3498db; color: white; }
        .status-checked_out { background: #95a5a6; color: white; }

        /* Alertas */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
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
            
            .items-table {
                font-size: 0.8rem;
            }
            
            .items-table th,
            .items-table td {
                padding: 0.5rem;
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
                <li><a href="reservations.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
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
                <h1><i class="fas fa-edit"></i> Actualizar Reserva</h1>
                <a href="reservation_view.php?id=<?= $id ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver a Detalles
                </a>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-custom mb-4">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success alert-custom mb-4">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            
            <!-- Información de la reserva -->
            <div class="info-card">
                <h4><i class="fas fa-info-circle"></i> Información de la Reserva</h4>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-hashtag"></i> Código:</strong> <span class="badge bg-dark"><?= $reservation['reservation_code'] ?></span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-user"></i> Cliente:</strong> <?= htmlspecialchars($reservation['first_name'] ?? 'Guest') ?> <?= htmlspecialchars($reservation['last_name'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?= $reservation['email'] ?? '-' ?></p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-calendar-check"></i> Check-in:</strong> <?= date('d/m/Y', strtotime($reservation['check_in'])) ?> (<?= CHECKIN_TIME ?>)</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-calendar-times"></i> Check-out:</strong> <?= date('d/m/Y', strtotime($reservation['check_out'])) ?> (<?= CHECKOUT_TIME ?>)</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-dollar-sign"></i> Total:</strong> <span class="fw-bold text-success"><?= formatPrice($reservation['total_amount'] ?? 0) ?></span></p>
                    </div>
                </div>
            </div>
            
            <!-- Habitaciones reservadas -->
            <div class="info-card">
                <h4><i class="fas fa-bed"></i> Habitaciones Reservadas</h4>
                <table class="items-table">
                    <thead>
                        <tr><th>Habitación</th><th>Precio/noche</th><th>Noches</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><i class="fas fa-door-open"></i> <?= htmlspecialchars($item['room_name']) ?></td>
                            <td><?= formatPrice($item['price_per_night']) ?></td>
                            <td><?= $item['nights'] ?> <?= $item['nights'] == 1 ? 'noche' : 'noches' ?></td>
                            <td><?= formatPrice($item['subtotal']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Formulario de actualización -->
            <form method="POST" class="form-card">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Estado de la reserva</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?= $reservation['status'] == 'pending' ? 'selected' : ?>>🕐 Pendiente</option>
                                <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : ?>>✅ Confirmada</option>
                                <option value="checked_in" <?= $reservation['status'] == 'checked_in' ? 'selected' : ?>>🏨 Check-in</option>
                                <option value="checked_out" <?= $reservation['status'] == 'checked_out' ? 'selected' : ?>>🚪 Check-out</option>
                                <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : ?>>❌ Cancelada</option>
                                <option value="no_show" <?= $reservation['status'] == 'no_show' ? 'selected' : ?>>👤 No Show</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Estado de pago</label>
                            <select name="payment_status" class="form-select">
                                <option value="pending" <?= ($reservation['payment_status'] ?? '') == 'pending' ? 'selected' : ?>>🕐 Pendiente</option>
                                <option value="paid" <?= ($reservation['payment_status'] ?? '') == 'paid' ? 'selected' : ?>>✅ Pagado</option>
                                <option value="refunded" <?= ($reservation['payment_status'] ?? '') == 'refunded' ? 'selected' : ?>>🔄 Reembolsado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-wallet"></i> Método de pago</label>
                            <select name="payment_method" class="form-select">
                                <option value="">📌 Seleccionar</option>
                                <option value="credit_card" <?= ($reservation['payment_method'] ?? '') == 'credit_card' ? 'selected' : ?>>💳 Tarjeta de Crédito</option>
                                <option value="paypal" <?= ($reservation['payment_method'] ?? '') == 'paypal' ? 'selected' : ?>>💰 PayPal</option>
                                <option value="transfer" <?= ($reservation['payment_method'] ?? '') == 'transfer' ? 'selected' : ?>>🏦 Transferencia Bancaria</option>
                                <option value="cash" <?= ($reservation['payment_method'] ?? '') == 'cash' ? 'selected' : ?>>💵 Efectivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Fecha de reserva</label>
                            <input type="text" value="<?= date('d/m/Y H:i:s', strtotime($reservation['created_at'])) ?>" class="form-control" disabled>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Solicitudes especiales</label>
                    <textarea name="special_requests" rows="3" class="form-control" placeholder="Notas o solicitudes especiales del cliente..."><?= htmlspecialchars($reservation['special_requests'] ?? '') ?></textarea>
                </div>
                
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Actualizar Reserva
                    </button>
                    <a href="reservation_view.php?id=<?= $id ?>" class="btn-back">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>