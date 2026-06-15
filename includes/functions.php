<?php
/**
 * FUNCIONES GENERALES DEL SISTEMA
 */

require_once __DIR__ . '/config.php';

// =====================================================
// CÁLCULO DE IMPUESTOS
// =====================================================
function calculateTaxes($subtotal) {
    $sales_tax = round($subtotal * SALES_TAX_RATE, 2);
    $hotel_tax = round($subtotal * HOTEL_TAX_RATE, 2);
    
    return [
        'subtotal' => $subtotal,
        'sales_tax' => $sales_tax,
        'hotel_tax' => $hotel_tax,
        'total_taxes' => $sales_tax + $hotel_tax,
        'total_due' => $subtotal + $sales_tax + $hotel_tax
    ];
}

// =====================================================
// VALIDAR FECHAS
// =====================================================
function validateDates($check_in, $check_out) {
    $today = new DateTime();
    $today->modify('today');
    $checkInDate = new DateTime($check_in);
    $checkOutDate = new DateTime($check_out);
    
    if ($checkInDate < $today) {
        return ['valid' => false, 'message' => 'La fecha de entrada no puede ser anterior a hoy'];
    }
    
    if ($checkOutDate <= $checkInDate) {
        return ['valid' => false, 'message' => 'La fecha de salida debe ser posterior a la fecha de entrada'];
    }
    
    $interval = $checkInDate->diff($checkOutDate);
    $nights = $interval->days;
    
    if ($nights > MAX_NIGHTS) {
        return ['valid' => false, 'message' => 'La estancia no puede exceder ' . MAX_NIGHTS . ' noches'];
    }
    
    $maxAdvance = new DateTime();
    $maxAdvance->modify('+' . MAX_ADVANCE_DAYS . ' days');
    
    if ($checkInDate > $maxAdvance) {
        return ['valid' => false, 'message' => 'Solo se pueden hacer reservas con ' . MAX_ADVANCE_DAYS . ' días de anticipación'];
    }
    
    return ['valid' => true, 'nights' => $nights];
}

// =====================================================
// CALCULAR NOCHES
// =====================================================
function calculateNights($check_in, $check_out) {
    $diff = strtotime($check_out) - strtotime($check_in);
    return max(1, floor($diff / (60 * 60 * 24)));
}

// =====================================================
// PRECIO DINÁMICO
// =====================================================
function getDynamicPrice($conn, $room_id, $date) {
    return null;
}

// =====================================================
// OBTENER HABITACIONES DISPONIBLES
// =====================================================
function getAvailableRooms($conn, $check_in, $check_out, $adults = 1) {
    $sql = "SELECT r.*, rc.name as category_name
            FROM rooms r
            LEFT JOIN room_categories rc ON r.category_id = rc.id
            WHERE r.status = 'active' 
            AND r.capacity >= ?
            ORDER BY rc.sort_order, r.price_per_night";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $adults);
    $stmt->execute();
    return $stmt->get_result();
}

// =====================================================
// OBTENER CARRITO DE RESERVAS
// =====================================================
function getReservationCart($conn, $session_id, $user_id = null) {
    $sql = "SELECT r.*, ri.*, DATEDIFF(r.check_out, r.check_in) as nights
            FROM reservations r
            JOIN reservation_items ri ON r.id = ri.reservation_id
            WHERE (r.session_id = ? OR r.user_id = ?) AND r.status = 'pending'
            ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// =====================================================
// CREAR O ACTUALIZAR RESERVA PENDIENTE
// =====================================================
function getOrCreatePendingReservation($conn, $session_id, $user_id = null) {
    $sql = "SELECT id FROM reservations 
            WHERE (session_id = ? OR user_id = ?) AND status = 'pending'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    $check_in = date('Y-m-d', strtotime('+15 days'));
    $check_out = date('Y-m-d', strtotime('+17 days'));
    
    $sql = "INSERT INTO reservations (session_id, user_id, check_in, check_out, adults, children, status) 
            VALUES (?, ?, ?, ?, 2, 0, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $session_id, $user_id, $check_in, $check_out);
    $stmt->execute();
    
    return $conn->insert_id;
}

// =====================================================
// AGREGAR HABITACIÓN AL CARRITO
// =====================================================
function addRoomToCart($conn, $reservation_id, $room_id, $room_name, $price_per_night, $nights, $check_in, $check_out) {
    $sql = "SELECT id FROM reservation_items WHERE reservation_id = ? AND room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reservation_id, $room_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return false;
    }
    
    $subtotal = $price_per_night * $nights;
    
    $sql = "INSERT INTO reservation_items (reservation_id, room_id, room_name, price_per_night, nights, subtotal) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisdis", $reservation_id, $room_id, $room_name, $price_per_night, $nights, $subtotal);
    
    $sql_update = "UPDATE reservations SET check_in = ?, check_out = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssi", $check_in, $check_out, $reservation_id);
    $stmt_update->execute();
    
    return $stmt->execute();
}

// =====================================================
// ELIMINAR ITEM DEL CARRITO
// =====================================================
function removeCartItem($conn, $item_id) {
    $sql = "DELETE FROM reservation_items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    return $stmt->execute();
}

// =====================================================
// CALCULAR TOTAL DEL CARRITO
// =====================================================
function calculateCartTotal($conn, $reservation_id) {
    $sql = "SELECT SUM(subtotal) as total FROM reservation_items WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $subtotal = $row['total'] ?? 0;
    
    $sql_tp = "SELECT id FROM travel_protection WHERE reservation_id = ?";
    $stmt_tp = $conn->prepare($sql_tp);
    $stmt_tp->bind_param("i", $reservation_id);
    $stmt_tp->execute();
    
    if ($stmt_tp->get_result()->num_rows > 0) {
        $subtotal += TRAVEL_PROTECTION_FEE;
    }
    
    return calculateTaxes($subtotal);
}

// =====================================================
// OBTENER DISPONIBILIDAD DE HABITACIÓN
// =====================================================
function getRoomAvailability($conn, $room_id, $date) {
    $sql = "SELECT available_units FROM room_availability 
            WHERE room_id = ? AND available_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $room_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['available_units'];
    }
    
    $sql = "SELECT total_units FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total_units'] ?? 0;
}

// =====================================================
// ACTUALIZAR DISPONIBILIDAD
// =====================================================
function updateRoomAvailability($conn, $room_id, $date, $available_units) {
    $sql = "INSERT INTO room_availability (room_id, available_date, available_units) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE available_units = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $room_id, $date, $available_units, $available_units);
    return $stmt->execute();
}

// =====================================================
// ESTADÍSTICAS DEL DASHBOARD
// =====================================================
function getDashboardStats($conn) {
    $stats = [];
    
    $sql = "SELECT COUNT(*) as total FROM reservations WHERE DATE(created_at) = CURDATE()";
    $result = $conn->query($sql);
    $stats['reservations_today'] = $result->fetch_assoc()['total'];
    
    $sql = "SELECT COUNT(*) as total FROM reservations WHERE status = 'confirmed'";
    $result = $conn->query($sql);
    $stats['confirmed'] = $result->fetch_assoc()['total'];
    
    $sql = "SELECT SUM(total_amount) as total FROM reservations 
            WHERE MONTH(created_at) = MONTH(CURDATE()) 
            AND status IN ('confirmed', 'checked_out')";
    $result = $conn->query($sql);
    $stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;
    
    $sql = "SELECT COUNT(DISTINCT r.id) as occupied 
            FROM reservations r 
            WHERE CURDATE() BETWEEN r.check_in AND r.check_out 
            AND r.status IN ('confirmed', 'checked_in')";
    $result = $conn->query($sql);
    $stats['occupied'] = $result->fetch_assoc()['occupied'] ?? 0;
    
    $sql = "SELECT SUM(total_units) as total FROM rooms WHERE status = 'active'";
    $result = $conn->query($sql);
    $stats['total_rooms'] = $result->fetch_assoc()['total'] ?? 0;
    
    $stats['occupancy_rate'] = $stats['total_rooms'] > 0 ? round(($stats['occupied'] / $stats['total_rooms']) * 100) : 0;
    
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $result = $conn->query($sql);
    $stats['total_users'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

// =====================================================
// SANITIZAR ENTRADAS
// =====================================================
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
// =====================================================
// VALIDAR EMAIL
// =====================================================
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>