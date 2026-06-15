<?php
/**
 * VER DETALLE DE RESERVA - VERSIÓN CORREGIDA
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? 0;

// Obtener datos de la reserva
$sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone, u.address
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
$sql = "SELECT ri.*, DATEDIFF(r.check_out, r.check_in) as nights 
        FROM reservation_items ri 
        JOIN reservations r ON ri.reservation_id = r.id 
        WHERE ri.reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result();

// Obtener travel protection
$sql = "SELECT * FROM travel_protection WHERE reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$travel_protection = $stmt->get_result()->fetch_assoc();

// Calcular totales
$subtotal = 0;
while($item = $items->fetch_assoc()) {
    $subtotal += $item['subtotal'];
}
$items->data_seek(0);
$taxes = calculateTaxes($subtotal);
if ($travel_protection) $subtotal += TRAVEL_PROTECTION_FEE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva #<?= $reservation['reservation_code'] ?> - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-sidebar {
            width: 280px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-sidebar h3 {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid #3d566e;
        }
        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }
        .admin-sidebar li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 0.85rem 1.5rem;
            transition: 0.3s;
        }
        .admin-sidebar li a:hover {
            background: #1a252f;
        }
        .admin-sidebar li a.active {
            background: #1a252f;
            border-left: 3px solid #e74c3c;
        }
        .admin-content {
            margin-left: 280px;
            padding: 2rem;
        }
        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .btn-back, .btn-edit {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back { background: #95a5a6; color: white; }
        .btn-edit { background: #3498db; color: white; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 70px; }
            .admin-sidebar h3, .admin-sidebar li a span { display: none; }
            .admin-sidebar li a { text-align: center; padding: 1rem; }
            .admin-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <div class="admin-container d-flex">
        <div class="admin-sidebar">
            <h3>🏨 <?= SITE_NAME ?></h3>
            <ul>
                <li><a href="dashboard.php">📊 <span>Dashboard</span></a></li>
                <li><a href="rooms.php">🏨 <span>Habitaciones</span></a></li>
                <li><a href="reservations.php" class="active">📅 <span>Reservas</span></a></li>
                <li><a href="availability.php">📆 <span>Disponibilidad</span></a></li>
                <li><a href="users.php">👥 <span>Usuarios</span></a></li>
                <li><a href="reports.php">📈 <span>Reportes</span></a></li>
                <li><a href="../index.php">🌐 <span>Ver sitio</span></a></li>
                <li><a href="logout.php">🚪 <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <div class="admin-content w-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-white">Detalle de Reserva</h1>
                <div>
                    <a href="reservations.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
                    <a href="reservation_update.php?id=<?= $id ?>" class="btn-edit ms-2"><i class="fas fa-edit"></i> Editar</a>
                </div>
            </div>
            
            <div class="detail-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Código: <?= $reservation['reservation_code'] ?></h2>
                    <span class="status-badge" style="background: <?= $reservation['status'] == 'confirmed' ? '#2ecc71' : ($reservation['status'] == 'pending' ? '#f39c12' : '#e74c3c') ?>; color: white;">
                        <?= strtoupper($reservation['status']) ?>
                    </span>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h4>Información del Cliente</h4>
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($reservation['first_name'] ?? 'Guest') ?> <?= htmlspecialchars($reservation['last_name'] ?? '') ?></p>
                        <p><strong>Email:</strong> <?= $reservation['email'] ?? '-' ?></p>
                        <p><strong>Teléfono:</strong> <?= $reservation['phone'] ?? '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h4>Información de la Estancia</h4>
                        <p><strong>Check-in:</strong> <?= date('d/m/Y', strtotime($reservation['check_in'])) ?> (<?= CHECKIN_TIME ?>)</p>
                        <p><strong>Check-out:</strong> <?= date('d/m/Y', strtotime($reservation['check_out'])) ?> (<?= CHECKOUT_TIME ?>)</p>
                        <p><strong>Adultos:</strong> <?= $reservation['adults'] ?> | <strong>Niños:</strong> <?= $reservation['children'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="detail-card">
                <h4>Habitaciones Reservadas</h4>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr><th>Habitación</th><th>Precio/noche</th><th>Noches</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['room_name']) ?></td>
                            <td><?= formatPrice($item['price_per_night']) ?></td>
                            <td><?= $item['nights'] ?></td>
                            <td><?= formatPrice($item['subtotal']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($travel_protection): ?>
                        <tr style="background: #f8f9fa;">
                            <td colspan="3"><strong>🛡️ Travel Protection</strong></td>
                            <td><?= formatPrice(TRAVEL_PROTECTION_FEE) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="detail-card">
                <h4>Resumen de Pago</h4>
                <div style="max-width: 400px;">
                    <div class="d-flex justify-content-between py-2"><span>Subtotal:</span><span><?= formatPrice($subtotal) ?></span></div>
                    <div class="d-flex justify-content-between py-2"><span>Impuesto Ventas (<?= SALES_TAX_RATE * 100 ?>%):</span><span><?= formatPrice($taxes['sales_tax']) ?></span></div>
                    <div class="d-flex justify-content-between py-2"><span>Impuesto Hotel (<?= HOTEL_TAX_RATE * 100 ?>%):</span><span><?= formatPrice($taxes['hotel_tax']) ?></span></div>
                    <div class="d-flex justify-content-between py-2 border-top mt-2 pt-2 fw-bold"><span>Total:</span><span><?= formatPrice($taxes['total_due']) ?></span></div>
                    <div class="mt-3">
                        <p><strong>Método de pago:</strong> <?= $reservation['payment_method'] ?? 'Pendiente' ?></p>
                        <p><strong>Fecha de reserva:</strong> <?= date('d/m/Y H:i:s', strtotime($reservation['created_at'])) ?></p>
                    </div>
                </div>
            </div>
            
            <?php if($reservation['special_requests']): ?>
            <div class="detail-card">
                <h4>Solicitudes Especiales</h4>
                <p><?= nl2br(htmlspecialchars($reservation['special_requests'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>