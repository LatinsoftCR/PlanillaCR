<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * SCRIPT PARA VERIFICAR Y RESTABLECER EL USUARIO ADMINISTRADOR 'admin'
 */
header('Content-Type: text/plain; charset=utf-8');

// Cargar la conexión existente
require_once __DIR__ . '/../config/conexion.php';

echo "VERIFICANDO CONFIGURACIÓN DE ACCESO DEL USUARIO ADMINISTRADOR...\n";
echo "===============================================================\n\n";

try {
    // 1. Verificar si existe la tabla Usuario
    echo "1. Comprobando la tabla 'Usuario'...\n";
    $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'Usuario'");
    $table = $stmt->fetch();
    if (!$table) {
        echo "❌ La tabla 'Usuario' no existe en la base de datos. Asegúrese de que la base de datos está inicializada.\n";
        exit;
    }
    echo "   -> Tabla 'Usuario' encontrada.\n";

    // 2. Verificar si existe el Stored Procedure sp_UsuarioLogin
    echo "\n2. Comprobando el Stored Procedure 'sp_UsuarioLogin'...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM sys.objects WHERE object_id = OBJECT_ID('sp_UsuarioLogin') AND type = 'P'");
    $stmt->execute();
    $sp = $stmt->fetch();
    if ($sp['total'] == 0) {
        echo "⚠️ El Stored Procedure 'sp_UsuarioLogin' no existe. Vamos a crearlo para asegurar el correcto funcionamiento del login.\n";
        
        // Crear sp_UsuarioLogin
        $pdo->exec("
            CREATE PROCEDURE sp_UsuarioLogin
            (
                @Username VARCHAR(100)
            )
            AS
            BEGIN
                SET NOCOUNT ON;
                SELECT CodUsuario, CodEmpleado, Username, PasswordHash, Correo, Rol, Estado, NombreCompleto = Username -- Fallback si no hay empleado
                FROM Usuario
                WHERE (Username = @Username OR Correo = @Username) AND Estado = 'ACTIVO';
            END
        ");
        echo "   -> Stored Procedure 'sp_UsuarioLogin' creado exitosamente.\n";
    } else {
        echo "   -> Stored Procedure 'sp_UsuarioLogin' ya existe.\n";
    }

    // 3. Verificar si el usuario 'admin' existe
    echo "\n3. Buscando usuario 'admin'...\n";
    $stmt = $pdo->prepare("SELECT * FROM Usuario WHERE Username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();

    $newPassword = 'Admin123*';
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    if ($user) {
        echo "   -> El usuario 'admin' ya existe en la base de datos.\n";
        echo "   -> Su correo es: " . $user['Correo'] . "\n";
        echo "   -> Su rol es: " . $user['Rol'] . "\n";
        echo "   -> Su estado es: " . $user['Estado'] . "\n";
        
        // Verificar si la clave actual coincide con Admin123*
        if (password_verify($newPassword, $user['PasswordHash'])) {
            echo "   -> ¡La contraseña del usuario 'admin' ya está correctamente configurada como 'Admin123*'!\n";
        } else {
            echo "   -> La contraseña actual NO coincide con 'Admin123*'. Actualizando contraseña...\n";
            $update = $pdo->prepare("UPDATE Usuario SET PasswordHash = :hash, Estado = 'ACTIVO' WHERE Username = 'admin'");
            $update->execute(['hash' => $passwordHash]);
            echo "   -> ¡Contraseña del usuario 'admin' restablecida a 'Admin123*' exitosamente!\n";
        }
    } else {
        echo "   -> El usuario 'admin' no existe. Procediendo a crearlo...\n";
        
        // Buscar si existe un empleado al cual ligar el usuario, si no, crear uno o dejar en NULL
        $stmtEmpleado = $pdo->query("SELECT TOP 1 CodEmpleado FROM Empleado");
        $empleado = $stmtEmpleado->fetch();
        $codEmpleado = $empleado ? $empleado['CodEmpleado'] : null;
        
        if (!$codEmpleado) {
            echo "   -> No se encontró ningún empleado en la base de datos. Creando empleado por defecto...\n";
            
            // Buscar empresa
            $stmtEmpresa = $pdo->query("SELECT TOP 1 CodEmpresa FROM Empresa");
            $empresa = $stmtEmpresa->fetch();
            $codEmpresa = $empresa ? $empresa['CodEmpresa'] : 1;
            
            // Crear empresa de prueba si no hay
            if (!$empresa) {
                $pdo->exec("INSERT INTO Empresa (Nombre, CedulaJuridica, Moneda, Estado) VALUES ('Empresa Demo', '3-101-000000', 'CRC', 'ACTIVO')");
                $codEmpresa = $pdo->lastInsertId();
            }
            
            // Buscar sucursal, depto, puesto, horario
            $pdo->exec("IF NOT EXISTS (SELECT * FROM Sucursal) INSERT INTO Sucursal (CodEmpresa, Nombre, Estado) VALUES ($codEmpresa, 'Central', 'ACTIVO')");
            $pdo->exec("IF NOT EXISTS (SELECT * FROM Departamento) INSERT INTO Departamento (CodEmpresa, Descripcion, Estado) VALUES ($codEmpresa, 'Sistemas', 'ACTIVO')");
            $pdo->exec("IF NOT EXISTS (SELECT * FROM Puesto) INSERT INTO Puesto (CodEmpresa, Descripcion, Estado) VALUES ($codEmpresa, 'Admin', 'ACTIVO')");
            $pdo->exec("IF NOT EXISTS (SELECT * FROM Horario) INSERT INTO Horario (CodEmpresa, Descripcion, HoraEntrada, HoraSalida, HorasDia, Estado) VALUES ($codEmpresa, 'Normal', '08:00', '17:00', 8, 'ACTIVO')");
            
            $sucursal = $pdo->query("SELECT TOP 1 CodSucursal FROM Sucursal")->fetch()['CodSucursal'];
            $depto = $pdo->query("SELECT TOP 1 CodDepartamento FROM Departamento")->fetch()['CodDepartamento'];
            $puesto = $pdo->query("SELECT TOP 1 CodPuesto FROM Puesto")->fetch()['CodPuesto'];
            $horario = $pdo->query("SELECT TOP 1 CodHorario FROM Horario")->fetch()['CodHorario'];
            
            $insertEmp = $pdo->prepare("
                INSERT INTO Empleado (
                    CodEmpresa, CodSucursal, CodDepartamento, CodPuesto, CodHorario, 
                    Identificacion, Nombre, Apellido1, Apellido2, FechaNacimiento, 
                    FechaIngreso, Correo, TipoSalario, SalarioBase, Estado
                ) VALUES (
                    :empresa, :sucursal, :depto, :puesto, :horario, 
                    '1-1111-1111', 'Administrador', 'Sistema', 'Principal', '1990-01-01', 
                    '2026-01-01', 'admin@planillacr.com', 'MENSUAL', 1000000.00, 'ACTIVO'
                )
            ");
            $insertEmp->execute([
                'empresa' => $codEmpresa,
                'sucursal' => $sucursal,
                'depto' => $depto,
                'puesto' => $puesto,
                'horario' => $horario
            ]);
            $codEmpleado = $pdo->lastInsertId();
        }
        
        $insertUser = $pdo->prepare("
            INSERT INTO Usuario (CodEmpleado, Username, PasswordHash, Correo, Rol, Estado, UsuarioCreacion)
            VALUES (:empleado, 'admin', :hash, 'admin@planillacr.com', 'ADMINISTRADOR', 'ACTIVO', 'SISTEMA')
        ");
        $insertUser->execute([
            'empleado' => $codEmpleado,
            'hash' => $passwordHash
        ]);
        echo "   -> ¡Usuario 'admin' creado exitosamente con la contraseña 'Admin123*'!\n";
    }

    echo "\n===============================================================\n";
    echo "¡PROCESO COMPLETADO EXITOSAMENTE!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR AL CONFIGURAR EL USUARIO:\n" . $e->getMessage() . "\n";
}
