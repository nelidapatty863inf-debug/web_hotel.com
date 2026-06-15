<?php
/**
 * CALENDARIO DE DISPONIBILIDAD - VERSIÓN CORREGIDA
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// NOTA: Las constantes CURRENCY_SYMBOL ya están definidas en config.php

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$msg = $_GET['msg'] ?? '';
$msg_type = $_GET['msg_type'] ?? '';

$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$month_name = date('F Y', $first_day);

// Obtener habitaciones
$rooms = $conn->query("SELECT id, name, total_units, price_per_night FROM rooms WHERE status = 'active' ORDER BY id");

// Generar matriz de disponibilidad
$availability = [];
while($room = $rooms->fetch_assoc()) {
    $room_id = $room['id'];
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $available = getRoomAvailability($conn, $room_id, $date);
        $availability[$room_id][$day] = $available;
    }
}
$rooms->data_seek(0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .admin-sidebar h3 { padding: 1.5rem; text-align: center; border-bottom: 1px solid #3d566e; }
        .admin-sidebar ul { list-style: none; margin-top: 1rem; padding: 0; }
        .admin-sidebar li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.85rem 1.5rem;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .admin-sidebar li a:hover, .admin-sidebar li a.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #e74c3c;
        }
        .admin-content { margin-left: 280px; padding: 2rem; }
        .calendar-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .month-title { font-size: 1.5rem; font-weight: 600; color: #2c3e50; }
        .legend {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
        }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
        .legend-color { width: 20px; height: 20px; border-radius: 4px; }
        .availability-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .availability-table th {
            background: #2c3e50;
            color: white;
            padding: 0.75rem;
            text-align: center;
            border: 1px solid #3d566e;
            position: sticky;
            top: 0;
        }
        .availability-table td {
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ecf0f1;
            cursor: pointer;
            transition: 0.2s;
        }
        .availability-table td:hover { opacity: 0.8; }
        .room-name-cell {
            background: #f8f9fa;
            font-weight: 600;
            text-align: left !important;
            position: sticky;
            left: 0;
            z-index: 1;
        }
        .available-cell { background: #d5f5e3; color: #27ae60; }
        .unavailable-cell { background: #fadbd8; color: #e74c3c; }
        .btn-save {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(46,204,113,0.3); }
        @media (max-width: 768px) {
            .admin-sidebar { width: 70px; }
            .admin-sidebar h3, .admin-sidebar li a span { display: none; }
            .admin-sidebar li a { justify-content: center; padding: 1rem; }
            .admin-content { margin-left: 70px; padding: 1rem; }
            .calendar-nav { flex-direction: column; text-align: center; }
            .availability-table { font-size: 0.7rem; }
        }
    </style>
</head>
<body>
    <div class="admin-container d-flex">
        <div class="admin-sidebar">
            <h3>🏨 <?= SITE_NAME ?></h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="rooms.php"><i class="fas fa-bed"></i> <span>Habitaciones</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                <li><a href="availability.php" class="active"><i class="fas fa-calendar-check"></i> <span>Disponibilidad</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <div class="admin-content w-100">
            <div class="admin-header">
                <h1 class="text-white"><i class="fas fa-calendar-check"></i> Calendario de Disponibilidad</h1>
            </div>
            
            <?php if($msg): ?>
                <div class="alert alert-<?= $msg_type == 'success' ? 'success' : ($msg_type == 'warning' ? 'warning' : 'info') ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $msg_type == 'success' ? 'check-circle' : ($msg_type == 'warning' ? 'exclamation-triangle' : 'info-circle') ?>"></i> <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="legend">
                <div class="legend-item"><div class="legend-color" style="background: #d5f5e3;"></div><span><i class="fas fa-check-circle text-success"></i> Disponible</span></div>
                <div class="legend-item"><div class="legend-color" style="background: #fadbd8;"></div><span><i class="fas fa-times-circle text-danger"></i> No disponible / Ocupado</span></div>
                <div class="legend-item"><div class="legend-color" style="background: #fdebd0;"></div><span><i class="fas fa-edit text-warning"></i> Clic para editar</span></div>
            </div>
            
            <div class="calendar-nav">
                <a href="?year=<?= date('Y', strtotime("$year-$month-01 -1 month")) ?>&month=<?= date('m', strtotime("$year-$month-01 -1 month")) ?>" class="btn btn-outline-light"><i class="fas fa-chevron-left"></i> Mes Anterior</a>
                <h2 class="month-title"><i class="fas fa-calendar-alt"></i> <?= $month_name ?></h2>
                <a href="?year=<?= date('Y', strtotime("$year-$month-01 +1 month")) ?>&month=<?= date('m', strtotime("$year-$month-01 +1 month")) ?>" class="btn btn-outline-light">Mes Siguiente <i class="fas fa-chevron-right"></i></a>
            </div>
            
            <div class="calendar-container">
                <form id="availabilityForm" method="POST" action="availability_update.php">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <table class="availability-table">
                        <thead>
                            <tr>
                                <th style="min-width: 180px;">Habitación</th>
                                <th style="min-width: 100px;">Precio</th>
                                <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                                    <th><?= $day ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($room = $rooms->fetch_assoc()): ?>
                            <tr>
                                <td class="room-name-cell">
                                    <strong><i class="fas fa-bed"></i> <?= htmlspecialchars($room['name']) ?></strong>
                                    <br><small class="text-muted">Total: <?= $room['total_units'] ?> unidades</small>
                                </td>
                                <td class="room-name-cell" style="background: #e8f8f5;">
                                    <span class="text-success fw-bold"><?= formatPrice($room['price_per_night']) ?></span>
                                </td>
                                <?php for($day = 1; $day <= $days_in_month; $day++): 
                                    $available = $availability[$room['id']][$day] ?? $room['total_units'];
                                    $cellClass = $available > 0 ? 'available-cell' : 'unavailable-cell';
                                ?>
                                <td class="<?= $cellClass ?>" 
                                    data-room="<?= $room['id'] ?>" 
                                    data-day="<?= $day ?>"
                                    data-current="<?= $available ?>"
                                    onclick="editCell(this)">
                                    <?= $available ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-save btn-lg">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let editingCell = null;
        
        function editCell(cell) {
            if (editingCell && editingCell !== cell) editingCell.classList.remove('editing-cell');
            editingCell = cell;
            cell.classList.add('editing-cell');
            
            const currentValue = cell.dataset.current;
            const input = document.createElement('input');
            input.type = 'number';
            input.value = currentValue;
            input.min = 0;
            input.max = 100;
            input.style.width = '60px';
            input.style.padding = '0.25rem';
            input.style.textAlign = 'center';
            input.style.borderRadius = '5px';
            input.style.border = '1px solid #e74c3c';
            
            input.addEventListener('blur', function() {
                const newValue = parseInt(this.value);
                if (!isNaN(newValue) && newValue >= 0) {
                    cell.textContent = newValue;
                    cell.dataset.current = newValue;
                    if (newValue > 0) {
                        cell.classList.remove('unavailable-cell');
                        cell.classList.add('available-cell');
                    } else {
                        cell.classList.remove('available-cell');
                        cell.classList.add('unavailable-cell');
                    }
                    const form = document.getElementById('availabilityForm');
                    let hiddenInput = form.querySelector(`input[name="availability[${cell.dataset.room}][${cell.dataset.day}]"]`);
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = `availability[${cell.dataset.room}][${cell.dataset.day}]`;
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = newValue;
                }
                cell.classList.remove('editing-cell');
                editingCell = null;
            });
            
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') this.blur();
            });
            
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
        }
    </script>
</body>
</html>