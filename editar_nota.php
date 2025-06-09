<?php
require_once 'config.php';
verificarLogin();

$mensaje = '';
$tipo_mensaje = '';

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: notas.php');
    exit();
}

$nota_id = (int)$_GET['id'];
$clientes = obtenerClientes();

// Obtener la nota a editar
$nota = null;
try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        SELECT n.*, c.nombre_cliente, c.responsable as cliente_responsable
        FROM notas n 
        LEFT JOIN clientes c ON n.cliente_id = c.id 
        WHERE n.id = ?
    ");
    $stmt->execute([$nota_id]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nota) {
        $mensaje = 'Nota no encontrada';
        $tipo_mensaje = 'error';
    }
} catch (PDOException $e) {
    $mensaje = 'Error al cargar la nota: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $nota) {
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $categoria = trim($_POST['categoria'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'Media';
    
    if (!empty($titulo)) {
        try {
            // Verificar si necesitamos agregar columnas
            $pdo->exec("ALTER TABLE notas ADD COLUMN IF NOT EXISTS categoria VARCHAR(100)");
            $pdo->exec("ALTER TABLE notas ADD COLUMN IF NOT EXISTS prioridad ENUM('Baja', 'Media', 'Alta', 'Urgente') DEFAULT 'Media'");
            
            $stmt = $pdo->prepare("
                UPDATE notas 
                SET titulo = ?, cliente_id = ?, contenido = ?, categoria = ?, prioridad = ?
                WHERE id = ?
            ");
            $stmt->execute([$titulo, $cliente_id, $contenido, $categoria, $prioridad, $nota_id]);
            
            $mensaje = 'Nota actualizada exitosamente';
            $tipo_mensaje = 'success';
            
            // Recargar datos de la nota
            $stmt = $pdo->prepare("
                SELECT n.*, c.nombre_cliente, c.responsable as cliente_responsable
                FROM notas n 
                LEFT JOIN clientes c ON n.cliente_id = c.id 
                WHERE n.id = ?
            ");
            $stmt->execute([$nota_id]);
            $nota = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar nota: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'El título es obligatorio';
        $tipo_mensaje = 'error';
    }
}

// Si no hay nota válida, redirigir
if (!$nota) {
    header('Location: notas.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Nota - Sistema Kanban</title>
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
            max-width: 1000px;
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

        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 30px;
            transition: background 0.3s;
            font-weight: bold;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .edit-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 6px solid #F09146;
        }

        .note-header {
            background: linear-gradient(135deg, #F09146, #A23004);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .note-header h3 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .note-meta {
            opacity: 0.9;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            gap: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #121A28;
            font-weight: bold;
            font-size: 18px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 18px;
            border: 3px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 200px;
            line-height: 1.6;
        }

        .form-group.large-textarea textarea {
            min-height: 300px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #F09146;
            box-shadow: 0 0 0 3px rgba(240, 145, 70, 0.1);
        }

        .required {
            color: #A23004;
        }

        .campo-info {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .mensaje {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f8f9fa;
        }

        .btn {
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-save {
            background: #28a745;
            color: white;
        }

        .btn-save:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .client-info {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 10px;
            border-left: 4px solid #2196f3;
            margin-top: 10px;
        }

        .client-info h4 {
            color: #1976d2;
            margin-bottom: 5px;
        }

        .client-info p {
            color: #666;
            margin: 0;
        }

        .priority-badges {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .priority-badge {
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }

        .priority-baja { background: #d4edda; color: #155724; }
        .priority-media { background: #fff3cd; color: #856404; }
        .priority-alta { background: #f8d7da; color: #721c24; }
        .priority-urgente { background: #f5c6cb; color: #A23004; }

        .word-count {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .form-icon {
            text-align: center;
            font-size: 60px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                grid-template-columns: 1fr;
            }
            
            .priority-badges {
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
            <a href="clientes.php" class="nav-btn">👥 Clientes</a>
            <a href="registrar_cliente.php" class="nav-btn">➕ Nuevo Cliente</a>
            <a href="tareas_calendar.php" class="nav-btn">📋 Tareas</a>
            <a href="notas.php" class="nav-btn active">📝 Notas</a>
            <a href="google_auth.php" class="nav-btn">📅 Google Calendar</a>
            <a href="logout.php" class="nav-btn logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-content">
        <a href="notas.php" class="back-btn">← Volver a Notas</a>
        
        <div class="page-title">
            <h2>✏️ Editor de Notas</h2>
            <p>Modifica tu nota con un editor completo y profesional</p>
        </div>

        <div class="edit-container">
            <div class="note-header">
                <div class="form-icon">📝</div>
                <h3>Editando Nota</h3>
                <div class="note-meta">
                    Creada: <?php echo date('d/m/Y H:i', strtotime($nota['fecha_creacion'])); ?> | 
                    Última modificación: <?php echo date('d/m/Y H:i', strtotime($nota['fecha_actualizacion'])); ?>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="titulo">📌 Título de la Nota: <span class="required">*</span></label>
                            <input type="text" id="titulo" name="titulo" required 
                                   value="<?php echo htmlspecialchars($nota['titulo']); ?>"
                                   placeholder="Título descriptivo de tu nota">
                            <div class="campo-info">Un título claro y descriptivo te ayudará a encontrar la nota fácilmente</div>
                        </div>

                        <div class="form-group">
                            <label for="categoria">🏷️ Categoría:</label>
                            <input type="text" id="categoria" name="categoria" 
                                   value="<?php echo htmlspecialchars($nota['categoria'] ?? ''); ?>"
                                   placeholder="Ej: Reunión, Idea, Recordatorio, Proyecto">
                            <div class="campo-info">Organiza tus notas por categorías para mejor búsqueda</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cliente_id">🏢 Cliente Asociado:</label>
                            <select id="cliente_id" name="cliente_id">
                                <option value="" <?php echo !$nota['cliente_id'] ? 'selected' : ''; ?>>
                                    📋 Nota General (sin cliente específico)
                                </option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>" 
                                            <?php echo $nota['cliente_id'] == $cliente['id'] ? 'selected' : ''; ?>>
                                        🏢 <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($nota['cliente_id']): ?>
                                <div class="client-info">
                                    <h4>Cliente Actual: <?php echo htmlspecialchars($nota['nombre_cliente']); ?></h4>
                                    <p>👤 Responsable: <?php echo htmlspecialchars($nota['cliente_responsable']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="prioridad">⚡ Prioridad:</label>
                            <select id="prioridad" name="prioridad">
                                <option value="Baja" <?php echo ($nota['prioridad'] ?? 'Media') == 'Baja' ? 'selected' : ''; ?>>
                                    🟢 Baja - Información general
                                </option>
                                <option value="Media" <?php echo ($nota['prioridad'] ?? 'Media') == 'Media' ? 'selected' : ''; ?>>
                                    🟡 Media - Seguimiento normal
                                </option>
                                <option value="Alta" <?php echo ($nota['prioridad'] ?? 'Media') == 'Alta' ? 'selected' : ''; ?>>
                                    🟠 Alta - Requiere atención
                                </option>
                                <option value="Urgente" <?php echo ($nota['prioridad'] ?? 'Media') == 'Urgente' ? 'selected' : ''; ?>>
                                    🔴 Urgente - Acción inmediata
                                </option>
                            </select>
                            <div class="priority-badges">
                                <div class="priority-badge priority-baja">Baja</div>
                                <div class="priority-badge priority-media">Media</div>
                                <div class="priority-badge priority-alta">Alta</div>
                                <div class="priority-badge priority-urgente">Urgente</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width large-textarea">
                        <label for="contenido">📄 Contenido de la Nota:</label>
                        <textarea id="contenido" name="contenido" 
                                  placeholder="Escribe aquí todo el contenido de tu nota. Tienes espacio suficiente para incluir todos los detalles que necesites..."><?php echo htmlspecialchars($nota['contenido']); ?></textarea>
                        <div class="word-count" id="wordCount">
                            Palabras: <span id="wordCountNumber">0</span> | Caracteres: <span id="charCountNumber">0</span>
                        </div>
                        <div class="campo-info">Puedes incluir listas, URLs, números de teléfono, direcciones, etc. El formato se preservará.</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-save">
                        💾 Guardar Cambios
                    </button>
                    <a href="notas.php" class="btn btn-cancel">
                        ❌ Cancelar Edición
                    </a>
                    <a href="notas.php?eliminar=<?php echo $nota['id']; ?>" class="btn btn-delete"
                       onclick="return confirm('⚠️ ¿Estás seguro de eliminar esta nota?\n\nTítulo: <?php echo htmlspecialchars($nota['titulo']); ?>\n\nEsta acción no se puede deshacer.')">
                        🗑️ Eliminar Nota
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Contador de palabras y caracteres
        function updateWordCount() {
            const textarea = document.getElementById('contenido');
            const text = textarea.value;
            
            const words = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
            const chars = text.length;
            
            document.getElementById('wordCountNumber').textContent = words;
            document.getElementById('charCountNumber').textContent = chars;
        }

        // Inicializar contador
        document.addEventListener('DOMContentLoaded', function() {
            updateWordCount();
            document.getElementById('contenido').addEventListener('input', updateWordCount);
        });

        // Auto-resize del textarea
        document.getElementById('contenido').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(this.scrollHeight, 300) + 'px';
        });

        // Confirmar antes de salir si hay cambios sin guardar
        let originalContent = document.getElementById('contenido').value;
        let originalTitle = document.getElementById('titulo').value;

        window.addEventListener('beforeunload', function(e) {
            const currentContent = document.getElementById('contenido').value;
            const currentTitle = document.getElementById('titulo').value;
            
            if (currentContent !== originalContent || currentTitle !== originalTitle) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Actualizar valores originales al guardar
        document.querySelector('form').addEventListener('submit', function() {
            originalContent = document.getElementById('contenido').value;
            originalTitle = document.getElementById('titulo').value;
        });

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S para guardar
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
            
            // Escape para cancelar
            if (e.key === 'Escape') {
                if (confirm('¿Estás seguro de cancelar? Los cambios no guardados se perderán.')) {
                    window.location.href = 'notas.php';
                }
            }
        });
    </script>
</body>
</html>