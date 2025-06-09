<?php
require_once 'config.php';

class ReportsManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Progreso por cliente por semana/mes
    public function getClientProgress($user_id, $period = 'month', $start_date = null, $end_date = null) {
        try {
            // Definir fechas si no se proporcionan
            if (!$start_date || !$end_date) {
                if ($period === 'week') {
                    $start_date = date('Y-m-d', strtotime('monday this week'));
                    $end_date = date('Y-m-d', strtotime('sunday this week'));
                } else { // month
                    $start_date = date('Y-m-01');
                    $end_date = date('Y-m-t');
                }
            }
            
            $query = "SELECT 
                        c.id as client_id,
                        c.name as client_name,
                        c.company,
                        COUNT(t.id) as total_tasks,
                        COUNT(CASE WHEN t.status = 'done' THEN 1 END) as completed_tasks,
                        COUNT(CASE WHEN t.status = 'todo' THEN 1 END) as pending_tasks,
                        COUNT(CASE WHEN t.status = 'progress' THEN 1 END) as in_progress_tasks,
                        COUNT(CASE WHEN t.status = 'review' THEN 1 END) as in_review_tasks,
                        COUNT(CASE WHEN t.priority = 'high' THEN 1 END) as high_priority_tasks,
                        COUNT(CASE WHEN t.priority = 'medium' THEN 1 END) as medium_priority_tasks,
                        COUNT(CASE WHEN t.priority = 'low' THEN 1 END) as low_priority_tasks,
                        ROUND(
                            (COUNT(CASE WHEN t.status = 'done' THEN 1 END) * 100.0 / 
                            NULLIF(COUNT(t.id), 0)), 2
                        ) as completion_percentage,
                        AVG(CASE 
                            WHEN t.status = 'done' AND t.updated_at IS NOT NULL 
                            THEN DATEDIFF(t.updated_at, t.created_at) 
                        END) as avg_completion_days
                     FROM clients c 
                     LEFT JOIN tasks t ON c.id = t.client_id 
                        AND t.user_id = :user_id 
                        AND DATE(t.created_at) BETWEEN :start_date AND :end_date
                     WHERE c.user_id = :user_id 
                     GROUP BY c.id, c.name, c.company
                     ORDER BY total_tasks DESC, completion_percentage DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'period' => $period,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener progreso por cliente: ' . $e->getMessage()];
        }
    }
    
    // Tiempo promedio por tipo de tarea
    public function getTaskTypeAnalytics($user_id, $period = 'month') {
        try {
            $date_condition = '';
            if ($period === 'week') {
                $date_condition = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            } elseif ($period === 'month') {
                $date_condition = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            } elseif ($period === 'quarter') {
                $date_condition = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            }
            
            $query = "SELECT 
                        tt.title as template_name,
                        tt.category,
                        COUNT(t.id) as total_tasks,
                        COUNT(CASE WHEN t.status = 'done' THEN 1 END) as completed_tasks,
                        AVG(CASE 
                            WHEN t.status = 'done' AND t.updated_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) 
                        END) as avg_completion_hours,
                        AVG(CASE 
                            WHEN t.status = 'done' AND t.updated_at IS NOT NULL 
                            THEN DATEDIFF(t.updated_at, t.created_at) 
                        END) as avg_completion_days,
                        MIN(CASE 
                            WHEN t.status = 'done' AND t.updated_at IS NOT NULL 
                            THEN DATEDIFF(t.updated_at, t.created_at) 
                        END) as min_completion_days,
                        MAX(CASE 
                            WHEN t.status = 'done' AND t.updated_at IS NOT NULL 
                            THEN DATEDIFF(t.updated_at, t.created_at) 
                        END) as max_completion_days,
                        t.priority,
                        COUNT(CASE WHEN t.priority = 'high' THEN 1 END) as high_priority_count,
                        COUNT(CASE WHEN t.priority = 'medium' THEN 1 END) as medium_priority_count,
                        COUNT(CASE WHEN t.priority = 'low' THEN 1 END) as low_priority_count
                     FROM tasks t
                     LEFT JOIN task_templates tt ON t.title LIKE CONCAT('%', tt.title, '%')
                     WHERE t.user_id = :user_id $date_condition
                     GROUP BY COALESCE(tt.title, 'Tarea Personalizada'), tt.category, t.priority
                     ORDER BY total_tasks DESC, avg_completion_hours ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $results = $stmt->fetchAll();
            
            // Obtener también estadísticas generales
            $general_query = "SELECT 
                                COUNT(*) as total_tasks,
                                COUNT(CASE WHEN status = 'done' THEN 1 END) as completed_tasks,
                                COUNT(CASE WHEN status = 'todo' THEN 1 END) as pending_tasks,
                                COUNT(CASE WHEN status = 'progress' THEN 1 END) as in_progress_tasks,
                                COUNT(CASE WHEN status = 'review' THEN 1 END) as in_review_tasks,
                                AVG(CASE 
                                    WHEN status = 'done' AND updated_at IS NOT NULL 
                                    THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) 
                                END) as overall_avg_hours,
                                COUNT(CASE WHEN priority = 'high' THEN 1 END) as total_high_priority,
                                COUNT(CASE WHEN priority = 'medium' THEN 1 END) as total_medium_priority,
                                COUNT(CASE WHEN priority = 'low' THEN 1 END) as total_low_priority
                             FROM tasks 
                             WHERE user_id = :user_id $date_condition";
            
            $general_stmt = $this->conn->prepare($general_query);
            $general_stmt->bindParam(':user_id', $user_id);
            $general_stmt->execute();
            
            return [
                'success' => true,
                'task_types' => $results,
                'general_stats' => $general_stmt->fetch(),
                'period' => $period
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener analytics de tipos de tarea: ' . $e->getMessage()];
        }
    }
    
    // Productividad del equipo (para un usuario individual o equipo)
    public function getProductivityMetrics($user_id, $period = 'month') {
        try {
            $date_conditions = $this->getDateConditions($period);
            
            $query = "SELECT 
                        u.username,
                        u.full_name,
                        COUNT(t.id) as total_tasks,
                        COUNT(CASE WHEN t.status = 'done' THEN 1 END) as completed_tasks,
                        COUNT(CASE WHEN t.status = 'todo' THEN 1 END) as pending_tasks,
                        COUNT(CASE WHEN t.status = 'progress' THEN 1 END) as in_progress_tasks,
                        COUNT(CASE WHEN t.status = 'review' THEN 1 END) as in_review_tasks,
                        ROUND(
                            (COUNT(CASE WHEN t.status = 'done' THEN 1 END) * 100.0 / 
                            NULLIF(COUNT(t.id), 0)), 2
                        ) as completion_rate,
                        AVG(CASE 
                            WHEN t.status = 'done' AND t.updated_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) 
                        END) as avg_completion_hours,
                        COUNT(CASE WHEN t.priority = 'high' AND t.status = 'done' THEN 1 END) as high_priority_completed,
                        COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'done' THEN 1 END) as overdue_tasks,
                        COUNT(n.id) as total_notes,
                        COUNT(c.id) as total_clients
                     FROM users u
                     LEFT JOIN tasks t ON u.id = t.user_id {$date_conditions['task_condition']}
                     LEFT JOIN notes n ON u.id = n.user_id {$date_conditions['note_condition']}
                     LEFT JOIN clients c ON u.id = c.user_id
                     WHERE u.id = :user_id
                     GROUP BY u.id, u.username, u.full_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $productivity = $stmt->fetch();
            
            // Obtener tendencia semanal
            $trend_query = "SELECT 
                              YEARWEEK(created_at) as week_year,
                              WEEK(created_at) as week_num,
                              COUNT(id) as tasks_created,
                              COUNT(CASE WHEN status = 'done' THEN 1 END) as tasks_completed
                           FROM tasks 
                           WHERE user_id = :user_id 
                             AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                           GROUP BY YEARWEEK(created_at)
                           ORDER BY week_year";
            
            $trend_stmt = $this->conn->prepare($trend_query);
            $trend_stmt->bindParam(':user_id', $user_id);
            $trend_stmt->execute();
            
            return [
                'success' => true,
                'productivity' => $productivity,
                'weekly_trend' => $trend_stmt->fetchAll(),
                'period' => $period
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener métricas de productividad: ' . $e->getMessage()];
        }
    }
    
    // Dashboard de resumen ejecutivo
    public function getExecutiveDashboard($user_id) {
        try {
            // Métricas principales
            $main_metrics_query = "SELECT 
                                     COUNT(DISTINCT c.id) as total_clients,
                                     COUNT(t.id) as total_tasks,
                                     COUNT(CASE WHEN t.status = 'done' THEN 1 END) as completed_tasks,
                                     COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'done' THEN 1 END) as overdue_tasks,
                                     COUNT(CASE WHEN t.due_date = CURDATE() AND t.status != 'done' THEN 1 END) as due_today,
                                     COUNT(CASE WHEN t.priority = 'high' AND t.status != 'done' THEN 1 END) as high_priority_pending,
                                     COUNT(n.id) as total_notes
                                  FROM users u
                                  LEFT JOIN clients c ON u.id = c.user_id
                                  LEFT JOIN tasks t ON u.id = t.user_id
                                  LEFT JOIN notes n ON u.id = n.user_id
                                  WHERE u.id = :user_id";
            
            $main_stmt = $this->conn->prepare($main_metrics_query);
            $main_stmt->bindParam(':user_id', $user_id);
            $main_stmt->execute();
            
            // Actividad reciente (últimos 7 días)
            $recent_activity_query = "SELECT 
                                        DATE(created_at) as activity_date,
                                        COUNT(id) as tasks_created,
                                        'task' as activity_type
                                     FROM tasks 
                                     WHERE user_id = :user_id 
                                       AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                     GROUP BY DATE(created_at)
                                     UNION ALL
                                     SELECT 
                                        DATE(created_at) as activity_date,
                                        COUNT(id) as tasks_created,
                                        'client' as activity_type
                                     FROM clients 
                                     WHERE user_id = :user_id 
                                       AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                     GROUP BY DATE(created_at)
                                     ORDER BY activity_date DESC";
            
            $activity_stmt = $this->conn->prepare($recent_activity_query);
            $activity_stmt->bindParam(':user_id', $user_id);
            $activity_stmt->execute();
            
            // Top clientes por tareas
            $top_clients_query = "SELECT 
                                    c.name,
                                    c.company,
                                    COUNT(t.id) as task_count,
                                    COUNT(CASE WHEN t.status = 'done' THEN 1 END) as completed_count,
                                    ROUND(
                                        (COUNT(CASE WHEN t.status = 'done' THEN 1 END) * 100.0 / 
                                        NULLIF(COUNT(t.id), 0)), 2
                                    ) as completion_rate
                                 FROM clients c
                                 LEFT JOIN tasks t ON c.id = t.client_id
                                 WHERE c.user_id = :user_id
                                 GROUP BY c.id, c.name, c.company
                                 HAVING task_count > 0
                                 ORDER BY task_count DESC, completion_rate DESC
                                 LIMIT 5";
            
            $clients_stmt = $this->conn->prepare($top_clients_query);
            $clients_stmt->bindParam(':user_id', $user_id);
            $clients_stmt->execute();
            
            return [
                'success' => true,
                'main_metrics' => $main_stmt->fetch(),
                'recent_activity' => $activity_stmt->fetchAll(),
                'top_clients' => $clients_stmt->fetchAll()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener dashboard ejecutivo: ' . $e->getMessage()];
        }
    }
    
    // Generar datos para exportación
    public function generateExportData($user_id, $report_type, $format = 'array', $period = 'month') {
        try {
            $data = [];
            
            switch ($report_type) {
                case 'client_progress':
                    $result = $this->getClientProgress($user_id, $period);
                    $data = $result['data'];
                    break;
                    
                case 'task_analytics':
                    $result = $this->getTaskTypeAnalytics($user_id, $period);
                    $data = $result['task_types'];
                    break;
                    
                case 'productivity':
                    $result = $this->getProductivityMetrics($user_id, $period);
                    $data = [$result['productivity']];
                    break;
                    
                case 'executive_dashboard':
                    $result = $this->getExecutiveDashboard($user_id);
                    $data = [
                        'metrics' => $result['main_metrics'],
                        'top_clients' => $result['top_clients']
                    ];
                    break;
                    
                default:
                    throw new Exception('Tipo de reporte no válido');
            }
            
            if ($format === 'csv') {
                return $this->convertToCSV($data, $report_type);
            }
            
            return [
                'success' => true,
                'data' => $data,
                'report_type' => $report_type,
                'period' => $period,
                'generated_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al generar datos de exportación: ' . $e->getMessage()];
        }
    }
    
    // Convertir datos a CSV
    private function convertToCSV($data, $report_type) {
        $csv = '';
        
        if (empty($data)) {
            return $csv;
        }
        
        // Headers específicos por tipo de reporte
        switch ($report_type) {
            case 'client_progress':
                $csv .= "Cliente,Empresa,Total Tareas,Completadas,Pendientes,En Progreso,En Revisión,% Completado,Días Promedio\n";
                foreach ($data as $row) {
                    $csv .= implode(',', [
                        $row['client_name'],
                        $row['company'] ?? '',
                        $row['total_tasks'],
                        $row['completed_tasks'],
                        $row['pending_tasks'],
                        $row['in_progress_tasks'],
                        $row['in_review_tasks'],
                        $row['completion_percentage'] ?? 0,
                        round($row['avg_completion_days'] ?? 0, 2)
                    ]) . "\n";
                }
                break;
                
            case 'task_analytics':
                $csv .= "Tipo de Tarea,Categoría,Total,Completadas,Horas Promedio,Días Promedio\n";
                foreach ($data as $row) {
                    $csv .= implode(',', [
                        $row['template_name'] ?? 'N/A',
                        $row['category'] ?? 'General',
                        $row['total_tasks'],
                        $row['completed_tasks'],
                        round($row['avg_completion_hours'] ?? 0, 2),
                        round($row['avg_completion_days'] ?? 0, 2)
                    ]) . "\n";
                }
                break;
        }
        
        return $csv;
    }
    
    // Función auxiliar para condiciones de fecha
    private function getDateConditions($period) {
        switch ($period) {
            case 'week':
                return [
                    'task_condition' => 'AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
                    'note_condition' => 'AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)'
                ];
            case 'month':
                return [
                    'task_condition' => 'AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
                    'note_condition' => 'AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)'
                ];
            case 'quarter':
                return [
                    'task_condition' => 'AND t.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)',
                    'note_condition' => 'AND n.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)'
                ];
            default:
                return [
                    'task_condition' => '',
                    'note_condition' => ''
                ];
        }
    }
}

// Verificar autenticación
checkLogin();

$reportsManager = new ReportsManager();
$user_id = $_SESSION['user_id'];

// API endpoints
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        $period = $_GET['period'] ?? 'month';
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;
        
        switch ($action) {
            case 'client_progress':
                $result = $reportsManager->getClientProgress($user_id, $period, $start_date, $end_date);
                break;
                
            case 'task_analytics':
                $result = $reportsManager->getTaskTypeAnalytics($user_id, $period);
                break;
                
            case 'productivity':
                $result = $reportsManager->getProductivityMetrics($user_id, $period);
                break;
                
            case 'executive_dashboard':
                $result = $reportsManager->getExecutiveDashboard($user_id);
                break;
                
            case 'export':
                $report_type = $_GET['report_type'] ?? '';
                $format = $_GET['format'] ?? 'json';
                
                if ($format === 'csv') {
                    $result = $reportsManager->generateExportData($user_id, $report_type, 'csv', $period);
                    if ($result['success']) {
                        header('Content-Type: text/csv');
                        header('Content-Disposition: attachment; filename="reporte_' . $report_type . '_' . date('Y-m-d') . '.csv"');
                        echo $result['data'];
                        exit;
                    }
                } else {
                    $result = $reportsManager->generateExportData($user_id, $report_type, 'array', $period);
                }
                break;
                
            default:
                $result = ['error' => 'Acción no válida'];
        }
        
        jsonResponse($result);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>