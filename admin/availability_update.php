<?php
/**
 * ACTUALIZAR DISPONIBILIDAD - VERSIÓN MEJORADA CON BOOTSTRAP
 * Moneda: Bolivianos (BOB)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Configurar moneda en Bolivianos
define('CURRENCY_SYMBOL', 'Bs. ');
define('CURRENCY_CODE', 'BOB');

$message = '';
$message_type = '';
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$month = isset($_POST['month']) ? intval($_POST['month']) : date('m');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $year = intval($_POST['year']);
    $month = intval($_POST['month']);
    $availability_data = $_POST['availability'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    $updated_rooms = [];
    
    foreach ($availability_data as $room_id => $days) {
        foreach ($days as $day => $units) {
            $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $units = intval($units);
            
            if ($units >= 0) {
                if (updateRoomAvailability($conn, $room_id, $date, $units)) {
                    $success_count++;
                    $updated_rooms[$room_id] = true;
                } else {
                    $error_count++;
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $message = "✅ Disponibilidad actualizada: $success_count cambios guardados";
        $message_type = "success";
        
        // Registrar en el log
        if (function_exists('writeLog')) {
            writeLog("Usuario {$_SESSION['user_name']} actualizó disponibilidad para $success_count fechas", 'info');
        }
    }
    
    if ($error_count > 0) {
        $message .= " ⚠️ $error_count errores";
        $message_type = "warning";
    }
    
    if ($success_count == 0 && $error_count == 0) {
        $message = "ℹ️ No se realizaron cambios";
        $message_type = "info";
    }
}

// Redirigir con mensaje
header('Location: availability.php?year=' . $year . '&month=' . $month . '&msg=' . urlencode($message) . '&msg_type=' . $message_type);
exit;
?>