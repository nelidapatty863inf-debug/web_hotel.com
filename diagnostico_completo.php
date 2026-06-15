<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DIAGNÓSTICO COMPLETO DEL LOGIN</h1>";

// Conectar a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'hotel_reservas';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("<p style='color:red'>❌ Error de conexión: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green'>✅ Conexión a BD exitosa</p>";

// 1. Verificar la tabla users
echo "<h2>1. Estructura de la tabla users</h2>";
$sql = "DESCRIBE users";
$result = $conn->query($sql);
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>❌ Error: " . $conn->error . "</p>";
}

// 2. Ver todos los usuarios
echo "<h2>2. Usuarios en la base de datos</h2>";
$sql = "SELECT id, email, first_name, role, status, LEFT(password, 35) as pass_preview FROM users";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr style='background:#2c3e50; color:white'><th>ID</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Status</th><th>Password (primeros 35 chars)</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['pass_preview'] . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>❌ No hay usuarios en la BD</p>";
}

// 3. Probar login con admin@hotel.com / 12345
echo "<h2>3. Probando login con admin@hotel.com / 12345</h2>";
$email = 'admin@hotel.com';
$password = '12345';

$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p>✅ Usuario encontrado</p>";
    echo "<p>ID: " . $user['id'] . "</p>";
    echo "<p>Email: " . $user['email'] . "</p>";
    echo "<p>Rol: " . $user['role'] . "</p>";
    echo "<p>Status: " . $user['status'] . "</p>";
    echo "<p>Password en BD: " . $user['password'] . "</p>";
    
    if (password_verify($password, $user['password'])) {
        echo "<p style='color:green; font-size:1.2rem'>✅ CONTRASEÑA CORRECTA</p>";
        echo "<p>El login DEBERÍA FUNCIONAR correctamente.</p>";
    } else {
        echo "<p style='color:red; font-size:1.2rem'>❌ CONTRASEÑA INCORRECTA</p>";
        echo "<p>La contraseña '$password' NO coincide con la BD.</p>";
        
        // Corregir la contraseña
        echo "<h3>🔧 Corrigiendo contraseña...</h3>";
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $update = "UPDATE users SET password = '$new_hash' WHERE email = '$email'";
        if ($conn->query($update)) {
            echo "<p style='color:green'>✅ Contraseña actualizada correctamente. Ahora intenta login nuevamente.</p>";
        } else {
            echo "<p style='color:red'>❌ Error al actualizar: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ Usuario no encontrado: $email</p>";
    echo "<h3>🔧 Creando usuario...</h3>";
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = "INSERT INTO users (email, password, first_name, last_name, role, status) 
               VALUES ('$email', '$hash', 'Admin', 'Principal', 'admin', 'active')";
    if ($conn->query($insert)) {
        echo "<p style='color:green'>✅ Usuario creado correctamente. Ahora intenta login.</p>";
    } else {
        echo "<p style='color:red'>❌ Error al crear: " . $conn->error . "</p>";
    }
}

// 4. Probar login con smileyr11@gmail.com
echo "<h2>4. Probando login con smileyr11@gmail.com / njrc111</h2>";
$email2 = 'smileyr11@gmail.com';
$password2 = 'njrc111';

$sql = "SELECT * FROM users WHERE email = '$email2'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password2, $user['password'])) {
        echo "<p style='color:green'>✅ CONTRASEÑA CORRECTA para $email2</p>";
    } else {
        echo "<p style='color:red'>❌ CONTRASEÑA INCORRECTA para $email2</p>";
        // Corregir
        $new_hash = password_hash($password2, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_hash' WHERE email = '$email2'");
        echo "<p>🔧 Contraseña corregida. Ahora prueba: $email2 / $password2</p>";
    }
} else {
    echo "<p style='color:red'>❌ Usuario no encontrado: $email2</p>";
    $hash = password_hash($password2, PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (email, password, first_name, last_name, role, status) 
                  VALUES ('$email2', '$hash', 'Smileyr', 'Admin', 'admin', 'active')");
    echo "<p>✅ Usuario creado. Prueba: $email2 / $password2</p>";
}

echo "<h2>5. Enlace para probar login</h2>";
echo "<a href='admin/login.php' style='display:inline-block; background:#2ecc71; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Ir al Login</a>";
?>