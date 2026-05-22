<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX DE RECURSOS HUMANOS (AJAX/RECURSOS_HUMANOS.PHP)
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar seguridad de sesión activa
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso denegado: Sesión no activa."]);
    exit;
}

require_once '../config/conexion.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');
$usuarioAccion = $_SESSION['username'];

switch ($action) {

    // ==========================================================================
    // 1. ACCIÓN: LISTAR EMPLEADOS (VW_EMPLEADOCOMPLETO)
    // ==========================================================================
    case 'list_employees':
        try {
            // Consultamos la vista precompilada
            $stmt = $pdo->query("SELECT * FROM vw_EmpleadoCompleto ORDER BY CodEmpleado DESC");
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $employees
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error de base de datos al listar empleados: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 2. ACCIÓN: OBTENER CATÁLOGOS BASE PARA FORMULARIOS
    // ==========================================================================
    case 'get_catalogs':
        try {
            // Obtenemos los listados activos ordenados
            $empresas = $pdo->query("SELECT CodEmpresa, Nombre FROM Empresa WHERE Estado = 'ACTIVO' ORDER BY Nombre")->fetchAll(PDO::FETCH_ASSOC);
            $sucursales = $pdo->query("SELECT CodSucursal, CodEmpresa, Nombre FROM Sucursal WHERE Estado = 'ACTIVO' ORDER BY Nombre")->fetchAll(PDO::FETCH_ASSOC);
            $departamentos = $pdo->query("SELECT CodDepartamento, CodEmpresa, Descripcion FROM Departamento WHERE Estado = 'ACTIVO' ORDER BY Descripcion")->fetchAll(PDO::FETCH_ASSOC);
            $puestos = $pdo->query("SELECT CodPuesto, CodEmpresa, Descripcion FROM Puesto WHERE Estado = 'ACTIVO' ORDER BY Descripcion")->fetchAll(PDO::FETCH_ASSOC);
            $horarios = $pdo->query("SELECT CodHorario, CodEmpresa, Descripcion, CAST(HoraEntrada AS VARCHAR(5)) AS HoraEntrada, CAST(HoraSalida AS VARCHAR(5)) AS HoraSalida FROM Horario WHERE Estado = 'ACTIVO' ORDER BY Descripcion")->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "empresas" => $empresas,
                "sucursales" => $sucursales,
                "departamentos" => $departamentos,
                "puestos" => $puestos,
                "horarios" => $horarios
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error de base de datos al cargar catálogos organizacionales: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 3. ACCIÓN: OBTENER EMPLEADO ESPECÍFICO POR ID
    // ==========================================================================
    case 'get_employee':
        $cod_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($cod_empleado <= 0) {
            echo json_encode(["success" => false, "message" => "El código de empleado no es válido."]);
            exit;
        }

        try {
            $sql = "SELECT e.*, 
                           comp.Nombre AS EmpresaNombre, 
                           comp.NombreComercial AS EmpresaNombreComercial, 
                           comp.CedulaJuridica AS EmpresaCedulaJuridica, 
                           comp.Direccion AS EmpresaDireccion, 
                           comp.Telefono AS EmpresaTelefono,
                           comp.Correo AS EmpresaCorreo,
                           comp.Logo AS EmpresaLogo,
                           p.Descripcion AS Puesto,
                           d.Descripcion AS Departamento,
                           s.Nombre AS Sucursal,
                           h.Descripcion AS Horario
                    FROM Empleado e
                    LEFT JOIN Empresa comp ON e.CodEmpresa = comp.CodEmpresa
                    LEFT JOIN Puesto p ON e.CodPuesto = p.CodPuesto
                    LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
                    LEFT JOIN Sucursal s ON e.CodSucursal = s.CodSucursal
                    LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                    WHERE e.CodEmpleado = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $cod_empleado]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($employee) {
                // Check if employee has active garnishments/embargos or child support (pensiones alimenticias)
                $sqlEmbargo = "SELECT COUNT(*) 
                               FROM DeduccionEmpleado de
                               INNER JOIN RubroPlanilla r ON de.CodRubro = r.CodRubro
                               WHERE de.CodEmpleado = :id 
                                 AND de.Estado = 'ACTIVO'
                                 AND (de.Descripcion LIKE '%embargo%' 
                                      OR de.Descripcion LIKE '%pension%' 
                                      OR de.Descripcion LIKE '%pensión%' 
                                      OR de.Descripcion LIKE '%alimenticia%'
                                      OR r.Descripcion LIKE '%embargo%'
                                      OR r.Descripcion LIKE '%pension%'
                                      OR r.Descripcion LIKE '%pensión%'
                                      OR r.Descripcion LIKE '%alimenticia%')";
                $stmtEmbargo = $pdo->prepare($sqlEmbargo);
                $stmtEmbargo->execute(['id' => $cod_empleado]);
                $hasEmbargo = intval($stmtEmbargo->fetchColumn()) > 0;
                $employee['TieneEmbargos'] = $hasEmbargo ? 'posee' : 'libre';

                // Quitar posibles nulos para evitar problemas en inputs HTML
                foreach ($employee as $key => $val) {
                    if (is_null($val)) {
                        $employee[$key] = '';
                    }
                }
                
                echo json_encode([
                    "success" => true,
                    "data" => $employee
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "No se encontró ningún empleado registrado con el código " . $cod_empleado . "."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error de base de datos al recuperar empleado: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 4. ACCIÓN: GUARDAR / EDITAR EMPLEADO (STORED PROCEDURES)
    // ==========================================================================
    case 'save_employee':
        // Recuperar y limpiar parámetros
        $cod_empleado = isset($_POST['cod_empleado']) ? intval($_POST['cod_empleado']) : 0;
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $cod_sucursal = isset($_POST['cod_sucursal']) ? intval($_POST['cod_sucursal']) : 0;
        $cod_departamento = isset($_POST['cod_departamento']) ? intval($_POST['cod_departamento']) : 0;
        $cod_puesto = isset($_POST['cod_puesto']) ? intval($_POST['cod_puesto']) : 0;
        $cod_horario = isset($_POST['cod_horario']) ? intval($_POST['cod_horario']) : 0;
        
        $identificacion = isset($_POST['identificacion']) ? trim($_POST['identificacion']) : '';
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $apellido1 = isset($_POST['apellido1']) ? trim($_POST['apellido1']) : '';
        $apellido2 = isset($_POST['apellido2']) ? trim($_POST['apellido2']) : NULL; // Opcional
        
        $fecha_nacimiento = isset($_POST['fecha_nacimiento']) ? trim($_POST['fecha_nacimiento']) : '';
        $fecha_ingreso = isset($_POST['fecha_ingreso']) ? trim($_POST['fecha_ingreso']) : '';
        
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : NULL; // Opcional
        $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
        $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : NULL; // Opcional
        
        $tipo_salario = isset($_POST['tipo_salario']) ? trim($_POST['tipo_salario']) : '';
        $salario_base = isset($_POST['salario_base']) ? floatval($_POST['salario_base']) : 0.00;
        
        $banco = isset($_POST['banco']) ? trim($_POST['banco']) : NULL; // Opcional
        $cuenta_iban = isset($_POST['cuenta_iban']) ? trim($_POST['cuenta_iban']) : NULL; // Opcional
        $cuenta_contable = isset($_POST['cuenta_contable']) && !empty($_POST['cuenta_contable']) ? trim($_POST['cuenta_contable']) : NULL; // Opcional - Contable
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO'; // Solo para actualizaciones

        // Validaciones del servidor
        if ($cod_empresa <= 0 || $cod_sucursal <= 0 || $cod_departamento <= 0 || $cod_puesto <= 0 || $cod_horario <= 0) {
            echo json_encode(["success" => false, "message" => "Todos los catálogos organizacionales (empresa, sucursal, departamento, puesto, horario) son requeridos."]);
            exit;
        }
        if (empty($identificacion) || empty($nombre) || empty($apellido1) || empty($fecha_nacimiento) || empty($fecha_ingreso) || empty($correo) || empty($tipo_salario) || $salario_base <= 0) {
            echo json_encode(["success" => false, "message" => "Por favor, complete todos los campos marcados como obligatorios y asigne un salario base válido."]);
            exit;
        }

        try {
            if ($cod_empleado === 0) {
                // ==========================================
                // LLAMADO A: SP_EMPLEADOINSERTAR
                // ==========================================
                $sql = "EXEC sp_EmpleadoInsertar 
                            @CodEmpresa = :cod_empresa,
                            @CodSucursal = :cod_sucursal,
                            @CodDepartamento = :cod_departamento,
                            @CodPuesto = :cod_puesto,
                            @CodHorario = :cod_horario,
                            @Identificacion = :identificacion,
                            @Nombre = :nombre,
                            @Apellido1 = :apellido1,
                            @Apellido2 = :apellido2,
                            @FechaNacimiento = :fecha_nacimiento,
                            @FechaIngreso = :fecha_ingreso,
                            @Telefono = :telefono,
                            @Correo = :correo,
                            @Direccion = :direccion,
                            @TipoSalario = :tipo_salario,
                            @SalarioBase = :salario_base,
                            @Banco = :banco,
                            @CuentaIBAN = :cuenta_iban,
                            @CuentaContable = :cuenta_contable,
                            @UsuarioAccion = :usuario_accion";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'cod_sucursal' => $cod_sucursal,
                    'cod_departamento' => $cod_departamento,
                    'cod_puesto' => $cod_puesto,
                    'cod_horario' => $cod_horario,
                    'identificacion' => $identificacion,
                    'nombre' => $nombre,
                    'apellido1' => $apellido1,
                    'apellido2' => $apellido2,
                    'fecha_nacimiento' => $fecha_nacimiento,
                    'fecha_ingreso' => $fecha_ingreso,
                    'telefono' => $telefono,
                    'correo' => $correo,
                    'direccion' => $direccion,
                    'tipo_salario' => $tipo_salario,
                    'salario_base' => $salario_base,
                    'banco' => $banco,
                    'cuenta_iban' => $cuenta_iban,
                    'cuenta_contable' => $cuenta_contable,
                    'usuario_accion' => $usuarioAccion
                ]);
                
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    echo json_encode(["success" => true, "message" => $res['Message'], "id" => $res['CodEmpleado']]);
                } else {
                    echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo insertar el empleado en la base de datos."]);
                }
            } else {
                // ==========================================
                // LLAMADO A: SP_EMPLEADOACTUALIZAR
                // ==========================================
                $sql = "EXEC sp_EmpleadoActualizar 
                            @CodEmpleado = :cod_empleado,
                            @CodEmpresa = :cod_empresa,
                            @CodSucursal = :cod_sucursal,
                            @CodDepartamento = :cod_departamento,
                            @CodPuesto = :cod_puesto,
                            @CodHorario = :cod_horario,
                            @Identificacion = :identificacion,
                            @Nombre = :nombre,
                            @Apellido1 = :apellido1,
                            @Apellido2 = :apellido2,
                            @FechaNacimiento = :fecha_nacimiento,
                            @FechaIngreso = :fecha_ingreso,
                            @Telefono = :telefono,
                            @Correo = :correo,
                            @Direccion = :direccion,
                            @TipoSalario = :tipo_salario,
                            @SalarioBase = :salario_base,
                            @Banco = :banco,
                            @CuentaIBAN = :cuenta_iban,
                            @CuentaContable = :cuenta_contable,
                            @Estado = :estado,
                            @UsuarioAccion = :usuario_accion";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'cod_empleado' => $cod_empleado,
                    'cod_empresa' => $cod_empresa,
                    'cod_sucursal' => $cod_sucursal,
                    'cod_departamento' => $cod_departamento,
                    'cod_puesto' => $cod_puesto,
                    'cod_horario' => $cod_horario,
                    'identificacion' => $identificacion,
                    'nombre' => $nombre,
                    'apellido1' => $apellido1,
                    'apellido2' => $apellido2,
                    'fecha_nacimiento' => $fecha_nacimiento,
                    'fecha_ingreso' => $fecha_ingreso,
                    'telefono' => $telefono,
                    'correo' => $correo,
                    'direccion' => $direccion,
                    'tipo_salario' => $tipo_salario,
                    'salario_base' => $salario_base,
                    'banco' => $banco,
                    'cuenta_iban' => $cuenta_iban,
                    'cuenta_contable' => $cuenta_contable,
                    'estado' => $estado,
                    'usuario_accion' => $usuarioAccion
                ]);
                
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    echo json_encode(["success" => true, "message" => $res['Message'], "id" => $res['CodEmpleado']]);
                } else {
                    echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo actualizar el empleado."]);
                }
            }
        } catch (PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "Fallo de infraestructura en SQL Server: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 5. ACCIÓN: DAR DE BAJA / INACTIVAR EMPLEADO (SP_EMPLEADOINACTIVAR)
    // ==========================================================================
    case 'delete_employee':
        $cod_empleado = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $fecha_salida = isset($_POST['fecha_salida']) && !empty($_POST['fecha_salida']) ? trim($_POST['fecha_salida']) : date('Y-m-d');
        
        if ($cod_empleado <= 0) {
            echo json_encode(["success" => false, "message" => "El código de empleado a dar de baja no es válido."]);
            exit;
        }

        try {
            $sql = "EXEC sp_EmpleadoInactivar 
                        @CodEmpleado = :cod_empleado,
                        @FechaSalida = :fecha_salida,
                        @UsuarioAccion = :usuario_accion";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'cod_empleado' => $cod_empleado,
                'fecha_salida' => $fecha_salida,
                'usuario_accion' => $usuarioAccion
            ]);
            
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                echo json_encode(["success" => true, "message" => $res['Message']]);
            } else {
                echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo dar de baja al empleado."]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "Fallo en base de datos al dar de baja lógica: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // ACCIÓN NO ENCONTRADA
    // ==========================================================================
    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Acción de Recursos Humanos no soportada por el controlador."]);
        break;
}
