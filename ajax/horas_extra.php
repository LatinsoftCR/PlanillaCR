<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX DE HORAS EXTRA (AJAX/HORAS_EXTRA.PHP)
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
    // 1. LISTAR REPORTES DE HORAS EXTRA
    // ==========================================================================
    case 'list_overtime':
        try {
            $sql = "SELECT he.CodHoraExtra, he.CodEmpleado, he.Fecha, he.CantidadHoras, he.FactorMultiplicador, 
                           he.MontoCalculado, he.Observacion, he.Estado,
                           e.Nombre + ' ' + e.Apellido1 AS Colaborador,
                           h.Descripcion AS Horario,
                           e.TipoSalario, e.SalarioBase
                    FROM HoraExtra he
                    INNER JOIN Empleado e ON he.CodEmpleado = e.CodEmpleado
                    LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                    ORDER BY he.Fecha DESC, he.CodHoraExtra DESC";
            
            $stmt = $pdo->query($sql);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $records
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error de base de datos al listar horas extra: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 2. OBTENER UN REGISTRO ESPECÍFICO POR ID
    // ==========================================================================
    case 'get_overtime':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de reporte inválido."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM HoraExtra WHERE CodHoraExtra = :id");
            $stmt->execute(['id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                // Quitar nulos para que no den fallos en HTML inputs
                foreach ($record as $key => $val) {
                    if (is_null($val)) {
                        $record[$key] = '';
                    }
                }
                echo json_encode(["success" => true, "data" => $record]);
            } else {
                echo json_encode(["success" => false, "message" => "No se encontró el registro de horas extra."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al recuperar registro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 3. GUARDAR / EDITAR REPORTE DE HORA EXTRA
    // ==========================================================================
    case 'save_overtime':
        $cod_hora_extra = isset($_POST['cod_hora_extra']) ? intval($_POST['cod_hora_extra']) : 0;
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $cod_empleado = isset($_POST['cod_empleado']) ? intval($_POST['cod_empleado']) : 0;
        $fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
        $cantidad_horas = isset($_POST['cantidad_horas']) ? floatval($_POST['cantidad_horas']) : 0.00;
        $factor = isset($_POST['factor_multiplicador']) ? floatval($_POST['factor_multiplicador']) : 1.50;
        $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'PENDIENTE';

        // Validaciones básicas
        if ($cod_empleado <= 0 || empty($fecha) || $cantidad_horas <= 0 || $factor <= 0) {
            echo json_encode(["success" => false, "message" => "Por favor, complete todos los campos requeridos con valores válidos."]);
            exit;
        }

        if ($cod_empresa <= 0) {
            try {
                $stmtEmp = $pdo->prepare("SELECT CodEmpresa FROM Empleado WHERE CodEmpleado = :cod");
                $stmtEmp->execute(['cod' => $cod_empleado]);
                $cod_empresa = $stmtEmp->fetchColumn();
            } catch (Exception $e) {
                // Ignorar
            }
            if (!$cod_empresa) {
                $cod_empresa = 1; // Empresa default
            }
        }

        try {
            if ($cod_hora_extra === 0) {
                // INSERTAR
                $sql = "EXEC sp_HoraExtraInsertar 
                            @CodEmpresa = :cod_empresa,
                            @CodEmpleado = :cod_empleado,
                            @Fecha = :fecha,
                            @CantidadHoras = :horas,
                            @FactorMultiplicador = :factor,
                            @Observacion = :obs,
                            @UsuarioAccion = :usuario";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'cod_empleado' => $cod_empleado,
                    'fecha' => $fecha,
                    'horas' => $cantidad_horas,
                    'factor' => $factor,
                    'obs' => $observacion,
                    'usuario' => $usuarioAccion
                ]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    echo json_encode(["success" => true, "message" => $res['Message'], "id" => $res['CodHoraExtra']]);
                } else {
                    echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "Error al guardar el registro."]);
                }
            } else {
                // ACTUALIZAR
                $sql = "EXEC sp_HoraExtraActualizar 
                            @CodHoraExtra = :id,
                            @Fecha = :fecha,
                            @CantidadHoras = :horas,
                            @FactorMultiplicador = :factor,
                            @Observacion = :obs,
                            @Estado = :estado,
                            @UsuarioAccion = :usuario";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id' => $cod_hora_extra,
                    'fecha' => $fecha,
                    'horas' => $cantidad_horas,
                    'factor' => $factor,
                    'obs' => $observacion,
                    'estado' => $estado,
                    'usuario' => $usuarioAccion
                ]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    echo json_encode(["success" => true, "message" => $res['Message'], "id" => $res['CodHoraExtra']]);
                } else {
                    echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "Error al actualizar el registro."]);
                }
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos en SQL Server: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 4. ANULAR / ELIMINAR REGISTRO DE HORA EXTRA
    // ==========================================================================
    case 'delete_overtime':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de registro no válido."]);
            exit;
        }

        try {
            $sql = "EXEC sp_HoraExtraEliminar @CodHoraExtra = :id, @UsuarioAccion = :usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'usuario' => $usuarioAccion
            ]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                echo json_encode(["success" => true, "message" => $res['Message']]);
            } else {
                echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo anular la hora extra."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos al anular registro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5. CÁLCULO DE MONTO ESTIMADO EN VIVO (PREVIEW)
    // ==========================================================================
    case 'calculate_preview':
        $cod_empleado = isset($_GET['cod_empleado']) ? intval($_GET['cod_empleado']) : 0;
        $horas = isset($_GET['horas']) ? floatval($_GET['horas']) : 0.00;
        $factor = isset($_GET['factor']) ? floatval($_GET['factor']) : 1.50;

        if ($cod_empleado <= 0 || $horas <= 0) {
            echo json_encode(["success" => false, "monto" => 0.00, "salario_hora" => 0.00]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT dbo.fn_ObtenerSalarioHora(:cod) AS SalarioHora");
            $stmt->execute(['cod' => $cod_empleado]);
            $salario_hora = floatval($stmt->fetchColumn());

            $monto_estimado = round($horas * $factor * $salario_hora, 2);

            echo json_encode([
                "success" => true,
                "salario_hora" => $salario_hora,
                "monto" => $monto_estimado
            ]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al precalcular: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 6. VALIDAR CÉDULAS DE COLABORADORES (INDIVIDUAL O MASIVO)
    // ==========================================================================
    case 'validate_cedulas':
        $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
        $cedulas_raw = isset($_POST['cedulas']) ? $_POST['cedulas'] : [];
        
        $list = [];
        if (!empty($cedula)) {
            $list[] = $cedula;
        }
        if (is_array($cedulas_raw)) {
            foreach ($cedulas_raw as $c) {
                $c = trim($c);
                if (!empty($c)) {
                    $list[] = $c;
                }
            }
        } elseif (!empty($cedulas_raw)) {
            // Si es un JSON string
            $decoded = json_decode($cedulas_raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $c) {
                    $c = trim($c);
                    if (!empty($c)) {
                        $list[] = $c;
                    }
                }
            }
        }
        
        if (empty($list)) {
            echo json_encode(["success" => true, "data" => []]);
            exit;
        }
        
        try {
            // Eliminar duplicados
            $list = array_values(array_unique($list));
            
            // Generar placeholders para la consulta IN
            $placeholders = [];
            $params = [];
            foreach ($list as $index => $c) {
                $placeholder = ":cedula_" . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $c;
            }
            
            $sql = "SELECT CodEmpleado, Identificacion, CodEmpresa,
                           Nombre + ' ' + Apellido1 AS Colaborador,
                           dbo.fn_ObtenerSalarioHora(CodEmpleado) AS SalarioHora
                    FROM Empleado
                    WHERE Identificacion IN (" . implode(", ", $placeholders) . ") 
                      AND Estado = 'ACTIVO'";
                      
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Retornar indexado por Cédula para fácil consumo en JS
            $mapped = [];
            foreach ($employees as $emp) {
                $mapped[$emp['Identificacion']] = [
                    "CodEmpleado" => intval($emp['CodEmpleado']),
                    "CodEmpresa" => intval($emp['CodEmpresa']),
                    "Colaborador" => $emp['Colaborador'],
                    "SalarioHora" => floatval($emp['SalarioHora'])
                ];
            }
            
            echo json_encode(["success" => true, "data" => $mapped]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al validar cédulas: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 7. MIGRACIÓN / INSERCIÓN TRANSACCIONAL MASIVA DE HORAS EXTRA (EXCEL)
    // ==========================================================================
    case 'save_batch_overtime':
        $batch_raw = isset($_POST['batch_data']) ? $_POST['batch_data'] : '';
        if (empty($batch_raw)) {
            echo json_encode(["success" => false, "message" => "No se recibieron datos para importar."]);
            exit;
        }
        
        $batch_data = json_decode($batch_raw, true);
        if (!is_array($batch_data) || empty($batch_data)) {
            echo json_encode(["success" => false, "message" => "El formato de los datos del lote es inválido o está vacío."]);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            $sqlInsert = "EXEC sp_HoraExtraInsertar 
                            @CodEmpresa = :cod_empresa,
                            @CodEmpleado = :cod_empleado,
                            @Fecha = :fecha,
                            @CantidadHoras = :horas,
                            @FactorMultiplicador = :factor,
                            @Observacion = :obs,
                            @UsuarioAccion = :usuario";
                            
            $stmtInsert = $pdo->prepare($sqlInsert);
            $successCount = 0;
            
            foreach ($batch_data as $row) {
                $cod_empleado = isset($row['cod_empleado']) ? intval($row['cod_empleado']) : 0;
                $cod_empresa = isset($row['cod_empresa']) ? intval($row['cod_empresa']) : 0;
                $fecha = isset($row['fecha']) ? trim($row['fecha']) : '';
                $cantidad_horas = isset($row['cantidad_horas']) ? floatval($row['cantidad_horas']) : 0.00;
                $factor = isset($row['factor_multiplicador']) ? floatval($row['factor_multiplicador']) : 1.50;
                $observacion = isset($row['observacion']) ? trim($row['observacion']) : '';
                
                // Si falta fecha, poner hoy
                if (empty($fecha)) {
                    $fecha = date('Y-m-d');
                }
                
                if ($cod_empleado <= 0 || $cantidad_horas <= 0 || $factor <= 0) {
                    throw new Exception("Fila con parámetros inválidos: Colaborador ID $cod_empleado, Horas $cantidad_horas, Factor $factor.");
                }
                
                // Buscar empresa si no viene dada
                if ($cod_empresa <= 0) {
                    $stmtEmp = $pdo->prepare("SELECT CodEmpresa FROM Empleado WHERE CodEmpleado = :cod");
                    $stmtEmp->execute(['cod' => $cod_empleado]);
                    $cod_empresa = intval($stmtEmp->fetchColumn());
                    if (!$cod_empresa) {
                        $cod_empresa = 1;
                    }
                }
                
                $stmtInsert->execute([
                    'cod_empresa' => $cod_empresa,
                    'cod_empleado' => $cod_empleado,
                    'fecha' => $fecha,
                    'horas' => $cantidad_horas,
                    'factor' => $factor,
                    'obs' => $observacion,
                    'usuario' => $usuarioAccion
                ]);
                
                $res = $stmtInsert->fetch(PDO::FETCH_ASSOC);
                // Vaciar el buffer del resultset de SQL Server
                $stmtInsert->closeCursor();
                
                if (!$res || !isset($res['Success']) || intval($res['Success']) !== 1) {
                    $errMsg = (isset($res['Message'])) ? $res['Message'] : "Fallo en la ejecución del Stored Procedure sp_HoraExtraInsertar.";
                    throw new Exception($errMsg);
                }
                
                $successCount++;
            }
            
            $pdo->commit();
            echo json_encode([
                "success" => true,
                "message" => "¡Carga masiva completada con éxito! Se procesaron $successCount reportes de horas extra.",
                "count" => $successCount
            ]);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode([
                "success" => false,
                "message" => "Error al procesar la importación por lote: " . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Acción de horas extra no soportada por el controlador."]);
        break;
}
