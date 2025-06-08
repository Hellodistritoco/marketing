<?php
require_once 'config.php';

class TaskTemplateManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener todas las plantillas del usuario
    public function getTemplates($user_id) {
        try {
            $query = "SELECT * FROM task_templates WHERE user_id = :user_id AND is_active = 1 ORDER BY category, title";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return [
                'success' => true,
                'templates' => $stmt->fetchAll()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener plantillas: ' . $e->getMessage()];
        }
    }
    
    // Crear tareas desde plantillas para un cliente específico
    public function createTasksFromTemplates($user_id, $client_id, $template_ids) {
        try {
            $this->conn->beginTransaction();
            
            $created_tasks = [];
            
            foreach ($template_ids as $template_id) {
                // Obtener la plantilla
                $query = "SELECT * FROM task_templates WHERE id = :template_id AND user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':template_id', $template_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $template = $stmt->fetch();
                    
                    // Crear tarea desde plantilla
                    $insert_query = "INSERT INTO tasks (user_id, client_id, title, description, priority, status) 
                                   VALUES (:user_id, :client_id, :title, :description, :priority, 'todo')";
                    $insert_stmt = $this->conn->prepare($insert_query);
                    $insert_stmt->bindParam(':user_id', $user_id);
                    $insert_stmt->bindParam(':client_id', $client_id);
                    $insert_stmt->bindParam(':title', $template['title']);
                    $insert_stmt->bindParam(':description', $template['description']);
                    $insert_stmt->bindParam(':priority', $template['priority']);
                    
                    if ($insert_stmt->execute()) {
                        $created_tasks[] = [
                            'id' => $this->conn->lastInsertId(),
                            'title' => $template['title']
                        ];
                    }
                }
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Tareas creadas desde plantillas correctamente',
                'created_tasks' => $created_tasks
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error al crear tareas: ' . $e->getMessage()];
        }
    }
    
    // Crear nueva plantilla
    public function createTemplate($user_id, $data) {
        try {
            $query = "INSERT INTO task_templates (user_id, title, description, priority, category) 
                     VALUES (:user_id, :title, :description, :priority, :category)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':category', $data['category']);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Plantilla creada correctamente',
                    'template_id' => $this->conn->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear plantilla'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Actualizar plantilla
    public function updateTemplate($user_id, $template_id, $data) {
        try {
            $query = "UPDATE task_templates SET title = :title, description = :description, 
                     priority = :priority, category = :category 
                     WHERE id = :template_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':template_id', $template_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':category', $data['category']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Plantilla actualizada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar plantilla'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Eliminar plantilla (soft delete)
    public function deleteTemplate($user_id, $template_id) {
        try {
            $query = "UPDATE task_templates SET is_active = 0 WHERE id = :template_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':template_id', $template_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Plantilla eliminada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar plantilla'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
    
    // Obtener plantillas agrupadas por categoría
    public function getTemplatesByCategory($user_id) {
        try {
            $query = "SELECT * FROM task_templates WHERE user_id = :user_id AND is_active = 1 ORDER BY category, title";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $templates = $stmt->fetchAll();
            $grouped = [];
            
            foreach ($templates as $template) {
                $category = $template['category'] ?: 'General';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $template;
            }
            
            return [
                'success' => true,
                'templates' => $grouped
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener plantillas: ' . $e->getMessage()];
        }
    }
}

// Verificar autenticación
checkLogin();

$templateManager = new TaskTemplateManager();
$user_id = $_SESSION['user_id'];

// API endpoints
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'grouped') {
            $result = $templateManager->getTemplatesByCategory($user_id);
        } else {
            $result = $templateManager->getTemplates($user_id);
        }
        jsonResponse($result);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($_GET['action']) && $_GET['action'] === 'create_tasks') {
            // Crear tareas desde plantillas
            $result = $templateManager->createTasksFromTemplates(
                $user_id,
                $input['client_id'],
                $input['template_ids']
            );
        } else {
            // Crear nueva plantilla
            $result = $templateManager->createTemplate($user_id, $input);
        }
        jsonResponse($result);
        break;
        
    case 'PUT':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de plantilla requerido'], 400);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $templateManager->updateTemplate($user_id, $_GET['id'], $input);
        jsonResponse($result);
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'ID de plantilla requerido'], 400);
        }
        $result = $templateManager->deleteTemplate($user_id, $_GET['id']);
        jsonResponse($result);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>