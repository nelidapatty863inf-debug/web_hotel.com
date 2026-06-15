<?php
/**
 * MIS RESERVAS - Historial de reservas del usuario - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

// Configurar moneda en Bolivianos

//define('CURRENCY_SYMBOL', 'Bs. ');

//define('CURRENCY_CODE', 'BOB');

$user_id = $_SESSION['user_id'];

// Obtener reservas del usuario
$sql = "SELECT r.*, 
        GROUP_CONCAT(ri.room_name SEPARATOR ', ') as rooms,
        COUNT(ri.id) as total_rooms
        FROM reservations r
        LEFT JOIN reservation_items ri ON r.id = ri.reservation_id
        WHERE r.user_id = ?
        GROUP BY r.id
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();

// Obtener estadísticas del usuario
$sql = "SELECT 
        COUNT(*) as total_reservations,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as active_reservations,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
        SUM(total_amount) as total_spent
        FROM reservations WHERE user_id = ? AND status IN ('confirmed', 'checked_out', 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservas - <?= SITE_NAME ?></title>
    
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

        /* Contenedor */
        .container-custom {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .profile-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-item {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 16px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .stat-item:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
        }

        /* Booking Cards */
        .booking-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #ecf0f1;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .booking-code {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .booking-code i {
            color: #667eea;
        }

        .booking-status {
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .booking-status.confirmed {
            background: #2ecc71;
            color: white;
        }
        .booking-status.pending {
            background: #f39c12;
            color: white;
        }
        .booking-status.cancelled {
            background: #e74c3c;
            color: white;
        }
        .booking-status.checked_in {
            background: #3498db;
            color: white;
        }
        .booking-status.checked_out {
            background: #95a5a6;
            color: white;
        }

        .booking-dates {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .date-badge {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .date-badge i {
            margin-right: 0.5rem;
            color: #667eea;
        }

        .booking-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .booking-info p {
            margin-bottom: 0.5rem;
        }

        .booking-info p:last-child {
            margin-bottom: 0;
        }

        .booking-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-view {
            background: #3498db;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view:hover {
            background: #2980b9;
            transform: translateY(-2px);
            color: white;
        }

        .btn-cancel {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cancel:hover {
            background: #c0392b;
            transform: translateY(-2px);
            color: white;
        }

        .btn-invoice {
            background: #2ecc71;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-invoice:hover {
            background: #27ae60;
            transform: translateY(-2px);
            color: white;
        }

        .empty-bookings {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 24px;
        }

        .empty-bookings i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
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
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            nav {
                flex-direction: column;
                gap: 1rem;
            }
            .container-custom {
                padding: 0 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .booking-header {
                flex-direction: column;
                text-align: center;
            }
            .booking-dates {
                justify-content: center;
            }
            .booking-actions {
                justify-content: center;
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
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="index.php#rooms"><i class="fas fa-bed"></i> Habitaciones</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrito</a></li>
                <li><a href="admin/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                <?php if(isAdmin()): ?>
                    <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container-custom">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Mis Reservas</h1>
            <p class="mb-0">Bienvenido, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></p>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_reservations'] ?? 0) ?></div>
                    <div><i class="fas fa-calendar-alt"></i> Reservas totales</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['active_reservations'] ?? 0) ?></div>
                    <div><i class="fas fa-check-circle"></i> Reservas activas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= formatPrice($stats['total_spent'] ?? 0) ?></div>
                    <div><i class="fas fa-dollar-sign"></i> Total gastado</div>
                </div>
            </div>
        </div>
        
        <h2 class="mb-4"><i class="fas fa-history text-primary"></i> Historial de Reservas</h2>
        
        <?php if($reservations->num_rows == 0): ?>
            <div class="empty-bookings">
                <i class="fas fa-calendar-times"></i>
                <p class="mb-3">No tienes reservas aún</p>
                <a href="index.php#rooms" class="btn-view">
                    <i class="fas fa-bed"></i> Ver habitaciones
                </a>
            </div>
        <?php else: ?>
            <?php while($booking = $reservations->fetch_assoc()): ?>
            <div class="booking-card">
                <div class="booking-header">
                    <span class="booking-code">
                        <i class="fas fa-ticket-alt"></i> <?= $booking['reservation_code'] ?>
                    </span>
                    <span class="booking-status <?= $booking['status'] ?>">
                        <?php 
                            switch($booking['status']) {
                                case 'confirmed': echo '<i class="fas fa-check-circle"></i> Confirmada'; break;
                                case 'pending': echo '<i class="fas fa-clock"></i> Pendiente'; break;
                                case 'cancelled': echo '<i class="fas fa-times-circle"></i> Cancelada'; break;
                                case 'checked_in': echo '<i class="fas fa-hotel"></i> En curso'; break;
                                case 'checked_out': echo '<i class="fas fa-check-double"></i> Finalizada'; break;
                                default: echo $booking['status'];
                            }
                        ?>
                    </span>
                </div>
                
                <div class="booking-dates">
                    <div class="date-badge">
                        <i class="fas fa-calendar-check"></i> Check-in: <strong><?= date('d/m/Y', strtotime($booking['check_in'])) ?></strong>
                    </div>
                    <div class="date-badge">
                        <i class="fas fa-calendar-times"></i> Check-out: <strong><?= date('d/m/Y', strtotime($booking['check_out'])) ?></strong>
                    </div>
                    <div class="date-badge">
                        <i class="fas fa-moon"></i> Noches: <strong><?= calculateNights($booking['check_in'], $booking['check_out']) ?></strong>
                    </div>
                </div>
                
                <div class="booking-info">
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fas fa-bed text-primary"></i> <strong>Habitaciones:</strong> <?= htmlspecialchars($booking['rooms']) ?></p>
                            <p><i class="fas fa-door-open text-primary"></i> <strong>Total habitaciones:</strong> <?= $booking['total_rooms'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-users text-primary"></i> <strong>Huéspedes:</strong> <?= $booking['adults'] ?> adultos, <?= $booking['children'] ?> niños</p>
                            <p><i class="fas fa-dollar-sign text-primary"></i> <strong>Total:</strong> <span class="fw-bold text-success"><?= formatPrice($booking['total_amount'] ?? 0) ?></span></p>
                        </div>
                    </div>
                    <p class="mb-0"><i class="fas fa-calendar-alt text-primary"></i> <strong>Reservado el:</strong> <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></p>
                </div>
                
                <div class="booking-actions">
                    <a href="reservation_details.php?id=<?= $booking['id'] ?>" class="btn-view">
                        <i class="fas fa-eye"></i> Ver detalles
                    </a>
                    <?php if($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                        <?php 
                        $check_in_date = new DateTime($booking['check_in']);
                        $today = new DateTime();
                        $diff = $today->diff($check_in_date)->days;
                        if ($check_in_date > $today && $diff >= CANCELLATION_DAYS):
                        ?>
                        <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="btn-cancel" onclick="return confirm('¿Estás seguro de cancelar esta reserva? Esta acción no se puede deshacer.')">
                            <i class="fas fa-times"></i> Cancelar reserva
                        </a>
                    <?php endif; endif; ?>
                    <a href="invoice.php?id=<?= $booking['id'] ?>" class="btn-invoice">
                        <i class="fas fa-file-invoice"></i> Ver factura
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
    
    <footer>
        <div class="container-custom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>