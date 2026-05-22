<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX PARA ACCIONES DE SEGURIDAD
 */
header('Content-Type: application/json; charset=utf-8');

// Prevenir acceso directo que no sea petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método de petición no permitido."]);
    exit;
}

require_once '../config/conexion.php';

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if (empty($action)) {
    echo json_encode(["success" => false, "message" => "Acción de seguridad no especificada."]);
    exit;
}

switch ($action) {
    
    // ==========================================================================
    // 1. ACCIÓN: INICIAR SESIÓN (LOGIN)
    // ==========================================================================
    case 'login':
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($username) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Por favor, complete todos los campos requeridos."]);
            exit;
        }

        try {
            // Invocar Stored Procedure para buscar el usuario
            $stmt = $pdo->prepare("EXEC sp_UsuarioLogin @Username = :username");
            $stmt->execute(['username' => $username]);
            $user_row = $stmt->fetch();

            if ($user_row) {
                // Verificar el hash de contraseña almacenado (BCRYPT)
                if (password_verify($password, $user_row['PasswordHash'])) {
                    
                    // Iniciar sesión en PHP de forma segura
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    $_SESSION['user_id'] = $user_row['CodUsuario'];
                    $_SESSION['username'] = $user_row['Username'];
                    $_SESSION['email'] = $user_row['Correo'];
                    $_SESSION['role'] = $user_row['Rol'];
                    $_SESSION['fullname'] = $user_row['NombreCompleto'];

                    // Registrar inicio de sesión en la Bitácora (Regla de Auditoría #9)
                    $log_stmt = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (:usuario, 'INICIAR SESIÓN', :detalle)");
                    $log_stmt->execute([
                        'usuario' => $user_row['Username'],
                        'detalle' => "Inicio de sesión exitoso en el ERP desde el portal de acceso."
                    ]);

                    echo json_encode([
                        "success" => true,
                        "message" => "¡Bienvenido, " . htmlspecialchars($user_row['NombreCompleto']) . "!"
                    ]);
                    
                } else {
                    echo json_encode(["success" => false, "message" => "La contraseña ingresada no es correcta. Vuelva a intentarlo."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "El usuario o correo electrónico ingresado no existe en el sistema."]);
            }
            
        } catch (PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "Error interno de base de datos durante la autenticación: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 2. ACCIÓN: GENERAR TOKEN DE RECUPERACIÓN VIA CORREO
    // ==========================================================================
    case 'recuperar':
        $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';

        if (empty($correo)) {
            echo json_encode(["success" => false, "message" => "El correo electrónico es requerido."]);
            exit;
        }

        try {
            // Generar un token criptográfico seguro de 64 caracteres hex
            $token = bin2hex(random_bytes(32));
            
            // Vigencia del token de 1 hora
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Invocar el Stored Procedure transaccional
            $stmt = $pdo->prepare("EXEC sp_UsuarioGenerarToken @Correo = :correo, @Token = :token, @FechaExpiracion = :expiracion, @UsuarioAccion = 'SISTEMA_RECUPERACION'");
            $stmt->execute([
                'correo' => $correo,
                'token' => $token,
                'expiracion' => $fechaExpiracion
            ]);
            
            // El Stored Procedure nos retorna la información del usuario si el correo existe
            $user_info = $stmt->fetch();

            if ($user_info) {
                // Flujo en servidor de producción: Aquí enviaríamos un correo real usando PHPMailer o mail()
                // Ejemplo de estructura de correo:
                // $enlace = "http://allison-pc/PlanillaCR/restablecer.php?token=" . $token;
                // mail($correo, "Restablecer Contraseña - PlanillaCR", "Hola " . $user_info['NombreEmpleado'] . ", restablezca su clave en: " . $enlace);
                
                // En esta fase de desarrollo y para facilitar las pruebas locales del usuario,
                // generamos la URL simulada y la devolvemos en el JSON para poder acceder directamente
                $demo_link = "restablecer.php?token=" . $token;

                echo json_encode([
                    "success" => true,
                    "message" => "Se ha generado un token seguro para restablecer su contraseña.",
                    "demo_link" => $demo_link
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "No se pudo asociar la solicitud a ningún usuario."]);
            }
            
        } catch (PDOException $e) {
            // Capturar la excepción controlada lanzada por el Stored Procedure (ej: correo no existe)
            // SQL Server lanza errores en base de datos que PDO mapea como PDOException
            $errorMsg = $e->getMessage();
            
            // Filtrar mensajes de error amigables de SQL Server (eliminando prefijos técnicos)
            if (strpos($errorMsg, 'El correo electrónico no se encuentra') !== false) {
                $errorMsg = "El correo electrónico ingresado no se encuentra registrado en el sistema o la cuenta está inactiva.";
            } else {
                $errorMsg = "Error en base de datos: " . $errorMsg;
            }
            
            echo json_encode([
                "success" => false,
                "message" => $errorMsg
            ]);
        }
        break;

    // ==========================================================================
    // 3. ACCIÓN: RESTABLECER CONTRASEÑA CON EL TOKEN
    // ==========================================================================
    case 'restablecer':
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($token) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Los parámetros de token y contraseña son obligatorios."]);
            exit;
        }

        if (strlen($password) < 8) {
            echo json_encode(["success" => false, "message" => "La contraseña debe tener al menos 8 caracteres."]);
            exit;
        }

        try {
            // Cifrar la nueva contraseña con BCRYPT (Cifrado seguro por defecto en PHP)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            // Invocar el Stored Procedure transaccional para restablecer la contraseña
            $stmt = $pdo->prepare("EXEC sp_UsuarioRestablecerClave @Token = :token, @PasswordHash = :passhash, @UsuarioAccion = 'SISTEMA_RESTABLECER'");
            
            $stmt->execute([
                'token' => $token,
                'passhash' => $passwordHash
            ]);

            echo json_encode([
                "success" => true,
                "message" => "Contraseña restablecida correctamente. Ya puede iniciar sesión."
            ]);
            
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            if (strpos($errorMsg, 'El enlace de recuperación no es válido') !== false) {
                $errorMsg = "El enlace de recuperación ya no es válido, ha caducado o ya fue utilizado anteriormente.";
            } else {
                $errorMsg = "Error al actualizar la contraseña: " . $errorMsg;
            }
            
            echo json_encode([
                "success" => false,
                "message" => $errorMsg
            ]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción de seguridad no reconocida."]);
        break;
}
