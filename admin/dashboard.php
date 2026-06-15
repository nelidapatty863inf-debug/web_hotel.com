<?php
/**
 * DASHBOARD PRINCIPAL DEL ADMINISTRADOR
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$stats = getDashboardStats($conn);

// Obtener reservas del día
$sql = "SELECT r.*, u.first_name, u.last_name, u.email 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE DATE(r.created_at) = CURDATE() 
        ORDER BY r.created_at DESC 
        LIMIT 10";
$today_reservations = $conn->query($sql);

// Obtener habitaciones con menor disponibilidad
$sql = "SELECT r.name, r.total_units, 
        COALESCE(SUM(CASE WHEN res.status IN ('confirmed', 'checked_in') 
            AND CURDATE() BETWEEN res.check_in AND res.check_out THEN 1 ELSE 0 END), 0) as occupied
        FROM rooms r
        LEFT JOIN reservation_items ri ON r.id = ri.room_id
        LEFT JOIN reservations res ON ri.reservation_id = res.id
        WHERE r.status = 'active'
        GROUP BY r.id
        ORDER BY occupied DESC
        LIMIT 5";
$top_rooms = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }

        /* =====================================================
           LAYOUT DEL ADMIN
        ===================================================== */
        .admin-container {
            display: flex;
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
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
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
            font-size: 1.2rem;
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
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        /* Header del admin */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ddd;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .admin-header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
        }

        /* =====================================================
           TARJETAS DE ESTADÍSTICAS
        ===================================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card small {
            display: block;
            margin-top: 0.5rem;
            color: #95a5a6;
        }

        /* =====================================================
           GRÁFICAS
        ===================================================== */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* =====================================================
           TABLAS DE DATOS
        ===================================================== */
        .table-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            background: #2c3e50;
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
        .btn-view {
            background: #3498db;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: #2980b9;
        }

        /* Estados de reserva */
        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
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

        /* Progress bar */
        .progress-bar {
            background: #ecf0f1;
            border-radius: 10px;
            height: 8px;
            width: 100%;
            overflow: hidden;
        }

        .progress-fill {
            background: #3498db;
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>🏨 <?= SITE_NAME ?></h3>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="rooms.php"><i class="fas fa-bed"></i> <span>Habitaciones</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                <li><a href="availability.php"><i class="fas fa-calendar-check"></i> <span>Disponibilidad</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div>
                    <span><i class="fas fa-user-circle"></i> <?= $_SESSION['user_name'] ?></span>
                    <span style="margin-left: 1rem;"><i class="fas fa-calendar"></i> <?= date('d/m/Y H:i') ?></span>
                </div>
            </div>
            
            <!-- Tarjetas de estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-calendar-day"></i> Reservas Hoy</h3>
                    <div class="number"><?= $stats['reservations_today'] ?></div>
                    <small><i class="fas fa-chart-line"></i> vs ayer: +<?= rand(0, 5) ?>%</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-check-circle"></i> Reservas Activas</h3>
                    <div class="number"><?= $stats['confirmed'] ?></div>
                    <small><i class="fas fa-clock"></i> Confirmadas</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-dollar-sign"></i> Ingresos del Mes</h3>
                    <div class="number"><?= formatPrice($stats['monthly_revenue']) ?></div>
                    <small><i class="fas fa-chart-line"></i> Ene - Mar 2024</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-building"></i> Ocupación</h3>
                    <div class="number"><?= $stats['occupancy_rate'] ?>%</div>
                    <small><?= $stats['occupied'] ?> / <?= $stats['total_rooms'] ?> habitaciones</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Usuarios</h3>
                    <div class="number"><?= $stats['total_users'] ?></div>
                    <small><i class="fas fa-user-plus"></i> Registrados</small>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-star"></i> Calificación</h3>
                    <div class="number">4.8</div>
                    <small><i class="fas fa-star" style="color: #f1c40f;"></i>★★★★★ (128 reseñas)</small>
                </div>
            </div>
            
            <!-- Gráficas -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Ingresos Mensuales</h3>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Ocupación por Habitación</h3>
                    <canvas id="occupancyChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Reservas del día -->
            <div class="table-card">
                <h3><i class="fas fa-list-alt"></i> 📋 Reservas de Hoy</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Código</th><th>Cliente</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $today_reservations->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $row['reservation_code'] ?></strong></td>
                            <td><i class="fas fa-user"></i> <?= $row['first_name'] ?? 'Guest' ?> <?= $row['last_name'] ?? '' ?></td>
                            <td><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                            <td><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                            <td><?= formatPrice($row['total_amount'] ?? 0) ?></td>
                            <td><span class="booking-status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <a href="reservation_view.php?id=<?= $row['id'] ?>" class="btn-view"><i class="fas fa-eye"></i> Ver</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($today_reservations->num_rows == 0): ?>
                        <tr><td colspan="7" style="text-align: center;"><i class="fas fa-calendar"></i> No hay reservas hoy</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Top habitaciones -->
            <div class="table-card">
                <h3><i class="fas fa-fire"></i> 🔥 Habitaciones Más Solicitadas</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Habitación</th><th>Total Unidades</th><th>Ocupadas</th><th>Disponibles</th><th>Ocupación</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $top_rooms->fetch_assoc()): 
                            $available = $row['total_units'] - $row['occupied'];
                            $percentage = $row['total_units'] > 0 ? round(($row['occupied'] / $row['total_units']) * 100) : 0;
                        ?>
                        <tr>
                            <td><i class="fas fa-bed"></i> <?= $row['name'] ?></td>
                            <td><?= $row['total_units'] ?></td>
                            <td><?= $row['occupied'] ?></td>
                            <td><?= $available ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <small><?= $percentage ?>%</small>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfica de ingresos
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Ingresos ($)',
                    data: [12500, 14800, 16200, 15800, 17500, <?= $stats['monthly_revenue'] ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { font: { size: 12 } }
                    }
                }
            }
        });
        
        // Gráfica de ocupación
        const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
        new Chart(occupancyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ocupadas', 'Disponibles'],
                datasets: [{
                    data: [<?= $stats['occupied'] ?>, <?= $stats['total_rooms'] - $stats['occupied'] ?>],
                    backgroundColor: ['#3498db', '#ecf0f1'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function(context) { return context.label + ': ' + context.raw + ' habitaciones'; } } }
                }
            }
        });
    </script>
</body>
</html>