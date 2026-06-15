<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/email.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$session_id = $_SESSION['reservation_session'];

// Obtener reserva pendiente
$sql = "SELECT r.*, u.first_name, u.last_name, u.email 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE (r.session_id = ? OR r.user_id = ?) AND r.status = 'pending'
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header('Location: cart.php');
    exit;
}

// Obtener items
$sql = "SELECT ri.*, DATEDIFF(r.check_out, r.check_in) as nights 
        FROM reservation_items ri 
        JOIN reservations r ON ri.reservation_id = r.id 
        WHERE ri.reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation['id']);
$stmt->execute();
$items = $stmt->get_result();

$subtotal = 0;
while($item = $items->fetch_assoc()) {
    $subtotal += $item['subtotal'];
}

// Travel protection
$sql = "SELECT id FROM travel_protection WHERE reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation['id']);
$stmt->execute();
$has_travel_protection = $stmt->get_result()->num_rows > 0;
if ($has_travel_protection) {
    $subtotal += TRAVEL_PROTECTION_FEE;
}

$discount = floatval($reservation['discount'] ?? 0);
$subtotal_after_discount = $subtotal - $discount;
$taxes = calculateTaxes($subtotal_after_discount);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $special_requests = sanitizeInput($_POST['special_requests'] ?? '');
    $total = $taxes['total_due'];
    $reservation_id = $reservation['id'];
    $payment_status = 'paid';
    
    // ACTUALIZAR RESERVA - CORREGIDO: 6 signos ?, 6 variables
    $sql = "UPDATE reservations SET 
            user_id = ?, 
            status = 'confirmed', 
            payment_method = ?, 
            payment_status = ?,
            total_amount = ?,
            special_requests = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // 7 signos ? en la consulta (user_id, payment_method, payment_status, total_amount, special_requests, id)
    // Tipos: i=integer, s=string, d=decimal
    $stmt->bind_param("issdsi", $user_id, $payment_method, $payment_status, $total, $special_requests, $reservation_id);
    $stmt->execute();
    
    // Enviar email
    $email_details = "Reserva #" . $reservation['reservation_code'] . "\n";
    $email_details .= "Total: " . formatPrice($total) . "\n";
    $email_details .= "Fechas: " . date('d/m/Y', strtotime($reservation['check_in'])) . " - " . date('d/m/Y', strtotime($reservation['check_out']));
    sendConfirmationEmail($_SESSION['user_email'], $reservation['reservation_code'], $email_details);
    
    header('Location: confirmation.php?id=' . $reservation_id);
    exit;
}

$total = $taxes['total_due'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Reserva - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        header { background: #2c3e50; color: white; padding: 1rem 2rem; }
        nav { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .logo h1 { font-size: 1.5rem; margin: 0; }
        .nav-links { display: flex; list-style: none; gap: 2rem; margin: 0; }
        .nav-links a { color: white; text-decoration: none; }
        .container-custom { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .payment-method { flex: 1; padding: 1rem; border: 2px solid #ddd; border-radius: 12px; text-align: center; cursor: pointer; background: white; margin: 0.5rem; }
        .payment-method.selected { border-color: #2ecc71; background: #e8f8f5; }
        .payment-method i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
        .btn-pay { background: #2ecc71; color: white; border: none; padding: 1rem; border-radius: 50px; width: 100%; margin-top: 1rem; }
        .checkout-summary { background: white; border-radius: 20px; padding: 1.5rem; position: sticky; top: 100px; }
        .summary-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .summary-row.total { border-top: 2px solid #333; font-weight: bold; }
        footer { background: #2c3e50; color: white; text-align: center; padding: 2rem; margin-top: 3rem; }
        @media (max-width: 768px) { .nav-links { flex-wrap: wrap; gap: 1rem; } nav { flex-direction: column; } }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>🏨 <?= SITE_NAME ?></h1></div>
            <ul class="nav-links">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="cart.php">← Volver al carrito</a></li>
                <li><a href="admin/logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <div class="container-custom">
        <h1 class="mb-4">Finalizar Reserva</h1>
        
        <div class="row">
            <div class="col-lg-7">
                <div style="background:white; border-radius:20px; padding:1.5rem;">
                    <h3>Datos del cliente</h3>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                    <p><strong>Email:</strong> <?= $_SESSION['user_email'] ?></p>
                    
                    <h3 class="mt-4">Método de pago</h3>
                    <div class="payment-methods d-flex">
                        <div class="payment-method" data-method="tarjeta" onclick="selectPayment('tarjeta')">
                            <i class="fas fa-credit-card"></i><span>Tarjeta</span>
                        </div>
                        <div class="payment-method" data-method="yape" onclick="selectPayment('yape')">
                            <i class="fas fa-mobile-alt"></i><span>Yape</span>
                        </div>
                        <div class="payment-method" data-method="qr" onclick="selectPayment('qr')">
                            <i class="fas fa-qrcode"></i><span>QR</span>
                        </div>
                    </div>
                    
                    <form method="POST" id="checkoutForm">
                        <input type="hidden" name="payment_method" id="paymentMethod">
                        
                        <div id="yapeInfo" style="display:none;" class="mt-3 p-3 bg-light rounded">
                            <p><strong>📱 Yape Bolivia:</strong> <?= YAPE_NUMBER ?></p>
                            <p><strong>Monto:</strong> <?= formatPrice($total) ?></p>
                            <p><strong>Referencia:</strong> <?= $reservation['reservation_code'] ?></p>
                        </div>
                        
                        <div id="qrInfo" style="display:none;" class="mt-3 p-3 bg-light rounded text-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=Pago%20Hotel%20<?= $reservation['reservation_code'] ?>%20<?= $total ?>%20Bs." alt="QR">
                            <p class="mt-2"><strong><?= BANK_NAME ?></strong><br>Cuenta: <?= BANK_ACCOUNT ?></p>
                        </div>
                        
                        <div class="mt-3">
                            <label class="form-label">Solicitudes especiales</label>
                            <textarea name="special_requests" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn-pay">Confirmar Reserva</button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-5">
                <div class="checkout-summary">
                    <h3>Resumen de tu reserva</h3>
                    <?php $items->data_seek(0); while($item = $items->fetch_assoc()): ?>
                    <div class="summary-row">
                        <span><?= htmlspecialchars($item['room_name']) ?></span>
                        <span><?= formatPrice($item['subtotal']) ?></span>
                    </div>
                    <?php endwhile; ?>
                    <?php if($has_travel_protection): ?>
                    <div class="summary-row">
                        <span>Protección de Viaje</span>
                        <span><?= formatPrice(TRAVEL_PROTECTION_FEE) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($discount > 0): ?>
                    <div class="summary-row">
                        <span>Descuento</span>
                        <span>-<?= formatPrice($discount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Impuestos (13%)</span>
                        <span><?= formatPrice($taxes['total_taxes']) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total a pagar</span>
                        <span class="text-success"><?= formatPrice($total) ?></span>
                    </div>
                    <div class="mt-3 p-2 bg-light rounded">
                        <small>Código: <?= $reservation['reservation_code'] ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?></p>
    </footer>
    
    <script>
        let selectedMethod = '';
        function selectPayment(method) {
            selectedMethod = method;
            document.getElementById('paymentMethod').value = method;
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
            document.querySelector(`.payment-method[data-method="${method}"]`).classList.add('selected');
            document.getElementById('yapeInfo').style.display = method === 'yape' ? 'block' : 'none';
            document.getElementById('qrInfo').style.display = method === 'qr' ? 'block' : 'none';
        }
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (!selectedMethod) {
                e.preventDefault();
                alert('Selecciona un método de pago');
                return false;
            }
            return confirm('¿Confirmar la reserva?');
        });
        selectPayment('tarjeta');
    </script>
</body>
</html>