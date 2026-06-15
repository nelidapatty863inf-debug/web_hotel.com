<?php
/**
 * CONFIRMACIÓN DE RESERVA - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Configurar moneda en Bolivianos
// define('CURRENCY_SYMBOL', 'Bs. ');
// define('CURRENCY_CODE', 'BOB');

$reservation_id = $_GET['id'] ?? 0;

$sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header('Location: index.php');
    exit;
}

// Obtener items
$sql = "SELECT * FROM reservation_items WHERE reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$items = $stmt->get_result();

// Obtener factura
$sql = "SELECT * FROM invoices WHERE reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación - <?= SITE_NAME ?></title>
    
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

        .confirmation-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .confirmation-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
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

        .success-icon {
            width: 80px;
            height: 80px;
            background: #d5f5e3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-icon i {
            font-size: 3rem;
            color: #2ecc71;
        }

        h1 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .reservation-code {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            border-radius: 16px;
            margin: 1.5rem 0;
            color: white;
        }

        .reservation-code h3 {
            font-size: 1.8rem;
            letter-spacing: 2px;
            font-weight: 700;
            margin: 0;
        }

        .reservation-code small {
            opacity: 0.9;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            text-align: left;
            margin: 1.5rem 0;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 12px;
        }

        .detail-item strong {
            display: block;
            color: #7f8c8d;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-item span {
            font-size: 1rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .items-table {
            width: 100%;
            margin: 1rem 0;
            border-collapse: collapse;
        }

        .items-table th {
            background: #2c3e50;
            color: white;
            padding: 0.75rem;
            text-align: left;
        }

        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .total-row {
            background: #f8f9fa;
            font-weight: bold;
        }

        .invoice-info {
            background: #e8f8f5;
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
        }

        .btn-print, .btn-home {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-print {
            background: #3498db;
            color: white;
        }

        .btn-print:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-home {
            background: #2ecc71;
            color: white;
        }

        .btn-home:hover {
            background: #27ae60;
            transform: translateY(-2px);
            color: white;
        }

        @media print {
            body {
                background: white;
            }
            .btn-actions, header, footer, .admin-sidebar, .nav-links {
                display: none !important;
            }
            .confirmation-card {
                box-shadow: none;
                padding: 0;
            }
            .reservation-code {
                background: #2c3e50;
            }
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            .confirmation-card {
                padding: 1.5rem;
            }
            .items-table {
                font-size: 0.8rem;
            }
            .items-table th, .items-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>¡Reserva Confirmada!</h1>
            <p class="text-muted">Tu reserva ha sido procesada exitosamente. Te hemos enviado un correo con los detalles.</p>
            
            <div class="reservation-code">
                <h3><?= $reservation['reservation_code'] ?></h3>
                <small><i class="fas fa-qrcode"></i> Código de reserva</small>
            </div>
            
            <div class="details-grid">
                <div class="detail-item">
                    <strong><i class="fas fa-user"></i> Nombre</strong>
                    <span><?= htmlspecialchars($reservation['first_name'] ?? 'Guest') ?> <?= htmlspecialchars($reservation['last_name'] ?? '') ?></span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-envelope"></i> Email</strong>
                    <span><?= $reservation['email'] ?? '-' ?></span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-phone"></i> Teléfono</strong>
                    <span><?= $reservation['phone'] ?? '-' ?></span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-calendar-alt"></i> Fecha de reserva</strong>
                    <span><?= date('d/m/Y H:i', strtotime($reservation['created_at'])) ?></span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-calendar-check"></i> Check-in</strong>
                    <span><?= date('d/m/Y', strtotime($reservation['check_in'])) ?> <span class="text-muted">(<?= CHECKIN_TIME ?> hrs)</span></span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-calendar-times"></i> Check-out</strong>
                    <span><?= date('d/m/Y', strtotime($reservation['check_out'])) ?> <span class="text-muted">(<?= CHECKOUT_TIME ?> hrs)</span></span>
                </div>
            </div>
            
            <h3 class="text-start mb-3"><i class="fas fa-bed"></i> Detalle de habitaciones</h3>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Habitación</th>
                            <th>Precio/noche</th>
                            <th>Noches</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><i class="fas fa-door-open text-primary"></i> <?= htmlspecialchars($item['room_name']) ?></td>
                            <td><?= formatPrice($item['price_per_night']) ?></td>
                            <td><?= $item['nights'] ?></td>
                            <td class="fw-bold"><?= formatPrice($item['subtotal']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="total-row">
                            <td colspan="3"><strong><i class="fas fa-dollar-sign"></i> Total pagado</strong></td>
                            <td><strong class="text-success"><?= formatPrice($reservation['total_amount']) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if($invoice): ?>
            <div class="invoice-info">
                <p class="mb-1"><strong><i class="fas fa-file-invoice"></i> Factura:</strong> <?= $invoice['invoice_number'] ?></p>
                <p class="mb-0"><strong><i class="fas fa-clock"></i> Fecha de pago:</strong> <?= date('d/m/Y H:i', strtotime($invoice['payment_date'])) ?></p>
            </div>
            <?php endif; ?>
            
            <div class="btn-actions mt-4">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <a href="index.php" class="btn-home">
                    <i class="fas fa-home"></i> Volver al inicio
                </a>
            </div>
            
            <div class="mt-4 pt-3 border-top">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Para cualquier consulta, contáctanos al <?= SITE_PHONE ?> o escribe a <?= SITE_EMAIL ?>
                </small>
            </div>
        </div>
    </div>
</body>
</html>