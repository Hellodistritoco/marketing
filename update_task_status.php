<?php
require_once 'config.php';
verificarLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['id']) && isset($input['estado'])) {
        $id = (int)$input['id'];
        $estado = $input['estado'];
        
        $estados_validos = ['Por Hacer', 'En Progreso', 'En Revisión', 'Completado'];
        
        if (in_array($estado, $estados_validos)) {
            try {
                $pdo = conectarDB();
                
                // Obtener datos de la tarea antes de actualizar
                $stmt = $pdo->prepare("
                    SELECT t.*, c.nombre_cliente 
                    FROM tareas t 
                    LEFT JOIN clientes c ON t.cliente_id = c.id 
                    WHERE t.id = ?
                ");
                $stmt->execute([$id]);
                $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$tarea) {
                    echo json_encode(['success' => false, 'message' => 'Tarea no encontrada']);
                    exit;
                }
                
                // Actualizar estado de la tarea
                $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
                $stmt->execute([$estado, $id]);
                
                // Intentar actualizar Google Calendar si está conectado
                $calendar_updated = false;
                $calendar_message = '';
                
                if (function_exists('isGoogleCalendarConnected') && isGoogleCalendarConnected()) {
                    try {
                        require_once 'google_calendar_config.php';
                        
                        // Buscar evento de Google Calendar asociado
                        $stmt = $pdo->prepare("SELECT google_event_id FROM tarea_calendar_events WHERE tarea_id = ?");
                        $stmt->execute([$id]);
                        $calendar_event = $stmt->fetch();
                        
                        if ($calendar_event && $calendar_event['google_event_id']) {
                            $access_token = getValidGoogleToken();
                            if ($access_token) {
                                // Actualizar datos de la tarea con el nuevo estado
                                $tarea['estado'] = $estado;
                                
                                $updated_event = updateGoogleCalendarEvent(
                                    $access_token, 
                                    $calendar_event['google_event_id'], 
                                    $tarea
                                );
                                
                                if ($updated_event) {
                                    $calendar_updated = true;
                                    $calendar_message = ' y sincronizado con Google Calendar';
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // No fallar si hay error en Calendar
                        $calendar_message = ' (error en sincronización Calendar)';
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Estado actualizado correctamente' . $calendar_message,
                    'nuevo_estado' => $estado,
                    'calendar_updated' => $calendar_updated,
                    'tarea_nombre' => $tarea['nombre_tarea']
                ]);
                
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