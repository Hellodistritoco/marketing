<?php
require_once 'config.php';
verificarLogin();

$mensaje = '';
$tipo_mensaje = '';

// Obtener todos los clientes para el selector
$clientes = obtenerClientes();

// Obtener notas organizadas por cliente
function obtenerNotasPorCliente() {
    $pdo = conectarDB();
    
    // Notas generales (sin cliente asociado)
    $stmt = $pdo->query("
        SELECT * FROM notas 
        WHERE cliente_id IS NULL 
        ORDER BY fecha_actualizacion DESC
    ");
    $notas_generales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Notas por cliente
    $stmt = $pdo->query("
        SELECT n.*, c.nombre_cliente, c.responsable
        FROM notas n 
        INNER JOIN clientes c ON n.cliente_id = c.id 
        ORDER BY c.nombre_cliente, n.fecha_actualizacion DESC
    ");
    $notas_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar notas por cliente
    $notas_por_cliente = [];
    foreach ($notas_clientes as $nota) {
        $cliente_id = $nota['cliente_id'];
        if (!isset($notas_por_cliente[$cliente_id])) {
            $notas_por_cliente[$cliente_id] = [
                'cliente' => [
                    'id' => $cliente_id,
                    'nombre' => $nota['nombre_cliente'],
                    'responsable' => $nota['responsable']
                ],
                'notas' => []
            ];
        }
        $notas_por_cliente[$cliente_id]['notas'][] = $nota;
    }
    
    return [
        'generales' => $notas_generales,
        'por_cliente' => $notas_por_cliente
    ];
}

// Obtener estadísticas de notas
function obtenerEstadisticasNotas() {
    $pdo = conectarDB();
    
    $stats = [];
    
    // Total de notas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notas");
    $stats['total_notas'] = $stmt->fetch()['total'];
    
    // Notas generales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notas WHERE cliente_id IS NULL");
    $stats['notas_generales'] = $stmt->fetch()['total'];
    
    // Notas por cliente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notas WHERE cliente_id IS NOT NULL");
    $stats['notas_clientes'] = $stmt->fetch()['total'];
    
    // Clientes con notas
    $stmt = $pdo->query("SELECT COUNT(DISTINCT cliente_id) as total FROM notas WHERE cliente_id IS NOT NULL");
    $stats['clientes_con_notas'] = $stmt->fetch()['total'];
    
    return $stats;
}

// Editar nota
if (isset($_GET['editar']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nota_id = (int)$_GET['editar'];
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    
    if (!empty($titulo)) {
        try {
            $pdo = conectarDB();
            $stmt = $pdo->prepare("UPDATE notas SET titulo = ?, cliente_id = ?, contenido = ? WHERE id = ?");
            $stmt->execute([$titulo, $cliente_id, $contenido, $nota_id]);
            
            $mensaje = 'Nota actualizada exitosamente';
            $tipo_mensaje = 'success';
            
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar nota: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'El título es obligatorio';
        $tipo_mensaje = 'error';
    }
}

// Obtener nota específica para editar
$nota_editar = null;
if (isset($_GET['editar'])) {
    $nota_id = (int)$_GET['editar'];
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("
            SELECT n.*, c.nombre_cliente 
            FROM notas n 
            LEFT JOIN clientes c ON n.cliente_id = c.id 
            WHERE n.id = ?
        ");
        $stmt->execute([$nota_id]);
        $nota_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = 'Error al cargar nota: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Eliminar nota
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("DELETE FROM notas WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensaje = 'Nota eliminada exitosamente';
        $tipo_mensaje = 'success';
    } catch (PDOException $e) {
        $mensaje = 'Error al eliminar nota: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

$notas_data = obtenerNotasPorCliente();
$estadisticas = obtenerEstadisticasNotas();

// Filtros
$filtro_cliente = $_GET['filtro_cliente'] ?? 'todos';
$buscar = $_GET['buscar'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas por Cliente - Sistema Kanban</title>
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

        /* Estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 4px solid #F09146;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #F09146;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Filtros */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr auto;
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
            min-width: 200px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 5px solid #F09146;
            height: fit-content;
        }

        .notes-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 5px solid #F09146;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #121A28;
            font-weight: bold;
            font-size: 16px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #F09146;
        }

        .btn-submit {
            background: #F09146;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background: #A23004;
        }

        .mensaje {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-icon {
            text-align: center;
            font-size: 50px;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        /* Secciones de notas */
        .notes-section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #F09146;
        }

        .section-title {
            color: #121A28;
            font-size: 20px;
            font-weight: bold;
        }

        .notes-count {
            background: #F09146;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .cliente-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0 15px 0;
            padding: 12px 20px;
            background: #e3f2fd;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
        }

        .cliente-info h4 {
            color: #121A28;
            margin-bottom: 5px;
        }

        .cliente-responsable {
            color: #666;
            font-size: 14px;
        }

        .note-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
            transition: all 0.3s;
            cursor: pointer;
        }

        .note-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #F09146;
        }

        .note-card.client-note {
            border-left: 4px solid #2196f3;
        }

        .note-card.general-note {
            border-left: 4px solid #F09146;
        }

        .note-title {
            font-weight: bold;
            color: #121A28;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .note-preview {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .note-content {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .note-meta {
            font-size: 12px;
            color: #999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .note-actions {
            display: flex;
            gap: 10px;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .read-more-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #F09146;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .note-card:hover .read-more-indicator {
            opacity: 1;
        }

        /* Modal para ver nota completa */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 0;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            animation: slideIn 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #F09146, #A23004);
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .modal-subtitle {
            opacity: 0.9;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .client-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
        }

        .modal-body {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
        }

        .note-full-content {
            color: #444;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 25px;
            white-space: pre-wrap;
        }

        .note-empty-content {
            color: #999;
            font-style: italic;
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .modal-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-meta {
            color: #666;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-close {
            background: #6c757d;
            color: white;
        }

        .btn-close:hover {
            background: #5a6268;
        }

        .btn-delete-modal {
            background: #dc3545;
            color: white;
        }

        .btn-delete-modal:hover {
            background: #c82333;
        }

        .delete-confirmation {
            display: none;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .delete-confirmation.show {
            display: block;
        }

        .delete-confirmation p {
            color: #856404;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .delete-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-confirm-delete {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-cancel-delete {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .edit-form {
            display: none;
            padding: 30px;
        }

        .edit-form.show {
            display: block;
        }

        .edit-form-group {
            margin-bottom: 20px;
        }

        .edit-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #121A28;
            font-weight: bold;
            font-size: 16px;
        }

        .edit-form-group input,
        .edit-form-group select,
        .edit-form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .edit-form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .edit-form-group input:focus,
        .edit-form-group select:focus,
        .edit-form-group textarea:focus {
            outline: none;
            border-color: #F09146;
        }

        .edit-form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn-save-edit {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-save-edit:hover {
            background: #218838;
        }

        .btn-cancel-edit {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-cancel-edit:hover {
            background: #5a6268;
        }

        .btn-edit-modal {
            background: #007bff;
            color: white;
        }

        .btn-edit-modal:hover {
            background: #0056b3;
        }

        .no-notes {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .required {
            color: #A23004;
        }

        .client-tag {
            display: inline-block;
            background: #2196f3;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 8px;
        }

        .general-tag {
            display: inline-block;
            background: #F09146;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
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
            <a href="clientes.php" class="nav-btn">👥 Clientes</a>
            <a href="registrar_cliente.php" class="nav-btn">➕ Nuevo Cliente</a>
            <a href="tareas_calendar.php" class="nav-btn">📋 Tareas</a>
            <a href="notas.php" class="nav-btn active">📝 Notas</a>
            <a href="google_auth.php" class="nav-btn">📅 Google Calendar</a>
            <a href="logout.php" class="nav-btn logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-content">
        <a href="dashboard.php" class="back-btn">← Volver al Dashboard</a>
        
        <div class="page-title">
            <h2>📝 Notas por Cliente</h2>
            <p>Organiza tus notas por cliente o como notas generales</p>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_notas']; ?></div>
                <div class="stat-label">Total Notas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['notas_generales']; ?></div>
                <div class="stat-label">Notas Generales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['notas_clientes']; ?></div>
                <div class="stat-label">Notas de Clientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['clientes_con_notas']; ?></div>
                <div class="stat-label">Clientes con Notas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar en notas..." 
                           value="<?php echo htmlspecialchars($buscar); ?>">
                    <span class="search-icon">🔍</span>
                </div>
                
                <select name="filtro_cliente" class="filter-select" onchange="this.form.submit()">
                    <option value="todos" <?php echo $filtro_cliente === 'todos' ? 'selected' : ''; ?>>Todas las notas</option>
                    <option value="generales" <?php echo $filtro_cliente === 'generales' ? 'selected' : ''; ?>>Solo notas generales</option>
                    <option value="clientes" <?php echo $filtro_cliente === 'clientes' ? 'selected' : ''; ?>>Solo notas de clientes</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="cliente_<?php echo $cliente['id']; ?>" 
                                <?php echo $filtro_cliente === 'cliente_' . $cliente['id'] ? 'selected' : ''; ?>>
                            📋 <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Formulario para agregar/editar nota -->
            <div class="form-container">
                <div class="form-icon">📝</div>
                <h3 style="text-align: center; margin-bottom: 20px; color: #121A28;">
                    <?php echo $nota_editar ? 'Editar Nota' : 'Nueva Nota'; ?>
                </h3>
                
                <?php if ($nota_editar): ?>
                    <div style="text-align: center; margin-bottom: 20px; padding: 10px; background: #e7f3ff; border-radius: 8px;">
                        <strong>Editando:</strong> "<?php echo htmlspecialchars($nota_editar['titulo']); ?>"
                        <br><small><a href="notas.php" style="color: #007bff;">← Cancelar edición</a></small>
                    </div>
                <?php endif; ?>
                
                <form method="POST" <?php echo $nota_editar ? 'action="?editar=' . $nota_editar['id'] . '"' : ''; ?>>
                    <div class="form-group">
                        <label for="cliente_id">Asociar a Cliente:</label>
                        <select id="cliente_id" name="cliente_id">
                            <option value="" <?php echo ($nota_editar && !$nota_editar['cliente_id']) ? 'selected' : ''; ?>>
                                📋 Nota General (sin cliente)
                            </option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" 
                                        <?php echo ($nota_editar && $nota_editar['cliente_id'] == $cliente['id']) ? 'selected' : ''; ?>>
                                    🏢 <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titulo">Título: <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" required 
                               placeholder="Título de la nota"
                               value="<?php echo $nota_editar ? htmlspecialchars($nota_editar['titulo']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="contenido">Contenido:</label>
                        <textarea id="contenido" name="contenido" 
                                  placeholder="Escribe el contenido de tu nota..."><?php echo $nota_editar ? htmlspecialchars($nota_editar['contenido']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <?php echo $nota_editar ? '💾 Actualizar Nota' : '✅ Agregar Nota'; ?>
                    </button>
                    
                    <?php if ($nota_editar): ?>
                        <a href="notas.php" style="display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none;">
                            ❌ Cancelar Edición
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Lista de notas organizadas -->
            <div class="notes-container">
                <?php
                $mostrar_generales = ($filtro_cliente === 'todos' || $filtro_cliente === 'generales');
                $mostrar_clientes = ($filtro_cliente === 'todos' || $filtro_cliente === 'clientes' || strpos($filtro_cliente, 'cliente_') === 0);
                ?>

                <!-- Notas Generales -->
                <?php if ($mostrar_generales): ?>
                    <div class="notes-section">
                        <div class="section-header">
                            <div class="section-title">📋 Notas Generales</div>
                            <div class="notes-count"><?php echo count($notas_data['generales']); ?></div>
                        </div>
                        
                        <?php if (empty($notas_data['generales'])): ?>
                            <div class="no-notes">
                                📝 No hay notas generales aún.<br>
                                <small>Las notas generales no están asociadas a ningún cliente específico.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notas_data['generales'] as $nota): ?>
                                <?php
                                // Aplicar filtro de búsqueda
                                if (!empty($buscar) && stripos($nota['titulo'] . ' ' . $nota['contenido'], $buscar) === false) continue;
                                ?>
                                <div class="note-card general-note" onclick="abrirModal(<?php echo $nota['id']; ?>, '<?php echo htmlspecialchars($nota['titulo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($nota['contenido'], ENT_QUOTES); ?>', null, '<?php echo date('d/m/Y H:i', strtotime($nota['fecha_actualizacion'])); ?>')">
                                    <div class="read-more-indicator">👁️</div>
                                    
                                    <div class="note-title">
                                        <?php echo htmlspecialchars($nota['titulo']); ?>
                                    </div>
                                    <?php if ($nota['contenido']): ?>
                                        <div class="note-preview">
                                            <?php echo nl2br(htmlspecialchars($nota['contenido'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="note-preview" style="font-style: italic; color: #999;">
                                            Sin contenido adicional
                                        </div>
                                    <?php endif; ?>
                                    <div class="note-meta">
                                        <span>
                                            📅 <?php echo date('d/m/Y H:i', strtotime($nota['fecha_actualizacion'])); ?>
                                        </span>
                                        <div class="note-actions" onclick="event.stopPropagation();">
                                            <button class="btn-delete" 
                                                    onclick="confirmarEliminar(<?php echo $nota['id']; ?>)">
                                                🗑️ Eliminar
                                            </button>
                                        </div>
                                    </div>
                                    <span class="general-tag">General</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Notas por Cliente -->
                <?php if ($mostrar_clientes): ?>
                    <?php foreach ($notas_data['por_cliente'] as $cliente_id => $data): ?>
                        <?php
                        // Filtro por cliente específico
                        if (strpos($filtro_cliente, 'cliente_') === 0) {
                            $cliente_filtro_id = (int)str_replace('cliente_', '', $filtro_cliente);
                            if ($cliente_id !== $cliente_filtro_id) continue;
                        }
                        ?>
                        
                        <div class="notes-section">
                            <div class="cliente-header">
                                <div class="cliente-info">
                                    <h4>🏢 <?php echo htmlspecialchars($data['cliente']['nombre']); ?></h4>
                                    <div class="cliente-responsable">👤 <?php echo htmlspecialchars($data['cliente']['responsable']); ?></div>
                                </div>
                                <div class="notes-count"><?php echo count($data['notas']); ?> notas</div>
                            </div>
                            
                            <?php foreach ($data['notas'] as $nota): ?>
                                <?php
                                // Aplicar filtro de búsqueda
                                if (!empty($buscar) && stripos($nota['titulo'] . ' ' . $nota['contenido'], $buscar) === false) continue;
                                ?>
                                <div class="note-card client-note" onclick="abrirModal(<?php echo $nota['id']; ?>, '<?php echo htmlspecialchars($nota['titulo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($nota['contenido'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($data['cliente']['nombre'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y H:i', strtotime($nota['fecha_actualizacion'])); ?>')">
                                    <div class="read-more-indicator">👁️</div>
                                    
                                    <div class="note-title">
                                        <?php echo htmlspecialchars($nota['titulo']); ?>
                                    </div>
                                    <?php if ($nota['contenido']): ?>
                                        <div class="note-preview">
                                            <?php echo nl2br(htmlspecialchars($nota['contenido'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="note-preview" style="font-style: italic; color: #999;">
                                            Sin contenido adicional
                                        </div>
                                    <?php endif; ?>
                                    <div class="note-meta">
                                        <span>
                                            📅 <?php echo date('d/m/Y H:i', strtotime($nota['fecha_actualizacion'])); ?>
                                        </span>
                                        <div class="note-actions" onclick="event.stopPropagation();">
                                            <button class="btn-delete" 
                                                    onclick="confirmarEliminar(<?php echo $nota['id']; ?>)">
                                                🗑️ Eliminar
                                            </button>
                                        </div>
                                    </div>
                                    <span class="client-tag"><?php echo htmlspecialchars($data['cliente']['nombre']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($notas_data['generales']) && empty($notas_data['por_cliente'])): ?>
                    <div class="no-notes">
                        📝 No hay notas aún.<br>
                        ¡Agrega tu primera nota usando el formulario de la izquierda!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para ver nota completa -->
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle"></div>
                <div class="modal-subtitle">
                    <span id="modalDate"></span>
                    <span id="modalClient" class="client-badge"></span>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="delete-confirmation" id="deleteConfirmation">
                    <p>⚠️ ¿Estás seguro de que deseas eliminar esta nota?</p>
                    <p style="font-weight: normal; margin-bottom: 20px;">Esta acción no se puede deshacer.</p>
                    <div class="delete-buttons">
                        <button class="btn-confirm-delete" onclick="eliminarNota()">
                            🗑️ Sí, Eliminar
                        </button>
                        <button class="btn-cancel-delete" onclick="cancelarEliminacion()">
                            ❌ Cancelar
                        </button>
                    </div>
                </div>
                
                <div id="noteContentArea">
                    <div class="note-full-content" id="modalContent"></div>
                </div>
                
                <!-- Formulario de edición dentro del modal -->
                <div class="edit-form" id="editForm">
                    <form id="editNoteForm">
                        <div class="edit-form-group">
                            <label for="editCliente">Asociar a Cliente:</label>
                            <select id="editCliente" name="cliente_id">
                                <option value="">📋 Nota General (sin cliente)</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>">
                                        🏢 <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="edit-form-group">
                            <label for="editTitulo">Título: <span class="required">*</span></label>
                            <input type="text" id="editTitulo" name="titulo" required>
                        </div>

                        <div class="edit-form-group">
                            <label for="editContenido">Contenido:</label>
                            <textarea id="editContenido" name="contenido" placeholder="Escribe el contenido de tu nota..."></textarea>
                        </div>

                        <div class="edit-form-actions">
                            <button type="button" class="btn-save-edit" onclick="guardarEdicion()">
                                💾 Guardar Cambios
                            </button>
                            <button type="button" class="btn-cancel-edit" onclick="cancelarEdicion()">
                                ❌ Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="modal-meta">
                    📝 Haz clic fuera del modal o presiona Escape para cerrar
                </div>
                <div class="modal-actions">
                    <button class="btn-modal btn-edit-modal" onclick="mostrarFormularioEdicion()">
                        ✏️ Editar Nota
                    </button>
                    <button class="btn-modal btn-delete-modal" onclick="mostrarConfirmacionEliminar()">
                        🗑️ Eliminar Nota
                    </button>
                    <button class="btn-modal btn-close" onclick="cerrarModal()">
                        ✕ Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let notaActualId = null;
        let notaActualClienteId = null;

        function abrirModal(id, titulo, contenido, cliente, fecha) {
            notaActualId = id;
            
            document.getElementById('modalTitle').textContent = titulo;
            document.getElementById('modalDate').textContent = '📅 ' + fecha;
            
            const clienteBadge = document.getElementById('modalClient');
            if (cliente) {
                clienteBadge.textContent = '🏢 ' + cliente;
                clienteBadge.style.display = 'inline-block';
                // Buscar el ID del cliente para la edición
                notaActualClienteId = buscarClienteIdPorNombre(cliente);
            } else {
                clienteBadge.textContent = '📋 Nota General';
                clienteBadge.style.display = 'inline-block';
                notaActualClienteId = null;
            }
            
            const contentArea = document.getElementById('modalContent');
            if (contenido && contenido.trim()) {
                contentArea.textContent = contenido;
                contentArea.className = 'note-full-content';
            } else {
                contentArea.innerHTML = '<div class="note-empty-content">📝 Esta nota no tiene contenido adicional</div>';
                contentArea.className = '';
            }
            
            // Llenar formulario de edición
            document.getElementById('editTitulo').value = titulo;
            document.getElementById('editContenido').value = contenido || '';
            document.getElementById('editCliente').value = notaActualClienteId || '';
            
            // Reset del estado
            resetearEstadoModal();
            
            document.getElementById('noteModal').classList.add('show');
        }

        function buscarClienteIdPorNombre(nombreCliente) {
            const select = document.getElementById('editCliente');
            for (let option of select.options) {
                if (option.textContent.includes(nombreCliente)) {
                    return option.value;
                }
            }
            return null;
        }

        function resetearEstadoModal() {
            document.getElementById('deleteConfirmation').classList.remove('show');
            document.getElementById('noteContentArea').style.display = 'block';
            document.getElementById('editForm').classList.remove('show');
        }

        function cerrarModal() {
            document.getElementById('noteModal').classList.remove('show');
            notaActualId = null;
            notaActualClienteId = null;
        }

        function mostrarFormularioEdicion() {
            // Redirigir a la página de edición dedicada
            window.location.href = 'editar_nota.php?id=' + notaActualId;
        }

        function cancelarEdicion() {
            document.getElementById('editForm').classList.remove('show');
            document.getElementById('noteContentArea').style.display = 'block';
        }

        function guardarEdicion() {
            const titulo = document.getElementById('editTitulo').value.trim();
            const contenido = document.getElementById('editContenido').value.trim();
            const clienteId = document.getElementById('editCliente').value;
            
            if (!titulo) {
                alert('El título es obligatorio');
                return;
            }
            
            // Crear formulario para envío
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?editar=' + notaActualId;
            
            // Agregar campos
            const campos = [
                { name: 'titulo', value: titulo },
                { name: 'contenido', value: contenido },
                { name: 'cliente_id', value: clienteId }
            ];
            
            campos.forEach(campo => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = campo.name;
                input.value = campo.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }

        function mostrarConfirmacionEliminar() {
            document.getElementById('deleteConfirmation').classList.add('show');
            document.getElementById('noteContentArea').style.display = 'none';
            document.getElementById('editForm').classList.remove('show');
        }

        function cancelarEliminacion() {
            document.getElementById('deleteConfirmation').classList.remove('show');
            document.getElementById('noteContentArea').style.display = 'block';
        }

        function eliminarNota() {
            if (notaActualId) {
                window.location.href = '?eliminar=' + notaActualId;
            }
        }

        function confirmarEliminar(id) {
            if (confirm('¿Estás seguro de eliminar esta nota?')) {
                window.location.href = '?eliminar=' + id;
            }
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('noteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });

        // Auto-submit del formulario de búsqueda con delay
        let searchTimeout;
        document.querySelector('input[name="buscar"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>