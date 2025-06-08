<?php
require_once 'config.php';

class TaskManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener todas las tareas del usuario con información del cliente
    public function getTasks($user_id, $status = null) {
        try {
            $query = "SELECT t.*, c.name as client_name, c.company as client_company 
                     FROM tasks t 
                     LEFT JOIN clients c ON t.client_id = c.id 
                     WHERE t.user_id = :user_id";
                     
            if ($status) {
                $query .= " AND t.status = :status";
            }
            
            $query .= " ORDER BY t.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            
            $stmt->execute();
            
            return [
                'success' => true,
                'tasks' => $stmt->fetchAll()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener tareas: ' . $e->getMessage()];
        }
    }
    
    // Crear nueva tarea
    public function createTask($user_id, $data) {
        try {
            $query = "INSERT INTO tasks (user_id, client_id, title, description, status, priority, due_date) 
                     VALUES (:user_id, :client_id, :title, :description, :status, :priority, :due_date)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':due_date', $data['due_date']);
            
            if ($stmt->execute()) {
                $task_id = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Tarea creada correctamente',
                    'task_id' => $task_id
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear tarea'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Actualizar tarea
    public function updateTask($user_id, $task_id, $data) {
        try {
            $query = "UPDATE tasks SET client_id = :client_id, title = :title, description = :description, 
                     status = :status, priority = :priority, due_date = :due_date, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :task_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':due_date', $data['due_date']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Tarea actualizada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar tarea'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Actualizar solo el estado de una tarea (para drag & drop)
    public function updateTaskStatus($user_id, $task_id, $status) {
        try {
            $query = "UPDATE tasks SET status = :status, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :task_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Estado de tarea actualizado'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar estado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Eliminar tarea
    public function deleteTask($user_id, $task_id) {
        try {
            $query = "DELETE FROM tasks WHERE id = :task_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Tarea eliminada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar tarea'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Obtener tarea específica
    public function getTask($user_id, $task_id) {
        try {
            $query = "SELECT t.*, c.name as client_name, c.company as client_company 
                     FROM tasks t 
                     LEFT JOIN clients c ON t.client_id = c.id 
                     WHERE t.id = :task_id AND t.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'task' => $stmt->fetch()
                ];
            } else {
                return ['success' => false, 'message' => 'Tarea no encontrada'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Obtener estadísticas de tareas
    public function getTaskStats($user_id) {
        try {
            $query = "SELECT 
                        status,
                        COUNT(*) as count,
                        priority,
                        COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
                        COUNT(CASE WHEN priority = 'medium' THEN 1 END) as medium_priority,
                        COUNT(CASE WHEN priority = 'low' THEN 1 END) as low_priority
                     FROM tasks 
                     WHERE user_id = :user_id 
                     GROUP BY status, priority";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $stats = [
                'todo' => 0,
                'progress' => 0,
                'review' => 0,
                'done' => 0,
                'total' => 0,
                'high_priority' => 0,
                'medium_priority' => 0,
                'low_priority' => 0
            ];
            
            while ($row = $stmt->fetch()) {
                $stats[$row['status']] = $row['count'];
                $stats['total'] += $row['count'];
            }
            
            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()];
        }
    }
}

// Verificar autenticación
checkLogin();

$taskManager = new TaskManager();
$user_id = $_SESSION['user_id'];

// API endpoints
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'stats':
                    $result = $taskManager->getTaskStats($user_id);
                    break;
                default:
                    jsonResponse(['error' => 'Acción no válida'], 400);
            }
        } else if (isset($_GET['id'])) {
            $result = $taskManager->getTask($user_id, $_GET['id']);
        } else {
            $status = $_GET['status'] ?? null;
            $result = $taskManager->getTasks($user_id, $status);
        }
        jsonResponse($result);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $taskManager->createTask($user_id, $input);
        jsonResponse($result);
        break;
        
    case 'PUT':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de tarea requerido'], 400);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Si solo se está actualizando el estado
        if (isset($_GET['action']) && $_GET['action'] === 'status') {
            $result = $taskManager->updateTaskStatus($user_id, $_GET['id'], $input['status']);
        } else {
            $result = $taskManager->updateTask($user_id, $_GET['id'], $input);
        }
        jsonResponse($result);
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de tarea requerido'], 400);
        }
        $result = $taskManager->deleteTask($user_id, $_GET['id']);
        jsonResponse($result);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>