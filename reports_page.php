<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'hellfhpr_database';
$username = 'hellfhpr_kanban';
$password = 'xetw%2r&4#}R';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para obtener todas las tareas
function obtenerTareas($pdo, $cliente_id = null, $periodo = null) {
    $whereConditions = [];
    $params = [];
    
    if ($cliente_id) {
        $whereConditions[] = "t.cliente_id = :cliente_id";
        $params[':cliente_id'] = $cliente_id;
    }
    
    if ($periodo) {
        switch ($periodo) {
            case 'week':
                $whereConditions[] = "t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $whereConditions[] = "t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'quarter':
                $whereConditions[] = "t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
            case 'year':
                $whereConditions[] = "t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $sql = "SELECT t.*, c.nombre_cliente, c.responsable as cliente_responsable 
            FROM tareas t 
            LEFT JOIN clientes c ON t.cliente_id = c.id 
            $whereClause
            ORDER BY t.fecha_creacion DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener estadísticas
function obtenerEstadisticas($pdo, $cliente_id = null) {
    $stats = [];
    $whereClause = $cliente_id ? "WHERE cliente_id = :cliente_id" : "";
    $params = $cliente_id ? [':cliente_id' => $cliente_id] : [];
    
    // Total de tareas por estado
    $sql = "SELECT estado, COUNT(*) as total FROM tareas $whereClause GROUP BY estado";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total de tareas por prioridad
    $sql = "SELECT prioridad, COUNT(*) as total FROM tareas $whereClause GROUP BY prioridad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats['por_prioridad'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total de tareas por tipo
    $sql = "SELECT tipo_tarea, COUNT(*) as total FROM tareas $whereClause GROUP BY tipo_tarea";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats['por_tipo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total de tareas por cliente
    if (!$cliente_id) {
        $sql = "SELECT c.id, c.nombre_cliente, COUNT(t.id) as total 
                FROM clientes c 
                LEFT JOIN tareas t ON c.id = t.cliente_id 
                GROUP BY c.id, c.nombre_cliente
                ORDER BY total DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats['por_cliente'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $stats;
}

// Función para obtener progreso detallado por cliente
function obtenerProgresoCliente($pdo, $cliente_id = null) {
    $whereClause = $cliente_id ? "WHERE c.id = :cliente_id" : "";
    
    $sql = "SELECT 
                c.id,
                c.nombre_cliente,
                c.responsable,
                COUNT(t.id) as total_tareas,
                COUNT(CASE WHEN t.estado = 'Completado' THEN 1 END) as tareas_completadas,
                COUNT(CASE WHEN t.estado = 'En Progreso' THEN 1 END) as tareas_progreso,
                COUNT(CASE WHEN t.estado = 'En Revisión' THEN 1 END) as tareas_revision,
                COUNT(CASE WHEN t.estado = 'Por Hacer' THEN 1 END) as tareas_pendientes,
                ROUND(
                    (COUNT(CASE WHEN t.estado = 'Completado' THEN 1 END) * 100.0) / 
                    NULLIF(COUNT(t.id), 0), 2
                ) as porcentaje_progreso,
                AVG(
                    CASE WHEN t.estado = 'Completado' 
                    THEN TIMESTAMPDIFF(DAY, t.fecha_creacion, t.fecha_actualizacion) 
                    END
                ) as tiempo_promedio_dias
            FROM clientes c 
            LEFT JOIN tareas t ON c.id = t.cliente_id 
            $whereClause
            GROUP BY c.id, c.nombre_cliente, c.responsable
            HAVING total_tareas > 0
            ORDER BY porcentaje_progreso DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($cliente_id) {
        $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para analytics de tareas
function obtenerAnalyticsTareas($pdo) {
    $sql = "SELECT 
                tipo_tarea,
                COUNT(*) as total_tareas,
                COUNT(CASE WHEN estado = 'Completado' THEN 1 END) as completadas,
                ROUND(
                    (COUNT(CASE WHEN estado = 'Completado' THEN 1 END) * 100.0) / 
                    COUNT(*), 2
                ) as tasa_completado,
                AVG(
                    CASE WHEN estado = 'Completado' 
                    THEN TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_actualizacion) 
                    END
                ) as tiempo_promedio_horas,
                COUNT(CASE WHEN prioridad = 'Alta' THEN 1 END) as alta_prioridad,
                COUNT(CASE WHEN prioridad = 'Urgente' THEN 1 END) as urgente
            FROM tareas 
            GROUP BY tipo_tarea
            ORDER BY total_tareas DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para métricas de productividad
function obtenerProductividad($pdo) {
    $sql = "SELECT 
                COUNT(*) as total_tareas,
                COUNT(CASE WHEN estado = 'Completado' THEN 1 END) as tareas_completadas,
                ROUND(
                    (COUNT(CASE WHEN estado = 'Completado' THEN 1 END) * 100.0) / 
                    COUNT(*), 2
                ) as tasa_completado_general,
                AVG(
                    CASE WHEN estado = 'Completado' 
                    THEN TIMESTAMPDIFF(DAY, fecha_creacion, fecha_actualizacion) 
                    END
                ) as tiempo_promedio_completado,
                COUNT(CASE WHEN estado IN ('En Progreso', 'En Revisión') THEN 1 END) as tareas_activas,
                COUNT(CASE WHEN prioridad IN ('Alta', 'Urgente') THEN 1 END) as tareas_criticas
            FROM tareas";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Agregar tendencias semanales
    $sql_tendencias = "SELECT 
                        WEEK(fecha_creacion) as semana,
                        YEAR(fecha_creacion) as año,
                        COUNT(*) as tareas_creadas,
                        COUNT(CASE WHEN estado = 'Completado' THEN 1 END) as tareas_completadas_semana
                       FROM tareas 
                       WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
                       GROUP BY YEAR(fecha_creacion), WEEK(fecha_creacion)
                       ORDER BY año DESC, semana DESC";
    
    $stmt = $pdo->prepare($sql_tendencias);
    $stmt->execute();
    $result['tendencias_semanales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $result;
}

// Procesar parámetros de URL
$cliente_seleccionado = isset($_GET['cliente']) ? (int)$_GET['cliente'] : null;
$periodo_seleccionado = isset($_GET['periodo']) ? $_GET['periodo'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$report_type = isset($_GET['type']) ? $_GET['type'] : null;

// Manejar acciones AJAX
if ($action) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'generate':
            switch ($report_type) {
                case 'client_progress':
                    $data = obtenerProgresoCliente($pdo, $cliente_seleccionado);
                    break;
                case 'task_analytics':
                    $data = obtenerAnalyticsTareas($pdo);
                    break;
                case 'productivity':
                    $data = obtenerProductividad($pdo);
                    break;
                case 'executive_dashboard':
                    $data = [
                        'progreso_clientes' => obtenerProgresoCliente($pdo),
                        'analytics_tareas' => obtenerAnalyticsTareas($pdo),
                        'productividad' => obtenerProductividad($pdo),
                        'estadisticas' => obtenerEstadisticas($pdo)
                    ];
                    break;
                default:
                    $data = ['error' => 'Tipo de reporte no válido'];
            }
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
            
        case 'export':
            // Manejar exportación (implementar según necesidades)
            echo json_encode(['success' => true, 'message' => 'Exportación iniciada']);
            exit;
    }
}

// Si es una petición AJAX normal, devolver JSON
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'tareas' => obtenerTareas($pdo, $cliente_seleccionado, $periodo_seleccionado),
        'estadisticas' => obtenerEstadisticas($pdo, $cliente_seleccionado),
        'progreso_clientes' => obtenerProgresoCliente($pdo, $cliente_seleccionado),
        'analytics_tareas' => obtenerAnalyticsTareas($pdo),
        'productividad' => obtenerProductividad($pdo)
    ]);
    exit;
}

// Obtener datos para la página
$tareas = obtenerTareas($pdo, $cliente_seleccionado, $periodo_seleccionado);
$estadisticas = obtenerEstadisticas($pdo, $cliente_seleccionado);
$progreso_clientes = obtenerProgresoCliente($pdo, $cliente_seleccionado);
$analytics_tareas = obtenerAnalyticsTareas($pdo);
$productividad = obtenerProductividad($pdo);

// Obtener información del cliente seleccionado
$cliente_info = null;
if ($cliente_seleccionado) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
    $stmt->execute([':id' => $cliente_seleccionado]);
    $cliente_info = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Marketing - Sistema Kanban</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #121A28 0%, #A23004 100%);
            min-height: 100vh;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid #F09146;
        }

        .header h1 {
            color: #F09146;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #121A28;
            font-size: 16px;
        }

        .client-info-banner {
            background: linear-gradient(135deg, #F09146 0%, #A23004 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(240, 145, 70, 0.3);
        }

        .client-info-banner h3 {
            margin-bottom: 5px;
            font-size: 20px;
        }

        .back-link {
            display: inline-block;
            background: white;
            color: #F09146;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .period-selector {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #F09146;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .period-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .period-controls select,
        .period-controls input {
            padding: 8px 12px;
            border: 2px solid #F09146;
            border-radius: 8px;
            font-size: 14px;
        }

        .loading-spinner {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #F09146;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-top: 4px solid #F09146;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(240, 145, 70, 0.3);
        }

        .stat-card h3 {
            color: #121A28;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-weight: 600;
            color: #121A28;
        }

        .stat-value {
            background: linear-gradient(135deg, #F09146 0%, #A23004 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
        }

        .client-link {
            color: #F09146;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .client-link:hover {
            color: #A23004;
            text-decoration: underline;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-top: 4px solid #F09146;
            overflow: hidden;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(240, 145, 70, 0.3);
        }

        .report-header {
            padding: 20px 20px 10px;
            border-bottom: 1px solid #eee;
        }

        .report-header h3 {
            color: #121A28;
            margin-bottom: 8px;
            font-size: 18px;
        }

        .report-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .report-content {
            padding: 20px;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #F09146 0%, #A23004 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #A23004 0%, #F09146 100%);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #121A28;
            border: 2px solid #F09146;
        }

        .btn-secondary:hover {
            background: #F09146;
            color: white;
        }

        .tasks-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #F09146;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #121A28;
            font-size: 24px;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #F09146;
            background: white;
            color: #F09146;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: linear-gradient(135deg, #F09146 0%, #A23004 100%);
            color: white;
            border-color: #A23004;
        }

        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tasks-table th,
        .tasks-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .tasks-table th {
            background: linear-gradient(135deg, #121A28 0%, #F09146 100%);
            font-weight: 600;
            color: white;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .priority-baja {
            background: #d4edda;
            color: #155724;
        }

        .priority-media {
            background: #fff3cd;
            color: #856404;
        }

        .priority-alta {
            background: #ffeaa0;
            color: #A23004;
        }

        .priority-urgente {
            background: #A23004;
            color: white;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-por-hacer {
            background: #e2e3e5;
            color: #121A28;
        }

        .status-en-progreso {
            background: #bee5eb;
            color: #0c5460;
        }

        .status-en-revision {
            background: #ffe6cc;
            color: #F09146;
        }

        .status-completado {
            background: #d4edda;
            color: #155724;
        }

        .type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            background: #f1f3f4;
            color: #121A28;
            border: 1px solid #F09146;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #F09146 0%, #A23004 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .refresh-btn:hover {
            background: linear-gradient(135deg, #A23004 0%, #F09146 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(240, 145, 70, 0.4);
        }

        .report-result {
            background: #f8f9fa;
            border: 1px solid #F09146;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .report-result.show {
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .stats-grid,
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .navigation-controls {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-btn {
                width: 100%;
                text-align: center;
            }
            
            .period-selector {
                flex-direction: column;
                text-align: center;
            }
            
            .period-controls {
                justify-content: center;
                flex-direction: column;
                width: 100%;
            }
            
            .period-controls select,
            .period-controls input {
                width: 100%;
                min-width: auto;
            }
            
            .report-content {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .report-actions-container {
                grid-column: 1;
            }
            
            .filter-controls {
                justify-content: center;
            }
            
            .tasks-table {
                font-size: 14px;
            }
            
            .tasks-table th,
            .tasks-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Reportes de Marketing</h1>
            <p>Panel de control y estadísticas del sistema Kanban</p>
        </div>

        <!-- Client Info Banner (if client selected) -->
        <?php if ($cliente_info): ?>
        <div class="client-info-banner">
            <h3>📋 Reporte para: <?php echo htmlspecialchars($cliente_info['nombre_cliente']); ?></h3>
            <p>Responsable: <?php echo htmlspecialchars($cliente_info['responsable']); ?></p>
            <a href="reports_page.php" class="back-link">← Volver a todos los clientes</a>
        </div>
        <?php endif; ?>

        <!-- Navigation and Controls -->
        <div class="controls-section">
            <!-- Navigation Buttons -->
            <div class="navigation-controls">
                <a href="index.php" class="nav-btn">🏠 Dashboard Principal</a>
                <a href="tareas_calendar.php" class="nav-btn">📅 Calendario de Tareas</a>
                <a href="reports_page.php" class="nav-btn active">📊 Reportes</a>
            </div>
            
            <!-- Period and Client Selector -->
            <div class="period-selector">
                <div>
                    <h3 style="margin: 0; color: #121A28;">Filtros de Análisis</h3>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Selecciona período y cliente para generar reportes específicos</p>
                </div>
                <div class="period-controls">
                    <select id="clientFilter" onchange="updateFilters()">
                        <option value="">Todos los Clientes</option>
                        <?php 
                        // Obtener lista de clientes para el filtro
                        $stmt = $pdo->prepare("SELECT id, nombre_cliente FROM clientes ORDER BY nombre_cliente");
                        $stmt->execute();
                        $clientes_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($clientes_list as $cliente): 
                        ?>
                            <option value="<?php echo $cliente['id']; ?>" <?php echo $cliente_seleccionado == $cliente['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="reportPeriod" onchange="updatePeriod()">
                        <option value="all" <?php echo !$periodo_seleccionado || $periodo_seleccionado === 'all' ? 'selected' : ''; ?>>Todo el Tiempo</option>
                        <option value="week" <?php echo $periodo_seleccionado === 'week' ? 'selected' : ''; ?>>Esta Semana</option>
                        <option value="month" <?php echo $periodo_seleccionado === 'month' ? 'selected' : ''; ?>>Este Mes</option>
                        <option value="quarter" <?php echo $periodo_seleccionado === 'quarter' ? 'selected' : ''; ?>>Este Trimestre</option>
                        <option value="year" <?php echo $periodo_seleccionado === 'year' ? 'selected' : ''; ?>>Este Año</option>
                        <option value="custom">Período Personalizado</option>
                    </select>
                    <div id="customDateRange" style="display: none;">
                        <input type="date" id="startDate" placeholder="Fecha inicial">
                        <input type="date" id="endDate" placeholder="Fecha final">
                    </div>
                    <button class="btn btn-primary" onclick="generateReports()">Aplicar Filtros</button>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner" style="display: none;">
            <div class="spinner"></div>
            <p>Generando reportes, por favor espera...</p>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <!-- Progreso por Cliente -->
            <div class="report-card">
                <div class="report-header">
                    <h3>📈 Progreso por Cliente</h3>
                    <p>Análisis detallado del progreso y completado de tareas por cliente</p>
                </div>
                <div class="report-content">
                    <div class="report-features">
                        <p><strong>Incluye:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px; color: #666; line-height: 1.6;">
                            <li>Tareas totales vs completadas</li>
                            <li>Porcentaje de progreso</li>
                            <li>Tiempo promedio de completado</li>
                            <li>Distribución por estado</li>
                            <li>Comparativa entre clientes</li>
                            <li>Métricas de rendimiento</li>
                        </ul>
                    </div>
                    <div class="report-actions-container">
                        <div class="report-actions">
                            <button class="btn btn-primary" onclick="generateSpecificReport('client_progress')" style="width: 100%;">
                                📊 Generar Reporte
                            </button>
                        </div>
                        <div class="report-actions">
                            <button class="btn btn-secondary" onclick="exportReport('client_progress', 'csv')" style="width: 48%;">
                                📥 CSV
                            </button>
                            <button class="btn btn-secondary" onclick="exportReport('client_progress', 'pdf')" style="width: 48%;">
                                📄 PDF
                            </button>
                        </div>
                    </div>
                    <div id="report-client-progress" class="report-result"></div>
                </div>
            </div>
        </div>

        <!-- Analytics de Tareas -->
        <div class="reports-grid">
            <div class="report-card">
                <div class="report-header">
                    <h3>🎯 Analytics de Tareas</h3>
                    <p>Análisis de eficiencia y tiempo por tipo de tarea</p>
                </div>
                <div class="report-content">
                    <div class="report-features">
                        <p><strong>Incluye:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px; color: #666; line-height: 1.6;">
                            <li>Tiempo promedio por tipo</li>
                            <li>Análisis por categorías</li>
                            <li>Eficiencia por plantillas</li>
                            <li>Distribución por prioridad</li>
                            <li>Tasa de éxito por tipo</li>
                            <li>Identificación de cuellos de botella</li>
                        </ul>
                    </div>
                    <div class="report-actions-container">
                        <div class="report-actions">
                            <button class="btn btn-primary" onclick="generateSpecificReport('task_analytics')" style="width: 100%;">
                                🎯 Generar Reporte
                            </button>
                        </div>
                        <div class="report-actions">
                            <button class="btn btn-secondary" onclick="exportReport('task_analytics', 'csv')" style="width: 48%;">
                                📥 CSV
                            </button>
                            <button class="btn btn-secondary" onclick="exportReport('task_analytics', 'pdf')" style="width: 48%;">
                                📄 PDF
                            </button>
                        </div>
                    </div>
                    <div id="report-task-analytics" class="report-result"></div>
                </div>
            </div>
        </div>

        <!-- Productividad -->
        <div class="reports-grid">
            <div class="report-card">
                <div class="report-header">
                    <h3>⚡ Productividad</h3>
                    <p>Métricas de rendimiento y eficiencia del equipo</p>
                </div>
                <div class="report-content">
                    <div class="report-features">
                        <p><strong>Incluye:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px; color: #666; line-height: 1.6;">
                            <li>Tasa de completado general</li>
                            <li>Tiempo promedio por tarea</li>
                            <li>Cumplimiento de fechas</li>
                            <li>Tendencias semanales</li>
                            <li>Análisis de carga de trabajo</li>
                            <li>Indicadores de productividad</li>
                        </ul>
                    </div>
                    <div class="report-actions-container">
                        <div class="report-actions">
                            <button class="btn btn-primary" onclick="generateSpecificReport('productivity')" style="width: 100%;">
                                ⚡ Generar Reporte
                            </button>
                        </div>
                        <div class="report-actions">
                            <button class="btn btn-secondary" onclick="exportReport('productivity', 'csv')" style="width: 48%;">
                                📥 CSV
                            </button>
                            <button class="btn btn-secondary" onclick="exportReport('productivity', 'pdf')" style="width: 48%;">
                                📄 PDF
                            </button>
                        </div>
                    </div>
                    <div id="report-productivity" class="report-result"></div>
                </div>
            </div>
        </div>

        <!-- Executive Dashboard -->
        <div class="reports-grid">
            <div class="report-card">
                <div class="report-header">
                    <h3>👔 Dashboard Ejecutivo</h3>
                    <p>Resumen ejecutivo completo para toma de decisiones</p>
                </div>
                <div class="report-content">
                    <div class="report-features">
                        <p><strong>Incluye:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px; color: #666; line-height: 1.6;">
                            <li>KPIs principales</li>
                            <li>Resumen por cliente</li>
                            <li>Alertas y recomendaciones</li>
                            <li>Proyecciones de cumplimiento</li>
                            <li>Análisis comparativo</li>
                            <li>Resumen para stakeholders</li>
                        </ul>
                    </div>
                    <div class="report-actions-container">
                        <div class="report-actions">
                            <button class="btn btn-primary" onclick="generateSpecificReport('executive_dashboard')" style="width: 100%;">
                                👔 Generar Dashboard
                            </button>
                        </div>
                        <div class="report-actions">
                            <button class="btn btn-secondary" onclick="exportReport('executive_dashboard', 'pdf')" style="width: 48%;">
                                📄 PDF
                            </button>
                            <button class="btn btn-secondary" onclick="exportReport('executive_dashboard', 'excel')" style="width: 48%;">
                                📊 Excel
                            </button>
                        </div>
                    </div>
                    <div id="report-executive-dashboard" class="report-result"></div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <!-- Tareas por Estado -->
            <div class="stat-card">
                <h3>📈 Tareas por Estado</h3>
                <div id="estadosStats">
                    <?php if (!empty($estadisticas['por_estado'])): ?>
                        <?php foreach ($estadisticas['por_estado'] as $estado): ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo htmlspecialchars($estado['estado']); ?></span>
                            <span class="stat-value"><?php echo $estado['total']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stat-item">
                            <span class="stat-label">Sin datos</span>
                            <span class="stat-value">0</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tareas por Prioridad -->
            <div class="stat-card">
                <h3>🔥 Tareas por Prioridad</h3>
                <div id="prioridadStats">
                    <?php if (!empty($estadisticas['por_prioridad'])): ?>
                        <?php foreach ($estadisticas['por_prioridad'] as $prioridad): ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo htmlspecialchars($prioridad['prioridad']); ?></span>
                            <span class="stat-value"><?php echo $prioridad['total']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stat-item">
                            <span class="stat-label">Sin datos</span>
                            <span class="stat-value">0</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tareas por Tipo -->
            <div class="stat-card">
                <h3>📋 Tareas por Tipo</h3>
                <div id="tipoStats">
                    <?php if (!empty($estadisticas['por_tipo'])): ?>
                        <?php foreach ($estadisticas['por_tipo'] as $tipo): ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo htmlspecialchars($tipo['tipo_tarea']); ?></span>
                            <span class="stat-value"><?php echo $tipo['total']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stat-item">
                            <span class="stat-label">Sin datos</span>
                            <span class="stat-value">0</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tareas por Cliente -->
            <?php if (!$cliente_seleccionado && !empty($estadisticas['por_cliente'])): ?>
            <div class="stat-card">
                <h3>👥 Tareas por Cliente</h3>
                <div id="clienteStats">
                    <?php foreach ($estadisticas['por_cliente'] as $cliente): ?>
                    <div class="stat-item">
                        <span class="stat-label">
                            <a href="reports_page.php?cliente=<?php echo $cliente['id']; ?>" class="client-link">
                                <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                            </a>
                        </span>
                        <span class="stat-value"><?php echo $cliente['total']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tabla de Tareas -->
        <div class="tasks-section">
            <div class="section-header">
                <h2>📝 Lista Completa de Tareas</h2>
                <div class="filter-controls">
                    <button class="filter-btn active" onclick="filtrarTareas('todas')">Todas</button>
                    <button class="filter-btn" onclick="filtrarTareas('Por Hacer')">Por Hacer</button>
                    <button class="filter-btn" onclick="filtrarTareas('En Progreso')">En Progreso</button>
                    <button class="filter-btn" onclick="filtrarTareas('En Revisión')">En Revisión</button>
                    <button class="filter-btn" onclick="filtrarTareas('Completado')">Completado</button>
                    <button class="refresh-btn" onclick="actualizarDatos()">🔄 Actualizar</button>
                </div>
            </div>

            <div id="tasksTableContainer">
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tarea</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Responsable</th>
                            <th>Fecha Creación</th>
                        </tr>
                    </thead>
                    <tbody id="tasksTableBody">
                        <?php if (!empty($tareas)): ?>
                            <?php foreach ($tareas as $tarea): ?>
                            <tr data-estado="<?php echo htmlspecialchars($tarea['estado']); ?>">
                                <td><?php echo $tarea['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($tarea['nombre_tarea']); ?></strong>
                                    <?php if ($tarea['descripcion']): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars(substr($tarea['descripcion'], 0, 50)) . (strlen($tarea['descripcion']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($tarea['nombre_cliente'] && !$cliente_seleccionado): ?>
                                        <a href="reports_page.php?cliente=<?php echo $tarea['cliente_id']; ?>" class="client-link">
                                            <?php echo htmlspecialchars($tarea['nombre_cliente']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($tarea['nombre_cliente'] ?? 'Sin cliente'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="type-badge"><?php echo htmlspecialchars($tarea['tipo_tarea']); ?></span></td>
                                <td><span class="priority-badge priority-<?php echo strtolower(str_replace(' ', '-', $tarea['prioridad'])); ?>"><?php echo htmlspecialchars($tarea['prioridad']); ?></span></td>
                                <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $tarea['estado'])); ?>"><?php echo htmlspecialchars($tarea['estado']); ?></span></td>
                                <td><?php echo htmlspecialchars($tarea['responsable']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($tarea['fecha_creacion'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666; padding: 40px;">
                                    No hay tareas para mostrar
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let todasLasTareas = <?php echo json_encode($tareas); ?>;
        let estadisticasGlobales = <?php echo json_encode($estadisticas); ?>;
        let progresoClientes = <?php echo json_encode($progreso_clientes); ?>;
        let analyticsTareas = <?php echo json_encode($analytics_tareas); ?>;
        let productividad = <?php echo json_encode($productividad); ?>;
        let clienteSeleccionado = <?php echo json_encode($cliente_seleccionado); ?>;

        // Función para actualizar período
        function updatePeriod() {
            const select = document.getElementById('reportPeriod');
            const customRange = document.getElementById('customDateRange');
            
            if (select.value === 'custom') {
                customRange.style.display = 'flex';
                customRange.style.gap = '10px';
            } else {
                customRange.style.display = 'none';
            }
        }

        // Función para actualizar filtros (nueva función)
        function updateFilters() {
            const clientFilter = document.getElementById('clientFilter').value;
            const periodFilter = document.getElementById('reportPeriod').value;
            
            // Actualizar URL con filtros
            const params = new URLSearchParams();
            
            if (clientFilter) {
                params.append('cliente', clientFilter);
            }
            
            if (periodFilter && periodFilter !== 'all') {
                params.append('periodo', periodFilter);
            }
            
            // Construir nueva URL
            let newUrl = 'reports_page.php';
            if (params.toString()) {
                newUrl += '?' + params.toString();
            }
            
            // Redireccionar con nuevos filtros
            window.location.href = newUrl;
        }

        // Función para generar reportes (actualizada)
        function generateReports() {
            updateFilters(); // Usar la misma lógica de filtros
        }

        // Función para generar reporte específico
        function generateSpecificReport(reportType) {
            const spinner = document.getElementById('loadingSpinner');
            spinner.style.display = 'block';
            
            const params = new URLSearchParams({
                action: 'generate',
                type: reportType
            });
            
            if (clienteSeleccionado) {
                params.append('cliente', clienteSeleccionado);
            }
            
            fetch(`reports_page.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    if (data.success) {
                        displayReportResults(reportType, data.data);
                    } else {
                        alert('Error generando reporte: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    console.error('Error:', error);
                    alert('Error de conexión al generar reporte');
                });
        }

        // Función para mostrar resultados de reportes
        function displayReportResults(reportType, data) {
            const resultContainer = document.getElementById(`report-${reportType.replace('_', '-')}`);
            let html = '';
            
            switch (reportType) {
                case 'client_progress':
                    html = '<h4>📈 Progreso por Cliente</h4>';
                    if (data && data.length > 0) {
                        html += '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                        html += '<thead><tr style="background: #f8f9fa;"><th style="padding: 8px; border: 1px solid #ddd;">Cliente</th><th style="padding: 8px; border: 1px solid #ddd;">Total Tareas</th><th style="padding: 8px; border: 1px solid #ddd;">Completadas</th><th style="padding: 8px; border: 1px solid #ddd;">% Progreso</th><th style="padding: 8px; border: 1px solid #ddd;">Tiempo Promedio (días)</th></tr></thead><tbody>';
                        data.forEach(cliente => {
                            html += `<tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">${cliente.nombre_cliente}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${cliente.total_tareas}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${cliente.tareas_completadas}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${cliente.porcentaje_progreso || 0}%</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${Math.round(cliente.tiempo_promedio_dias || 0)}</td>
                            </tr>`;
                        });
                        html += '</tbody></table></div>';
                    } else {
                        html += '<p style="color: #666; padding: 20px;">No hay datos de progreso para mostrar.</p>';
                    }
                    break;
                    
                case 'task_analytics':
                    html = '<h4>🎯 Analytics de Tareas</h4>';
                    if (data && data.length > 0) {
                        html += '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                        html += '<thead><tr style="background: #f8f9fa;"><th style="padding: 8px; border: 1px solid #ddd;">Tipo de Tarea</th><th style="padding: 8px; border: 1px solid #ddd;">Total</th><th style="padding: 8px; border: 1px solid #ddd;">Completadas</th><th style="padding: 8px; border: 1px solid #ddd;">Tasa Éxito</th><th style="padding: 8px; border: 1px solid #ddd;">Tiempo Promedio (hrs)</th></tr></thead><tbody>';
                        data.forEach(tipo => {
                            html += `<tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">${tipo.tipo_tarea}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${tipo.total_tareas}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${tipo.completadas}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${tipo.tasa_completado || 0}%</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">${Math.round(tipo.tiempo_promedio_horas || 0)}</td>
                            </tr>`;
                        });
                        html += '</tbody></table></div>';
                    } else {
                        html += '<p style="color: #666; padding: 20px;">No hay datos de analytics para mostrar.</p>';
                    }
                    break;
                    
                case 'productivity':
                    html = '<h4>⚡ Métricas de Productividad</h4>';
                    if (data) {
                        html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h5 style="margin: 0 0 5px 0; color: #F09146;">Tasa de Completado</h5>
                                <div style="font-size: 24px; font-weight: bold; color: #121A28;">${data.tasa_completado_general || 0}%</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h5 style="margin: 0 0 5px 0; color: #F09146;">Tiempo Promedio</h5>
                                <div style="font-size: 24px; font-weight: bold; color: #121A28;">${Math.round(data.tiempo_promedio_completado || 0)} días</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h5 style="margin: 0 0 5px 0; color: #F09146;">Tareas Activas</h5>
                                <div style="font-size: 24px; font-weight: bold; color: #121A28;">${data.tareas_activas || 0}</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h5 style="margin: 0 0 5px 0; color: #F09146;">Tareas Críticas</h5>
                                <div style="font-size: 24px; font-weight: bold; color: #121A28;">${data.tareas_criticas || 0}</div>
                            </div>
                        </div>`;
                        
                        if (data.tendencias_semanales && data.tendencias_semanales.length > 0) {
                            html += '<h5 style="margin: 20px 0 10px 0;">Tendencias Semanales:</h5>';
                            html += '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">';
                            html += '<thead><tr style="background: #f8f9fa;"><th style="padding: 8px; border: 1px solid #ddd;">Semana</th><th style="padding: 8px; border: 1px solid #ddd;">Tareas Creadas</th><th style="padding: 8px; border: 1px solid #ddd;">Tareas Completadas</th></tr></thead><tbody>';
                            data.tendencias_semanales.forEach(semana => {
                                html += `<tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;">Sem ${semana.semana}/${semana.año}</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">${semana.tareas_creadas}</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">${semana.tareas_completadas_semana}</td>
                                </tr>`;
                            });
                            html += '</tbody></table></div>';
                        }
                    } else {
                        html += '<p style="color: #666; padding: 20px;">No hay datos de productividad para mostrar.</p>';
                    }
                    break;
                    
                case 'executive_dashboard':
                    html = '<h4>👔 Dashboard Ejecutivo</h4>';
                    if (data) {
                        html += '<div style="display: grid; gap: 20px; margin-top: 15px;">';
                        
                        // KPIs principales
                        if (data.productividad) {
                            html += `<div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                                <h5 style="margin: 0 0 15px 0; color: #F09146;">📊 KPIs Principales</h5>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                    <div style="text-align: center; padding: 10px;">
                                        <div style="font-size: 20px; font-weight: bold; color: #121A28;">${data.productividad.total_tareas || 0}</div>
                                        <div style="font-size: 12px; color: #666;">Total Tareas</div>
                                    </div>
                                    <div style="text-align: center; padding: 10px;">
                                        <div style="font-size: 20px; font-weight: bold; color: #121A28;">${data.productividad.tasa_completado_general || 0}%</div>
                                        <div style="font-size: 12px; color: #666;">Tasa Completado</div>
                                    </div>
                                    <div style="text-align: center; padding: 10px;">
                                        <div style="font-size: 20px; font-weight: bold; color: #121A28;">${data.productividad.tareas_activas || 0}</div>
                                        <div style="font-size: 12px; color: #666;">Tareas Activas</div>
                                    </div>
                                </div>
                            </div>`;
                        }
                        
                        // Resumen por cliente (top 5)
                        if (data.progreso_clientes && data.progreso_clientes.length > 0) {
                            html += `<div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                                <h5 style="margin: 0 0 15px 0; color: #F09146;">🏆 Top 5 Clientes por Progreso</h5>
                                <div style="overflow-x: auto;">`;
                            data.progreso_clientes.slice(0, 5).forEach((cliente, index) => {
                                const badge = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '📈';
                                html += `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #ddd;">
                                    <span>${badge} ${cliente.nombre_cliente}</span>
                                    <span style="font-weight: bold; color: #F09146;">${cliente.porcentaje_progreso || 0}%</span>
                                </div>`;
                            });
                            html += '</div></div>';
                        }
                        
                        html += '</div>';
                    } else {
                        html += '<p style="color: #666; padding: 20px;">No hay datos del dashboard para mostrar.</p>';
                    }
                    break;
            }
            
            resultContainer.innerHTML = html;
            resultContainer.classList.add('show');
        }

        // Función para exportar reportes
        function exportReport(reportType, format) {
            const params = new URLSearchParams({
                action: 'export',
                type: reportType,
                format: format
            });
            
            if (clienteSeleccionado) {
                params.append('cliente', clienteSeleccionado);
            }
            
            // Abrir en nueva ventana para descarga
            window.open(`reports_page.php?${params.toString()}`);
        }

        // Función para filtrar tareas
        function filtrarTareas(filtro) {
            // Actualizar botones activos
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filtrar filas de la tabla
            const filas = document.querySelectorAll('#tasksTableBody tr');
            filas.forEach(fila => {
                if (filtro === 'todas') {
                    fila.style.display = '';
                } else {
                    const estadoFila = fila.getAttribute('data-estado');
                    fila.style.display = estadoFila === filtro ? '' : 'none';
                }
            });
        }

        // Función para actualizar datos vía AJAX
        function actualizarDatos() {
            const refreshBtn = document.querySelector('.refresh-btn');
            refreshBtn.textContent = '🔄 Actualizando...';
            refreshBtn.disabled = true;

            const params = new URLSearchParams({ ajax: '1' });
            
            if (clienteSeleccionado) {
                params.append('cliente', clienteSeleccionado);
            }

            fetch(`reports_page.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    todasLasTareas = data.tareas;
                    estadisticasGlobales = data.estadisticas;
                    progresoClientes = data.progreso_clientes;
                    analyticsTareas = data.analytics_tareas;
                    productividad = data.productividad;
                    
                    // Actualizar estadísticas
                    actualizarEstadisticas();
                    
                    // Actualizar tabla
                    actualizarTabla();
                    
                    refreshBtn.textContent = '🔄 Actualizar';
                    refreshBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Error actualizando datos:', error);
                    refreshBtn.textContent = '❌ Error';
                    setTimeout(() => {
                        refreshBtn.textContent = '🔄 Actualizar';
                        refreshBtn.disabled = false;
                    }, 2000);
                });
        }

        // Función para actualizar estadísticas
        function actualizarEstadisticas() {
            // Actualizar estadísticas por estado
            let html = '';
            if (estadisticasGlobales.por_estado && estadisticasGlobales.por_estado.length > 0) {
                estadisticasGlobales.por_estado.forEach(estado => {
                    html += `
                        <div class="stat-item">
                            <span class="stat-label">${estado.estado}</span>
                            <span class="stat-value">${estado.total}</span>
                        </div>
                    `;
                });
            } else {
                html = '<div class="stat-item"><span class="stat-label">Sin datos</span><span class="stat-value">0</span></div>';
            }
            document.getElementById('estadosStats').innerHTML = html;

            // Actualizar estadísticas por prioridad
            html = '';
            if (estadisticasGlobales.por_prioridad && estadisticasGlobales.por_prioridad.length > 0) {
                estadisticasGlobales.por_prioridad.forEach(prioridad => {
                    html += `
                        <div class="stat-item">
                            <span class="stat-label">${prioridad.prioridad}</span>
                            <span class="stat-value">${prioridad.total}</span>
                        </div>
                    `;
                });
            } else {
                html = '<div class="stat-item"><span class="stat-label">Sin datos</span><span class="stat-value">0</span></div>';
            }
            document.getElementById('prioridadStats').innerHTML = html;

            // Actualizar estadísticas por tipo
            html = '';
            if (estadisticasGlobales.por_tipo && estadisticasGlobales.por_tipo.length > 0) {
                estadisticasGlobales.por_tipo.forEach(tipo => {
                    html += `
                        <div class="stat-item">
                            <span class="stat-label">${tipo.tipo_tarea}</span>
                            <span class="stat-value">${tipo.total}</span>
                        </div>
                    `;
                });
            } else {
                html = '<div class="stat-item"><span class="stat-label">Sin datos</span><span class="stat-value">0</span></div>';
            }
            document.getElementById('tipoStats').innerHTML = html;

            // Actualizar estadísticas por cliente (solo si no hay cliente seleccionado)
            if (!clienteSeleccionado && estadisticasGlobales.por_cliente) {
                html = '';
                if (estadisticasGlobales.por_cliente.length > 0) {
                    estadisticasGlobales.por_cliente.forEach(cliente => {
                        html += `
                            <div class="stat-item">
                                <span class="stat-label">
                                    <a href="reports_page.php?cliente=${cliente.id}" class="client-link">
                                        ${cliente.nombre_cliente}
                                    </a>
                                </span>
                                <span class="stat-value">${cliente.total}</span>
                            </div>
                        `;
                    });
                } else {
                    html = '<div class="stat-item"><span class="stat-label">Sin clientes</span><span class="stat-value">0</span></div>';
                }
                const clienteStatsElement = document.getElementById('clienteStats');
                if (clienteStatsElement) {
                    clienteStatsElement.innerHTML = html;
                }
            }
        }

        // Función para actualizar tabla
        function actualizarTabla() {
            let html = '';
            if (todasLasTareas && todasLasTareas.length > 0) {
                todasLasTareas.forEach(tarea => {
                    const descripcionCorta = tarea.descripcion ? 
                        (tarea.descripcion.length > 50 ? 
                            tarea.descripcion.substring(0, 50) + '...' : 
                            tarea.descripcion) : '';
                    
                    const clienteLink = tarea.nombre_cliente && !clienteSeleccionado ?
                        `<a href="reports_page.php?cliente=${tarea.cliente_id}" class="client-link">${tarea.nombre_cliente}</a>` :
                        (tarea.nombre_cliente || 'Sin cliente');
                    
                    html += `
                        <tr data-estado="${tarea.estado}">
                            <td>${tarea.id}</td>
                            <td>
                                <strong>${tarea.nombre_tarea}</strong>
                                ${descripcionCorta ? `<br><small style="color: #666;">${descripcionCorta}</small>` : ''}
                            </td>
                            <td>${clienteLink}</td>
                            <td><span class="type-badge">${tarea.tipo_tarea}</span></td>
                            <td><span class="priority-badge priority-${tarea.prioridad.toLowerCase().replace(' ', '-')}">${tarea.prioridad}</span></td>
                            <td><span class="status-badge status-${tarea.estado.toLowerCase().replace(' ', '-')}">${tarea.estado}</span></td>
                            <td>${tarea.responsable}</td>
                            <td>${new Date(tarea.fecha_creacion).toLocaleDateString('es-ES')}</td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="8" style="text-align: center; color: #666; padding: 40px;">
                            No hay tareas para mostrar
                        </td>
                    </tr>
                `;
            }
            document.getElementById('tasksTableBody').innerHTML = html;
        }

        // Auto-actualizar cada 60 segundos
        setInterval(actualizarDatos, 60000);

        // Ejecutar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sistema de reportes cargado correctamente');
            console.log('Total de tareas:', todasLasTareas.length);
            console.log('Cliente seleccionado:', clienteSeleccionado);
            
            // Inicializar período personalizado si está seleccionado
            updatePeriod();
        });
    </script>
</body>
</html>