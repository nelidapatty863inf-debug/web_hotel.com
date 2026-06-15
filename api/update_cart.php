<?php
/**
 * API: ACTUALIZAR CARRITO (cantidades, fechas, etc.)
 * Método: POST
 * Parámetros: action, item_id, nights (opcional), check_in (opcional), check_out (opcional)
 * Retorna: JSON con resultado y totales actualizados
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'message' => '', 'cart' => [], 'totals' => []];

$action = $_POST['action'] ?? '';
$item_id = intval($_POST['item_id'] ?? 0);
$nights = intval($_POST['nights'] ?? 0);
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';

$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

switch ($action) {
    case 'update_nights':
        // Actualizar número de noches de un item
        if ($item_id <= 0 || $nights <= 0) {
            $response['message'] = 'Parámetros inválidos';
            break;
        }
        
        // Obtener información del item
        $sql = "SELECT ri.*, r.check_in, r.check_out, r.adults, r.children 
                FROM reservation_items ri 
                JOIN reservations r ON ri.reservation_id = r.id 
                WHERE ri.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        
        if (!$item) {
            $response['message'] = 'Item no encontrado';
            break;
        }
        
        // Calcular nuevo subtotal
        $new_subtotal = $item['price_per_night'] * $nights;
        
        // Actualizar
        $sql = "UPDATE reservation_items SET nights = ?, subtotal = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idi", $nights, $new_subtotal, $item_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Noches actualizadas';
        } else {
            $response['message'] = 'Error al actualizar';
        }
        break;
        
    case 'update_dates':
        // Actualizar fechas de toda la reserva
        if (empty($check_in) || empty($check_out)) {
            $response['message'] = 'Fechas inválidas';
            break;
        }
        
        // Validar fechas
        $dateValidation = validateDates($check_in, $check_out);
        if (!$dateValidation['valid']) {
            $response['message'] = $dateValidation['message'];
            break;
        }
        
        $new_nights = $dateValidation['nights'];
        
        // Obtener reserva actual
        $sql = "SELECT DISTINCT reservation_id FROM reservation_items WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        if (!$item) {
            $response['message'] = 'Reserva no encontrada';
            break;
        }
        
        $reservation_id = $item['reservation_id'];
        
        // Actualizar fechas de la reserva
        $sql = "UPDATE reservations SET check_in = ?, check_out = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $check_in, $check_out, $reservation_id);
        $stmt->execute();
        
        // Actualizar subtotales de todos los items
        $sql = "SELECT ri.id, ri.room_id, ri.price_per_night, r.check_in, r.check_out 
                FROM reservation_items ri 
                JOIN reservations r ON ri.reservation_id = r.id 
                WHERE ri.reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $items = $stmt->get_result();
        
        while ($cart_item = $items->fetch_assoc()) {
            $new_subtotal = $cart_item['price_per_night'] * $new_nights;
            $sql_update = "UPDATE reservation_items SET nights = ?, subtotal = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("idi", $new_nights, $new_subtotal, $cart_item['id']);
            $stmt_update->execute();
        }
        
        $response['success'] = true;
        $response['message'] = 'Fechas actualizadas';
        break;
        
    case 'clear_cart':
        // Vaciar todo el carrito
        $sql = "SELECT id FROM reservations WHERE (session_id = ? OR user_id = ?) AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sql_delete = "DELETE FROM reservation_items WHERE reservation_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $row['id']);
            $stmt_delete->execute();
            
            $sql_delete_res = "DELETE FROM reservations WHERE id = ?";
            $stmt_delete_res = $conn->prepare($sql_delete_res);
            $stmt_delete_res->bind_param("i", $row['id']);
            $stmt_delete_res->execute();
        }
        
        $response['success'] = true;
        $response['message'] = 'Carrito vaciado';
        break;
        
    default:
        $response['message'] = 'Acción no válida';
        break;
}

// Si la operación fue exitosa, obtener los totales actualizados
if ($response['success']) {
    // Obtener reserva pendiente
    $sql = "SELECT id FROM reservations WHERE (session_id = ? OR user_id = ?) AND status = 'pending' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $totals = calculateCartTotal($conn, $row['id']);
        $response['totals'] = $totals;
        
        // Obtener items actualizados
        $sql = "SELECT ri.*, r.check_in, r.check_out 
                FROM reservation_items ri 
                JOIN reservations r ON ri.reservation_id = r.id 
                WHERE ri.reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $items = $stmt->get_result();
        
        while ($cart_item = $items->fetch_assoc()) {
            $response['cart'][] = [
                'id' => $cart_item['id'],
                'room_name' => $cart_item['room_name'],
                'price_per_night' => $cart_item['price_per_night'],
                'nights' => $cart_item['nights'],
                'subtotal' => $cart_item['subtotal'],
                'check_in' => $cart_item['check_in'],
                'check_out' => $cart_item['check_out']
            ];
        }
    } else {
        $response['totals'] = calculateTaxes(0);
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>