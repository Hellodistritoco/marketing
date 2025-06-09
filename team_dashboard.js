// =============================================
// TEAM DASHBOARD JAVASCRIPT - Sistema de Gestión de Equipos
// =============================================

// Variables globales
let currentUser = {
    id: parseInt(document.querySelector('meta[name="user-id"]')?.content) || 1,
    role: document.querySelector('meta[name="user-role"]')?.content || 'admin',
    name: document.querySelector('meta[name="user-name"]')?.content || 'Usuario'
};

let users = [];
let clients = [];
let assignments = [];
let permissions = [];
let currentChatContact = null;
let chatInterval = null;

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando dashboard de equipos...');
    initializeApp();
});

// Función principal de inicialización
async function initializeApp() {
    try {
        setupEventListeners();
        await loadInitialData();
        console.log('✅ Dashboard de equipos inicializado correctamente');
    } catch (error) {
        console.error('❌ Error inicializando aplicación:', error);
        showGlobalError('Error al cargar el sistema. Por favor, recarga la página.');
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Búsqueda de contactos en chat
    const contactSearch = document.getElementById('contactSearch');
    if (contactSearch) {
        contactSearch.addEventListener('input', function() {
            filterContacts(this.value);
        });
    }

    // Cerrar modales al hacer clic fuera
    window.addEventListener('click', function(event) {
        const modals = ['userModal', 'assignmentModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                modal.classList.remove('show');
            }
        });
    });

    // Limpiar intervalos al cambiar de página
    window.addEventListener('beforeunload', function() {
        if (chatInterval) {
            clearInterval(chatInterval);
        }
    });

    // Manejar cambios de visibilidad
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && chatInterval) {
            clearInterval(chatInterval);
            chatInterval = null;
        }
    });
}

// Cargar datos iniciales
async function loadInitialData() {
    console.log('📊 Cargando datos iniciales...');
    
    const loadingPromises = [
        loadUsers(),
        loadClients(),
        loadAssignments(),
        loadPermissions(),
        loadChatContacts()
    ];

    try {
        await Promise.all(loadingPromises);
        updateOverview();
        console.log('✅ Datos iniciales cargados correctamente');
    } catch (error) {
        console.error('❌ Error cargando datos iniciales:', error);
        throw error;
    }
}

// Mostrar sección
function showSection(sectionName) {
    // Ocultar todas las secciones
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remover clase activa de todas las tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Mostrar sección seleccionada
    const targetSection = document.getElementById(sectionName + 'Section');
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Agregar clase activa a la tab seleccionada
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    // Cargar datos específicos según la sección
    switch(sectionName) {
        case 'users':
            displayUsers();
            break;
        case 'assignments':
            displayAssignments();
            break;
        case 'permissions':
            // Los permisos se cargan cuando se selecciona un rol
            break;
        case 'chat':
            loadChatContacts();
            break;
        case 'overview':
            updateOverview();
            break;
    }
}

// Cargar usuarios
async function loadUsers() {
    try {
        const response = await fetch('team_management.php?action=get_users');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            users = result.users || [];
            console.log(`👥 ${users.length} usuarios cargados`);
        } else {
            console.error('Error en respuesta de usuarios:', result);
            users = [];
        }
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        users = [];
    }
}

// Cargar clientes
async function loadClients() {
    try {
        const response = await fetch('clients.php');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            clients = result.clients || [];
            console.log(`👤 ${clients.length} clientes cargados`);
        } else {
            console.error('Error en respuesta de clientes:', result);
            clients = [];
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
        clients = [];
    }
}

// Cargar asignaciones
async function loadAssignments() {
    try {
        const response = await fetch('team_management.php?action=get_assignments');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            assignments = result.assignments || [];
            console.log(`📋 ${assignments.length} asignaciones cargadas`);
        } else {
            console.error('Error en respuesta de asignaciones:', result);
            assignments = [];
        }
    } catch (error) {
        console.error('Error cargando asignaciones:', error);
        assignments = [];
    }
}

// Cargar permisos
async function loadPermissions() {
    try {
        const response = await fetch('team_management.php?action=get_permissions');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (result.success) {
            permissions = result.permissions || [];
            console.log(`🔐 ${permissions.length} permisos cargados`);
        } else {
            console.error('Error en respuesta de permisos:', result);
            permissions = [];
        }
    } catch (error) {
        console.error('Error cargando permisos:', error);
        permissions = [];
    }
}

// Actualizar resumen
function updateOverview() {
    const overviewContainer = document.getElementById('teamOverview');
    if (!overviewContainer) return;
    
    // Calcular estadísticas por rol
    const roleStats = {
        admin: users.filter(u => u.role === 'admin').length,
        manager: users.filter(u => u.role === 'manager').length,
        operario: users.filter(u => u.role === 'operario').length,
        cliente: users.filter(u => u.role === 'cliente').length
    };

    const totalUsers = users.length;
    const totalClients = clients.length;
    const totalAssignments = assignments.length;

    overviewContainer.innerHTML = `
        <!-- Estadísticas Generales -->
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-card-avatar">📊</div>
                <div class="team-card-info">
                    <h3>Estadísticas Generales</h3>
                    <p style="color: #666;">Resumen del sistema</p>
                </div>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${totalUsers}</div>
                    <div class="stat-label">Total Usuarios</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${totalClients}</div>
                    <div class="stat-label">Total Clientes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${totalAssignments}</div>
                    <div class="stat-label">Asignaciones</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${users.filter(u => u.status === 'active').length}</div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
            </div>
        </div>

        <!-- Administradores -->
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-card-avatar">👑</div>
                <div class="team-card-info">
                    <h3>Administradores</h3>
                    <div class="role-badge role-admin">ADMIN</div>
                </div>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${roleStats.admin}</div>
                    <div class="stat-label">Administradores</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Acceso Total</div>
                </div>
            </div>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                Acceso completo al sistema, gestión de usuarios y configuración.
            </p>
        </div>

        <!-- Managers -->
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-card-avatar">🎯</div>
                <div class="team-card-info">
                    <h3>Managers</h3>
                    <div class="role-badge role-manager">MANAGER</div>
                </div>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${roleStats.manager}</div>
                    <div class="stat-label">Managers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${assignments.filter(a => users.find(u => u.id == a.operario_id)?.role === 'manager').length}</div>
                    <div class="stat-label">Clientes Asignados</div>
                </div>
            </div>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                Gestión de tareas, clientes y supervisión de operarios.
            </p>
        </div>

        <!-- Operarios -->
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-card-avatar">🔧</div>
                <div class="team-card-info">
                    <h3>Operarios</h3>
                    <div class="role-badge role-operario">OPERARIO</div>
                </div>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${roleStats.operario}</div>
                    <div class="stat-label">Operarios</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${assignments.filter(a => users.find(u => u.id == a.operario_id)?.role === 'operario').length}</div>
                    <div class="stat-label">Clientes Asignados</div>
                </div>
            </div>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                Ejecución de tareas para clientes asignados.
            </p>
        </div>

        <!-- Clientes -->
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-card-avatar">👤</div>
                <div class="team-card-info">
                    <h3>Clientes</h3>
                    <div class="role-badge role-cliente">CLIENTE</div>
                </div>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${roleStats.cliente}</div>
                    <div class="stat-label">Usuarios Cliente</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${totalClients}</div>
                    <div class="stat-label">Perfiles Cliente</div>
                </div>
            </div>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                Acceso a su perfil, tareas y programación de reuniones.
            </p>
        </div>
    `;
}

// Mostrar usuarios
function displayUsers() {
    const usersGrid = document.getElementById('usersGrid');
    if (!usersGrid) return;
    
    if (users.length === 0) {
        usersGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">No hay usuarios registrados</div>';
        return;
    }

    usersGrid.innerHTML = '';
    
    users.forEach(user => {
        const userCard = document.createElement('div');
        userCard.className = 'team-card';
        userCard.innerHTML = `
            <div class="team-card-header">
                <div class="team-card-avatar">
                    ${user.full_name.charAt(0).toUpperCase()}
                </div>
                <div class="team-card-info">
                    <h3>${escapeHtml(user.full_name)}</h3>
                    <div class="role-badge role-${user.role}">${user.role.toUpperCase()}</div>
                </div>
            </div>
            <div style="margin: 15px 0;">
                <p><strong>Usuario:</strong> ${escapeHtml(user.username)}</p>
                <p><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                <p><strong>Departamento:</strong> ${escapeHtml(user.department || 'No especificado')}</p>
                <p><strong>Estado:</strong> 
                    <span style="color: ${user.status === 'active' ? '#28a745' : '#dc3545'};">
                        ${user.status === 'active' ? 'Activo' : 'Inactivo'}
                    </span>
                </p>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${user.assigned_clients || 0}</div>
                    <div class="stat-label">Clientes Asignados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${user.total_tasks || 0}</div>
                    <div class="stat-label">Total Tareas</div>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="editUser(${user.id})" style="flex: 1;">
                    ✏️ Editar
                </button>
                <button class="btn btn-secondary" onclick="chatWithUser(${user.id}, '${escapeHtml(user.full_name)}')" style="flex: 1;">
                    💬 Chat
                </button>
            </div>
        `;
        usersGrid.appendChild(userCard);
    });
}

// Mostrar asignaciones
function displayAssignments() {
    const assignmentsGrid = document.getElementById('assignmentsGrid');
    if (!assignmentsGrid) return;
    
    if (assignments.length === 0) {
        assignmentsGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">No hay asignaciones registradas</div>';
        return;
    }

    assignmentsGrid.innerHTML = '';
    
    assignments.forEach(assignment => {
        const assignmentCard = document.createElement('div');
        assignmentCard.className = 'team-card';
        assignmentCard.innerHTML = `
            <div class="team-card-header">
                <div class="team-card-avatar">🔗</div>
                <div class="team-card-info">
                    <h3>${escapeHtml(assignment.nombre_cliente)}</h3>
                    <p style="color: #666;">Asignado a: ${escapeHtml(assignment.operario_name)}</p>
                </div>
            </div>
            <div style="margin: 15px 0;">
                <p><strong>Cliente:</strong> ${escapeHtml(assignment.nombre_cliente)}</p>
                <p><strong>Empresa:</strong> ${escapeHtml(assignment.client_company || 'No especificada')}</p>
                <p><strong>Operario:</strong> ${escapeHtml(assignment.operario_name)}</p>
                <p><strong>Asignado por:</strong> ${escapeHtml(assignment.assigned_by_name)}</p>
                <p><strong>Fecha:</strong> ${new Date(assignment.assigned_at).toLocaleDateString()}</p>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="viewClientTasks(${assignment.cliente_id})" style="flex: 1;">
                    📋 Ver Tareas
                </button>
                <button class="btn btn-secondary" onclick="reassignClient(${assignment.cliente_id})" style="flex: 1;">
                    🔄 Reasignar
                </button>
            </div>
        `;
        assignmentsGrid.appendChild(assignmentCard);
    });
}

// Funciones de modal
function openUserModal() {
    const modal = document.getElementById('userModal');
    if (modal) {
        modal.classList.add('show');
    }
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    if (modal) {
        modal.classList.remove('show');
    }
    
    const form = document.getElementById('userForm');
    if (form) {
        form.reset();
    }
    
    const message = document.getElementById('userMessage');
    if (message) {
        message.innerHTML = '';
    }
}

function openAssignmentModal() {
    loadAssignmentOptions();
    const modal = document.getElementById('assignmentModal');
    if (modal) {
        modal.classList.add('show');
    }
}

function closeAssignmentModal() {
    const modal = document.getElementById('assignmentModal');
    if (modal) {
        modal.classList.remove('show');
    }
    
    const form = document.getElementById('assignmentForm');
    if (form) {
        form.reset();
    }
    
    const message = document.getElementById('assignmentMessage');
    if (message) {
        message.innerHTML = '';
    }
}

// Cargar opciones para asignaciones
function loadAssignmentOptions() {
    // Cargar clientes
    const clientSelect = document.getElementById('assignmentClient');
    if (clientSelect) {
        clientSelect.innerHTML = '<option value="">Seleccionar cliente</option>';
        clients.forEach(client => {
            clientSelect.innerHTML += `<option value="${client.id}">${escapeHtml(client.nombre_cliente)}</option>`;
        });
    }

    // Cargar operarios y managers
    const operarioSelect = document.getElementById('assignmentOperario');
    if (operarioSelect) {
        operarioSelect.innerHTML = '<option value="">Seleccionar operario</option>';
        users.filter(u => ['operario', 'manager'].includes(u.role) && u.status === 'active').forEach(user => {
            operarioSelect.innerHTML += `<option value="${user.id}">${escapeHtml(user.full_name)} (${user.role})</option>`;
        });
    }
}

// Manejar envío de formulario de usuario
async function handleUserSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const userData = {
        username: formData.get('username'),
        full_name: formData.get('full_name'),
        email: formData.get('email'),
        password: formData.get('password'),
        role: formData.get('role'),
        phone: formData.get('phone'),
        department: formData.get('department'),
        hire_date: formData.get('hire_date')
    };

    try {
        const response = await fetch('team_management.php?action=create_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            showMessage('userMessage', result.message, false);
            await loadUsers();
            updateOverview();
            setTimeout(() => closeUserModal(), 2000);
        } else {
            showMessage('userMessage', result.message, true);
        }
    } catch (error) {
        console.error('Error creando usuario:', error);
        showMessage('userMessage', 'Error de conexión', true);
    }
}

// Manejar envío de formulario de asignación
async function handleAssignmentSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const assignmentData = {
        cliente_id: formData.get('cliente_id'),
        operario_id: formData.get('operario_id'),
        assigned_by: currentUser.id
    };

    try {
        const response = await fetch('team_management.php?action=assign_client', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(assignmentData)
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            showMessage('assignmentMessage', result.message, false);
            await loadAssignments();
            updateOverview();
            setTimeout(() => closeAssignmentModal(), 2000);
        } else {
            showMessage('assignmentMessage', result.message, true);
        }
    } catch (error) {
        console.error('Error creando asignación:', error);
        showMessage('assignmentMessage', 'Error de conexión', true);
    }
}

// Filtrar usuarios
function filterUsers() {
    const searchTerm = document.getElementById('userSearch')?.value?.toLowerCase() || '';
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    
    const filteredUsers = users.filter(user => {
        const matchesSearch = user.full_name.toLowerCase().includes(searchTerm) || 
                            user.username.toLowerCase().includes(searchTerm) ||
                            user.email.toLowerCase().includes(searchTerm);
        const matchesRole = !roleFilter || user.role === roleFilter;
        
        return matchesSearch && matchesRole;
    });

    // Actualizar vista con usuarios filtrados
    const usersGrid = document.getElementById('usersGrid');
    if (!usersGrid) return;
    
    usersGrid.innerHTML = '';
    
    if (filteredUsers.length === 0) {
        usersGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">No se encontraron usuarios</div>';
        return;
    }
    
    filteredUsers.forEach(user => {
        const userCard = document.createElement('div');
        userCard.className = 'team-card';
        userCard.innerHTML = `
            <div class="team-card-header">
                <div class="team-card-avatar">
                    ${user.full_name.charAt(0).toUpperCase()}
                </div>
                <div class="team-card-info">
                    <h3>${escapeHtml(user.full_name)}</h3>
                    <div class="role-badge role-${user.role}">${user.role.toUpperCase()}</div>
                </div>
            </div>
            <div style="margin: 15px 0;">
                <p><strong>Usuario:</strong> ${escapeHtml(user.username)}</p>
                <p><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                <p><strong>Departamento:</strong> ${escapeHtml(user.department || 'No especificado')}</p>
                <p><strong>Estado:</strong> 
                    <span style="color: ${user.status === 'active' ? '#28a745' : '#dc3545'};">
                        ${user.status === 'active' ? 'Activo' : 'Inactivo'}
                    </span>
                </p>
            </div>
            <div class="team-card-stats">
                <div class="stat-item">
                    <div class="stat-number">${user.assigned_clients || 0}</div>
                    <div class="stat-label">Clientes Asignados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${user.total_tasks || 0}</div>
                    <div class="stat-label">Total Tareas</div>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="editUser(${user.id})" style="flex: 1;">
                    ✏️ Editar
                </button>
                <button class="btn btn-secondary" onclick="chatWithUser(${user.id}, '${escapeHtml(user.full_name)}')" style="flex: 1;">
                    💬 Chat
                </button>
            </div>
        `;
        usersGrid.appendChild(userCard);
    });
}

// Cargar permisos por rol
async function loadRolePermissions() {
    const roleSelect = document.getElementById('rolePermissionSelect');
    const content = document.getElementById('permissionsContent');
    
    if (!roleSelect || !content) return;
    
    const role = roleSelect.value;
    
    if (!role) {
        content.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">Selecciona un rol para ver sus permisos</div>';
        return;
    }

    try {
        const response = await fetch(`team_management.php?action=get_role_permissions&role=${encodeURIComponent(role)}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
            const rolePermissions = result.permissions || [];
            
            content.innerHTML = `
                <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3 style="color: #121A28; margin-bottom: 20px;">
                        🔐 Permisos del rol: <span class="role-badge role-${role}">${role.toUpperCase()}</span>
                    </h3>
                    <div class="permissions-grid">
                        ${permissions.map(permission => `
                            <div class="permission-item">
                                <input type="checkbox" class="permission-checkbox" 
                                       ${rolePermissions.includes(permission.id) ? 'checked' : ''} 
                                       data-permission="${permission.id}" 
                                       onchange="updateRolePermission('${role}', ${permission.id}, this.checked)">
                                <div>
                                    <strong>${escapeHtml(permission.permission_name)}</strong>
                                    <br><small style="color: #666;">${escapeHtml(permission.description)}</small>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando permisos:', error);
        content.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">Error al cargar permisos</div>';
    }
}

// Actualizar permiso de rol
async function updateRolePermission(role, permissionId, granted) {
    try {
        const response = await fetch('team_management.php?action=update_role_permission', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                role: role,
                permission_id: permissionId,
                granted: granted
            })
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        
        if (!result.success) {
            alert('Error al actualizar permiso: ' + result.message);
            // Revertir checkbox
            if (event && event.target) {
                event.target.checked = !granted;
            }
        } else {
            console.log(`✅ Permiso ${granted ? 'otorgado' : 'revocado'} correctamente`);
        }
    } catch (error) {
        console.error('Error actualizando permiso:', error);
        alert('Error de conexión');
        if (event && event.target) {
            event.target.checked = !granted;
        }
    }
}

// Funciones de Chat
async function loadChatContacts() {
    try {
        const response = await fetch(`chat_system.php?action=get_contacts&user_id=${currentUser.id}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
            displayChatContacts(result.contacts || []);
        } else {
            console.error('Error en respuesta de contactos:', result);
        }
    } catch (error) {
        console.error('Error cargando contactos:', error);
        const contactsList = document.getElementById('contactsList');
        if (contactsList) {
            contactsList.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Error cargando contactos</div>';
        }
    }
}

function displayChatContacts(contacts) {
    const contactsList = document.getElementById('contactsList');
    if (!contactsList) return;
    
    if (contacts.length === 0) {
        contactsList.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">No hay conversaciones</div>';
        return;
    }

    contactsList.innerHTML = '';
    
    contacts.forEach(contact => {
        const contactElement = document.createElement('div');
        contactElement.className = 'contact-item';
        contactElement.onclick = () => openChat(contact.contact_id, contact.contact_name);
        
        contactElement.innerHTML = `
            <div class="contact-info">
                <div class="contact-avatar">
                    ${contact.contact_name.charAt(0).toUpperCase()}
                </div>
                <div style="flex: 1;">
                    <div class="contact-name">${escapeHtml(contact.contact_name)}</div>
                    <div class="contact-last-message">
                        ${contact.last_message ? escapeHtml(contact.last_message.substring(0, 30)) + '...' : 'Sin mensajes'}
                    </div>
                </div>
                ${contact.unread_count > 0 ? `<div class="unread-badge">${contact.unread_count}</div>` : ''}
            </div>
        `;
        
        contactsList.appendChild(contactElement);
    });
}

function filterContacts(searchTerm) {
    const contacts = document.querySelectorAll('.contact-item');
    contacts.forEach(contact => {
        const nameElement = contact.querySelector('.contact-name');
        if (nameElement) {
            const name = nameElement.textContent.toLowerCase();
            contact.style.display = name.includes(searchTerm.toLowerCase()) ? 'block' : 'none';
        }
    });
}

async function openChat(contactId, contactName) {
    currentChatContact = { id: contactId, name: contactName };
    
    // Actualizar header del chat
    const chatHeader = document.getElementById('chatHeader');
    if (chatHeader) {
        chatHeader.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="contact-avatar">${contactName.charAt(0).toUpperCase()}</div>
                <div>
                    <h3 style="margin: 0;">${escapeHtml(contactName)}</h3>
                    <p style="margin: 0; opacity: 0.9; font-size: 14px;">En línea</p>
                </div>
            </div>
        `;
    }
    
    // Mostrar input de chat
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.style.display = 'block';
    }
    
    // Marcar contacto como activo
    document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
    if (event && event.target) {
        const contactItem = event.target.closest('.contact-item');
        if (contactItem) {
            contactItem.classList.add('active');
        }
    }
    
    // Cargar mensajes
    await loadChatMessages(contactId);
    
    // Marcar mensajes como leídos
    await markMessagesAsRead(contactId);
    
    // Iniciar actualización automática
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(() => loadChatMessages(contactId), 3000);
}

async function loadChatMessages(contactId) {
    try {
        const response = await fetch(`chat_system.php?action=get_conversation&user1=${currentUser.id}&user2=${contactId}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
            displayChatMessages(result.messages || []);
        }
    } catch (error) {
        console.error('Error cargando mensajes:', error);
    }
}

function displayChatMessages(messages) {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;
    
    if (messages.length === 0) {
        messagesContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">No hay mensajes en esta conversación</div>';
        return;
    }

    messagesContainer.innerHTML = '';
    
    messages.forEach(message => {
        const messageElement = document.createElement('div');
        messageElement.className = `message ${message.sender_id == currentUser.id ? 'own' : ''}`;
        
        messageElement.innerHTML = `
            <div class="message-bubble">
                <div>${escapeHtml(message.message)}</div>
                <div class="message-time">
                    ${new Date(message.created_at).toLocaleTimeString()}
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(messageElement);
    });
    
    // Scroll al final
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

async function sendMessage(event) {
    event.preventDefault();
    
    if (!currentChatContact) return;
    
    const messageInput = document.getElementById('messageInput');
    if (!messageInput) return;
    
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    try {
        const response = await fetch('chat_system.php?action=send_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sender_id: currentUser.id,
                receiver_id: currentChatContact.id,
                message: message
            })
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            messageInput.value = '';
            await loadChatMessages(currentChatContact.id);
        } else {
            alert('Error al enviar mensaje: ' + result.message);
        }
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        alert('Error de conexión');
    }
}

async function markMessagesAsRead(senderId) {
    try {
        await fetch('chat_system.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: currentUser.id,
                sender_id: senderId
            })
        });
    } catch (error) {
        console.error('Error marcando mensajes como leídos:', error);
    }
}

function chatWithUser(userId, userName) {
    // Cambiar a la sección de chat
    showSection('chat');
    
    // Abrir chat con el usuario después de un breve delay
    setTimeout(() => {
        openChat(userId, userName);
    }, 500);
}

// Funciones auxiliares
function showMessage(elementId, message, isError = false) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `<div class="alert ${isError ? 'alert-error' : 'alert-success'}">${escapeHtml(message)}</div>`;
    }
}

function showGlobalError(message) {
    console.error('Error global:', message);
    // Aquí podrías mostrar una notificación global
    alert(message);
}

function refreshData() {
    console.log('🔄 Refrescando datos...');
    loadInitialData();
}

function editUser(userId) {
    // TODO: Implementar edición de usuario
    console.log('Editando usuario:', userId);
    alert('Función de edición en desarrollo');
}

function viewClientTasks(clientId) {
    // Redirigir a vista de tareas del cliente
    window.open(`reports_page.php?cliente=${clientId}`, '_blank');
}

function reassignClient(clientId) {
    // Abrir modal de reasignación
    openAssignmentModal();
    
    // Pre-seleccionar el cliente
    setTimeout(() => {
        const clientSelect = document.getElementById('assignmentClient');
        if (clientSelect) {
            clientSelect.value = clientId;
        }
    }, 100);
}

// Función para escapar HTML
function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Función para formatear fechas
function formatDate(dateString) {
    try {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (error) {
        return dateString;
    }
}

// Función para detectar el rol del usuario desde PHP
function detectUserRole() {
    // Intentar obtener el rol desde elementos PHP
    const bodyElement = document.body;
    const userRole = bodyElement.dataset.userRole || 'user';
    const userId = bodyElement.dataset.userId || '1';
    const userName = bodyElement.dataset.userName || 'Usuario';
    
    currentUser = {
        id: parseInt(userId),
        role: userRole,
        name: userName
    };
    
    console.log('👤 Usuario detectado:', currentUser);
}

// Auto-inicialización adicional
document.addEventListener('DOMContentLoaded', function() {
    detectUserRole();
});

// Debug: Exponer funciones globales para testing
if (typeof window !== 'undefined') {
    window.TeamDashboard = {
        currentUser,
        users,
        clients,
        assignments,
        permissions,
        loadInitialData,
        refreshData,
        showSection
    };
}