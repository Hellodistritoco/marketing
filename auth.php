<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Login de usuario
    public function login($username, $password) {
        try {
            $query = "SELECT id, username, password, full_name, email FROM users WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Verificar contraseña
                if (password_verify($password, $user['password'])) {
                    // Crear sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email']
                        ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Contraseña incorrecta'];
                }
            } else {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Logout de usuario
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Sesión cerrada correctamente'];
    }
    
    // Registrar nuevo usuario
    public function register($username, $password, $email, $full_name) {
        try {
            // Verificar si el usuario ya existe
            $query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El usuario o email ya existe'];
            }
            
            // Crear nuevo usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, password, email, full_name) VALUES (:username, :password, :email, :full_name)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':full_name', $full_name);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Usuario registrado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al registrar usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Verificar sesión activa
    public function checkSession() {
        if (isset($_SESSION['user_id'])) {
            return [
                'success' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'No hay sesión activa'];
        }
    }
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $auth = new Auth();
    
    switch ($_GET['action'] ?? '') {
        case 'login':
            $result = $auth->login($input['username'] ?? '', $input['password'] ?? '');
            jsonResponse($result);
            break;
            
        case 'logout':
            $result = $auth->logout();
            jsonResponse($result);
            break;
            
        case 'register':
            $result = $auth->register(
                $input['username'] ?? '',
                $input['password'] ?? '',
                $input['email'] ?? '',
                $input['full_name'] ?? ''
            );
            jsonResponse($result);
            break;
            
        case 'check':
            $result = $auth->checkSession();
            jsonResponse($result);
            break;
            
        default:
            jsonResponse(['error' => 'Acción no válida'], 400);
    }
} else {
    jsonResponse(['error' => 'Método no permitido'], 405);
}
?>