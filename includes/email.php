<?php
/**
 * ENVÍO DE EMAILS CON PHPMailer
 */

require_once __DIR__ . '/config.php';

// Verificar si existe PHPMailer
$phpmailer_file = __DIR__ . '/../phpmailer/src/PHPMailer.php';

if (!file_exists($phpmailer_file)) {
    // Si no está instalado, usar función simulada
    function sendConfirmationEmail($to, $reservation_code, $details) {
        writeLog("Email simulado para: $to - Reserva: $reservation_code", 'info');
        return true;
    }
    return;
}

// Cargar PHPMailer
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendConfirmationEmail($to, $reservation_code, $details) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = "✅ Confirmación de Reserva - " . SITE_NAME;
        $mail->Body    = "
        <html>
        <body style='font-family:Arial;'>
            <h2>🏨 " . SITE_NAME . "</h2>
            <h3>¡Reserva Confirmada!</h3>
            <p><strong>Código:</strong> " . $reservation_code . "</p>
            <p><strong>Detalles:</strong><br>" . nl2br($details) . "</p>
            <p>Gracias por preferirnos.</p>
            <hr>
            <small>" . SITE_ADDRESS . "<br>Tel: " . SITE_PHONE . "</small>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>