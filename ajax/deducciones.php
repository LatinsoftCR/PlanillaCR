<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX DE DEDUCCIONES (AJAX/DEDUCCIONES.PHP)
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
    // 1. LISTAR DEDUCCIONES PROGRAMADAS
    // ==========================================================================
    case 'list_deductions':
        try {
            $sql = "SELECT de.CodDeduccionEmpleado, de.CodEmpleado, de.CodRubro, de.Descripcion, 
                           de.TipoDeduccion, de.Monto, de.Porcentaje, de.MontoTotalOriginal, de.SaldoRestante, de.Estado,
                           e.Nombre + ' ' + e.Apellido1 AS Colaborador,
                           r.Codigo AS CodigoRubro, r.Descripcion AS NombreRubro,
                           e.SalarioBase
                    FROM DeduccionEmpleado de
                    INNER JOIN Empleado e ON de.CodEmpleado = e.CodEmpleado
                    INNER JOIN RubroPlanilla r ON de.CodRubro = r.CodRubro
                    ORDER BY de.CodDeduccionEmpleado DESC";
            
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
                "message" => "Error de base de datos al listar deducciones: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 2. OBTENER DEDUCCIÓN ESPECÍFICA POR ID
    // ==========================================================================
    case 'get_deduction':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de deducción inválido."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM DeduccionEmpleado WHERE CodDeduccionEmpleado = :id");
            $stmt->execute(['id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                // Quitar nulos para campos vacíos en HTML
                foreach ($record as $key => $val) {
                    if (is_null($val)) {
                        $record[$key] = '';
                    }
                }
                echo json_encode(["success" => true, "data" => $record]);
            } else {
                echo json_encode(["success" => false, "message" => "Deducción no encontrada."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al recuperar registro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 3. GUARDAR / EDITAR REGLA DE DEDUCCIÓN
    // ==========================================================================
    case 'save_deduction':
        $cod_deduccion_empleado = isset($_POST['cod_deduccion_empleado']) ? intval($_POST['cod_deduccion_empleado']) : 0;
        $cod_empresa = isset($_POST['cod_empresa']) ? intval($_POST['cod_empresa']) : 0;
        $cod_empleado = isset($_POST['cod_empleado']) ? intval($_POST['cod_empleado']) : 0;
        $cod_rubro = isset($_POST['cod_rubro']) ? intval($_POST['cod_rubro']) : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $tipo_deduccion = isset($_POST['tipo_deduccion']) ? trim($_POST['tipo_deduccion']) : 'MONTO';
        $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0.00;
        $porcentaje = isset($_POST['porcentaje']) ? floatval($_POST['porcentaje']) : 0.00;
        $monto_total_original = isset($_POST['monto_total_original']) && !empty($_POST['monto_total_original']) ? floatval($_POST['monto_total_original']) : null;
        $saldo_restante = isset($_POST['saldo_restante']) && $_POST['saldo_restante'] !== '' ? floatval($_POST['saldo_restante']) : $monto_total_original;
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';

        // Validaciones del servidor
        if ($cod_empleado <= 0 || $cod_rubro <= 0 || empty($descripcion) || empty($tipo_deduccion)) {
            echo json_encode(["success" => false, "message" => "Por favor, complete todos los campos obligatorios."]);
            exit;
        }

        if ($tipo_deduccion === 'MONTO' && $monto <= 0) {
            echo json_encode(["success" => false, "message" => "El monto de deducción debe ser mayor a 0."]);
            exit;
        }

        if ($tipo_deduccion === 'PORCENTAJE' && ($porcentaje <= 0 || $porcentaje > 100)) {
            echo json_encode(["success" => false, "message" => "El porcentaje de deducción debe ser entre 0.01% y 100%."]);
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
                $cod_empresa = 1; // Default
            }
        }

        try {
            if ($cod_deduccion_empleado === 0) {
                // INSERTAR
                $sql = "EXEC sp_DeduccionEmpleadoInsertar 
                            @CodEmpresa = :cod_empresa,
                            @CodEmpleado = :cod_empleado,
                            @CodRubro = :cod_rubro,
                            @Descripcion = :descripcion,
                            @TipoDeduccion = :tipo_deduccion,
                            @Monto = :monto,
                            @Porcentaje = :porcentaje,
                            @MontoTotalOriginal = :original,
                            @UsuarioAccion = :usuario";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'cod_empresa' => $cod_empresa,
                    'cod_empleado' => $cod_empleado,
                    'cod_rubro' => $cod_rubro,
                    'descripcion' => $descripcion,
                    'tipo_deduccion' => $tipo_deduccion,
                    'monto' => $monto,
                    'porcentaje' => $porcentaje,
                    'original' => $monto_total_original,
                    'usuario' => $usuarioAccion
                ]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    echo json_encode(["success" => true, "message" => $res['Message'], "id" => $res['CodDeduccionEmpleado']]);
                } else {
                    echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo registrar la deducción."]);
                }
            } else {
                // ACTUALIZAR
                $sql = "EXEC sp_DeduccionEmpleadoActualizar 
                            @CodDeduccionEmpleado = :id,
                            @CodRubro = :cod_rubro,
                            @Descripcion = :descripcion,
                            @TipoDeduccion = :tipo_deduccion,
                            @Monto = :monto,
                            @Porcentaje = :porcentaje,
                            @MontoTotalOriginal = :original,
                            @SaldoRestante = :saldo,
                            @Estado = :estado,
                            @UsuarioAccion = :usuario";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id' => $cod_deduccion_empleado,
                    'cod_rubro' => $cod_rubro,
                    'descripcion' => $descripcion,
                    'tipo_deduccion' => $tipo_deduccion,
                    'monto' => $monto,
                    'porcentaje' => $porcentaje,
                    'original' => $monto_total_original,
                    'saldo' => $saldo_restante,
                    'estado' => $estado,
                    'usuario' => $usuarioAccion
                ]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    echo json_encode(["success" => true, "message" => $res['Message'], "id" => $res['CodDeduccionEmpleado']]);
                } else {
                    echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo actualizar la deducción."]);
                }
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos en SQL Server: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 4. ELIMINAR / INACTIVAR DEDUCCIÓN
    // ==========================================================================
    case 'delete_deduction':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de deducción no válido."]);
            exit;
        }

        try {
            $sql = "EXEC sp_DeduccionEmpleadoEliminar @CodDeduccionEmpleado = :id, @UsuarioAccion = :usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'usuario' => $usuarioAccion
            ]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                echo json_encode(["success" => true, "message" => $res['Message']]);
            } else {
                echo json_encode(["success" => false, "message" => isset($res['Message']) ? $res['Message'] : "No se pudo inactivar la deducción."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos al inactivar: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5. LISTAR RUBROS DE TIPO DEDUCCION (CATÁLOGO PARA DIGITAR)
    // ==========================================================================
    case 'get_deduction_rubros':
        try {
            $stmt = $pdo->query("SELECT CodRubro, Codigo, Descripcion FROM RubroPlanilla WHERE Tipo = 'DEDUCCION' AND Estado = 'ACTIVO' ORDER BY Prioridad");
            $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $rubros]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al listar rubros: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5.1. MANTENIMIENTO: LISTAR RUBROS DEDUCCIÓN
    // ==========================================================================
    case 'list_rubros':
        try {
            $stmt = $pdo->query("SELECT CodRubro, Codigo, Descripcion, Prioridad, AfectaCCSS, AfectaRenta, AfectaVacaciones, AfectaAguinaldo, Estado 
                                 FROM RubroPlanilla 
                                 WHERE Tipo = 'DEDUCCION' 
                                 ORDER BY Prioridad ASC, CodRubro DESC");
            $rubros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $rubros]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al listar rubros: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5.2. MANTENIMIENTO: OBTENER UN RUBRO DEDUCCIÓN
    // ==========================================================================
    case 'get_rubro':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de rubro inválido."]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM RubroPlanilla WHERE CodRubro = :id AND Tipo = 'DEDUCCION'");
            $stmt->execute(['id' => $id]);
            $rubro = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rubro) {
                // Normalizar nulos
                foreach ($rubro as $key => $val) {
                    if (is_null($val)) {
                        $rubro[$key] = '';
                    }
                }
                echo json_encode(["success" => true, "data" => $rubro]);
            } else {
                echo json_encode(["success" => false, "message" => "Rubro no encontrado o no es de tipo deducción."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al recuperar rubro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5.3. MANTENIMIENTO: GUARDAR/MODIFICAR RUBRO DEDUCCIÓN
    // ==========================================================================
    case 'save_rubro':
        $cod_rubro = isset($_POST['cod_rubro']) ? intval($_POST['cod_rubro']) : 0;
        $codigo = isset($_POST['codigo']) ? strtoupper(trim($_POST['codigo'])) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $prioridad = isset($_POST['prioridad']) ? intval($_POST['prioridad']) : 0;
        
        // Checkbox values (nativos de la tabla en SQL Server)
        $afecta_ccss = isset($_POST['afecta_ccss']) ? 1 : 0;
        $afecta_renta = isset($_POST['afecta_renta']) ? 1 : 0;
        $afecta_vacaciones = isset($_POST['afecta_vacaciones']) ? 1 : 0;
        $afecta_aguinaldo = isset($_POST['afecta_aguinaldo']) ? 1 : 0;
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'ACTIVO';

        if (empty($codigo) || empty($descripcion)) {
            echo json_encode(["success" => false, "message" => "Por favor, complete todos los campos obligatorios."]);
            exit;
        }

        try {
            // Validar código único
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM RubroPlanilla WHERE Codigo = :codigo AND CodRubro <> :id");
            $stmtCheck->execute(['codigo' => $codigo, 'id' => $cod_rubro]);
            if (intval($stmtCheck->fetchColumn()) > 0) {
                echo json_encode(["success" => false, "message" => "El código de rubro '$codigo' ya se encuentra registrado por otra clasificación."]);
                exit;
            }

            if ($cod_rubro === 0) {
                // INSERT
                $sql = "INSERT INTO RubroPlanilla (Codigo, Descripcion, Tipo, Prioridad, AfectaCCSS, AfectaRenta, AfectaVacaciones, AfectaAguinaldo, Estado, UsuarioCreacion) 
                        VALUES (:codigo, :descripcion, 'DEDUCCION', :prioridad, :afecta_ccss, :afecta_renta, :afecta_vacaciones, :afecta_aguinaldo, 'ACTIVO', :usuario)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'prioridad' => $prioridad,
                    'afecta_ccss' => $afecta_ccss,
                    'afecta_renta' => $afecta_renta,
                    'afecta_vacaciones' => $afecta_vacaciones,
                    'afecta_aguinaldo' => $afecta_aguinaldo,
                    'usuario' => $usuarioAccion
                ]);

                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (:usuario, 'CREAR RUBRO DEDUCCION', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se creó la clasificación de deducción [$codigo] - $descripcion."
                ]);

                echo json_encode(["success" => true, "message" => "Clasificación de deducción creada con éxito."]);
            } else {
                // UPDATE
                $sql = "UPDATE RubroPlanilla 
                        SET Codigo = :codigo, Descripcion = :descripcion, Prioridad = :prioridad, 
                            AfectaCCSS = :afecta_ccss, AfectaRenta = :afecta_renta, 
                            AfectaVacaciones = :afecta_vacaciones, AfectaAguinaldo = :afecta_aguinaldo, Estado = :estado,
                            FechaModificacion = GETDATE(), UsuarioModificacion = :usuario 
                        WHERE CodRubro = :id AND Tipo = 'DEDUCCION'";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'prioridad' => $prioridad,
                    'afecta_ccss' => $afecta_ccss,
                    'afecta_renta' => $afecta_renta,
                    'afecta_vacaciones' => $afecta_vacaciones,
                    'afecta_aguinaldo' => $afecta_aguinaldo,
                    'estado' => $estado,
                    'usuario' => $usuarioAccion,
                    'id' => $cod_rubro
                ]);

                // Bitácora
                $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (:usuario, 'MODIFICAR RUBRO DEDUCCION', :detalle)");
                $stmtBit->execute([
                    'usuario' => $usuarioAccion,
                    'detalle' => "Se modificó la clasificación de deducción ID $cod_rubro ($codigo)."
                ]);

                echo json_encode(["success" => true, "message" => "Clasificación de deducción actualizada con éxito."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos en SQL Server: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5.4. MANTENIMIENTO: INACTIVAR RUBRO DEDUCCIÓN
    // ==========================================================================
    case 'delete_rubro':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de rubro no válido."]);
            exit;
        }
        try {
            // Validar si tiene transacciones o registros activos en DeduccionEmpleado
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM DeduccionEmpleado WHERE CodRubro = :id AND Estado = 'ACTIVO'");
            $stmtCheck->execute(['id' => $id]);
            if (intval($stmtCheck->fetchColumn()) > 0) {
                echo json_encode(["success" => false, "message" => "No se puede inactivar este rubro porque hay colaboradores que lo tienen asignado en sus deducciones activas."]);
                exit;
            }

            $sql = "UPDATE RubroPlanilla SET Estado = 'INACTIVO', FechaModificacion = GETDATE(), UsuarioModificacion = :usuario WHERE CodRubro = :id AND Tipo = 'DEDUCCION'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'usuario' => $usuarioAccion
            ]);

            // Bitácora
            $stmtBit = $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (:usuario, 'INACTIVAR RUBRO DEDUCCION', :detalle)");
            $stmtBit->execute([
                'usuario' => $usuarioAccion,
                'detalle' => "Baja lógica aplicada a clasificación de deducción ID $id."
            ]);

            echo json_encode(["success" => true, "message" => "Clasificación de deducción inactivada correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos al inactivar rubro: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 6. VALIDAR CÉDULAS PARA MIGRACIÓN DE DEDUCCIONES (EXCEL)
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
            $list = array_values(array_unique($list));
            $placeholders = [];
            $params = [];
            foreach ($list as $index => $c) {
                $placeholder = ":cedula_" . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $c;
            }
            
            $sql = "SELECT CodEmpleado, Identificacion, CodEmpresa,
                           Nombre + ' ' + Apellido1 AS Colaborador,
                           SalarioBase
                    FROM Empleado
                    WHERE Identificacion IN (" . implode(", ", $placeholders) . ") 
                      AND Estado = 'ACTIVO'";
                      
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $mapped = [];
            foreach ($employees as $emp) {
                $mapped[$emp['Identificacion']] = [
                    "CodEmpleado" => intval($emp['CodEmpleado']),
                    "CodEmpresa" => intval($emp['CodEmpresa']),
                    "Colaborador" => $emp['Colaborador'],
                    "SalarioBase" => floatval($emp['SalarioBase'])
                ];
            }
            
            echo json_encode(["success" => true, "data" => $mapped]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al validar cédulas: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 7. INSERCIÓN TRANSACCIONAL MASIVA DE DEDUCCIONES / PRÉSTAMOS
    // ==========================================================================
    case 'save_batch_deductions':
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
            
            $sqlInsert = "EXEC sp_DeduccionEmpleadoInsertar 
                            @CodEmpresa = :cod_empresa,
                            @CodEmpleado = :cod_empleado,
                            @CodRubro = :cod_rubro,
                            @Descripcion = :descripcion,
                            @TipoDeduccion = 'MONTO',
                            @Monto = :monto,
                            @Porcentaje = 0.00,
                            @MontoTotalOriginal = :original,
                            @UsuarioAccion = :usuario";
                            
            $stmtInsert = $pdo->prepare($sqlInsert);
            $successCount = 0;
            
            foreach ($batch_data as $row) {
                $cod_empresa = intval($row['cod_empresa']);
                $cod_empleado = intval($row['cod_empleado']);
                $cod_rubro = intval($row['cod_rubro']);
                $descripcion = trim($row['descripcion']);
                $monto = floatval($row['monto']);
                $original = isset($row['original']) && !empty($row['original']) ? floatval($row['original']) : null;
                
                $stmtInsert->execute([
                    'cod_empresa' => $cod_empresa,
                    'cod_empleado' => $cod_empleado,
                    'cod_rubro' => $cod_rubro,
                    'descripcion' => $descripcion,
                    'monto' => $monto,
                    'original' => $original,
                    'usuario' => $usuarioAccion
                ]);
                
                $res = $stmtInsert->fetch(PDO::FETCH_ASSOC);
                $stmtInsert->closeCursor(); // Liberar recursos del cursor para la siguiente iteración
                
                if ($res && isset($res['Success']) && intval($res['Success']) === 1) {
                    $successCount++;
                } else {
                    $errMsg = isset($res['Message']) ? $res['Message'] : "Error desconocido en SP.";
                    throw new Exception("Error al insertar deducción para empleado $cod_empleado: $errMsg");
                }
            }
            
            $pdo->commit();
            echo json_encode([
                "success" => true,
                "message" => "¡Importación masiva completada! Se registraron exitosamente $successCount deducciones de manera segura.",
                "count" => $successCount
            ]);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Se canceló toda la importación (Transaction Rollback) debido a un error: " . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Acción no soportada por el controlador de deducciones."]);
        break;
}
