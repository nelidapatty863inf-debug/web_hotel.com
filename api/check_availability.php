<?php
/**
 * API: VERIFICAR DISPONIBILIDAD DE HABITACIONES
 * Método: GET/POST
 * Parámetros: check_in, check_out, adults, room_id (opcional)
 * Retorna: JSON con disponibilidad
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => '', 'data' => []];

// Obtener parámetros
$check_in = $_GET['check_in'] ?? $_POST['check_in'] ?? date('Y-m-d', strtotime('+1 day'));
$check_out = $_GET['check_out'] ?? $_POST['check_out'] ?? date('Y-m-d', strtotime('+3 days'));
$adults = intval($_GET['adults'] ?? $_POST['adults'] ?? 1);
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : (isset($_POST['room_id']) ? intval($_POST['room_id']) : 0);

// Validar fechas
$dateValidation = validateDates($check_in, $check_out);
if (!$dateValidation['valid']) {
    $response['message'] = $dateValidation['message'];
    echo json_encode($response);
    exit;
}

$nights = $dateValidation['nights'];

// Si se especifica una habitación específica
if ($room_id > 0) {
    $sql = "SELECT r.*, rc.name as category_name 
            FROM rooms r 
            LEFT JOIN room_categories rc ON r.category_id = rc.id 
            WHERE r.id = ? AND r.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        $response['message'] = 'Habitación no encontrada';
        echo json_encode($response);
        exit;
    }
    
    // Verificar disponibilidad para cada fecha
    $available = true;
    $unavailable_dates = [];
    $current = strtotime($check_in);
    $end = strtotime($check_out);
    
    while ($current < $end) {
        $date = date('Y-m-d', $current);
        $available_units = getRoomAvailability($conn, $room_id, $date);
        
        if ($available_units <= 0) {
            $available = false;
            $unavailable_dates[] = $date;
        }
        
        $current = strtotime('+1 day', $current);
    }
    
    $response['success'] = true;
    $response['data'] = [
        'room' => $room,
        'available' => $available,
        'nights' => $nights,
        'total_price' => $room['price_per_night'] * $nights,
        'unavailable_dates' => $unavailable_dates
    ];
    
} else {
    // Obtener todas las habitaciones disponibles
    $rooms = getAvailableRooms($conn, $check_in, $check_out, $adults);
    $available_rooms = [];
    
    while ($room = $rooms->fetch_assoc()) {
        // Calcular precio dinámico
        $dynamic_price = null;
        $current = strtotime($check_in);
        $end = strtotime($check_out);
        $total_price = 0;
        $price_breakdown = [];
        
        while ($current < $end) {
            $date = date('Y-m-d', $current);
            $day_price = $room['price_per_night'];
            
            // Verificar precio dinámico por temporada
            $dynamic = getDynamicPrice($conn, $room['id'], $date);
            if ($dynamic) {
                $day_price = $dynamic;
            }
            
            $total_price += $day_price;
            $price_breakdown[] = ['date' => $date, 'price' => $day_price];
            $current = strtotime('+1 day', $current);
        }
        
        $available_rooms[] = [
            'id' => $room['id'],
            'name' => $room['name'],
            'room_number' => $room['room_number'],
            'category' => $room['category_name'],
            'description' => $room['description'],
            'amenities' => explode(',', $room['amenities'] ?? ''),
            'capacity' => $room['capacity'],
            'bed_type' => $room['bed_type'],
            'size_sqm' => $room['size_sqm'],
            'price_per_night' => $room['price_per_night'],
            'total_price' => $total_price,
            'nights' => $nights,
            'price_breakdown' => $price_breakdown,
            'total_units' => $room['total_units']
        ];
    }
    
    $response['success'] = true;
    $response['data'] = [
        'check_in' => $check_in,
        'check_out' => $check_out,
        'nights' => $nights,
        'adults' => $adults,
        'available_rooms' => $available_rooms,
        'total_available' => count($available_rooms)
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>