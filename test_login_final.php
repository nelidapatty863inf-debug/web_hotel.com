<?php
require_once 'includes/config.php';

$email = 'smileyr881@gmail.com';
$password = 'Njr11';

echo "<h1>Verificando login</h1>";
echo "<p>Email: $email</p>";
echo "<p>Password: $password</p>";

$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    echo "<p>✅ Usuario encontrado</p>";
    echo "<p>Rol: " . $row['role'] . "</p>";
    echo "<p>Status: " . $row['status'] . "</p>";
    
    if (password_verify($password, $row['password'])) {
        echo "<p style='color:green'>✅ CONTRASEÑA CORRECTA</p>";
    } else {
        echo "<p style='color:red'>❌ CONTRASEÑA INCORRECTA</p>";
        
        // Forzar corrección
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_hash' WHERE email = '$email'");
        echo "<p>🔧 Contraseña actualizada. Ahora debería funcionar.</p>";
    }
} else {
    echo "<p>❌ Usuario no encontrado</p>";
}
?>