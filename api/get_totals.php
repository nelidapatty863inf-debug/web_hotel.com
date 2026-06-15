<?php
/**
 * API: OBTENER TOTALES DEL CARRITO
 * Método: GET
 * Parámetros: include_tax (opcional, default true)
 * Retorna: JSON con subtotal, impuestos y total
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'data' => []];

$include_tax = isset($_GET['include_tax']) ? filter_var($_GET['include_tax'], FILTER_VALIDATE_BOOLEAN) : true;
$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

// Buscar reserva pendiente
$sql = "SELECT id FROM reservations WHERE (session_id = ? OR user_id = ?) AND status = 'pending' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $reservation_id = $row['id'];
    
    // Obtener subtotal de items
    $sql = "SELECT SUM(subtotal) as subtotal FROM reservation_items WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $subtotal = floatval($row['subtotal'] ?? 0);
    
    // Verificar si tiene travel protection
    $sql = "SELECT id FROM travel_protection WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $subtotal += TRAVEL_PROTECTION_FEE;
    }
    
    if ($include_tax) {
        $taxes = calculateTaxes($subtotal);
        $response['data'] = $taxes;
    } else {
        $response['data'] = [
            'subtotal' => $subtotal,
            'formatted_subtotal' => formatPrice($subtotal)
        ];
    }
    
    // Obtener conteo de items
    $sql = "SELECT COUNT(*) as item_count FROM reservation_items WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['data']['item_count'] = $row['item_count'];
    $response['data']['reservation_id'] = $reservation_id;
    
} else {
    // Carrito vacío
    if ($include_tax) {
        $response['data'] = calculateTaxes(0);
    } else {
        $response['data'] = [
            'subtotal' => 0,
            'formatted_subtotal' => formatPrice(0)
        ];
    }
    $response['data']['item_count'] = 0;
    $response['data']['reservation_id'] = null;
}

$response['success'] = true;
$response['data']['currency'] = CURRENCY_SYMBOL;
$response['data']['tax_rate'] = TOTAL_TAX_RATE * 100;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>