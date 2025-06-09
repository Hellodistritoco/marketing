<?php
// =============================================
// TEAM_MANAGEMENT.PHP - Sistema de Gestión de Equipos
// =============================================

require_once 'config.php';

// Verificar que el usuario esté logueado
session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

class TeamManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Verificar permisos del usuario
    public function hasPermission($user_id, $permission_name) {
        $sql = "SELECT COUNT(*) as count 
                FROM users u 
                JOIN role_permissions rp ON u.role = rp.role 
                JOIN permissions p ON rp.permission_id = p.id 
                WHERE u.id = :user_id AND p.permission_name = :permission_name AND u.status = 'active'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id, ':permission_name' => $permission_name]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    // Obtener todos los usuarios con filtros
    public function getUsers($role = null, $status = 'active') {
        $whereConditions = [];
        $params = [];
        
        if ($status) {
            $whereConditions[] = "u.status = :status";
            $params[':status'] = $status;
        }
        
        if ($role) {
            $whereConditions[] = "u.role = :role";
            $params[':role'] = $role;
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT ca.cliente_id) as assigned_clients,
                       COUNT(DISTINCT t.id) as total_tasks
                FROM users u 
                LEFT JOIN client_assignments ca ON u.id = ca.operario_id AND ca.is_active = 1
                LEFT JOIN tareas t ON u.id = t.user_id
                $whereClause
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Crear nuevo usuario
    public function createUser($data) {
        try {
            // Verificar si el usuario ya existe
            $checkSql = "SELECT id FROM users WHERE username = :username OR email = :email";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([':username' => $data['username'], ':email' => $data['email']]);
            
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El usuario o email ya existe'];
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, full_name, email, password_hash, role, phone, department, hire_date, status) 
                    VALUES (:username, :full_name, :email, :password_hash, :role, :phone, :department, :hire_date, 'active')";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':username' => $data['username'],
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':password_hash' => $hashedPassword,
                ':role' => $data['role'],
                ':phone' => $data['phone'] ?? null,
                ':department' => $data['department'] ?? null,
                ':hire_date' => $data['hire_date'] ?? null
            ]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'Usuario creado correctamente',
                    'user_id' => $this->conn->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Asignar cliente a operario
    public function assignClientToOperario($cliente_id, $operario_id, $assigned_by) {
        try {
            // Verificar que el operario tenga el rol correcto
            $checkRole = "SELECT role FROM users WHERE id = :operario_id AND status = 'active'";
            $stmt = $this->conn->prepare($checkRole);
            $stmt->execute([':operario_id' => $operario_id]);
            $user = $stmt->fetch();
            
            if (!$user || !in_array($user['role'], ['operario', 'manager'])) {
                return ['success' => false, 'message' => 'Solo se puede asignar a operarios o managers activos'];
            }
            
            // Desactivar asignaciones anteriores para este cliente
            $deactivateSql = "UPDATE client_assignments SET is_active = 0 WHERE cliente_id = :cliente_id AND is_active = 1";
            $this->conn->prepare($deactivateSql)->execute([':cliente_id' => $cliente_id]);
            
            // Crear nueva asignación
            $sql = "INSERT INTO client_assignments (cliente_id, operario_id, assigned_by) 
                    VALUES (:cliente_id, :operario_id, :assigned_by)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':cliente_id' => $cliente_id,
                ':operario_id' => $operario_id,
                ':assigned_by' => $assigned_by
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Cliente asignado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al asignar cliente'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Obtener asignaciones
    public function getAssignments() {
        $sql = "SELECT ca.*, 
                       c.nombre_cliente, c.responsable as client_company,
                       u1.full_name as operario_name, u1.role as operario_role,
                       u2.full_name as assigned_by_name
                FROM client_assignments ca
                JOIN clientes c ON ca.cliente_id = c.id
                JOIN users u1 ON ca.operario_id = u1.id
                JOIN users u2 ON ca.assigned_by = u2.id
                WHERE ca.is_active = 1
                ORDER BY ca.assigned_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener clientes asignados a un operario
    public function getAssignedClients($operario_id) {
        $sql = "SELECT c.*, ca.assigned_at, u.full_name as assigned_by_name
                FROM clientes c
                JOIN client_assignments ca ON c.id = ca.cliente_id
                JOIN users u ON ca.assigned_by = u.id
                WHERE ca.operario_id = :operario_id AND ca.is_active = 1
                ORDER BY ca.assigned_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':operario_id' => $operario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener permisos por rol
    public function getRolePermissions($role = null) {
        if ($role) {
            $sql = "SELECT p.id, p.permission_name, p.description,
                           CASE WHEN rp.role IS NOT NULL THEN 1 ELSE 0 END as granted
                    FROM permissions p
                    LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role = :role
                    ORDER BY p.permission_name";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':role' => $role]);
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'permissions' => array_filter($permissions, function($p) { return $p['granted']; })
            ];
        } else {
            $sql = "SELECT * FROM permissions ORDER BY permission_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return [
                'success' => true,
                'permissions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        }
    }
    
    // Actualizar permiso de rol
    public function updateRolePermission($role, $permission_id, $granted) {
        try {
            if ($granted) {
                // Agregar permiso
                $sql = "INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (:role, :permission_id)";
            } else {
                // Quitar permiso
                $sql = "DELETE FROM role_permissions WHERE role = :role AND permission_id = :permission_id";
            }
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([':role' => $role, ':permission_id' => $permission_id]);
            
            return [
                'success' => $result,
                'message' => $granted ? 'Permiso otorgado' : 'Permiso revocado'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Obtener tareas filtradas por permisos del usuario
    public function getTasksForUser($user_id, $user_role, $client_id = null) {
        $whereConditions = [];
        $params = [];
        
        switch ($user_role) {
            case 'admin':
                // Admin ve todas las tareas
                if ($client_id) {
                    $whereConditions[] = "t.cliente_id = :client_id";
                    $params[':client_id'] = $client_id;
                }
                break;
                
            case 'manager':
                // Manager ve todas las tareas pero puede filtrar por cliente
                if ($client_id) {
                    $whereConditions[] = "t.cliente_id = :client_id";
                    $params[':client_id'] = $client_id;
                }
                break;
                
            case 'operario':
                // Operario solo ve tareas de sus clientes asignados
                $whereConditions[] = "t.cliente_id IN (
                    SELECT cliente_id FROM client_assignments 
                    WHERE operario_id = :user_id AND is_active = 1
                )";
                $params[':user_id'] = $user_id;
                
                if ($client_id) {
                    $whereConditions[] = "t.cliente_id = :client_id";
                    $params[':client_id'] = $client_id;
                }
                break;
                
            case 'cliente':
                // Cliente solo ve sus propias tareas
                $whereConditions[] = "t.cliente_id = (
                    SELECT id FROM clientes WHERE email = (
                        SELECT email FROM users WHERE id = :user_id
                    )
                )";
                $params[':user_id'] = $user_id;
                break;
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        $sql = "SELECT t.*, c.nombre_cliente, u.full_name as assigned_to
                FROM tareas t
                LEFT JOIN clientes c ON t.cliente_id = c.id
                LEFT JOIN users u ON t.user_id = u.id
                $whereClause
                ORDER BY t.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Instanciar el manager
$teamManager = new TeamManager();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Procesar peticiones
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_users':
        $role = $_GET['role'] ?? null;
        $status = $_GET['status'] ?? 'active';
        $users = $teamManager->getUsers($role, $status);
        jsonResponse(['success' => true, 'users' => $users]);
        break;
        
    case 'create_user':
        // Solo admin puede crear usuarios
        if ($user_role !== 'admin') {
            jsonResponse(['error' => 'Sin permisos'], 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $teamManager->createUser($input);
        jsonResponse($result);
        break;
        
    case 'assign_client':
        // Solo admin y manager pueden asignar
        if (!in_array($user_role, ['admin', 'manager'])) {
            jsonResponse(['error' => 'Sin permisos'], 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $teamManager->assignClientToOperario(
            $input['cliente_id'], 
            $input['operario_id'], 
            $user_id
        );
        jsonResponse($result);
        break;
        
    case 'get_assignments':
        $assignments = $teamManager->getAssignments();
        jsonResponse(['success' => true, 'assignments' => $assignments]);
        break;
        
    case 'get_permissions':
        $permissions = $teamManager->getRolePermissions();
        jsonResponse($permissions);
        break;
        
    case 'get_role_permissions':
        $role = $_GET['role'] ?? null;
        if (!$role) {
            jsonResponse(['error' => 'Rol requerido'], 400);
        }
        
        $permissions = $teamManager->getRolePermissions($role);
        // Retornar solo los IDs de permisos otorgados
        $granted_permissions = array_column($permissions['permissions'], 'id');
        jsonResponse(['success' => true, 'permissions' => $granted_permissions]);
        break;
        
    case 'update_role_permission':
        // Solo admin puede modificar permisos
        if ($user_role !== 'admin') {
            jsonResponse(['error' => 'Sin permisos'], 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $teamManager->updateRolePermission(
            $input['role'],
            $input['permission_id'],
            $input['granted']
        );
        jsonResponse($result);
        break;
        
    case 'get_tasks':
        $client_id = $_GET['client_id'] ?? null;
        $tasks = $teamManager->getTasksForUser($user_id, $user_role, $client_id);
        jsonResponse(['success' => true, 'tasks' => $tasks]);
        break;
        
    default:
        jsonResponse(['error' => 'Acción no válida'], 400);
}
?>