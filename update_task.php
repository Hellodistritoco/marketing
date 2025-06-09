<?php
require_once 'config.php';
verificarLogin();

// Este archivo maneja las actualizaciones de estado de las tareas vía AJAX
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['id']) && isset($input['estado'])) {
        $id = $input['id'];
        $estado = $input['estado'];
        
        $estados_validos = ['Por Hacer', 'En Progreso', 'En Revisión', 'Completado'];
        
        if (in_array($estado, $estados_validos)) {
            try {
                $pdo = conectarDB();
                $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
                $stmt->execute([$estado, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>