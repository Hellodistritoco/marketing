<div class="template-actions">
                            <button class="btn-small btn-edit" onclick="editTemplate(${template.id})">Editar</button>
                            <button class="btn-small btn-delete" onclick="deleteTemplate(${template.id})">Eliminar</button>
                        </div>
                    `;
                    templateList.appendChild(templateCard);
                });
                
                categoryDiv.appendChild(templateList);
                templatesContainer.appendChild(categoryDiv);
            });
        }

        function openTemplateModal() {
            document.getElementById('templateModal').style.display = 'block';
        }

        function closeTemplateModal() {
            closeModal('templateModal');
        }

        async function handleTemplateSubmit(e) {
            e.preventDefault();
            setButtonLoading('templateSubmitBtn', true);
            
            const formData = new FormData(e.target);
            const data = {
                title: formData.get('title'),
                category: formData.get('category'),
                priority: formData.get('priority'),
                description: formData.get('description')
            };

            try {
                const url = editingTemplateId ? `task_templates.php?id=${editingTemplateId}` : 'task_templates.php';
                const method = editingTemplateId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('templateMessage', result.message);
                    await loadTemplates();
                    setTimeout(() => closeTemplateModal(), 1500);
                } else {
                    showMessage('templateMessage', result.message, true);
                }
            } catch (error) {
                showMessage('templateMessage', 'Error de conexión', true);
            } finally {
                setButtonLoading('templateSubmitBtn', false);
            }
        }

        async function editTemplate(templateId) {
            // Buscar la plantilla en todas las categorías
            let template = null;
            Object.keys(templates).forEach(category => {
                const found = templates[category].find(t => t.id === templateId);
                if (found) template = found;
            });
            
            if (!template) return;

            editingTemplateId = templateId;
            document.getElementById('templateModalTitle').textContent = 'Editar Plantilla';
            document.getElementById('templateTitle').value = template.title;
            document.getElementById('templateCategory').value = template.category || '';
            document.getElementById('templatePriority').value = template.priority;
            document.getElementById('templateDescription').value = template.description || '';
            
            openTemplateModal();
        }

        async function deleteTemplate(templateId) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta plantilla?')) return;

            try {
                const response = await fetch(`task_templates.php?id=${templateId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.success) {
                    await loadTemplates();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Error al eliminar plantilla');
            }
        }

        // Funciones para crear tareas desde plantillas
        function openTaskTemplatesForClient(clientId, clientName) {
            selectedClientForTasks = clientId;
            document.getElementById('clientNameForTasks').textContent = clientName;
            loadTemplatesForSelection();
            document.getElementById('taskTemplatesModal').style.display = 'block';
        }

        function closeTaskTemplatesModal() {
            document.getElementById('taskTemplatesModal').style.display = 'none';
            selectedClientForTasks = null;
        }

        async function loadTemplatesForSelection() {
            try {
                const response = await fetch('task_templates.php?action=grouped');
                const result = await response.json();

                if (result.success) {
                    displayTemplatesForSelection(result.templates);
                } else {
                    console.error('Error al cargar plantillas:', result.message);
                }
            } catch (error) {
                console.error('Error al cargar plantillas:', error);
            }
        }

        function displayTemplatesForSelection(templatesData) {
            const container = document.getElementById('templatesCheckboxList');
            
            if (Object.keys(templatesData).length === 0) {
                container.innerHTML = '<div class="loading">No hay plantillas disponibles</div>';
                return;
            }

            let html = '<button type="button" class="btn-select-all" onclick="toggleAllTemplates()">Seleccionar Todo</button>';
            
            Object.keys(templatesData).forEach(category => {
                html += `
                    <div class="template-checkbox-group">
                        <h4>${category}</h4>
                `;
                
                templatesData[category].forEach(template => {
                    html += `
                        <div class="template-checkbox-item">
                            <input type="checkbox" id="template_${template.id}" value="${template.id}" class="template-checkbox">
                            <label for="template_${template.id}" class="template-checkbox-label">
                                ${template.title}
                                ${template.description ? '<br><small style="color: #666;">' + template.description + '</small>' : ''}
                            </label>
                            <span class="template-checkbox-priority priority-${template.priority}">${getPriorityText(template.priority)}</span>
                        </div>
                    `;
                });
                
                html += '</div>';
            });
            
            container.innerHTML = html;
        }

        function toggleAllTemplates() {
            const checkboxes = document.querySelectorAll('.template-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            const button = document.querySelector('.btn-select-all');
            button.textContent = allChecked ? 'Seleccionar Todo' : 'Deseleccionar Todo';
        }

        async function createTasksFromTemplates() {
            const selectedTemplates = Array.from(document.querySelectorAll('.template-checkbox:checked'))
                .map(cb => parseInt(cb.value));
            
            if (selectedTemplates.length === 0) {
                showMessage('taskTemplatesMessage', 'Por favor selecciona al menos una plantilla', true);
                return;
            }

            try {
                const response = await fetch('task_templates.php?action=create_tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        client_id: selectedClientForTasks,
                        template_ids: selectedTemplates
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('taskTemplatesMessage', `Se crearon ${result.created_tasks.length} tareas correctamente`);
                    await loadTasks();
                    updateKanbanBoard();
                    setTimeout(() => closeTaskTemplatesModal(), 2000);
                } else {
                    showMessage('taskTemplatesMessage', result.message, true);
                }
            } catch (error) {
                showMessage('taskTemplatesMessage', 'Error de conexión', true);
            }
        }

        // Actualizar función closeModal para incluir las nuevas plantillas
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Reset forms
            if (modalId === 'clientModal') {
                document.getElementById('clientForm').reset();
                editingClientId = null;
                document.getElementById('clientModalTitle').textContent = 'Agregar Cliente';
            } else if (modalId === 'taskModal') {
                document.getElementById('taskForm').reset();
                editingTaskId = null;
                document.getElementById('taskModalTitle').textContent = 'Agregar Tarea';
            } else if (modalId === 'noteModal') {
                document.getElementById('noteForm').reset();
                editingNoteId = null;
                document.getElementById('noteModalTitle').textContent = 'Agregar Nota';
            } else if (modalId === 'templateModal') {
                document.getElementById('templateForm').reset();
                editingTemplateId = null;
                document.getElementById('templateModalTitle').textContent = 'Agregar Plantilla';
            } else if (modalId === 'taskTemplatesModal') {
                selectedClientForTasks = null;
            }
        }

        // Actualizar setButtonLoading para incluir plantillas
        function setButtonLoading(buttonId, loading) {
            const button = document.getElementById(buttonId);
            button.disabled = loading;
            if (loading) {
                button.textContent = 'Cargando...';
            } else {
                // Restaurar texto original basado en el ID
                if (buttonId === 'loginBtn') button.textContent = 'Iniciar Sesión';
                else if (buttonId === 'clientSubmitBtn') button.textContent = editingClientId ? 'Actualizar Cliente' : 'Guardar Cliente';
                else if (buttonId === 'taskSubmitBtn') button.textContent = editingTaskId ? 'Actualizar Tarea' : 'Guardar Tarea';
                else if (buttonId === 'noteSubmitBtn') button.textContent = editingNoteId ? 'Actualizar Nota' : 'Guardar Nota';
                else if (buttonId === 'templateSubmitBtn') button.textContent = editingTemplateId ? 'Actualizar Plantilla' : 'Guardar Plantilla';
            }
        }<?php
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Login Styles */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-form {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #667eea;
            font-size: 28px;
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

        /* Header */
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

        /* Navigation */
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

        /* Content Sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* Clients Section */
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

        /* Kanban Board */
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

        /* Modal Styles */
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

        /* Loading and states */
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .hidden {
            display: none !important;
        }

        /* Notes Section */
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .note-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .note-card:hover {
            transform: translateY(-5px);
        }

        .note-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .note-content {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .note-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }

        /* Templates Section */
        .templates-container {
            margin-top: 20px;
        }

        .template-category {
            margin-bottom: 30px;
        }

        .template-category h3 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e5e9;
        }

        .template-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .template-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-left: 4px solid #667eea;
        }

        .template-card:hover {
            transform: translateY(-3px);
        }

        .template-card h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .template-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .template-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .template-priority {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .template-actions {
            display: flex;
            gap: 10px;
        }

        /* Templates Selection Modal */
        .templates-checkbox-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 15px;
        }

        .template-checkbox-group {
            margin-bottom: 20px;
        }

        .template-checkbox-group h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .template-checkbox-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .template-checkbox-item:last-child {
            border-bottom: none;
        }

        .template-checkbox-item input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }

        .template-checkbox-label {
            flex: 1;
            cursor: pointer;
        }

        .template-checkbox-priority {
            margin-left: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-select-all {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
            }
            
            .clients-grid, .notes-grid {
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
        <form class="login-form" id="loginForm">
            <h2>Marketing Kanban</h2>
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn" id="loginBtn">Iniciar Sesión</button>
            <div id="loginMessage"></div>
        </form>
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
                <div class="nav-tab" onclick="showSection('templates')">Plantillas</div>
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

            <!-- Templates Section -->
            <div id="templatesSection" class="content-section">
                <div class="header">
                    <h2>Plantillas de Tareas</h2>
                    <button class="btn-primary" onclick="openTemplateModal()">Agregar Plantilla</button>
                </div>
                <div class="templates-container" id="templatesContainer">
                    <div class="loading">Cargando plantillas...</div>
                </div>
            </div>

            <!-- Notes Section -->
            <div id="notesSection" class="content-section">
                <div class="header">
                    <h2>Bloc de Notas</h2>
                    <button class="btn-primary" onclick="openNoteModal()">Agregar Nota</button>
                </div>
                <div class="notes-grid" id="notesGrid">
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

    <!-- Template Modal -->
    <div id="templateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="templateModalTitle">Agregar Plantilla</h3>
                <span class="close" onclick="closeTemplateModal()">&times;</span>
            </div>
            <form id="templateForm">
                <div class="form-group">
                    <label for="templateTitle">Título de la Plantilla:</label>
                    <input type="text" id="templateTitle" name="title" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="templateCategory">Categoría:</label>
                        <input type="text" id="templateCategory" name="category" placeholder="ej: Pautas, Marketing, Análisis">
                    </div>
                    <div class="form-group">
                        <label for="templatePriority">Prioridad:</label>
                        <select id="templatePriority" name="priority" required>
                            <option value="low">Baja</option>
                            <option value="medium">Media</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="templateDescription">Descripción:</label>
                    <textarea id="templateDescription" name="description" placeholder="Describe la plantilla de tarea..."></textarea>
                </div>
                <button type="submit" class="btn" id="templateSubmitBtn">Guardar Plantilla</button>
                <div id="templateMessage"></div>
            </form>
        </div>
    </div>

    <!-- Task Templates Selection Modal -->
    <div id="taskTemplatesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Seleccionar Plantillas para <span id="clientNameForTasks"></span></h3>
                <span class="close" onclick="closeTaskTemplatesModal()">&times;</span>
            </div>
            <div class="templates-selection">
                <p>Selecciona las tareas que quieres crear para este cliente:</p>
                <div id="templatesCheckboxList" class="templates-checkbox-container">
                    <div class="loading">Cargando plantillas...</div>
                </div>
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="button" class="btn" onclick="createTasksFromTemplates()">Crear Tareas Seleccionadas</button>
                    <button type="button" class="btn-secondary" onclick="closeTaskTemplatesModal()">Cancelar</button>
                </div>
                <div id="taskTemplatesMessage"></div>
            </div>
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
        let templates = [];
        let editingClientId = null;
        let editingTaskId = null;
        let editingNoteId = null;
        let editingTemplateId = null;
        let selectedClientForTasks = null;

        // Inicializar aplicación
        document.addEventListener('DOMContentLoaded', function() {
            // Setup event listeners
            document.getElementById('loginForm').addEventListener('submit', handleLogin);
            document.getElementById('clientForm').addEventListener('submit', handleClientSubmit);
            document.getElementById('taskForm').addEventListener('submit', handleTaskSubmit);
            document.getElementById('noteForm').addEventListener('submit', handleNoteSubmit);
            document.getElementById('templateForm').addEventListener('submit', handleTemplateSubmit);
            
            // Setup modal close on outside click
            window.onclick = function(event) {
                const modals = ['clientModal', 'taskModal', 'noteModal', 'templateModal', 'taskTemplatesModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            }

            // Si hay usuario logueado, cargar datos
            if (currentUser) {
                loadInitialData();
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
                // Restaurar texto original basado en el ID
                if (buttonId === 'loginBtn') button.textContent = 'Iniciar Sesión';
                else if (buttonId === 'clientSubmitBtn') button.textContent = editingClientId ? 'Actualizar Cliente' : 'Guardar Cliente';
                else if (buttonId === 'taskSubmitBtn') button.textContent = editingTaskId ? 'Actualizar Tarea' : 'Guardar Tarea';
                else if (buttonId === 'noteSubmitBtn') button.textContent = editingNoteId ? 'Actualizar Nota' : 'Guardar Nota';
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Reset forms
            if (modalId === 'clientModal') {
                document.getElementById('clientForm').reset();
                editingClientId = null;
                document.getElementById('clientModalTitle').textContent = 'Agregar Cliente';
            } else if (modalId === 'taskModal') {
                document.getElementById('taskForm').reset();
                editingTaskId = null;
                document.getElementById('taskModalTitle').textContent = 'Agregar Tarea';
            } else if (modalId === 'noteModal') {
                document.getElementById('noteForm').reset();
                editingNoteId = null;
                document.getElementById('noteModalTitle').textContent = 'Agregar Nota';
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
            } else if (sectionName === 'templates') {
                loadTemplates();
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
                loadTemplates()
            ]);
            updateKanbanBoard();
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
                        <button class="btn-small" style="background: #17a2b8; color: white;" onclick="openTaskTemplatesForClient(${client.id}, '${client.name}')">Crear Tareas</button>
                        <button class="btn-small btn-delete" onclick="deleteClient(${client.id})">Eliminar</button>
                    </div>
                `;
                clientsGrid.appendChild(clientCard);
            });
        }

        function openClientModal() {
            updateTaskClientSelect();
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

        function getPriorityText(priority) {
            switch(priority) {
                case 'high': return 'ALTA';
                case 'medium': return 'MEDIA';
                case 'low': return 'BAJA';
                default: return 'MEDIA';
            }
        }

        function openTaskModal() {
            updateTaskClientSelect();
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
                noteCard.className = 'note-card';
                noteCard.innerHTML = `
                    <div class="note-title">${note.title}</div>
                    <div class="note-content">${note.content}</div>
                    <div class="note-meta">
                        ${note.task_title ? `Tarea: ${note.task_title}` : 'Nota general'} - 
                        ${new Date(note.created_at).toLocaleDateString()}
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