<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * DESTRUCCIÓN SEGURA DE SESIÓN DE USUARIO
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registrar auditoría de salida en la Bitácora (Regla de Auditoría #9)
if (isset($_SESSION['username'])) {
    require_once 'config/conexion.php';
    try {
        $log_stmt = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (:usuario, 'CERRAR SESIÓN', :detalle)");
        $log_stmt->execute([
            'usuario' => $_SESSION['username'],
            'detalle' => "El usuario cerró sesión voluntariamente de forma segura desde el panel principal."
        ]);
    } catch (PDOException $e) {
        // Se omiten fallos de bitácora para garantizar la salida del usuario
    }
}

// Limpiar todas las variables de sesión en memoria
$_SESSION = array();

// Destruir la cookie de sesión de PHP en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión físicamente en el servidor
session_destroy();

// Redirección inmediata al login
header("Location: login.php");
exit;
