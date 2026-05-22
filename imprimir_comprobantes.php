<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * REPORTE DE COMPROBANTES DE PAGO (IMPRIMIR_COMPROBANTES.PHP)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/conexion.php';

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

$codPlanilla = isset($_GET['id']) ? intval($_GET['id']) : 0;
$codEmpleado = isset($_GET['emp']) ? intval($_GET['emp']) : 0;

if ($codPlanilla === 0) {
    die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error: Código de planilla no proporcionado.</div>");
}

try {
    // 1. Obtener datos generales de la planilla
    $stmtP = $pdo->prepare("
        SELECT p.CodPlanilla, tp.Descripcion AS TipoPlanilla, p.FechaInicio, p.FechaFin, p.FechaPago
        FROM Planilla p
        INNER JOIN TipoPlanilla tp ON p.CodTipoPlanilla = tp.CodTipoPlanilla
        WHERE p.CodPlanilla = ?
    ");
    $stmtP->execute([$codPlanilla]);
    $planilla = $stmtP->fetch(PDO::FETCH_ASSOC);

    if (!$planilla) {
        die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error: Planilla no encontrada.</div>");
    }

    // 2. Obtener lista de colaboradores a incluir
    $sqlEmp = "
        SELECT DISTINCT
            e.CodEmpleado,
            e.Identificacion,
            e.Nombre + ' ' + e.Apellido1 + ' ' + ISNULL(e.Apellido2, '') AS Colaborador,
            d.Descripcion AS Departamento,
            pu.Descripcion AS Puesto,
            h.Descripcion AS Horario,
            ISNULL(e.Banco, 'NO ESPECIFICADO') AS Banco,
            ISNULL(e.CuentaIBAN, 'NO ESPECIFICADA') AS CuentaIBAN,
            emp.Nombre AS EmpresaNombre,
            emp.NombreComercial AS EmpresaComercial,
            emp.Telefono AS EmpresaTelefono,
            emp.Correo AS EmpresaCorreo,
            emp.Direccion AS EmpresaDireccion,
            emp.Logo AS EmpresaLogo,
            emp.CedulaJuridica AS EmpresaCedula
        FROM PlanillaDetalle pd
        INNER JOIN Empleado e ON pd.CodEmpleado = e.CodEmpleado
        LEFT JOIN Empresa emp ON e.CodEmpresa = emp.CodEmpresa
        LEFT JOIN Departamento d ON e.CodDepartamento = d.CodDepartamento
        LEFT JOIN Puesto pu ON e.CodPuesto = pu.CodPuesto
        LEFT JOIN Horario h ON e.CodHorario = h.CodHorario
        WHERE pd.CodPlanilla = :codPlanilla
    ";

    if ($codEmpleado > 0) {
        $sqlEmp .= " AND e.CodEmpleado = :codEmpleado";
    }

    $sqlEmp .= " ORDER BY Colaborador";

    $stmtEmp = $pdo->prepare($sqlEmp);
    $paramsEmp = [':codPlanilla' => $codPlanilla];
    if ($codEmpleado > 0) {
        $paramsEmp[':codEmpleado'] = $codEmpleado;
    }
    $stmtEmp->execute($paramsEmp);
    $colaboradores = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($colaboradores)) {
        die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error: No se encontraron colaboradores calculados en esta planilla.</div>");
    }

} catch (PDOException $e) {
    die("<div style='padding: 20px; font-family: sans-serif; color: #ef4444;'>Error de base de datos: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobantes de Pago - PlanillaCR</title>
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Action Toolbar (Pantalla) */
        .toolbar {
            width: 9.33in;
            background: #ffffff;
            padding: 12px 24px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toolbar-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        /* Contenedor Principal de Boletas */
        .boletas-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0; /* Sin espacio entre tarjetas para calzar con el prepicado de forma continua */
        }

        /* Boleta de Pago Individual - Calzada a 3.5 pulgadas de alto */
        .boleta-container {
            width: 9.33in;
            height: 3.55in;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            padding: 10px 15px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        /* Cabecera de la Boleta */
        .boleta-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 48px;
            margin-bottom: 4px;
        }
        .logo-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        /* SVG Logos Estilizados tipo Bahía Radio */
        .circular-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo-green {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
        }
        .logo-orange {
            background: linear-gradient(135deg, #ff9800, #e65100);
        }
        .logo-cursive {
            font-size: 8px;
            font-style: italic;
            line-height: 1;
            font-weight: 300;
        }
        .logo-bold {
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1;
        }
        .logo-fm {
            font-size: 6px;
            font-weight: 500;
            line-height: 1;
        }
        .header-slogan {
            border: 1px solid #111827;
            padding: 2px 8px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-title-box {
            text-align: center;
        }
        .header-title-main {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
        }
        .header-title-period {
            font-size: 9.5px;
            color: #4b5563;
            font-weight: 500;
        }
        .header-meta {
            text-align: right;
            font-size: 8.5px;
            color: #374151;
            line-height: 1.3;
        }        /* Cuadrícula de Información del Colaborador */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid #374151;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 4px;
            background-color: #f9fafb;
        }
        .info-cell {
            padding: 3px 6px;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            font-size: 9px;
            line-height: 1.2;
        }
        .info-cell:nth-child(4n) {
            border-right: none;
        }
        .info-cell:nth-last-child(-n+4) {
            border-bottom: none;
        }
        .info-label {
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            font-size: 8px;
            display: inline-block;
            width: 90px;
        }
        .info-value {
            color: #374151;
            font-weight: 500;
        }
 
        /* Tabla de Desglose de Rubros (Devengos / Deducciones) */
        .rubros-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #374151;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 4px;
            font-size: 9px;
        }
        .rubros-table th {
            background-color: #f3f4f6;
            color: #111827;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            padding: 4px 6px;
            border-bottom: 1px solid #374151;
            border-right: 1px solid #d1d5db;
        }
        .rubros-table th:last-child {
            border-right: none;
        }
        .rubros-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
            vertical-align: middle;
            line-height: 1.1;
        }
        .rubros-table td:last-child {
            border-right: none;
        }
        .rubros-table tr:last-child td {
            border-bottom: none;
        }
        .col-left {
            border-right: 1px solid #374151 !important;
        }
        .acct-badge {
            font-size: 7.5px;
            color: #4b5563;
            background-color: #f3f4f6;
            padding: 1.5px 4px;
            border-radius: 3px;
            font-family: monospace;
            margin-right: 5px;
            font-weight: 600;
            display: inline-block;
        }
        .font-mono {
            font-family: monospace;
            font-size: 9.5px;
        }
        .text-right {
            text-align: right;
        }
        .rubros-content-row {
            height: 14px;
        }

        /* Totales y Firmas */
        .boleta-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2px 0;
            height: 28px;
        }
        .signature-line {
            font-size: 8.5px;
            font-weight: 600;
            color: #111827;
            width: 60%;
        }
        .net-pay-box {
            background-color: #f3f4f6;
            border: 1px solid #374151;
            padding: 4px 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 12px;
        }

        /* Leyenda de la Empresa del Grupo */
        .corporate-footer {
            border-top: 1px dashed #d1d5db;
            padding-top: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 24px;
        }
        .corporate-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 9.5px;
            font-weight: 700;
            color: #374151;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .corporate-info {
            font-size: 7.5px;
            color: #6b7280;
            font-weight: 500;
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
                border-bottom: 1px dashed #9ca3af; /* Línea prepicada para el corte */
                box-shadow: none !important;
            }
            /* Regla de Salto de Página cada 3 boletas */
            .page-break-delimiter {
                page-break-after: always;
                break-after: page;
            }
            /* Ocultar barra superior en impresión */
            @page {
                size: 9.33in 11in;
                margin: 0;
            }
        }

        /* Estilos Blanco y Negro */
        .bw-mode {
            background-color: #ffffff !important;
            color: #000000 !important;
        }
        .bw-mode .boletas-wrapper {
            background-color: #ffffff !important;
        }
        .bw-mode .boleta-container {
            background-color: #ffffff !important;
            color: #000000 !important;
            border-color: #000000 !important;
        }
        .bw-mode .circular-logo {
            background: #000000 !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }
        .bw-mode .info-grid {
            background-color: #ffffff !important;
            border-color: #000000 !important;
        }
        .bw-mode .info-cell {
            border-right-color: #000000 !important;
            border-bottom-color: #000000 !important;
        }
        .bw-mode .info-label {
            color: #000000 !important;
        }
        .bw-mode .info-value {
            color: #000000 !important;
        }
        .bw-mode .rubros-table {
            border-color: #000000 !important;
        }
        .bw-mode .rubros-table th {
            background-color: #ffffff !important;
            color: #000000 !important;
            border-bottom-color: #000000 !important;
            border-right-color: #000000 !important;
        }
        .bw-mode .rubros-table td {
            border-right-color: #000000 !important;
            border-bottom-color: #000000 !important;
        }
        .bw-mode .acct-badge {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }
        .bw-mode .net-pay-box {
            background-color: #ffffff !important;
            border: 1.5px solid #000000 !important;
            color: #000000 !important;
        }
        .bw-mode .corporate-logo, .bw-mode .corporate-info, .bw-mode .header-title-period, .bw-mode .header-title-main {
            color: #000000 !important;
        }
    </style>
</head>
<body>

    <!-- Barra de Herramientas superior (Solo pantalla) -->
    <div class="toolbar">
        <div>
            <h1 class="toolbar-title"><i class="fa-solid fa-print me-2 text-primary"></i>Generador de Boletas de Pago Continuo</h1>
            <p style="font-size: 11.5px; color: #6b7280; margin-top: 2px;">Visualización y calce de 3 boletas por hoja de 9.33" x 11"</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" id="btnToggleBW" style="background-color: #4b5563;">
                <i class="fa-solid fa-circle-half-stroke"></i>
                <span>Modo Blanco y Negro</span>
            </button>
            <button class="btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                <span>Mandar a Impresora / Guardar PDF</span>
            </button>
        </div>
    </div>

    <!-- Contenedor de Boletas -->
    <div class="boletas-wrapper">
        <?php 
        $counter = 0;
        foreach ($colaboradores as $c) {
            $counter++;
            $empId = $c['CodEmpleado'];

            // Obtener rubros de devengo y deducción de este empleado en esta planilla
            $stmtD = $pdo->prepare("
                SELECT 
                    pd.Cantidad,
                    pd.Monto,
                    rp.Codigo,
                    rp.Descripcion,
                    rp.Tipo,
                    ISNULL(rp.CuentaContable, 'N/A') AS CuentaContable
                FROM PlanillaDetalle pd
                INNER JOIN RubroPlanilla rp ON pd.CodRubro = rp.CodRubro
                WHERE pd.CodPlanilla = :codPlanilla AND pd.CodEmpleado = :empId AND pd.Monto > 0
                ORDER BY rp.Tipo DESC, rp.Prioridad ASC
            ");
            $stmtD->execute([':codPlanilla' => $codPlanilla, ':empId' => $empId]);
            $rubros = $stmtD->fetchAll(PDO::FETCH_ASSOC);

            // Separar rubros en devengos y deducciones
            $devengos = [];
            $deducciones = [];
            $totalDev = 0.0;
            $totalDed = 0.0;

            foreach ($rubros as $r) {
                if ($r['Tipo'] === 'DEVENGO') {
                    $devengos[] = $r;
                    $totalDev += floatval($r['Monto']);
                } else {
                    $deducciones[] = $r;
                    $totalDed += floatval($r['Monto']);
                }
            }

            $netoPagar = $totalDev - $totalDed;

            // Asegurar que las listas tengan el mismo tamaño (relleno de celdas vacías para uniformidad visual)
            $maxRows = max(count($devengos), count($deducciones), 3); // Mínimo 3 filas para mantener el alto estándar
            ?>

            <div class="boleta-container">
                
                <!-- Cabecera -->
                <div class="boleta-header">
                    <div class="logo-group">
                        <?php if (!empty($c['EmpresaLogo'])): ?>
                            <img src="<?php echo htmlspecialchars($c['EmpresaLogo']); ?>" alt="Logo Empresa" style="max-height: 48px; max-width: 160px; object-fit: contain; border-radius: 4px;">
                        <?php else: ?>
                            <!-- Círculo Green Bahia Limon -->
                            <div class="circular-logo logo-green">
                                <span class="logo-cursive">Bahia</span>
                                <span class="logo-bold">Limón</span>
                                <span class="logo-fm">107.9 FM</span>
                            </div>
                            <!-- Círculo Orange Bahia Puntarenas -->
                            <div class="circular-logo logo-orange">
                                <span class="logo-cursive">Bahia</span>
                                <span class="logo-bold">Puntarenas</span>
                                <span class="logo-fm">107.9 FM</span>
                            </div>
                            <!-- Slogan Central -->
                            <div class="header-slogan">
                                De Puerto a Puerto
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Título -->
                    <div class="header-title-box">
                        <h2 class="header-title-main">Boleta de Pago</h2>
                        <span class="header-title-period">Periodo: <?php echo date('d/m/Y', strtotime($planilla['FechaInicio'])) . ' al ' . date('d/m/Y', strtotime($planilla['FechaFin'])); ?></span>
                    </div>

                    <!-- Hora y Fecha -->
                    <div class="header-meta">
                        <strong>Hora:</strong> <?php echo date('h:i:s A'); ?><br>
                        <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($planilla['FechaPago'])); ?>
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
                        <span class="info-value font-mono"><?php echo getOrCreateConsecutivo($pdo, 'PLANILLA', $codPlanilla, $c['CodEmpleado'], $_SESSION['username']); ?></span>
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
                        <span class="info-label">Ubicación:</span>
                        <span class="info-value">NO DEFINIDA</span>
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

                <!-- Rubros Table -->
                <table class="rubros-table">
                    <thead>
                        <tr>
                            <th colspan="3" style="width: 50%;">BENEFICIOS (INGRESOS)</th>
                            <th colspan="2" style="width: 50%;">DEDUCCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        for ($i = 0; $i < $maxRows; $i++) {
                            $dev = isset($devengos[$i]) ? $devengos[$i] : null;
                            $ded = isset($deducciones[$i]) ? $deducciones[$i] : null;
                            ?>
                            <tr class="rubros-content-row">
                                <!-- Devengos -->
                                <td>
                                    <?php if ($dev): ?>
                                        <?php if (!empty($dev['CuentaContable']) && $dev['CuentaContable'] !== 'N/A'): ?>
                                            <span class="acct-badge">[<?php echo htmlspecialchars($dev['CuentaContable']); ?>]</span>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($dev['Descripcion']); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono" style="width: 60px;">
                                    <?php 
                                    if ($dev && floatval($dev['Cantidad']) > 0) {
                                        $isBaseSalary = (strpos(strtolower($dev['Codigo']), 'sb') !== false || strpos(strtolower($dev['Descripcion']), 'salario base') !== false || strpos(strtolower($dev['Descripcion']), 'sueldo base') !== false);
                                        if ($isBaseSalary) {
                                            $tipoPlanillaDesc = strtolower($planilla['TipoPlanilla']);
                                            $displayQty = (strpos($tipoPlanillaDesc, 'quincenal') !== false) ? 96.0 : 192.0;
                                            echo number_format($displayQty, 1, '.', '');
                                        } else {
                                            echo number_format($dev['Cantidad'], 1, '.', '');
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="text-right font-mono col-left" style="width: 100px;">
                                    <?php if ($dev): ?>
                                        ₡<?php echo number_format($dev['Monto'], 2, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Deducciones -->
                                <td>
                                    <?php if ($ded): ?>
                                        <?php if (!empty($ded['CuentaContable']) && $ded['CuentaContable'] !== 'N/A'): ?>
                                            <span class="acct-badge">[<?php echo htmlspecialchars($ded['CuentaContable']); ?>]</span>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($ded['Descripcion']); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono" style="width: 100px;">
                                    <?php if ($ded): ?>
                                        ₡<?php echo number_format($ded['Monto'], 2, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        
                        <!-- Totalizadores del Grid -->
                        <tr class="fw-bold" style="background-color: #f9fafb;">
                            <td colspan="2" class="text-right" style="font-weight: 700; text-transform: uppercase;">Total Ingresos</td>
                            <td class="text-right font-mono col-left" style="font-weight: 700;">₡<?php echo number_format($totalDev, 2, ',', '.'); ?></td>
                            <td class="text-right" style="font-weight: 700; text-transform: uppercase;">Total Deducciones</td>
                            <td class="text-right font-mono" style="font-weight: 700;">₡<?php echo number_format($totalDed, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer de la Boleta (Firma y Neto) -->
                <div class="boleta-footer">
                    <div class="signature-line">
                        RECIBIDO CONFORME: __________________________________________________
                    </div>
                    <div class="net-pay-box">
                        <span>TOTAL NETO A PAGAR:</span>
                        <span style="color: #111827; font-size: 13.5px;">₡<?php echo number_format($netoPagar, 2, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Footer de la Empresa -->
                <div class="corporate-footer">
                    <div class="corporate-logo">
                        <i class="fa-solid fa-building text-primary"></i>
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

            <!-- Si es el 3er elemento de un grupo de 3, agregar un salto de página para impresión en lote -->
            <?php if ($counter % 3 === 0 && $counter < count($colaboradores)) { ?>
                <div class="page-break-delimiter"></div>
            <?php } ?>

        <?php } ?>
    </div>

    <script>
        document.getElementById('btnToggleBW').addEventListener('click', function() {
            document.body.classList.toggle('bw-mode');
            const icon = this.querySelector('i');
            const span = this.querySelector('span');
            if (document.body.classList.contains('bw-mode')) {
                span.textContent = 'Modo Color';
                this.style.backgroundColor = '#2563eb';
            } else {
                span.textContent = 'Modo Blanco y Negro';
                this.style.backgroundColor = '#4b5563';
            }
        });
    </script>
</body>
</html>
