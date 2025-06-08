<?php
require_once 'config.php';

class ClientManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener todos los clientes del usuario
    public function getClients($user_id) {
        try {
            $query = "SELECT * FROM clients WHERE user_id = :user_id ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return [
                'success' => true,
                'clients' => $stmt->fetchAll()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener clientes: ' . $e->getMessage()];
        }
    }
    
    // Crear nuevo cliente
    public function createClient($user_id, $data) {
        try {
            $query = "INSERT INTO clients (user_id, name, email, company, phone, notes) 
                     VALUES (:user_id, :name, :email, :company, :phone, :notes)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':company', $data['company']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':notes', $data['notes']);
            
            if ($stmt->execute()) {
                $client_id = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Cliente creado correctamente',
                    'client_id' => $client_id
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear cliente'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Actualizar cliente
    public function updateClient($user_id, $client_id, $data) {
        try {
            $query = "UPDATE clients SET name = :name, email = :email, company = :company, 
                     phone = :phone, notes = :notes, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :client_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':company', $data['company']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':notes', $data['notes']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Cliente actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar cliente'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Eliminar cliente
    public function deleteClient($user_id, $client_id) {
        try {
            // Verificar si el cliente tiene tareas asociadas
            $query = "SELECT COUNT(*) as task_count FROM tasks WHERE client_id = :client_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result['task_count'] > 0) {
                return [
                    'success' => false, 
                    'message' => 'No se puede eliminar el cliente porque tiene tareas asociadas'
                ];
            }
            
            // Eliminar cliente
            $query = "DELETE FROM clients WHERE id = :client_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Cliente eliminado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar cliente'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Obtener cliente específico
    public function getClient($user_id, $client_id) {
        try {
            $query = "SELECT * FROM clients WHERE id = :client_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'client' => $stmt->fetch()
                ];
            } else {
                return ['success' => false, 'message' => 'Cliente no encontrado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
}

// Verificar autenticación
checkLogin();

$clientManager = new ClientManager();
$user_id = $_SESSION['user_id'];

// API endpoints
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $result = $clientManager->getClient($user_id, $_GET['id']);
        } else {
            $result = $clientManager->getClients($user_id);
        }
        jsonResponse($result);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $clientManager->createClient($user_id, $input);
        jsonResponse($result);
        break;
        
    case 'PUT':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de cliente requerido'], 400);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $clientManager->updateClient($user_id, $_GET['id'], $input);
        jsonResponse($result);
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de cliente requerido'], 400);
        }
        $result = $clientManager->deleteClient($user_id, $_GET['id']);
        jsonResponse($result);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>