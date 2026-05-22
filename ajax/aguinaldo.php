<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONTROLLER AJAX - MÓDULO DE AGUINALDOS (L.C.R.)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {
    // ==========================================================================
    // 1. CALCULAR PROYECCIÓN / CÁLCULO DE AGUINALDOS
    // ==========================================================================
    case 'calculate':
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $includeOpen = isset($_POST['include_open']) ? intval($_POST['include_open']) : 0;

        if ($year < 2000 || $year > 2100) {
            echo json_encode(["success" => false, "message" => "Año especificado fuera de rango."]);
            exit;
        }

        // Definir el rango del periodo de aguinaldo según la ley de Costa Rica:
        // Del 1 de Diciembre del año anterior al 30 de Noviembre del año actual.
        $fechaInicio = ($year - 1) . '-12-01';
        $fechaFin = $year . '-11-30';

        // Definir estados de planilla a incluir
        $estados = ['PROCESADA', 'APLICADA'];
        if ($includeOpen === 1) {
            $estados[] = 'ABIERTA';
        }

        try {
            // Construir el placeholder de estados de forma dinámica e inofensiva
            $statePlaceholders = [];
            foreach ($estados as $k => $state) {
                $statePlaceholders[] = ":state_" . $k;
            }
            $stateSqlStr = implode(', ', $statePlaceholders);

            // Query para traer los montos agrupados por empleado y mes de pago en el periodo
            $sql = "
                SELECT 
                    e.CodEmpleado,
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    MONTH(p.FechaPago) AS MesPago,
                    SUM(pd.Monto) AS MontoMes
                FROM Empleado e
                INNER JOIN PlanillaDetalle pd ON e.CodEmpleado = pd.CodEmpleado
                INNER JOIN Planilla p ON pd.CodPlanilla = p.CodPlanilla
                INNER JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
                WHERE rp.Tipo = 'DEVENGO'
                  AND rp.AfectaAguinaldo = 1
                  AND p.Estado IN ($stateSqlStr)
                  AND p.FechaPago BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY e.CodEmpleado, e.Identificacion, e.Nombre, e.Apellido1, e.Apellido2, MONTH(p.FechaPago)
                ORDER BY e.CodEmpleado, MesPago
            ";

            $stmt = $pdo->prepare($sql);
            
            // Vincular parámetros de estado
            foreach ($estados as $k => $state) {
                $stmt->bindValue(':state_' . $k, $state);
            }
            $stmt->bindValue(':fecha_inicio', $fechaInicio);
            $stmt->bindValue(':fecha_fin', $fechaFin);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mapear los meses de la planilla según el ciclo costarricense (de Diciembre a Noviembre)
            $monthsOrder = [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

            $employeesData = [];
            foreach ($results as $r) {
                $id = $r['CodEmpleado'];
                if (!isset($employeesData[$id])) {
                    // Inicializar el desglose de los 12 meses en cero
                    $breakdown = [];
                    foreach ($monthsOrder as $m) {
                        $breakdown[$m] = 0.00;
                    }

                    $employeesData[$id] = [
                        'CodEmpleado' => $id,
                        'Identificacion' => $r['Identificacion'],
                        'Colaborador' => $r['Colaborador'],
                        'Breakdown' => $breakdown,
                        'TotalAcumulado' => 0.00,
                        'MontoAguinaldo' => 0.00
                    ];
                }

                $mes = intval($r['MesPago']);
                $monto = floatval($r['MontoMes']);
                
                if (isset($employeesData[$id]['Breakdown'][$mes])) {
                    $employeesData[$id]['Breakdown'][$mes] += $monto;
                }
                $employeesData[$id]['TotalAcumulado'] += $monto;
            }

            // Calcular el aguinaldo final: acumulado bruto dividido por 12
            $finalList = [];
            foreach ($employeesData as $id => $emp) {
                $emp['MontoAguinaldo'] = round($emp['TotalAcumulado'] / 12, 2);
                
                // Formatear los meses para retornar un objeto limpio al datatable
                $formattedBreakdown = [];
                foreach ($monthsOrder as $m) {
                    $formattedBreakdown[$m] = number_format($emp['Breakdown'][$m], 2, '.', '');
                }
                $emp['Breakdown'] = $formattedBreakdown;
                $finalList[] = $emp;
            }

            echo json_encode([
                "success" => true,
                "periodo" => ($year - 1) . "-" . $year,
                "data" => $finalList
            ]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 2. GUARDAR / CERRAR AGUINALDOS EN EL HISTÓRICO
    // ==========================================================================
    case 'save':
        $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
        $payload = isset($_POST['payload']) ? json_decode($_POST['payload'], true) : null;

        if ($year < 2000 || $year > 2100 || empty($payload)) {
            echo json_encode(["success" => false, "message" => "Parámetros inválidos para guardar."]);
            exit;
        }

        $periodo = ($year - 1) . "-" . $year;

        try {
            $pdo->beginTransaction();

            // 1. Eliminar cálculos previos para este periodo para evitar duplicación
            $stmtDel = $pdo->prepare("DELETE FROM Aguinaldo WHERE Periodo = ?");
            $stmtDel->execute([$periodo]);

            // 2. Insertar los nuevos acumulados
            $stmtIns = $pdo->prepare("
                INSERT INTO Aguinaldo (Periodo, CodEmpleado, Acumulado, Estado, UsuarioCreacion)
                VALUES (?, ?, ?, 'PROCESADO', ?)
            ");

            $insertedCount = 0;
            foreach ($payload as $emp) {
                $codEmpleado = intval($emp['CodEmpleado']);
                $montoAguinaldo = floatval($emp['MontoAguinaldo']);

                if ($codEmpleado > 0 && $montoAguinaldo >= 0) {
                    $stmtIns->execute([
                        $periodo,
                        $codEmpleado,
                        $montoAguinaldo,
                        $_SESSION['username']
                    ]);
                    $insertedCount++;
                }
            }

            // Registrar en la Bitácora del Sistema
            $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (?, 'CÁLCULO AGUINALDO', ?)")
                ->execute([$_SESSION['username'], "Guardado histórico de aguinaldos periodo: {$periodo} con {$insertedCount} registros."]);

            $pdo->commit();

            echo json_encode([
                "success" => true,
                "message" => "¡Aguinaldos del periodo {$periodo} guardados exitosamente!",
                "count" => $insertedCount
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(["success" => false, "message" => "Error al guardar históricos: " . $e->getMessage()]);
        }
        break;

    // ==========================================================================
    // 3. EXPORTAR FORMATO BANCARIO (BAC, BNCR, BCR) PARA AGUINALDO
    // ==========================================================================
    case 'export_bank':
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $includeOpen = isset($_GET['include_open']) ? intval($_GET['include_open']) : 0;
        $banco = isset($_GET['banco']) ? trim($_GET['banco']) : 'BAC';
        $referencia = isset($_GET['referencia']) ? trim($_GET['referencia']) : 'AGUINALDO ' . $year;

        if ($year < 2000 || $year > 2100) {
            http_response_code(400);
            echo "Error: Año fuera de rango.";
            exit;
        }

        // 1. Calcular Aguinaldos para el periodo
        $fechaInicio = ($year - 1) . '-12-01';
        $fechaFin = $year . '-11-30';

        $estados = ['PROCESADA', 'APLICADA'];
        if ($includeOpen === 1) {
            $estados[] = 'ABIERTA';
        }

        try {
            $statePlaceholders = [];
            foreach ($estados as $k => $state) {
                $statePlaceholders[] = ":state_" . $k;
            }
            $stateSqlStr = implode(', ', $statePlaceholders);

            $sql = "
                SELECT 
                    e.CodEmpleado,
                    e.Identificacion,
                    e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
                    ISNULL(e.CuentaIBAN, 'NO ESPECIFICADA') AS CuentaIBAN,
                    MONTH(p.FechaPago) AS MesPago,
                    SUM(pd.Monto) AS MontoMes
                FROM Empleado e
                INNER JOIN PlanillaDetalle pd ON e.CodEmpleado = pd.CodEmpleado
                INNER JOIN Planilla p ON pd.CodPlanilla = p.CodPlanilla
                INNER JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
                WHERE rp.Tipo = 'DEVENGO'
                  AND rp.AfectaAguinaldo = 1
                  AND p.Estado IN ($stateSqlStr)
                  AND p.FechaPago BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY e.CodEmpleado, e.Identificacion, e.Nombre, e.Apellido1, e.Apellido2, e.CuentaIBAN, MONTH(p.FechaPago)
                ORDER BY e.CodEmpleado, MesPago
            ";

            $stmt = $pdo->prepare($sql);
            foreach ($estados as $k => $state) {
                $stmt->bindValue(':state_' . $k, $state);
            }
            $stmt->bindValue(':fecha_inicio', $fechaInicio);
            $stmt->bindValue(':fecha_fin', $fechaFin);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $employeesData = [];
            foreach ($results as $r) {
                $id = $r['CodEmpleado'];
                if (!isset($employeesData[$id])) {
                    $employeesData[$id] = [
                        'CodEmpleado' => $id,
                        'Identificacion' => $r['Identificacion'],
                        'Colaborador' => $r['Colaborador'],
                        'CuentaIBAN' => $r['CuentaIBAN'],
                        'TotalAcumulado' => 0.00
                    ];
                }
                $employeesData[$id]['TotalAcumulado'] += floatval($r['MontoMes']);
            }

            $finalList = [];
            foreach ($employeesData as $emp) {
                $montoAguinaldo = round($emp['TotalAcumulado'] / 12, 2);
                if ($montoAguinaldo > 0) {
                    $emp['MontoAguinaldo'] = $montoAguinaldo;
                    $finalList[] = $emp;
                }
            }

            if (empty($finalList)) {
                http_response_code(404);
                echo "Error: No hay aguinaldos calculados para exportar en este periodo.";
                exit;
            }

            // Sanitizar referencia
            $referencia = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $referencia);
            $referencia = substr($referencia, 0, 40);

            $output = "";
            $filename = "Aguinaldos_" . $banco . "_" . $year;

            switch ($banco) {
                case 'BAC':
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    $output .= "CuentaDestino,Monto,Identificacion,Nombre,Referencia\r\n";
                    foreach ($finalList as $r) {
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($r['MontoAguinaldo'], 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                case 'BNCR':
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
                    foreach ($finalList as $r) {
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s\t%s\t%s\t%s\t%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($r['MontoAguinaldo'], 2, '.', ''),
                            $r['Identificacion'],
                            substr($nombreLimpio, 0, 40),
                            $referencia
                        );
                    }
                    break;

                case 'BCR':
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
                    foreach ($finalList as $r) {
                        $nombreLimpio = $r['Colaborador'];
                        if (function_exists('iconv')) {
                            $nombreLimpio = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombreLimpio);
                        }
                        $nombreLimpio = preg_replace('/[^a-zA-Z\s]/', '', $nombreLimpio);
                        $output .= sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            str_replace([' ', '-'], '', $r['CuentaIBAN']),
                            number_format($r['MontoAguinaldo'], 2, '.', ''),
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

            // Guardar una copia directamente en el Escritorio local si es localhost
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

            // Registrar Bitácora
            $pdo->prepare("INSERT INTO BitacoraPlanilla (Usuario, Proceso, Detalle) VALUES (?, 'EXPORTAR AGUINALDO BANCO', ?)")
                ->execute([$_SESSION['username'], "Exportado archivo bancario {$banco} para Aguinaldos del periodo {$year}."]);

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
