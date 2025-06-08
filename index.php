<?php
require_once 'config.php';

// Verificar si hay sesión activa
$user_logged_in = isset($_SESSION['user_id']);
$user_data = null;

if ($user_logged_in) {
    $user_data = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Kanban - Sistema de Gestión</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* Login Styles - Basado en CodePen */
        .login-container {
            background: #f6f5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Montserrat', sans-serif;
            height: 100vh;
            margin: 0;
        }

        .container-login {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            position: relative;
            overflow: hidden;
            width: 768px;
            max-width: 100%;
            min-height: 480px;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .overlay {
            background: #FF416C;
            background: -webkit-linear-gradient(to right, #FF4B2B, #FF416C);
            background: linear-gradient(to right, #FF4B2B, #FF416C);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-right {
            right: 0;
            transform: translateX(0);
        }

        .social-container {
            margin: 20px 0;
        }

        .social-container a {
            border: 1px solid #DDDDDD;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            height: 40px;
            width: 40px;
            text-decoration: none;
            color: #333;
        }

        .login-form {
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            text-align: center;
        }

        .login-form h1 {
            font-weight: bold;
            margin: 0;
            margin-bottom: 20px;
            color: #333;
        }

        .login-form span {
            font-size: 12px;
            margin-bottom: 20px;
        }

        .login-form input {
            background-color: #eee;
            border: none;
            padding: 12px 15px;
            margin: 8px 0;
            width: 100%;
            outline: none;
        }

        .login-form button {
            border-radius: 20px;
            border: 1px solid #FF4B2B;
            background-color: #FF4B2B;
            color: #FFFFFF;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
            cursor: pointer;
            margin-top: 15px;
        }

        .login-form button:active {
            transform: scale(0.95);
        }

        .login-form button:focus {
            outline: none;
        }

        .login-form button:disabled {
            background-color: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container-login {
                width: 90%;
                min-height: 500px;
            }
            
            .sign-in-container {
                width: 100%;
            }
            
            .overlay-container {
                display: none;
            }
            
            .login-form {
                padding: 0 30px;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }

        .success-message {
            color: #28a745;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-secondary {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .nav-tabs {
            background: white;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 30px;
            display: flex;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .nav-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .nav-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-tab:not(.active) {
            color: #6c757d;
        }

        .nav-tab:hover:not(.active) {
            background: #f8f9fa;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .client-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .client-card:hover {
            transform: translateY(-5px);
        }

        .client-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .client-info {
            margin-bottom: 15px;
        }

        .client-info p {
            margin-bottom: 8px;
            color: #666;
        }

        .client-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .kanban-column {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-height: 500px;
        }

        .kanban-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e5e9;
        }

        .kanban-header h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .task-count {
            color: #6c757d;
            font-size: 14px;
        }

        .task-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            cursor: move;
            transition: all 0.3s;
        }

        .task-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .task-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .task-client {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .task-description {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .task-priority {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high {
            background: #ffe6e6;
            color: #dc3545;
        }

        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }

        .priority-low {
            background: #d4edda;
            color: #155724;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #667eea;
            font-size: 24px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
            }
            
            .clients-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Login Screen -->
    <div id="loginScreen" class="login-container" <?php echo $user_logged_in ? 'style="display: none;"' : ''; ?>>
        <div class="container-login">
            <div class="form-container sign-in-container">
                <form class="login-form" id="loginForm">
                    <h1>Iniciar Sesión</h1>
                    <div class="social-container">
                        <a href="#" class="social">📊</a>
                        <a href="#" class="social">📈</a>
                        <a href="#" class="social">💼</a>
                    </div>
                    <span>o usa tu cuenta</span>
                    <input type="text" id="username" name="username" placeholder="Usuario" required />
                    <input type="password" id="password" name="password" placeholder="Contraseña" required />
                    <button type="submit" id="loginBtn">Iniciar Sesión</button>
                    <div id="loginMessage"></div>
                </form>
            </div>
            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-right">
                        <h1>¡Hola, Amigo!</h1>
                        <p>Ingresa tus datos personales y comienza tu viaje con nosotros</p>
                        <p>Sistema de Gestión de Marketing</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Application -->
    <div id="mainApp" <?php echo !$user_logged_in ? 'class="hidden"' : ''; ?>>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>Marketing Kanban</h1>
                <div class="header-actions">
                    <span id="userWelcome">
                        <?php echo $user_logged_in ? 'Bienvenido, ' . htmlspecialchars($user_data['full_name']) : ''; ?>
                    </span>
                    <button class="btn-secondary" onclick="logout()">Cerrar Sesión</button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="nav-tabs">
                <div class="nav-tab active" onclick="showSection('dashboard')">Dashboard</div>
                <div class="nav-tab" onclick="showSection('clients')">Clientes</div>
                <div class="nav-tab" onclick="showSection('tasks')">Tareas</div>
                <div class="nav-tab" onclick="showSection('notes')">Notas</div>
            </div>

            <!-- Dashboard Section -->
            <div id="dashboardSection" class="content-section active">
                <div class="kanban-board">
                    <div class="kanban-column">
                        <div class="kanban-header">
                            <h3>Por Hacer</h3>
                            <div class="task-count" id="todoCount">0 tareas</div>
                        </div>
                        <div id="todoColumn" class="tasks-container"></div>
                    </div>
                    <div class="kanban-column">
                        <div class="kanban-header">
                            <h3>En Progreso</h3>
                            <div class="task-count" id="progressCount">0 tareas</div>
                        </div>
                        <div id="progressColumn" class="tasks-container"></div>
                    </div>
                    <div class="kanban-column">
                        <div class="kanban-header">
                            <h3>En Revisión</h3>
                            <div class="task-count" id="reviewCount">0 tareas</div>
                        </div>
                        <div id="reviewColumn" class="tasks-container"></div>
                    </div>
                    <div class="kanban-column">
                        <div class="kanban-header">
                            <h3>Completado</h3>
                            <div class="task-count" id="doneCount">0 tareas</div>
                        </div>
                        <div id="doneColumn" class="tasks-container"></div>
                    </div>
                </div>
            </div>

            <!-- Clients Section -->
            <div id="clientsSection" class="content-section">
                <div class="header">
                    <h2>Gestión de Clientes</h2>
                    <button class="btn-primary" onclick="openClientModal()">Agregar Cliente</button>
                </div>
                <div class="clients-grid" id="clientsGrid">
                    <div class="loading">Cargando clientes...</div>
                </div>
            </div>

            <!-- Tasks Section -->
            <div id="tasksSection" class="content-section">
                <div class="header">
                    <h2>Gestión de Tareas</h2>
                    <button class="btn-primary" onclick="openTaskModal()">Agregar Tarea</button>
                </div>
                <div class="kanban-board">
                    <div class="kanban-column">
                        <div class="kanban-header">
                            <h3>Todas las Tareas</h3>
                            <div class="task-count" id="allTasksCount">0 tareas</div>
                        </div>
                        <div id="allTasksColumn" class="tasks-container">
                            <div class="loading">Cargando tareas...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div id="notesSection" class="content-section">
                <div class="header">
                    <h2>Bloc de Notas</h2>
                    <button class="btn-primary" onclick="openNoteModal()">Agregar Nota</button>
                </div>
                <div class="clients-grid" id="notesGrid">
                    <div class="loading">Cargando notas...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Modal -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="clientModalTitle">Agregar Cliente</h3>
                <span class="close" onclick="closeClientModal()">&times;</span>
            </div>
            <form id="clientForm">
                <div class="form-group">
                    <label for="clientName">Nombre del Cliente:</label>
                    <input type="text" id="clientName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="clientEmail">Email:</label>
                    <input type="email" id="clientEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="clientCompany">Empresa:</label>
                    <input type="text" id="clientCompany" name="company">
                </div>
                <div class="form-group">
                    <label for="clientPhone">Teléfono:</label>
                    <input type="tel" id="clientPhone" name="phone">
                </div>
                <div class="form-group">
                    <label for="clientNotes">Notas:</label>
                    <textarea id="clientNotes" name="notes" placeholder="Información adicional sobre el cliente..."></textarea>
                </div>
                <button type="submit" class="btn" id="clientSubmitBtn">Guardar Cliente</button>
                <div id="clientMessage"></div>
            </form>
        </div>
    </div>

    <!-- Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="taskModalTitle">Agregar Tarea</h3>
                <span class="close" onclick="closeTaskModal()">&times;</span>
            </div>
            <form id="taskForm">
                <div class="form-group">
                    <label for="taskType">Tipo de Tarea:</label>
                    <select id="taskType" name="task_type" onchange="loadTaskTemplate()">
                        <option value="">Cargando plantillas...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskTitle">Título de la Tarea:</label>
                    <input type="text" id="taskTitle" name="title" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="taskClient">Cliente:</label>
                        <select id="taskClient" name="client_id" required>
                            <option value="">Seleccionar Cliente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="taskPriority">Prioridad:</label>
                        <select id="taskPriority" name="priority" required>
                            <option value="low">Baja</option>
                            <option value="medium">Media</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="taskDescription">Descripción:</label>
                    <textarea id="taskDescription" name="description" placeholder="Describe la tarea de marketing..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="taskStatus">Estado:</label>
                        <select id="taskStatus" name="status" required>
                            <option value="todo">Por Hacer</option>
                            <option value="progress">En Progreso</option>
                            <option value="review">En Revisión</option>
                            <option value="done">Completado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="taskDueDate">Fecha Límite:</label>
                        <input type="date" id="taskDueDate" name="due_date">
                    </div>
                </div>
                <button type="submit" class="btn" id="taskSubmitBtn">Guardar Tarea</button>
                <div id="taskMessage"></div>
            </form>
        </div>
    </div>

    <!-- Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="noteModalTitle">Agregar Nota</h3>
                <span class="close" onclick="closeNoteModal()">&times;</span>
            </div>
            <form id="noteForm">
                <div class="form-group">
                    <label for="noteTitle">Título de la Nota:</label>
                    <input type="text" id="noteTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="noteTask">Tarea Relacionada (Opcional):</label>
                    <select id="noteTask" name="task_id">
                        <option value="">Sin tarea asociada</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="noteContent">Contenido:</label>
                    <textarea id="noteContent" name="content" placeholder="Escribe tu nota aquí..." rows="6" required></textarea>
                </div>
                <button type="submit" class="btn" id="noteSubmitBtn">Guardar Nota</button>
                <div id="noteMessage"></div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales
        let currentUser = <?php echo $user_logged_in ? json_encode($user_data) : 'null'; ?>;
        let clients = [];
        let tasks = [];
        let notes = [];
        let taskTemplates = [];
        let editingClientId = null;
        let editingTaskId = null;
        let editingNoteId = null;

        // Inicializar aplicación
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Aplicación iniciada');
            
            // Setup event listeners
            document.getElementById('loginForm').addEventListener('submit', handleLogin);
            document.getElementById('clientForm').addEventListener('submit', handleClientSubmit);
            document.getElementById('taskForm').addEventListener('submit', handleTaskSubmit);
            document.getElementById('noteForm').addEventListener('submit', handleNoteSubmit);
            
            // Setup modal close on outside click
            window.onclick = function(event) {
                const modals = ['clientModal', 'taskModal', 'noteModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            }

            // Test buttons functionality
            console.log('Botones configurados correctamente');

            // Si hay usuario logueado, cargar datos
            if (currentUser) {
                console.log('Usuario logueado, cargando datos...');
                loadInitialData();
            } else {
                console.log('No hay usuario logueado');
            }
        });

        // Funciones de utilidad
        function showMessage(elementId, message, isError = false) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.className = isError ? 'error-message' : 'success-message';
            setTimeout(() => {
                element.textContent = '';
                element.className = '';
            }, 5000);
        }

        function setButtonLoading(buttonId, loading) {
            const button = document.getElementById(buttonId);
            button.disabled = loading;
            if (loading) {
                button.textContent = 'Cargando...';
            } else {
                // Restaurar texto original
                if (buttonId === 'loginBtn') button.textContent = 'Iniciar Sesión';
                else if (buttonId === 'clientSubmitBtn') button.textContent = editingClientId ? 'Actualizar Cliente' : 'Guardar Cliente';
                else if (buttonId === 'taskSubmitBtn') button.textContent = editingTaskId ? 'Actualizar Tarea' : 'Guardar Tarea';
                else if (buttonId === 'noteSubmitBtn') button.textContent = editingNoteId ? 'Actualizar Nota' : 'Guardar Nota';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
            
            // Reset forms
            if (modalId === 'clientModal') {
                document.getElementById('clientForm').reset();
                editingClientId = null;
                document.getElementById('clientModalTitle').textContent = 'Agregar Cliente';
                document.getElementById('clientMessage').innerHTML = '';
            } else if (modalId === 'taskModal') {
                document.getElementById('taskForm').reset();
                editingTaskId = null;
                document.getElementById('taskModalTitle').textContent = 'Agregar Tarea';
                document.getElementById('taskMessage').innerHTML = '';
                // Resetear selector de tipo de tarea
                updateTaskTypeSelect();
            } else if (modalId === 'noteModal') {
                document.getElementById('noteForm').reset();
                editingNoteId = null;
                document.getElementById('noteModalTitle').textContent = 'Agregar Nota';
                document.getElementById('noteMessage').innerHTML = '';
            }
        }

        // Autenticación
        async function handleLogin(e) {
            e.preventDefault();
            setButtonLoading('loginBtn', true);
            
            const formData = new FormData(e.target);
            const data = {
                username: formData.get('username'),
                password: formData.get('password')
            };

            try {
                const response = await fetch('auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    currentUser = result.user;
                    document.getElementById('loginScreen').style.display = 'none';
                    document.getElementById('mainApp').classList.remove('hidden');
                    document.getElementById('userWelcome').textContent = `Bienvenido, ${result.user.full_name}`;
                    
                    // Cargar datos iniciales
                    await loadInitialData();
                } else {
                    showMessage('loginMessage', result.message, true);
                }
            } catch (error) {
                showMessage('loginMessage', 'Error de conexión', true);
            } finally {
                setButtonLoading('loginBtn', false);
            }
        }

        async function logout() {
            try {
                await fetch('auth.php?action=logout', { method: 'POST' });
                currentUser = null;
                document.getElementById('loginScreen').style.display = 'flex';
                document.getElementById('mainApp').classList.add('hidden');
                document.getElementById('loginForm').reset();
            } catch (error) {
                console.error('Error al cerrar sesión:', error);
            }
        }

        // Navegación
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + 'Section').classList.add('active');
            
            // Add active class to selected tab
            event.target.classList.add('active');
            
            // Load data based on section
            if (sectionName === 'clients') {
                loadClients();
            } else if (sectionName === 'tasks') {
                loadTasks();
            } else if (sectionName === 'notes') {
                loadNotes();
            } else if (sectionName === 'dashboard') {
                updateKanbanBoard();
            }
        }

        // Cargar datos iniciales
        async function loadInitialData() {
            await Promise.all([
                loadClients(),
                loadTasks(),
                loadNotes(),
                loadTaskTemplates()
            ]);
            updateKanbanBoard();
        }

        // Cargar plantillas de tareas
        async function loadTaskTemplates() {
            try {
                const response = await fetch('task_templates.php');
                const result = await response.json();

                if (result.success) {
                    taskTemplates = result.templates;
                    updateTaskTypeSelect();
                } else {
                    console.error('Error al cargar plantillas:', result.message);
                }
            } catch (error) {
                console.error('Error al cargar plantillas:', error);
            }
        }

        // Actualizar selector de tipo de tarea
        function updateTaskTypeSelect() {
            const select = document.getElementById('taskType');
            select.innerHTML = '<option value="">Seleccionar tipo de tarea...</option>';
            
            if (taskTemplates.length === 0) {
                select.innerHTML += '<option value="" disabled>No hay plantillas disponibles</option>';
                return;
            }

            // Agrupar por categoría
            const grouped = {};
            taskTemplates.forEach(template => {
                const category = template.category || 'General';
                if (!grouped[category]) {
                    grouped[category] = [];
                }
                grouped[category].push(template);
            });

            // Agregar opción personalizada
            select.innerHTML += '<option value="custom">Tarea personalizada</option>';

            // Agregar plantillas por categoría
            Object.keys(grouped).sort().forEach(category => {
                const optgroup = document.createElement('optgroup');
                optgroup.label = category;
                
                grouped[category].forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.title;
                    optgroup.appendChild(option);
                });
                
                select.appendChild(optgroup);
            });
        }

        // Cargar plantilla seleccionada
        function loadTaskTemplate() {
            const select = document.getElementById('taskType');
            const templateId = select.value;
            
            if (!templateId || templateId === 'custom') {
                // Limpiar campos para tarea personalizada
                document.getElementById('taskTitle').value = '';
                document.getElementById('taskDescription').value = '';
                document.getElementById('taskPriority').value = 'medium';
                return;
            }
            
            // Buscar plantilla
            const template = taskTemplates.find(t => t.id == templateId);
            if (template) {
                // Auto-completar campos
                document.getElementById('taskTitle').value = template.title;
                document.getElementById('taskDescription').value = template.description || '';
                document.getElementById('taskPriority').value = template.priority;
            }
        }

        function getPriorityText(priority) {
            switch(priority) {
                case 'high': return 'Alta';
                case 'medium': return 'Media';
                case 'low': return 'Baja';
                default: return 'Media';
            }
        }

        // Gestión de Clientes
        async function loadClients() {
            try {
                const response = await fetch('clients.php');
                const result = await response.json();

                if (result.success) {
                    clients = result.clients;
                    displayClients();
                    updateTaskClientSelect();
                } else {
                    console.error('Error al cargar clientes:', result.message);
                }
            } catch (error) {
                console.error('Error al cargar clientes:', error);
            }
        }

        function displayClients() {
            const clientsGrid = document.getElementById('clientsGrid');
            
            if (clients.length === 0) {
                clientsGrid.innerHTML = '<div class="loading">No hay clientes registrados</div>';
                return;
            }

            clientsGrid.innerHTML = '';
            
            clients.forEach(client => {
                const clientCard = document.createElement('div');
                clientCard.className = 'client-card';
                clientCard.innerHTML = `
                    <h3>${client.name}</h3>
                    <div class="client-info">
                        <p><strong>Email:</strong> ${client.email || 'No especificado'}</p>
                        <p><strong>Empresa:</strong> ${client.company || 'No especificado'}</p>
                        <p><strong>Teléfono:</strong> ${client.phone || 'No especificado'}</p>
                        <p><strong>Notas:</strong> ${client.notes || 'Sin notas'}</p>
                    </div>
                    <div class="client-actions">
                        <button class="btn-small btn-edit" onclick="editClient(${client.id})">Editar</button>
                        <button class="btn-small btn-delete" onclick="deleteClient(${client.id})">Eliminar</button>
                    </div>
                `;
                clientsGrid.appendChild(clientCard);
            });
        }

        function openClientModal() {
            document.getElementById('clientModal').style.display = 'block';
        }

        function closeClientModal() {
            closeModal('clientModal');
        }

        async function handleClientSubmit(e) {
            e.preventDefault();
            setButtonLoading('clientSubmitBtn', true);
            
            const formData = new FormData(e.target);
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                company: formData.get('company'),
                phone: formData.get('phone'),
                notes: formData.get('notes')
            };

            try {
                const url = editingClientId ? `clients.php?id=${editingClientId}` : 'clients.php';
                const method = editingClientId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('clientMessage', result.message);
                    await loadClients();
                    setTimeout(() => closeClientModal(), 1500);
                } else {
                    showMessage('clientMessage', result.message, true);
                }
            } catch (error) {
                showMessage('clientMessage', 'Error de conexión', true);
            } finally {
                setButtonLoading('clientSubmitBtn', false);
            }
        }

        async function editClient(clientId) {
            const client = clients.find(c => c.id === clientId);
            if (!client) return;

            editingClientId = clientId;
            document.getElementById('clientModalTitle').textContent = 'Editar Cliente';
            document.getElementById('clientName').value = client.name;
            document.getElementById('clientEmail').value = client.email || '';
            document.getElementById('clientCompany').value = client.company || '';
            document.getElementById('clientPhone').value = client.phone || '';
            document.getElementById('clientNotes').value = client.notes || '';
            
            openClientModal();
        }

        async function deleteClient(clientId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este cliente?')) return;

            try {
                const response = await fetch(`clients.php?id=${clientId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.success) {
                    await loadClients();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Error al eliminar cliente');
            }
        }

        // Gestión de Tareas
        async function loadTasks() {
            try {
                const response = await fetch('tasks.php');
                const result = await response.json();

                if (result.success) {
                    tasks = result.tasks;
                    displayTasks();
                    updateNoteTaskSelect();
                } else {
                    console.error('Error al cargar tareas:', result.message);
                }
            } catch (error) {
                console.error('Error al cargar tareas:', error);
            }
        }

        function displayTasks() {
            const allTasksColumn = document.getElementById('allTasksColumn');
            
            if (tasks.length === 0) {
                allTasksColumn.innerHTML = '<div class="loading">No hay tareas registradas</div>';
                document.getElementById('allTasksCount').textContent = '0 tareas';
                return;
            }

            allTasksColumn.innerHTML = '';
            
            tasks.forEach(task => {
                const taskCard = createTaskCard(task);
                allTasksColumn.appendChild(taskCard);
            });
            
            document.getElementById('allTasksCount').textContent = `${tasks.length} tareas`;
        }

        function createTaskCard(task) {
            const taskCard = document.createElement('div');
            taskCard.className = 'task-card';
            taskCard.innerHTML = `
                <div class="task-title">${task.title}</div>
                <div class="task-client">Cliente: ${task.client_name || 'Sin cliente'}</div>
                <div class="task-description">${task.description || 'Sin descripción'}</div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="task-priority priority-${task.priority}">${getPriorityText(task.priority)}</span>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn-small btn-edit" onclick="editTask(${task.id})">Editar</button>
                        <button class="btn-small btn-delete" onclick="deleteTask(${task.id})">Eliminar</button>
                    </div>
                </div>
            `;
            return taskCard;
        }

        function openTaskModal() {
            updateTaskClientSelect();
            updateTaskTypeSelect();
            document.getElementById('taskModal').style.display = 'block';
        }

        function closeTaskModal() {
            closeModal('taskModal');
        }

        async function handleTaskSubmit(e) {
            e.preventDefault();
            setButtonLoading('taskSubmitBtn', true);
            
            const formData = new FormData(e.target);
            const data = {
                title: formData.get('title'),
                client_id: formData.get('client_id'),
                priority: formData.get('priority'),
                description: formData.get('description'),
                status: formData.get('status'),
                due_date: formData.get('due_date') || null
            };

            try {
                const url = editingTaskId ? `tasks.php?id=${editingTaskId}` : 'tasks.php';
                const method = editingTaskId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('taskMessage', result.message);
                    await loadTasks();
                    updateKanbanBoard();
                    setTimeout(() => closeTaskModal(), 1500);
                } else {
                    showMessage('taskMessage', result.message, true);
                }
            } catch (error) {
                showMessage('taskMessage', 'Error de conexión', true);
            } finally {
                setButtonLoading('taskSubmitBtn', false);
            }
        }

        async function editTask(taskId) {
            const task = tasks.find(t => t.id === taskId);
            if (!task) return;

            editingTaskId = taskId;
            document.getElementById('taskModalTitle').textContent = 'Editar Tarea';
            document.getElementById('taskType').value = 'custom';
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskClient').value = task.client_id;
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskDescription').value = task.description || '';
            document.getElementById('taskStatus').value = task.status;
            document.getElementById('taskDueDate').value = task.due_date || '';
            
            openTaskModal();
        }

        async function deleteTask(taskId) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta tarea?')) return;

            try {
                const response = await fetch(`tasks.php?id=${taskId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.success) {
                    await loadTasks();
                    updateKanbanBoard();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Error al eliminar tarea');
            }
        }

        function updateTaskClientSelect() {
            const taskClientSelect = document.getElementById('taskClient');
            taskClientSelect.innerHTML = '<option value="">Seleccionar Cliente</option>';
            
            clients.forEach(client => {
                const option = document.createElement('option');
                option.value = client.id;
                option.textContent = client.name;
                taskClientSelect.appendChild(option);
            });
        }

        // Gestión de Notas
        async function loadNotes() {
            try {
                const response = await fetch('notes.php');
                const result = await response.json();

                if (result.success) {
                    notes = result.notes;
                    displayNotes();
                } else {
                    console.error('Error al cargar notas:', result.message);
                }
            } catch (error) {
                console.error('Error al cargar notas:', error);
            }
        }

        function displayNotes() {
            const notesGrid = document.getElementById('notesGrid');
            
            if (notes.length === 0) {
                notesGrid.innerHTML = '<div class="loading">No hay notas registradas</div>';
                return;
            }

            notesGrid.innerHTML = '';
            
            notes.forEach(note => {
                const noteCard = document.createElement('div');
                noteCard.className = 'client-card';
                noteCard.innerHTML = `
                    <h3>${note.title}</h3>
                    <div class="client-info">
                        <p>${note.content}</p>
                        <p><small>${note.task_title ? `Tarea: ${note.task_title}` : 'Nota general'} - ${new Date(note.created_at).toLocaleDateString()}</small></p>
                    </div>
                    <div class="client-actions">
                        <button class="btn-small btn-edit" onclick="editNote(${note.id})">Editar</button>
                        <button class="btn-small btn-delete" onclick="deleteNote(${note.id})">Eliminar</button>
                    </div>
                `;
                notesGrid.appendChild(noteCard);
            });
        }

        function openNoteModal() {
            updateNoteTaskSelect();
            document.getElementById('noteModal').style.display = 'block';
        }

        function closeNoteModal() {
            closeModal('noteModal');
        }

        async function handleNoteSubmit(e) {
            e.preventDefault();
            setButtonLoading('noteSubmitBtn', true);
            
            const formData = new FormData(e.target);
            const data = {
                title: formData.get('title'),
                content: formData.get('content'),
                task_id: formData.get('task_id') || null
            };

            try {
                const url = editingNoteId ? `notes.php?id=${editingNoteId}` : 'notes.php';
                const method = editingNoteId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('noteMessage', result.message);
                    await loadNotes();
                    setTimeout(() => closeNoteModal(), 1500);
                } else {
                    showMessage('noteMessage', result.message, true);
                }
            } catch (error) {
                showMessage('noteMessage', 'Error de conexión', true);
            } finally {
                setButtonLoading('noteSubmitBtn', false);
            }
        }

        async function editNote(noteId) {
            const note = notes.find(n => n.id === noteId);
            if (!note) return;

            editingNoteId = noteId;
            document.getElementById('noteModalTitle').textContent = 'Editar Nota';
            document.getElementById('noteTitle').value = note.title;
            document.getElementById('noteContent').value = note.content;
            document.getElementById('noteTask').value = note.task_id || '';
            
            openNoteModal();
        }

        async function deleteNote(noteId) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta nota?')) return;

            try {
                const response = await fetch(`notes.php?id=${noteId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.success) {
                    await loadNotes();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Error al eliminar nota');
            }
        }

        function updateNoteTaskSelect() {
            const noteTaskSelect = document.getElementById('noteTask');
            noteTaskSelect.innerHTML = '<option value="">Sin tarea asociada</option>';
            
            tasks.forEach(task => {
                const option = document.createElement('option');
                option.value = task.id;
                option.textContent = task.title;
                noteTaskSelect.appendChild(option);
            });
        }

        // Kanban Board
        function updateKanbanBoard() {
            const columns = {
                'todo': document.getElementById('todoColumn'),
                'progress': document.getElementById('progressColumn'),
                'review': document.getElementById('reviewColumn'),
                'done': document.getElementById('doneColumn')
            };
            
            // Clear all columns
            Object.values(columns).forEach(column => {
                column.innerHTML = '';
            });
            
            const counts = { todo: 0, progress: 0, review: 0, done: 0 };
            
            // Add tasks to appropriate columns
            tasks.forEach(task => {
                const taskCard = createTaskCard(task);
                
                if (columns[task.status]) {
                    columns[task.status].appendChild(taskCard);
                    counts[task.status]++;
                }
            });
            
            // Update task counts
            document.getElementById('todoCount').textContent = `${counts.todo} tareas`;
            document.getElementById('progressCount').textContent = `${counts.progress} tareas`;
            document.getElementById('reviewCount').textContent = `${counts.review} tareas`;
            document.getElementById('doneCount').textContent = `${counts.done} tareas`;
        }
    </script>
</body>
</html>