<?php
require_once 'config.php';
require_once 'google_calendar_config.php';
verificarLogin();

echo "<h1>🔧 Solucionador Error 403: access_denied</h1>";
echo "<hr>";

// Detectar el problema específico
echo "<h2>🎯 Diagnóstico del Error 403</h2>";
echo "<div style='background:#f8d7da; padding:20px; border-radius:10px; margin:20px 0; border-left:5px solid #dc3545;'>";
echo "<h3>❌ Error detectado: 403 access_denied</h3>";
echo "<p>Este error indica que Google rechazó la autorización por una de estas razones:</p>";
echo "<ul>";
echo "<li>🔒 <strong>Cliente OAuth no verificado</strong> (más probable)</li>";
echo "<li>⚙️ <strong>Configuración incorrecta en Google Cloud Console</strong></li>";
echo "<li>🚫 <strong>Permisos insuficientes en el proyecto</strong></li>";
echo "<li>📧 <strong>Cuenta de Google con restricciones</strong></li>";
echo "</ul>";
echo "</div>";

// Verificar configuración actual
echo "<h2>📋 Configuración Actual</h2>";
echo "<table border='1' style='border-collapse:collapse; width:100%; margin-bottom:20px;'>";
echo "<tr style='background:#F09146; color:white;'><th>Parámetro</th><th>Valor</th><th>Estado</th></tr>";

$client_id_valid = !empty(GOOGLE_CLIENT_ID) && GOOGLE_CLIENT_ID !== 'TU_CLIENT_ID.apps.googleusercontent.com';
$client_secret_valid = !empty(GOOGLE_CLIENT_SECRET) && GOOGLE_CLIENT_SECRET !== 'TU_CLIENT_SECRET';
$redirect_uri_valid = !empty(GOOGLE_REDIRECT_URI) && strpos(GOOGLE_REDIRECT_URI, 'hellodistrito.com') !== false;

echo "<tr><td><strong>Client ID</strong></td><td>" . ($client_id_valid ? '✅ Configurado' : '❌ No configurado') . "</td><td>" . (strlen(GOOGLE_CLIENT_ID) > 20 ? '✅' : '❌') . "</td></tr>";
echo "<tr><td><strong>Client Secret</strong></td><td>" . ($client_secret_valid ? '✅ Configurado' : '❌ No configurado') . "</td><td>" . (strlen(GOOGLE_CLIENT_SECRET) > 10 ? '✅' : '❌') . "</td></tr>";
echo "<tr><td><strong>Redirect URI</strong></td><td>" . GOOGLE_REDIRECT_URI . "</td><td>" . ($redirect_uri_valid ? '✅' : '❌') . "</td></tr>";
echo "<tr><td><strong>Scopes</strong></td><td>" . GOOGLE_SCOPES . "</td><td>✅</td></tr>";
echo "</table>";

// Solución paso a paso
echo "<h2>🚀 Solución Paso a Paso</h2>";

echo "<div style='background:#fff3cd; padding:20px; border-radius:10px; margin:20px 0; border-left:5px solid #ffc107;'>";
echo "<h3>⚡ SOLUCIÓN RÁPIDA: Pantalla de consentimiento OAuth</h3>";
echo "<p>El problema más común es que <strong>la pantalla de consentimiento OAuth no está configurada o publicada</strong>.</p>";
echo "</div>";

echo "<div style='background:#e3f2fd; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>📝 Paso 1: Configurar Pantalla de Consentimiento</h3>";
echo "<ol>";
echo "<li>Ve a <a href='https://console.cloud.google.com/apis/credentials/consent' target='_blank'>📋 Google Cloud Console - Pantalla de consentimiento OAuth</a></li>";
echo "<li><strong>Tipo de usuario:</strong> Selecciona <code>Externo</code></li>";
echo "<li><strong>Información de la aplicación:</strong>";
echo "<ul style='margin:10px 0;'>";
echo "<li><strong>Nombre de la aplicación:</strong> <code>Sistema Kanban</code></li>";
echo "<li><strong>Email de asistencia al usuario:</strong> <code>tu-email@gmail.com</code></li>";
echo "<li><strong>Dominio autorizado:</strong> <code>hellodistrito.com</code></li>";
echo "<li><strong>Email de contacto del desarrollador:</strong> <code>tu-email@gmail.com</code></li>";
echo "</ul></li>";
echo "<li><strong>Alcances:</strong> Agregar <code>https://www.googleapis.com/auth/calendar</code></li>";
echo "<li><strong>Usuarios de prueba:</strong> Agregar tu email y emails de quienes van a usar el sistema</li>";
echo "<li><strong>¡IMPORTANTE!</strong> Haz clic en <strong>\"PUBLICAR APLICACIÓN\"</strong></li>";
echo "</ol>";
echo "</div>";

echo "<div style='background:#d4edda; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>🔧 Paso 2: Verificar Credenciales OAuth</h3>";
echo "<ol>";
echo "<li>Ve a <a href='https://console.cloud.google.com/apis/credentials' target='_blank'>🔑 Google Cloud Console - Credenciales</a></li>";
echo "<li>Edita tu <strong>Cliente OAuth 2.0</strong></li>";
echo "<li>Verifica estos campos:";
echo "<ul style='margin:10px 0;'>";
echo "<li><strong>Orígenes autorizados JavaScript:</strong><br><code>https://hellodistrito.com</code></li>";
echo "<li><strong>URIs de redirección autorizadas:</strong><br><code>https://hellodistrito.com/marketing/google_callback.php</code></li>";
echo "</ul></li>";
echo "<li>Guarda los cambios</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background:#f0f8ff; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>📋 Paso 3: Verificar API habilitada</h3>";
echo "<ol>";
echo "<li>Ve a <a href='https://console.cloud.google.com/apis/library/calendar-json.googleapis.com' target='_blank'>📅 Google Calendar API</a></li>";
echo "<li>Asegúrate de que esté <strong>\"HABILITADA\"</strong></li>";
echo "<li>Si no está habilitada, haz clic en <strong>\"HABILITAR\"</strong></li>";
echo "</ol>";
echo "</div>";

// Generar URL de prueba
echo "<h2>🧪 Prueba de Conexión</h2>";

// URL con parámetros adicionales para debugging
$auth_params = array(
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'scope' => GOOGLE_SCOPES,
    'response_type' => 'code',
    'access_type' => 'offline',
    'approval_prompt' => 'force',
    'include_granted_scopes' => 'true',
    'state' => 'test_' . time()
);

$test_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($auth_params);

echo "<div style='background:#f8f9fa; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>🚀 Probar Conexión OAuth</h3>";
echo "<p>Después de configurar la pantalla de consentimiento, prueba este enlace:</p>";
echo "<p><a href='$test_url' target='_blank' style='background:#28a745; color:white; padding:15px 30px; text-decoration:none; border-radius:10px; font-weight:bold; display:inline-block; margin:10px 0;'>🔗 Probar Autorización Google</a></p>";
echo "<p><small>Este enlace incluye parámetros adicionales para mejor compatibilidad.</small></p>";
echo "</div>";

// Configuración recomendada para google_calendar_config.php
echo "<h2>⚙️ Configuración Recomendada</h2>";
echo "<div style='background:#f8f9fa; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>📝 google_calendar_config.php optimizado</h3>";
echo "<pre style='background:white; padding:15px; border-radius:5px; overflow-x:auto;'>";
echo htmlspecialchars('<?php
// Configuración OAuth Google Calendar - OPTIMIZADA PARA EVITAR ERROR 403

define(\'GOOGLE_CLIENT_ID\', \'TU_CLIENT_ID.apps.googleusercontent.com\');
define(\'GOOGLE_CLIENT_SECRET\', \'TU_CLIENT_SECRET\');
define(\'GOOGLE_REDIRECT_URI\', \'https://hellodistrito.com/marketing/google_callback.php\');

// Scopes más específicos
define(\'GOOGLE_SCOPES\', \'https://www.googleapis.com/auth/calendar\');

// Función mejorada para URL de autorización
function getGoogleAuthUrl() {
    $params = array(
        \'client_id\' => GOOGLE_CLIENT_ID,
        \'redirect_uri\' => GOOGLE_REDIRECT_URI,
        \'scope\' => GOOGLE_SCOPES,
        \'response_type\' => \'code\',
        \'access_type\' => \'offline\',
        \'approval_prompt\' => \'force\',
        \'include_granted_scopes\' => \'true\',
        \'state\' => \'kanban_\' . time()
    );
    
    return \'https://accounts.google.com/o/oauth2/auth?\' . http_build_query($params);
}
');
echo "</pre>";
echo "</div>";

// Checklist final
echo "<h2>✅ Checklist Final</h2>";
echo "<div style='background:#e8f5e8; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>📋 Antes de probar de nuevo:</h3>";
echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
echo "<tr style='background:#28a745; color:white;'><th>Tarea</th><th>Estado</th><th>Acción</th></tr>";
echo "<tr><td>🔧 Pantalla de consentimiento configurada</td><td>❓</td><td>Configurar en Google Cloud Console</td></tr>";
echo "<tr><td>📱 Aplicación publicada (no en modo prueba)</td><td>❓</td><td>Hacer clic en 'PUBLICAR APLICACIÓN'</td></tr>";
echo "<tr><td>📅 Google Calendar API habilitada</td><td>❓</td><td>Verificar en biblioteca de APIs</td></tr>";
echo "<tr><td>🔑 Credenciales OAuth correctas</td><td>❓</td><td>Verificar Client ID y Secret</td></tr>";
echo "<tr><td>🌐 URIs de redirección exactas</td><td>❓</td><td>Debe coincidir exactamente</td></tr>";
echo "<tr><td>⏱️ Esperar 5-10 minutos</td><td>❓</td><td>Google tarda en propagar cambios</td></tr>";
echo "</table>";
echo "</div>";

// Enlaces útiles
echo "<h2>🔗 Enlaces Útiles</h2>";
echo "<ul>";
echo "<li><a href='https://console.cloud.google.com/apis/credentials/consent' target='_blank'>📋 Configurar Pantalla de Consentimiento</a></li>";
echo "<li><a href='https://console.cloud.google.com/apis/credentials' target='_blank'>🔑 Gestionar Credenciales OAuth</a></li>";
echo "<li><a href='https://console.cloud.google.com/apis/library/calendar-json.googleapis.com' target='_blank'>📅 Google Calendar API</a></li>";
echo "<li><a href='https://developers.google.com/calendar/api/quickstart/php' target='_blank'>📚 Documentación Oficial</a></li>";
echo "<li><a href='google_auth.php'>🏠 Volver a Google Auth</a></li>";
echo "</ul>";

echo "<hr>";
echo "<div style='background:#dc3545; color:white; padding:15px; border-radius:10px; margin:20px 0;'>";
echo "<h3>⚠️ IMPORTANTE</h3>";
echo "<p>El problema más común del error 403 es <strong>NO PUBLICAR LA APLICACIÓN</strong> en la pantalla de consentimiento OAuth.</p>";
echo "<p>Asegúrate de hacer clic en <strong>\"PUBLICAR APLICACIÓN\"</strong> después de configurar todo.</p>";
echo "</div>";

echo "<p><em>🗑️ Elimina este archivo (fix_oauth.php) después de resolver el problema.</em></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fix OAuth 403</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        h1, h2 { color: #121A28; }
        h2 { color: #F09146; margin-top: 30px; }
        h3 { color: #121A28; margin-top: 20px; }
        table { 
            background: white; 
            border-radius: 5px;
            margin: 10px 0;
            width: 100%;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 14px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        a { 
            color: #A23004; 
            text-decoration: none;
            font-weight: bold;
        }
        a:hover { 
            text-decoration: underline; 
        }
        ul, ol {
            margin: 10px 0;
            padding-left: 30px;
        }
        li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
</body>
</html>