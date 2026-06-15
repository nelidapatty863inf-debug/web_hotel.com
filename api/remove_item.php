<?php
/**
 * API: ELIMINAR ITEM DEL CARRITO
 * Método: POST/GET
 * Parámetros: id (item_id)
 * Retorna: JSON con resultado
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// Obtener ID del item
$item_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($item_id <= 0) {
    $response['message'] = 'ID de item inválido';
    echo json_encode($response);
    exit;
}

// Obtener información del item y la reserva
$sql = "SELECT ri.*, r.session_id, r.user_id 
        FROM reservation_items ri 
        JOIN reservations r ON ri.reservation_id = r.id 
        WHERE ri.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    $response['message'] = 'Item no encontrado';
    echo json_encode($response);
    exit;
}

// Verificar permisos (solo el dueño de la reserva puede eliminar)
$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

if ($item['session_id'] != $session_id && ($user_id === null || $item['user_id'] != $user_id)) {
    $response['message'] = 'No tienes permiso para eliminar este item';
    echo json_encode($response);
    exit;
}

// Eliminar el item
$sql = "DELETE FROM reservation_items WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Item eliminado correctamente';
    
    // Verificar si quedan items en la reserva
    $sql = "SELECT COUNT(*) as count FROM reservation_items WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item['reservation_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['cart_count'] = $row['count'];
    
    // Si no quedan items, eliminar la reserva pendiente
    if ($row['count'] == 0) {
        $sql = "DELETE FROM reservations WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item['reservation_id']);
        $stmt->execute();
    }
} else {
    $response['message'] = 'Error al eliminar el item';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>