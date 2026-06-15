<?php
/**
 * GESTIÓN DE RESERVAS - VERSIÓN CORREGIDA
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Filtros
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$search = $_GET['search'] ?? '';

// Construir consulta
$sql = "SELECT r.*, u.first_name, u.last_name, u.email,
        GROUP_CONCAT(ri.room_name SEPARATOR ', ') as rooms,
        COUNT(ri.id) as total_rooms
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN reservation_items ri ON r.id = ri.reservation_id
        WHERE 1=1";
$params = [];
$types = "";

if ($status_filter) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_from && $date_to) {
    $sql .= " AND r.check_in >= ? AND r.check_out <= ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

if ($search) {
    $sql .= " AND (r.reservation_code LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$sql .= " GROUP BY r.id ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reservations = $stmt->get_result();

// Obtener estados para el resumen
$status_counts = [];
$sql_status = "SELECT status, COUNT(*) as count FROM reservations GROUP BY status";
$result_status = $conn->query($sql_status);
while($row = $result_status->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .stat-card .number { font-size: 1.6rem; font-weight: bold; color: #2c3e50; }
        .filters-card, .table-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead { background: #2c3e50; color: white; }
        .data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .status-pending { background: #f39c12; color: white; }
        .status-confirmed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        .status-checked_in { background: #3498db; color: white; }
        .status-checked_out { background: #95a5a6; color: white; }
        .btn-view, .btn-edit { padding: 5px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view { background: #3498db; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 70px; }
            .admin-sidebar h3, .admin-sidebar li a span { display: none; }
            .admin-sidebar li a { justify-content: center; padding: 1rem; }
            .admin-content { margin-left: 70px; padding: 1rem; }
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
                <li><a href="reservations.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                <li><a href="availability.php"><i class="fas fa-calendar-check"></i> <span>Disponibilidad</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <div class="admin-content w-100">
            <div class="stats-grid">
                <div class="stat-card" onclick="filterByStatus('')"><h3>Total Reservas</h3><div class="number"><?= array_sum($status_counts) ?></div></div>
                <div class="stat-card" onclick="filterByStatus('pending')"><h3>Pendientes</h3><div class="number text-warning"><?= $status_counts['pending'] ?? 0 ?></div></div>
                <div class="stat-card" onclick="filterByStatus('confirmed')"><h3>Confirmadas</h3><div class="number text-success"><?= $status_counts['confirmed'] ?? 0 ?></div></div>
                <div class="stat-card" onclick="filterByStatus('cancelled')"><h3>Canceladas</h3><div class="number text-danger"><?= $status_counts['cancelled'] ?? 0 ?></div></div>
            </div>
            
            <div class="filters-card">
                <form method="GET" id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select" id="statusSelect">
                            <option value="">Todos</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmada</option>
                            <option value="checked_in" <?= $status_filter == 'checked_in' ? 'selected' : '' ?>>Check-in</option>
                            <option value="checked_out" <?= $status_filter == 'checked_out' ? 'selected' : '' ?>>Check-out</option>
                            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha desde</label>
                        <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha hasta</label>
                        <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Código, cliente...">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                        <a href="reservations.php" class="btn btn-secondary"><i class="fas fa-eraser"></i> Limpiar</a>
                    </div>
                </form>
            </div>
            
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table" id="reservationsTable">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Habitaciones</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $reservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['reservation_code'] ?></td>
                                <td><?= htmlspecialchars($row['first_name'] ?? 'Guest') ?> <?= htmlspecialchars($row['last_name'] ?? '') ?></td>
                                <td><?= $row['email'] ?? '-' ?></td>
                                <td><?= date('d/m/Y', strtotime($row['check_in'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['check_out'])) ?></td>
                                <td><?= $row['total_rooms'] ?> hab(s) - <?= htmlspecialchars(substr($row['rooms'] ?? '', 0, 20)) ?>...</td>
                                <td><?= formatPrice($row['total_amount'] ?? 0) ?></td>
                                <td><span class="status-badge status-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="reservation_view.php?id=<?= $row['id'] ?>" class="btn-view">Ver</a>
                                    <a href="reservation_update.php?id=<?= $row['id'] ?>" class="btn-edit">Editar</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#reservationsTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                pageLength: 10,
                responsive: true
            });
        });
        
        function filterByStatus(status) {
            document.getElementById('statusSelect').value = status;
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>