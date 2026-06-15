<?php
/**
 * API: APLICAR CUPÓN DE DESCUENTO
 * Método: POST
 * Parámetros: coupon_code
 * Retorna: JSON con resultado y descuento aplicado
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'message' => '', 'discount' => 0, 'new_total' => 0];

$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? $_GET['coupon_code'] ?? ''));
$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

if (empty($coupon_code)) {
    $response['message'] = 'Ingresa un código de cupón';
    echo json_encode($response);
    exit;
}

// Verificar cupón
$today = date('Y-m-d');
$sql = "SELECT * FROM coupons 
        WHERE code = ? 
        AND status = 'active' 
        AND valid_from <= ? 
        AND valid_to >= ?
        AND (usage_limit IS NULL OR used_count < usage_limit)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $coupon_code, $today, $today);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();

if (!$coupon) {
    $response['message'] = 'Cupón inválido o expirado';
    echo json_encode($response);
    exit;
}

// Obtener reserva pendiente
$sql = "SELECT id, total_amount, subtotal FROM reservations 
        WHERE (session_id = ? OR user_id = ?) AND status = 'pending' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    $response['message'] = 'No hay reserva activa';
    echo json_encode($response);
    exit;
}

// Calcular subtotal actual
$sql = "SELECT SUM(subtotal) as subtotal FROM reservation_items WHERE reservation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation['id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$subtotal = floatval($row['subtotal'] ?? 0);

// Verificar monto mínimo
if ($coupon['min_purchase'] && $subtotal < $coupon['min_purchase']) {
    $response['message'] = 'El monto mínimo para este cupón es ' . formatPrice($coupon['min_purchase']);
    echo json_encode($response);
    exit;
}

// Calcular descuento
if ($coupon['discount_type'] == 'percentage') {
    $discount = $subtotal * ($coupon['discount_value'] / 100);
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
        $discount = $coupon['max_discount'];
    }
} else {
    $discount = $coupon['discount_value'];
}

// Aplicar descuento
$sql = "UPDATE reservations SET discount = ?, coupon_code = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("dsi", $discount, $coupon_code, $reservation['id']);
$stmt->execute();

// Incrementar uso del cupón
$sql = "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coupon['id']);
$stmt->execute();

$new_subtotal = $subtotal - $discount;
$taxes = calculateTaxes($new_subtotal);

$response['success'] = true;
$response['message'] = 'Cupón aplicado correctamente';
$response['discount'] = $discount;
$response['formatted_discount'] = formatPrice($discount);
$response['new_subtotal'] = $new_subtotal;
$response['new_total'] = $taxes['total_due'];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>