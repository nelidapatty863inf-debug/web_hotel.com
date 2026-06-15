<?php
/**
 * API: OBTENER CANTIDAD DE ITEMS EN EL CARRITO
 * Método: GET
 * Retorna: JSON con el número de items
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'count' => 0];

$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

$sql = "SELECT COUNT(ri.id) as count 
        FROM reservations r 
        JOIN reservation_items ri ON r.id = ri.reservation_id 
        WHERE (r.session_id = ? OR r.user_id = ?) AND r.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$response['success'] = true;
$response['count'] = intval($row['count'] ?? 0);

echo json_encode($response);
?>