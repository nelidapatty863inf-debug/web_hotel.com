<?php
/**
 * GESTIÓN DE HABITACIONES - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// COMENTA O ELIMINA ESTAS LÍNEAS:
// define('CURRENCY_SYMBOL', 'Bs. ');
// define('CURRENCY_CODE', 'BOB');

// Obtener todas las habitaciones
$sql = "SELECT r.*, rc.name as category_name, rc.sort_order 
        FROM rooms r 
        LEFT JOIN room_categories rc ON r.category_id = rc.id 
        ORDER BY rc.sort_order, r.id";
$rooms = $conn->query($sql);

// Obtener categorías para el filtro
$categories = $conn->query("SELECT * FROM room_categories ORDER BY sort_order");

// Estadísticas de habitaciones
$total_rooms = 0;
$active_rooms = 0;
$maintenance_rooms = 0;
$inactive_rooms = 0;

$rooms_stats = $conn->query("SELECT status, COUNT(*) as count FROM rooms GROUP BY status");
while($stat = $rooms_stats->fetch_assoc()) {
    if($stat['status'] == 'active') $active_rooms = $stat['count'];
    if($stat['status'] == 'maintenance') $maintenance_rooms = $stat['count'];
    if($stat['status'] == 'inactive') $inactive_rooms = $stat['count'];
    $total_rooms += $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitaciones - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 16px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }

        .stat-card .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Filtros */
        .filters-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* Tabla */
        .table-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
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
            white-space: nowrap;
        }

        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Botones */
        .btn-add {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
            color: white;
        }

        .btn-edit {
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: #2980b9;
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            background: #c0392b;
            color: white;
        }

        /* Estados */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-active { background: #2ecc71; color: white; }
        .status-maintenance { background: #f39c12; color: white; }
        .status-inactive { background: #95a5a6; color: white; }

        /* Precio */
        .price {
            font-weight: 600;
            color: #2c3e50;
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
                grid-template-columns: repeat(2, 1fr);
            }
            
            .data-table {
                font-size: 0.75rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                <li><a href="rooms.php" class="active"><i class="fas fa-bed"></i> <span>Habitaciones</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> <span>Reservas</span></a></li>
                <li><a href="availability.php"><i class="fas fa-calendar-check"></i> <span>Disponibilidad</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>
                <li><a href="../index.php"><i class="fas fa-globe"></i> <span>Ver sitio</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="admin-content w-100">
            <div class="admin-header">
                <h1><i class="fas fa-bed"></i> Gestión de Habitaciones</h1>
                <a href="rooms_edit.php" class="btn-add">
                    <i class="fas fa-plus-circle"></i> Nueva Habitación
                </a>
            </div>
            
            <!-- Tarjetas de estadísticas -->
            <div class="stats-grid">
                <div class="stat-card" onclick="filterByStatus('all')">
                    <h3><i class="fas fa-building"></i> Total Habitaciones</h3>
                    <div class="number"><?= $total_rooms ?></div>
                    <small>Todas</small>
                </div>
                <div class="stat-card" onclick="filterByStatus('active')">
                    <h3><i class="fas fa-check-circle"></i> Activas</h3>
                    <div class="number text-success"><?= $active_rooms ?></div>
                    <small>Disponibles</small>
                </div>
                <div class="stat-card" onclick="filterByStatus('maintenance')">
                    <h3><i class="fas fa-tools"></i> Mantenimiento</h3>
                    <div class="number text-warning"><?= $maintenance_rooms ?></div>
                    <small>En reparación</small>
                </div>
                <div class="stat-card" onclick="filterByStatus('inactive')">
                    <h3><i class="fas fa-ban"></i> Inactivas</h3>
                    <div class="number text-danger"><?= $inactive_rooms ?></div>
                    <small>Fuera de servicio</small>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters-card">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search"></i> Buscar</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Nombre, número...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-layer-group"></i> Categoría</label>
                        <select id="categoryFilter" class="form-select">
                            <option value="">Todas las categorías</option>
                            <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-toggle-on"></i> Estado</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="active">🟢 Activo</option>
                            <option value="maintenance">🟡 Mantenimiento</option>
                            <option value="inactive">🔴 Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button onclick="filterRooms()" class="btn-add w-100" style="margin: 0;">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de habitaciones -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table" id="roomsTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-image"></i> Imagen</th>
                                <th><i class="fas fa-tag"></i> Nombre</th>
                                <th><i class="fas fa-hashtag"></i> Número</th>
                                <th><i class="fas fa-layer-group"></i> Categoría</th>
                                <th><i class="fas fa-dollar-sign"></i> Precio/noche</th>
                                <th><i class="fas fa-users"></i> Capacidad</th>
                                <th><i class="fas fa-cubes"></i> Unidades</th>
                                <th><i class="fas fa-toggle-on"></i> Estado</th>
                                <th><i class="fas fa-cogs"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($room = $rooms->fetch_assoc()): ?>
                            <tr data-category="<?= $room['category_id'] ?>" data-status="<?= $room['status'] ?>">
                                <td><?= $room['id'] ?></td>
                                <td>
                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 40px;">
                                        <i class="fas fa-bed fa-2x text-primary"></i>
                                    </div>
                                </td>
                                <td><strong><?= htmlspecialchars($room['name']) ?></strong></td>
                                <td><?= $room['room_number'] ?? '-' ?></td>
                                <td><span class="badge bg-info"><?= $room['category_name'] ?></span></td>
                                <td class="price"><?= formatPrice($room['price_per_night']) ?></td>
                                <td><i class="fas fa-user"></i> <?= $room['capacity'] ?> pers</td>
                                <td><span class="badge bg-secondary"><?= $room['total_units'] ?> unidades</span></td>
                                <td>
                                    <span class="status-badge status-<?= $room['status'] ?>">
                                        <?= $room['status'] == 'active' ? '🟢 Activo' : ($room['status'] == 'maintenance' ? '🟡 Mantenimiento' : '🔴 Inactivo') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="rooms_edit.php?id=<?= $room['id'] ?>" class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="rooms_delete.php?id=<?= $room['id'] ?>" class="btn-delete" title="Eliminar" onclick="return confirm('¿Eliminar esta habitación? Esta acción no se puede deshacer.')">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            window.roomsTable = $('#roomsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[0, 'asc']]
            });
        });
        
        // Filtrar por estado desde tarjetas
        function filterByStatus(status) {
            if (status === 'all') {
                document.getElementById('statusFilter').value = '';
            } else {
                document.getElementById('statusFilter').value = status;
            }
            filterRooms();
        }
        
        // Filtrar habitaciones
        function filterRooms() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            // Actualizar DataTable
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const row = roomsTable.row(dataIndex).node();
                const rowCategory = row.getAttribute('data-category');
                const rowStatus = row.getAttribute('data-status');
                const rowName = data[2].toLowerCase();
                const rowNumber = data[3].toLowerCase();
                
                let show = true;
                if (search && !rowName.includes(search) && !rowNumber.includes(search)) show = false;
                if (category && rowCategory != category) show = false;
                if (status && rowStatus != status) show = false;
                
                return show;
            });
            
            roomsTable.draw();
            $.fn.dataTable.ext.search.pop();
        }
        
        // Event listeners
        document.getElementById('searchInput').addEventListener('keyup', filterRooms);
        document.getElementById('categoryFilter').addEventListener('change', filterRooms);
        document.getElementById('statusFilter').addEventListener('change', filterRooms);
    </script>
</body>
</html>