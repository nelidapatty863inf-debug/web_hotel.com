<?php
/**
 * API: ACTIVAR/DESACTIVAR PROTECCIÓN DE VIAJE
 * Método: POST
 * Parámetros: enabled (true/false)
 * Retorna: JSON con resultado
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'message' => ''];

$enabled = filter_var($_POST['enabled'] ?? $_GET['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
$session_id = $_SESSION['reservation_session'];
$user_id = $_SESSION['user_id'] ?? null;

// Obtener reserva pendiente
$sql = "SELECT id FROM reservations WHERE (session_id = ? OR user_id = ?) AND status = 'pending' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $session_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    $response['message'] = 'No hay reserva activa';
    echo json_encode($response);
    exit;
}

$reservation_id = $row['id'];

if ($enabled) {
    // Agregar travel protection
    $sql = "INSERT INTO travel_protection (reservation_id, fee) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("id", $reservation_id, TRAVEL_PROTECTION_FEE);
} else {
    // Eliminar travel protection
    $sql = "DELETE FROM travel_protection WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
}

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = $enabled ? 'Protección de viaje agregada' : 'Protección de viaje eliminada';
} else {
    $response['message'] = 'Error al actualizar';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>