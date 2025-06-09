<?php
require_once 'config.php';
verificarLogin();

$kanban = obtenerTareasPorEstado();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Kanban</title>
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

        .welcome-msg {
            font-size: 14px;
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
            border: none;
            cursor: pointer;
        }

        .nav-btn:hover {
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

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .kanban-column {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-height: 400px;
        }

        .column-header {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
            color: white;
        }

        .por-hacer { background: #6c757d; }
        .en-progreso { background: #F09146; }
        .en-revision { background: #ffc107; }
        .completado { background: #28a745; }

        .task-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #F09146;
        }

        .task-card.editing {
            border-color: #007bff;
            background: #e7f3ff;
        }

        .task-title {
            font-weight: bold;
            color: #121A28;
            margin-bottom: 10px;
        }

        .task-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .task-type {
            display: inline-block;
            background: #F09146;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 8px;
        }

        .priority-alta { border-left: 4px solid #dc3545; }
        .priority-media { border-left: 4px solid #ffc107; }
        .priority-baja { border-left: 4px solid #28a745; }
        .priority-urgente { border-left: 4px solid #A23004; }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title h2 {
            color: #121A28;
            font-size: 32px;
            margin-bottom: 10px;
        }

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
            font-size: 36px;
            font-weight: bold;
            color: #F09146;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        /* Modal para editar tarea */
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
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            animation: slideIn 0.3s;
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .modal-header h3 {
            color: #121A28;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .modal-task-title {
            color: #666;
            font-size: 16px;
        }

        .estado-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }

        .estado-btn {
            padding: 15px 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: white;
            color: #666;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .estado-btn:hover {
            border-color: #F09146;
            background: #f8f9fa;
        }

        .estado-btn.active {
            border-color: #F09146;
            background: #F09146;
            color: white;
        }

        .estado-btn.por-hacer.active { background: #6c757d; border-color: #6c757d; }
        .estado-btn.en-progreso.active { background: #F09146; border-color: #F09146; }
        .estado-btn.en-revision.active { background: #ffc107; border-color: #ffc107; color: #212529; }
        .estado-btn.completado.active { background: #28a745; border-color: #28a745; }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn-save {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-save:hover {
            background: #218838;
        }

        .btn-save:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #F09146;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        .success-message, .error-message {
            display: none;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .task-edit-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #F09146;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .task-card:hover .task-edit-icon {
            opacity: 1;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .estado-buttons {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column;
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
            <a href="reports_page.php" class="nav-btn">👥 Reportes</a>
            <a href="registrar_cliente.php" class="nav-btn">➕ Nuevo Cliente</a>
            <a href="tareas_calendar.php" class="nav-btn">📋 Tareas</a>
            <a href="notas.php" class="nav-btn">📝 Notas</a>
            <a href="google_auth.php" class="nav-btn">📅 Google Calendar</a>
            <a href="logout.php" class="nav-btn logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-title">
            <h2>📊 Tareas Activas</h2>
            <p>Vista general del estado de todas las tareas - <strong>Haz clic en una tarea para editarla</strong></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($kanban['Por Hacer']); ?></div>
                <div class="stat-label">Por Hacer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($kanban['En Progreso']); ?></div>
                <div class="stat-label">En Progreso</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($kanban['En Revisión']); ?></div>
                <div class="stat-label">En Revisión</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($kanban['Completado']); ?></div>
                <div class="stat-label">Completado</div>
            </div>
        </div>

        <div class="kanban-board">
            <?php 
            $columnas = [
                'Por Hacer' => 'por-hacer',
                'En Progreso' => 'en-progreso',
                'En Revisión' => 'en-revision',
                'Completado' => 'completado'
            ];
            
            foreach ($columnas as $estado => $clase): 
            ?>
                <div class="kanban-column">
                    <div class="column-header <?php echo $clase; ?>">
                        <?php echo $estado; ?>
                    </div>
                    
                    <?php foreach ($kanban[$estado] as $tarea): ?>
                        <div class="task-card priority-<?php echo strtolower($tarea['prioridad']); ?>" 
                             onclick="editarTarea(<?php echo $tarea['id']; ?>, '<?php echo htmlspecialchars($tarea['nombre_tarea'], ENT_QUOTES); ?>', '<?php echo $tarea['estado']; ?>')">
                            
                            <div class="task-edit-icon">✏️</div>
                            
                            <div class="task-title"><?php echo htmlspecialchars($tarea['nombre_tarea']); ?></div>
                            <div class="task-info">👤 <?php echo htmlspecialchars($tarea['responsable']); ?></div>
                            <div class="task-info">🏢 <?php echo htmlspecialchars($tarea['nombre_cliente'] ?? 'Sin cliente'); ?></div>
                            <div class="task-info">⚡ <?php echo htmlspecialchars($tarea['prioridad']); ?></div>
                            <?php if ($tarea['descripcion']): ?>
                                <div class="task-info">📝 <?php echo htmlspecialchars(substr($tarea['descripcion'], 0, 50)) . '...'; ?></div>
                            <?php endif; ?>
                            <span class="task-type"><?php echo htmlspecialchars($tarea['tipo_tarea']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal para editar tarea -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editar Estado de Tarea</h3>
                <div class="modal-task-title" id="modalTaskTitle"></div>
            </div>

            <div class="success-message" id="successMessage"></div>
            <div class="error-message" id="errorMessage"></div>

            <div class="loading" id="loadingSpinner">
                <div class="spinner"></div>
                <div>Actualizando tarea...</div>
            </div>

            <div id="editContent">
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Selecciona el nuevo estado para esta tarea:
                </p>

                <div class="estado-buttons">
                    <button class="estado-btn por-hacer" data-estado="Por Hacer">
                        📋 Por Hacer
                    </button>
                    <button class="estado-btn en-progreso" data-estado="En Progreso">
                        🔄 En Progreso
                    </button>
                    <button class="estado-btn en-revision" data-estado="En Revisión">
                        👀 En Revisión
                    </button>
                    <button class="estado-btn completado" data-estado="Completado">
                        ✅ Completado
                    </button>
                </div>

                <div class="modal-actions">
                    <button class="btn-save" onclick="guardarCambios()" id="saveBtn" disabled>
                        💾 Guardar Cambios
                    </button>
                    <button class="btn-cancel" onclick="cerrarModal()">
                        ❌ Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let tareaSeleccionada = null;
        let estadoOriginal = null;
        let estadoNuevo = null;

        function editarTarea(id, nombre, estadoActual) {
            tareaSeleccionada = id;
            estadoOriginal = estadoActual;
            estadoNuevo = null;

            document.getElementById('modalTaskTitle').textContent = nombre;
            
            // Reset de botones
            document.querySelectorAll('.estado-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.estado === estadoActual) {
                    btn.classList.add('active');
                }
            });
            
            // Reset de mensajes
            ocultarMensajes();
            mostrarContenido();
            
            document.getElementById('saveBtn').disabled = true;
            document.getElementById('editModal').classList.add('show');
        }

        function cerrarModal() {
            document.getElementById('editModal').classList.remove('show');
            tareaSeleccionada = null;
            estadoOriginal = null;
            estadoNuevo = null;
        }

        // Event listeners para botones de estado
        document.querySelectorAll('.estado-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Quitar active de todos
                document.querySelectorAll('.estado-btn').forEach(b => b.classList.remove('active'));
                
                // Agregar active al seleccionado
                this.classList.add('active');
                
                estadoNuevo = this.dataset.estado;
                
                // Habilitar botón guardar solo si cambió el estado
                document.getElementById('saveBtn').disabled = (estadoNuevo === estadoOriginal);
            });
        });

        async function guardarCambios() {
            if (!tareaSeleccionada || !estadoNuevo || estadoNuevo === estadoOriginal) {
                return;
            }

            ocultarMensajes();
            ocultarContenido();
            mostrarLoading();

            try {
                const response = await fetch('update_task_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: tareaSeleccionada,
                        estado: estadoNuevo
                    })
                });

                const result = await response.json();

                ocultarLoading();

                if (result.success) {
                    mostrarMensaje('success', result.message);
                    
                    // Esperar 1.5 segundos y recargar la página
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarMensaje('error', result.message);
                    mostrarContenido();
                }
            } catch (error) {
                ocultarLoading();
                mostrarMensaje('error', 'Error de conexión: ' + error.message);
                mostrarContenido();
            }
        }

        function mostrarMensaje(tipo, mensaje) {
            const element = document.getElementById(tipo === 'success' ? 'successMessage' : 'errorMessage');
            element.textContent = mensaje;
            element.style.display = 'block';
        }

        function ocultarMensajes() {
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
        }

        function mostrarLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }

        function ocultarLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        function mostrarContenido() {
            document.getElementById('editContent').style.display = 'block';
        }

        function ocultarContenido() {
            document.getElementById('editContent').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('editModal').addEventListener('click', function(e) {
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
    </script>
</body>
</html>