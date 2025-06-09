<?php
require_once 'config.php';
verificarLogin();

// Función para obtener todos los clientes con sus tareas y reuniones
function obtenerClientesConTareasYReuniones() {
    try {
        $pdo = conectarDB();
        
        // Obtener todos los clientes
        $stmt = $pdo->query("SELECT * FROM clientes ORDER BY nombre_cliente");
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada cliente, obtener sus tareas y reuniones
        foreach ($clientes as &$cliente) {
            // Obtener tareas
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM tareas 
                    WHERE cliente_id = ? 
                    ORDER BY fecha_creacion DESC
                ");
                $stmt->execute([$cliente['id']]);
                $cliente['tareas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $cliente['tareas'] = [];
            }
            
            // Obtener reuniones
            try {
                $stmt = $pdo->prepare("
                    SELECT *, 
                           titulo as tema,
                           fecha_inicio as fecha_reunion,
                           google_meet_link as link_reunion,
                           descripcion as agenda,
                           creado_por as organizador,
                           TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_fin) as duracion
                    FROM reuniones 
                    WHERE cliente_id = ? 
                    ORDER BY fecha_inicio ASC
                ");
                $stmt->execute([$cliente['id']]);
                $cliente['reuniones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $cliente['reuniones'] = [];
            }
            
            // Calcular estadísticas de tareas
            $cliente['total_tareas'] = count($cliente['tareas']);
            $cliente['tareas_completadas'] = count(array_filter($cliente['tareas'], function($t) { 
                return isset($t['estado']) && $t['estado'] === 'Completado'; 
            }));
            $cliente['tareas_pendientes'] = $cliente['total_tareas'] - $cliente['tareas_completadas'];
            $cliente['progreso'] = $cliente['total_tareas'] > 0 ? round(($cliente['tareas_completadas'] / $cliente['total_tareas']) * 100) : 0;
            
            // Calcular estadísticas de reuniones
            $cliente['total_reuniones'] = count($cliente['reuniones']);
            $cliente['reuniones_futuras'] = count(array_filter($cliente['reuniones'], function($r) { 
                return isset($r['fecha_reunion']) && strtotime($r['fecha_reunion']) > time(); 
            }));
            $cliente['reuniones_pasadas'] = $cliente['total_reuniones'] - $cliente['reuniones_futuras'];
            
            // Próxima reunión
            $reuniones_futuras = array_filter($cliente['reuniones'], function($r) { 
                return isset($r['fecha_reunion']) && strtotime($r['fecha_reunion']) > time(); 
            });
            $cliente['proxima_reunion'] = !empty($reuniones_futuras) ? reset($reuniones_futuras) : null;
        }
        
        return $clientes;
        
    } catch (Exception $e) {
        error_log("Error en obtenerClientesConTareasYReuniones: " . $e->getMessage());
        return [];
    }
}

// Función para obtener estadísticas generales
function obtenerEstadisticasGenerales() {
    try {
        $pdo = conectarDB();
        $stats = [];
        
        // Total de clientes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
        $stats['total_clientes'] = $stmt->fetch()['total'];
        
        // Clientes con tareas activas
        try {
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT cliente_id) as total 
                FROM tareas 
                WHERE cliente_id IS NOT NULL AND estado != 'Completado'
            ");
            $stats['clientes_activos'] = $stmt->fetch()['total'];
        } catch (Exception $e) {
            $stats['clientes_activos'] = 0;
        }
        
        // Cliente con más tareas
        try {
            $stmt = $pdo->query("
                SELECT c.nombre_cliente, COUNT(t.id) as total_tareas
                FROM clientes c
                LEFT JOIN tareas t ON c.id = t.cliente_id
                GROUP BY c.id, c.nombre_cliente
                ORDER BY total_tareas DESC
                LIMIT 1
            ");
            $top_cliente = $stmt->fetch();
            $stats['top_cliente'] = $top_cliente ? $top_cliente : ['nombre_cliente' => 'N/A', 'total_tareas' => 0];
        } catch (Exception $e) {
            $stats['top_cliente'] = ['nombre_cliente' => 'N/A', 'total_tareas' => 0];
        }
        
        // Total de reuniones programadas
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM reuniones 
                WHERE fecha_inicio > NOW()
            ");
            $stats['reuniones_futuras'] = $stmt->fetch()['total'];
        } catch (Exception $e) {
            $stats['reuniones_futuras'] = 0;
        }
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticasGenerales: " . $e->getMessage());
        return [
            'total_clientes' => 0,
            'clientes_activos' => 0,
            'top_cliente' => ['nombre_cliente' => 'N/A', 'total_tareas' => 0],
            'reuniones_futuras' => 0
        ];
    }
}

$clientes = obtenerClientesConTareasYReuniones();
$estadisticas = obtenerEstadisticasGenerales();

// Filtros
$filtro_estado = $_GET['filtro_estado'] ?? 'todos';
$buscar = $_GET['buscar'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema Kanban</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #121A28;
        }

        .header {
            background: #121A28;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #F09146;
        }

        .nav-menu {
            background: white;
            padding: 15px 0;
            border-bottom: 3px solid #F09146;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: #F09146;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: #A23004;
        }

        .nav-btn.active {
            background: #A23004;
        }

        .logout-btn {
            background: #A23004;
            margin-left: auto;
        }

        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title h2 {
            color: #121A28;
            font-size: 32px;
            margin-bottom: 10px;
        }

        /* Estadísticas generales */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 4px solid #F09146;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #F09146;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Filtros y búsqueda */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #F09146;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-select {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            cursor: pointer;
        }

        .btn-add {
            background: #28a745;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-add:hover {
            background: #218838;
        }

        /* Lista de clientes */
        .clientes-grid {
            display: grid;
            gap: 25px;
        }

        .cliente-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .cliente-card:hover {
            transform: translateY(-2px);
        }

        .cliente-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .cliente-info h3 {
            color: #121A28;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .cliente-info .responsable {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .cliente-info .fecha {
            color: #999;
            font-size: 14px;
        }

        .cliente-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            text-align: center;
        }

        .stat-mini {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }

        .stat-mini-number {
            font-size: 24px;
            font-weight: bold;
            color: #F09146;
            margin-bottom: 5px;
        }

        .stat-mini-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #F09146, #A23004);
            transition: width 0.3s;
        }

        /* Botón de reunión */
        .btn-reunion {
            background: linear-gradient(135deg, #4285f4, #1a73e8);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            display: block;
            transition: all 0.3s;
            line-height: 1.2;
        }

        .btn-reunion:hover {
            background: linear-gradient(135deg, #1a73e8, #1557b0);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
        }

        .stat-mini.action-button {
            background: transparent;
            padding: 0;
        }

        .stat-mini.action-button .btn-reunion {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
        }

        /* Próxima reunión */
        .proxima-reunion {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: center;
        }

        .proxima-reunion-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .proxima-reunion-info {
            font-size: 14px;
            line-height: 1.4;
        }

        .reunion-fecha {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .reunion-tema {
            opacity: 0.9;
            font-size: 13px;
        }

        .no-reunion {
            background: #6c757d;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: center;
            font-size: 12px;
            opacity: 0.8;
        }

        /* Secciones de contenido */
        .content-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .section {
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
        }

        .section-header {
            background: #F09146;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-weight: bold;
            font-size: 16px;
        }

        .section-count {
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        .btn-toggle {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .section-content {
            display: none;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .section-content.show {
            display: block;
        }

        /* Items de tareas */
        .tareas-list {
            display: grid;
            gap: 15px;
        }

        .tarea-item {
            background: white;
            border-left: 4px solid #F09146;
            padding: 15px;
            border-radius: 8px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .tarea-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .tarea-title {
            font-weight: bold;
            color: #121A28;
            margin-bottom: 8px;
        }

        .tarea-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 12px;
            color: #666;
        }

        .tarea-tipo {
            background: #F09146;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            display: inline-block;
            margin-top: 8px;
        }

        .estado-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .estado-por-hacer { background: #6c757d; color: white; }
        .estado-en-progreso { background: #F09146; color: white; }
        .estado-en-revision { background: #ffc107; color: #212529; }
        .estado-completado { background: #28a745; color: white; }

        /* Items de reuniones */
        .reuniones-list {
            display: grid;
            gap: 15px;
        }

        .reunion-item {
            background: white;
            border-left: 4px solid #4285f4;
            padding: 15px;
            border-radius: 8px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .reunion-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .reunion-item.pasada {
            opacity: 0.7;
            border-left-color: #6c757d;
        }

        .reunion-title {
            font-weight: bold;
            color: #121A28;
            margin-bottom: 8px;
        }

        .reunion-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 12px;
            color: #666;
        }

        .reunion-estado {
            background: #4285f4;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            display: inline-block;
            margin-top: 8px;
        }

        .reunion-estado.pasada {
            background: #6c757d;
        }

        .reunion-estado.hoy {
            background: #28a745;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .no-items {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 30px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Modal para detalles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-overlay.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.4s ease-out;
            position: relative;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #F09146, #A23004);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 25px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .modal-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-right: 50px;
        }

        .modal-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }

        .modal-body {
            padding: 40px;
        }

        .tarea-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #F09146;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .detail-value {
            font-size: 16px;
            color: #121A28;
            font-weight: 500;
        }

        .detail-value.large {
            font-size: 20px;
            font-weight: bold;
        }

        .descripcion-section {
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }

        .descripcion-title {
            font-size: 18px;
            font-weight: bold;
            color: #121A28;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .descripcion-content {
            color: #666;
            line-height: 1.6;
            font-size: 16px;
        }

        .descripcion-empty {
            color: #999;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .cliente-header {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .content-sections {
                grid-template-columns: 1fr;
            }

            .cliente-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>📋 Sistema Kanban</h1>
            <div class="welcome-msg">
                <?php echo htmlspecialchars($_SESSION['bienvenido']); ?>
            </div>
        </div>
    </header>

    <nav class="nav-menu">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-btn">🏠 Dashboard</a>
            <a href="clientes.php" class="nav-btn active">👥 Clientes</a>
            <a href="registrar_cliente.php" class="nav-btn">➕ Nuevo Cliente</a>
            <a href="tareas_calendar.php" class="nav-btn">📋 Tareas</a>
            <a href="notas.php" class="nav-btn">📝 Notas</a>
            <a href="google_auth.php" class="nav-btn">📅 Google Calendar</a>
            <a href="logout.php" class="nav-btn logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-title">
            <h2>👥 Gestión de Clientes</h2>
            <p>Vista completa de clientes, tareas y reuniones</p>
        </div>

        <!-- Estadísticas generales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_clientes']; ?></div>
                <div class="stat-label">Total Clientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['clientes_activos']; ?></div>
                <div class="stat-label">Clientes Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['reuniones_futuras']; ?></div>
                <div class="stat-label">Reuniones Programadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($clientes); ?></div>
                <div class="stat-label">Mostrando</div>
            </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar cliente..." 
                           value="<?php echo htmlspecialchars($buscar); ?>">
                    <span class="search-icon">🔍</span>
                </div>
                
                <select name="filtro_estado" class="filter-select" onchange="this.form.submit()">
                    <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos los clientes</option>
                    <option value="activos" <?php echo $filtro_estado === 'activos' ? 'selected' : ''; ?>>Con tareas activas</option>
                    <option value="con_reuniones" <?php echo $filtro_estado === 'con_reuniones' ? 'selected' : ''; ?>>Con reuniones programadas</option>
                    <option value="sin_tareas" <?php echo $filtro_estado === 'sin_tareas' ? 'selected' : ''; ?>>Sin tareas</option>
                </select>
                
                <a href="registrar_cliente.php" class="btn-add">➕ Nuevo Cliente</a>
            </form>
        </div>

        <!-- Lista de clientes -->
        <div class="clientes-grid">
            <?php if (empty($clientes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3>No hay clientes registrados</h3>
                    <p>Comienza agregando tu primer cliente al sistema</p>
                    <a href="registrar_cliente.php" class="btn-add" style="margin-top: 20px;">➕ Agregar Primer Cliente</a>
                </div>
            <?php else: ?>
                <?php foreach ($clientes as $cliente): ?>
                    <?php
                    // Aplicar filtros
                    if (!empty($buscar) && stripos($cliente['nombre_cliente'], $buscar) === false) continue;
                    
                    switch ($filtro_estado) {
                        case 'activos':
                            if ($cliente['tareas_pendientes'] === 0) continue 2;
                            break;
                        case 'con_reuniones':
                            if ($cliente['reuniones_futuras'] === 0) continue 2;
                            break;
                        case 'sin_tareas':
                            if ($cliente['total_tareas'] > 0) continue 2;
                            break;
                    }
                    ?>
                    
                    <div class="cliente-card">
                        <div class="cliente-header">
                            <div class="cliente-info">
                                <h3>🏢 <?php echo htmlspecialchars($cliente['nombre_cliente']); ?></h3>
                                <div class="responsable">👤 <?php echo htmlspecialchars($cliente['responsable']); ?></div>
                                <div class="fecha">📅 Cliente desde: <?php echo date('d/m/Y', strtotime($cliente['fecha_creacion'])); ?></div>
                                <?php if ($cliente['total_tareas'] > 0): ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $cliente['progreso']; ?>%"></div>
                                    </div>
                                    <small><?php echo $cliente['progreso']; ?>% completado</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cliente-stats">
                                <div class="stat-mini">
                                    <div class="stat-mini-number"><?php echo $cliente['total_tareas']; ?></div>
                                    <div class="stat-mini-label">Total Tareas</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="stat-mini-number"><?php echo $cliente['tareas_pendientes']; ?></div>
                                    <div class="stat-mini-label">Pendientes</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="stat-mini-number"><?php echo $cliente['total_reuniones']; ?></div>
                                    <div class="stat-mini-label">Reuniones</div>
                                </div>
                                <div class="stat-mini action-button">
                                    <a href="agendar_reunion.php?cliente=<?php echo $cliente['id']; ?>" 
                                       class="btn-reunion">
                                        📹 AGENDAR<br>REUNIÓN
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Próxima reunión -->
                        <?php if ($cliente['proxima_reunion']): ?>
                            <div class="proxima-reunion">
                                <div class="proxima-reunion-title">🎯 PRÓXIMA REUNIÓN</div>
                                <div class="proxima-reunion-info">
                                    <div class="reunion-fecha">
                                        📅 <?php echo date('d/m/Y H:i', strtotime($cliente['proxima_reunion']['fecha_reunion'])); ?>
                                    </div>
                                    <div class="reunion-tema">
                                        📝 <?php echo htmlspecialchars($cliente['proxima_reunion']['tema'] ?? 'Reunión programada'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-reunion">
                                📅 No hay reuniones programadas
                            </div>
                        <?php endif; ?>

                        <!-- Secciones de contenido -->
                        <div class="content-sections">
                            <!-- Tareas -->
                            <div class="section">
                                <div class="section-header">
                                    <div>
                                        <span class="section-title">📋 Tareas</span>
                                        <span class="section-count"><?php echo $cliente['total_tareas']; ?></span>
                                    </div>
                                    <button type="button" class="btn-toggle" onclick="toggleSection('tareas-<?php echo $cliente['id']; ?>')">
                                        Ver
                                    </button>
                                </div>
                                <div class="section-content" id="tareas-<?php echo $cliente['id']; ?>">
                                    <?php if (!empty($cliente['tareas'])): ?>
                                        <div class="tareas-list">
                                            <?php foreach (array_slice($cliente['tareas'], 0, 5) as $tarea): ?>
                                                <div class="tarea-item" onclick="abrirModalTarea(<?php echo htmlspecialchars(json_encode($tarea), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <div class="tarea-title"><?php echo htmlspecialchars($tarea['nombre_tarea']); ?></div>
                                                    <div class="tarea-meta">
                                                        <div>👤 <?php echo htmlspecialchars($tarea['responsable']); ?></div>
                                                        <div>⚡ <?php echo htmlspecialchars($tarea['prioridad'] ?? 'Media'); ?></div>
                                                        <div>📅 <?php echo date('d/m/Y', strtotime($tarea['fecha_creacion'])); ?></div>
                                                        <div>
                                                            <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $tarea['estado'] ?? 'Por hacer')); ?>">
                                                                <?php echo htmlspecialchars($tarea['estado'] ?? 'Por hacer'); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <span class="tarea-tipo"><?php echo htmlspecialchars($tarea['tipo_tarea'] ?? 'General'); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($cliente['tareas']) > 5): ?>
                                                <div style="text-align: center; padding: 10px; color: #666; font-size: 12px;">
                                                    ... y <?php echo count($cliente['tareas']) - 5; ?> tareas más
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-items">
                                            📝 No hay tareas asignadas
                                            <br><small><a href="tareas_calendar.php" style="color: #F09146;">Crear primera tarea</a></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Reuniones -->
                            <div class="section">
                                <div class="section-header">
                                    <div>
                                        <span class="section-title">📅 Reuniones</span>
                                        <span class="section-count"><?php echo $cliente['total_reuniones']; ?></span>
                                    </div>
                                    <button type="button" class="btn-toggle" onclick="toggleSection('reuniones-<?php echo $cliente['id']; ?>')">
                                        Ver
                                    </button>
                                </div>
                                <div class="section-content" id="reuniones-<?php echo $cliente['id']; ?>">
                                    <?php if (!empty($cliente['reuniones'])): ?>
                                        <div class="reuniones-list">
                                            <?php foreach (array_slice($cliente['reuniones'], 0, 5) as $reunion): ?>
                                                <?php 
                                                $fecha_reunion = strtotime($reunion['fecha_reunion']);
                                                $es_pasada = $fecha_reunion < time();
                                                $es_hoy = date('Y-m-d', $fecha_reunion) === date('Y-m-d');
                                                ?>
                                                <div class="reunion-item <?php echo $es_pasada ? 'pasada' : ''; ?>" 
                                                     onclick="abrirModalReunion(<?php echo htmlspecialchars(json_encode($reunion), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <div class="reunion-title">
                                                        <?php echo htmlspecialchars($reunion['tema'] ?? 'Reunión programada'); ?>
                                                    </div>
                                                    <div class="reunion-meta">
                                                        <div>📅 <?php echo date('d/m/Y', $fecha_reunion); ?></div>
                                                        <div>🕐 <?php echo date('H:i', $fecha_reunion); ?></div>
                                                        <div>⏱️ <?php echo $reunion['duracion'] ?? '30'; ?> min</div>
                                                        <div>📍 <?php echo $reunion['google_meet_link'] ? 'Google Meet' : 'Ubicación TBD'; ?></div>
                                                    </div>
                                                    <span class="reunion-estado <?php echo $es_pasada ? 'pasada' : ($es_hoy ? 'hoy' : ''); ?>">
                                                        <?php 
                                                        if ($es_hoy) echo '🔥 HOY';
                                                        elseif ($es_pasada) echo '✅ Realizada';
                                                        else echo '📅 Programada';
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($cliente['reuniones']) > 5): ?>
                                                <div style="text-align: center; padding: 10px; color: #666; font-size: 12px;">
                                                    ... y <?php echo count($cliente['reuniones']) - 5; ?> reuniones más
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-items">
                                            📅 No hay reuniones programadas
                                            <br><small><a href="agendar_reunion.php?cliente=<?php echo $cliente['id']; ?>" style="color: #4285f4;">Agendar primera reunión</a></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para detalles de tarea -->
    <div class="modal-overlay" id="tareaModal">
        <div class="modal-content">
            <div class="modal-header">
                <button class="modal-close" onclick="cerrarModalTarea()">×</button>
                <h2 class="modal-title" id="modalTareaTitle">Detalles de la Tarea</h2>
                <div class="modal-subtitle" id="modalTareaSubtitle">Información completa</div>
            </div>
            
            <div class="modal-body">
                <div class="tarea-details-grid" id="tareaDetailsGrid">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
                
                <div class="descripcion-section">
                    <h3 class="descripcion-title">📝 Descripción</h3>
                    <div class="descripcion-content" id="tareaDescripcion">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de reunión -->
    <div class="modal-overlay" id="reunionModal">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4285f4, #1a73e8);">
                <button class="modal-close" onclick="cerrarModalReunion()">×</button>
                <h2 class="modal-title" id="modalReunionTitle">Detalles de la Reunión</h2>
                <div class="modal-subtitle" id="modalReunionSubtitle">Información de la reunión</div>
            </div>
            
            <div class="modal-body">
                <div class="tarea-details-grid" id="reunionDetailsGrid">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
                
                <div class="descripcion-section">
                    <h3 class="descripcion-title">📝 Descripción / Agenda</h3>
                    <div class="descripcion-content" id="reunionAgenda">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let tareaActual = null;
        let reunionActual = null;

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const btn = section.previousElementSibling.querySelector('.btn-toggle');
            
            section.classList.toggle('show');
            
            if (section.classList.contains('show')) {
                btn.textContent = 'Ocultar';
            } else {
                btn.textContent = 'Ver';
            }
        }

        function abrirModalTarea(tarea) {
            tareaActual = tarea;
            
            // Llenar título
            document.getElementById('modalTareaTitle').textContent = tarea.nombre_tarea || 'Tarea sin título';
            document.getElementById('modalTareaSubtitle').textContent = `Tarea #${tarea.id} - ${tarea.tipo_tarea || 'General'}`;
            
            // Crear grid de detalles
            const grid = document.getElementById('tareaDetailsGrid');
            grid.innerHTML = '';
            
            // Helper functions
            function getPrioridadIcon(prioridad) {
                switch(prioridad?.toLowerCase()) {
                    case 'alta': return '🔴';
                    case 'media': return '🟡';
                    case 'baja': return '🟢';
                    default: return '⚪';
                }
            }

            function getEstadoIcon(estado) {
                switch(estado?.toLowerCase().replace(' ', '-')) {
                    case 'por-hacer': return '📋';
                    case 'en-progreso': return '⚡';
                    case 'en-revision': return '👀';
                    case 'completado': return '✅';
                    default: return '📝';
                }
            }

            function formatearFecha(fecha) {
                if (!fecha) return 'No especificada';
                const date = new Date(fecha);
                return date.toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
            }

            // Crear cards de detalles
            const detalles = [
                {
                    label: 'Estado',
                    value: `<div class="estado-detail">
                        ${getEstadoIcon(tarea.estado)}
                        <span class="estado-badge estado-${(tarea.estado || 'por-hacer').toLowerCase().replace(' ', '-')}">${tarea.estado || 'Por hacer'}</span>
                    </div>`,
                    class: 'large'
                },
                {
                    label: 'Prioridad',
                    value: `<div class="prioridad-detail prioridad-${(tarea.prioridad || 'media').toLowerCase()}">
                        ${getPrioridadIcon(tarea.prioridad)}
                        <strong>${tarea.prioridad || 'Media'}</strong>
                    </div>`,
                    class: 'large'
                },
                {
                    label: 'Responsable',
                    value: `👤 ${tarea.responsable || 'No asignado'}`
                },
                {
                    label: 'Tipo de Tarea',
                    value: `📂 ${tarea.tipo_tarea || 'General'}`
                },
                {
                    label: 'Fecha de Creación',
                    value: `📅 ${formatearFecha(tarea.fecha_creacion)}`
                },
                {
                    label: 'Fecha Límite',
                    value: tarea.fecha_limite ? `⏰ ${formatearFecha(tarea.fecha_limite)}` : '⏰ Sin fecha límite'
                }
            ];

            // Renderizar cards
            detalles.forEach(detalle => {
                const card = document.createElement('div');
                card.className = 'detail-card';
                card.innerHTML = `
                    <div class="detail-label">${detalle.label}</div>
                    <div class="detail-value ${detalle.class || ''}">${detalle.value}</div>
                `;
                grid.appendChild(card);
            });
            
            // Llenar descripción
            const descripcionDiv = document.getElementById('tareaDescripcion');
            if (tarea.descripcion && tarea.descripcion.trim()) {
                descripcionDiv.innerHTML = `<p>${tarea.descripcion.replace(/\n/g, '<br>')}</p>`;
            } else {
                descripcionDiv.innerHTML = '<div class="descripcion-empty">📝 No hay descripción disponible para esta tarea</div>';
            }
            
            // Mostrar modal
            document.getElementById('tareaModal').classList.add('show');
        }

        function abrirModalReunion(reunion) {
            if (!reunion) return;
            
            reunionActual = reunion;
            
            // Llenar título
            document.getElementById('modalReunionTitle').textContent = reunion.tema || reunion.titulo || 'Reunión programada';
            document.getElementById('modalReunionSubtitle').textContent = `Reunión #${reunion.id}`;
            
            // Crear grid de detalles
            const grid = document.getElementById('reunionDetailsGrid');
            grid.innerHTML = '';
            
            function formatearFechaCompleta(fecha) {
                if (!fecha) return 'No especificada';
                const date = new Date(fecha);
                return date.toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function formatearFechaHora(fecha) {
                if (!fecha) return 'No especificada';
                const date = new Date(fecha);
                return date.toLocaleTimeString('es-ES', { 
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function getEstadoReunion(fecha) {
                if (!fecha) return '❓ Sin fecha';
                const fechaReunion = new Date(fecha);
                const ahora = new Date();
                const hoy = new Date().toDateString();
                
                if (fechaReunion.toDateString() === hoy) {
                    return '🔥 HOY';
                } else if (fechaReunion < ahora) {
                    return '✅ Realizada';
                } else {
                    return '📅 Programada';
                }
            }

            // Crear cards de detalles
            const detalles = [
                {
                    label: 'Estado',
                    value: getEstadoReunion(reunion.fecha_reunion || reunion.fecha_inicio),
                    class: 'large'
                },
                {
                    label: 'Fecha y Hora',
                    value: `📅 ${formatearFechaCompleta(reunion.fecha_reunion || reunion.fecha_inicio)}`,
                    class: 'large'
                },
                {
                    label: 'Duración',
                    value: `⏱️ ${reunion.duracion || '30'} minutos`
                },
                {
                    label: 'Organizador',
                    value: `👤 ${reunion.organizador || reunion.creado_por || 'No especificado'}`
                }
            ];

            // Agregar hora de fin si existe
            if (reunion.fecha_fin) {
                detalles.push({
                    label: 'Hora de Fin',
                    value: `🕐 ${formatearFechaHora(reunion.fecha_fin)}`
                });
            }

            // Agregar emails de invitados si existen
            if (reunion.emails_invitados) {
                detalles.push({
                    label: 'Invitados',
                    value: `👥 ${reunion.emails_invitados.split(',').length} invitados`
                });
            }

            // Agregar enlace de Google Meet si existe
            if (reunion.google_meet_link || reunion.link_reunion) {
                detalles.push({
                    label: 'Enlace de Reunión',
                    value: `<a href="${reunion.google_meet_link || reunion.link_reunion}" target="_blank" style="color: #4285f4; text-decoration: none;">🔗 Unirse a Google Meet</a>`
                });
            }

            // Renderizar cards
            detalles.forEach(detalle => {
                const card = document.createElement('div');
                card.className = 'detail-card';
                card.innerHTML = `
                    <div class="detail-label">${detalle.label}</div>
                    <div class="detail-value ${detalle.class || ''}">${detalle.value}</div>
                `;
                grid.appendChild(card);
            });
            
            // Llenar descripción/agenda
            const agendaDiv = document.getElementById('reunionAgenda');
            const descripcion = reunion.agenda || reunion.descripcion;
            if (descripcion && descripcion.trim()) {
                agendaDiv.innerHTML = `<p>${descripcion.replace(/\n/g, '<br>')}</p>`;
            } else {
                agendaDiv.innerHTML = '<div class="descripcion-empty">📝 No hay descripción o agenda disponible para esta reunión</div>';
            }
            
            // Mostrar modal
            document.getElementById('reunionModal').classList.add('show');
        }

        function cerrarModalTarea() {
            document.getElementById('tareaModal').classList.remove('show');
            tareaActual = null;
        }

        function cerrarModalReunion() {
            document.getElementById('reunionModal').classList.remove('show');
            reunionActual = null;
        }

        // Cerrar modales al hacer clic fuera
        document.getElementById('tareaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalTarea();
            }
        });

        document.getElementById('reunionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalReunion();
            }
        });

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalTarea();
                cerrarModalReunion();
            }
        });

        // Auto-submit del formulario de búsqueda con delay
        let searchTimeout;
        const searchInput = document.querySelector('input[name="buscar"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }
    </script>
</body>
</html>