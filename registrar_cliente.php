<?php
require_once 'config.php';
verificarLogin();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    
    if (!empty($nombre_cliente) && !empty($responsable)) {
        try {
            $pdo = conectarDB();
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre_cliente, responsable) VALUES (?, ?)");
            $stmt->execute([$nombre_cliente, $responsable]);
            
            $mensaje = 'Cliente agregado exitosamente';
            $tipo_mensaje = 'success';
            
            // Redirigir después de 2 segundos
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'tareas.php';
                }, 2000);
            </script>";
            
        } catch (PDOException $e) {
            $mensaje = 'Error al agregar cliente: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Cliente - Sistema Kanban</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #121A28;
        }

        .header {
            background: #121A28;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #F09146;
        }

        .nav-menu {
            background: white;
            padding: 15px 0;
            border-bottom: 3px solid #F09146;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: #F09146;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: #A23004;
        }

        .nav-btn.active {
            background: #A23004;
        }

        .logout-btn {
            background: #A23004;
            margin-left: auto;
        }

        .main-content {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title h2 {
            color: #121A28;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 5px solid #F09146;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #121A28;
            font-weight: bold;
            font-size: 16px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #F09146;
        }

        .btn-submit {
            background: #F09146;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background: #A23004;
        }

        .mensaje {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-icon {
            text-align: center;
            font-size: 60px;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>📋 Sistema Kanban</h1>
            <div class="welcome-msg">
                <?php echo htmlspecialchars($_SESSION['bienvenido']); ?>
            </div>
        </div>
    </header>

    <nav class="nav-menu">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-btn">🏠 Dashboard</a>
            <a href="registrar_cliente.php" class="nav-btn active">👥 Registrar Cliente</a>
            <a href="tareas.php" class="nav-btn">📋 Tareas</a>
            <a href="notas.php" class="nav-btn">📝 Notas</a>
            <a href="logout.php" class="nav-btn logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-content">
        <a href="dashboard.php" class="back-btn">← Volver al Dashboard</a>
        
        <div class="page-title">
            <h2>👥 Registrar Cliente</h2>
            <p>Agrega un nuevo cliente al sistema</p>
        </div>

        <div class="form-container">
            <div class="form-icon">🏢</div>
            
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="nombre_cliente">Nombre del Cliente:</label>
                    <input type="text" id="nombre_cliente" name="nombre_cliente" required 
                           placeholder="Ej: Empresa ABC, Corporación XYZ">
                </div>

                <div class="form-group">
                    <label for="responsable">Responsable:</label>
                    <input type="text" id="responsable" name="responsable" required 
                           placeholder="Ej: Juan Pérez, María García">
                </div>

                <button type="submit" class="btn-submit">✅ Agregar Cliente</button>
            </form>
        </div>
    </main>
</body>
</html>