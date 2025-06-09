<?php
// Script para crear o actualizar el usuario administrador
// Ejecuta este archivo UNA VEZ y luego elimínalo

require_once 'config.php';

echo "<h2>🔐 Crear/Actualizar Usuario Administrador</h2>";
echo "<hr>";

try {
    $pdo = conectarDB();
    echo "✅ Conexión a base de datos exitosa<br><br>";
    
    // Datos del usuario admin
    $usuario = 'admin';
    $password_plain = 'admin123';
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $bienvenido = 'Bienvenido - este tu fucking control';
    
    echo "<h3>📋 Datos del usuario:</h3>";
    echo "Usuario: <strong>$usuario</strong><br>";
    echo "Contraseña: <strong>$password_plain</strong><br>";
    echo "Mensaje: <strong>$bienvenido</strong><br>";
    echo "Hash generado: <code style='font-size:10px;'>$password_hash</code><br><br>";
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $usuario_existe = $stmt->fetch();
    
    if ($usuario_existe) {
        // Actualizar usuario existente
        echo "<h3>🔄 Actualizando usuario existente...</h3>";
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, bienvenido = ? WHERE usuario = ?");
        $stmt->execute([$password_hash, $bienvenido, $usuario]);
        echo "✅ Usuario '<strong>$usuario</strong>' actualizado correctamente<br>";
    } else {
        // Crear nuevo usuario
        echo "<h3>➕ Creando nuevo usuario...</h3>";
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, bienvenido) VALUES (?, ?, ?)");
        $stmt->execute([$usuario, $password_hash, $bienvenido]);
        echo "✅ Usuario '<strong>$usuario</strong>' creado correctamente<br>";
    }
    
    echo "<br><h3>🧪 Probando login...</h3>";
    
    // Probar que el login funciona
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password_plain, $user['password'])) {
        echo "✅ <strong>Login funciona correctamente!</strong><br>";
        echo "👉 Ahora puedes ir a <a href='login.php' style='color:#F09146; font-weight:bold;'>login.php</a> y entrar con:<br>";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Usuario: <strong>admin</strong><br>";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;Contraseña: <strong>admin123</strong><br>";
    } else {
        echo "❌ Error: El login no funciona. Hay un problema con el hash de contraseña.<br>";
    }
    
    echo "<br><h3>📊 Usuarios en la base de datos:</h3>";
    $stmt = $pdo->query("SELECT id, usuario, bienvenido, fecha_creacion FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usuarios)) {
        echo "❌ No hay usuarios en la base de datos<br>";
    } else {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background:#F09146; color:white;'><th>ID</th><th>Usuario</th><th>Mensaje</th><th>Fecha</th></tr>";
        foreach ($usuarios as $u) {
            echo "<tr>";
            echo "<td>" . $u['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($u['usuario']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($u['bienvenido']) . "</td>";
            echo "<td>" . $u['fecha_creacion'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<br>🔧 <strong>Soluciones:</strong><br>";
    echo "1. Verifica que la tabla 'usuarios' existe<br>";
    echo "2. Ejecuta el archivo estructura.sql<br>";
    echo "3. Verifica la conexión a la base de datos<br>";
}

echo "<hr>";
echo "<p style='color:#A23004; font-weight:bold;'>⚠️ IMPORTANTE: Elimina este archivo (crear_usuario.php) después de usarlo por seguridad.</p>";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario Admin</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background-color: #f5f5f5;
        }
        h2 { color: #121A28; }
        h3 { color: #F09146; }
        table { 
            background: white; 
            padding: 10px; 
            border-radius: 5px;
            margin-top: 10px;
        }
        th, td { 
            padding: 8px 12px; 
            text-align: left; 
        }
        a { 
            color: #A23004; 
            text-decoration: none;
            font-weight: bold;
        }
        a:hover { 
            text-decoration: underline; 
        }
        code {
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
            word-break: break-all;
        }
    </style>
</head>
<body>
</body>
</html>