<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONEXIÓN A BASE DE DATOS SQL SERVER VÍA PDO
 */

// Parámetros de conexión configurables (permiten leer variables de entorno en producción/Docker)
$db_host = getenv('DB_HOST') ?: "172.25.2.3,1443";
$db_name = getenv('DB_NAME') ?: "PlanillaCR";
$db_user = getenv('DB_USER') !== false ? getenv('DB_USER') : "sa";
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "Latin123";

try {
    // Configuración del DSN de conexión
    $dsn = "sqlsrv:Server={$db_host};Database={$db_name};ConnectionPooling=0;Encrypt=false;TrustServerCertificate=true";

    // Opciones base de PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    // Forzar la codificación UTF-8 en el driver SQLSRV para evitar problemas de tildes y eñes
    if (class_exists('PDO') && defined('PDO::SQLSRV_ATTR_ENCODING')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    } else {
        // Fallback usando los valores enteros del driver SQLSRV (1002 = PDO::SQLSRV_ATTR_ENCODING, 3 = PDO::SQLSRV_ENCODING_UTF8)
        $options[1002] = 3;
    }

    if (empty($db_user)) {
        // Autenticación integrada de Windows
        $pdo = new PDO($dsn, null, null, $options);
    } else {
        // Autenticación de SQL Server
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    }

} catch (PDOException $e) {
    // Si la solicitud es vía AJAX, devolvemos un JSON limpio, de lo contrario un mensaje HTML elegante
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "success" => false,
            "message" => "Error de infraestructura: No se pudo establecer la conexión con el servidor SQL Server local. Detalle: " . $e->getMessage()
        ]);
    } else {
        echo "<div style='padding: 20px; background-color: #1a1a1e; color: #ff5555; font-family: sans-serif; border-radius: 8px; border: 1px solid #3a3a42; max-width: 600px; margin: 50px auto;'>";
        echo "<h3 style='margin-top:0;'>⚠️ Error de Conexión de Infraestructura</h3>";
        echo "<p>No se pudo conectar a la base de datos en la instancia <strong>{$db_host}</strong>.</p>";
        echo "<p><strong>Detalle Técnico:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='font-size: 13px; color: #888;'>Por favor verifique que la instancia local de SQL Server Express esté iniciada y configurada para aceptar conexiones TCP/IP.</p>";
        echo "</div>";
    }
    exit;
}
