<?php
/**
 * API: AGREGAR HABITACIÓN AL CARRITO - VERSIÓN SILENCIOSA
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = [
    'success' => false, 
    'message' => '', 
    'cart_count' => 0,
    'redirect' => ''
];

// Obtener parámetros
$room_id = intval($_POST['room_id'] ?? $_GET['room_id'] ?? 0);
$check_in = $_POST['check_in'] ?? $_GET['check_in'] ?? date('Y-m-d', strtotime('+1 day'));
$check_out = $_POST['check_out'] ?? $_GET['check_out'] ?? date('Y-m-d', strtotime('+3 days'));
$adults = intval($_POST['adults'] ?? $_GET['adults'] ?? 2);
$children = intval($_POST['children'] ?? $_GET['children'] ?? 0);

// Validar room_id
if ($room_id <= 0) {
    $response['redirect'] = 'index.php';
    echo json_encode($response);
    exit;
}

// Validar fechas
$dateValidation = validateDates($check_in, $check_out);
if (!$dateValidation['valid']) {
    $response['redirect'] = 'room_details.php?id=' . $room_id;
    echo json_encode($response);
    exit;
}

$nights = $dateValidation['nights'];

// Obtener información de la habitación
$sql = "SELECT * FROM rooms WHERE id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    $response['redirect'] = 'index.php';
    echo json_encode($response);
    exit;
}

// Verificar disponibilidad
$current = strtotime($check_in);
$end = strtotime($check_out);
$available = true;

while ($current < $end) {
    $date = date('Y-m-d', $current);
    $available_units = getRoomAvailability($conn, $room_id, $date);
    
    if ($available_units <= 0) {
        $available = false;
        break;
    }
    $current = strtotime('+1 day', $current);
}

if (!$available) {
    $response['redirect'] = 'room_details.php?id=' . $room_id . '&error=no_disponible';
    echo json_encode($response);
    exit;
}

// Obtener o crear reserva pendiente
$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

$reservation_id = getOrCreatePendingReservation($conn, $session_id, $user_id);

// Actualizar fechas
$sql = "UPDATE reservations SET 
        check_in = ?, check_out = ?, adults = ?, children = ? 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssiii", $check_in, $check_out, $adults, $children, $reservation_id);
$stmt->execute();

// Verificar si ya está en el carrito
$sql = "SELECT id FROM reservation_items WHERE reservation_id = ? AND room_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reservation_id, $room_id);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    // Ya está en el carrito - redirigir directamente
    $response['success'] = true;
    $response['redirect'] = 'cart.php';
    echo json_encode($response);
    exit;
}

// Calcular precio total
$total_price = 0;
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
    $current = strtotime('+1 day', $current);
}

// Agregar al carrito
$sql = "INSERT INTO reservation_items (reservation_id, room_id, room_name, price_per_night, nights, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisdid", $reservation_id, $room_id, $room['name'], $room['price_per_night'], $nights, $total_price);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['redirect'] = 'cart.php';
    
    // Obtener cantidad
    $sql = "SELECT COUNT(*) as count FROM reservation_items WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['cart_count'] = $row['count'];
} else {
    $response['redirect'] = 'room_details.php?id=' . $room_id;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>