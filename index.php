<?php
require_once 'config.php';

// Verificar si el usuario ya está logueado
if (isset($_SESSION['usuario_logueado'])) {
    // Si ya está logueado, redirigir al dashboard
    header('Location: dashboard.php');
    exit();
} else {
    // Si no está logueado, redirigir al login
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Kanban - Cargando...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #121A28 0%, #A23004 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .loading-container {
            text-align: center;
            padding: 40px;
        }

        .loading-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }

        .loading-text {
            font-size: 24px;
            font-weight: bold;
            color: #F09146;
        }

        .loading-subtitle {
            font-size: 16px;
            margin-top: 10px;
            opacity: 0.8;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid #F09146;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
        // Si por alguna razón el PHP no redirige, hacer redirección por JavaScript después de 3 segundos
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
</head>
<body>
    <div class="loading-container">
        <div class="loading-icon">📋</div>
        <div class="loading-text">Sistema Kanban</div>
        <div class="loading-subtitle">Iniciando sistema...</div>
        <div class="spinner"></div>
        <p style="font-size: 14px; margin-top: 20px; opacity: 0.7;">
            Si no eres redirigido automáticamente, 
            <a href="login.php" style="color: #F09146; text-decoration: none;">haz clic aquí</a>
        </p>
    </div>
</body>
</html>