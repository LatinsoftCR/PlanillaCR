<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLADOR AJAX DE PLANILLAS (AJAX/PLANILLA.PHP)
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
    // 1. LISTAR PERIODOS DE PLANILLA CALCULADOS
    // ==========================================================================
    case 'list_planillas':
        try {
            $sql = "
                SELECT 
                    p.CodPlanilla,
                    tp.Descripcion AS TipoPlanilla,
                    p.FechaInicio,
                    p.FechaFin,
                    p.FechaPago,
                    p.Estado,
                    ISNULL(SUM(CASE WHEN rp.Tipo = 'DEVENGO' THEN pd.Monto ELSE 0 END), 0) AS TotalDevengos,
                    ISNULL(SUM(CASE WHEN rp.Tipo = 'DEDUCCION' THEN pd.Monto ELSE 0 END), 0) AS TotalDeducciones
                FROM Planilla p
                INNER JOIN TipoPlanilla tp ON p.CodTipoPlanilla = tp.CodTipoPlanilla
                LEFT JOIN PlanillaDetalle pd ON p.CodPlanilla = pd.CodPlanilla
                LEFT JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
                GROUP BY p.CodPlanilla, tp.Descripcion, p.FechaInicio, p.FechaFin, p.FechaPago, p.Estado
                ORDER BY p.CodPlanilla DESC
            ";
            
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear y calcular neto
            $formatted = [];
            foreach ($data as $r) {
                $devengos = floatval($r['TotalDevengos']);
                $deducciones = floatval($r['TotalDeducciones']);
                $neto = $devengos - $deducciones;
                
                $formatted[] = [
                    'CodPlanilla' => intval($r['CodPlanilla']),
                    'TipoPlanilla' => $r['TipoPlanilla'],
                    'FechaInicio' => $r['FechaInicio'],
                    'FechaFin' => $r['FechaFin'],
                    'FechaPago' => $r['FechaPago'],
                    'Estado' => $r['Estado'],
                    'TotalDevengos' => $devengos,
                    'TotalDeducciones' => $deducciones,
                    'TotalNeto' => $neto
                ];
            }
            
            echo json_encode(["success" => true, "data" => $formatted]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 2. OBTENER TIPOS DE PLANILLA DISPONIBLES
    // ==========================================================================
    case 'get_tipo_planillas':
        try {
            $stmt = $pdo->query("SELECT CodTipoPlanilla, Descripcion FROM TipoPlanilla WHERE CodTipoPlanilla <= 3 ORDER BY CodTipoPlanilla");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $data]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener tipos de planilla: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 3. GENERAR Y CALCULAR PLANILLA COMPLETA
    // ==========================================================================
    case 'generate_planilla':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $codTipoPlanilla = isset($_POST['cod_tipo_planilla']) ? intval($_POST['cod_tipo_planilla']) : 0;
        $fechaInicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
        $fechaFin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
        $fechaPago = isset($_POST['fecha_pago']) ? trim($_POST['fecha_pago']) : '';

        if ($codTipoPlanilla === 0 || empty($fechaInicio) || empty($fechaFin) || empty($fechaPago)) {
            echo json_encode(["success" => false, "message" => "Todos los campos de fecha y tipo son obligatorios."]);
            exit;
        }

        if ($fechaInicio > $fechaFin) {
            echo json_encode(["success" => false, "message" => "La fecha de inicio no puede ser posterior a la fecha de fin."]);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Ejecutar sp_GenerarPlanilla para insertar cabecera y salarios/horas/deducciones base
            $stmt = $pdo->prepare("EXEC sp_GenerarPlanilla @CodEmpresa = 1, @CodTipoPlanilla = :tipo, @FechaInicio = :ini, @FechaFin = :fin, @FechaPago = :pago, @Usuario = :user");
            $stmt->execute([
                ':tipo' => $codTipoPlanilla,
                ':ini' => $fechaInicio,
                ':fin' => $fechaFin,
                ':pago' => $fechaPago,
                ':user' => $usuarioAccion
            ]);
            
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $codPlanilla = isset($res['CodPlanilla']) ? intval($res['CodPlanilla']) : 0;
            $stmt->closeCursor();

            if ($codPlanilla === 0) {
                throw new Exception("El Stored Procedure sp_GenerarPlanilla no retornó un CodPlanilla válido.");
            }

            // 2. Ejecutar sp_CalcularCCSS para deducir CCSS obrero
            $stmtCCSS = $pdo->prepare("EXEC sp_CalcularCCSS @CodPlanilla = :id, @Usuario = :user");
            $stmtCCSS->execute([
                ':id' => $codPlanilla,
                ':user' => $usuarioAccion
            ]);
            $stmtCCSS->closeCursor();

            // 3. Ejecutar sp_CalcularRenta para retener renta de hacienda
            $stmtRenta = $pdo->prepare("EXEC sp_CalcularRenta @CodPlanilla = :id, @Usuario = :user");
            $stmtRenta->execute([
                ':id' => $codPlanilla,
                ':user' => $usuarioAccion
            ]);
            $stmtRenta->closeCursor();

            $pdo->commit();
            echo json_encode([
                "success" => true, 
                "message" => "¡Planilla generada y calculada exitosamente en SQL Server!", 
                "cod_planilla" => $codPlanilla
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "message" => "Error al generar planilla: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 4. OBTENER DETALLE DE COLABORADORES DE UNA PLANILLA
    // ==========================================================================
    case 'get_planilla_detalle':
        $codPlanilla = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($codPlanilla === 0) {
            echo json_encode(["success" => false, "message" => "Código de planilla inválido."]);
            exit;
        }

        try {
            $sql = "
                SELECT 
                    e.CodEmpleado,
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    ISNULL(SUM(CASE WHEN rp.Codigo = 'SAL' THEN pd.Monto ELSE 0 END), 0) AS SalarioBase,
                    ISNULL(SUM(CASE WHEN rp.Codigo = 'HEX' THEN pd.Monto ELSE 0 END), 0) AS HorasExtra,
                    ISNULL(SUM(CASE WHEN rp.Codigo = 'DES' THEN pd.Monto ELSE 0 END), 0) AS Destajos,
                    ISNULL(SUM(CASE WHEN rp.Codigo = 'CCSS' THEN pd.Monto ELSE 0 END), 0) AS CCSS,
                    ISNULL(SUM(CASE WHEN rp.Codigo = 'REN' THEN pd.Monto ELSE 0 END), 0) AS Renta,
                    ISNULL(SUM(CASE WHEN rp.Tipo = 'DEDUCCION' AND rp.Codigo NOT IN ('CCSS', 'REN') THEN pd.Monto ELSE 0 END), 0) AS OtrasDeducciones,
                    ISNULL(SUM(CASE WHEN rp.Tipo = 'DEVENGO' THEN pd.Monto ELSE 0 END), 0) AS TotalDevengos,
                    ISNULL(SUM(CASE WHEN rp.Tipo = 'DEDUCCION' THEN pd.Monto ELSE 0 END), 0) AS TotalDeducciones,
                    (ISNULL(SUM(CASE WHEN rp.Tipo = 'DEVENGO' THEN pd.Monto ELSE 0 END), 0) - ISNULL(SUM(CASE WHEN rp.Tipo = 'DEDUCCION' THEN pd.Monto ELSE 0 END), 0)) AS Neto
                FROM PlanillaDetalle pd
                INNER JOIN Empleado e ON pd.CodEmpleado = e.CodEmpleado
                INNER JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
                WHERE pd.CodPlanilla = :cod_planilla
                GROUP BY e.CodEmpleado, e.Identificacion, e.Nombre, e.Apellido1, e.Apellido2
                ORDER BY Colaborador
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':cod_planilla' => $codPlanilla]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear numéricos
            $formatted = [];
            foreach ($data as $r) {
                $formatted[] = [
                    'CodEmpleado' => intval($r['CodEmpleado']),
                    'Identificacion' => $r['Identificacion'],
                    'Colaborador' => $r['Colaborador'],
                    'SalarioBase' => floatval($r['SalarioBase']),
                    'HorasExtra' => floatval($r['HorasExtra']),
                    'Destajos' => floatval($r['Destajos']),
                    'CCSS' => floatval($r['CCSS']),
                    'Renta' => floatval($r['Renta']),
                    'OtrasDeducciones' => floatval($r['OtrasDeducciones']),
                    'TotalDevengos' => floatval($r['TotalDevengos']),
                    'TotalDeducciones' => floatval($r['TotalDeducciones']),
                    'Neto' => floatval($r['Neto'])
                ];
            }

            echo json_encode(["success" => true, "data" => $formatted]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener detalle de planilla: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 5. CERRAR / APLICAR PLANILLA (CAMBIA ESTADO A APLICADA)
    // ==========================================================================
    case 'apply_planilla':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $codPlanilla = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($codPlanilla === 0) {
            echo json_encode(["success" => false, "message" => "Código de planilla inválido."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("EXEC sp_AplicarPlanilla @CodPlanilla = :id, @Usuario = :user");
            $stmt->execute([
                ':id' => $codPlanilla,
                ':user' => $usuarioAccion
            ]);
            echo json_encode(["success" => true, "message" => "¡La planilla ha sido aplicada y cerrada definitivamente!"]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al aplicar planilla: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 6. ANULAR / ELIMINAR PLANILLA EN ESTADO ABIERTO
    // ==========================================================================
    case 'annul_planilla':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            exit;
        }

        $codPlanilla = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($codPlanilla === 0) {
            echo json_encode(["success" => false, "message" => "Código de planilla inválido."]);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Obtener detalles de la planilla
            $stmt = $pdo->prepare("SELECT FechaInicio, FechaFin, CodEmpresa, Estado FROM Planilla WHERE CodPlanilla = ?");
            $stmt->execute([$codPlanilla]);
            $planilla = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$planilla) {
                throw new Exception("El registro de planilla especificado no existe.");
            }

            if ($planilla['Estado'] === 'APLICADA') {
                throw new Exception("No es posible anular una planilla que ya ha sido cerrada y aplicada definitivamente.");
            }

            // 2. Revertir amortizaciones de deducciones de préstamo
            // Buscamos detalles de deducciones aplicadas que tengan MontoTotalOriginal
            $stmtDeds = $pdo->prepare("
                SELECT pd.CodEmpleado, pd.CodRubro, pd.Monto, de.CodDeduccionEmpleado
                FROM PlanillaDetalle pd
                INNER JOIN DeduccionEmpleado de ON pd.CodEmpleado = de.CodEmpleado AND pd.CodRubro = de.CodRubro
                WHERE pd.CodPlanilla = ? AND de.MontoTotalOriginal IS NOT NULL
            ");
            $stmtDeds->execute([$codPlanilla]);
            $deds = $stmtDeds->fetchAll(PDO::FETCH_ASSOC);
            foreach ($deds as $ded) {
                $pdo->prepare("
                    UPDATE DeduccionEmpleado
                    SET SaldoRestante = SaldoRestante + ?,
                        Estado = 'ACTIVO'
                    WHERE CodDeduccionEmpleado = ?
                ")->execute([$ded['Monto'], $ded['CodDeduccionEmpleado']]);
            }

            // 3. Revertir estado de Horas Extra a PENDIENTE
            $pdo->prepare("
                UPDATE HoraExtra
                SET Estado = 'PENDIENTE',
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = ?
                WHERE Estado = 'PROCESADO'
                  AND Fecha BETWEEN ? AND ?
                  AND CodEmpleado IN (SELECT CodEmpleado FROM Empleado WHERE CodEmpresa = ?)
            ")->execute([$usuarioAccion, $planilla['FechaInicio'], $planilla['FechaFin'], $planilla['CodEmpresa']]);

            // 4. Revertir estado de Destajos a PENDIENTE
            $pdo->prepare("
                UPDATE Destajo
                SET Estado = 'PENDIENTE',
                    FechaModificacion = GETDATE(),
                    UsuarioModificacion = ?
                WHERE Estado = 'PROCESADO'
                  AND Fecha BETWEEN ? AND ?
                  AND CodEmpleado IN (SELECT CodEmpleado FROM Empleado WHERE CodEmpresa = ?)
            ")->execute([$usuarioAccion, $planilla['FechaInicio'], $planilla['FechaFin'], $planilla['CodEmpresa']]);

            // 5. Eliminar detalles de la planilla
            $pdo->prepare("DELETE FROM PlanillaDetalle WHERE CodPlanilla = ?")->execute([$codPlanilla]);

            // 6. Eliminar cabecera de la planilla
            $pdo->prepare("DELETE FROM Planilla WHERE CodPlanilla = ?")->execute([$codPlanilla]);

            // 7. Auditoría
            $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (?, 'ANULACIÓN PLANILLA', ?)")
                ->execute([$usuarioAccion, 'ANULACIÓN PLANILLA', 'Planilla ID: ' . $codPlanilla . ' anulada correctamente por el usuario. Se revirtieron horas extra, destajos y amortizaciones de préstamos.']);

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "¡Planilla anulada correctamente! Los destajos, horas extra y saldos amortizados se revirtieron a su estado original."]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "message" => "Error al anular planilla: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 7. EXPORTAR TRANSFERENCIA BANCARIA (BAC, BNCR, BCR)
    // ==========================================================================
    case 'export_bank':
        $codPlanilla = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $banco = isset($_GET['banco']) ? strtoupper(trim($_GET['banco'])) : '';

        if ($codPlanilla === 0 || empty($banco)) {
            http_response_code(400);
            echo "Error: Código de planilla o banco no especificado.";
            exit;
        }

        try {
            // 1. Obtener detalles de la planilla
            $stmtP = $pdo->prepare("
                SELECT p.CodPlanilla, tp.Descripcion AS TipoPlanilla, p.FechaInicio, p.FechaFin, p.FechaPago
                FROM Planilla p
                INNER JOIN TipoPlanilla tp ON p.CodTipoPlanilla = tp.CodTipoPlanilla
                WHERE p.CodPlanilla = ?
            ");
            $stmtP->execute([$codPlanilla]);
            $planillaInfo = $stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$planillaInfo) {
                http_response_code(404);
                echo "Error: Planilla no encontrada.";
                exit;
            }

            // 2. Obtener los colaboradores de la planilla con su neto y CuentaIBAN
            $sql = "
                SELECT 
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    e.Banco,
                    ISNULL(e.CuentaIBAN, '') AS CuentaIBAN,
                    (ISNULL(SUM(CASE WHEN rp.Tipo = 'DEVENGO' THEN pd.Monto ELSE 0 END), 0) - 
                     ISNULL(SUM(CASE WHEN rp.Tipo = 'DEDUCCION' THEN pd.Monto ELSE 0 END), 0)) AS Neto
                FROM PlanillaDetalle pd
                INNER JOIN Empleado e ON pd.CodEmpleado = e.CodEmpleado
                INNER JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
                WHERE pd.CodPlanilla = :cod_planilla
                GROUP BY e.Identificacion, e.Nombre, e.Apellido1, e.Apellido2, e.Banco, e.CuentaIBAN
                HAVING (ISNULL(SUM(CASE WHEN rp.Tipo = 'DEVENGO' THEN pd.Monto ELSE 0 END), 0) - 
                        ISNULL(SUM(CASE WHEN rp.Tipo = 'DEDUCCION' THEN pd.Monto ELSE 0 END), 0)) > 0
                ORDER BY Colaborador
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':cod_planilla' => $codPlanilla]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                http_response_code(400);
                echo "Error: No hay transacciones netas a transferir en esta planilla.";
                exit;
            }

            // Crear la referencia de la planilla
            $referencia = "Planilla " . $planillaInfo['TipoPlanilla'] . " al " . date('d-m-Y', strtotime($planillaInfo['FechaFin']));
            if (function_exists('iconv')) {
                $referencia = @iconv('UTF-8', 'ASCII//TRANSLIT', $referencia);
            }
            $referencia = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $referencia);
            $referencia = substr($referencia, 0, 40);

            $output = "";
            $filename = "Planilla_" . $banco . "_ID" . $codPlanilla;

            switch ($banco) {
                case 'BAC':
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    $output .= "CuentaDestino,Monto,Identificacion,Nombre,Referencia\r\n";
                    foreach ($data as $r) {
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($r['Neto'], 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                case 'BNCR':
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
                    foreach ($data as $r) {
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s\t%s\t%s\t%s\t%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($r['Neto'], 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                case 'BCR':
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
                    foreach ($data as $r) {
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($r['Neto'], 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                default:
                    http_response_code(400);
                    echo "Error: Formato de banco no soportado.";
                    exit;
            }

            // Guardar una copia directamente en el Escritorio local si es localhost
            $isLocal = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['SERVER_NAME'] === 'localhost');
            if ($isLocal) {
                // En WampServer (servicio de Windows), Apache suele correr bajo el usuario SYSTEM.
                // Buscaremos las carpetas de usuario reales en C:\Users para encontrar el escritorio físico.
                $possibleDesktops = [
                    'C:\Users\Usuario\Desktop',
                    'C:\Users\\' . getenv('USERNAME') . '\Desktop'
                ];
                
                $envProfile = getenv('USERPROFILE');
                if ($envProfile && strpos(strtolower($envProfile), 'systemprofile') === false) {
                    $possibleDesktops[] = $envProfile . DIRECTORY_SEPARATOR . 'Desktop';
                }
                
                if (is_dir('C:\Users')) {
                    $dirs = glob('C:\Users\*', GLOB_ONLYDIR);
                    if ($dirs) {
                        foreach ($dirs as $d) {
                            $base = basename($d);
                            if (!in_array(strtolower($base), ['.net v4.5', '.net v4.5 classic', 'defaultapppool', 'openpgsvc', 'all users', 'default', 'default user', 'publico', 'public', 'systemprofile'])) {
                                $possibleDesktops[] = $d . DIRECTORY_SEPARATOR . 'Desktop';
                            }
                        }
                    }
                }
                
                $desktopPath = '';
                foreach ($possibleDesktops as $path) {
                    if (is_dir($path)) {
                        $desktopPath = $path;
                        break;
                    }
                }

                if (!empty($desktopPath) && is_dir($desktopPath)) {
                    $ext = ($banco === 'BAC') ? '.csv' : '.txt';
                    $desktopFile = $desktopPath . DIRECTORY_SEPARATOR . $filename . $ext;
                    @file_put_contents($desktopFile, $output);
                }
            }

            // Registrar en bitácora
            $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (?, 'EXPORTACION BANCARIA', ?)")
                ->execute([$_SESSION['username'], "Exportacion bancaria planilla ID: {$codPlanilla} para banco {$banco}"]);

            echo $output;
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error de base de datos: " . $e->getMessage();
            exit;
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no reconocida."]);
        break;
}
