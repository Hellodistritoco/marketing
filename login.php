<?php
require_once 'config.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($usuario) && !empty($password)) {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_logueado'] = $user['usuario'];
            $_SESSION['bienvenido'] = $user['bienvenido'];
            header('Location: dashboard.php');
            exit();
        } else {
            $mensaje = 'Usuario o contraseña incorrectos';
        }
    } else {
        $mensaje = 'Todos los campos son obligatorios';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Kanban</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            min-height: 500px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Lado izquierdo - Formulario (tu código original pero mejorado) */
        .login-form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .kanban-icon {
            font-size: 48px;
            color: #F09146;
            margin-bottom: 15px;
        }

        .login-header h1 {
            color: #121A28;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: bold;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .login-title {
            font-size: 36px;
            font-weight: bold;
            color: #121A28;
            margin-bottom: 30px;
            text-align: center;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .social-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #ddd;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
            font-size: 18px;
        }

        .social-btn:hover {
            border-color: #F09146;
            color: #F09146;
            transform: translateY(-2px);
        }

        .social-btn.facebook:hover {
            border-color: #3b5998;
            color: #3b5998;
        }

        .social-btn.google:hover {
            border-color: #dd4b39;
            color: #dd4b39;
        }

        .social-btn.linkedin:hover {
            border-color: #0077b5;
            color: #0077b5;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            color: #666;
            font-size: 14px;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #121A28;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 18px 20px;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f5f5f5;
            outline: none;
        }

        .form-group input:focus {
            border-color: #F09146;
            background: white;
            box-shadow: 0 0 0 3px rgba(240, 145, 70, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .forgot-password {
            text-align: center;
            margin: 20px 0;
        }

        .forgot-password a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: #F09146;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #F09146, #A23004);
            color: white;
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(240, 145, 70, 0.3);
            background: linear-gradient(135deg, #A23004, #F09146);
        }

        .mensaje {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Lado derecho - Bienvenida */
        .welcome-section {
            background: linear-gradient(135deg, #F09146, #A23004);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-image {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .welcome-title {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .welcome-subtitle {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .signup-btn {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .signup-btn:hover {
            background: white;
            color: #F09146;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.2);
        }

        .footer-text {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            opacity: 0.8;
            font-style: italic;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .welcome-section {
                display: none;
            }

            .login-form-section {
                padding: 40px 30px;
            }

            .login-title {
                font-size: 28px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .login-form-section {
                padding: 30px 20px;
            }

            .login-title {
                font-size: 24px;
            }

            .login-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Lado izquierdo - Formulario (tu código original mejorado) -->
        <div class="login-form-section">
            <div class="login-header">
                <div class="kanban-icon">📋</div>
                <h1>Sistema Kanban</h1>
                <p>Accede a tu panel de control</p>
            </div>

            <h2 class="login-title">Sign in</h2>

            <div class="social-login">
                <div class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="social-btn google">
                    <i class="fab fa-google"></i>
                </div>
                <div class="social-btn linkedin">
                    <i class="fab fa-linkedin-in"></i>
                </div>
            </div>

            <div class="divider">
                <span>or use your account</span>
            </div>

            <?php if ($mensaje): ?>
                <div class="mensaje">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" 
                           id="usuario" 
                           name="usuario" 
                           placeholder="Email" 
                           required>
                </div>

                <div class="form-group">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Password" 
                           required>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="alert('Contacta al administrador para recuperar tu contraseña')">
                        Forgot your password?
                    </a>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>

        <!-- Lado derecho - Bienvenida (NUEVO) -->
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-image">
                    <i class="fas fa-rocket"></i>
                </div>
                
                <h1 class="welcome-title">Hello, Friend!</h1>
                
                <p class="welcome-subtitle">
                    Enter your personal details and start<br>
                    journey with us
                </p>
                
                <button class="signup-btn" onclick="alert('Contacta al administrador para crear una cuenta')">
                    SIGN UP
                </button>
            </div>
            
            <div class="footer-text">
                Desarrollado con ❤️ por el puto amo
            </div>
        </div>
    </div>

    <script>
        // Efecto hover en botones sociales
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                alert('Funcionalidad de login social próximamente disponible');
            });
        });

        // Animación de entrada para los elementos
        document.addEventListener('DOMContentLoaded', function() {
            const formInputs = document.querySelectorAll('.form-group input');
            
            formInputs.forEach((input, index) => {
                input.style.animationDelay = `${index * 0.1}s`;
                input.classList.add('fade-in');
            });
        });
    </script>

    <style>
        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>