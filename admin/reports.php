<?php
/**
 * REPORTES Y ESTADÍSTICAS - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Configurar moneda en Bolivianos
//define('CURRENCY_SYMBOL', 'Bs. ');
//define('CURRENCY_CODE', 'BOB');

// Estadísticas generales
$sql = "SELECT 
            COUNT(*) as total_reservations,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_booking,
            COUNT(DISTINCT user_id) as unique_customers
        FROM reservations 
        WHERE status IN ('confirmed', 'checked_in', 'checked_out')";
$stats = $conn->query($sql)->fetch_assoc();

// Reservas por mes
$sql = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(total_amount) as revenue
        FROM reservations 
        WHERE status IN ('confirmed', 'checked_in', 'checked_out')
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12";
$monthly_stats = $conn->query($sql);

// Habitaciones más populares
$sql = "SELECT 
            ri.room_name,
            COUNT(*) as bookings,
            SUM(ri.subtotal) as revenue
        FROM reservation_items ri
        GROUP BY ri.room_name
        ORDER BY bookings DESC
        LIMIT 10";
$popular_rooms = $conn->query($sql);

// Métodos de pago
$sql = "SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(total_amount) as total
        FROM reservations 
        WHERE payment_method IS NOT NULL AND status IN ('confirmed', 'checked_in', 'checked_out')
        GROUP BY payment_method";
$payment_methods = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        /* Tarjetas de estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-card .number {
            font-size: 2.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Gráficas */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .chart-card h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tablas */
        .table-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .table-card h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
        }

        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Botones */
        .btn-print {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }

        /* Progress bar */
        .progress-custom {
            background: #ecf0f1;
            border-radius: 10px;
            height: 8px;
            width: 100%;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #3498db, #2ecc71);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s;
        }

        /* Badges */
        .badge-payment {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 0.8rem;
                overflow-x: auto;
                display: block;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card, .chart-card, .table-card {
            animation: fadeIn 0.5s ease forwards;
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
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="admin-content w-100">
            <div class="admin-header">
                <h1><i class="fas fa-chart-line"></i> Reportes y Estadísticas</h1>
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Imprimir Reporte
                </button>
            </div>
            
            <!-- Tarjetas resumen -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-calendar-check"></i> Total Reservas</h3>
                    <div class="number"><?= number_format($stats['total_reservations'] ?? 0) ?></div>
                    <small class="text-muted"><i class="fas fa-chart-line"></i> Histórico</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-dollar-sign"></i> Ingresos Totales</h3>
                    <div class="number"><?= formatPrice($stats['total_revenue'] ?? 0) ?></div>
                    <small class="text-muted">en <?= CURRENCY_CODE ?></small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-receipt"></i> Ticket Promedio</h3>
                    <div class="number"><?= formatPrice($stats['avg_booking'] ?? 0) ?></div>
                    <small class="text-muted">por reserva</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Clientes Únicos</h3>
                    <div class="number"><?= number_format($stats['unique_customers'] ?? 0) ?></div>
                    <small class="text-muted"><i class="fas fa-user-plus"></i> Registrados</small>
                </div>
            </div>
            
            <!-- Gráficas -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Reservas por Mes</h3>
                    <canvas id="monthlyChart" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Métodos de Pago</h3>
                    <canvas id="paymentChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Habitaciones populares -->
            <div class="table-card">
                <h3><i class="fas fa-trophy"></i> 🏆 Habitaciones Más Populares</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Habitación</th><th>Reservas</th><th>Ingresos</th><th>% del total</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_bookings = 0;
                        $popular_rooms->data_seek(0);
                        while($row = $popular_rooms->fetch_assoc()) {
                            $total_bookings += $row['bookings'];
                        }
                        $popular_rooms->data_seek(0);
                        ?>
                        <?php while($row = $popular_rooms->fetch_assoc()): 
                            $percentage = $total_bookings > 0 ? round(($row['bookings'] / $total_bookings) * 100) : 0;
                        ?>
                        <tr>
                            <td><i class="fas fa-bed text-primary"></i> <?= htmlspecialchars($row['room_name']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= $row['bookings'] ?></span></td>
                            <td><span class="badge bg-success"><?= formatPrice($row['revenue']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress-custom">
                                        <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <span class="fw-bold"><?= $percentage ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tabla mensual -->
            <div class="table-card">
                <h3><i class="fas fa-calendar-alt"></i> 📊 Resumen Mensual</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Mes</th><th>Reservas</th><th>Ingresos</th><th>Promedio</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $monthly_stats->fetch_assoc()): ?>
                        <tr>
                            <td><i class="fas fa-calendar"></i> <?= date('F Y', strtotime($row['month'] . '-01')) ?></td>
                            <td><span class="badge bg-primary"><?= number_format($row['count']) ?></span></td>
                            <td><span class="badge bg-success"><?= formatPrice($row['revenue']) ?></span></td>
                            <td><span class="badge bg-warning text-dark"><?= formatPrice($row['revenue'] / $row['count']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($monthly_stats->num_rows == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay datos disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfica mensual
        const monthlyData = <?php
            $months = [];
            $counts = [];
            $revenues = [];
            $monthly_stats->data_seek(0);
            while($row = $monthly_stats->fetch_assoc()) {
                $months[] = date('M Y', strtotime($row['month'] . '-01'));
                $counts[] = $row['count'];
                $revenues[] = $row['revenue'];
            }
            echo json_encode(['months' => array_reverse($months), 'counts' => array_reverse($counts), 'revenues' => array_reverse($revenues)]);
        ?>;
        
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.months,
                datasets: [
                    {
                        label: 'Reservas',
                        data: monthlyData.counts,
                        backgroundColor: '#3498db',
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ingresos (<?= CURRENCY_SYMBOL ?>)',
                        data: monthlyData.revenues,
                        backgroundColor: '#2ecc71',
                        borderRadius: 8,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw;
                                if (context.dataset.label.includes('Ingresos')) {
                                    return label + ': <?= CURRENCY_SYMBOL ?> ' + value.toLocaleString('es-BO');
                                }
                                return label + ': ' + value;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Número de Reservas' }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: { display: true, text: 'Ingresos (<?= CURRENCY_SYMBOL ?>)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
        
        // Gráfica de métodos de pago
        const paymentData = <?php
            $methods = [];
            $counts = [];
            $payment_methods->data_seek(0);
            while($row = $payment_methods->fetch_assoc()) {
                $method_name = $row['payment_method'] == 'credit_card' ? '💳 Tarjeta' : 
                              ($row['payment_method'] == 'paypal' ? '💰 PayPal' : 
                              ($row['payment_method'] == 'transfer' ? '🏦 Transferencia' : '📱 Otro'));
                $methods[] = $method_name;
                $counts[] = $row['count'];
            }
            echo json_encode(['methods' => $methods, 'counts' => $counts]);
        ?>;
        
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: paymentData.methods,
                datasets: [{
                    data: paymentData.counts,
                    backgroundColor: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' reservas';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>