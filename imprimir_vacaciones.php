<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * COMPROBANTES DE PAGO DE VACACIONES (IMPRIMIR_VACACIONES.PHP)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/conexion.php';

$idMovimiento = isset($_GET['id']) ? intval($_GET['id']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$codEmpleado = isset($_GET['emp']) ? intval($_GET['emp']) : 0;

function getOrCreateConsecutivoVac($pdo, $codMovimiento, $codEmpleado, $usuario) {
    $tipo = 'VACACIONES';
    $stmt = $pdo->prepare("SELECT ConsecutivoStr FROM ConsecutivoComprobante WHERE Tipo = ? AND CodReferencia = ? AND CodEmpleado = ?");
    $stmt->execute([$tipo, $codMovimiento, $codEmpleado]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row['ConsecutivoStr'];
    }

    try {
        $pdo->beginTransaction();
        $stmtMax = $pdo->prepare("SELECT ISNULL(MAX(Codigo), 0) + 1 AS NextVal FROM ConsecutivoComprobante");
        $stmtMax->execute();
        $resMax = $stmtMax->fetch(PDO::FETCH_ASSOC);
        $nextVal = $resMax ? intval($resMax['NextVal']) : 1;
        $consecutivoStr = 'C-' . str_pad($nextVal, 5, '0', STR_PAD_LEFT);
        $stmtIns = $pdo->prepare("INSERT INTO ConsecutivoComprobante (Tipo, CodReferencia, CodEmpleado, ConsecutivoStr, UsuarioCreacion) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->execute([$tipo, $codMovimiento, $codEmpleado, $consecutivoStr, $usuario]);
        $pdo->commit();
        return $consecutivoStr;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 'C-' . str_pad($codMovimiento, 5, '0', STR_PAD_LEFT);
    }
}

function calcularSalarioDiarioVac($salarioBase, $tipoSalario, $horasDia = 8.0) {
    $salarioBase = floatval($salarioBase);
    $tipoSalario = strtoupper(trim($tipoSalario));
    $horasDia = floatval($horasDia) > 0 ? floatval($horasDia) : 8.0;
    if ($tipoSalario === 'SEMANAL') {
        return $salarioBase / 6;
    }
    if ($tipoSalario === 'HORA') {
        return $salarioBase * $horasDia;
    }
    return $salarioBase / 30;
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
            ISNULL(e.CuentaIBAN, 'NO ESPECIFICADA') AS CuentaIBAN,
            emp.Nombre AS EmpresaNombre,
            emp.NombreComercial AS EmpresaComercial,
            emp.Telefono AS EmpresaTelefono,
            emp.Correo AS EmpresaCorreo,
            emp.Direccion AS EmpresaDireccion,
            emp.Logo AS EmpresaLogo,
            emp.CedulaJuridica AS EmpresaCedula
        FROM VacacionesDetalle vd
        INNER JOIN Empleado e ON vd.CodEmpleado = e.CodEmpleado
        LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
        LEFT JOIN Empresa emp ON e.CodEmpresa = emp.CodEmpresa
        LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
        LEFT JOIN Puesto pu ON e.CodPuesto = pu.CodPuesto
        WHERE vd.TipoMovimiento = 'PAGO'
          AND vd.Estado = 'ACTIVO'
          AND vd.MontoPagado > 0
    ";

    $params = [];
    if ($idMovimiento > 0) {
        $sql .= " AND vd.Codigo = :id";
        $params['id'] = $idMovimiento;
    } else {
        $sql .= " AND YEAR(vd.FechaCreacion) = :year";
        $params['year'] = $year;
        if ($codEmpleado > 0) {
            $sql .= " AND vd.CodEmpleado = :emp";
            $params['emp'] = $codEmpleado;
        }
    }

    $sql .= " ORDER BY Colaborador, vd.FechaCreacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pagos)) {
        die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error: No se encontraron comprobantes de pago de vacaciones para imprimir.</div>");
    }

} catch (PDOException $e) {
    die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>");
}

$periodoLabel = $idMovimiento > 0
    ? 'Movimiento #' . str_pad($idMovimiento, 5, '0', STR_PAD_LEFT)
    : 'Año ' . $year;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobantes de Pago de Vacaciones - PlanillaCR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .toolbar {
            width: 100%;
            max-width: 9.33in;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .toolbar-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
        }
        .btn-print {
            background-color: #0056b3;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover { background-color: #004085; }
        .boletas-wrapper {
            width: 100%;
            max-width: 9.33in;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .boleta-container {
            width: 9.33in;
            height: 3.45in;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .boleta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .logo-group { display: flex; align-items: center; gap: 10px; }
        .header-title-box { text-align: center; }
        .header-title-main {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .header-title-period {
            font-size: 10px;
            color: #4b5563;
            font-weight: 500;
            margin-top: 1px;
            display: block;
        }
        .header-meta {
            font-size: 10px;
            color: #4b5563;
            text-align: right;
            line-height: 1.3;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 8px;
        }
        .info-cell { display: flex; flex-direction: column; gap: 1px; }
        .info-label {
            font-size: 8px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 11px;
            font-weight: 500;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .font-mono { font-family: monospace; font-weight: 600; }
        .rubros-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .rubros-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 4px 10px;
            border-bottom: 1.5px solid #cbd5e1;
        }
        .rubros-table td {
            font-size: 10.5px;
            color: #334155;
            padding: 3px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .rubros-table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .boleta-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 4px;
        }
        .signature-line { font-size: 9px; color: #4b5563; font-weight: 500; }
        .net-pay-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 11px;
            color: #15803d;
        }
        .corporate-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 4px;
            margin-top: 4px;
        }
        @media print {
            body { background-color: #ffffff; padding: 0; margin: 0; width: 9.33in; }
            .toolbar { display: none !important; }
            .boleta-container { border-bottom: 1px dashed #9ca3af; box-shadow: none !important; }
            .page-break-delimiter { page-break-after: always; break-after: page; }
            @page { size: 9.33in 11in; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div>
            <h1 class="toolbar-title"><i class="fa-solid fa-print me-2 text-primary"></i>Comprobantes de Pago de Vacaciones</h1>
            <p style="font-size: 11.5px; color: #6b7280; margin-top: 2px;">Visualización y calce de 3 comprobantes por hoja de 9.33" x 11" (<?php echo htmlspecialchars($periodoLabel); ?>)</p>
        </div>
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Mandar a Impresora / Guardar PDF</span>
        </button>
    </div>

    <div class="boletas-wrapper">
        <?php
        $counter = 0;
        foreach ($pagos as $c) {
            $counter++;
            $salarioDiario = calcularSalarioDiarioVac($c['SalarioBase'], $c['TipoSalario'], $c['HorasDia']);
            $dias = floatval($c['DiasSolicitados']);
            $montoNeto = floatval($c['MontoPagado']);
            $montoCalc = round($dias * $salarioDiario, 2);
            // Corregir registros guardados con parseo incorrecto (ej. 208 en vez de 208.000,00)
            if ($montoCalc > 100 && $montoNeto > 0 && $montoNeto < $montoCalc * 0.5) {
                $montoNeto = $montoCalc;
            }
            $fechaPago = date('d/m/Y', strtotime($c['FechaCreacion']));
            $consecutivo = getOrCreateConsecutivoVac($pdo, intval($c['CodMovimiento']), intval($c['CodEmpleado']), $_SESSION['username']);

            $formula = 'Base ÷ 30';
            if (strtoupper($c['TipoSalario']) === 'SEMANAL') {
                $formula = 'Base ÷ 6';
            } elseif (strtoupper($c['TipoSalario']) === 'HORA') {
                $formula = 'Hora × Jornada';
            }
            ?>
            <div class="boleta-container">
                <div class="boleta-header">
                    <div class="logo-group">
                        <?php if (!empty($c['EmpresaLogo'])): ?>
                            <img src="<?php echo htmlspecialchars($c['EmpresaLogo']); ?>" alt="Logo" style="max-height: 40px; max-width: 140px; object-fit: contain;">
                        <?php else: ?>
                            <i class="fa-solid fa-umbrella-beach" style="font-size: 28px; color: #2563eb;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="header-title-box">
                        <h2 class="header-title-main">Comprobante de Pago de Vacaciones</h2>
                        <span class="header-title-period">Compensación monetaria de días de vacaciones — <?php echo htmlspecialchars($periodoLabel); ?></span>
                    </div>
                    <div class="header-meta">
                        <strong>Hora:</strong> <?php echo date('h:i:s A'); ?><br>
                        <strong>Fecha Pago:</strong> <?php echo $fechaPago; ?>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-cell">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value" style="font-weight: 700;"><?php echo htmlspecialchars($c['Colaborador']); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Comprobante:</span>
                        <span class="info-value font-mono"><?php echo $consecutivo; ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">C. Costos:</span>
                        <span class="info-value"><?php echo htmlspecialchars($c['Departamento'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Identificación:</span>
                        <span class="info-value font-mono"><?php echo htmlspecialchars($c['Identificacion']); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Puesto:</span>
                        <span class="info-value"><?php echo htmlspecialchars($c['Puesto'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Concepto:</span>
                        <span class="info-value" style="color: #15803d; font-weight: 700;">PAGO VACACIONES</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Departamento:</span>
                        <span class="info-value"><?php echo htmlspecialchars($c['Departamento'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Banco:</span>
                        <span class="info-value" style="font-size: 8px;"><?php echo htmlspecialchars($c['Banco']) . ' - ' . htmlspecialchars($c['CuentaIBAN']); ?></span>
                    </div>
                </div>

                <table class="rubros-table">
                    <thead>
                        <tr>
                            <th style="width: 55%;">Concepto / Cálculo</th>
                            <th class="text-right" style="width: 22%;">Detalle</th>
                            <th class="text-right" style="width: 23%;">Monto (₡)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Salario Base (<?php echo htmlspecialchars($c['TipoSalario']); ?>)</td>
                            <td class="text-right font-mono">—</td>
                            <td class="text-right font-mono">₡<?php echo number_format(floatval($c['SalarioBase']), 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>Salario Diario (<?php echo $formula; ?>)</td>
                            <td class="text-right font-mono"><?php echo number_format($dias, 2); ?> días</td>
                            <td class="text-right font-mono">₡<?php echo number_format($salarioDiario, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Liquidación: Salario Diario × Días Compensados</strong></td>
                            <td class="text-right font-mono"><?php echo number_format($dias, 2); ?> × ₡<?php echo number_format($salarioDiario, 2, ',', '.'); ?></td>
                            <td class="text-right font-mono"><strong>₡<?php echo number_format($montoCalc, 2, ',', '.'); ?></strong></td>
                        </tr>
                        <?php if (abs($montoCalc - $montoNeto) > 0.01 && floatval($c['MontoPagado']) >= $montoCalc * 0.5): ?>
                        <tr>
                            <td>Ajuste / Monto autorizado</td>
                            <td class="text-right">—</td>
                            <td class="text-right font-mono">₡<?php echo number_format($montoNeto, 2, ',', '.'); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="boleta-footer">
                    <div class="signature-line">RECIBIDO CONFORME: __________________________________________________</div>
                    <div class="net-pay-box">
                        <span>NETO DE VACACIONES:</span>
                        <span style="font-size: 13px;">₡<?php echo number_format($montoNeto, 2, ',', '.'); ?></span>
                    </div>
                </div>

                <div class="corporate-footer">
                    <span><i class="fa-solid fa-umbrella-beach"></i> <?php echo htmlspecialchars(!empty($c['EmpresaComercial']) ? $c['EmpresaComercial'] : ($c['EmpresaNombre'] ?? 'PlanillaCR ERP')); ?></span>
                    <span><?php echo htmlspecialchars($c['Observaciones'] ?? ''); ?></span>
                </div>
            </div>

            <?php if ($counter % 3 === 0 && $counter < count($pagos)) { ?>
                <div class="page-break-delimiter"></div>
            <?php } ?>
        <?php } ?>
    </div>
</body>
</html>
