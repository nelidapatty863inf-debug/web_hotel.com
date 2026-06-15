<?php
session_start();
date_default_timezone_set('America/La_Paz');

// Base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_reservas');

// Sitio
define('SITE_NAME', 'Suites Hotel');
define('SITE_URL', 'http://localhost/web.com/');
define('SITE_EMAIL', 'reservaciones@suiteshotel.com');
define('SITE_PHONE', '+591 76543210');
define('SITE_ADDRESS', 'Av. Principal #123, La Paz - Bolivia');

// Impuestos Bolivia
define('SALES_TAX_RATE', 0.13);
define('HOTEL_TAX_RATE', 0.00);
define('TOTAL_TAX_RATE', 0.13);

// Reservas
define('CHECKIN_TIME', '15:00');
define('CHECKOUT_TIME', '12:00');
define('CANCELLATION_DAYS', 2);
define('MAX_ADVANCE_DAYS', 180);
define('MAX_NIGHTS', 30);

// Protección de viaje
define('TRAVEL_PROTECTION_FEE', 19.98);

// Moneda Bolivia
define('CURRENCY_SYMBOL', 'Bs. ');
define('CURRENCY_CODE', 'BOB');
define('DECIMAL_PLACES', 2);
define('DECIMAL_SEPARATOR', '.');
define('THOUSANDS_SEPARATOR', ',');

// Datos bancarios
define('BANK_NAME', 'Banco Unión');
define('BANK_ACCOUNT', '2734745-000-001');
define('YAPE_NUMBER', '+591 76543210');

// Email SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'smileyr881@gmail.com');
define('SMTP_PASS', 'jpdwavlfplxqbthw');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'smileyr881@gmail.com');
define('SMTP_FROM_NAME', SITE_NAME);

// Seguridad
define('SESSION_TIMEOUT', 7200);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);

// Conexión BD
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Sesión
if (!isset($_SESSION['reservation_session'])) {
    $_SESSION['reservation_session'] = session_id();
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['reservation_session'] = session_id();
}
$_SESSION['last_activity'] = time();

// Funciones
function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

function writeLog($message, $type = 'info') {
    $logFile = __DIR__ . '/../logs/system.log';
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function formatPrice($amount) {
    return CURRENCY_SYMBOL . number_format($amount, DECIMAL_PLACES, DECIMAL_SEPARATOR, THOUSANDS_SEPARATOR);
}
?>