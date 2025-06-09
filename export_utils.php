<?php
require_once 'config.php';

class ExportUtils {
    
    // Generar PDF usando TCPDF o similar
    public static function generatePDF($data, $reportType, $title = 'Reporte') {
        // Esta función requeriría una librería como TCPDF
        // Para simplicidad, generamos HTML que puede convertirse a PDF
        
        $html = self::generateHTMLReport($data, $reportType, $title);
        
        // Si tienes TCPDF instalado, descomenta:
        /*
        require_once('tcpdf/tcpdf.php');
        
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('reporte.pdf', 'S');
        */
        
        return $html;
    }
    
    // Generar HTML para reportes
    public static function generateHTMLReport($data, $reportType, $title) {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; color: #667eea; font-weight: bold; }
                .report-title { font-size: 20px; margin: 10px 0; }
                .date { color: #666; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0;
                th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
                th { background-color: #667eea; color: white; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .metric-box { display: inline-block; margin: 10px; padding: 15px; 
                             background: #f8f9fa; border-radius: 8px; text-align: center; }
                .metric-value { font-size: 24px; font-weight: bold; color: #667eea; }
                .metric-label { font-size: 12px; color: #666; }
                .progress-bar { width: 100px; height: 20px; background: #e1e5e9; 
                               border-radius: 10px; overflow: hidden; display: inline-block; }
                .progress-fill { height: 100%; background: #667eea; }
                .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>📊 Marketing Kanban</div>
                <div class='report-title'>{$title}</div>
                <div class='date'>Generado el: " . date('d/m/Y H:i:s') . "</div>
            </div>";

        switch ($reportType) {
            case 'client_progress':
                $html .= self::generateClientProgressHTML($data);
                break;
            case 'task_analytics':
                $html .= self::generateTaskAnalyticsHTML($data);
                break;
            case 'productivity':
                $html .= self::generateProductivityHTML($data);
                break;
            case 'executive_dashboard':
                $html .= self::generateExecutiveDashboardHTML($data);
                break;
        }

        $html .= "
        </body>
        </html>";

        return $html;
    }
    
    // HTML para progreso por cliente
    private static function generateClientProgressHTML($data) {
        $html = "<h2>📈 Progreso por Cliente</h2>";
        
        if (empty($data)) {
            return $html . "<p>No hay datos disponibles para mostrar.</p>";
        }

        $html .= "
        <div class='summary'>
            <h3>Resumen</h3>
            <div class='metric-box'>
                <div class='metric-value'>" . count($data) . "</div>
                <div class='metric-label'>Total Clientes</div>
            </div>";

        $totalTasks = array_sum(array_column($data, 'total_tasks'));
        $totalCompleted = array_sum(array_column($data, 'completed_tasks'));
        $avgCompletion = $totalTasks > 0 ? round(($totalCompleted / $totalTasks) * 100, 2) : 0;

        $html .= "
            <div class='metric-box'>
                <div class='metric-value'>{$totalTasks}</div>
                <div class='metric-label'>Total Tareas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>{$avgCompletion}%</div>
                <div class='metric-label'>Promedio Completado</div>
            </div>
        </div>";

        $html .= "
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Empresa</th>
                    <th>Total Tareas</th>
                    <th>Completadas</th>
                    <th>Pendientes</th>
                    <th>En Progreso</th>
                    <th>En Revisión</th>
                    <th>% Completado</th>
                    <th>Progreso Visual</th>
                </tr>
            </thead>
            <tbody>";

        foreach ($data as $client) {
            $completion = $client['completion_percentage'] ?? 0;
            $html .= "
                <tr>
                    <td><strong>" . htmlspecialchars($client['client_name']) . "</strong></td>
                    <td>" . htmlspecialchars($client['company'] ?? 'N/A') . "</td>
                    <td>{$client['total_tasks']}</td>
                    <td>{$client['completed_tasks']}</td>
                    <td>{$client['pending_tasks']}</td>
                    <td>{$client['in_progress_tasks']}</td>
                    <td>{$client['in_review_tasks']}</td>
                    <td><strong>{$completion}%</strong></td>
                    <td>
                        <div class='progress-bar'>
                            <div class='progress-fill' style='width: {$completion}%'></div>
                        </div>
                    </td>
                </tr>";
        }

        $html .= "</tbody></table>";
        return $html;
    }

    // HTML para analytics de tareas
    private static function generateTaskAnalyticsHTML($data) {
        $html = "<h2>🎯 Analytics de Tipos de Tarea</h2>";
        
        if (empty($data['task_types'])) {
            return $html . "<p>No hay datos disponibles para mostrar.</p>";
        }

        $generalStats = $data['general_stats'] ?? [];
        
        $html .= "
        <div class='summary'>
            <h3>Estadísticas Generales</h3>
            <div class='metric-box'>
                <div class='metric-value'>" . ($generalStats['total_tasks'] ?? 0) . "</div>
                <div class='metric-label'>Total Tareas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($generalStats['completed_tasks'] ?? 0) . "</div>
                <div class='metric-label'>Completadas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . round($generalStats['overall_avg_hours'] ?? 0) . "h</div>
                <div class='metric-label'>Promedio General</div>
            </div>
        </div>";

        $html .= "
        <table>
            <thead>
                <tr>
                    <th>Tipo de Tarea</th>
                    <th>Categoría</th>
                    <th>Total</th>
                    <th>Completadas</th>
                    <th>Tiempo Promedio</th>
                    <th>Eficiencia</th>
                    <th>Prioridad Alta</th>
                    <th>Prioridad Media</th>
                    <th>Prioridad Baja</th>
                </tr>
            </thead>
            <tbody>";

        foreach ($data['task_types'] as $taskType) {
            $efficiency = $taskType['total_tasks'] > 0 ? 
                round(($taskType['completed_tasks'] / $taskType['total_tasks']) * 100) : 0;
            
            $html .= "
                <tr>
                    <td><strong>" . htmlspecialchars($taskType['template_name'] ?? 'Personalizada') . "</strong></td>
                    <td>" . htmlspecialchars($taskType['category'] ?? 'General') . "</td>
                    <td>{$taskType['total_tasks']}</td>
                    <td>{$taskType['completed_tasks']}</td>
                    <td>" . round($taskType['avg_completion_hours'] ?? 0) . "h</td>
                    <td><strong>{$efficiency}%</strong></td>
                    <td>{$taskType['high_priority_count']}</td>
                    <td>{$taskType['medium_priority_count']}</td>
                    <td>{$taskType['low_priority_count']}</td>
                </tr>";
        }

        $html .= "</tbody></table>";
        return $html;
    }

    // HTML para productividad
    private static function generateProductivityHTML($data) {
        $html = "<h2>⚡ Métricas de Productividad</h2>";
        
        if (empty($data['productivity'])) {
            return $html . "<p>No hay datos disponibles para mostrar.</p>";
        }

        $productivity = $data['productivity'];
        
        $html .= "
        <div class='summary'>
            <h3>Métricas Principales</h3>
            <div class='metric-box'>
                <div class='metric-value'>" . round($productivity['completion_rate'] ?? 0) . "%</div>
                <div class='metric-label'>Tasa de Completado</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . round($productivity['avg_completion_hours'] ?? 0) . "h</div>
                <div class='metric-label'>Tiempo Promedio</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($productivity['high_priority_completed'] ?? 0) . "</div>
                <div class='metric-label'>Alta Prioridad Completadas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($productivity['overdue_tasks'] ?? 0) . "</div>
                <div class='metric-label'>Tareas Vencidas</div>
            </div>
        </div>";

        $html .= "
        <table>
            <thead>
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Total de Tareas</strong></td>
                    <td>" . ($productivity['total_tasks'] ?? 0) . "</td>
                    <td>Tareas totales en el período</td>
                </tr>
                <tr>
                    <td><strong>Tareas Completadas</strong></td>
                    <td>" . ($productivity['completed_tasks'] ?? 0) . "</td>
                    <td>Tareas finalizadas exitosamente</td>
                </tr>
                <tr>
                    <td><strong>Tareas Pendientes</strong></td>
                    <td>" . ($productivity['pending_tasks'] ?? 0) . "</td>
                    <td>Tareas por iniciar</td>
                </tr>
                <tr>
                    <td><strong>Tareas en Progreso</strong></td>
                    <td>" . ($productivity['in_progress_tasks'] ?? 0) . "</td>
                    <td>Tareas actualmente en desarrollo</td>
                </tr>
                <tr>
                    <td><strong>Total de Notas</strong></td>
                    <td>" . ($productivity['total_notes'] ?? 0) . "</td>
                    <td>Notas creadas en el período</td>
                </tr>
                <tr>
                    <td><strong>Total de Clientes</strong></td>
                    <td>" . ($productivity['total_clients'] ?? 0) . "</td>
                    <td>Clientes gestionados</td>
                </tr>
            </tbody>
        </table>";

        // Tendencia semanal si está disponible
        if (!empty($data['weekly_trend'])) {
            $html .= "<h3>📈 Tendencia Semanal</h3>";
            $html .= "
            <table>
                <thead>
                    <tr>
                        <th>Semana</th>
                        <th>Tareas Creadas</th>
                        <th>Tareas Completadas</th>
                        <th>Eficiencia</th>
                    </tr>
                </thead>
                <tbody>";

            foreach ($data['weekly_trend'] as $week) {
                $efficiency = $week['tasks_created'] > 0 ? 
                    round(($week['tasks_completed'] / $week['tasks_created']) * 100) : 0;
                
                $html .= "
                    <tr>
                        <td>Semana {$week['week_num']}</td>
                        <td>{$week['tasks_created']}</td>
                        <td>{$week['tasks_completed']}</td>
                        <td>{$efficiency}%</td>
                    </tr>";
            }

            $html .= "</tbody></table>";
        }

        return $html;
    }

    // HTML para dashboard ejecutivo
    private static function generateExecutiveDashboardHTML($data) {
        $html = "<h2>📊 Dashboard Ejecutivo</h2>";
        
        if (empty($data['metrics'])) {
            return $html . "<p>No hay datos disponibles para mostrar.</p>";
        }

        $metrics = $data['metrics'];
        
        $html .= "
        <div class='summary'>
            <h3>Métricas Principales</h3>
            <div class='metric-box'>
                <div class='metric-value'>" . ($metrics['total_clients'] ?? 0) . "</div>
                <div class='metric-label'>Total Clientes</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($metrics['total_tasks'] ?? 0) . "</div>
                <div class='metric-label'>Total Tareas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($metrics['completed_tasks'] ?? 0) . "</div>
                <div class='metric-label'>Completadas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($metrics['overdue_tasks'] ?? 0) . "</div>
                <div class='metric-label'>Vencidas</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($metrics['due_today'] ?? 0) . "</div>
                <div class='metric-label'>Vencen Hoy</div>
            </div>
            <div class='metric-box'>
                <div class='metric-value'>" . ($metrics['total_notes'] ?? 0) . "</div>
                <div class='metric-label'>Total Notas</div>
            </div>
        </div>";

        // Top clientes si están disponibles
        if (!empty($data['top_clients'])) {
            $html .= "<h3>🏆 Top Clientes por Actividad</h3>";
            $html .= "
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Total Tareas</th>
                        <th>Completadas</th>
                        <th>Tasa de Completado</th>
                        <th>Progreso</th>
                    </tr>
                </thead>
                <tbody>";

            foreach ($data['top_clients'] as $client) {
                $completion = $client['completion_rate'] ?? 0;
                $html .= "
                    <tr>
                        <td><strong>" . htmlspecialchars($client['name']) . "</strong></td>
                        <td>" . htmlspecialchars($client['company'] ?? 'N/A') . "</td>
                        <td>{$client['task_count']}</td>
                        <td>{$client['completed_count']}</td>
                        <td><strong>{$completion}%</strong></td>
                        <td>
                            <div class='progress-bar'>
                                <div class='progress-fill' style='width: {$completion}%'></div>
                            </div>
                        </td>
                    </tr>";
            }

            $html .= "</tbody></table>";
        }

        return $html;
    }

    // Generar Excel usando PhpSpreadsheet o CSV mejorado
    public static function generateExcel($data, $reportType, $filename = 'reporte.csv') {
        // Para simplicidad, generamos CSV mejorado
        // En producción, se podría usar PhpSpreadsheet para Excel real
        
        $csv = "\xEF\xBB\xBF"; // BOM para UTF-8
        
        switch ($reportType) {
            case 'client_progress':
                $csv .= self::generateClientProgressCSV($data);
                break;
            case 'task_analytics':
                $csv .= self::generateTaskAnalyticsCSV($data);
                break;
            case 'productivity':
                $csv .= self::generateProductivityCSV($data);
                break;
            case 'executive_dashboard':
                $csv .= self::generateExecutiveDashboardCSV($data);
                break;
        }

        return $csv;
    }

    // CSV para progreso por cliente
    private static function generateClientProgressCSV($data) {
        $csv = "REPORTE DE PROGRESO POR CLIENTE\n";
        $csv .= "Generado el: " . date('d/m/Y H:i:s') . "\n\n";
        
        $csv .= "Cliente,Empresa,Total Tareas,Completadas,Pendientes,En Progreso,En Revision,Porcentaje Completado,Dias Promedio Completado\n";
        
        foreach ($data as $client) {
            $csv .= '"' . str_replace('"', '""', $client['client_name']) . '",';
            $csv .= '"' . str_replace('"', '""', $client['company'] ?? '') . '",';
            $csv .= $client['total_tasks'] . ',';
            $csv .= $client['completed_tasks'] . ',';
            $csv .= $client['pending_tasks'] . ',';
            $csv .= $client['in_progress_tasks'] . ',';
            $csv .= $client['in_review_tasks'] . ',';
            $csv .= ($client['completion_percentage'] ?? 0) . '%,';
            $csv .= round($client['avg_completion_days'] ?? 0, 2) . "\n";
        }

        return $csv;
    }

    // CSV para analytics de tareas
    private static function generateTaskAnalyticsCSV($data) {
        $csv = "REPORTE DE ANALYTICS DE TAREAS\n";
        $csv .= "Generado el: " . date('d/m/Y H:i:s') . "\n\n";
        
        // Estadísticas generales
        if (!empty($data['general_stats'])) {
            $stats = $data['general_stats'];
            $csv .= "ESTADISTICAS GENERALES\n";
            $csv .= "Total Tareas," . ($stats['total_tasks'] ?? 0) . "\n";
            $csv .= "Completadas," . ($stats['completed_tasks'] ?? 0) . "\n";
            $csv .= "Pendientes," . ($stats['pending_tasks'] ?? 0) . "\n";
            $csv .= "En Progreso," . ($stats['in_progress_tasks'] ?? 0) . "\n";
            $csv .= "Promedio Horas," . round($stats['overall_avg_hours'] ?? 0, 2) . "\n\n";
        }
        
        $csv .= "DETALLE POR TIPO DE TAREA\n";
        $csv .= "Tipo Tarea,Categoria,Total,Completadas,Horas Promedio,Dias Promedio,Alta Prioridad,Media Prioridad,Baja Prioridad\n";
        
        foreach ($data['task_types'] as $taskType) {
            $csv .= '"' . str_replace('"', '""', $taskType['template_name'] ?? 'Personalizada') . '",';
            $csv .= '"' . str_replace('"', '""', $taskType['category'] ?? 'General') . '",';
            $csv .= $taskType['total_tasks'] . ',';
            $csv .= $taskType['completed_tasks'] . ',';
            $csv .= round($taskType['avg_completion_hours'] ?? 0, 2) . ',';
            $csv .= round($taskType['avg_completion_days'] ?? 0, 2) . ',';
            $csv .= $taskType['high_priority_count'] . ',';
            $csv .= $taskType['medium_priority_count'] . ',';
            $csv .= $taskType['low_priority_count'] . "\n";
        }

        return $csv;
    }

    // CSV para productividad
    private static function generateProductivityCSV($data) {
        $csv = "REPORTE DE PRODUCTIVIDAD\n";
        $csv .= "Generado el: " . date('d/m/Y H:i:s') . "\n\n";
        
        if (!empty($data['productivity'])) {
            $productivity = $data['productivity'];
            
            $csv .= "METRICAS PRINCIPALES\n";
            $csv .= "Usuario," . ($productivity['username'] ?? 'N/A') . "\n";
            $csv .= "Nombre Completo," . ($productivity['full_name'] ?? 'N/A') . "\n";
            $csv .= "Total Tareas," . ($productivity['total_tasks'] ?? 0) . "\n";
            $csv .= "Completadas," . ($productivity['completed_tasks'] ?? 0) . "\n";
            $csv .= "Pendientes," . ($productivity['pending_tasks'] ?? 0) . "\n";
            $csv .= "En Progreso," . ($productivity['in_progress_tasks'] ?? 0) . "\n";
            $csv .= "Tasa Completado," . round($productivity['completion_rate'] ?? 0, 2) . "%\n";
            $csv .= "Horas Promedio," . round($productivity['avg_completion_hours'] ?? 0, 2) . "\n";
            $csv .= "Alta Prioridad Completadas," . ($productivity['high_priority_completed'] ?? 0) . "\n";
            $csv .= "Tareas Vencidas," . ($productivity['overdue_tasks'] ?? 0) . "\n";
            $csv .= "Total Notas," . ($productivity['total_notes'] ?? 0) . "\n";
            $csv .= "Total Clientes," . ($productivity['total_clients'] ?? 0) . "\n\n";
        }

        // Tendencia semanal
        if (!empty($data['weekly_trend'])) {
            $csv .= "TENDENCIA SEMANAL\n";
            $csv .= "Semana,Tareas Creadas,Tareas Completadas,Eficiencia\n";
            
            foreach ($data['weekly_trend'] as $week) {
                $efficiency = $week['tasks_created'] > 0 ? 
                    round(($week['tasks_completed'] / $week['tasks_created']) * 100) : 0;
                
                $csv .= $week['week_num'] . ',';
                $csv .= $week['tasks_created'] . ',';
                $csv .= $week['tasks_completed'] . ',';
                $csv .= $efficiency . "%\n";
            }
        }

        return $csv;
    }

    // CSV para dashboard ejecutivo
    private static function generateExecutiveDashboardCSV($data) {
        $csv = "DASHBOARD EJECUTIVO\n";
        $csv .= "Generado el: " . date('d/m/Y H:i:s') . "\n\n";
        
        if (!empty($data['metrics'])) {
            $metrics = $data['metrics'];
            
            $csv .= "METRICAS PRINCIPALES\n";
            $csv .= "Total Clientes," . ($metrics['total_clients'] ?? 0) . "\n";
            $csv .= "Total Tareas," . ($metrics['total_tasks'] ?? 0) . "\n";
            $csv .= "Tareas Completadas," . ($metrics['completed_tasks'] ?? 0) . "\n";
            $csv .= "Tareas Vencidas," . ($metrics['overdue_tasks'] ?? 0) . "\n";
            $csv .= "Vencen Hoy," . ($metrics['due_today'] ?? 0) . "\n";
            $csv .= "Alta Prioridad Pendientes," . ($metrics['high_priority_pending'] ?? 0) . "\n";
            $csv .= "Total Notas," . ($metrics['total_notes'] ?? 0) . "\n\n";
        }

        if (!empty($data['top_clients'])) {
            $csv .= "TOP CLIENTES\n";
            $csv .= "Cliente,Empresa,Total Tareas,Completadas,Tasa Completado\n";
            
            foreach ($data['top_clients'] as $client) {
                $csv .= '"' . str_replace('"', '""', $client['name']) . '",';
                $csv .= '"' . str_replace('"', '""', $client['company'] ?? '') . '",';
                $csv .= $client['task_count'] . ',';
                $csv .= $client['completed_count'] . ',';
                $csv .= ($client['completion_rate'] ?? 0) . "%\n";
            }
        }

        return $csv;
    }

    // Enviar email con reporte adjunto
    public static function emailReport($toEmail, $reportData, $reportType, $format = 'pdf') {
        // Esta función requeriría PHPMailer o similar
        // Ejemplo básico:
        
        $subject = "Reporte de Marketing Kanban - " . ucfirst(str_replace('_', ' ', $reportType));
        $body = "Se adjunta el reporte solicitado generado el " . date('d/m/Y H:i:s');
        
        // Generar attachment
        if ($format === 'pdf') {
            $attachment = self::generatePDF($reportData, $reportType);
            $filename = "reporte_{$reportType}_" . date('Y-m-d') . ".pdf";
        } else {
            $attachment = self::generateExcel($reportData, $reportType);
            $filename = "reporte_{$reportType}_" . date('Y-m-d') . ".csv";
        }
        
        // Con PHPMailer:
        /*
        $mail = new PHPMailer();
        $mail->setFrom('noreply@marketingkanban.com', 'Marketing Kanban');
        $mail->addAddress($toEmail);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->addStringAttachment($attachment, $filename);
        
        return $mail->send();
        */
        
        return false; // Placeholder
    }

    // Programar reportes automáticos
    public static function scheduleReport($userId, $reportType, $frequency, $email) {
        // Esta función guardaría la configuración en base de datos
        // para ser ejecutada por un cron job
        
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $query = "INSERT INTO scheduled_reports (user_id, report_type, frequency, email, next_run) 
                     VALUES (:user_id, :report_type, :frequency, :email, :next_run)";
            
            $next_run = self::calculateNextRun($frequency);
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':report_type', $reportType);
            $stmt->bindParam(':frequency', $frequency);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':next_run', $next_run);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al programar reporte: " . $e->getMessage());
            return false;
        }
    }

    // Calcular próxima ejecución
    private static function calculateNextRun($frequency) {
        switch ($frequency) {
            case 'daily':
                return date('Y-m-d H:i:s', strtotime('+1 day'));
            case 'weekly':
                return date('Y-m-d H:i:s', strtotime('+1 week'));
            case 'monthly':
                return date('Y-m-d H:i:s', strtotime('+1 month'));
            default:
                return date('Y-m-d H:i:s', strtotime('+1 week'));
        }
    }
}

// Script para cron job (ejecutar reportes programados)
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'run-scheduled') {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "SELECT * FROM scheduled_reports WHERE next_run <= NOW() AND is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $scheduledReports = $stmt->fetchAll();
        
        foreach ($scheduledReports as $schedule) {
            // Generar y enviar reporte
            $reportsManager = new ReportsManager();
            
            switch ($schedule['report_type']) {
                case 'client_progress':
                    $data = $reportsManager->getClientProgress($schedule['user_id']);
                    break;
                case 'task_analytics':
                    $data = $reportsManager->getTaskTypeAnalytics($schedule['user_id']);
                    break;
                case 'productivity':
                    $data = $reportsManager->getProductivityMetrics($schedule['user_id']);
                    break;
                default:
                    continue 2;
            }
            
            if ($data['success']) {
                $sent = ExportUtils::emailReport(
                    $schedule['email'], 
                    $data, 
                    $schedule['report_type'], 
                    'pdf'
                );
                
                if ($sent) {
                    // Actualizar próxima ejecución
                    $next_run = ExportUtils::calculateNextRun($schedule['frequency']);
                    $update_query = "UPDATE scheduled_reports SET next_run = :next_run WHERE id = :id";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':next_run', $next_run);
                    $update_stmt->bindParam(':id', $schedule['id']);
                    $update_stmt->execute();
                    
                    echo "Reporte enviado: {$schedule['report_type']} a {$schedule['email']}\n";
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error ejecutando reportes programados: " . $e->getMessage());
    }
}
?>