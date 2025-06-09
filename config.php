<?php
// ✅ Configuración CORREGIDA de la base de datos
define('DB_HOST', 'server71.web-hosting.com');  // SIN https:// ni /
define('DB_NAME', 'hellfhpr_database');
define('DB_USER', 'hellfhpr_kanban');
define('DB_PASS', 'xetw%2r&4#}R');

// Si tu hosting usa un puerto específico, agrégalo así:
// define('DB_HOST', 'server71.web-hosting.com:3306');

// Configuración de sesión
session_start();

// Función para conectar a la base de datos con mejor manejo de errores
function conectarDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch(PDOException $e) {
        // Log del error para debugging
        error_log("Error de conexión DB: " . $e->getMessage());
        
        // Mostrar error más detallado para debugging
        die("Error de conexión: " . $e->getMessage() . "<br><br>
             <strong>Verifica:</strong><br>
             - Host: " . DB_HOST . "<br>
             - Base de datos: " . DB_NAME . "<br>
             - Usuario: " . DB_USER . "<br>
             - ¿El servidor MySQL está activo?<br>
             - ¿Los datos de conexión son correctos?");
    }
}

// Función para verificar si el usuario está logueado
function verificarLogin() {
    if (!isset($_SESSION['usuario_logueado'])) {
        header('Location: login.php');
        exit();
    }
}

// Función para obtener todos los clientes
function obtenerClientes() {
    $pdo = conectarDB();
    $stmt = $pdo->query("SELECT * FROM clientes ORDER BY nombre_cliente");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener tareas por estado
function obtenerTareasPorEstado() {
    $pdo = conectarDB();
    $stmt = $pdo->query("
        SELECT t.*, c.nombre_cliente 
        FROM tareas t 
        LEFT JOIN clientes c ON t.cliente_id = c.id 
        ORDER BY t.fecha_creacion DESC
    ");
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $kanban = [
        'Por Hacer' => [],
        'En Progreso' => [],
        'En Revisión' => [],
        'Completado' => []
    ];
    
    foreach ($tareas as $tarea) {
        $kanban[$tarea['estado']][] = $tarea;
    }
    
    return $kanban;
}

// Función para probar la conexión
function probarConexion() {
    try {
        $pdo = conectarDB();
        echo "✅ Conexión exitosa a la base de datos!<br>";
        echo "Host: " . DB_HOST . "<br>";
        echo "Base de datos: " . DB_NAME . "<br>";
        echo "Usuario: " . DB_USER . "<br>";
        return true;
    } catch (Exception $e) {
        echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Colores del sistema
define('COLOR_PRIMARIO', '#F09146');
define('COLOR_SECUNDARIO', '#121A28');
define('COLOR_ACCENT', '#A23004');
?>