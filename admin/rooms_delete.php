<?php
/**
 * ELIMINAR HABITACIÓN - VERSIÓN MEJORADA CON BOOTSTRAP
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? 0;
$confirmed = $_GET['confirm'] ?? false;

if ($id > 0) {
    // Obtener información de la habitación antes de eliminarla
    $sql = "SELECT name, room_number FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        $_SESSION['admin_error'] = 'La habitación no existe';
        header('Location: rooms.php');
        exit;
    }
    
    // Verificar si la habitación tiene reservas
    $sql = "SELECT COUNT(*) as count FROM reservation_items WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $has_reservations = $row['count'] > 0;
    
    // Si tiene reservas, mostrar mensaje de error
    if ($has_reservations) {
        $_SESSION['admin_error'] = '❌ No se puede eliminar la habitación "' . htmlspecialchars($room['name']) . '" porque tiene ' . $row['count'] . ' reserva(s) asociada(s)';
        header('Location: rooms.php');
        exit;
    }
    
    // Si está confirmado, proceder a eliminar
    if ($confirmed === 'true') {
        // Eliminar disponibilidad asociada primero
        $sql = "DELETE FROM room_availability WHERE room_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Eliminar la habitación
        $sql = "DELETE FROM rooms WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_success'] = '✅ Habitación "' . htmlspecialchars($room['name']) . '" eliminada correctamente';
            
            // Registrar en el log
            if (function_exists('writeLog')) {
                writeLog("Usuario {$_SESSION['user_name']} eliminó la habitación ID: $id - {$room['name']}", 'info');
            }
        } else {
            $_SESSION['admin_error'] = '❌ Error al eliminar la habitación';
        }
        
        header('Location: rooms.php');
        exit;
    }
} else {
    $_SESSION['admin_error'] = 'ID de habitación inválido';
    header('Location: rooms.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación - Admin <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirm-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            margin: 2rem auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .warning-icon {
            width: 80px;
            height: 80px;
            background: #fee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .warning-icon i {
            font-size: 3rem;
            color: #e74c3c;
        }

        .room-details {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 1rem;
            margin: 1.5rem 0;
        }

        .room-details p {
            margin-bottom: 0.5rem;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231,76,60,0.3);
        }

        @media (max-width: 480px) {
            .confirm-card {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .btn-cancel, .btn-delete {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="confirm-card">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2 class="text-center mb-3">⚠️ Confirmar Eliminación</h2>
        
        <p class="text-center text-muted mb-4">
            ¿Estás seguro de que deseas eliminar la siguiente habitación?<br>
            Esta acción no se puede deshacer.
        </p>
        
        <div class="room-details">
            <p><strong><i class="fas fa-bed"></i> Habitación:</strong> <?= htmlspecialchars($room['name']) ?></p>
            <p><strong><i class="fas fa-hashtag"></i> Número:</strong> <?= htmlspecialchars($room['room_number'] ?? 'N/A') ?></p>
            <p class="mb-0"><strong><i class="fas fa-calendar-alt"></i> Fecha:</strong> <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="rooms.php" class="btn-cancel">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <a href="rooms_delete.php?id=<?= $id ?>&confirm=true" class="btn-delete">
                <i class="fas fa-trash-alt"></i> Eliminar
            </a>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> Las habitaciones con reservas asociadas no pueden eliminarse.
            </small>
        </div>
    </div>
</body>
</html>