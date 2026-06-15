<?php
require_once 'includes/config.php';

echo "<h1>🔐 VERIFICACIÓN DE LOGIN</h1>";

$emails = ['admin@hotel.com', 'smileyr11@gmail.com', 'cliente@test.com'];

foreach ($emails as $email) {
    echo "<hr>";
    echo "<h3>Probando: $email</h3>";
    
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>✅ Usuario encontrado</p>";
        echo "<p>Rol: " . $user['role'] . "</p>";
        echo "<p>Status: " . $user['status'] . "</p>";
        
        // Determinar contraseña según el email
        if ($email == 'admin@hotel.com') {
            $test_password = 'admin123';
        } elseif ($email == 'smileyr11@gmail.com') {
            $test_password = 'njrc111';
        } else {
            $test_password = '12345';
        }
        
        if (password_verify($test_password, $user['password'])) {
            echo "<p style='color:green'>✅ CONTRASEÑA CORRECTA: $test_password</p>";
        } else {
            echo "<p style='color:red'>❌ CONTRASEÑA INCORRECTA: $test_password</p>";
            echo "<p>Password en BD: " . $user['password'] . "</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Usuario no encontrado</p>";
    }
}

echo "<hr>";
echo "<h2>📋 Todos los usuarios en BD:</h2>";
$sql = "SELECT id, email, role, status FROM users";
$result = $conn->query($sql);
echo "<table border='1' cellpadding='8'>";
echo "<tr style='background:#2c3e50; color:white'><th>ID</th><th>Email</th><th>Rol</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>