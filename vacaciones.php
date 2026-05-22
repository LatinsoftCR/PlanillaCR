<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO Y REGISTRO DE VACACIONES (VACACIONES.PHP)
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
    <title>Gestión de Vacaciones - PlanillaCR ERP</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables Bootstrap 5 CSS CDN -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Dark/Light Executive CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
    
    <style>
        /* Estilos específicos premium para DataTables y Vacaciones */
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

        /* Tarjetas de Resumen KPI */
        .kpi-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-lg);
            padding: 20px;
            box-shadow: 0 4px 20px var(--shadow-main);
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px var(--shadow-main);
            border-color: var(--primary-color);
        }
        
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .kpi-icon.primary { background-color: rgba(var(--primary-rgb), 0.12); color: var(--primary-color); }
        .kpi-icon.success { background-color: rgba(46, 204, 113, 0.12); color: #2ecc71; }
        .kpi-icon.warning { background-color: rgba(241, 196, 15, 0.12); color: #f1c40f; }

        /* Badge de Saldo disponible en colunas */
        .saldo-badge {
            font-weight: 700;
            font-size: 13px;
            padding: 4px 8px;
            border-radius: var(--border-radius-sm);
        }
        .saldo-badge.positive { background-color: rgba(46, 204, 113, 0.12); color: #2ecc71; }
        .saldo-badge.negative { background-color: rgba(231, 76, 60, 0.12); color: #e74c3c; }

        /* Estilos del modal y inputs */
        .modal-content {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 15px 45px rgba(0,0,0,0.5);
        }
        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        #vacacionesTabNav .nav-link {
            color: var(--text-secondary);
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-md) !important;
        }
        #vacacionesTabNav .nav-link.active {
            color: #fff;
            background-color: var(--primary-color, #2563eb);
            border-color: var(--primary-color, #2563eb);
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout">
        <!-- ==========================================
             BARRA LATERAL DE NAVEGACIÓN
             ========================================== -->
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
                    <li class="sidebar-item active">
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
                    <li class="sidebar-item">
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

        <!-- ==========================================
             ÁREA DE CONTENIDO PRINCIPAL
             ========================================== -->
        <main class="main-dashboard">
            <!-- Header superior -->
            <header class="dashboard-header">
                <div class="welcome-msg">
                    <h1>Vacaciones de Colaboradores</h1>
                    <p>Saldos de periodos, disfrute de días y control de compensación monetaria</p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnNewVacation" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-umbrella-beach"></i>
                        <span>Registrar Vacaciones</span>
                    </button>
                    <a href="#pagos" class="btn btn-outline-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnGoPagos" style="border-radius: var(--border-radius-md);">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Pagos y Comprobantes</span>
                    </a>

                    <div class="date-badge">
                        <i class="fa-regular fa-calendar"></i>
                        <span id="currentDate"><?php echo date('d/m/Y'); ?></span>
                    </div>
                    
                    <button class="theme-toggle-btn" id="themeToggle" style="position: static;" title="Cambiar Tema Visual">
                        <i class="fa-solid fa-moon" id="themeIcon"></i>
                    </button>
                </div>
            </header>

            <!-- Dashboard de KPI Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon primary">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 12px; font-weight: 500; text-transform: uppercase;">Días Totales Acumulados</span>
                            <h2 class="mb-0 fw-bold" id="kpiAcumulados" style="color: var(--text-primary);">0.00</h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon warning">
                            <i class="fa-solid fa-plane-departure"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 12px; font-weight: 500; text-transform: uppercase;">Días Gozados / Disfrutados</span>
                            <h2 class="mb-0 fw-bold" id="kpiTomados" style="color: var(--text-primary);">0.00</h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon success">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 12px; font-weight: 500; text-transform: uppercase;">Días Netos Disponibles</span>
                            <h2 class="mb-0 fw-bold" id="kpiDisponibles" style="color: var(--text-primary);">0.00</h2>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills gap-2 mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold px-4" data-bs-toggle="pill" data-bs-target="#paneSaldos" type="button" role="tab">
                        <i class="fa-solid fa-calendar-days me-2"></i>Saldos por Colaborador
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold px-4" data-bs-toggle="pill" data-bs-target="#panePagos" type="button" role="tab">
                        <i class="fa-solid fa-file-invoice-dollar me-2"></i>Pagos y Comprobantes
                        <span class="badge bg-primary ms-1" id="badgePagosCount">0</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
            <div class="tab-pane fade show active" id="paneSaldos" role="tabpanel">
            <div class="table-responsive">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 0;">Saldos Generales por Colaborador</h3>
                </div>
                
                <table class="table" id="tableVacations">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Identificación</th>
                            <th>Ingreso</th>
                            <th>Antigüedad</th>
                            <th>Planilla</th>
                            <th>Ganados</th>
                            <th>Disfrutados</th>
                            <th>Saldo Disponible</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Carga vía AJAX -->
                    </tbody>
                </table>
            </div>
            </div>

            <div class="tab-pane fade" id="panePagos" role="tabpanel">
            <section>
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">Pagos y Comprobantes de Vacaciones</h3>
                        <p class="text-muted mb-0" style="font-size: 12.5px;">Compensaciones monetarias registradas — desglose, impresión y archivo para el banco</p>
                    </div>
                </div>

                <div class="filter-panel mb-3 p-3" style="background-color: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--border-radius-lg);">
                    <form id="frmPagosVacaciones" class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label for="txtYearPagos" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Año del pago:</label>
                            <select class="form-select" id="txtYearPagos" style="border-radius: var(--border-radius-md);">
                                <?php
                                $currentYear = date('Y');
                                for ($y = $currentYear + 1; $y >= 2020; $y--) {
                                    $sel = ($y == $currentYear) ? 'selected' : '';
                                    echo "<option value='{$y}' {$sel}>{$y}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-9 d-flex gap-2 justify-content-end align-items-center flex-wrap">
                            <button type="submit" class="btn btn-primary px-3 py-2 d-flex align-items-center gap-2 fw-semibold" style="border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-list"></i>
                                <span>Cargar Pagos</span>
                            </button>
                            <button type="button" class="btn btn-secondary px-3 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnPrintAllVacaciones" disabled style="border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-print"></i>
                                <span>Imprimir Todos</span>
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle px-3 py-2 d-flex align-items-center gap-2 fw-semibold" type="button" id="btnExportBankVacaciones" data-bs-toggle="dropdown" disabled style="border-radius: var(--border-radius-md);">
                                    <i class="fa-solid fa-file-export"></i>
                                    <span>Exportar Banco</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="background-color: var(--bg-surface); border: 1px solid var(--border-light);">
                                    <li><a class="dropdown-item py-2" href="#" onclick="exportVacacionesToBank('BAC'); return false;"><i class="fa-solid fa-file-csv me-2 text-info"></i>BAC (CSV)</a></li>
                                    <li><a class="dropdown-item py-2" href="#" onclick="exportVacacionesToBank('BNCR'); return false;"><i class="fa-solid fa-file-lines me-2 text-success"></i>BNCR (TXT)</a></li>
                                    <li><a class="dropdown-item py-2" href="#" onclick="exportVacacionesToBank('BCR'); return false;"><i class="fa-solid fa-file-lines me-2 text-warning"></i>BCR (TXT)</a></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover w-100" id="tblPagosVacaciones">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Identificación</th>
                                <th>Colaborador</th>
                                <th class="text-end">Días</th>
                                <th class="text-end">Monto Neto</th>
                                <th class="text-center" style="width: 140px;">Desglose</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
            </div>
            </div>
        </main>
    </div>

    <!-- MODAL: DESGLOSE DE PAGO DE VACACIONES -->
    <div class="modal fade" id="modalBreakdownVac" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border-light);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="lblBreakdownVacTitle">Desglose de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--close-btn-filter);"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Cálculo del pago según salario base, tipo de planilla y días compensados (Ley CR).</p>
                    <div id="breakdownVacContainer" class="border rounded px-3 py-2" style="background-color: var(--bg-main); border-color: var(--border-color) !important;"></div>
                    <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                        <span class="fw-bold text-secondary">Salario diario:</span>
                        <span class="fw-bold text-primary font-monospace" id="lblSalarioDiarioVac">₡0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="fw-bold text-secondary">Neto de vacaciones:</span>
                        <span class="fw-bold text-success font-monospace" id="lblMontoNetoVac" style="font-size: 16px;">₡0.00</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL: REGISTRAR / SOLICITAR VACACIONES
         ========================================== -->
    <div class="modal fade" id="modalVacation" tabindex="-1" aria-labelledby="modalVacationLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="modalVacationLabel" style="color: var(--text-primary); font-weight: 600;">
                        <i class="fa-solid fa-umbrella-beach text-primary"></i>
                        <span>Registrar Movimiento de Vacaciones</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--close-btn-filter);"></button>
                </div>
                
                <form id="formVacation" autocomplete="off">
                    <div class="modal-body" style="background-color: var(--bg-surface-elevated); max-height: 75vh; overflow-y: auto; padding: 25px;">
                        <div class="row g-3">
                            <!-- Colaborador -->
                            <div class="col-md-6">
                                <label for="cod_empleado" class="form-label form-label-desc">Colaborador <span class="text-danger">*</span></label>
                                <select class="form-select" id="cod_empleado" name="cod_empleado" required>
                                    <option value="">Seleccione un colaborador...</option>
                                    <!-- Carga dinámica -->
                                </select>
                            </div>
                            
                            <!-- Tipo de Movimiento -->
                            <div class="col-md-6">
                                <label for="tipo_movimiento" class="form-label form-label-desc">Tipo de Movimiento <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo_movimiento" name="tipo_movimiento" required>
                                    <option value="DISFRUTE" selected>Disfrute de Días (Gozados)</option>
                                    <option value="PAGO">Compensación / Pago de Saldo (Venta)</option>
                                    <option value="AJUSTE">Ajuste de Saldo (+/- Adiciones)</option>
                                </select>
                            </div>

                            <!-- Bloque Fechas (Solo Disfrute) -->
                            <div class="row g-3 px-0 mx-0" id="bloqueFechas">
                                <div class="col-md-6">
                                    <label for="fecha_inicio" class="form-label form-label-desc">Fecha de Inicio <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_fin" class="form-label form-label-desc">Fecha de Fin <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                                </div>
                            </div>

                            <!-- Cantidad de Días (Solo Ajustes o Pago Manual) -->
                            <div class="col-md-4 d-none" id="bloqueDiasManual">
                                <label for="dias_manual" class="form-label form-label-desc">Cantidad de Días <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="dias_manual" name="dias_manual" placeholder="Ej: 5.00">
                            </div>

                            <!-- Cálculo de Días en Vivo (Para previsualizar antes de guardar) -->
                            <div class="col-md-4">
                                <label class="form-label form-label-desc">Total Días a Aplicar</label>
                                <input type="text" class="form-control fw-bold" id="dias_calculados" style="background-color: var(--bg-main); border-color: var(--border-color); color: var(--primary-color);" readonly value="0.00">
                            </div>

                            <!-- Estimación de Monto Financiero (Ley Costa Rica) -->
                            <div class="col-md-4" id="bloqueMontoFinanciero">
                                <label class="form-label form-label-desc">Monto Estimado de Pago (₡)</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="background-color: var(--bg-main); border-color: var(--border-color); color: var(--text-muted);">₡</span>
                                    <input type="text" class="form-control fw-bold text-success" id="monto_pagar" style="background-color: var(--bg-main); border-color: var(--border-color);" readonly value="0.00">
                                    <input type="hidden" id="monto_pagar_num" value="0">
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-12">
                                <label for="observaciones" class="form-label form-label-desc">Observaciones / Motivo <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Ej: Toma vacaciones correspondientes al periodo 2025." required></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background-color: var(--bg-surface); padding: 15px 25px;">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md);">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2" style="border-radius: var(--border-radius-md);">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Guardar Registro</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL: VER HISTORIAL DETALLADO
         ========================================== -->
    <div class="modal fade" id="modalHistory" tabindex="-1" aria-labelledby="modalHistoryLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="modalHistoryLabel" style="color: var(--text-primary); font-weight: 600;">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                        <span>Historial de Movimientos de Vacaciones</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--close-btn-filter);"></button>
                </div>
                
                <div class="modal-body" style="background-color: var(--bg-surface-elevated); padding: 25px;">
                    <div class="mb-4">
                        <h4 id="historyColaboradorName" class="mb-1" style="font-size: 16px; font-weight: 600; color: var(--text-primary);">Mario Rojas</h4>
                        <p class="text-muted mb-0" id="historyColaboradorDetalle" style="font-size: 12.5px;">Fecha Ingreso: 10/05/2020 | Antigüedad: 6 años</p>
                    </div>

                    <div class="table-responsive p-0 border-0 shadow-none">
                        <table class="table" id="tableHistory">
                            <thead>
                                <tr>
                                    <th>Fecha Registro</th>
                                    <th>Tipo</th>
                                    <th>Periodo / Fechas</th>
                                    <th>Días Solicitados</th>
                                    <th>Monto Compensado</th>
                                    <th>Observaciones</th>
                                    <th>Registrado Por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Carga dinámica -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor dinámico e invisible para el envío Acción de Personal -->
    <form id="personalActionForm" action="imprimir_comprobantes.php" method="POST" target="_blank" style="display: none;">
        <input type="hidden" name="motivo" value="Vacaciones">
        <input type="hidden" name="empName" id="paEmpName">
        <input type="hidden" name="empCedula" id="paEmpCedula">
        <input type="hidden" name="empNacimiento" id="paEmpNacimiento">
        <input type="hidden" name="empTelefono" id="paEmpTelefono">
        <input type="hidden" name="empDireccion" id="paEmpDireccion">
        <input type="hidden" name="empPuesto" id="paEmpPuesto">
        <input type="hidden" name="empDepto" id="paEmpDepto">
        <input type="hidden" name="empSucursal" id="paEmpSucursal">
        <input type="hidden" name="empHorario" id="paEmpHorario">
        <input type="hidden" name="empEstado" id="paEmpEstado">
        <input type="hidden" name="empSalarioActual" id="paEmpSalarioActual">
        <input type="hidden" name="observaciones" id="paObservaciones">
        <input type="hidden" name="nacionalidad" value="Costarricense">
    </form>

    <!-- Bootstrap 5, jQuery, DataTables & SweetAlert JS CDNs -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const currentUserFullname = "<?php echo htmlspecialchars($fullname); ?>";
        let paymentsData = [];

        /** Convierte monto en formato es-CR (208.000,00) a número 208000.00 */
        function parseMontoColonesCR(valor) {
            if (valor === null || valor === undefined || valor === '') return 0;
            if (typeof valor === 'number' && !isNaN(valor)) return valor;
            let s = String(valor).replace(/₡/g, '').replace(/\s/g, '').trim();
            if (!s) return 0;
            if (s.indexOf(',') !== -1) {
                s = s.replace(/\./g, '').replace(',', '.');
            } else if ((s.match(/\./g) || []).length > 1) {
                s = s.replace(/\./g, '');
            }
            const n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }

        function formatMontoColonesCR(num) {
            return parseMontoColonesCR(num).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function viewBreakdownVac(idx) {
            const row = paymentsData[idx];
            if (!row || !row.Breakdown) return;
            const b = row.Breakdown;
            $('#lblBreakdownVacTitle').text('Desglose de ' + row.Colaborador);
            let html = '';
            (b.Lineas || []).forEach(function(linea) {
                const val = typeof linea.valor === 'number'
                    ? (linea.concepto.indexOf('Días') >= 0 ? linea.valor.toFixed(2) + ' días' : '₡' + linea.valor.toLocaleString('es-CR', { minimumFractionDigits: 2 }))
                    : linea.valor;
                html += `<div class="d-flex justify-content-between py-1 border-bottom" style="border-color: var(--border-color) !important;">
                    <span style="font-size: 13px;">${linea.concepto}</span>
                    <span class="font-monospace fw-semibold">${val}</span>
                </div>`;
            });
            $('#breakdownVacContainer').html(html);
            $('#lblSalarioDiarioVac').text('₡' + b.SalarioDiario.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
            $('#lblMontoNetoVac').text('₡' + b.MontoPagado.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
            $('#modalBreakdownVac').modal('show');
        }

        function printIndividualVacacion(idx) {
            const row = paymentsData[idx];
            if (!row) return;
            window.open('imprimir_vacaciones.php?id=' + row.CodMovimiento, '_blank');
        }

        function exportVacacionesToBank(banco) {
            const year = $('#txtYearPagos').val();
            Swal.fire({
                title: 'Exportación ' + banco,
                text: 'Indique la referencia para la transferencia bancaria de vacaciones:',
                input: 'text',
                inputValue: 'VACACIONES ' + year,
                showCancelButton: true,
                confirmButtonText: 'Generar archivo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const ref = encodeURIComponent(result.value || ('VACACIONES ' + year));
                    const url = `ajax/vacaciones.php?action=export_bank&year=${year}&banco=${banco}&referencia=${ref}`;
                    window.location.href = url;
                    Swal.fire({
                        title: 'Archivo generado',
                        html: `El archivo para el banco <strong>${banco}</strong> ha sido descargado.<br><br><span class="text-success fw-semibold"><i class="fa-solid fa-desktop"></i> Si está en localhost, también se guardó una copia en el Escritorio.</span>`,
                        icon: 'success',
                        timer: 3500,
                        showConfirmButton: false
                    });
                }
            });
        }

        $(document).ready(function() {
            let activeColaborador = null;
            let listColaboradores = [];

            // Control de tema visual (claro/oscuro)
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

            $('#themeToggle').on('click', function() {
                const currentTheme = localStorage.getItem('theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });

            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const hoy = new Date();
            const fechaEsp = hoy.toLocaleDateString('es-CR', opciones);
            const fechaCap = fechaEsp.charAt(0).toUpperCase() + fechaEsp.slice(1);
            $('#currentDate').text(fechaCap);

            // 1. Inicializar la DataTable Principal
            const table = $('#tableVacations').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: 8 }
                ]
            });

            // 2. Cargar Saldos y Actualizar KPI Cards
            function loadBalances() {
                $.ajax({
                    url: 'ajax/vacaciones.php?action=list_balances',
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            listColaboradores = res.data;
                            table.clear();
                            
                            let totalAcum = 0;
                            let totalTom = 0;
                            let totalDisp = 0;

                            res.data.forEach(function(row) {
                                totalAcum += row.DiasGanados;
                                totalTom += row.DiasTomados;
                                totalDisp += row.Saldo;

                                const antStr = row.AntiguedadAnios + 'a ' + row.AntiguedadMeses + 'm';
                                const saldoClass = row.Saldo >= 0 ? 'positive' : 'negative';
                                
                                const btnHistorial = `<button class="btn btn-sm btn-outline-primary me-1 py-1 px-2 btn-view-history" data-id="${row.CodEmpleado}" title="Ver Historial"><i class="fa-solid fa-clock-rotate-left"></i></button>`;
                                const btnEditar = `<button class="btn btn-sm btn-outline-success py-1 px-2 btn-add-vac" data-id="${row.CodEmpleado}" title="Registrar"><i class="fa-solid fa-plus"></i></button>`;

                                table.row.add([
                                    `<div class="fw-semibold text-white">${row.Colaborador}</div><div class="text-muted style="font-size:11px;">${row.Puesto}</div>`,
                                    row.Identificacion,
                                    row.FechaIngreso.split('-').reverse().join('/'),
                                    antStr,
                                    `<span class="badge bg-secondary">${row.TipoSalario}</span>`,
                                    row.DiasGanados.toFixed(2),
                                    row.DiasTomados.toFixed(2),
                                    `<span class="saldo-badge ${saldoClass}">${row.Saldo.toFixed(2)} días</span>`,
                                    `<div class="d-flex">${btnHistorial}${btnEditar}</div>`
                                ]);
                            });
                            
                            table.draw();

                            // Actualizar KPIs superiores
                            $('#kpiAcumulados').text(totalAcum.toFixed(2));
                            $('#kpiTomados').text(totalTom.toFixed(2));
                            $('#kpiDisponibles').text(totalDisp.toFixed(2));

                            // Rellenar selector de colaboradores en el formulario
                            const select = $('#cod_empleado');
                            select.empty().append('<option value="">Seleccione un colaborador...</option>');
                            res.data.forEach(function(row) {
                                select.append(`<option value="${row.CodEmpleado}">${row.Colaborador} (${row.Puesto})</option>`);
                            });
                        }
                    }
                });
            }

            loadBalances();

            // Pagos de vacaciones (comprobantes / banco)
            let tablePagosVacaciones = $('#tblPagosVacaciones').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                order: [[0, 'desc']],
                columnDefs: [{ orderable: false, targets: 5 }]
            });

            function loadPayments() {
                const year = $('#txtYearPagos').val();
                $.getJSON('ajax/vacaciones.php?action=list_payments&year=' + year, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', res.message || 'No se pudieron cargar los pagos.', 'error');
                        return;
                    }
                    paymentsData = res.data || [];
                    $('#badgePagosCount').text(paymentsData.length);
                    tablePagosVacaciones.clear();

                    if (paymentsData.length === 0) {
                        $('#btnPrintAllVacaciones, #btnExportBankVacaciones').prop('disabled', true);
                    } else {
                        $('#btnPrintAllVacaciones, #btnExportBankVacaciones').prop('disabled', false);
                    }

                    paymentsData.forEach(function(row, idx) {
                        const fecha = row.FechaCreacion ? row.FechaCreacion.substring(0, 10).split('-').reverse().join('/') : '';
                        const acciones = `
                            <button class="btn btn-outline-info btn-sm px-2 py-1 d-inline-flex align-items-center gap-1" onclick="viewBreakdownVac(${idx})" style="border-radius: 4px; font-size:11.5px;" title="Ver desglose del cálculo">
                                <i class="fa-solid fa-eye"></i><span>Desglose</span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm px-2 py-1 d-inline-flex align-items-center" onclick="printIndividualVacacion(${idx})" style="border-radius: 4px; font-size:11.5px;" title="Imprimir comprobante">
                                <i class="fa-solid fa-print"></i>
                            </button>`;
                        tablePagosVacaciones.row.add([
                            fecha,
                            row.Identificacion,
                            row.Colaborador,
                            row.DiasSolicitados.toFixed(2),
                            `<span class="text-success fw-bold">₡${row.MontoPagado.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span>`,
                            acciones
                        ]);
                    });
                    tablePagosVacaciones.draw();
                }).fail(function(xhr) {
                    console.error('list_payments', xhr.responseText);
                    Swal.fire('Error', 'No se pudo conectar con el servidor de pagos. Verifique que inició sesión.', 'error');
                });
            }

            $('#frmPagosVacaciones').on('submit', function(e) {
                e.preventDefault();
                loadPayments();
            });

            loadPayments();

            function showPagosTab() {
                const tabPagos = document.querySelector('[data-bs-target="#panePagos"]');
                if (tabPagos && typeof bootstrap !== 'undefined') {
                    bootstrap.Tab.getOrCreateInstance(tabPagos).show();
                }
            }
            if (window.location.hash === '#pagos') {
                showPagosTab();
            }
            $('#btnGoPagos').on('click', function(e) {
                e.preventDefault();
                window.location.hash = 'pagos';
                showPagosTab();
            });

            $('#btnPrintAllVacaciones').on('click', function() {
                const year = $('#txtYearPagos').val();
                window.open('imprimir_vacaciones.php?year=' + year, '_blank');
            });

            // 3. Manejo de Cambio del Tipo de Movimiento en Formulario
            $('#tipo_movimiento').change(function() {
                const tipo = $(this).val();
                if (tipo === 'DISFRUTE') {
                    $('#bloqueFechas').removeClass('d-none');
                    $('#fecha_inicio').prop('required', true);
                    $('#fecha_fin').prop('required', true);
                    $('#bloqueDiasManual').addClass('d-none');
                    $('#dias_manual').prop('required', false);
                    $('#bloqueMontoFinanciero').addClass('d-none');
                } else if (tipo === 'PAGO') {
                    $('#bloqueFechas').addClass('d-none');
                    $('#fecha_inicio').prop('required', false);
                    $('#fecha_fin').prop('required', false);
                    $('#bloqueDiasManual').removeClass('d-none');
                    $('#dias_manual').prop('required', true);
                    $('#bloqueMontoFinanciero').removeClass('d-none');
                } else {
                    // AJUSTE
                    $('#bloqueFechas').addClass('d-none');
                    $('#fecha_inicio').prop('required', false);
                    $('#fecha_fin').prop('required', false);
                    $('#bloqueDiasManual').removeClass('d-none');
                    $('#dias_manual').prop('required', true);
                    $('#bloqueMontoFinanciero').addClass('d-none');
                }
                triggerPreviewCalculation();
            });

            // 4. Gatillar cálculo preliminar en vivo
            $('#fecha_inicio, #fecha_fin, #dias_manual, #cod_empleado').change(function() {
                triggerPreviewCalculation();
            });

            function triggerPreviewCalculation() {
                const cod = $('#cod_empleado').val();
                const tipo = $('#tipo_movimiento').val();
                const start = $('#fecha_inicio').val();
                const end = $('#fecha_fin').val();
                const manual = $('#dias_manual').val();

                if (!cod) {
                    $('#dias_calculados').val('0.00');
                    $('#monto_pagar').val('0.00');
                    $('#monto_pagar_num').val(0);
                    return;
                }

                let url = `ajax/vacaciones.php?action=calculate_preview&cod_empleado=${cod}&tipo_movimiento=${tipo}`;
                if (tipo === 'DISFRUTE') {
                    if (!start || !end) return;
                    url += `&fecha_inicio=${start}&fecha_fin=${end}`;
                } else {
                    if (!manual || parseFloat(manual) <= 0) return;
                    url += `&dias=${manual}`;
                }

                $.getJSON(url, function(res) {
                    if (res.success) {
                        $('#dias_calculados').val(res.dias.toFixed(2));
                        $('#monto_pagar').val(res.monto.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                    }
                });
            }

            // 5. Abrir Modal de Registro desde el Header
            $('#btnNewVacation').click(function() {
                $('#formVacation')[0].reset();
                $('#tipo_movimiento').val('DISFRUTE').trigger('change');
                $('#modalVacationLabel span').text('Registrar Movimiento de Vacaciones');
                $('#modalVacation').modal('show');
            });

            // 6. Registrar Vacaciones con Acción de Personal Integrada
            $(document).on('click', '.btn-add-vac', function() {
                const id = $(this).data('id');
                $('#formVacation')[0].reset();
                $('#cod_empleado').val(id).trigger('change');
                $('#tipo_movimiento').val('DISFRUTE').trigger('change');
                $('#modalVacation').modal('show');
            });

            $('#formVacation').submit(function(e) {
                e.preventDefault();
                const empId = $('#cod_empleado').val();
                const selectedEmp = listColaboradores.find(x => x.CodEmpleado == empId);
                activeColaborador = selectedEmp;

                const formData = {
                    action: 'save_request',
                    cod_empleado: empId,
                    tipo_movimiento: $('#tipo_movimiento').val(),
                    fecha_inicio: $('#fecha_inicio').val(),
                    fecha_fin: $('#fecha_fin').val(),
                    dias_solicitados: $('#tipo_movimiento').val() === 'DISFRUTE' ? $('#dias_calculados').val() : $('#dias_manual').val(),
                    observaciones: $('#observaciones').val(),
                    monto_pagado: $('#tipo_movimiento').val() === 'PAGO'
                        ? ($('#monto_pagar_num').val() || parseMontoColonesCR($('#monto_pagar').val()))
                        : 0.00
                };

                $.ajax({
                    url: 'ajax/vacaciones.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalVacation').modal('hide');
                            loadBalances();
                            if (formData.tipo_movimiento === 'PAGO') {
                                loadPayments();
                            }

                            if (formData.tipo_movimiento === 'DISFRUTE') {
                                // Flujo de Acción de Personal Automatizada
                                Swal.fire({
                                    title: '¡Vacaciones Registradas!',
                                    text: '¿Desea generar e imprimir la Acción de Personal en este momento?',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, generar e imprimir',
                                    cancelButtonText: 'No, solo guardar',
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#aaa'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Rellenar formulario invisible de Acción de Personal
                                        $('#paEmpName').val(selectedEmp.Colaborador);
                                        $('#paEmpCedula').val(selectedEmp.Identificacion);
                                        $('#paEmpNacimiento').val(selectedEmp.FechaNacimiento);
                                        $('#paEmpTelefono').val(selectedEmp.Telefono || 'N/A');
                                        $('#paEmpDireccion').val(selectedEmp.Direccion || 'N/A');
                                        $('#paEmpPuesto').val(selectedEmp.Puesto || 'N/A');
                                        $('#paEmpDepto').val(selectedEmp.Departamento || 'N/A');
                                        $('#paEmpSucursal').val(selectedEmp.Sucursal || 'N/A');
                                        $('#paEmpHorario').val(selectedEmp.Horario || 'N/A');
                                        $('#paEmpEstado').val('ACTIVO');
                                        $('#paEmpSalarioActual').val(selectedEmp.SalarioBase);
                                        $('#paObservaciones').val(`Toma de ${formData.dias_solicitados} días de vacaciones del ${formData.fecha_inicio.split('-').reverse().join('/')} al ${formData.fecha_fin.split('-').reverse().join('/')}. \nObservaciones: ${formData.observaciones}`);

                                        // Enviar formulario
                                        $('#personalActionForm').submit();
                                    }
                                });
                            } else if (formData.tipo_movimiento === 'PAGO') {
                                // Flujo de Comprobante de Pago de Vacaciones
                                Swal.fire({
                                    title: '¡Compensación Registrada!',
                                    text: '¿Desea generar e imprimir el Comprobante Oficial de Pago de Vacaciones?',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, imprimir comprobante',
                                    cancelButtonText: 'No, solo guardar',
                                    confirmButtonColor: '#2ecc71',
                                    cancelButtonColor: '#aaa'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.open('imprimir_vacaciones.php?id=' + res.codigo_movimiento, '_blank');
                                    }
                                });
                            } else {
                                Swal.fire('¡Éxito!', res.message, 'success');
                            }
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            });

            // 7. Cargar e Historial Individual
            $(document).on('click', '.btn-view-history', function() {
                const id = $(this).data('id');
                activeColaborador = listColaboradores.find(x => x.CodEmpleado == id);
                
                $('#historyColaboradorName').text(activeColaborador.Colaborador);
                $('#historyColaboradorDetalle').text(`Fecha Ingreso: ${activeColaborador.FechaIngreso.split('-').reverse().join('/')} | Antigüedad: ${activeColaborador.AntiguedadAnios} años, ${activeColaborador.AntiguedadMeses} meses | Puesto: ${activeColaborador.Puesto}`);

                $.ajax({
                    url: 'ajax/vacaciones.php?action=get_history&cod_empleado=' + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            const tbody = $('#tableHistory tbody');
                            tbody.empty();

                            if (res.data.length === 0) {
                                tbody.append('<tr><td colspan="8" class="text-center text-muted">No existen movimientos registrados para este colaborador.</td></tr>');
                            } else {
                                res.data.forEach(function(row) {
                                    const dateReg = row.FechaCreacion.substring(0, 10).split('-').reverse().join('/') + ' ' + row.FechaCreacion.substring(11, 16);
                                    let datesStr = 'N/A';
                                    if (row.FechaInicio) {
                                        datesStr = row.FechaInicio.split('-').reverse().join('/') + ' al ' + row.FechaFin.split('-').reverse().join('/');
                                    }

                                    const typeBadge = row.TipoMovimiento === 'DISFRUTE' ? '<span class="badge bg-primary">DISFRUTE</span>' :
                                                      row.TipoMovimiento === 'PAGO' ? '<span class="badge bg-success">VENTA / PAGO</span>' :
                                                      '<span class="badge bg-warning">AJUSTE</span>';

                                    const montoStr = row.MontoPagado > 0 ? '₡' + row.MontoPagado.toLocaleString('es-CR', { minimumFractionDigits: 2 }) : 'N/A';
                                    
                                    let btnPrint = '';
                                    if (row.TipoMovimiento === 'PAGO') {
                                        btnPrint = `<button class="btn btn-sm btn-outline-success py-0 px-2 me-1 btn-print-req" data-id="${row.Codigo}" title="Imprimir Comprobante"><i class="fa-solid fa-print"></i></button>`;
                                    }
                                    
                                    const btnAnular = `<button class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-req" data-id="${row.Codigo}" title="Anular Movimiento"><i class="fa-solid fa-trash-can"></i></button>`;

                                    tbody.append(`
                                        <tr>
                                            <td>${dateReg}</td>
                                            <td>${typeBadge}</td>
                                            <td class="fw-semibold">${datesStr}</td>
                                            <td class="fw-bold text-white">${row.DiasSolicitados.toFixed(2)} días</td>
                                            <td class="text-success fw-bold">${montoStr}</td>
                                            <td style="font-size:12.5px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${row.Observaciones}">${row.Observaciones}</td>
                                            <td>${row.UsuarioCreacion}</td>
                                            <td>${btnPrint}${btnAnular}</td>
                                        </tr>
                                    `);
                                });
                            }
                            $('#modalHistory').modal('show');
                        }
                    }
                });
            });

            // 7.5 Manejo de click en botón de reimpresión de comprobante
            $(document).on('click', '.btn-print-req', function() {
                const id = $(this).data('id');
                window.open('imprimir_vacaciones.php?id=' + id, '_blank');
            });

            // 8. Anular Movimiento de Vacaciones
            $(document).on('click', '.btn-delete-req', function() {
                const id = $(this).data('id');
                
                Swal.fire({
                    title: '¿Está seguro de anular?',
                    text: "Esta acción devolverá la cantidad de días al saldo del colaborador y marcará el registro como inactivo.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Sí, anular movimiento',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'ajax/vacaciones.php',
                            type: 'POST',
                            data: { action: 'delete_request', id: id },
                            dataType: 'json',
                            success: function(res) {
                                if (res.success) {
                                    $('#modalHistory').modal('hide');
                                    loadBalances();
                                    loadPayments();
                                    Swal.fire('¡Anulado!', res.message, 'success');
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // 9. Interceptar e Imprimir Acción de Personal de Vacaciones dinámicamente con Blanco y Negro
            $('#personalActionForm').on('submit', function(e) {
                e.preventDefault();
                
                const empName = $('#paEmpName').val();
                const empCedula = $('#paEmpCedula').val();
                const empNacimiento = $('#paEmpNacimiento').val();
                const empTelefono = $('#paEmpTelefono').val() || 'N/A';
                const empDireccion = $('#paEmpDireccion').val() || 'N/A';
                const empPuesto = $('#paEmpPuesto').val() || 'N/A';
                const empDepto = $('#paEmpDepto').val() || 'N/A';
                const empSucursal = $('#paEmpSucursal').val() || 'N/A';
                const empHorario = $('#paEmpHorario').val() || 'N/A';
                const empEstado = $('#paEmpEstado').val() || 'ACTIVO';
                const empSalarioActual = parseFloat($('#paEmpSalarioActual').val() || 0);
                const observaciones = $('#paObservaciones').val();

                const area = 'Operativa';
                const motivo = 'Vacaciones';
                const fechaConf = new Date().toISOString().split('T')[0];
                const fechaEfec = new Date().toISOString().split('T')[0];
                const nombramiento = 'No aplica';
                const contratoAt = 'Tiempo Completo';
                const nacionalidad = 'Costarricense';

                const empEmpresa = (activeColaborador && activeColaborador.EmpresaNombre) || 'Corporación Allison S.A.';
                const empEmpresaComercial = (activeColaborador && activeColaborador.EmpresaNombreComercial) || (activeColaborador && activeColaborador.EmpresaNombre) || 'LATINSOFT COSTA RICA';
                const empEmpresaCedula = (activeColaborador && activeColaborador.EmpresaCedulaJuridica) || '3-101-998877';

                const ccssActual = empSalarioActual * 0.1067;
                const netoActual = empSalarioActual - ccssActual;

                const formatDate = (dateStr) => {
                    if (!dateStr) return '';
                    const p = dateStr.split('-');
                    if (p.length < 3) return dateStr;
                    return p[2] + '/' + p[1] + '/' + p[0];
                };

                const formatColones = (num) => {
                    return '₡' + num.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                const printWindow = window.open('', '_blank', 'width=900,height=950');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Acción de Personal - ${empName}</title>
                        <meta charset="utf-8">
                        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                        <style>
                            body {
                                font-family: 'Montserrat', sans-serif;
                                color: #1e293b;
                                background-color: #ffffff;
                                padding: 30px;
                                margin: 0;
                                font-size: 11px;
                                line-height: 1.5;
                            }
                            .letterhead {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                border-bottom: 3px solid #1e3a8a;
                                padding-bottom: 15px;
                                margin-bottom: 15px;
                            }
                            .logo-container {
                                display: flex;
                                align-items: center;
                                gap: 12px;
                            }
                            .logo-text {
                                font-weight: 700;
                                font-size: 20px;
                                text-transform: uppercase;
                                letter-spacing: 1px;
                                color: #0f172a;
                            }
                            .logo-subtext {
                                font-size: 11px;
                                color: #64748b;
                                font-weight: 600;
                            }
                            .document-title {
                                text-align: right;
                            }
                            .document-title h2 {
                                margin: 0;
                                font-size: 18px;
                                font-weight: 700;
                                color: #0f172a;
                                letter-spacing: 0.5px;
                            }
                            .section-header {
                                background-color: #1e3a8a;
                                color: #ffffff;
                                font-weight: 700;
                                text-transform: uppercase;
                                padding: 6px 12px;
                                font-size: 10.5px;
                                margin-top: 18px;
                                margin-bottom: 10px;
                                letter-spacing: 0.75px;
                                border-radius: 6px;
                                box-shadow: 0 2px 4px rgba(30, 58, 138, 0.1);
                            }
                            table {
                                width: 100%;
                                border-collapse: separate;
                                border-spacing: 0;
                                margin-bottom: 10px;
                                border-radius: 6px;
                                overflow: hidden;
                                border: 1px solid #cbd5e1;
                            }
                            td, th {
                                padding: 6px 10px;
                                text-align: left;
                                border-bottom: 1px solid #cbd5e1;
                                border-right: 1px solid #cbd5e1;
                            }
                            td:last-child, th:last-child {
                                border-right: none;
                            }
                            tr:last-child td {
                                border-bottom: none;
                            }
                            .no-border {
                                border: none;
                                border-radius: 0;
                                overflow: visible;
                            }
                            .no-border td {
                                border: none;
                                padding: 5px 8px;
                            }
                            .label {
                                font-weight: 700;
                                color: #475569;
                                width: 22%;
                            }
                            .value {
                                color: #0f172a;
                                border-bottom: 1px dashed #94a3b8;
                            }
                            .checkbox-container {
                                display: inline-flex;
                                align-items: center;
                                margin-right: 15px;
                            }
                            .checkbox-box {
                                width: 15px;
                                height: 15px;
                                border: 1px solid #475569;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: bold;
                                font-size: 10px;
                                margin-right: 8px;
                                background-color: #f8fafc;
                                border-radius: 4px;
                                color: #1e3a8a;
                            }
                            .observations-box {
                                border: 1px solid #cbd5e1;
                                padding: 12px;
                                background-color: #f8fafc;
                                min-height: 50px;
                                font-style: italic;
                                font-size: 11.5px;
                                border-radius: 8px;
                                color: #334155;
                                white-space: pre-wrap;
                            }
                            .signatures-container {
                                margin-top: 45px;
                                display: flex;
                                justify-content: space-between;
                            }
                            .signature-block {
                                width: 45%;
                                text-align: center;
                            }
                            .signature-line {
                                border-top: 1.5px solid #475569;
                                margin-top: 50px;
                                padding-top: 6px;
                                font-weight: 700;
                                font-size: 11px;
                                color: #0f172a;
                            }
                            .signature-sub {
                                font-size: 10px;
                                color: #64748b;
                            }
                            .btn-print-action {
                                background-color: #1e3a8a;
                                color: white;
                                border: none;
                                padding: 8px 18px;
                                font-size: 12px;
                                font-weight: bold;
                                border-radius: 6px;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                                box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.2);
                            }
                            @media print {
                                .no-print-toolbar {
                                    display: none !important;
                                }
                                body {
                                    padding: 0;
                                }
                            }

                            /* Estilos Blanco y Negro */
                            .bw-mode {
                                background-color: #ffffff !important;
                                color: #000000 !important;
                            }
                            .bw-mode .letterhead {
                                border-bottom: 3px solid #000000 !important;
                            }
                            .bw-mode .logo-text, .bw-mode .logo-subtext {
                                color: #000000 !important;
                            }
                            .bw-mode .section-header {
                                background-color: #000000 !important;
                                color: #ffffff !important;
                                box-shadow: none !important;
                            }
                            .bw-mode table {
                                border: 1px solid #000000 !important;
                            }
                            .bw-mode td, .bw-mode th {
                                border-bottom: 1px solid #000000 !important;
                                border-right: 1px solid #000000 !important;
                            }
                            .bw-mode .checkbox-box {
                                border: 1px solid #000000 !important;
                                color: #000000 !important;
                                background-color: #ffffff !important;
                            }
                            .bw-mode .observations-box {
                                border: 1px solid #000000 !important;
                                background-color: #ffffff !important;
                                color: #000000 !important;
                            }
                            .bw-mode .signature-line {
                                border-top: 1.5px solid #000000 !important;
                                color: #000000 !important;
                            }
                            .bw-mode .signature-sub {
                                color: #000000 !important;
                            }
                            .bw-mode .value {
                                color: #000000 !important;
                                border-bottom: 1px dashed #000000 !important;
                            }
                            .bw-mode .label {
                                color: #000000 !important;
                            }
                        </style>
                    </head>
                    <body>
                        <div style="display: flex; gap: 10px; margin-bottom: 18px;" class="no-print-toolbar">
                            <button class="btn-print-action" id="btnToggleBW"><i class="fa-solid fa-circle-half-stroke"></i> Modo Blanco y Negro</button>
                            <button class="btn-print-action" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Documento</button>
                        </div>
                        
                        <div class="letterhead">
                            <div class="logo-container">
                                <img src="img/logo_bahia.png" alt="Logo" style="height: 55px; max-height: 55px; object-fit: contain; margin-right: 10px;">
                                <div>
                                    <div class="logo-text">${empEmpresaComercial}</div>
                                    <div class="logo-subtext">${empEmpresa}</div>
                                </div>
                            </div>
                            <div class="document-title">
                                <h2>ACCIÓN DE PERSONAL</h2>
                            </div>
                        </div>

                        <!-- METADATA SUPERIOR -->
                        <table class="no-border">
                            <tr>
                                <td class="label">Área:</td>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${area === 'Administrativa' ? 'X' : ''}</div> Administrativa
                                    </div>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${area === 'Operativa' ? 'X' : ''}</div> Operativa
                                    </div>
                                </td>
                                <td class="label">Fecha Confección:</td>
                                <td class="value">${formatDate(fechaConf)}</td>
                            </tr>
                            <tr>
                                <td class="label">Efectiva del:</td>
                                <td class="value">${formatDate(fechaEfec)}</td>
                                <td class="label">Contrato/Jornada:</td>
                                <td class="value">${contratoAt}</td>
                            </tr>
                        </table>

                        <div class="section-header">Motivo de la Acción</div>
                        <table class="no-border">
                            <tr>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Ingreso' ? 'X' : ''}</div> Ingreso
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Aumento Salario' ? 'X' : ''}</div> Aumento Salario
                                    </div>
                                </td>
                                <td rowspan="4" style="border: 2px solid #333; padding: 10px; width: 35%; vertical-align: top; background-color: #fafafa;">
                                    <strong>Tipo de nombramiento:</strong><br><br>
                                    <div class="checkbox-container" style="display: flex; margin-bottom: 8px;">
                                        <div class="checkbox-box">${nombramiento === 'Temporal' ? 'X' : ''}</div> Temporal
                                    </div><br>
                                    <div class="checkbox-container" style="display: flex; margin-bottom: 8px;">
                                        <div class="checkbox-box">${nombramiento === 'Indefinido' ? 'X' : ''}</div> Indefinido
                                    </div><br>
                                    <div class="checkbox-container" style="display: flex;">
                                        <div class="checkbox-box">${nombramiento === 'No aplica' ? 'X' : ''}</div> No aplica
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Cambio de cargo' ? 'X' : ''}</div> Cambio de cargo
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Despido' ? 'X' : ''}</div> Despido
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Ascenso' ? 'X' : ''}</div> Ascenso
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Renuncia' ? 'X' : ''}</div> Renuncia
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Cambio Moneda Salario' ? 'X' : ''}</div> Cambio Moneda Salario
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Vacaciones' ? 'X' : ''}</div> Vacaciones
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="checkbox-container">
                                        <div class="checkbox-box">${motivo === 'Otros' ? 'X' : ''}</div> Otros
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </table>

                        <div class="section-header">Datos Personales</div>
                        <table class="no-border">
                            <tr>
                                <td class="label">Nombre:</td>
                                <td class="value" style="width: 40%">${empName}</td>
                                <td class="label" style="width: 15%">Cédula:</td>
                                <td class="value">${empCedula}</td>
                            </tr>
                            <tr>
                                <td class="label">Nacionalidad:</td>
                                <td class="value">${nacionalidad}</td>
                                <td class="label">Teléfono:</td>
                                <td class="value">${empTelefono}</td>
                            </tr>
                            <tr>
                                <td class="label">Fecha Nacimiento:</td>
                                <td class="value">${formatDate(empNacimiento)}</td>
                                <td class="label">Fecha Ingreso:</td>
                                <td class="value">${formatDate(activeColaborador ? activeColaborador.FechaIngreso : '')}</td>
                            </tr>
                        </table>

                        <table style="width: 100%; border: none; border-collapse: collapse; margin-top: 10px; table-layout: fixed;">
                            <tr>
                                <td style="width: 49%; vertical-align: top; padding: 0; border: none; background: transparent;">
                                    <div class="section-header" style="margin-top: 0;">Situación Actual</div>
                                    <table class="no-border" style="width: 100%;">
                                        <tr>
                                            <td class="label" style="width: 40%">Puesto:</td>
                                            <td class="value">${empPuesto}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Departamento:</td>
                                            <td class="value">${empDepto}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Sucursal:</td>
                                            <td class="value">${empSucursal}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Horario/Jornada:</td>
                                            <td class="value">${empHorario}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Estado Laboral:</td>
                                            <td class="value">${empEstado}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Salario Bruto:</td>
                                            <td class="value">${formatColones(empSalarioActual)}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">CCSS 10.67%:</td>
                                            <td class="value">${formatColones(ccssActual)}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Salario Neto:</td>
                                            <td class="value" style="font-weight: 700;">${formatColones(netoActual)}</td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 2%; border: none; background: transparent;"></td>
                                <td style="width: 49%; vertical-align: top; padding: 0; border: none; background: transparent;">
                                    <div class="section-header" style="margin-top: 0;">Situación Propuesta</div>
                                    <table class="no-border" style="width: 100%;">
                                        <tr>
                                            <td class="label" style="width: 40%">Puesto:</td>
                                            <td class="value">${empPuesto}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Departamento:</td>
                                            <td class="value">${empDepto}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Sucursal:</td>
                                            <td class="value">${empSucursal}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Horario/Jornada:</td>
                                            <td class="value">${empHorario}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Estado Laboral:</td>
                                            <td class="value">${empEstado}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Salario Bruto:</td>
                                            <td class="value">${formatColones(empSalarioActual)}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">CCSS 10.67%:</td>
                                            <td class="value">${formatColones(ccssActual)}</td>
                                        </tr>
                                        <tr>
                                            <td class="label">Salario Neto:</td>
                                            <td class="value" style="font-weight: 700;">${formatColones(netoActual)}</td>
                                        </tr>
                                        <tr>
                                            <td class="label" style="color: #b30000;">Aumento:</td>
                                            <td class="value" style="font-weight: 700; color: #b30000;">0.00%</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div class="section-header">Observaciones</div>
                        <div class="observations-box">${observaciones}</div>

                        <div class="signatures-container">
                            <div class="signature-block">
                                <div class="signature-line">Aprobado por</div>
                                <div class="signature-sub"><b>${currentUserFullname}</b><br>Director Administrativo / Recursos Humanos</div>
                            </div>
                            <div class="signature-block">
                                <div class="signature-line">Recibe conforme</div>
                                <div class="signature-sub"><b>${empName}</b><br>Empleado Colaborador</div>
                            </div>
                        </div>

                        <script>
                            document.getElementById('btnToggleBW').addEventListener('click', function() {
                                document.body.classList.toggle('bw-mode');
                                if (document.body.classList.contains('bw-mode')) {
                                    this.innerHTML = '<i class="fa-solid fa-circle-half-stroke"></i> Modo Color';
                                    this.style.backgroundColor = '#2563eb';
                                } else {
                                    this.innerHTML = '<i class="fa-solid fa-circle-half-stroke"></i> Modo Blanco y Negro';
                                    this.style.backgroundColor = '#1e3a8a';
                                }
                            });
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            });
        });
    </script>
</body>
</html>
