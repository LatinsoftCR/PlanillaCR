<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * REPORTE DE COMPROBANTES DE PAGO DE AGUINALDO (IMPRIMIR_AGUINALDOS.PHP)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/conexion.php';

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$codEmpleado = isset($_GET['emp']) ? intval($_GET['emp']) : 0;
$includeOpen = isset($_GET['include_open']) ? intval($_GET['include_open']) : 0;

if ($year === 0) {
    die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error: Año de aguinaldo no proporcionado.</div>");
}

// Función auxiliar para obtener o crear el consecutivo único del comprobante
function getOrCreateConsecutivo($pdo, $tipo, $codReferencia, $codEmpleado, $usuario) {
    $stmt = $pdo->prepare("SELECT ConsecutivoStr FROM ConsecutivoComprobante WHERE Tipo = ? AND CodReferencia = ? AND CodEmpleado = ?");
    $stmt->execute([$tipo, $codReferencia, $codEmpleado]);
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
        $stmtIns->execute([$tipo, $codReferencia, $codEmpleado, $consecutivoStr, $usuario]);

        $pdo->commit();
        return $consecutivoStr;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 'C-' . str_pad($codEmpleado, 5, '0', STR_PAD_LEFT);
    }
}

try {
    // 1. Obtener la lista de colaboradores y desgloses del periodo
    $fechaInicio = ($year - 1) . '-12-01';
    $fechaFin = $year . '-11-30';

    $estados = ['PROCESADA', 'APLICADA'];
    if ($includeOpen === 1) {
        $estados[] = 'ABIERTA';
    }

    $statePlaceholders = [];
    foreach ($estados as $k => $state) {
        $statePlaceholders[] = ":state_" . $k;
    }
    $stateSqlStr = implode(', ', $statePlaceholders);

    $sqlEmp = "
        SELECT DISTINCT
            e.CodEmpleado,
            e.Identificacion,
            e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
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
        FROM Empleado e
        INNER JOIN PlanillaDetalle pd ON e.CodEmpleado = pd.CodEmpleado
        INNER JOIN Planilla p ON pd.CodPlanilla = p.CodPlanilla
        LEFT JOIN Empresa emp ON e.CodEmpresa = emp.CodEmpresa
        LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
        LEFT JOIN Puesto pu ON e.CodPuesto = pu.CodPuesto
        WHERE p.Estado IN ($stateSqlStr)
          AND p.FechaPago BETWEEN :fecha_inicio AND :fecha_fin
    ";

    if ($codEmpleado > 0) {
        $sqlEmp .= " AND e.CodEmpleado = :codEmpleado";
    }

    $sqlEmp .= " ORDER BY Colaborador";

    $stmtEmp = $pdo->prepare($sqlEmp);
    foreach ($estados as $k => $state) {
        $stmtEmp->bindValue(':state_' . $k, $state);
    }
    $stmtEmp->bindValue(':fecha_inicio', $fechaInicio);
    $stmtEmp->bindValue(':fecha_fin', $fechaFin);
    if ($codEmpleado > 0) {
        $stmtEmp->bindValue(':codEmpleado', $codEmpleado);
    }
    $stmtEmp->execute();
    $colaboradores = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($colaboradores)) {
        die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error: No se encontraron acumulados de aguinaldo en el periodo especificado.</div>");
    }

    // 2. Traer el desglose de meses de todos los empleados
    $sqlData = "
        SELECT 
            pd.CodEmpleado,
            MONTH(p.FechaPago) AS MesPago,
            SUM(pd.Monto) AS MontoMes
        FROM PlanillaDetalle pd
        INNER JOIN Planilla p ON pd.CodPlanilla = p.CodPlanilla
        INNER JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
        WHERE rp.Tipo = 'DEVENGO'
          AND rp.AfectaAguinaldo = 1
          AND p.Estado IN ($stateSqlStr)
          AND p.FechaPago BETWEEN :fecha_inicio AND :fecha_fin
        GROUP BY pd.CodEmpleado, MONTH(p.FechaPago)
    ";

    $stmtData = $pdo->prepare($sqlData);
    foreach ($estados as $k => $state) {
        $stmtData->bindValue(':state_' . $k, $state);
    }
    $stmtData->bindValue(':fecha_inicio', $fechaInicio);
    $stmtData->bindValue(':fecha_fin', $fechaFin);
    $stmtData->execute();
    $desglosesRaw = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    $desgloses = [];
    foreach ($desglosesRaw as $dr) {
        $empId = $dr['CodEmpleado'];
        $mes = intval($dr['MesPago']);
        $desgloses[$empId][$mes] = floatval($dr['MontoMes']);
    }

} catch (PDOException $e) {
    die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error de base de datos: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobantes de Aguinaldo - PlanillaCR</title>
    <!-- Google Fonts Outfit & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS Reset & General */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Barra de Herramientas superior */
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
            display: flex;
            align-items: center;
            gap: 8px;
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
            transition: background-color 0.15s ease;
        }
        .btn-print:hover {
            background-color: #004085;
        }

        /* Contenedor principal de boletas */
        .boletas-wrapper {
            width: 100%;
            max-width: 9.33in;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Contenedor individual de la Boleta (Ajustado para calce perfecto de 3 por página) */
        .boleta-container {
            width: 9.33in;
            height: 3.45in; /* Calce milimétrico para 3 boletas por hoja de 11 pulgadas */
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

        /* Cabecera de la boleta */
        .boleta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        /* Logos de Bahía Limón / Bahía Puntarenas */
        .logo-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .circular-logo {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            color: white;
            font-family: 'Outfit', sans-serif;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo-green {
            background: linear-gradient(135deg, #10b981, #047857);
        }
        .logo-orange {
            background: linear-gradient(135deg, #f97316, #c2410c);
        }
        .logo-cursive {
            font-size: 8px;
            font-style: italic;
            line-height: 1;
            margin-bottom: -2px;
        }
        .logo-bold {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: -0.2px;
            line-height: 1;
        }
        .logo-fm {
            font-size: 6px;
            font-weight: 500;
            opacity: 0.9;
            line-height: 1;
        }
        .header-slogan {
            font-size: 8px;
            font-weight: 600;
            color: #6b7280;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            align-self: center;
            margin-left: 2px;
        }

        /* Títulos */
        .header-title-box {
            text-align: center;
        }
        .header-title-main {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-title-period {
            font-size: 10.5px;
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

        /* Info Grid del Colaborador */
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
        .info-cell {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .info-label {
            font-size: 8px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .info-value {
            font-size: 11px;
            font-weight: 500;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .font-mono {
            font-family: monospace;
            font-weight: 600;
        }

        /* Tabla de Desglose de Meses */
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
        .rubros-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }

        /* Footer de la Boleta */
        .boleta-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 4px;
        }
        .signature-line {
            font-size: 9px;
            color: #4b5563;
            font-weight: 500;
        }
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

        /* Footer Corporativo */
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
        .corporate-logo {
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
            color: #6b7280;
        }
        .corporate-info {
            text-align: right;
            max-width: 70%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Estilos de Impresión */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
                width: 9.33in;
            }
            .toolbar {
                display: none !important;
            }
            .boleta-container {
                border-bottom: 1px dashed #9ca3af;
                box-shadow: none !important;
            }
            .page-break-delimiter {
                page-break-after: always;
                break-after: page;
            }
            @page {
                size: 9.33in 11in;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Herramientas superior (Solo pantalla) -->
    <div class="toolbar">
        <div>
            <h1 class="toolbar-title"><i class="fa-solid fa-print me-2 text-primary"></i>Comprobantes de Aguinaldo Continuo</h1>
            <p style="font-size: 11.5px; color: #6b7280; margin-top: 2px;">Visualización y calce de 3 comprobantes por hoja de 9.33" x 11" (Periodo <?php echo ($year - 1) . '-' . $year; ?>)</p>
        </div>
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Mandar a Impresora / Guardar PDF</span>
        </button>
    </div>

    <!-- Contenedor de Boletas -->
    <div class="boletas-wrapper">
        <?php 
        $counter = 0;
        $monthsNames = [
            12 => 'Diciembre',
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre'
        ];
        $monthsOrder = [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

        foreach ($colaboradores as $c) {
            $counter++;
            $empId = $c['CodEmpleado'];

            // Obtener el desglose particular de este colaborador
            $breakdown = isset($desgloses[$empId]) ? $desgloses[$empId] : [];
            $totalAcumulado = 0.00;
            foreach ($monthsOrder as $m) {
                $totalAcumulado += isset($breakdown[$m]) ? $breakdown[$m] : 0.00;
            }
            $montoAguinaldo = round($totalAcumulado / 12, 2);

            // Generar el consecutivo C-xxxxx único para esta boleta de Aguinaldo
            $consecutivo = getOrCreateConsecutivo($pdo, 'AGUINALDO', $year, $empId, $_SESSION['username']);
            ?>

            <div class="boleta-container">
                
                <!-- Cabecera -->
                <div class="boleta-header">
                    <div class="logo-group">
                        <?php if (!empty($c['EmpresaLogo'])): ?>
                            <img src="<?php echo htmlspecialchars($c['EmpresaLogo']); ?>" alt="Logo Empresa" style="max-height: 40px; max-width: 140px; object-fit: contain; border-radius: 4px;">
                        <?php else: ?>
                            <!-- Círculos Bahía -->
                            <div class="circular-logo logo-green">
                                <span class="logo-cursive">Bahia</span>
                                <span class="logo-bold">Limón</span>
                                <span class="logo-fm">107.9 FM</span>
                            </div>
                            <div class="circular-logo logo-orange">
                                <span class="logo-cursive">Bahia</span>
                                <span class="logo-bold">Puntarenas</span>
                                <span class="logo-fm">107.9 FM</span>
                            </div>
                            <div class="header-slogan">
                                De Puerto a Puerto
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Título con leyenda AGUINALDO -->
                    <div class="header-title-box">
                        <h2 class="header-title-main">Comprobante de Aguinaldo</h2>
                        <span class="header-title-period">Periodo Fiscal: Diciembre <?php echo ($year - 1); ?> - Noviembre <?php echo $year; ?></span>
                    </div>

                    <!-- Hora y Fecha -->
                    <div class="header-meta">
                        <strong>Hora:</strong> <?php echo date('h:i:s A'); ?><br>
                        <strong>Fecha Pago:</strong> <?php echo date('30/11/' . $year); ?>
                    </div>
                </div>

                <!-- Info Grid -->
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
                        <span class="info-value"><?php echo htmlspecialchars($c['Departamento']); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Identificación:</span>
                        <span class="info-value font-mono"><?php echo htmlspecialchars($c['Identificacion']); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Puesto:</span>
                        <span class="info-value"><?php echo htmlspecialchars($c['Puesto']); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Concepto:</span>
                        <span class="info-value text-success fw-bold">AGUINALDO ANUAL</span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Departamento:</span>
                        <span class="info-value"><?php echo htmlspecialchars($c['Departamento']); ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="info-label">Banco:</span>
                        <span class="info-value" style="font-size: 8px;"><?php echo htmlspecialchars($c['Banco']) . ' - ' . htmlspecialchars($c['CuentaIBAN']); ?></span>
                    </div>
                </div>

                <!-- Cuadrícula compacta de 12 meses (2 meses por fila) -->
                <table class="rubros-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Mes</th>
                            <th class="text-right" style="width: 25%;">Salario Afecto</th>
                            <th style="width: 25%;">Mes</th>
                            <th class="text-right" style="width: 25%;">Salario Afecto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        for ($i = 0; $i < 6; $i++) {
                            $m1 = $monthsOrder[$i];
                            $m2 = $monthsOrder[$i + 6];
                            $val1 = isset($breakdown[$m1]) ? $breakdown[$m1] : 0.00;
                            $val2 = isset($breakdown[$m2]) ? $breakdown[$m2] : 0.00;
                            ?>
                            <tr class="rubros-content-row">
                                <td><?php echo $monthsNames[$m1]; ?></td>
                                <td class="text-right font-mono">₡<?php echo number_format($val1, 2, ',', '.'); ?></td>
                                <td><?php echo $monthsNames[$m2]; ?></td>
                                <td class="text-right font-mono">₡<?php echo number_format($val2, 2, ',', '.'); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <!-- Footer (Firma y Neto) -->
                <div class="boleta-footer">
                    <div class="signature-line">
                        RECIBIDO CONFORME: __________________________________________________
                    </div>
                    <div class="net-pay-box">
                        <span>NETO DE AGUINALDO:</span>
                        <span style="color: #15803d; font-size: 13px;">₡<?php echo number_format($montoAguinaldo, 2, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Footer de la Empresa -->
                <div class="corporate-footer">
                    <div class="corporate-logo">
                        <i class="fa-solid fa-gift text-primary"></i>
                        <span><?php echo htmlspecialchars(!empty($c['EmpresaComercial']) ? $c['EmpresaComercial'] : (!empty($c['EmpresaNombre']) ? $c['EmpresaNombre'] : 'Bahía Radio 107.9 FM')); ?></span>
                    </div>
                    <div class="corporate-info">
                        <?php 
                        $tel = !empty($c['EmpresaTelefono']) ? $c['EmpresaTelefono'] : '2221-9723';
                        $correo = !empty($c['EmpresaCorreo']) ? $c['EmpresaCorreo'] : 'info@radiobahiapuerto.com';
                        $dir = !empty($c['EmpresaDireccion']) ? $c['EmpresaDireccion'] : 'San José, Costa Rica';
                        $ced = !empty($c['EmpresaCedula']) ? ' * Céd. Jurídica: ' . $c['EmpresaCedula'] : '';
                        echo "OFICINAS: " . htmlspecialchars($dir) . " * Teléfono: " . htmlspecialchars($tel) . " * Correo: " . htmlspecialchars($correo) . htmlspecialchars($ced);
                        ?>
                    </div>
                </div>

            </div>

            <!-- Salto de Página cada 3 boletas -->
            <?php if ($counter % 3 === 0 && $counter < count($colaboradores)) { ?>
                <div class="page-break-delimiter"></div>
            <?php } ?>

        <?php } ?>
    </div>

</body>
</html>
