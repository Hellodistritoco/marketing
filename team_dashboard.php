<?php
// =============================================
// TEAM_DASHBOARD.PHP - Interfaz Principal de Gestión de Equipos
// =============================================

require_once 'config.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Obtener información del usuario actual
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'];

// Verificar permisos básicos para acceder al dashboard de equipos
$allowed_roles = ['admin', 'manager'];
if (!in_array($user_role, $allowed_roles)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos - Marketing Kanban</title>
    
    <!-- CSS Externo -->
    <link rel="stylesheet" href="team_dashboard.css">
    
    <!-- Meta Tags -->
    <meta name="description" content="Sistema de gestión de equipos con roles, permisos y chat interno">
    <meta name="keywords" content="gestión, equipos, roles, permisos, chat, marketing">
    <meta name="author" content="Marketing Kanban System">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-info">
                <div>
                    <h1>👥 Gestión de Equipos</h1>
                    <p style="color: #121A28; margin-top: 5px;">Sistema de administración de usuarios, roles y permisos</p>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #121A28;">
                            <?php echo htmlspecialchars($user_name); ?>
                        </div>
                        <div class="role-badge role-<?php echo $user_role; ?>">
                            <?php echo ucfirst($user_role); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Controls -->
        <div class="navigation-controls">
            <a href="index.php" class="nav-btn">🏠 Dashboard Principal</a>
            <a href="reports_page.php" class="nav-btn">📊 Reportes</a>
            <a href="tareas_calendar.php" class="nav-btn">📅 Calendario</a>
            <a href="team_dashboard.php" class="nav-btn active">👥 Gestión de Equipos</a>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="showSection('overview')">📊 Resumen</div>
            <div class="nav-tab" onclick="showSection('users')">👤 Usuarios</div>
            <div class="nav-tab" onclick="showSection('assignments')">📋 Asignaciones</div>
            <div class="nav-tab" onclick="showSection('permissions')">🔐 Permisos</div>
            <div class="nav-tab" onclick="showSection('chat')">💬 Chat Interno</div>
        </div>

        <!-- Overview Section -->
        <div id="overviewSection" class="content-section active">
            <div class="actions-bar">
                <h2 style="color: #121A28; margin: 0;">📈 Resumen del Equipo</h2>
                <button class="btn btn-primary" onclick="refreshData()">🔄 Actualizar Datos</button>
            </div>

            <div class="team-grid" id="teamOverview">
                <div class="loading">Cargando datos del equipo...</div>
            </div>
        </div>

        <!-- Users Section -->
        <div id="usersSection" class="content-section">
            <div class="actions-bar">
                <div class="search-box">
                    <input type="text" id="userSearch" placeholder="Buscar usuarios..." onkeyup="filterUsers()">
                    <select id="roleFilter" onchange="filterUsers()">
                        <option value="">Todos los roles</option>
                        <option value="admin">Administrador</option>
                        <option value="manager">Manager</option>
                        <option value="operario">Operario</option>
                        <option value="cliente">Cliente</option>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="openUserModal()">➕ Agregar Usuario</button>
            </div>

            <div class="team-grid" id="usersGrid">
                <div class="loading">Cargando usuarios...</div>
            </div>
        </div>

        <!-- Assignments Section -->
        <div id="assignmentsSection" class="content-section">
            <div class="actions-bar">
                <h2 style="color: #121A28; margin: 0;">📋 Asignación Cliente-Operario</h2>
                <button class="btn btn-primary" onclick="openAssignmentModal()">🔗 Nueva Asignación</button>
            </div>

            <div class="team-grid" id="assignmentsGrid">
                <div class="loading">Cargando asignaciones...</div>
            </div>
        </div>

        <!-- Permissions Section -->
        <div id="permissionsSection" class="content-section">
            <div class="actions-bar">
                <h2 style="color: #121A28; margin: 0;">🔐 Gestión de Permisos</h2>
                <select id="rolePermissionSelect" onchange="loadRolePermissions()">
                    <option value="">Seleccionar rol</option>
                    <option value="admin">Administrador</option>
                    <option value="manager">Manager</option>
                    <option value="operario">Operario</option>
                    <option value="cliente">Cliente</option>
                </select>
            </div>

            <div id="permissionsContent">
                <div style="text-align: center; padding: 40px; color: #666;">
                    Selecciona un rol para ver sus permisos
                </div>
            </div>
        </div>

        <!-- Chat Section -->
        <div id="chatSection" class="content-section">
            <div class="chat-container">
                <div class="chat-sidebar">
                    <div style="padding: 20px; border-bottom: 1px solid #eee; background: white;">
                        <h3 style="color: #121A28; margin-bottom: 10px;">💬 Conversaciones</h3>
                        <input type="text" id="contactSearch" placeholder="Buscar contacto..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div id="contactsList">
                        <div class="loading">Cargando contactos...</div>
                    </div>
                </div>
                <div class="chat-main">
                    <div class="chat-header" id="chatHeader">
                        <div style="text-align: center;">
                            <h3>Selecciona una conversación</h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">Elige un contacto para comenzar a chatear</p>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div style="text-align: center; padding: 40px; color: #666;">
                            Selecciona un contacto para ver la conversación
                        </div>
                    </div>
                    <div class="chat-input" id="chatInput" style="display: none;">
                        <form class="chat-input-form" onsubmit="sendMessage(event)">
                            <input type="text" id="messageInput" placeholder="Escribe tu mensaje..." required>
                            <button type="submit" class="btn btn-primary">Enviar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Agregar Usuario</h3>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <form id="userForm" onsubmit="handleUserSubmit(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label for="userName">Nombre Completo:</label>
                        <input type="text" id="userName" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="userUsername">Usuario:</label>
                        <input type="text" id="userUsername" name="username" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userEmail">Email:</label>
                        <input type="email" id="userEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="userRole">Rol:</label>
                        <select id="userRole" name="role" required>
                            <option value="">Seleccionar rol</option>
                            <option value="admin">Administrador</option>
                            <option value="manager">Manager</option>
                            <option value="operario">Operario</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userPassword">Contraseña:</label>
                        <input type="password" id="userPassword" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="userPhone">Teléfono:</label>
                        <input type="tel" id="userPhone" name="phone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userDepartment">Departamento:</label>
                        <input type="text" id="userDepartment" name="department" placeholder="Marketing, Ventas, etc.">
                    </div>
                    <div class="form-group">
                        <label for="userHireDate">Fecha de Contratación:</label>
                        <input type="date" id="userHireDate" name="hire_date">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Usuario</button>
                <div id="userMessage" style="margin-top: 15px;"></div>
            </form>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔗 Asignar Cliente a Operario</h3>
                <span class="close" onclick="closeAssignmentModal()">&times;</span>
            </div>
            <form id="assignmentForm" onsubmit="handleAssignmentSubmit(event)">
                <div class="form-group">
                    <label for="assignmentClient">Cliente:</label>
                    <select id="assignmentClient" name="cliente_id" required>
                        <option value="">Seleccionar cliente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assignmentOperario">Operario/Manager:</label>
                    <select id="assignmentOperario" name="operario_id" required>
                        <option value="">Seleccionar operario</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Asignación</button>
                <div id="assignmentMessage" style="margin-top: 15px;"></div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="team_dashboard.js"></script>
</body>
</html>