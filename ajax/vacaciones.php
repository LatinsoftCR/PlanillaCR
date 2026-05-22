<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX DE VACACIONES (AJAX/VACACIONES.PHP)
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

/**
 * Calcula el salario diario según el tipo de salario (Ley CR).
 */
function calcularSalarioDiarioVacaciones($salarioBase, $tipoSalario, $horasDia = 8.0) {
    $salarioBase = floatval($salarioBase);
    $tipoSalario = strtoupper(trim($tipoSalario));
    $horasDia = floatval($horasDia);
    if ($horasDia <= 0) {
        $horasDia = 8.0;
    }

    if ($tipoSalario === 'SEMANAL') {
        return $salarioBase / 6;
    }
    if ($tipoSalario === 'HORA') {
        return $salarioBase * $horasDia;
    }
    return $salarioBase / 30;
}

/**
 * Arma el desglose de cálculo para un pago de vacaciones.
 */
/**
 * Parsea montos en formato es-CR (208.000,00) o numérico estándar.
 */
function parseMontoColonesCR($valor) {
    if ($valor === null || $valor === '') {
        return 0.0;
    }
    if (is_numeric($valor) && !is_string($valor)) {
        return floatval($valor);
    }
    $s = trim(preg_replace('/[₡\s]/u', '', (string) $valor));
    if ($s === '') {
        return 0.0;
    }
    if (strpos($s, ',') !== false) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif (substr_count($s, '.') > 1) {
        $s = str_replace('.', '', $s);
    }
    return floatval($s);
}

function armarDesgloseVacaciones($emp, $dias, $montoPagado) {
    $salarioBase = floatval($emp['SalarioBase']);
    $tipoSalario = strtoupper(trim($emp['TipoSalario']));
    $horasDia = floatval($emp['HorasDia'] ?? 8);
    $salarioDiario = calcularSalarioDiarioVacaciones($salarioBase, $tipoSalario, $horasDia);
    $dias = floatval($dias);
    $montoPagado = floatval($montoPagado);
    $montoCalculado = round($dias * $salarioDiario, 2);

    $formula = 'Salario Base ÷ 30';
    if ($tipoSalario === 'SEMANAL') {
        $formula = 'Salario Base ÷ 6';
    } elseif ($tipoSalario === 'HORA') {
        $formula = 'Tarifa Hora × Horas Jornada (' . number_format($horasDia, 2) . ')';
    }

    return [
        'SalarioBase' => $salarioBase,
        'TipoSalario' => $tipoSalario,
        'HorasDia' => $horasDia,
        'SalarioDiario' => round($salarioDiario, 2),
        'FormulaDiario' => $formula,
        'DiasCompensados' => $dias,
        'MontoCalculado' => $montoCalculado,
        'MontoPagado' => $montoPagado,
        'Lineas' => [
            ['concepto' => 'Salario Base (' . $tipoSalario . ')', 'valor' => $salarioBase],
            ['concepto' => 'Salario Diario (' . $formula . ')', 'valor' => round($salarioDiario, 2)],
            ['concepto' => 'Días de Vacaciones Compensados', 'valor' => $dias],
            ['concepto' => 'Monto = Salario Diario × Días', 'valor' => $montoCalculado],
        ]
    ];
}

switch ($action) {

    // ==========================================================================
    // 1. LISTAR BALANCES DE VACACIONES DE LOS COLABORADORES
    // ==========================================================================
    case 'list_balances':
        try {
            $sql = "SELECT e.CodEmpleado, e.Nombre, e.Apellido1, COALESCE(e.Apellido2, '') AS Apellido2,
                           e.Nombre + ' ' + e.Apellido1 + ' ' + COALESCE(e.Apellido2, '') AS Colaborador,
                           e.Identificacion, e.FechaIngreso, e.TipoSalario, e.SalarioBase,
                           e.Telefono, e.Direccion, e.FechaNacimiento,
                           d.Descripcion AS Departamento, p.Descripcion AS Puesto, s.Nombre AS Sucursal,
                           emp.Nombre AS EmpresaNombre, emp.NombreComercial AS EmpresaNombreComercial,
                           emp.CedulaJuridica AS EmpresaCedulaJuridica,
                           COALESCE(v.PeriodoInicio, e.FechaIngreso) AS PeriodoInicio,
                           COALESCE(v.PeriodoFin, DATEADD(year, 1, e.FechaIngreso)) AS PeriodoFin,
                           COALESCE(v.DiasGanados, 0.00) AS DiasGanados,
                           COALESCE(v.DiasTomados, 0.00) AS DiasTomados,
                           COALESCE(v.Saldo, 0.00) AS Saldo,
                           v.Codigo AS CodVacacion
                    FROM Empleado e
                    LEFT JOIN Vacaciones v ON e.CodEmpleado = v.CodEmpleado AND v.Estado = 'ACTIVO'
                    LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
                    LEFT JOIN Puesto p ON e.CodPuesto = p.CodPuesto
                    LEFT JOIN Sucursal s ON e.CodSucursal = s.CodSucursal
                    LEFT JOIN Empresa emp ON e.CodEmpresa = emp.CodEmpresa
                    WHERE e.Estado = 'ACTIVO'
                    ORDER BY e.Nombre, e.Apellido1";
            
            $stmt = $pdo->query($sql);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular antigüedad en años y meses
            foreach ($records as &$row) {
                $ingreso = new DateTime($row['FechaIngreso']);
                $hoy = new DateTime();
                $diff = $ingreso->diff($hoy);
                
                $row['AntiguedadAnios'] = $diff->y;
                $row['AntiguedadMeses'] = $diff->m;
                
                // Formatear saldos a 2 decimales para evitar problemas de precisión en JS
                $row['DiasGanados'] = floatval($row['DiasGanados']);
                $row['DiasTomados'] = floatval($row['DiasTomados']);
                $row['Saldo'] = floatval($row['Saldo']);
                $row['SalarioBase'] = floatval($row['SalarioBase']);
            }
            
            echo json_encode([
                "success" => true,
                "data" => $records
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error de base de datos al listar saldos: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 2. OBTENER HISTORIAL DE MOVIMIENTOS DE UN COLABORADOR
    // ==========================================================================
    case 'get_history':
        $cod_empleado = isset($_GET['cod_empleado']) ? intval($_GET['cod_empleado']) : 0;
        if ($cod_empleado <= 0) {
            echo json_encode(["success" => false, "message" => "Código de colaborador inválido."]);
            exit;
        }

        try {
            $sql = "SELECT vd.Codigo, vd.FechaInicio, vd.FechaFin, vd.DiasSolicitados, 
                           vd.TipoMovimiento, vd.Observaciones, vd.MontoPagado, vd.FechaCreacion, vd.UsuarioCreacion
                    FROM VacacionesDetalle vd
                    WHERE vd.CodEmpleado = :cod AND vd.Estado = 'ACTIVO'
                    ORDER BY vd.FechaCreacion DESC, vd.Codigo DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cod' => $cod_empleado]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($records as &$row) {
                $row['DiasSolicitados'] = floatval($row['DiasSolicitados']);
                $row['MontoPagado'] = floatval($row['MontoPagado']);
            }

            echo json_encode([
                "success" => true,
                "data" => $records
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error al obtener historial: " . $e->getMessage()
            ]);
        }
        break;

    // ==========================================================================
    // 3. PRECALCULAR DÍAS HÁBILES Y ESTIMACIÓN FINANCIERA (LEY CR)
    // ==========================================================================
    case 'calculate_preview':
        $cod_empleado = isset($_GET['cod_empleado']) ? intval($_GET['cod_empleado']) : 0;
        $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
        $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
        $tipo_movimiento = isset($_GET['tipo_movimiento']) ? trim($_GET['tipo_movimiento']) : 'DISFRUTE';

        if ($cod_empleado <= 0) {
            echo json_encode(["success" => false, "message" => "Colaborador inválido."]);
            exit;
        }

        try {
            // Obtener datos del empleado para ver su tipo de salario
            $stmtEmp = $pdo->prepare("
                SELECT e.SalarioBase, e.TipoSalario, COALESCE(h.HorasDia, 8.00) AS HorasDia
                FROM Empleado e
                LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                WHERE e.CodEmpleado = :cod
            ");
            $stmtEmp->execute(['cod' => $cod_empleado]);
            $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);

            if (!$emp) {
                echo json_encode(["success" => false, "message" => "No se encontró el colaborador."]);
                exit;
            }

            $salarioBase = floatval($emp['SalarioBase']);
            $tipoSalario = strtoupper(trim($emp['TipoSalario'])); // SEMANAL, QUINCENAL, MENSUAL, HORA, DESTAJO
            $horasDia = floatval($emp['HorasDia']);
            if ($horasDia <= 0) $horasDia = 8.00;
            
            // Calcular salario diario según la ley de Costa Rica
            $salarioDiario = 0.00;
            if ($tipoSalario === 'SEMANAL') {
                // En pago semanal se divide entre 6 (excluye el día de descanso no pagado)
                $salarioDiario = $salarioBase / 6;
            } elseif ($tipoSalario === 'HORA') {
                // En pago por hora, es el salario base (tarifa hora) multiplicado por las horas de la jornada diaria
                $salarioDiario = $salarioBase * $horasDia;
            } else {
                // Pago quincenal/mensual se divide entre 30 (cómputo comercial de 30 días con descansos incluidos)
                $salarioDiario = $salarioBase / 30;
            }

            $diasHabiles = 0.00;
            
            if ($tipo_movimiento === 'DISFRUTE') {
                if (empty($fecha_inicio) || empty($fecha_fin)) {
                    echo json_encode(["success" => false, "message" => "Rango de fechas incompleto."]);
                    exit;
                }

                $start = new DateTime($fecha_inicio);
                $end = new DateTime($fecha_fin);
                $end->modify('+1 day'); // Incluir el día final en el rango de iteración

                $interval = new DateInterval('P1D');
                $daterange = new DatePeriod($start, $interval ,$end);

                foreach($daterange as $date){
                    $dayOfWeek = intval($date->format('w')); // 0 = Domingo, 6 = Sábado
                    
                    if ($tipoSalario === 'SEMANAL') {
                        // En régimen semanal tradicional, los domingos son de descanso absoluto y no hábiles para vacaciones
                        if ($dayOfWeek !== 0) {
                            $diasHabiles += 1.00;
                        }
                    } else {
                        // En régimen mensual/quincenal comercial, también se goza excluyendo domingos por ley general,
                        // pero algunos contratos excluyen sábados. Dejemos domingos excluidos como regla legal estándar.
                        if ($dayOfWeek !== 0) {
                            $diasHabiles += 1.00;
                        }
                    }
                }
            } else {
                // Ajustes y pagos directos no tienen rango de fechas, se ingresa la cantidad de días directamente
                $diasHabiles = isset($_GET['dias']) ? floatval($_GET['dias']) : 0.00;
            }

            $montoEstimado = round($diasHabiles * $salarioDiario, 2);

            echo json_encode([
                "success" => true,
                "dias" => $diasHabiles,
                "salario_diario" => $salarioDiario,
                "monto" => $montoEstimado
            ]);

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error al calcular: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 4. GUARDAR / REGISTRAR SOLICITUD DE VACACIONES (TRANSACCIONAL)
    // ==========================================================================
    case 'save_request':
        $cod_empleado = isset($_POST['cod_empleado']) ? intval($_POST['cod_empleado']) : 0;
        $tipo_movimiento = isset($_POST['tipo_movimiento']) ? trim($_POST['tipo_movimiento']) : 'DISFRUTE';
        $fecha_inicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : null;
        $fecha_fin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
        $dias_solicitados = isset($_POST['dias_solicitados']) ? floatval($_POST['dias_solicitados']) : 0.00;
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $monto_pagado = isset($_POST['monto_pagado']) ? parseMontoColonesCR($_POST['monto_pagado']) : 0.00;

        // Validaciones básicas
        if ($cod_empleado <= 0) {
            echo json_encode(["success" => false, "message" => "Debe seleccionar un colaborador."]);
            exit;
        }
        if ($dias_solicitados <= 0) {
            echo json_encode(["success" => false, "message" => "Los días de vacaciones deben ser mayores a cero."]);
            exit;
        }
        if ($tipo_movimiento === 'DISFRUTE' && (empty($fecha_inicio) || empty($fecha_fin))) {
            echo json_encode(["success" => false, "message" => "Debe ingresar las fechas de inicio y fin para el disfrute."]);
            exit;
        }

        if ($tipo_movimiento === 'PAGO' && $monto_pagado <= 0) {
            $stmtEmpCalc = $pdo->prepare("
                SELECT e.SalarioBase, e.TipoSalario, COALESCE(h.HorasDia, 8.00) AS HorasDia
                FROM Empleado e
                LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                WHERE e.CodEmpleado = :cod
            ");
            $stmtEmpCalc->execute(['cod' => $cod_empleado]);
            $empCalc = $stmtEmpCalc->fetch(PDO::FETCH_ASSOC);
            if ($empCalc) {
                $salarioDiario = calcularSalarioDiarioVacaciones($empCalc['SalarioBase'], $empCalc['TipoSalario'], $empCalc['HorasDia']);
                $monto_pagado = round($dias_solicitados * $salarioDiario, 2);
            }
        }

        try {
            $pdo->beginTransaction();

            // 1. Obtener registro de saldo actual del empleado
            $stmtCheck = $pdo->prepare("SELECT Codigo, Saldo, DiasGanados, DiasTomados FROM Vacaciones WHERE CodEmpleado = :cod AND Estado = 'ACTIVO'");
            $stmtCheck->execute(['cod' => $cod_empleado]);
            $vacSaldo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            // Validar que tenga suficiente saldo para disfrutes o pagos
            if ($tipo_movimiento !== 'AJUSTE') {
                $saldoDisponible = $vacSaldo ? floatval($vacSaldo['Saldo']) : 0.00;
                if ($saldoDisponible < $dias_solicitados) {
                    throw new Exception("Saldo insuficiente. El colaborador solo tiene " . number_format($saldoDisponible, 2) . " días de vacaciones disponibles.");
                }
            }

            // 2. Insertar detalle de movimiento en VacacionesDetalle
            $sqlDetalle = "INSERT INTO VacacionesDetalle (CodEmpleado, FechaInicio, FechaFin, DiasSolicitados, TipoMovimiento, Observaciones, MontoPagado, UsuarioCreacion)
                           VALUES (:cod, :inicio, :fin, :dias, :tipo, :obs, :monto, :usuario)";
            $stmtDetDet = $pdo->prepare($sqlDetalle);
            $stmtDetDet->execute([
                'cod' => $cod_empleado,
                'inicio' => empty($fecha_inicio) ? null : $fecha_inicio,
                'fin' => empty($fecha_fin) ? null : $fecha_fin,
                'dias' => $dias_solicitados,
                'tipo' => $tipo_movimiento,
                'obs' => $observaciones,
                'monto' => $monto_pagado,
                'usuario' => $usuarioAccion
            ]);
            $codDetalle = $pdo->lastInsertId();

            // 3. Actualizar la tabla acumulada principal de Vacaciones
            if ($vacSaldo) {
                // Ya existe registro activo de vacaciones
                if ($tipo_movimiento === 'AJUSTE') {
                    $sqlUpd = "UPDATE Vacaciones 
                               SET DiasGanados = DiasGanados + :dias1,
                                   Saldo = Saldo + :dias2,
                                   FechaModificacion = GETDATE(),
                                   UsuarioModificacion = :usuario
                               WHERE Codigo = :cod";
                } else {
                    // DISFRUTE o PAGO
                    $sqlUpd = "UPDATE Vacaciones 
                               SET DiasTomados = DiasTomados + :dias1,
                                   Saldo = Saldo - :dias2,
                                   FechaModificacion = GETDATE(),
                                   UsuarioModificacion = :usuario
                               WHERE Codigo = :cod";
                }
                $stmtUpd = $pdo->prepare($sqlUpd);
                $stmtUpd->execute([
                    'dias1' => $dias_solicitados,
                    'dias2' => $dias_solicitados,
                    'usuario' => $usuarioAccion,
                    'cod' => $vacSaldo['Codigo']
                ]);
            } else {
                // No tiene registro de vacaciones activo, debemos crearlo
                // Buscar fecha de ingreso del empleado
                $stmtEmp = $pdo->prepare("SELECT FechaIngreso FROM Empleado WHERE CodEmpleado = :cod");
                $stmtEmp->execute(['cod' => $cod_empleado]);
                $fechaIngreso = $stmtEmp->fetchColumn();

                $periodoInicio = $fechaIngreso ? $fechaIngreso : date('Y-m-d');
                $periodoFin = date('Y-m-d', strtotime($periodoInicio . ' + 1 year'));

                if ($tipo_movimiento === 'AJUSTE') {
                    $diasGan = $dias_solicitados;
                    $diasTom = 0.00;
                    $saldoFin = $dias_solicitados;
                } else {
                    // Si se registra un rebajo sin saldo previo (por ejemplo, adelanto de vacaciones)
                    $diasGan = 0.00;
                    $diasTom = $dias_solicitados;
                    $saldoFin = -$dias_solicitados;
                }

                $sqlIns = "INSERT INTO Vacaciones (CodEmpleado, PeriodoInicio, PeriodoFin, DiasGanados, DiasTomados, Saldo, Estado, UsuarioCreacion)
                           VALUES (:cod, :p_inicio, :p_fin, :ganados, :tomados, :saldo, 'ACTIVO', :usuario)";
                $stmtIns = $pdo->prepare($sqlIns);
                $stmtIns->execute([
                    'cod' => $cod_empleado,
                    'p_inicio' => $periodoInicio,
                    'p_fin' => $periodoFin,
                    'ganados' => $diasGan,
                    'tomados' => $diasTom,
                    'saldo' => $saldoFin,
                    'usuario' => $usuarioAccion
                ]);
            }

            $pdo->commit();
            echo json_encode([
                "success" => true,
                "message" => "¡Movimiento de vacaciones registrado con éxito!",
                "codigo_movimiento" => $codDetalle
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "message" => "Error al guardar solicitud: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5. ANULAR SOLICITUD DE VACACIONES
    // ==========================================================================
    case 'delete_request':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de solicitud inválido."]);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Obtener los detalles del movimiento para saber cuántos días devolver
            $stmtDet = $pdo->prepare("SELECT CodEmpleado, DiasSolicitados, TipoMovimiento, Estado FROM VacacionesDetalle WHERE Codigo = :id");
            $stmtDet->execute(['id' => $id]);
            $mov = $stmtDet->fetch(PDO::FETCH_ASSOC);

            if (!$mov) {
                throw new Exception("No se encontró el movimiento de vacaciones.");
            }
            if ($mov['Estado'] !== 'ACTIVO') {
                throw new Exception("Este movimiento ya fue anulado o se encuentra inactivo.");
            }

            $cod_empleado = intval($mov['CodEmpleado']);
            $dias = floatval($mov['DiasSolicitados']);
            $tipo = $mov['TipoMovimiento'];

            // 2. Anular el movimiento en VacacionesDetalle
            $stmtAnular = $pdo->prepare("UPDATE VacacionesDetalle SET Estado = 'INACTIVO', FechaModificacion = GETDATE(), UsuarioModificacion = :usuario WHERE Codigo = :id");
            $stmtAnular->execute([
                'id' => $id,
                'usuario' => $usuarioAccion
            ]);

            // 3. Revertir saldo en la tabla Vacaciones
            $stmtCheck = $pdo->prepare("SELECT Codigo FROM Vacaciones WHERE CodEmpleado = :cod AND Estado = 'ACTIVO'");
            $stmtCheck->execute(['cod' => $cod_empleado]);
            $vac = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($vac) {
                if ($tipo === 'AJUSTE') {
                    // Si se anula un ajuste de saldo positivo, restamos los días del saldo y de ganados
                    $sqlUpd = "UPDATE Vacaciones 
                               SET DiasGanados = DiasGanados - :dias1,
                                   Saldo = Saldo - :dias2,
                                   FechaModificacion = GETDATE(),
                                   UsuarioModificacion = :usuario
                               WHERE Codigo = :cod";
                } else {
                    // Si se anula un DISFRUTE o PAGO, devolvemos los días al saldo sumándolos y restándolos de tomados
                    $sqlUpd = "UPDATE Vacaciones 
                               SET DiasTomados = DiasTomados - :dias1,
                                   Saldo = Saldo + :dias2,
                                   FechaModificacion = GETDATE(),
                                   UsuarioModificacion = :usuario
                               WHERE Codigo = :cod";
                }

                $stmtUpd = $pdo->prepare($sqlUpd);
                $stmtUpd->execute([
                    'dias1' => $dias,
                    'dias2' => $dias,
                    'usuario' => $usuarioAccion,
                    'cod' => $vac['Codigo']
                ]);
            }

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "¡Movimiento anulado y saldo restablecido correctamente!"]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "message" => "Error al anular movimiento: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 6. LISTAR PAGOS DE VACACIONES (COMPENSACIÓN / VENTA)
    // ==========================================================================
    case 'list_payments':
        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        if ($year < 2000 || $year > 2100) {
            echo json_encode(["success" => false, "message" => "Año fuera de rango."]);
            exit;
        }

        try {
            $sql = "
                SELECT 
                    vd.Codigo AS CodMovimiento,
                    vd.CodEmpleado,
                    vd.DiasSolicitados,
                    vd.MontoPagado,
                    vd.FechaCreacion,
                    vd.Observaciones,
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    e.TipoSalario,
                    e.SalarioBase,
                    COALESCE(h.HorasDia, 8.00) AS HorasDia,
                    d.Descripcion AS Departamento,
                    pu.Descripcion AS Puesto,
                    ISNULL(e.Banco, 'NO ESPECIFICADO') AS Banco,
                    ISNULL(e.CuentaIBAN, 'NO ESPECIFICADA') AS CuentaIBAN
                FROM VacacionesDetalle vd
                INNER JOIN Empleado e ON vd.CodEmpleado = e.CodEmpleado
                LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
                LEFT JOIN Puesto pu ON e.CodPuesto = pu.CodPuesto
                WHERE vd.TipoMovimiento = 'PAGO'
                  AND vd.Estado = 'ACTIVO'
                  AND vd.MontoPagado > 0
                  AND YEAR(vd.FechaCreacion) = :year
                ORDER BY vd.FechaCreacion DESC, vd.Codigo DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['year' => $year]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $montoPagado = floatval($row['MontoPagado']);
                $desglose = armarDesgloseVacaciones($row, $row['DiasSolicitados'], $montoPagado);
                if ($desglose['MontoCalculado'] > 100 && $montoPagado > 0 && $montoPagado < $desglose['MontoCalculado'] * 0.5) {
                    $montoPagado = $desglose['MontoCalculado'];
                    $desglose['MontoPagado'] = $montoPagado;
                }
                $data[] = [
                    'CodMovimiento' => intval($row['CodMovimiento']),
                    'CodEmpleado' => intval($row['CodEmpleado']),
                    'Identificacion' => $row['Identificacion'],
                    'Colaborador' => $row['Colaborador'],
                    'Departamento' => $row['Departamento'],
                    'Puesto' => $row['Puesto'],
                    'DiasSolicitados' => floatval($row['DiasSolicitados']),
                    'MontoPagado' => $montoPagado,
                    'FechaCreacion' => $row['FechaCreacion'],
                    'Observaciones' => $row['Observaciones'],
                    'Banco' => $row['Banco'],
                    'CuentaIBAN' => $row['CuentaIBAN'],
                    'SalarioBase' => floatval($row['SalarioBase']),
                    'TipoSalario' => $row['TipoSalario'],
                    'SalarioDiario' => $desglose['SalarioDiario'],
                    'MontoCalculado' => $desglose['MontoCalculado'],
                    'Breakdown' => $desglose
                ];
            }

            echo json_encode([
                "success" => true,
                "year" => $year,
                "data" => $data
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al listar pagos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 7. DESGLOSE DE UN PAGO DE VACACIONES
    // ==========================================================================
    case 'get_breakdown':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "Código de movimiento inválido."]);
            exit;
        }

        try {
            $sql = "
                SELECT 
                    vd.Codigo AS CodMovimiento,
                    vd.CodEmpleado,
                    vd.DiasSolicitados,
                    vd.MontoPagado,
                    vd.FechaCreacion,
                    vd.Observaciones,
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    e.TipoSalario,
                    e.SalarioBase,
                    COALESCE(h.HorasDia, 8.00) AS HorasDia,
                    d.Descripcion AS Departamento,
                    pu.Descripcion AS Puesto
                FROM VacacionesDetalle vd
                INNER JOIN Empleado e ON vd.CodEmpleado = e.CodEmpleado
                LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
                LEFT JOIN Puesto pu ON e.CodPuesto = pu.CodPuesto
                WHERE vd.Codigo = :id AND vd.TipoMovimiento = 'PAGO' AND vd.Estado = 'ACTIVO'
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(["success" => false, "message" => "Pago de vacaciones no encontrado."]);
                exit;
            }

            $montoPagado = floatval($row['MontoPagado']);
            $desglose = armarDesgloseVacaciones($row, $row['DiasSolicitados'], $montoPagado);
            if ($desglose['MontoCalculado'] > 100 && $montoPagado > 0 && $montoPagado < $desglose['MontoCalculado'] * 0.5) {
                $desglose['MontoPagado'] = $desglose['MontoCalculado'];
            }
            echo json_encode([
                "success" => true,
                "colaborador" => $row['Colaborador'],
                "data" => $desglose
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Error al obtener desglose: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 8. EXPORTAR PAGOS DE VACACIONES A BANCO (BAC, BNCR, BCR)
    // ==========================================================================
    case 'export_bank':
        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        $banco = isset($_GET['banco']) ? trim($_GET['banco']) : 'BAC';
        $referencia = isset($_GET['referencia']) ? trim($_GET['referencia']) : 'VACACIONES ' . $year;

        if ($year < 2000 || $year > 2100) {
            http_response_code(400);
            echo "Error: Año fuera de rango.";
            exit;
        }

        try {
            $sql = "
                SELECT 
                    vd.Codigo,
                    vd.DiasSolicitados,
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    e.TipoSalario,
                    e.SalarioBase,
                    COALESCE(h.HorasDia, 8.00) AS HorasDia,
                    ISNULL(e.CuentaIBAN, 'NO ESPECIFICADA') AS CuentaIBAN,
                    vd.MontoPagado
                FROM VacacionesDetalle vd
                INNER JOIN Empleado e ON vd.CodEmpleado = e.CodEmpleado
                LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
                WHERE vd.TipoMovimiento = 'PAGO'
                  AND vd.Estado = 'ACTIVO'
                  AND vd.MontoPagado > 0
                  AND YEAR(vd.FechaCreacion) = :year
                ORDER BY Colaborador
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['year' => $year]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                http_response_code(404);
                echo "Error: No hay pagos de vacaciones para exportar en el año {$year}.";
                exit;
            }

            $referencia = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $referencia);
            $referencia = substr($referencia, 0, 40);

            $output = "";
            $filename = "Vacaciones_" . $banco . "_" . $year;

            $resolverMontoExport = function ($r) {
                $montoExp = floatval($r['MontoPagado']);
                $desgloseExp = armarDesgloseVacaciones($r, $r['DiasSolicitados'], $montoExp);
                if ($desgloseExp['MontoCalculado'] > 100 && $montoExp > 0 && $montoExp < $desgloseExp['MontoCalculado'] * 0.5) {
                    return $desgloseExp['MontoCalculado'];
                }
                return $montoExp;
            };

            switch ($banco) {
                case 'BAC':
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    $output .= "CuentaDestino,Monto,Identificacion,Nombre,Referencia\r\n";
                    foreach ($rows as $r) {
                        $montoExp = $resolverMontoExport($r);
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($montoExp, 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                case 'BNCR':
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
                    foreach ($rows as $r) {
                        $montoExp = $resolverMontoExport($r);
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s\t%s\t%s\t%s\t%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($montoExp, 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                case 'BCR':
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
                    foreach ($rows as $r) {
                        $montoExp = $resolverMontoExport($r);
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($montoExp, 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                default:
                    http_response_code(400);
                    echo "Error: Banco no soportado.";
                    exit;
            }

            $isLocal = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['SERVER_NAME'] === 'localhost');
            if ($isLocal) {
                $possibleDesktops = [
                    'C:\Users\Usuario\Desktop',
                    'C:\Users\\' . getenv('USERNAME') . '\Desktop'
                ];
                $envProfile = getenv('USERPROFILE');
                if ($envProfile && strpos(strtolower($envProfile), 'systemprofile') === false) {
                    $possibleDesktops[] = $envProfile . DIRECTORY_SEPARATOR . 'Desktop';
                }
                $desktopPath = '';
                foreach ($possibleDesktops as $path) {
                    if (is_dir($path)) {
                        $desktopPath = $path;
                        break;
                    }
                }
                if (!empty($desktopPath)) {
                    $ext = ($banco === 'BAC') ? '.csv' : '.txt';
                    @file_put_contents($desktopPath . DIRECTORY_SEPARATOR . $filename . $ext, $output);
                }
            }

            $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (?, 'EXPORTAR VACACIONES BANCO', ?)")
                ->execute([$usuarioAccion, "Exportado archivo bancario {$banco} para pagos de vacaciones del año {$year}."]);

            echo $output;
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error de base de datos: " . $e->getMessage();
            exit;
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Acción no soportada por el controlador."]);
        break;
}
