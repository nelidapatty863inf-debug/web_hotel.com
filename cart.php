<?php
/**
 * CARRITO DE COMPRAS - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Configurar moneda en Bolivianos
// define('CURRENCY_SYMBOL', 'Bs. ');
// define('CURRENCY_CODE', 'BOB');

$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

// Obtener carrito
$cart_items = getReservationCart($conn, $session_id, $user_id);

$subtotal = 0;
$items_array = [];
$reservation_id = null;

while($item = $cart_items->fetch_assoc()) {
    $items_array[] = $item;
    $subtotal += $item['subtotal'];
    $reservation_id = $item['reservation_id'];
}

// Verificar travel protection
$has_travel_protection = false;
if ($reservation_id) {
    $sql = "SELECT id FROM travel_protection WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $has_travel_protection = $stmt->get_result()->num_rows > 0;
    
    if ($has_travel_protection) {
        $subtotal += TRAVEL_PROTECTION_FEE;
    }
}

$taxes = calculateTaxes($subtotal);

// Obtener datos de la reserva (fechas)
$reservation_data = null;
if ($reservation_id) {
    $sql = "SELECT check_in, check_out, adults, children FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $reservation_data = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - <?= SITE_NAME ?></title>
    
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
            background: #f5f5f5;
        }

        /* Header */
        header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo h1 {
            font-size: 1.5rem;
            margin: 0;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #e74c3c;
        }

        /* Contenedor principal */
        .container-custom {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Info card */
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #3498db;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        /* Items del carrito */
        .cart-item {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .item-price {
            text-align: right;
        }

        .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
        }

        /* Resumen */
        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            position: sticky;
            top: 100px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .cart-summary h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .summary-row.total {
            border-top: 2px solid #2c3e50;
            border-bottom: none;
            margin-top: 0.5rem;
            padding-top: 1rem;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Travel protection */
        .travel-protection {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .protection-price {
            margin-left: auto;
            font-weight: bold;
            color: #e74c3c;
        }

        .protection-info {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #7f8c8d;
        }

        /* Cupón */
        .coupon-section {
            margin: 1rem 0;
        }

        .coupon-input {
            display: flex;
            gap: 0.5rem;
        }

        .coupon-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 50px;
        }

        .btn-coupon {
            background: #3498db;
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 50px;
            transition: all 0.3s;
        }

        .btn-coupon:hover {
            background: #2980b9;
        }

        /* Botones */
        .btn-checkout {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
            color: white;
        }

        .btn-continue-shopping {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 50px;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-continue-shopping:hover {
            background: #7f8c8d;
            color: white;
        }

        .btn-remove {
            background: none;
            border: none;
            color: #e74c3c;
            margin-top: 0.5rem;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            color: #c0392b;
        }

        /* Login warning */
        .login-warning {
            background: #fdebd0;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
        }

        .login-warning a {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Empty cart */
        .empty-cart {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 20px;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            .item-price {
                text-align: center;
                margin-top: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            nav {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>🏨 <?= SITE_NAME ?></h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="index.php#rooms">Habitaciones</a></li>
                <li><a href="my_bookings.php">Mis Reservas</a></li>
                <?php if(isLoggedIn()): ?>
                    <li><a href="admin/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                    <?php if(isAdmin()): ?>
                        <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="admin/login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container-custom">
        <h1 class="mb-4"><i class="fas fa-shopping-cart text-primary"></i> Mi Carrito</h1>
        
        <?php if(empty($items_array)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <p class="mb-3">Tu carrito está vacío</p>
                <a href="index.php#rooms" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-bed"></i> Ver habitaciones
                </a>
            </div>
        <?php else: ?>
            <?php if($reservation_data): ?>
            <div class="info-card">
                <h3><i class="fas fa-calendar-alt"></i> 📅 Fechas de tu estancia</h3>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Check-in:</strong> <?= date('d/m/Y', strtotime($reservation_data['check_in'])) ?> <span class="text-muted">(<?= CHECKIN_TIME ?> hrs)</span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Check-out:</strong> <?= date('d/m/Y', strtotime($reservation_data['check_out'])) ?> <span class="text-muted">(<?= CHECKOUT_TIME ?> hrs)</span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Huéspedes:</strong> <?= $reservation_data['adults'] ?> adultos, <?= $reservation_data['children'] ?> niños</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <div class="col-lg-7">
                    <?php foreach($items_array as $item): ?>
                    <div class="cart-item" data-item-id="<?= $item['id'] ?>">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4><i class="fas fa-bed text-primary"></i> <?= htmlspecialchars($item['room_name']) ?></h4>
                                <p class="mb-1"><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($item['check_in'])) ?> - <?= date('d/m/Y', strtotime($item['check_out'])) ?></p>
                                <p class="mb-1"><i class="fas fa-moon"></i> <?= $item['nights'] ?> noches</p>
                                <p><i class="fas fa-tag"></i> <?= formatPrice($item['price_per_night']) ?> por noche</p>
                            </div>
                            <div class="col-md-4 item-price">
                                <div class="price"><?= formatPrice($item['subtotal']) ?></div>
                                <button class="btn-remove" onclick="removeFromCart(<?= $item['id'] ?>)">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-5">
                    <div class="cart-summary">
                        <h3><i class="fas fa-receipt"></i> Resumen de tu reserva</h3>
                        
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotal"><?= formatPrice($subtotal - ($has_travel_protection ? TRAVEL_PROTECTION_FEE : 0)) ?></span>
                        </div>
                        
                        <div class="travel-protection">
                            <label class="checkbox-label">
                                <input type="checkbox" id="travelProtection" <?= $has_travel_protection ? 'checked' : '' ?> onchange="toggleTravelProtection(this)">
                                <span><i class="fas fa-shield-alt text-success"></i> Protección de Viaje</span>
                                <span class="protection-price">+ <?= formatPrice(TRAVEL_PROTECTION_FEE) ?></span>
                            </label>
                            <div class="protection-info">
                                <i class="fas fa-info-circle"></i> Cancela sin penalización por enfermedad o accidente (aplica términos)
                            </div>
                        </div>
                        
                        <div class="summary-row">
                            <span>Impuesto ventas (<?= SALES_TAX_RATE * 100 ?>%):</span>
                            <span id="salesTax"><?= formatPrice($taxes['sales_tax']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Impuesto hotel (<?= HOTEL_TAX_RATE * 100 ?>%):</span>
                            <span id="hotelTax"><?= formatPrice($taxes['hotel_tax']) ?></span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total a pagar:</span>
                            <span id="totalDue" class="text-success fw-bold"><?= formatPrice($taxes['total_due']) ?></span>
                        </div>
                        
                        <div class="coupon-section">
                            <div class="coupon-input">
                                <input type="text" id="couponCode" class="form-control" placeholder="Código de descuento">
                                <button onclick="applyCoupon()" class="btn-coupon">Aplicar</button>
                            </div>
                            <div id="couponMessage" class="mt-2 small"></div>
                        </div>
                        
                        <?php if(!isLoggedIn()): ?>
                            <div class="login-warning">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <a href="admin/login.php?redirect=cart.php">Inicia sesión</a> para continuar con tu reserva
                            </div>
                        <?php endif; ?>
                        
                        <a href="checkout.php" class="btn-checkout" <?= !isLoggedIn() ? 'style="opacity:0.5; pointer-events:none;"' : '' ?>>
                            <i class="fas fa-credit-card"></i> Proceder al pago
                        </a>
                        
                        <a href="index.php#rooms" class="btn-continue-shopping">
                            <i class="fas fa-arrow-left"></i> Seguir comprando
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <div class="container-custom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <script src="assets/js/script.js"></script>
    <script>
        function updateTotals() {
            fetch('api/get_totals.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('subtotal').innerText = data.data.formatted_subtotal;
                        document.getElementById('salesTax').innerText = data.data.currency + data.data.sales_tax.toFixed(2);
                        document.getElementById('hotelTax').innerText = data.data.currency + data.data.hotel_tax.toFixed(2);
                        document.getElementById('totalDue').innerText = data.data.currency + data.data.total_due.toFixed(2);
                    }
                });
        }
        
        function removeFromCart(itemId) {
            if (confirm('¿Deseas eliminar esta habitación del carrito?')) {
                fetch('api/remove_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + itemId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.cart_count === 0) {
                            location.reload();
                        } else {
                            const item = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
                            if (item) item.remove();
                            updateTotals();
                        }
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
        
        function toggleTravelProtection(checkbox) {
            fetch('api/toggle_travel_protection.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'enabled=' + checkbox.checked
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTotals();
                } else {
                    alert(data.message);
                    checkbox.checked = !checkbox.checked;
                }
            });
        }
        
        function applyCoupon() {
            const coupon = document.getElementById('couponCode').value;
            if (!coupon) {
                document.getElementById('couponMessage').innerHTML = '<span class="text-danger">✗ Ingresa un código</span>';
                return;
            }
            
            fetch('api/apply_coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'coupon_code=' + encodeURIComponent(coupon)
            })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('couponMessage');
                if (data.success) {
                    msgDiv.innerHTML = '<span class="text-success">✓ ' + data.message + '</span>';
                    updateTotals();
                } else {
                    msgDiv.innerHTML = '<span class="text-danger">✗ ' + data.message + '</span>';
                }
            });
        }
    </script>
</body>
</html>