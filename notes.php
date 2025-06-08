<?php
require_once 'config.php';

class NotesManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener todas las notas del usuario
    public function getNotes($user_id, $task_id = null) {
        try {
            $query = "SELECT n.*, t.title as task_title 
                     FROM notes n 
                     LEFT JOIN tasks t ON n.task_id = t.id 
                     WHERE n.user_id = :user_id";
                     
            if ($task_id) {
                $query .= " AND n.task_id = :task_id";
            }
            
            $query .= " ORDER BY n.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($task_id) {
                $stmt->bindParam(':task_id', $task_id);
            }
            
            $stmt->execute();
            
            return [
                'success' => true,
                'notes' => $stmt->fetchAll()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener notas: ' . $e->getMessage()];
        }
    }
    
    // Crear nueva nota
    public function createNote($user_id, $data) {
        try {
            $query = "INSERT INTO notes (user_id, task_id, title, content) 
                     VALUES (:user_id, :task_id, :title, :content)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':task_id', $data['task_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':content', $data['content']);
            
            if ($stmt->execute()) {
                $note_id = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Nota creada correctamente',
                    'note_id' => $note_id
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear nota'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Actualizar nota
    public function updateNote($user_id, $note_id, $data) {
        try {
            $query = "UPDATE notes SET task_id = :task_id, title = :title, content = :content, 
                     updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :note_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $note_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':task_id', $data['task_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':content', $data['content']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Nota actualizada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar nota'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Eliminar nota
    public function deleteNote($user_id, $note_id) {
        try {
            $query = "DELETE FROM notes WHERE id = :note_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $note_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Nota eliminada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar nota'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Obtener nota específica
    public function getNote($user_id, $note_id) {
        try {
            $query = "SELECT n.*, t.title as task_title 
                     FROM notes n 
                     LEFT JOIN tasks t ON n.task_id = t.id 
                     WHERE n.id = :note_id AND n.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $note_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'note' => $stmt->fetch()
                ];
            } else {
                return ['success' => false, 'message' => 'Nota no encontrada'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
}

// Verificar autenticación
checkLogin();

$notesManager = new NotesManager();
$user_id = $_SESSION['user_id'];

// API endpoints
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $result = $notesManager->getNote($user_id, $_GET['id']);
        } else {
            $task_id = $_GET['task_id'] ?? null;
            $result = $notesManager->getNotes($user_id, $task_id);
        }
        jsonResponse($result);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $notesManager->createNote($user_id, $input);
        jsonResponse($result);
        break;
        
    case 'PUT':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de nota requerido'], 400);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $notesManager->updateNote($user_id, $_GET['id'], $input);
        jsonResponse($result);
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de nota requerido'], 400);
        }
        $result = $notesManager->deleteNote($user_id, $_GET['id']);
        jsonResponse($result);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>