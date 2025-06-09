<?php
// =============================================
// CHAT_SYSTEM.PHP - Sistema de Mensajería Interna
// =============================================

require_once 'config.php';

// Verificar que el usuario esté logueado
session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'No autorizado'], 401);
}

class ChatSystem {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Enviar mensaje
    public function sendMessage($sender_id, $receiver_id, $message, $message_type = 'text', $file_path = null) {
        try {
            // Verificar que ambos usuarios existan y estén activos
            $checkUsers = "SELECT COUNT(*) as count FROM users WHERE id IN (:sender_id, :receiver_id) AND status = 'active'";
            $stmt = $this->conn->prepare($checkUsers);
            $stmt->execute([':sender_id' => $sender_id, ':receiver_id' => $receiver_id]);
            $result = $stmt->fetch();
            
            if ($result['count'] != 2) {
                return ['success' => false, 'message' => 'Usuario no válido'];
            }
            
            $sql = "INSERT INTO chat_messages (sender_id, receiver_id, message, message_type, file_path) 
                    VALUES (:sender_id, :receiver_id, :message, :message_type, :file_path)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':sender_id' => $sender_id,
                ':receiver_id' => $receiver_id,
                ':message' => trim($message),
                ':message_type' => $message_type,
                ':file_path' => $file_path
            ]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message_id' => $this->conn->lastInsertId(),
                    'message' => 'Mensaje enviado correctamente'
                ];
            } else {
                return ['success' => false, 'message' => 'Error al enviar mensaje'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Obtener conversación entre dos usuarios
    public function getConversation($user1_id, $user2_id, $limit = 50) {
        try {
            $sql = "SELECT cm.*, 
                           u1.full_name as sender_name, u1.username as sender_username,
                           u2.full_name as receiver_name, u2.username as receiver_username
                    FROM chat_messages cm
                    JOIN users u1 ON cm.sender_id = u1.id
                    LEFT JOIN users u2 ON cm.receiver_id = u2.id
                    WHERE (cm.sender_id = :user1_id AND cm.receiver_id = :user2_id)
                       OR (cm.sender_id = :user2_id AND cm.receiver_id = :user1_id)
                    ORDER BY cm.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user1_id', $user1_id, PDO::PARAM_INT);
            $stmt->bindParam(':user2_id', $user2_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return [
                'success' => true,
                'messages' => $messages
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Obtener lista de conversaciones del usuario
    public function getUserConversations($user_id) {
        try {
            $sql = "SELECT 
                        contact_id,
                        contact_name,
                        contact_username,
                        last_message_time,
                        last_message,
                        unread_count
                    FROM (
                        SELECT 
                            CASE 
                                WHEN cm.sender_id = :user_id THEN cm.receiver_id 
                                ELSE cm.sender_id 
                            END as contact_id,
                            CASE 
                                WHEN cm.sender_id = :user_id THEN u2.full_name 
                                ELSE u1.full_name 
                            END as contact_name,
                            CASE 
                                WHEN cm.sender_id = :user_id THEN u2.username 
                                ELSE u1.username 
                            END as contact_username,
                            cm.created_at as last_message_time,
                            cm.message as last_message,
                            CASE WHEN cm.receiver_id = :user_id AND cm.is_read = 0 THEN 1 ELSE 0 END as is_unread,
                            ROW_NUMBER() OVER (PARTITION BY 
                                CASE 
                                    WHEN cm.sender_id = :user_id THEN cm.receiver_id 
                                    ELSE cm.sender_id 
                                END 
                                ORDER BY cm.created_at DESC
                            ) as rn
                        FROM chat_messages cm
                        JOIN users u1 ON cm.sender_id = u1.id
                        JOIN users u2 ON cm.receiver_id = u2.id
                        WHERE (cm.sender_id = :user_id OR cm.receiver_id = :user_id)
                          AND u1.status = 'active' AND u2.status = 'active'
                    ) ranked_messages
                    LEFT JOIN (
                        SELECT 
                            CASE 
                                WHEN sender_id = :user_id THEN receiver_id 
                                ELSE sender_id 
                            END as contact_id,
                            COUNT(*) as unread_count
                        FROM chat_messages
                        WHERE receiver_id = :user_id AND is_read = 0
                        GROUP BY contact_id
                    ) unread_counts ON ranked_messages.contact_id = unread_counts.contact_id
                    WHERE rn = 1
                    ORDER BY last_message_time DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay conversaciones, obtener lista de usuarios disponibles para chat
            if (empty($conversations)) {
                $sql = "SELECT id as contact_id, full_name as contact_name, username as contact_username,
                               NULL as last_message_time, NULL as last_message, 0 as unread_count
                        FROM users 
                        WHERE id != :user_id AND status = 'active'
                        ORDER BY full_name";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':user_id' => $user_id]);
                $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return [
                'success' => true,
                'contacts' => $conversations
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Marcar mensajes como leídos
    public function markAsRead($user_id, $sender_id) {
        try {
            $sql = "UPDATE chat_messages SET is_read = 1 
                    WHERE receiver_id = :user_id AND sender_id = :sender_id AND is_read = 0";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([':user_id' => $user_id, ':sender_id' => $sender_id]);