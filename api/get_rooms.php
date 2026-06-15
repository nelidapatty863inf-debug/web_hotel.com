<?php
/**
 * API: OBTENER LISTA DE HABITACIONES
 * Método: GET
 * Parámetros: id (opcional), category_id (opcional), status (opcional)
 * Retorna: JSON con habitaciones
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

$response = ['success' => false, 'data' => [], 'total' => 0];

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$status = $_GET['status'] ?? 'active';

// Construir consulta
$sql = "SELECT r.*, rc.name as category_name, rc.sort_order 
        FROM rooms r 
        LEFT JOIN room_categories rc ON r.category_id = rc.id 
        WHERE 1=1";
$params = [];
$types = "";

if ($id > 0) {
    $sql .= " AND r.id = ?";
    $params[] = $id;
    $types .= "i";
}

if ($category_id > 0) {
    $sql .= " AND r.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($status) {
    $sql .= " AND r.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY rc.sort_order, r.price_per_night";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = [
        'id' => $row['id'],
        'room_number' => $row['room_number'],
        'name' => $row['name'],
        'category_id' => $row['category_id'],
        'category_name' => $row['category_name'],
        'description' => $row['description'],
        'amenities' => explode(',', $row['amenities'] ?? ''),
        'capacity' => $row['capacity'],
        'bed_type' => $row['bed_type'],
        'size_sqm' => $row['size_sqm'],
        'price_per_night' => floatval($row['price_per_night']),
        'weekend_price' => $row['weekend_price'] ? floatval($row['weekend_price']) : null,
        'total_units' => $row['total_units'],
        'status' => $row['status']
    ];
}

$response['success'] = true;
$response['data'] = $rooms;
$response['total'] = count($rooms);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>