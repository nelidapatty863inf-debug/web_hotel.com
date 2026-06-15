<?php
/**
 * DETALLE DE HABITACIÓN - CON REDIRECCIÓN SILENCIOSA
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$room_id = $_GET['id'] ?? 0;
$check_in = $_GET['check_in'] ?? date('Y-m-d', strtotime('+1 day'));
$check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+3 days'));
$adults = intval($_GET['adults'] ?? 2);
$children = intval($_GET['children'] ?? 0);

// Validar fechas
$dateValidation = validateDates($check_in, $check_out);
if (!$dateValidation['valid']) {
    $check_in = date('Y-m-d', strtotime('+1 day'));
    $check_out = date('Y-m-d', strtotime('+3 days'));
    $nights = 2;
} else {
    $nights = $dateValidation['nights'];
}

// Obtener información de la habitación
$sql = "SELECT r.*, rc.name as category_name, rc.description as category_description
        FROM rooms r 
        LEFT JOIN room_categories rc ON r.category_id = rc.id 
        WHERE r.id = ? AND r.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    header('Location: index.php');
    exit;
}

// Calcular precio total
$total_price = 0;
$price_breakdown = [];
$current = strtotime($check_in);
$end = strtotime($check_out);

while ($current < $end) {
    $date = date('Y-m-d', $current);
    $day_price = $room['price_per_night'];
    
    $dynamic = getDynamicPrice($conn, $room_id, $date);
    if ($dynamic) {
        $day_price = $dynamic;
    }
    
    $total_price += $day_price;
    $price_breakdown[] = ['date' => date('d/m/Y', $current), 'price' => $day_price];
    $current = strtotime('+1 day', $current);
}

// Verificar disponibilidad
$available = true;
$unavailable_dates = [];
$current = strtotime($check_in);
$end = strtotime($check_out);

while ($current < $end) {
    $date = date('Y-m-d', $current);
    $available_units = getRoomAvailability($conn, $room_id, $date);
    
    if ($available_units <= 0) {
        $available = false;
        $unavailable_dates[] = date('d/m/Y', $current);
    }
    $current = strtotime('+1 day', $current);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($room['name']) ?> - <?= SITE_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        header { background: #2c3e50; color: white; padding: 1rem 2rem; position: sticky; top: 0; z-index: 100; }
        nav { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .logo h1 { font-size: 1.5rem; margin: 0; }
        .nav-links { display: flex; list-style: none; gap: 2rem; margin: 0; }
        .nav-links a { color: white; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: #e74c3c; }
        .admin-link { background: #e74c3c; padding: 0.3rem 0.8rem; border-radius: 20px; }
        .container-custom { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .room-detail { display: grid; grid-template-columns: 1fr 400px; gap: 2rem; }
        .room-image { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; height: 300px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; }
        .room-image i { font-size: 5rem; color: white; opacity: 0.5; }
        .room-category-badge { display: inline-block; background: #667eea; color: white; padding: 0.25rem 1rem; border-radius: 50px; font-size: 0.8rem; margin-bottom: 1rem; }
        .room-meta { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 16px; }
        .room-meta div { background: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem; }
        .room-amenities ul { list-style: none; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; padding: 0; }
        .room-amenities li { padding: 0.5rem; background: #f8f9fa; border-radius: 8px; }
        .room-amenities li i { color: #2ecc71; margin-right: 8px; }
        .price-breakdown { background: #f8f9fa; border-radius: 16px; padding: 1rem; margin-top: 1.5rem; }
        .price-table { width: 100%; border-collapse: collapse; }
        .price-table td { padding: 0.5rem; border-bottom: 1px solid #ecf0f1; }
        .price-table .total-row { font-weight: bold; border-top: 2px solid #2c3e50; }
        .booking-box { background: white; border-radius: 20px; padding: 1.5rem; position: sticky; top: 100px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .booking-box h3 { color: #2c3e50; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ecf0f1; }
        .booking-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: #f8f9fa; padding: 1rem; border-radius: 12px; margin: 1rem 0; }
        .booking-guests { display: flex; gap: 1rem; justify-content: space-between; background: #f8f9fa; padding: 0.75rem; border-radius: 12px; margin: 1rem 0; }
        .price-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #ecf0f1; }
        .price-row.total { font-weight: bold; font-size: 1.1rem; border-top: 2px solid #2c3e50; border-bottom: none; margin-top: 0.5rem; padding-top: 1rem; }
        .btn-book { width: 100%; background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 1rem; border-radius: 50px; font-weight: 600; margin-top: 1rem; transition: all 0.3s; }
        .btn-book:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(46,204,113,0.3); }
        .btn-book:disabled { background: #95a5a6; cursor: not-allowed; }
        .alert-warning { background: #fdebd0; color: #e74c3c; border: none; border-radius: 12px; padding: 1rem; }
        footer { background: #2c3e50; color: white; text-align: center; padding: 2rem; margin-top: 3rem; }
        @media (max-width: 992px) { .room-detail { grid-template-columns: 1fr; } .booking-box { position: static; margin-top: 2rem; } }
        @media (max-width: 768px) { .nav-links { flex-wrap: wrap; justify-content: center; gap: 1rem; } nav { flex-direction: column; gap: 1rem; } .room-amenities ul { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>🏨 <?= SITE_NAME ?></h1></div>
            <ul class="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="index.php#rooms"><i class="fas fa-bed"></i> Habitaciones</a></li>
                <li><a href="my_bookings.php"><i class="fas fa-calendar-alt"></i> Mis Reservas</a></li>
                <?php if(isLoggedIn()): ?>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrito</a></li>
                    <li><a href="admin/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                    <?php if(isAdmin()): ?>
                        <li><a href="admin/dashboard.php" class="admin-link"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="admin/login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container-custom">
        <div class="room-detail">
            <div class="room-detail-info">
                <div class="room-image"><i class="fas fa-bed"></i></div>
                <h1><?= htmlspecialchars($room['name']) ?></h1>
                <div class="room-category-badge"><i class="fas fa-tag"></i> <?= $room['category_name'] ?></div>
                
                <div class="room-meta">
                    <div><i class="fas fa-users"></i> <?= $room['capacity'] ?> personas</div>
                    <div><i class="fas fa-bed"></i> <?= $room['bed_type'] ?></div>
                    <div><i class="fas fa-expand"></i> <?= $room['size_sqm'] ?> m²</div>
                    <div><i class="fas fa-hashtag"></i> Hab. <?= $room['room_number'] ?? 'N/A' ?></div>
                </div>
                
                <div class="room-description">
                    <h3><i class="fas fa-align-left"></i> Descripción</h3>
                    <p><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                </div>
                
                <div class="room-amenities">
                    <h3><i class="fas fa-concierge-bell"></i> Servicios incluidos</h3>
                    <ul>
                        <?php 
                        $amenities = explode(',', $room['amenities'] ?? '');
                        foreach($amenities as $amenity): 
                            if(trim($amenity)):
                        ?>
                            <li><i class="fas fa-check-circle"></i> <?= trim(htmlspecialchars($amenity)) ?></li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
                
                <?php if(!empty($price_breakdown)): ?>
                <div class="price-breakdown">
                    <h3><i class="fas fa-chart-line"></i> Desglose de precios</h3>
                    <table class="price-table">
                        <?php foreach($price_breakdown as $break): ?>
                        <tr><td><?= $break['date'] ?> <span class="text-muted">(<?= CURRENCY_SYMBOL ?>)</span></td><td class="text-end fw-bold"><?= number_format($break['price'], 2) ?></td></tr>
                        <?php endforeach; ?>
                        <tr class="total-row"><td><strong>Total <?= $nights ?> <?= $nights == 1 ? 'noche' : 'noches' ?></strong></td><td class="text-end"><strong><?= number_format($total_price, 2) ?></strong></td></tr>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="booking-box">
                <h3><i class="fas fa-calendar-check"></i> Reservar ahora</h3>
                
                <?php if(!$available): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No hay disponibilidad para las fechas seleccionadas.
                        <?php if(!empty($unavailable_dates)): ?>
                            <br>📅 Fechas no disponibles: <?= implode(', ', $unavailable_dates) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="booking-dates">
                    <div class="date-item"><label><i class="fas fa-calendar-alt"></i> Check-in</label><p><strong><?= date('d/m/Y', strtotime($check_in)) ?></strong></p><small class="text-muted"><?= CHECKIN_TIME ?> hrs</small></div>
                    <div class="date-item"><label><i class="fas fa-calendar-alt"></i> Check-out</label><p><strong><?= date('d/m/Y', strtotime($check_out)) ?></strong></p><small class="text-muted"><?= CHECKOUT_TIME ?> hrs</small></div>
                </div>
                
                <div class="booking-guests">
                    <div><i class="fas fa-user"></i> Adultos: <strong><?= $adults ?></strong></div>
                    <div><i class="fas fa-child"></i> Niños: <strong><?= $children ?></strong></div>
                </div>
                
                <div class="booking-price-detail">
                    <div class="price-row"><span>Precio por noche:</span><span class="fw-bold"><?= formatPrice($room['price_per_night']) ?></span></div>
                    <div class="price-row"><span>Noches:</span><span><?= $nights ?></span></div>
                    <div class="price-row total"><span>Total a pagar:</span><span class="text-success"><?= formatPrice($total_price) ?></span></div>
                    <small class="tax-note"><i class="fas fa-info-circle"></i> Impuestos no incluidos (<?= TOTAL_TAX_RATE * 100 ?>%)</small>
                </div>
                
                <form id="bookingForm">
                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                    <input type="hidden" name="check_in" value="<?= $check_in ?>">
                    <input type="hidden" name="check_out" value="<?= $check_out ?>">
                    <input type="hidden" name="adults" value="<?= $adults ?>">
                    <input type="hidden" name="children" value="<?= $children ?>">
                    
                    <?php if($available): ?>
                        <button type="submit" class="btn-book">
                            <i class="fas fa-shopping-cart"></i> Agregar al Carrito
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-book" disabled>
                            <i class="fas fa-ban"></i> No Disponible
                        </button>
                    <?php endif; ?>
                </form>
                
                <div class="booking-info mt-3">
                    <hr>
                    <p class="mb-2"><i class="fas fa-info-circle text-primary"></i> Información importante:</p>
                    <ul class="small text-muted ps-3">
                        <li>Check-in a partir de las <?= CHECKIN_TIME ?> hrs</li>
                        <li>Check-out antes de las <?= CHECKOUT_TIME ?> hrs</li>
                        <li>Cancelación gratuita hasta <?= CANCELLATION_DAYS ?> días antes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container-custom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <script>
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        <?php if(!isLoggedIn()): ?>
            if (confirm('🔐 Debes iniciar sesión para realizar una reserva. ¿Deseas iniciar sesión ahora?')) {
                window.location.href = 'admin/login.php?redirect=room_details.php?id=<?= $room_id ?>&check_in=<?= $check_in ?>&check_out=<?= $check_out ?>';
            }
            return false;
        <?php endif; ?>
        
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
        btn.disabled = true;
        
        const formData = new FormData(this);
        
        fetch('api/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'cart.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'cart.php';
        });
    });
    </script>
</body>
</html>