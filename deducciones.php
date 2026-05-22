<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO DE DEDUCCIONES PROGRAMADAS Y PRÉSTAMOS (DEDUCCIONES.PHP)
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
    <title>Deducciones del Personal - PlanillaCR ERP</title>
    
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

        .btn-table-action:hover {
            color: #ffffff;
        }

        .btn-table-edit:hover {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }

        .btn-table-delete:hover {
            background-color: var(--danger) !important;
            border-color: var(--danger) !important;
        }

        /* Swiper Switch custom */
        .form-switch .form-check-input {
            width: 45px;
            height: 22px;
            cursor: pointer;
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout">
        
        <!-- SIDEBAR FIJA -->
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
                <li class="sidebar-item active">
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

        <!-- ÁREA DE CONTENIDO PRINCIPAL -->
        <main class="main-dashboard">
            
            <!-- Header superior -->
            <header class="dashboard-header">
                <div class="welcome-msg">
                    <h1>Deducciones del Personal</h1>
                    <p>Deducciones fijas, voluntarias y amortización automática de préstamos</p>
                </div>
                
                <div class="header-actions">
                    <!-- Configurar Deducción -->
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewDeduction" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-plus"></i>
                        <span>Configurar Deducción</span>
                    </button>

                    <!-- Cargar desde Excel (Asociación Solidarista / Otros) -->
                    <button class="btn btn-success px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnImportExcelDeducciones" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);">
                        <i class="fa-regular fa-file-excel"></i>
                        <span>Cargar desde Excel</span>
                    </button>
                    
                    <div class="date-badge">
                        <i class="fa-regular fa-calendar"></i>
                        <span id="currentDate"><?php echo date('d/m/Y'); ?></span>
                    </div>
                    
                    <button class="theme-toggle-btn" id="themeToggle" style="position: static;" title="Cambiar Tema Visual">
                        <i class="fa-solid fa-moon" id="themeIcon"></i>
                    </button>
                </div>
            </header>

            <!-- KPIs de Deducciones -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Deducciones Activas</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                            <i class="fa-solid fa-circle-nodes"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiTotalDeducciones">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-toggle-on"></i> Programadas</span>
                        <span class="kpi-desc">Deducciones vigentes</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Monto Proyectado Mensual</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(239, 68, 68, 0.1); color: var(--danger);">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiProyeccionMonto">₡0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral" style="color: var(--danger);"><i class="fa-solid fa-hand-holding-dollar"></i> Retenciones</span>
                        <span class="kpi-desc">Suma mensual estimada (montos fijos)</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Saldo Cartera Préstamos</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                            <i class="fa-solid fa-piggy-bank"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiCarteraPrestamos">₡0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral" style="color: var(--warning);"><i class="fa-solid fa-shield-halved"></i> Amortizable</span>
                        <span class="kpi-desc">Capital restante por cobrar</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Porcentaje Medio</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiPorcentajeMedio">0.00%</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up" style="color: #8b5cf6;"><i class="fa-solid fa-chart-simple"></i> Promedio</span>
                        <span class="kpi-desc">De deducciones porcentuales</span>
                    </div>
                </div>
            </section>

            <!-- Tabla de Datos -->
            <div class="table-responsive">
                <table id="tblDeductions" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Colaborador</th>
                            <th>Descripción</th>
                            <th>Rubro</th>
                            <th>Tipo</th>
                            <th>Monto / %</th>
                            <th>Saldo Restante</th>
                            <th>Estado</th>
                            <th class="text-center" style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Poblado asíncronamente con jQuery AJAX -->
                    </tbody>
                </table>
            </div>
            
        </main>
    </div>

    <!-- MODAL DE CONFIGURAR / EDITAR DEDUCCIÓN -->
    <div class="modal fade" id="modalDeduction" tabindex="-1" aria-labelledby="modalDeductionTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalDeductionTitle"><i class="fa-solid fa-tags text-primary me-2"></i>Configurar Deducción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmDeduction" novalidate>
                    <div class="modal-body px-4 py-3">
                        <input type="hidden" name="cod_deduccion_empleado" id="txtCodDeduccionEmpleado" value="0">
                        <input type="hidden" name="action" id="txtAction" value="save_deduction">
                        <input type="hidden" name="cod_empresa" id="txtCodEmpresa" value="1">
                        
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="txtCedulaRapida" class="form-label-desc">Cédula Rápida</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="txtCedulaRapida" placeholder="Digite ID y ENTER" style="background-color: var(--bg-surface-elevated); border-color: var(--border-color); color: var(--text-primary);">
                                    <button class="btn btn-outline-secondary" type="button" id="btnBuscarCedula"><i class="fa-solid fa-magnifying-glass"></i></button>
                                </div>
                                <small id="lblCedulaStatus" class="form-text mt-1 d-block" style="font-size: 11.5px; height: 16px; font-weight: 600;"></small>
                            </div>
                            <div class="col-md-7 mb-3">
                                <label for="ddlEmpleado" class="form-label-desc">Colaborador <span class="text-danger">*</span></label>
                                <select class="form-select" name="cod_empleado" id="ddlEmpleado" required>
                                    <option value="" disabled selected>-- Seleccione el Colaborador --</option>
                                    <!-- Cargado dinámicamente -->
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ddlRubro" class="form-label-desc">Clasificación (Rubro) <span class="text-danger">*</span></label>
                                <select class="form-select" name="cod_rubro" id="ddlRubro" required>
                                    <!-- Cargado dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtDescripcion" class="form-label-desc">Descripción Deducción <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="descripcion" id="txtDescripcion" placeholder="Ej: Préstamo BAC, Ahorro Coop" required maxlength="150">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ddlTipoDeduccion" class="form-label-desc">Tipo de Cálculo <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo_deduccion" id="ddlTipoDeduccion" required>
                                    <option value="MONTO" selected>MONTO FIJO (₡)</option>
                                    <option value="PORCENTAJE">PORCENTAJE (%)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="wrapperMonto">
                                <label for="txtMonto" class="form-label-desc">Monto por Nómina (₡) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="monto" id="txtMonto" placeholder="Ej: 25000.00" step="100" min="0" value="0.00">
                            </div>

                            <div class="col-md-6 mb-3" id="wrapperPorcentaje" style="display: none;">
                                <label for="txtPorcentaje" class="form-label-desc">Porcentaje de Sueldo (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="porcentaje" id="txtPorcentaje" placeholder="Ej: 5.00" step="0.01" min="0" max="100" value="0.00">
                            </div>
                        </div>

                        <!-- Switch de Préstamo Amortizable -->
                        <div class="form-check form-switch mb-3 mt-2 d-flex align-items-center gap-3">
                            <input class="form-check-input ms-0" type="checkbox" role="switch" id="chkEsPrestamo">
                            <label class="form-check-label form-label-desc mb-0" style="cursor: pointer;" for="chkEsPrestamo">¿Es un Préstamo o Saldo Amortizable?</label>
                        </div>

                        <!-- Sección de Amortización -->
                        <div class="row" id="wrapperAmortizacion" style="display: none; background-color: rgba(var(--primary-rgb), 0.03); border: 1px dashed var(--border-color); border-radius: var(--border-radius-md); padding: 15px 5px 5px 5px; margin: 0 1px 15px 1px;">
                            <div class="col-md-6 mb-3">
                                <label for="txtMontoTotalOriginal" class="form-label-desc">Principal Original (₡)</label>
                                <input type="number" class="form-control" name="monto_total_original" id="txtMontoTotalOriginal" placeholder="Ej: 500000" step="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtSaldoRestante" class="form-label-desc">Saldo Restante Actual (₡)</label>
                                <input type="number" class="form-control" name="saldo_restante" id="txtSaldoRestante" placeholder="Mismo del principal o menor">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3" id="wrapperEstado" style="display: none;">
                                <label for="ddlEstado" class="form-label-desc">Estado del Mantenimiento</label>
                                <select class="form-select" name="estado" id="ddlEstado">
                                    <option value="ACTIVO">ACTIVO (Aplica en nómina)</option>
                                    <option value="INACTIVO">INACTIVO (Suspendido)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: var(--border-radius-md); font-size: 13.5px;" id="btnSaveDeduction">Guardar Deducción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE IMPORTAR DEDUCCIONES / PRÉSTAMOS DESDE EXCEL -->
    <div class="modal fade" id="modalImportarDeducciones" tabindex="-1" aria-labelledby="modalImportarDeduccionesTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border: 1px solid rgba(25, 135, 84, 0.3);">
                <div class="modal-header px-4 py-3" style="border-bottom: 1px solid var(--border-light); background-color: rgba(25, 135, 84, 0.03);">
                    <h5 class="modal-title fw-bold" id="modalImportarDeduccionesTitle" style="color: var(--success);"><i class="fa-regular fa-file-excel text-success me-2"></i>Cargar Deducciones desde Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    
                    <!-- PASO 1: Pegar Datos de Excel -->
                    <div id="step1Deducciones">
                        <div class="alert alert-info py-2 px-3 mb-3" style="background-color: rgba(0, 86, 179, 0.1); border-color: rgba(0, 86, 179, 0.2); color: var(--text-primary); font-size: 13px;">
                            <i class="fa-solid fa-circle-info me-2 text-info"></i>
                            <strong>Instrucciones:</strong> Copie las columnas de su hoja de Excel (sin encabezados) y péguelas en el cuadro de texto.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ddlRubroImportar" class="form-label-desc">Clasificación (Rubro) a Aplicar <span class="text-danger">*</span></label>
                                <select class="form-select" id="ddlRubroImportar" required>
                                    <!-- Cargado dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ddlTipoCargaDeduccion" class="form-label-desc">Tipo de Estructura de Columnas <span class="text-danger">*</span></label>
                                <select class="form-select" id="ddlTipoCargaDeduccion">
                                    <option value="APORTACION" selected>APORTACIÓN RECURRENTE (Cédula | Monto | Observación)</option>
                                    <option value="PRESTAMO">PRÉSTAMO AMORTIZABLE (Cédula | Cuota | Principal Original | Observación)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="txtExcelPasteDeducciones" class="form-label-desc">Área de Pegado (Excel)</label>
                            <textarea class="form-control font-monospace" id="txtExcelPasteDeducciones" rows="8" placeholder="Pegue aquí sus celdas de Excel...&#10;Ej. Aportaciones:&#10;1-1234-5678&#9;15000.00&#9;Aporte Solidarista Mayo&#10;Ej. Préstamos:&#10;1-1234-5678&#9;25000.00&#9;100000.00&#9;Préstamo Personal Solidarista" style="font-size: 12.5px; background-color: var(--bg-main); border-color: var(--border-color); color: var(--text-primary);"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2" id="btnParseExcelDeducciones" style="border-radius: var(--border-radius-md); font-size: 13.5px;">
                                <i class="fa-solid fa-magnifying-glass-chart"></i>
                                <span>Analizar y Validar Datos</span>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 2: Tabla de Previsualización Interactiva -->
                    <div id="step2Deducciones" style="display: none;">
                        <h6 class="fw-bold mb-3 d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-list-check text-primary me-2"></i>Vista Previa de Deducciones Analizadas</span>
                            <span class="badge bg-secondary" id="lblTipoCargaBadge" style="font-size: 11px; padding: 5px 10px;">APORTACIONES</span>
                        </h6>

                        <div class="table-responsive p-0 border-0 mb-3" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-dark table-hover table-striped mb-0" id="tblPreviewDeducciones" style="font-size: 12.5px;">
                                <thead class="sticky-top bg-dark">
                                    <tr id="tblPreviewDeduccionesHeader">
                                        <!-- Dinámico -->
                                    </tr>
                                </thead>
                                <tbody id="tblPreviewDeduccionesBody">
                                    <!-- Dinámico -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Tarjetas de Stats Rápidas -->
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center" style="background-color: rgba(var(--primary-rgb), 0.05); border-color: var(--border-color) !important;">
                                    <span class="d-block text-muted" style="font-size: 10px; text-transform: uppercase;">Total Filas</span>
                                    <strong class="fs-6" id="statTotalDeducciones">0</strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center" style="background-color: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2) !important;">
                                    <span class="d-block text-success" style="font-size: 10px; text-transform: uppercase;">Válidas</span>
                                    <strong class="fs-6 text-success" id="statValidasDeducciones">0</strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center" style="background-color: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2) !important;">
                                    <span class="d-block text-danger" style="font-size: 10px; text-transform: uppercase;">Erróneas</span>
                                    <strong class="fs-6 text-danger" id="statErroresDeducciones">0</strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 border rounded text-center" style="background-color: rgba(255, 193, 7, 0.05); border-color: rgba(255, 193, 7, 0.2) !important;">
                                    <span class="d-block text-warning" style="font-size: 10px; text-transform: uppercase;">Monto Proyectado</span>
                                    <strong class="fs-6 text-warning" id="statMontoDeducciones">₡0.00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary px-4 py-2" id="btnBackToStep1Deducciones" style="border-radius: var(--border-radius-md); font-size: 13.5px;">
                                <i class="fa-solid fa-arrow-left me-2"></i>Modificar Pegado
                            </button>
                            <button type="button" class="btn btn-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2" id="btnProcessImportDeducciones" style="border-radius: var(--border-radius-md); font-size: 13.5px;">
                                <i class="fa-solid fa-file-import"></i>
                                <span>Procesar Importación</span>
                            </button>
                        </div>
                    </div>

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

        function showConfigModal() {
            Swal.fire({
                title: 'Configuración del ERP',
                text: 'Configuración general de parámetros legales de Costa Rica (Tablas de Renta de Ministerio de Hacienda y Tasas Obrero/Patronal de la CCSS) parametrizadas en la base de datos.',
                icon: 'info',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#0056b3',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            });
        }

        // CONTROLADOR DE DATOS DE DEDUCCIONES
        let tableDeductions;
        let employeesList = [];

        $(document).ready(function() {
            // Inicializar DataTables
            tableDeductions = $('#tblDeductions').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: 8 }
                ]
            });

            // Cargar Catálogos
            loadEmployees();
            loadRubros();
            loadDeductionsData();

            // Abrir Modal de Creación
            $('#btnAddNewDeduction').click(function() {
                $('#frmDeduction')[0].reset();
                $('#txtCodDeduccionEmpleado').val('0');
                $('#txtCedulaRapida').val('');
                $('#lblCedulaStatus').text('');
                $('#ddlEmpleado').prop('disabled', false);
                $('#ddlRubro').prop('disabled', false);
                $('#ddlTipoDeduccion').trigger('change');
                $('#chkEsPrestamo').prop('checked', false).trigger('change');
                $('#wrapperEstado').hide();
                $('#modalDeductionTitle').html('<i class="fa-solid fa-tags text-primary me-2"></i>Configurar Deducción');
                $('#modalDeduction').modal('show');
            });

            // ==========================================================================
            // LOGIC FOR QUICK SEARCH BY CÉDULA & KEYBOARD ENTER NAVIGATION
            // ==========================================================================
            // Búsqueda por Cédula Rápida en registro manual
            function buscarPorCedula() {
                let cedula = $('#txtCedulaRapida').val().trim();
                if (cedula === '') {
                    $('#lblCedulaStatus').text('');
                    return;
                }
                
                // Buscar en la lista local primero para velocidad
                let matched = employeesList.find(e => e.Identificacion.trim() === cedula);
                if (matched) {
                    $('#ddlEmpleado').val(matched.CodEmpleado).trigger('change');
                    $('#lblCedulaStatus').html('<span class="text-success"><i class="fa-solid fa-circle-check"></i> ' + matched.Nombre + ' ' + matched.Apellido1 + '</span>');
                    
                    setTimeout(function() {
                        $('#ddlRubro').focus();
                    }, 50);
                } else {
                    // Fallback consultar al backend
                    $.ajax({
                        url: 'ajax/horas_extra.php?action=validate_cedulas&cedula=' + encodeURIComponent(cedula),
                        type: 'GET',
                        dataType: 'json',
                        success: function(res) {
                            if (res.success && res.data && res.data[cedula]) {
                                let emp = res.data[cedula];
                                $('#ddlEmpleado').val(emp.CodEmpleado).trigger('change');
                                $('#lblCedulaStatus').html('<span class="text-success"><i class="fa-solid fa-circle-check"></i> ' + emp.Colaborador + '</span>');
                                
                                setTimeout(function() {
                                    $('#ddlRubro').focus();
                                }, 50);
                            } else {
                                $('#lblCedulaStatus').html('<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> No registrado</span>');
                                $('#ddlEmpleado').val('').trigger('change');
                            }
                        },
                        error: function() {
                            $('#lblCedulaStatus').html('<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Error de búsqueda</span>');
                        }
                    });
                }
            }

            $('#txtCedulaRapida').on('keydown', function(e) {
                if (e.which === 13) { // Enter
                    e.preventDefault();
                    buscarPorCedula();
                }
            }).on('blur', function() {
                buscarPorCedula();
            });

            $('#btnBuscarCedula').click(function() {
                buscarPorCedula();
            });

            // Navegación secuencial por tecla ENTER en modales
            $('.modal form').on('keydown', 'input, select', function(e) {
                if (e.which === 13) { // Tecla Enter
                    let tag = this.tagName.toLowerCase();
                    if (tag === 'textarea' || this.type === 'submit') {
                        return; // Permitir enter en textareas o submits
                    }
                    
                    e.preventDefault();
                    
                    let form = $(this).closest('form');
                    let inputs = form.find(':input:visible:not([readonly]):not([disabled])');
                    
                    // Excluir botones que cierran o cancelan
                    inputs = inputs.filter(function() {
                        return !$(this).hasClass('btn-close') && !$(this).hasClass('btn-outline-secondary');
                    });
                    
                    let idx = inputs.index(this);
                    if (idx > -1 && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                        if (inputs[idx + 1].tagName.toLowerCase() === 'input') {
                            inputs[idx + 1].select();
                        }
                    }
                }
            });

            // Manejar cambio en Tipo de Deducción (Monto vs Porcentaje)
            $('#ddlTipoDeduccion').change(function() {
                let val = $(this).val();
                if (val === 'MONTO') {
                    $('#wrapperMonto').show();
                    $('#txtMonto').prop('required', true);
                    $('#wrapperPorcentaje').hide();
                    $('#txtPorcentaje').prop('required', false).val('0.00');
                } else {
                    $('#wrapperMonto').hide();
                    $('#txtMonto').prop('required', false).val('0.00');
                    $('#wrapperPorcentaje').show();
                    $('#txtPorcentaje').prop('required', true);
                }
            });

            // Manejar toggle de Préstamo Amortizable
            $('#chkEsPrestamo').change(function() {
                let checked = $(this).is(':checked');
                if (checked) {
                    $('#wrapperAmortizacion').show();
                    $('#txtMontoTotalOriginal').prop('required', true);
                    $('#txtSaldoRestante').prop('required', true);
                } else {
                    $('#wrapperAmortizacion').hide();
                    $('#txtMontoTotalOriginal').prop('required', false).val('');
                    $('#txtSaldoRestante').prop('required', false).val('');
                }
            });

            // Sincronizar Saldo Restante Inicial al digitar principal
            $('#txtMontoTotalOriginal').on('keyup change', function() {
                if ($('#txtCodDeduccionEmpleado').val() === '0') {
                    $('#txtSaldoRestante').val($(this).val());
                }
            });

            // Envío del Formulario
            $('#frmDeduction').on('submit', function(e) {
                e.preventDefault();

                // Validaciones básicas
                let emp = $('#ddlEmpleado').val();
                let rubro = $('#ddlRubro').val();
                let desc = $('#txtDescripcion').val();
                let tipo = $('#ddlTipoDeduccion').val();
                
                if (!emp || !rubro || !desc || !tipo) {
                    Swal.fire('Atención', 'Complete los campos marcados como obligatorios.', 'warning');
                    return;
                }

                let formData = $(this).serialize();
                
                // Si es préstamo, asegurar los campos de amortización en la serialización
                if (!$('#chkEsPrestamo').is(':checked')) {
                    // Limpiamos los campos antes de enviar para forzar nulos en el backend
                    formData = formData.replace(/&monto_total_original=[^&]*/g, '')
                                         .replace(/&saldo_restante=[^&]*/g, '');
                }

                $.ajax({
                    url: 'ajax/deducciones.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalDeduction').modal('hide');
                            Swal.fire({
                                title: '¡Éxito!',
                                text: res.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadDeductionsData();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(err) {
                        Swal.fire('Error', 'Fallo al conectar con el servidor: ' + err.statusText, 'error');
                    }
                });
            });
        });

        // Cargar colaboradores para el dropdown
        function loadEmployees() {
            $.ajax({
                url: 'ajax/recursos_humanos.php?action=list_employees',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        employeesList = res.data;
                        let ddl = $('#ddlEmpleado');
                        ddl.empty().append('<option value="" disabled selected>-- Seleccione el Colaborador --</option>');
                        
                        employeesList.forEach(function(e) {
                            if (e.Estado === 'ACTIVO') {
                                ddl.append(`<option value="${e.CodEmpleado}">${e.Identificacion} - ${e.Nombre} ${e.Apellido1}</option>`);
                            }
                        });
                    }
                }
            });
        }

        // Cargar rubros de tipo deducción
        function loadRubros() {
            $.ajax({
                url: 'ajax/deducciones.php?action=get_deduction_rubros',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let ddl = $('#ddlRubro');
                        ddl.empty();
                        
                        res.data.forEach(function(r) {
                            // Marcar DED_VAR como seleccionada por defecto
                            let selected = r.Codigo === 'DED_VAR' ? 'selected' : '';
                            ddl.append(`<option value="${r.CodRubro}" ${selected}>[${r.Codigo}] - ${r.Descripcion}</option>`);
                        });
                    }
                }
            });
        }

        // Cargar deducciones registradas en el DataTable
        function loadDeductionsData() {
            $.ajax({
                url: 'ajax/deducciones.php?action=list_deductions',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableDeductions.clear();

                        let activas = 0;
                        let proyeccion = 0;
                        let cartera = 0;
                        let sumPorcentajes = 0;
                        let countPorcentajes = 0;

                        res.data.forEach(function(r) {
                            let badgeClass = r.Estado === 'ACTIVO' ? 'badge-status-activo' : 'badge-status-inactivo';
                            let badge = `<span class="badge-status ${badgeClass}">${r.Estado}</span>`;
                            
                            // KPIs
                            if (r.Estado === 'ACTIVO') {
                                activas++;
                                if (r.TipoDeduccion === 'MONTO') {
                                    proyeccion += parseFloat(r.Monto);
                                } else {
                                    sumPorcentajes += parseFloat(r.Porcentaje);
                                    countPorcentajes++;
                                }
                            }

                            let saldoVal = 'N/A';
                            if (r.MontoTotalOriginal !== null) {
                                let saldoNum = parseFloat(r.SaldoRestante);
                                saldoVal = `<span class="fw-bold text-warning">₡${saldoNum.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span>`;
                                if (r.Estado === 'ACTIVO') {
                                    cartera += saldoNum;
                                }
                            }

                            let calculoMonto = '';
                            if (r.TipoDeduccion === 'MONTO') {
                                calculoMonto = `<span class="fw-bold">₡${parseFloat(r.Monto).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span>`;
                            } else {
                                calculoMonto = `<span class="fw-semibold text-primary">${parseFloat(r.Porcentaje).toFixed(2)}%</span>`;
                            }

                            let btnEdit = `<button class="btn-table-action btn-table-edit" onclick="editDeduction(${r.CodDeduccionEmpleado})" title="Editar"><i class="fa-solid fa-pen"></i></button>`;
                            let btnDelete = '';
                            
                            if (r.Estado === 'ACTIVO') {
                                btnDelete = `<button class="btn-table-action btn-table-delete" onclick="deleteDeduction(${r.CodDeduccionEmpleado})" title="Inactivar"><i class="fa-solid fa-circle-minus"></i></button>`;
                            } else {
                                btnDelete = `<button class="btn-table-action" disabled title="Inactivo"><i class="fa-solid fa-lock"></i></button>`;
                            }

                            let acciones = `<div class="d-flex gap-2 justify-content-center">${btnEdit}${btnDelete}</div>`;

                            tableDeductions.row.add([
                                r.CodDeduccionEmpleado,
                                `<span class="fw-semibold">${r.Colaborador}</span>`,
                                r.Descripcion,
                                `<span class="badge bg-secondary-subtle text-secondary-emphasis font-monospace px-2 py-1" style="font-size: 11px;">${r.CodigoRubro}</span>`,
                                r.TipoDeduccion,
                                calculoMonto,
                                saldoVal,
                                badge,
                                acciones
                            ]);
                        });

                        tableDeductions.draw();

                        // Actualizar tarjetas de KPI
                        $('#kpiTotalDeducciones').text(activas);
                        $('#kpiProyeccionMonto').text('₡' + proyeccion.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpiCarteraPrestamos').text('₡' + cartera.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        
                        let promPorc = countPorcentajes > 0 ? (sumPorcentajes / countPorcentajes) : 0;
                        $('#kpiPorcentajeMedio').text(promPorc.toFixed(2) + '%');
                    }
                }
            });
        }

        // Editar Deducción
        function editDeduction(id) {
            $.ajax({
                url: 'ajax/deducciones.php',
                type: 'GET',
                data: { action: 'get_deduction', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let d = res.data;
                        $('#txtCodDeduccionEmpleado').val(d.CodDeduccionEmpleado);
                        $('#ddlEmpleado').val(d.CodEmpleado).prop('disabled', true);
                        $('#ddlRubro').val(d.CodRubro).prop('disabled', true);
                        $('#txtDescripcion').val(d.Descripcion);
                        $('#ddlTipoDeduccion').val(d.TipoDeduccion).trigger('change');
                        
                        $('#txtMonto').val(d.Monto);
                        $('#txtPorcentaje').val(d.Porcentaje);
                        
                        if (d.MontoTotalOriginal !== '' && d.MontoTotalOriginal !== null) {
                            $('#chkEsPrestamo').prop('checked', true).trigger('change');
                            $('#txtMontoTotalOriginal').val(d.MontoTotalOriginal);
                            $('#txtSaldoRestante').val(d.SaldoRestante);
                        } else {
                            $('#chkEsPrestamo').prop('checked', false).trigger('change');
                            $('#txtMontoTotalOriginal').val('');
                            $('#txtSaldoRestante').val('');
                        }

                        $('#ddlEstado').val(d.Estado);
                        $('#wrapperEstado').show();
                        $('#modalDeductionTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Configuración de Deducción');
                        $('#modalDeduction').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }

        // Inactivar Deducción
        function deleteDeduction(id) {
            Swal.fire({
                title: '¿Está seguro de inactivar?',
                text: "La deducción se inactivará lógicamente y dejará de aplicarse en las siguientes planillas generadas.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, inactivar',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/deducciones.php',
                        type: 'POST',
                        data: { action: 'delete_deduction', id: id },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: '¡Inactivada!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadDeductionsData();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        }
                    });
                }
            });
        }

        // ==========================================================================
        // CONTROLADOR DE CARGA MASIVA DESDE EXCEL PARA DEDUCCIONES & PRÉSTAMOS
        // ==========================================================================
        let parsedDeductions = [];

        // Abrir Modal de Carga Masiva
        $('#btnImportExcelDeducciones').click(function() {
            $('#txtExcelPasteDeducciones').val('');
            $('#step1Deducciones').show();
            $('#step2Deducciones').hide();
            
            // Cargar rubros de tipo deducción en ddlRubroImportar
            $.ajax({
                url: 'ajax/deducciones.php?action=get_deduction_rubros',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let ddl = $('#ddlRubroImportar');
                        ddl.empty();
                        ddl.append('<option value="" disabled selected>-- Seleccione el Rubro --</option>');
                        res.data.forEach(function(r) {
                            // Preseleccionar DED_VAR o ASE (Asociación Solidarista) si existe
                            let selected = (r.Codigo === 'DED_VAR' || r.Codigo === 'ASE') ? 'selected' : '';
                            ddl.append(`<option value="${r.CodRubro}" ${selected}>[${r.Codigo}] - ${r.Descripcion}</option>`);
                        });
                    }
                }
            });

            $('#modalImportarDeducciones').modal('show');
        });

        // Botón regresar en modal de importación
        $('#btnBackToStep1Deducciones').click(function() {
            $('#step2Deducciones').hide();
            $('#step1Deducciones').show();
        });

        // Analizar y validar el texto copiado de Excel
        $('#btnParseExcelDeducciones').click(function() {
            let rubroSelected = $('#ddlRubroImportar').val();
            if (!rubroSelected) {
                Swal.fire({
                    title: 'Atención',
                    text: 'Debe seleccionar la clasificación (rubro) a la cual pertenecerá la deducción.',
                    icon: 'warning',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                });
                return;
            }

            let rawText = $('#txtExcelPasteDeducciones').val().trim();
            if (rawText === '') {
                Swal.fire({
                    title: 'Atención',
                    text: 'Pegue datos válidos desde su archivo de Microsoft Excel.',
                    icon: 'warning',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                });
                return;
            }

            let tipoCarga = $('#ddlTipoCargaDeduccion').val(); // 'APORTACION' o 'PRESTAMO'
            let lines = rawText.split(/\r?\n/);
            let rawRows = [];
            let uniqueCedulas = [];

            lines.forEach(function(line) {
                let trimmedLine = line.trim();
                if (trimmedLine === '') return;

                // Separación por Tabulaciones (Excel original) con fallback a comas
                let cols = trimmedLine.split(/\t/);
                if (cols.length < 2) {
                    cols = trimmedLine.split(/,/);
                }

                if (cols.length >= 2) {
                    let cedula = cols[0].trim();
                    let monto = parseFloat(cols[1].replace(/[^\d.-]/g, '')) || 0;
                    
                    if (tipoCarga === 'APORTACION') {
                        let obs = cols[2] ? cols[2].trim() : 'Aporte Asociación Solidarista';
                        rawRows.push({
                            cedula: cedula,
                            monto: monto,
                            original: null,
                            obs: obs,
                            valid: false,
                            error: 'Pendiente de validar'
                        });
                    } else { // PRESTAMO
                        let original = cols[2] ? parseFloat(cols[2].replace(/[^\d.-]/g, '')) : monto;
                        let obs = cols[3] ? cols[3].trim() : 'Préstamo Asociación Solidarista';
                        rawRows.push({
                            cedula: cedula,
                            monto: monto,
                            original: original,
                            obs: obs,
                            valid: false,
                            error: 'Pendiente de validar'
                        });
                    }
                    uniqueCedulas.push(cedula);
                }
            });

            if (rawRows.length === 0) {
                Swal.fire('Atención', 'No se pudieron parsear filas del texto pegado. Asegúrese que tenga al menos Cédula y Monto.', 'warning');
                return;
            }

            // Validar Cédulas masivamente contra el servidor SQL Server
            $.ajax({
                url: 'ajax/deducciones.php?action=validate_cedulas',
                type: 'POST',
                data: { cedulas: JSON.stringify(uniqueCedulas) },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let empMap = res.data;
                        parsedDeductions = [];

                        rawRows.forEach(function(row) {
                            if (empMap[row.cedula]) {
                                let emp = empMap[row.cedula];
                                row.cod_empleado = emp.CodEmpleado;
                                row.cod_empresa = emp.CodEmpresa;
                                row.colaborador = emp.Colaborador;
                                row.valid = true;
                                row.error = '';
                            } else {
                                row.cod_empleado = 0;
                                row.cod_empresa = 1;
                                row.colaborador = '<span class="text-danger font-monospace">CÉDULA NO ENCONTRADA</span>';
                                row.valid = false;
                                row.error = 'Cédula inválida o inactivo';
                            }
                            parsedDeductions.push(row);
                        });

                        // Renderizar la tabla de previsualización
                        renderPreviewTable(tipoCarga);
                    } else {
                        Swal.fire('Error', 'Error al validar cédulas en el servidor.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo comunicar con el validador en el servidor.', 'error');
                }
            });
        });

        // Renderizar la tabla preview según el tipo de carga
        function renderPreviewTable(tipoCarga) {
            let header = $('#tblPreviewDeduccionesHeader');
            let body = $('#tblPreviewDeduccionesBody');
            
            header.empty();
            body.empty();

            $('#lblTipoCargaBadge').text(tipoCarga === 'APORTACION' ? 'APORTACIONES SOLIDARIAS / FIJAS' : 'PRÉSTAMOS AMORTIZABLES');

            if (tipoCarga === 'APORTACION') {
                header.append(`
                    <th style="width: 40px;" class="text-center"><input type="checkbox" id="chkSelectAllDeducciones" checked></th>
                    <th>Cédula</th>
                    <th>Colaborador</th>
                    <th class="text-end">Aporte Fijo</th>
                    <th>Descripción / Observación</th>
                    <th>Estado</th>
                `);
            } else { // PRESTAMO
                header.append(`
                    <th style="width: 40px;" class="text-center"><input type="checkbox" id="chkSelectAllDeducciones" checked></th>
                    <th>Cédula</th>
                    <th>Colaborador</th>
                    <th class="text-end">Cuota Fija</th>
                    <th class="text-end">Principal Original</th>
                    <th>Descripción / Observación</th>
                    <th>Estado</th>
                `);
            }

            parsedDeductions.forEach(function(row, idx) {
                let checkState = row.valid ? 'checked' : 'disabled';
                let rowClass = row.valid ? '' : 'table-danger-subtle';
                let badge = row.valid ? '<span class="badge bg-success">Válido</span>' : `<span class="badge bg-danger">${row.error}</span>`;
                
                let colsHtml = `
                    <td class="text-center"><input type="checkbox" class="chkRowDeduccion" data-index="${idx}" ${checkState}></td>
                    <td class="font-monospace">${row.cedula}</td>
                    <td>${row.colaborador}</td>
                    <td class="text-end fw-bold text-success">₡${row.monto.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                `;

                if (tipoCarga === 'PRESTAMO') {
                    colsHtml += `<td class="text-end fw-bold text-warning">₡${row.original.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>`;
                }

                colsHtml += `
                    <td>${row.obs}</td>
                    <td>${badge}</td>
                `;

                body.append(`<tr class="${rowClass}">${colsHtml}</tr>`);
            });

            // Registrar listeners para cambios
            $('#chkSelectAllDeducciones').change(function() {
                let checked = $(this).is(':checked');
                $('.chkRowDeduccion:not([disabled])').prop('checked', checked);
                recalculateImportStats();
            });

            $('.chkRowDeduccion').change(function() {
                recalculateImportStats();
            });

            recalculateImportStats();

            // Mostrar el paso 2
            $('#step1Deducciones').hide();
            $('#step2Deducciones').show();
        }

        // Recalcular estadísticas del lote previsualizado
        function recalculateImportStats() {
            let total = parsedDeductions.length;
            let validas = 0;
            let errores = 0;
            let montoTotal = 0;

            parsedDeductions.forEach(function(row, idx) {
                if (!row.valid) {
                    errores++;
                } else {
                    let isChecked = $(`.chkRowDeduccion[data-index="${idx}"]`).is(':checked');
                    if (isChecked) {
                        validas++;
                        montoTotal += row.monto;
                    }
                }
            });

            $('#statTotalDeducciones').text(total);
            $('#statValidasDeducciones').text(validas);
            $('#statErroresDeducciones').text(errores);
            $('#statMontoDeducciones').text('₡' + montoTotal.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
        }

        // Guardar transaccionalmente el lote de deducciones/préstamos
        $('#btnProcessImportDeducciones').click(function() {
            let rubroSelected = $('#ddlRubroImportar').val();
            let batchData = [];

            parsedDeductions.forEach(function(row, idx) {
                let isChecked = $(`.chkRowDeduccion[data-index="${idx}"]`).is(':checked');
                if (row.valid && isChecked) {
                    batchData.push({
                        cod_empresa: row.cod_empresa,
                        cod_empleado: row.cod_empleado,
                        cod_rubro: rubroSelected,
                        descripcion: row.obs,
                        monto: row.monto,
                        original: row.original
                    });
                }
            });

            if (batchData.length === 0) {
                Swal.fire({
                    title: 'Atención',
                    text: 'Debe seleccionar al menos una fila válida para importar.',
                    icon: 'warning',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                });
                return;
            }

            Swal.fire({
                title: 'Procesando Importación...',
                html: 'Guardando de manera segura en SQL Server...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'ajax/deducciones.php?action=save_batch_deductions',
                type: 'POST',
                data: { batch_data: JSON.stringify(batchData) },
                dataType: 'json',
                success: function(res) {
                    Swal.close();
                    if (res.success) {
                        Swal.fire({
                            title: '¡Excelente!',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#198754',
                            background: 'var(--bg-surface)',
                            color: 'var(--text-primary)'
                        }).then(() => {
                            $('#modalImportarDeducciones').modal('hide');
                            loadDeductionsData();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let errMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Error desconocido al transaccionar lote.';
                    Swal.fire('Error de Importación', errMsg, 'error');
                }
            });
        });
    </script>
</body>
</html>
