<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * PROCESAMIENTO Y CÁLCULO DE PLANILLAS (CALCULAR_PLANILLA.PHP)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control de seguridad de sesión activa
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];

$avatar_initial = strtoupper(substr($fullname, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cálculo de Planilla - PlanillaCR ERP</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables Bootstrap 5 CSS CDN -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom Dark/Light Executive CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
    
    <style>
        /* Estilos específicos premium para DataTables */
        .table-responsive {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-lg);
            padding: 20px;
            box-shadow: 0 10px 25px var(--shadow-main);
        }
        
        .table {
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
            margin-bottom: 0 !important;
        }
        
        .table th {
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--text-muted) !important;
            border-bottom: 2px solid var(--border-color) !important;
            padding: 14px 10px !important;
        }
        
        .table td {
            font-size: 13.5px;
            color: var(--text-secondary) !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color) !important;
            padding: 12px 10px !important;
        }
        
        .table tbody tr:hover td {
            color: var(--text-primary) !important;
            background-color: var(--bg-surface-elevated) !important;
        }
        
        /* Paginación y Buscador de DataTables */
        .dataTables_filter input {
            background-color: var(--bg-main) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--border-radius-md) !important;
            color: var(--text-primary) !important;
            padding: 6px 12px !important;
            font-size: 13px !important;
            outline: none !important;
        }
        
        .dataTables_length select {
            background-color: var(--bg-main) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--border-radius-sm) !important;
            color: var(--text-primary) !important;
            font-size: 13px !important;
            padding: 4px 8px !important;
        }
        
        .page-link {
            background-color: var(--bg-surface) !important;
            border-color: var(--border-color) !important;
            color: var(--text-secondary) !important;
            font-size: 13px !important;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #ffffff !important;
        }
        
        .page-link:hover {
            background-color: var(--bg-surface-elevated) !important;
            color: var(--text-primary) !important;
        }
        
        /* Insignias de Estado Premium */
        .badge-status {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .badge-status-activo {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .badge-status-inactivo {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .badge-status-aplicada {
            background-color: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .modal-content {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 20px 50px var(--shadow-main);
            color: var(--text-primary);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-light);
            padding: 24px 30px;
        }

        .modal-body {
            padding: 24px 30px;
        }

        .modal-footer {
            border-top: 1px solid var(--border-light);
            padding: 20px 30px;
        }
        
        .form-control, .form-select {
            background-color: var(--bg-main) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            border-radius: var(--border-radius-md) !important;
            padding: 10px 14px !important;
            font-size: 13.5px !important;
            outline: none !important;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15) !important;
        }

        .form-label-desc {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-table-action {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            background-color: var(--bg-surface-elevated);
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
        }

        .btn-table-action:hover:not(:disabled) {
            color: #ffffff;
            border-color: var(--text-muted);
            transform: translateY(-1px);
        }

        .btn-table-view:hover {
            color: #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.1) !important;
            border-color: rgba(59, 130, 246, 0.2) !important;
        }

        .btn-table-apply:hover {
            color: var(--success) !important;
            background-color: rgba(16, 185, 129, 0.1) !important;
            border-color: rgba(16, 185, 129, 0.2) !important;
        }

        .btn-table-delete:hover {
            color: var(--danger) !important;
            background-color: rgba(239, 68, 68, 0.1) !important;
            border-color: rgba(239, 68, 68, 0.2) !important;
        }

        /* Estilos de Drawer Lateral para Auditoría de Detalles */
        .audit-summary-strip {
            background-color: var(--bg-surface-elevated);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-md);
            padding: 16px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout">
        
        <!-- SIDEBAR FIJA - 100% CONGRUENTE -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Planilla<span>CR</span></h3>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="index.php">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Resumen / Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="empleados.php">
                        <i class="fa-solid fa-users"></i>
                        <span>Recursos Humanos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="puestos_deptos.php">
                        <i class="fa-solid fa-building-user"></i>
                        <span>Puestos & Deptos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="horarios.php">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>Horarios & Turnos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="horas_extra.php">
                        <i class="fa-solid fa-business-time"></i>
                        <span>Horas Extra</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="deducciones.php">
                        <i class="fa-solid fa-tags"></i>
                        <span>Deducciones</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="clasificaciones_deducciones.php">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rubros Deducción</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="vacaciones.php">
                        <i class="fa-solid fa-umbrella-beach"></i>
                        <span>Vacaciones</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="aguinaldo.php">
                        <i class="fa-solid fa-gift"></i>
                        <span>Aguinaldo</span>
                    </a>
                </li>
                <li class="sidebar-item active">
                    <a href="calcular_planilla.php">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Calcular Planilla</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="configuracion.php">
                        <i class="fa-solid fa-gears"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-profile-summary">
                    <div class="user-avatar" title="<?php echo htmlspecialchars($email); ?>">
                        <?php echo $avatar_initial; ?>
                    </div>
                    <div class="user-info-text">
                        <span class="user-info-name"><?php echo htmlspecialchars($fullname); ?></span>
                        <span class="user-info-role"><?php echo htmlspecialchars($role); ?></span>
                    </div>
                </div>
                
                <a href="logout.php" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- ÁREA DE CONTENIDO PRINCIPAL -->
        <main class="main-dashboard">
            
            <!-- Header superior - 100% CONGRUENTE -->
            <header class="dashboard-header">
                <div class="welcome-msg">
                    <h1>Procesamiento de Planilla</h1>
                    <p>Generación de nómina automática, retenciones de CCSS y Renta e integraciones del periodo</p>
                </div>
                
                <div class="header-actions">
                    <!-- Calcular Planilla -->
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnGenerarPlanillaModal" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-calculator"></i>
                        <span>+ Generar Nueva Planilla</span>
                    </button>

                    <div class="date-badge">
                        <i class="fa-regular fa-calendar"></i>
                        <span id="currentDate"><?php echo date('d/m/Y'); ?></span>
                    </div>

                    <!-- Botón de Tema Light/Dark -->
                    <button class="theme-toggle-btn" id="themeToggle" style="position: static;" title="Cambiar Tema Visual">
                        <i class="fa-solid fa-sun" id="themeIcon"></i>
                    </button>
                </div>
            </header>

            <!-- SECCIÓN DE KPIS GERENCIALES - 100% CONGRUENTE -->
            <section class="kpi-grid mb-4">
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Planillas Calculadas</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                    </div>
                    <div class="kpi-value text-primary" id="kpiTotalPlanillas">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-arrow-trend-up"></i> Histórico</span>
                        <span class="kpi-desc">Periodos procesados</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Total Devengado (Bruto)</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                    <div class="kpi-value text-success" id="kpiTotalDevengos">₡0.00</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up text-success"><i class="fa-solid fa-coins"></i> Salarios + Extras</span>
                        <span class="kpi-desc">Carga bruta calculada</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Retenciones Totales</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(239, 68, 68, 0.1); color: var(--danger);">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                    <div class="kpi-value text-danger" id="kpiTotalDeducciones">₡0.00</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-down text-danger"><i class="fa-solid fa-arrow-trend-down"></i> CCSS + Renta + Descs</span>
                        <span class="kpi-desc">Retenciones de Ley y Préstamos</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Neto Líquido a Pagar</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(251, 191, 36, 0.1); color: var(--warning);">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <div class="kpi-value text-warning" id="kpiTotalNeto">₡0.00</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up text-warning"><i class="fa-solid fa-credit-card"></i> Transferencias IBAN</span>
                        <span class="kpi-desc">Monto final transferible</span>
                    </div>
                </div>
            </section>

            <!-- Tabla de Datos -->
            <div class="table-responsive">
                <table id="tblPlanillas" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo Planilla</th>
                            <th>Desde / Hasta</th>
                            <th>Fecha de Pago</th>
                            <th class="text-end">Devengado</th>
                            <th class="text-end">Deducciones</th>
                            <th class="text-end">Neto a Pagar</th>
                            <th>Estado</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Poblado asíncronamente con jQuery AJAX -->
                    </tbody>
                </table>
            </div>
            
        </main>
    </div>

    <!-- MODAL GENERAR Y CALCULAR NUEVA PLANILLA -->
    <div class="modal fade" id="modalGenerarPlanilla" tabindex="-1" aria-labelledby="modalGenerarPlanillaTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalGenerarPlanillaTitle"><i class="fa-solid fa-calculator text-primary me-2"></i>Generar y Calcular Planilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmGenerarPlanilla" novalidate>
                    <div class="modal-body px-4 py-3">
                        <div class="alert alert-info border-0 rounded p-3 mb-4" style="background-color: rgba(var(--primary-rgb), 0.08); color: var(--text-primary); font-size: 13px; line-height: 1.5;">
                            <i class="fa-solid fa-circle-info text-primary me-2"></i>
                            <strong>Procesamiento Inteligente:</strong> Al procesar la planilla, el ERP jalará automáticamente los salarios base de todos los colaboradores activos, sumará las horas extra y destajos ingresados en el rango de fechas que aún estén pendientes, deducirá los aportes legales de CCSS, Renta Hacienda y aplicará amortizaciones automáticas de préstamos vigentes.
                        </div>

                        <div class="mb-3">
                            <label for="ddlTipoPlanillaImportar" class="form-label-desc">Frecuencia / Tipo de Nómina <span class="text-danger">*</span></label>
                            <select class="form-select" id="ddlTipoPlanillaImportar" required>
                                <!-- Cargado dinámicamente -->
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="txtFechaInicio" class="form-label-desc">Fecha de Inicio <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="txtFechaInicio" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtFechaFin" class="form-label-desc">Fecha de Fin <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="txtFechaFin" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="txtFechaPago" class="form-label-desc">Fecha de Pago <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="txtFechaPago" required>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2" id="btnProcessPlanilla" style="border-radius: var(--border-radius-md); font-size: 13.5px;">
                            <i class="fa-solid fa-gears"></i>
                            <span>Calcular y Generar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE Y AUDITORÍA DE PLANILLA -->
    <div class="modal fade" id="modalPlanillaDetalle" tabindex="-1" aria-labelledby="modalPlanillaDetalleTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalPlanillaDetalleTitle"><i class="fa-solid fa-list-check text-primary me-2"></i>Auditoría de Detalle de Nómina</h5>
                        <p class="text-muted mb-0 mt-1" style="font-size: 12.5px;">Detalle de devengos, deducciones y líquido neto por colaborador en el periodo</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 fw-semibold px-3 py-2" id="btnPrintAllReceipts" style="font-size: 12px; border-radius: var(--border-radius-md);">
                            <i class="fa-solid fa-print"></i>
                            <span>Imprimir Boletas (Todos)</span>
                        </button>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-info btn-sm dropdown-toggle d-flex align-items-center gap-2 fw-semibold px-3 py-2" type="button" id="dropdownBankExport" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 12px; border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-building-columns"></i>
                                <span>Enviar al Banco</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownBankExport" style="background-color: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                                <li><a class="dropdown-item py-2 text-light" href="#" onclick="exportToBank('BAC')"><i class="fa-solid fa-wallet text-danger me-2"></i>BAC Credomatic (.csv)</a></li>
                                <li><a class="dropdown-item py-2 text-light" href="#" onclick="exportToBank('BNCR')"><i class="fa-solid fa-wallet text-success me-2"></i>Banco Nacional (BNCR) (.txt)</a></li>
                                <li><a class="dropdown-item py-2 text-light" href="#" onclick="exportToBank('BCR')"><i class="fa-solid fa-wallet text-primary me-2"></i>Banco de Costa Rica (BCR) (.txt)</a></li>
                            </ul>
                        </div>

                        <button class="btn btn-outline-success btn-sm d-flex align-items-center gap-2 fw-semibold px-3 py-2" id="btnExportCSV" style="font-size: 12px; border-radius: var(--border-radius-md);">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Descargar CSV</span>
                        </button>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                    </div>
                </div>
                <div class="modal-body px-4 py-3">
                    
                    <!-- Faja de Resumen -->
                    <div class="audit-summary-strip">
                        <div class="row align-items-center justify-content-between g-3">
                            <div class="col-md-3">
                                <span class="d-block text-muted" style="font-size: 11px; text-transform: uppercase;">Tipo Planilla</span>
                                <strong class="fs-6" id="lblDetailTipo">N/A</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="d-block text-muted" style="font-size: 11px; text-transform: uppercase;">Periodo Procesado</span>
                                <strong class="fs-6" id="lblDetailPeriodo">N/A</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="d-block text-muted" style="font-size: 11px; text-transform: uppercase;">Fecha Pago</span>
                                <strong class="fs-6" id="lblDetailPago">N/A</strong>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="d-block text-muted" style="font-size: 11px; text-transform: uppercase; margin-bottom: 2px;">Estado Periodo</span>
                                <span id="lblDetailEstado">N/A</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Detalle -->
                    <div class="table-responsive p-2" style="box-shadow: none; border-color: var(--border-color);">
                        <table id="tblPlanillaDetalle" class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Colaborador</th>
                                    <th class="text-end">Salario Base</th>
                                    <th class="text-end">Horas Extra</th>
                                    <th class="text-end">Destajos</th>
                                    <th class="text-end">CCSS Obrero</th>
                                    <th class="text-end">Renta Único</th>
                                    <th class="text-end">Deds. Varias</th>
                                    <th class="text-end">Total Bruto</th>
                                    <th class="text-end">Deducciones</th>
                                    <th class="text-end" style="background-color: rgba(var(--primary-rgb), 0.03);">Neto Pagar</th>
                                    <th class="text-center">Boleta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Cargado dinámicamente -->
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold" style="border-top: 2px solid var(--text-muted); background-color: var(--bg-surface-elevated);">
                                    <td colspan="2" class="text-center">TOTAL DE LA NÓMINA</td>
                                    <td class="text-end" id="sumSalario">₡0.00</td>
                                    <td class="text-end" id="sumExtras">₡0.00</td>
                                    <td class="text-end" id="sumDestajos">₡0.00</td>
                                    <td class="text-end" id="sumCCSS">₡0.00</td>
                                    <td class="text-end" id="sumRenta">₡0.00</td>
                                    <td class="text-end" id="sumOtras">₡0.00</td>
                                    <td class="text-end" id="sumBruto">₡0.00</td>
                                    <td class="text-end" id="sumDeducciones">₡0.00</td>
                                    <td class="text-end text-warning" id="sumNeto" style="background-color: rgba(251, 191, 36, 0.05);">₡0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
                <div class="modal-footer px-4 py-3 d-flex justify-content-between">
                    <div id="wrapperApplyDetailFooter">
                        <!-- Botón interactivo para aplicar si está ABIERTA -->
                    </div>
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cerrar Vista</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JQUERY AND BOOTSTRAP BUNDLE JS -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DATATABLES BUNDLE JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ==========================================
        // Control de Tema Visual Unificado (Light/Dark)
        // ==========================================
        const applyTheme = (theme) => {
            const isLight = theme === 'light';
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            
            if (isLight) {
                body.classList.add('theme-light');
                body.setAttribute('data-bs-theme', 'light');
                if (themeIcon) {
                    themeIcon.className = 'fa-solid fa-sun';
                }
            } else {
                body.classList.remove('theme-light');
                body.setAttribute('data-bs-theme', 'dark');
                if (themeIcon) {
                    themeIcon.className = 'fa-solid fa-moon';
                }
            }
        };

        const savedTheme = localStorage.getItem('theme') || 'dark';
        applyTheme(savedTheme);

        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const currentTheme = localStorage.getItem('theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });
        }

        function showDevelopmentAlert(modulo) {
            Swal.fire({
                title: 'Módulo en Desarrollo',
                text: `La funcionalidad de ${modulo} está siendo implementada según el plan estratégico y estará disponible en el siguiente ciclo.`,
                icon: 'info',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#0056b3',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            });
        }

        // ==========================================================================
        // CONTROLADOR DE CALCULO DE PLANILLAS
        // ==========================================================================
        let tablePlanillas;
        let activeDetailId = 0;
        let currentDetailData = [];

        $(document).ready(function() {
            // Inicializar DataTable de Planillas Calculadas
            tablePlanillas = $('#tblPlanillas').DataTable({
                order: [[0, 'desc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                columnDefs: [
                    { targets: [4, 5, 6], className: 'text-end font-monospace fw-semibold' },
                    { targets: 7, className: 'text-center' },
                    { targets: 8, className: 'text-center', orderable: false }
                ]
            });

            // Cargar datos
            loadPlanillasData();

            // Cargar tipos de planilla en el modal
            loadTipoPlanillas();

            // Abrir Modal de Generación
            $('#btnGenerarPlanillaModal').click(function() {
                $('#frmGenerarPlanilla')[0].reset();
                
                // Sugerir fechas de quincena en base a la fecha actual por UX premium y congruencia de datos
                let today = new Date();
                let y = today.getFullYear();
                let m = String(today.getMonth() + 1).padStart(2, '0');
                let day = today.getDate();
                
                let startDay, endDay;
                if (day <= 15) {
                    startDay = '01';
                    endDay = '15';
                } else {
                    startDay = '16';
                    // Obtener último día del mes
                    let last = new Date(y, today.getMonth() + 1, 0).getDate();
                    endDay = String(last).padStart(2, '0');
                }
                
                $('#txtFechaInicio').val(`${y}-${m}-${startDay}`);
                $('#txtFechaFin').val(`${y}-${m}-${endDay}`);
                $('#txtFechaPago').val(`${y}-${m}-${endDay}`);

                $('#modalGenerarPlanilla').modal('show');
            });

            // Formulario de Generación
            $('#frmGenerarPlanilla').submit(function(e) {
                e.preventDefault();

                let codTipo = $('#ddlTipoPlanillaImportar').val();
                let fIni = $('#txtFechaInicio').val();
                let fFin = $('#txtFechaFin').val();
                let fPago = $('#txtFechaPago').val();

                if (!codTipo) {
                    Swal.fire('Atención', 'Debe seleccionar un tipo de nómina.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Calculando Planilla...',
                    html: 'Procesando salarios base, sumando horas extra y destajos del periodo, aplicando deducciones obreras, renta y préstamos amortizables...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'ajax/planilla.php?action=generate_planilla',
                    type: 'POST',
                    data: {
                        cod_tipo_planilla: codTipo,
                        fecha_inicio: fIni,
                        fecha_fin: fFin,
                        fecha_pago: fPago
                    },
                    dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        if (res.success) {
                            Swal.fire({
                                title: '¡Planilla Calculada!',
                                text: res.message,
                                icon: 'success',
                                confirmButtonColor: '#198754',
                                background: 'var(--bg-surface)',
                                color: 'var(--text-primary)'
                            }).then(() => {
                                $('#modalGenerarPlanilla').modal('hide');
                                loadPlanillasData();
                                viewPlanillaDetalle(res.cod_planilla);
                            });
                        } else {
                            Swal.fire('Error de Cálculo', res.message, 'error');
                        }
                    },
                    error: function(err) {
                        Swal.close();
                        Swal.fire('Error', 'Fallo al procesar cálculo en el servidor.', 'error');
                    }
                });
            });

            // Exportar detalle actual a CSV
            $('#btnExportCSV').click(function() {
                if (currentDetailData.length === 0) return;
                
                let csv = 'Cedula,Colaborador,Salario Base,Horas Extra,Destajos,CCSS Obrero,Renta Hacienda,Otras Deducciones,Total Bruto,Total Deducciones,Neto Liquido\r\n';
                
                currentDetailData.forEach(function(row) {
                    csv += `"${row.Identificacion}","${row.Colaborador}",${row.SalarioBase},${row.HorasExtra},${row.Destajos},${row.CCSS},${row.Renta},${row.OtrasDeducciones},${row.TotalDevengos},${row.TotalDeducciones},${row.Neto}\r\n`;
                });
                
                let blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' });
                let link = document.createElement("a");
                let url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `Planilla_Detalle_ID_${activeDetailId}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });

        // Cargar Catálogo de Tipos de Planilla
        function loadTipoPlanillas() {
            $.ajax({
                url: 'ajax/planilla.php?action=get_tipo_planillas',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let ddl = $('#ddlTipoPlanillaImportar');
                        ddl.empty().append('<option value="" disabled selected>-- Seleccione Frecuencia --</option>');
                        res.data.forEach(function(tp) {
                            // Preseleccionar Quincenal por defecto por comodidad
                            let selected = tp.CodTipoPlanilla === 2 ? 'selected' : '';
                            ddl.append(`<option value="${tp.CodTipoPlanilla}" ${selected}>Planilla ${tp.Descripcion}</option>`);
                        });
                    }
                }
            });
        }

        // Cargar Periodos de Planilla en DataTable
        function loadPlanillasData() {
            $.ajax({
                url: 'ajax/planilla.php?action=list_planillas',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tablePlanillas.clear();

                        let totalCalculadas = res.data.length;
                        let sumBruto = 0;
                        let sumDeds = 0;
                        let sumNeto = 0;

                        res.data.forEach(function(p) {
                            sumBruto += p.TotalDevengos;
                            sumDeds += p.TotalDeducciones;
                            sumNeto += p.TotalNeto;

                            // Badges de estado
                            let badgeClass = p.Estado === 'ABIERTA' ? 'badge-status-activo' : 'badge-status-aplicada';
                            let badgeIcon = p.Estado === 'ABIERTA' ? '<i class="fa-solid fa-folder-open"></i>' : '<i class="fa-solid fa-lock"></i>';
                            let badge = `<span class="badge-status ${badgeClass}">${badgeIcon} ${p.Estado}</span>`;
                            
                            // Botones de acciones
                            let btnView = `<button class="btn-table-action btn-table-view" onclick="viewPlanillaDetalle(${p.CodPlanilla})" title="Ver Detalle / Auditoría"><i class="fa-solid fa-eye"></i></button>`;
                            let btnApply = '';
                            let btnDelete = '';

                            if (p.Estado === 'ABIERTA') {
                                btnApply = `<button class="btn-table-action btn-table-apply" onclick="applyPlanilla(${p.CodPlanilla})" title="Aplicar / Cerrar Planilla"><i class="fa-solid fa-lock"></i></button>`;
                                btnDelete = `<button class="btn-table-action btn-table-delete" onclick="annulPlanilla(${p.CodPlanilla})" title="Anular Planilla"><i class="fa-solid fa-trash-can"></i></button>`;
                            } else {
                                btnApply = `<button class="btn-table-action" disabled title="Planilla Cerrada"><i class="fa-solid fa-lock-keyhole"></i></button>`;
                                btnDelete = `<button class="btn-table-action" disabled title="Planilla Cerrada"><i class="fa-solid fa-ban"></i></button>`;
                            }

                            let acciones = `<div class="d-flex gap-2 justify-content-center">${btnView}${btnApply}${btnDelete}</div>`;

                            let periodStr = `<span class="fw-semibold text-light">${p.FechaInicio} al ${p.FechaFin}</span>`;
                            let devStr = `₡${p.TotalDevengos.toLocaleString('es-CR', { minimumFractionDigits: 2 })}`;
                            let dedStr = `₡${p.TotalDeducciones.toLocaleString('es-CR', { minimumFractionDigits: 2 })}`;
                            let netStr = `<span class="text-warning fw-bold">₡${p.TotalNeto.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span>`;

                            tablePlanillas.row.add([
                                p.CodPlanilla,
                                p.TipoPlanilla,
                                periodStr,
                                p.FechaPago,
                                devStr,
                                dedStr,
                                netStr,
                                badge,
                                acciones
                            ]);
                        });

                        tablePlanillas.draw();

                        // KPIs
                        $('#kpiTotalPlanillas').text(totalCalculadas);
                        $('#kpiTotalDevengos').text('₡' + sumBruto.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpiTotalDeducciones').text('₡' + sumDeds.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpiTotalNeto').text('₡' + sumNeto.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    }
                }
            });
        }

        // Ver Detalle e Itemización de Planilla
        function viewPlanillaDetalle(id) {
            activeDetailId = id;
            
            // Buscar datos generales de la fila seleccionada
            $.ajax({
                url: 'ajax/planilla.php?action=list_planillas',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let p = res.data.find(x => x.CodPlanilla === id);
                        if (p) {
                            $('#lblDetailTipo').text('Planilla ' + p.TipoPlanilla);
                            $('#lblDetailPeriodo').text(`${p.FechaInicio} al ${p.FechaFin}`);
                            $('#lblDetailPago').text(p.FechaPago);

                            // Badge de estado en el modal
                            let badgeClass = p.Estado === 'ABIERTA' ? 'badge-status-activo' : 'badge-status-aplicada';
                            let badgeIcon = p.Estado === 'ABIERTA' ? '<i class="fa-solid fa-folder-open"></i>' : '<i class="fa-solid fa-lock"></i>';
                            $('#lblDetailEstado').html(`<span class="badge-status ${badgeClass}">${badgeIcon} ${p.Estado}</span>`);

                            // Footer interactivo: Si está abierta, dar opción de Aplicar
                            let footerBtnWrap = $('#wrapperApplyDetailFooter');
                            footerBtnWrap.empty();
                            if (p.Estado === 'ABIERTA') {
                                footerBtnWrap.append(`
                                    <button class="btn btn-success fw-semibold px-4 py-2 d-inline-flex align-items-center gap-2" onclick="applyPlanilla(${p.CodPlanilla})" style="border-radius: var(--border-radius-md); font-size: 13.5px;">
                                        <i class="fa-solid fa-lock"></i>
                                        <span>Aplicar y Cerrar Planilla</span>
                                    </button>
                                `);
                            }
                        }
                    }
                }
            });

            // Cargar itemización por colaborador
            $.ajax({
                url: 'ajax/planilla.php?action=get_planilla_detalle',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        currentDetailData = res.data;
                        let body = $('#tblPlanillaDetalle tbody');
                        body.empty();

                        let totSalario = 0, totExtras = 0, totDestajos = 0;
                        let totCCSS = 0, totRenta = 0, totOtras = 0;
                        let totBruto = 0, totDeds = 0, totNeto = 0;

                        res.data.forEach(function(r) {
                            totSalario += r.SalarioBase;
                            totExtras += r.HorasExtra;
                            totDestajos += r.Destajos;
                            totCCSS += r.CCSS;
                            totRenta += r.Renta;
                            totOtras += r.OtrasDeducciones;
                            totBruto += r.TotalDevengos;
                            totDeds += r.TotalDeducciones;
                            totNeto += r.Neto;

                            body.append(`
                                <tr>
                                    <td class="font-monospace" style="font-size: 12px;">${r.Identificacion}</td>
                                    <td class="fw-semibold text-light">${r.Colaborador}</td>
                                    <td class="text-end font-monospace">₡${r.SalarioBase.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace text-info">₡${r.HorasExtra.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace text-success">₡${r.Destajos.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace text-danger">₡${r.CCSS.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace text-danger">₡${r.Renta.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace text-danger">₡${r.OtrasDeducciones.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace fw-semibold text-light">₡${r.TotalDevengos.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace fw-semibold text-danger">₡${r.TotalDeducciones.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end font-monospace fw-bold text-warning" style="background-color: rgba(var(--primary-rgb), 0.015);">₡${r.Neto.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-center">
                                        <a href="imprimir_comprobantes.php?id=${id}&emp=${r.CodEmpleado}" target="_blank" class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 11px; border-radius: 4px; color: var(--text-primary); border-color: var(--border-color);">
                                            <i class="fa-solid fa-print text-primary"></i>
                                            <span>Boleta</span>
                                        </a>
                                    </td>
                                </tr>
                            `);
                        });

                        // Sumas finales en el footer
                        $('#sumSalario').text('₡' + totSalario.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumExtras').text('₡' + totExtras.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumDestajos').text('₡' + totDestajos.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumCCSS').text('₡' + totCCSS.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumRenta').text('₡' + totRenta.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumOtras').text('₡' + totOtras.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumBruto').text('₡' + totBruto.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumDeducciones').text('₡' + totDeds.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                        $('#sumNeto').text('₡' + totNeto.toLocaleString('es-CR', { minimumFractionDigits: 2 }));

                        $('#modalPlanillaDetalle').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }

        // Cerrar/Aplicar Planilla Definitivamente
        function applyPlanilla(id) {
            Swal.fire({
                title: '¿Desea cerrar la planilla?',
                text: "Al aplicar la planilla, el periodo pasará a estado APLICADA (cerrado). No podrá realizar modificaciones en horas extra ni deducciones reportadas para este periodo y los saldos amortizados quedarán en firme.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, Aplicar Planilla',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Aplicando Planilla...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'ajax/planilla.php?action=apply_planilla',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(res) {
                            Swal.close();
                            if (res.success) {
                                Swal.fire({
                                    title: '¡Planilla Cerrada!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                $('#modalPlanillaDetalle').modal('hide');
                                loadPlanillasData();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error', 'Fallo al procesar el cierre en el servidor.', 'error');
                        }
                    });
                }
            });
        }

        // Anular Planilla y revertir saldos
        function annulPlanilla(id) {
            Swal.fire({
                title: '¿Está seguro de anular la planilla?',
                text: "Esta acción eliminará el cálculo actual. Las horas extra y destajos del periodo regresarán a estado 'PENDIENTE' y los saldos amortizados en préstamos se restaurarán automáticamente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, Anular y Revertir',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Revirtiendo transacciones...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'ajax/planilla.php?action=annul_planilla',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(res) {
                            Swal.close();
                            if (res.success) {
                                Swal.fire({
                                    title: '¡Planilla Anulada!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                loadPlanillasData();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        },
                        error: function(err) {
                            Swal.close();
                            let errMsg = err.responseJSON ? err.responseJSON.message : 'Error desconocido al anular planilla.';
                            Swal.fire('Error de Anulación', errMsg, 'error');
                        }
                    });
                }
            });
        }

        // Exportar a banco (BAC, BNCR, BCR)
        function exportToBank(banco) {
            if (typeof activeDetailId === 'undefined' || !activeDetailId) {
                Swal.fire('Error', 'No hay ninguna planilla activa para exportar.', 'error');
                return;
            }
            
            Swal.fire({
                title: 'Exportando Archivo',
                html: 'Generando archivo de transferencia bancaria para <strong>' + banco + '</strong>...',
                icon: 'info',
                timer: 1200,
                showConfirmButton: false,
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            });
            
            setTimeout(function() {
                window.location.href = 'ajax/planilla.php?action=export_bank&id=' + activeDetailId + '&banco=' + banco;
                
                // Mostrar alerta de éxito
                Swal.fire({
                    title: '¡Exportación Exitosa!',
                    html: 'El archivo para <strong>' + banco + '</strong> se ha descargado en su navegador.<br><br><span style="font-size: 13.5px; color: #10b981; font-weight: 600;"><i class="fa-solid fa-desktop me-1"></i> ¡También se guardó una copia directa en su <strong>Escritorio</strong> para un acceso inmediato!</span>',
                    icon: 'success',
                    confirmButtonText: 'Excelente',
                    confirmButtonColor: 'var(--accent-color)',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                });
            }, 1000);
        }

        $(document).ready(function() {
            $('#btnPrintAllReceipts').on('click', function() {
                if (typeof activeDetailId === 'undefined' || !activeDetailId) {
                    Swal.fire('Error', 'No hay planilla activa para imprimir comprobantes.', 'error');
                    return;
                }
                window.open('imprimir_comprobantes.php?id=' + activeDetailId, '_blank');
            });
        });
    </script>
</body>
</html>
