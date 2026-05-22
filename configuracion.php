<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * CONFIGURACIÓN Y MANTENIMIENTO DE PARÁMETROS LEGALES Y DEL ERP (CONFIGURACION.PHP)
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
    <title>Configuración General & Leyes - PlanillaCR ERP</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables Bootstrap 5 CSS CDN -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom Dark/Light Executive CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
    
    <style>
        /* Estilos específicos de Pestañas y Paneles */
        .config-tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 12px;
        }

        .config-tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .config-tab-btn:hover {
            color: var(--text-primary);
            background-color: var(--bg-surface-elevated);
        }

        .config-tab-btn.active {
            color: #ffffff;
            background-color: var(--primary);
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);
        }

        .config-panel {
            display: none;
        }

        .config-panel.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Estilos del Módulo de Sumas y Dos Columnas */
        .config-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-lg);
            padding: 24px;
            box-shadow: 0 8px 30px var(--shadow-main);
            margin-bottom: 24px;
        }

        .config-card-title {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .carga-total-badge {
            background-color: rgba(var(--primary-rgb), 0.08);
            border: 1px dashed var(--primary);
            border-radius: var(--border-radius-md);
            padding: 14px 20px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .carga-total-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Preview table style */
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
            font-size: 13px;
            color: var(--text-secondary) !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color) !important;
            padding: 12px 10px !important;
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
            font-size: 11.5px;
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

        .modal-content {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
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
                <li class="sidebar-item">
                    <a href="calcular_planilla.php">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Calcular Planilla</span>
                    </a>
                </li>
                <li class="sidebar-item active">
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
                    <h1>Configuración Legal & ERP</h1>
                    <p>Mantenimiento de tasas obrero/patronales, FODESAF, impuesto de renta y variables del sistema</p>
                </div>
                
                <div class="header-actions">
                    <div class="date-badge">
                        <i class="fa-regular fa-calendar"></i>
                        <span id="currentDate"><?php echo date('d/m/Y'); ?></span>
                    </div>
                    
                    <button class="theme-toggle-btn" id="themeToggle" style="position: static;" title="Cambiar Tema Visual">
                        <i class="fa-solid fa-moon" id="themeIcon"></i>
                    </button>
                </div>
            </header>

            <!-- Barra de Pestañas (Tabs) de Configuración -->
            <nav class="config-tabs-nav">
                <button class="config-tab-btn active" data-target="panelCCSS">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>Cargas Sociales (CCSS & Leyes)</span>
                </button>
                <button class="config-tab-btn" data-target="panelRenta">
                    <i class="fa-solid fa-coins"></i>
                    <span>Impuesto sobre la Renta</span>
                </button>
                <button class="config-tab-btn" data-target="panelGeneral">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Variables del ERP</span>
                </button>
                <button class="config-tab-btn" data-target="panelContable" id="btnTabContable">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Cuentas Contables (Mapeo)</span>
                </button>
                <button class="config-tab-btn" data-target="panelEmpresa" id="btnTabEmpresa">
                    <i class="fa-solid fa-building"></i>
                    <span>Empresas</span>
                </button>
            </nav>

            <!-- ==========================================================================
                 PESTAÑA 1: CARGAS SOCIALES (CCSS & LEYES PATRONALES)
                 ========================================================================== -->
            <section class="config-panel active" id="panelCCSS">
                <form id="frmCCSS">
                    <div class="row">
                        <!-- Columna Obrero (Aporte del Colaborador) -->
                        <div class="col-lg-5 mb-4">
                            <div class="config-card h-100">
                                <div class="config-card-title">
                                    <i class="fa-solid fa-user-tie text-primary"></i>
                                    <span>Aportes del Colaborador (Obrero)</span>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="txtSemObrero" class="form-label-desc">Seguro Enfermedad & Maternidad (SEM) %</label>
                                    <input type="number" class="form-control calc-obrero" name="sem_obrero" id="txtSemObrero" placeholder="Ej: 5.50" step="0.01" min="0" required>
                                </div>

                                <div class="mb-3">
                                    <label for="txtIvmObrero" class="form-label-desc">Invalidez, Vejez & Muerte (IVM) %</label>
                                    <input type="number" class="form-control calc-obrero" name="ivm_obrero" id="txtIvmObrero" placeholder="Ej: 4.17" step="0.01" min="0" required>
                                </div>

                                <div class="mb-3">
                                    <label for="txtBpObrero" class="form-label-desc">Banco Popular %</label>
                                    <input type="number" class="form-control calc-obrero" name="bp_obrero" id="txtBpObrero" placeholder="Ej: 1.00" step="0.01" min="0" required>
                                </div>

                                <div class="carga-total-badge">
                                    <span>CARGA TOTAL OBRERA:</span>
                                    <span class="carga-total-value" id="lblSumaObrera">0.00%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Patrono (Aporte de la Empresa) -->
                        <div class="col-lg-7 mb-4">
                            <div class="config-card h-100">
                                <div class="config-card-title">
                                    <i class="fa-solid fa-briefcase text-primary"></i>
                                    <span>Aportes de la Empresa (Patronales & Leyes)</span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="txtSemPatrono" class="form-label-desc">SEM Patrono %</label>
                                        <input type="number" class="form-control calc-patrono" name="sem_patrono" id="txtSemPatrono" placeholder="Ej: 9.25" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="txtIvmPatrono" class="form-label-desc">IVM Patrono %</label>
                                        <input type="number" class="form-control calc-patrono" name="ivm_patrono" id="txtIvmPatrono" placeholder="Ej: 5.42" step="0.01" min="0" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="txtBpPatrono" class="form-label-desc">Banco Popular Patrono %</label>
                                        <input type="number" class="form-control calc-patrono" name="bp_patrono" id="txtBpPatrono" placeholder="Ej: 0.50" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="txtFodesaf" class="form-label-desc">FODESAF (Fodepsa) %</label>
                                        <input type="number" class="form-control calc-patrono" name="fodesaf" id="txtFodesaf" placeholder="Ej: 5.00" step="0.01" min="0" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="txtImas" class="form-label-desc">IMAS %</label>
                                        <input type="number" class="form-control calc-patrono" name="imas" id="txtImas" placeholder="Ej: 0.50" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="txtIna" class="form-label-desc">INA %</label>
                                        <input type="number" class="form-control calc-patrono" name="ina" id="txtIna" placeholder="Ej: 1.50" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="txtIns" class="form-label-desc">INS (Riesgos del Trabajo) %</label>
                                        <input type="number" class="form-control calc-patrono" name="ins" id="txtIns" placeholder="Ej: 1.50" step="0.01" min="0" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="txtFcl" class="form-label-desc">Aporte FCL %</label>
                                        <input type="number" class="form-control calc-patrono" name="fcl" id="txtFcl" placeholder="Ej: 1.50" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="txtRop" class="form-label-desc">Aporte ROP %</label>
                                        <input type="number" class="form-control calc-patrono" name="rop" id="txtRop" placeholder="Ej: 1.50" step="0.01" min="0" required>
                                    </div>
                                </div>

                                <div class="carga-total-badge">
                                    <span>CARGA TOTAL PATRONAL:</span>
                                    <span class="carga-total-value text-success" id="lblSumaPatronal">0.00%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Fechas de Vigencia y Envío -->
                    <div class="config-card">
                        <div class="config-card-title">
                            <i class="fa-solid fa-calendar-check text-primary"></i>
                            <span>Periodo de Vigencia Legal</span>
                        </div>
                        
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label for="txtVigenciaDesde" class="form-label-desc">Vigencia Desde <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="vigencia_desde" id="txtVigenciaDesde" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="txtVigenciaHasta" class="form-label-desc">Vigencia Hasta</label>
                                <input type="date" class="form-control" name="vigencia_hasta" id="txtVigenciaHasta" value="2099-12-31">
                            </div>
                            <div class="col-md-4 mb-3">
                                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Aplicar Nueva Tasa Legal</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Historial de Cargas Sociales en DataTable -->
                <div class="table-responsive">
                    <div class="config-card-title" style="border-bottom:none; margin-bottom: 10px;">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                        <span>Historial de Modificación de Cargas Sociales</span>
                    </div>
                    <table id="tblCcssHistory" class="table table-hover table-striped w-100">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Vigente Desde</th>
                                <th>Vigente Hasta</th>
                                <th>Total Obrero</th>
                                <th>Total Patrono</th>
                                <th>FODESAF %</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Poblado asíncronamente con jQuery AJAX -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ==========================================================================
                 PESTAÑA 2: TRAMOS DE IMPUESTO SOBRE LA RENTA (HACIENDA)
                 ========================================================================== -->
            <section class="config-panel" id="panelRenta">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-secondary" style="font-size: 13.5px;">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        Escalas salariales de retención mensual del Impuesto Único sobre el Trabajo de Costa Rica.
                    </div>
                    
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewRenta" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-plus"></i>
                        <span>Agregar Tramo Renta</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="tblRenta" class="table table-hover table-striped w-100">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Vigencia</th>
                                <th>Desde Salario</th>
                                <th>Hasta Salario</th>
                                <th>Tasa %</th>
                                <th>Exceso Sobre</th>
                                <th>Base Impuesto</th>
                                <th class="text-center" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Poblado asíncronamente -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ==========================================================================
                 PESTAÑA 3: CONFIGURACIONES GENERALES DEL ERP
                 ========================================================================== -->
            <section class="config-panel" id="panelGeneral">
                <form id="frmGeneral">
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="config-card h-100">
                                <div class="config-card-title">
                                    <i class="fa-solid fa-clock text-primary"></i>
                                    <span>Jornada & Tiempo Laboral</span>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="txtHorasSemana" class="form-label-desc">Horas Ordinarias por Semana (Límite Ley)</label>
                                    <input type="number" class="form-control" name="horas_semana" id="txtHorasSemana" placeholder="Ej: 48" min="1" max="100" required>
                                </div>

                                <div class="mb-3">
                                    <label for="txtDecimales" class="form-label-desc">Precisión de Decimales en Planilla</label>
                                    <select class="form-select" name="decimales_planilla" id="txtDecimales" required>
                                        <option value="0">0 (Sin decimales - Redondeo Entero)</option>
                                        <option value="2" selected>2 (Estándar Financiero .00)</option>
                                        <option value="4">4 (Alta Precisión Cambiaria .0000)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="config-card h-100">
                                <div class="config-card-title">
                                    <i class="fa-solid fa-sack-dollar text-primary"></i>
                                    <span>Moneda & Contabilidad</span>
                                </div>

                                <div class="mb-3">
                                    <label for="ddlMoneda" class="form-label-desc">Moneda por Defecto del Sistema</label>
                                    <select class="form-select" name="moneda_sistema" id="ddlMoneda" required>
                                        <option value="CRC" selected>CRC (₡ - Colón Costarricense)</option>
                                        <option value="USD">USD ($ - Dólar Estadounidense)</option>
                                    </select>
                                </div>

                                <div class="carga-total-badge" style="border: 1px dashed var(--border-color); background-color: var(--bg-surface-elevated); color: var(--text-secondary); height: 86px; display: flex; align-items: center;">
                                    <span>Los cambios en variables del sistema afectarán los cálculos automáticos de nómina siguientes.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-5 py-2.5 fw-semibold" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Parámetros ERP
                        </button>
                    </div>
                </form>
            </section>

            <!-- ==========================================================================
                 PESTAÑA 4: MAPEO DE CUENTAS CONTABLES (RUBROS)
                 ========================================================================== -->
            <section class="config-panel" id="panelContable">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-secondary" style="font-size: 13.5px;">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        Catálogo maestro de rubros salariales (ingresos/deducciones) y asignación de cuentas contables del ERP.
                    </div>
                    
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewRubro" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-plus"></i>
                        <span>Registrar Nuevo Rubro</span>
                    </button>
                </div>

                <div class="table-responsive" style="background-color: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--border-radius-lg); padding: 20px; box-shadow: 0 10px 25px var(--shadow-main);">
                    <table id="tblRubrosContables" class="table table-hover w-100" style="border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Código</th>
                                <th>Descripción</th>
                                <th style="width: 160px;">Tipo de Rubro</th>
                                <th>Afectación Legal</th>
                                <th style="width: 280px;">Cuenta Contable</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Poblado asíncronamente con AJAX -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ==========================================================================
                 PESTAÑA 5: MANTENIMIENTO DE EMPRESAS (CRUD)
                 ========================================================================== -->
            <section class="config-panel" id="panelEmpresa">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-secondary" style="font-size: 13.5px;">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        Registro y mantenimiento de las empresas operadas por el ERP.
                    </div>
                    
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewEmpresa" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-plus"></i>
                        <span>Registrar Nueva Empresa</span>
                    </button>
                </div>

                <div class="table-responsive" style="background-color: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--border-radius-lg); padding: 20px; box-shadow: 0 10px 25px var(--shadow-main);">
                    <table id="tblEmpresas" class="table table-hover w-100" style="border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Código</th>
                                <th>Nombre Comercial</th>
                                <th>Razón Social</th>
                                <th>Cédula Jurídica</th>
                                <th>Teléfono</th>
                                <th>Moneda</th>
                                <th style="width: 100px;">Estado</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Poblado asíncronamente con AJAX -->
                        </tbody>
                    </table>
                </div>
            </section>
            
        </main>
    </div>

    <!-- MODAL DE REGISTRO / EDICIÓN DE TRAMO DE RENTA -->
    <div class="modal fade" id="modalRenta" tabindex="-1" aria-labelledby="modalRentaTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalRentaTitle"><i class="fa-solid fa-coins text-primary me-2"></i>Registrar Tramo Renta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmRenta" novalidate>
                    <div class="modal-body px-4 py-3">
                        <input type="hidden" name="codigo_renta" id="txtCodRenta" value="0">
                        <input type="hidden" name="action" id="txtActionRenta" value="save_renta">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="txtDesdeMonto" class="form-label-desc">Desde Salario (₡) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="desde_monto" id="txtDesdeMonto" placeholder="Ej: 941001" step="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtHastaMonto" class="form-label-desc">Hasta Salario (₡) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="hasta_monto" id="txtHastaMonto" placeholder="Ej: 1380000" step="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="txtPorcentajeRenta" class="form-label-desc">Tasa Renta % <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="porcentaje_renta" id="txtPorcentajeRenta" placeholder="Ej: 10" step="0.1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="txtExcesoRenta" class="form-label-desc">Exceso Sobre (₡)</label>
                                <input type="number" class="form-control" name="exceso_renta" id="txtExcesoRenta" placeholder="Ej: 941000" step="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="txtBaseRenta" class="form-label-desc">Base Impuesto (₡)</label>
                                <input type="number" class="form-control" name="base_renta" id="txtBaseRenta" placeholder="Ej: 0" step="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: var(--border-radius-md); font-size: 13.5px;" id="btnSaveRenta">Guardar Tramo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE REGISTRO / EDICIÓN DE RUBRO (CATÁLOGO MAESTRO) -->
    <div class="modal fade" id="modalRubro" tabindex="-1" aria-labelledby="modalRubroTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalRubroTitle"><i class="fa-solid fa-folder-plus text-primary me-2"></i>Registrar Rubro de Planilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmRubro" novalidate>
                    <div class="modal-body px-4 py-3">
                        <input type="hidden" name="cod_rubro" id="txtCodRubro" value="0">
                        <input type="hidden" name="action" id="txtActionRubro" value="save_rubro">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="txtCodigoRubro" class="form-label-desc">Código (Sigla) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="codigo" id="txtCodigoRubro" placeholder="Ej: ASE" required maxlength="20" style="text-transform: uppercase;">
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Identificador único corto (Ej: ASE, PENS, ASOC)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtDescripcionRubro" class="form-label-desc">Descripción <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="descripcion" id="txtDescripcionRubro" placeholder="Ej: Asociación Solidarista" required maxlength="150">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ddlTipoRubro" class="form-label-desc">Tipo de Rubro <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" id="ddlTipoRubro" required>
                                    <option value="DEVENGO" selected>DEVENGO (Ingreso)</option>
                                    <option value="DEDUCCION">DEDUCCIÓN (Egreso/Retención)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtPrioridadRubro" class="form-label-desc">Prioridad de Cálculo / Orden</label>
                                <input type="number" class="form-control" name="prioridad" id="txtPrioridadRubro" value="10" min="1" max="1000">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="txtCuentaContableRubro" class="form-label-desc">Cuenta Contable Inicial (Opcional)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-calculator"></i></span>
                                    <input type="text" class="form-control border-secondary text-light bg-dark" name="cuenta_contable" id="txtCuentaContableRubro" placeholder="Mapear cuenta contable inicial..." style="font-weight: bold; letter-spacing: 0.5px;">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label-desc d-block mb-2">Afectación Legal (Cálculos de Costa Rica)</label>
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch d-flex align-items-center gap-2">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_ccss" id="chkAfectaCCSS">
                                        <label class="form-check-label mb-0" style="cursor: pointer; font-size: 13px;" for="chkAfectaCCSS">Afecta CCSS</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch d-flex align-items-center gap-2">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_renta" id="chkAfectaRenta">
                                        <label class="form-check-label mb-0" style="cursor: pointer; font-size: 13px;" for="chkAfectaRenta">Afecta Renta (Hacienda)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch d-flex align-items-center gap-2">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_vacaciones" id="chkAfectaVacaciones">
                                        <label class="form-check-label mb-0" style="cursor: pointer; font-size: 13px;" for="chkAfectaVacaciones">Afecta Vacaciones</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch d-flex align-items-center gap-2">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_aguinaldo" id="chkAfectaAguinaldo">
                                        <label class="form-check-label mb-0" style="cursor: pointer; font-size: 13px;" for="chkAfectaAguinaldo">Afecta Aguinaldo</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3" id="wrapperEstadoRubro" style="display: none;">
                            <label for="ddlEstadoRubro" class="form-label-desc">Estado</label>
                            <select class="form-select" name="estado" id="ddlEstadoRubro">
                                <option value="ACTIVO" selected>ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: var(--border-radius-md); font-size: 13.5px;" id="btnSaveRubro">Guardar Rubro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE REGISTRO / EDICIÓN DE EMPRESA -->
    <div class="modal fade" id="modalEmpresa" tabindex="-1" aria-labelledby="modalEmpresaTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalEmpresaTitle"><i class="fa-solid fa-building text-primary me-2"></i>Registrar Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmEmpresa" novalidate>
                    <div class="modal-body px-4 py-3">
                        <input type="hidden" name="cod_empresa" id="txtCodEmpresa" value="0">
                        <input type="hidden" name="action" id="txtActionEmpresa" value="save_empresa">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="txtNombreEmpresa" class="form-label-desc">Razón Social (Nombre Legal) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="txtNombreEmpresa" placeholder="Ej: Bahía Radio Costa Rica S.A." required maxlength="150">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtNombreComercialEmpresa" class="form-label-desc">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial" id="txtNombreComercialEmpresa" placeholder="Ej: Bahía Radio" maxlength="150">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="txtCedulaJuridicaEmpresa" class="form-label-desc">Cédula Jurídica <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="cedula_juridica" id="txtCedulaJuridicaEmpresa" placeholder="Ej: 3-101-XXXXXX" required maxlength="20">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="txtTelefonoEmpresa" class="form-label-desc">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="txtTelefonoEmpresa" placeholder="Ej: 2221-9723" maxlength="50">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="txtCorreoEmpresa" class="form-label-desc">Correo Electrónico</label>
                                <input type="email" class="form-control" name="correo" id="txtCorreoEmpresa" placeholder="Ej: info@radiobahiapuerto.com" maxlength="150">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="txtDireccionEmpresa" class="form-label-desc">Dirección Física</label>
                                <textarea class="form-control" name="direccion" id="txtDireccionEmpresa" placeholder="Ej: San José, del Teatro Nacional..." rows="2" maxlength="500"></textarea>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="txtCodigoCCSSEmpresa" class="form-label-desc">Código Patronal CCSS</label>
                                <input type="text" class="form-control" name="codigo_ccss" id="txtCodigoCCSSEmpresa" placeholder="Ej: 10-XXXX-XX" maxlength="50">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="txtCodigoINSEmpresa" class="form-label-desc">Código Póliza INS</label>
                                <input type="text" class="form-control" name="codigo_ins" id="txtCodigoINSEmpresa" placeholder="Ej: RT-XXXXX" maxlength="50">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="txtLogoEmpresa" class="form-label-desc">Ruta o URL del Logotipo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-image"></i></span>
                                    <input type="text" class="form-control" name="logo" id="txtLogoEmpresa" placeholder="Ej: img/logos/empresa1.png o una URL base64/externa" maxlength="300">
                                </div>
                                <div class="form-text text-secondary" style="font-size: 11px;">Especifique la ruta de la imagen o URL del logotipo para imprimir en los comprobantes de pago.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ddlMonedaEmpresa" class="form-label-desc">Moneda Comercial</label>
                                <select class="form-select" name="moneda" id="ddlMonedaEmpresa">
                                    <option value="CRC" selected>CRC (₡ - Colón Costarricense)</option>
                                    <option value="USD">USD ($ - Dólar)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="wrapperEstadoEmpresa" style="display: none;">
                                <label for="ddlEstadoEmpresa" class="form-label-desc">Estado</label>
                                <select class="form-select" name="estado" id="ddlEstadoEmpresa">
                                    <option value="ACTIVO" selected>ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: var(--border-radius-md); font-size: 13.5px;" id="btnSaveEmpresa">Guardar Empresa</button>
                    </div>
                </form>
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

        // CONTROLADOR DE PANTALLAS (TABS)
        $('.config-tab-btn').click(function() {
            $('.config-tab-btn').removeClass('active');
            $(this).addClass('active');

            const target = $(this).data('target');
            $('.config-panel').removeClass('active');
            $('#' + target).addClass('active');
        });

        // CONTROLADOR DE DATOS DE CONFIGURACIÓN
        let tableHistory;
        let tableRenta;
        let tableRubrosContables;
        let tableEmpresas;

        $(document).ready(function() {
            // Inicializar DataTables
            tableHistory = $('#tblCcssHistory').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                searching: false,
                lengthChange: false,
                pageLength: 5
            });

            tableRenta = $('#tblRenta').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                searching: false,
                lengthChange: false,
                pageLength: 10
            });

            tableRubrosContables = $('#tblRubrosContables').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                searching: true,
                lengthChange: false,
                pageLength: 15
            });

            tableEmpresas = $('#tblEmpresas').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                searching: true,
                lengthChange: false,
                pageLength: 10
            });

            // 1. Cargar Cargas Sociales Activas
            loadActiveCcss();
            loadCcssHistory();

            // 2. Cargar Tramos de Impuesto de Renta
            loadRentaBrackets();

            // 3. Cargar Parámetros Generales
            loadGeneralConfig();

            // 4. Cargar Rubros Contables Mapeados
            loadRubrosAccounting();

            // 5. Cargar Empresas
            loadEmpresas();

            // Sumas interactivas en vivo (CCSS Obrero y Patrono)
            $('.calc-obrero').on('input change', function() {
                recalculateObreroTotal();
            });

            $('.calc-patrono').on('input change', function() {
                recalculatePatronoTotal();
            });

            // Registrar nuevo tramo de Renta
            $('#btnAddNewRenta').click(function() {
                $('#frmRenta')[0].reset();
                $('#txtCodRenta').val('0');
                $('#modalRentaTitle').html('<i class="fa-solid fa-coins text-primary me-2"></i>Registrar Tramo Renta');
                $('#modalRenta').modal('show');
            });

            // Envío formulario CCSS & Fodesaf
            $('#frmCCSS').on('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: '¿Aplicar cambios legales?',
                    text: 'Se inactivará la tasa anterior y se creará una nueva vigencia para la CCSS y FODESAF.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, aplicar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0056b3',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let formData = $('#frmCCSS').serialize() + '&action=save_ccss';
                        
                        $.ajax({
                            url: 'ajax/configuracion.php',
                            type: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('¡Tasas Guardadas!', res.message, 'success');
                                    loadActiveCcss();
                                    loadCcssHistory();
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Envío formulario Renta Hacienda
            $('#frmRenta').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();

                $.ajax({
                    url: 'ajax/configuracion.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalRenta').modal('hide');
                            Swal.fire('¡Correcto!', res.message, 'success');
                            loadRentaBrackets();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            });

            // Envío formulario Variables ERP
            $('#frmGeneral').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize() + '&action=save_general';

                $.ajax({
                    url: 'ajax/configuracion.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('¡ERP Configurado!', res.message, 'success');
                            loadGeneralConfig();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            });

            // Registrar nuevo rubro (Abrir Modal)
            $('#btnAddNewRubro').click(function() {
                $('#frmRubro')[0].reset();
                $('#txtCodRubro').val('0');
                $('#txtCodigoRubro').prop('disabled', false);
                $('#wrapperEstadoRubro').hide();
                $('#modalRubroTitle').html('<i class="fa-solid fa-folder-plus text-primary me-2"></i>Registrar Rubro de Planilla');
                $('#modalRubro').modal('show');
            });

            // Envío de Formulario de Rubro (Guardar/Editar)
            $('#frmRubro').on('submit', function(e) {
                e.preventDefault();
                
                const codigo = $('#txtCodigoRubro').val().trim();
                const descripcion = $('#txtDescripcionRubro').val().trim();
                if (!codigo || !descripcion) {
                    Swal.fire('Atención', 'Complete los campos obligatorios marcados con *.', 'warning');
                    return;
                }

                // Habilitar temporalmente para enviar en serialización si está editando
                $('#txtCodigoRubro').prop('disabled', false);
                const formData = $(this).serialize();
                if ($('#txtCodRubro').val() !== '0') {
                    $('#txtCodigoRubro').prop('disabled', true);
                }

                $.ajax({
                    url: 'ajax/configuracion.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalRubro').modal('hide');
                            Swal.fire({
                                title: '¡Rubro Guardado!',
                                text: res.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                background: 'var(--bg-surface)',
                                color: 'var(--text-primary)'
                            });
                            loadRubrosAccounting();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Fallo de conexión al guardar el rubro.', 'error');
                    }
                });
            });

            // Registrar nueva empresa (Abrir Modal)
            $('#btnAddNewEmpresa').click(function() {
                $('#frmEmpresa')[0].reset();
                $('#txtCodEmpresa').val('0');
                $('#wrapperEstadoEmpresa').hide();
                $('#modalEmpresaTitle').html('<i class="fa-solid fa-building text-primary me-2"></i>Registrar Empresa');
                $('#modalEmpresa').modal('show');
            });

            // Envío de Formulario de Empresa (Guardar/Editar)
            $('#frmEmpresa').on('submit', function(e) {
                e.preventDefault();
                
                const nombre = $('#txtNombreEmpresa').val().trim();
                const cedula = $('#txtCedulaJuridicaEmpresa').val().trim();
                if (!nombre || !cedula) {
                    Swal.fire('Atención', 'Razón Social y Cédula Jurídica son obligatorias.', 'warning');
                    return;
                }

                const formData = $(this).serialize();

                $.ajax({
                    url: 'ajax/configuracion.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalEmpresa').modal('hide');
                            Swal.fire({
                                title: '¡Empresa Guardada!',
                                text: res.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                background: 'var(--bg-surface)',
                                color: 'var(--text-primary)'
                            });
                            loadEmpresas();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Fallo de conexión al guardar la empresa.', 'error');
                    }
                });
            });

            // Acción Editar Empresa (Delegación de Eventos)
            $(document).on('click', '.btn-edit-empresa', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: 'ajax/configuracion.php?action=get_empresa&id=' + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            const emp = res.data;
                            $('#txtCodEmpresa').val(emp.CodEmpresa);
                            $('#txtNombreEmpresa').val(emp.Nombre);
                            $('#txtNombreComercialEmpresa').val(emp.NombreComercial || '');
                            $('#txtCedulaJuridicaEmpresa').val(emp.CedulaJuridica);
                            $('#txtTelefonoEmpresa').val(emp.Telefono || '');
                            $('#txtCorreoEmpresa').val(emp.Correo || '');
                            $('#txtDireccionEmpresa').val(emp.Direccion || '');
                            $('#txtCodigoCCSSEmpresa').val(emp.CodigoCCSS || '');
                            $('#txtCodigoINSEmpresa').val(emp.CodigoINS || '');
                            $('#ddlMonedaEmpresa').val(emp.Moneda || 'CRC');
                            $('#ddlEstadoEmpresa').val(emp.Estado || 'ACTIVO');
                            $('#txtLogoEmpresa').val(emp.Logo || '');
                            
                            $('#wrapperEstadoEmpresa').show();
                            $('#modalEmpresaTitle').html('<i class="fa-solid fa-edit text-primary me-2"></i>Editar Empresa: ' + emp.Nombre);
                            $('#modalEmpresa').modal('show');
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            });

            // Acción Inactivar Empresa
            $(document).on('click', '.btn-delete-empresa', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: '¿Inactivar Empresa?',
                    text: `La empresa "${nombre}" pasará a estado INACTIVO. Esto no borrará registros históricos, pero impedirá su selección en nuevos empleados o sucursales.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, inactivar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#d33',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'ajax/configuracion.php',
                            type: 'POST',
                            data: { action: 'delete_empresa', id: id },
                            dataType: 'json',
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('¡Inactivada!', res.message, 'success');
                                    loadEmpresas();
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Acción Activar Empresa
            $(document).on('click', '.btn-activate-empresa', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: '¿Activar Empresa?',
                    text: `La empresa "${nombre}" pasará a estado ACTIVO, permitiendo su selección en nuevos empleados y sucursales.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, activar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'ajax/configuracion.php',
                            type: 'POST',
                            data: { action: 'activate_empresa', id: id },
                            dataType: 'json',
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('¡Activada!', res.message, 'success');
                                    loadEmpresas();
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });
        });

        // recalculador en vivo: Obrero
        function recalculateObreroTotal() {
            let sem = parseFloat($('#txtSemObrero').val()) || 0;
            let ivm = parseFloat($('#txtIvmObrero').val()) || 0;
            let bp = parseFloat($('#txtBpObrero').val()) || 0;
            let total = sem + ivm + bp;
            $('#lblSumaObrera').text(total.toFixed(2) + '%');
        }

        // recalculador en vivo: Patrono
        function recalculatePatronoTotal() {
            let sem = parseFloat($('#txtSemPatrono').val()) || 0;
            let ivm = parseFloat($('#txtIvmPatrono').val()) || 0;
            let bp = parseFloat($('#txtBpPatrono').val()) || 0;
            let fodesaf = parseFloat($('#txtFodesaf').val()) || 0;
            let imas = parseFloat($('#txtImas').val()) || 0;
            let ina = parseFloat($('#txtIna').val()) || 0;
            let ins = parseFloat($('#txtIns').val()) || 0;
            let fcl = parseFloat($('#txtFcl').val()) || 0;
            let rop = parseFloat($('#txtRop').val()) || 0;
            
            let total = sem + ivm + bp + fodesaf + imas + ina + ins + fcl + rop;
            $('#lblSumaPatronal').text(total.toFixed(2) + '%');
        }

        // Cargar CCSS Activo
        function loadActiveCcss() {
            $.ajax({
                url: 'ajax/configuracion.php?action=get_active_ccss',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let d = res.data;
                        $('#txtSemObrero').val(parseFloat(d.SEMObrero).toFixed(2));
                        $('#txtIvmObrero').val(parseFloat(d.IVMObrero).toFixed(2));
                        $('#txtBpObrero').val(parseFloat(d.BancoPopular).toFixed(2));
                        
                        $('#txtSemPatrono').val(parseFloat(d.SEMPatrono).toFixed(2));
                        $('#txtIvmPatrono').val(parseFloat(d.IVMPatrono).toFixed(2));
                        $('#txtBpPatrono').val(parseFloat(d.BancoPopularPatrono).toFixed(2));
                        
                        $('#txtFodesaf').val(parseFloat(d.FODESAF).toFixed(2));
                        $('#txtImas').val(parseFloat(d.IMAS).toFixed(2));
                        $('#txtIna').val(parseFloat(d.INA).toFixed(2));
                        $('#txtFcl').val(parseFloat(d.FCL).toFixed(2));
                        $('#txtRop').val(parseFloat(d.ROP).toFixed(2));
                        $('#txtIns').val(parseFloat(d.INS).toFixed(2));

                        $('#txtVigenciaDesde').val(d.VigenciaDesde);
                        $('#txtVigenciaHasta').val(d.VigenciaHasta);

                        recalculateObreroTotal();
                        recalculatePatronoTotal();
                    }
                }
            });
        }

        // Historial CCSS
        function loadCcssHistory() {
            $.ajax({
                url: 'ajax/configuracion.php?action=list_ccss_history',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableHistory.clear();
                        
                        res.data.forEach(function(r) {
                            let totalObrero = parseFloat(r.SEMObrero) + parseFloat(r.IVMObrero) + parseFloat(r.BancoPopular);
                            let totalPatrono = parseFloat(r.SEMPatrono) + parseFloat(r.IVMPatrono) + parseFloat(r.BancoPopularPatrono) + parseFloat(r.FODESAF) + parseFloat(r.IMAS) + parseFloat(r.INA) + parseFloat(r.INS) + parseFloat(r.FCL) + parseFloat(r.ROP);

                            let badge = `<span class="badge bg-success">Activo</span>`;
                            if (r.Estado !== 'ACTIVO') {
                                badge = `<span class="badge bg-secondary">Histórico</span>`;
                            }

                            tableHistory.row.add([
                                r.Codigo,
                                r.VigenciaDesde,
                                r.VigenciaHasta,
                                `<strong>${totalObrero.toFixed(2)}%</strong>`,
                                `<strong>${totalPatrono.toFixed(2)}%</strong>`,
                                `${parseFloat(r.FODESAF).toFixed(2)}%`,
                                badge
                            ]);
                        });

                        tableHistory.draw();
                    }
                }
            });
        }

        // Cargar tramos renta
        function loadRentaBrackets() {
            $.ajax({
                url: 'ajax/configuracion.php?action=list_renta',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableRenta.clear();

                        res.data.forEach(function(r) {
                            let hastaStr = r.HastaMonto > 90000000 ? 'En adelante' : '₡' + parseFloat(r.HastaMonto).toLocaleString('es-CR');
                            
                            let btnEdit = `<button class="btn-table-action btn-table-edit" onclick="editRenta(${r.Codigo})" title="Editar"><i class="fa-solid fa-pen"></i></button>`;
                            let btnDelete = `<button class="btn-table-action btn-table-delete" onclick="deleteRenta(${r.Codigo})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                            
                            let acciones = `<div class="d-flex gap-2 justify-content-center">${btnEdit}${btnDelete}</div>`;

                            tableRenta.row.add([
                                r.Codigo,
                                `${r.VigenciaDesde} a ${r.VigenciaHasta}`,
                                `₡${parseFloat(r.DesdeMonto).toLocaleString('es-CR')}`,
                                hastaStr,
                                `<strong>${parseFloat(r.Porcentaje).toFixed(1)}%</strong>`,
                                `₡${parseFloat(r.Exceso).toLocaleString('es-CR')}`,
                                `₡${parseFloat(r.Base).toLocaleString('es-CR')}`,
                                acciones
                            ]);
                        });

                        tableRenta.draw();
                    }
                }
            });
        }

        // Editar renta
        function editRenta(id) {
            $.ajax({
                url: 'ajax/configuracion.php',
                type: 'GET',
                data: { action: 'get_renta', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let d = res.data;
                        $('#txtCodRenta').val(d.Codigo);
                        $('#txtDesdeMonto').val(d.DesdeMonto);
                        $('#txtHastaMonto').val(d.HastaMonto);
                        $('#txtPorcentajeRenta').val(d.Porcentaje);
                        $('#txtExcesoRenta').val(d.Exceso);
                        $('#txtBaseRenta').val(d.Base);

                        $('#modalRentaTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Tramo Renta');
                        $('#modalRenta').modal('show');
                    }
                }
            });
        }

        // Eliminar Renta
        function deleteRenta(id) {
            Swal.fire({
                title: '¿Eliminar tramo de renta?',
                text: 'Esta acción no se puede deshacer y afectará el cálculo del impuesto sobre la renta de la planilla.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/configuracion.php',
                        type: 'POST',
                        data: { action: 'delete_renta', id: id },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Eliminado', res.message, 'success');
                                loadRentaBrackets();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        }
                    });
                }
            });
        }

        // Cargar configuraciones generales del ERP
        function loadGeneralConfig() {
            $.ajax({
                url: 'ajax/configuracion.php?action=get_general',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let d = res.data;
                        $('#txtHorasSemana').val(d.HORAS_SEMANA || '48');
                        $('#txtDecimales').val(d.DECIMALES_PLANILLA || '2');
                        $('#ddlMoneda').val(d.MONEDA || 'CRC');
                    }
                }
            });
        }

        // Cargar Rubros y sus cuentas contables mapeadas
        function loadRubrosAccounting() {
            $.ajax({
                url: 'ajax/configuracion.php?action=list_rubros',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableRubrosContables.clear();
                        res.data.forEach(function(rubro) {
                            const cuenta = rubro.CuentaContable ? rubro.CuentaContable : '';
                            
                            // Creamos una entrada interactiva inline para editar la cuenta contable
                            const inputHtml = `
                                <div class="input-group input-group-sm" style="width: 260px;">
                                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-calculator"></i></span>
                                    <input type="text" class="form-control form-control-sm border-secondary text-light bg-dark rubro-cuenta-input" 
                                           data-cod="${rubro.CodRubro}" 
                                           value="${cuenta}" 
                                           placeholder="Mapear cuenta contable..."
                                           style="min-width: 200px; width: 200px; font-weight: bold; letter-spacing: 0.5px;">
                                </div>
                            `;

                            const saveBtnHtml = `
                                <button class="btn btn-sm btn-outline-success px-2 py-1 btn-save-rubro-account" 
                                        onclick="saveRubroAccount(${rubro.CodRubro}, this)" 
                                        title="Guardar Cuenta"
                                        style="border-radius: var(--border-radius-sm); font-size:12px; height: 32px; width: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                            `;

                            // Habilitar edición o inactivación solo para rubros no fundamentales del sistema
                            const isSystem = ['SAL', 'HEX', 'DES', 'CCSS', 'REN', 'BP'].includes(rubro.Codigo);
                            
                            const editBtnHtml = `
                                <button class="btn btn-sm btn-outline-primary px-2 py-1" 
                                        onclick="editRubro(${rubro.CodRubro})" 
                                        title="Editar Rubro"
                                        style="border-radius: var(--border-radius-sm); font-size:12px; height: 32px; width: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            `;
                            
                            const deleteBtnHtml = isSystem ? `
                                <button class="btn btn-sm btn-outline-secondary px-2 py-1" 
                                        disabled 
                                        title="Rubro del Sistema (Bloqueado)"
                                        style="border-radius: var(--border-radius-sm); font-size:12px; height: 32px; width: 32px; display: inline-flex; align-items: center; justify-content: center; opacity: 0.4;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            ` : `
                                <button class="btn btn-sm btn-outline-danger px-2 py-1" 
                                        onclick="deleteRubro(${rubro.CodRubro})" 
                                        title="Inactivar Rubro"
                                        style="border-radius: var(--border-radius-sm); font-size:12px; height: 32px; width: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            `;

                            const actionGroup = `<div class="d-flex gap-2 justify-content-center">${saveBtnHtml}${editBtnHtml}${deleteBtnHtml}</div>`;

                            const badgeTipo = rubro.Tipo === 'DEVENGO' 
                                ? `<span class="badge bg-success text-white" style="font-size: 11px; padding: 4px 8px;">DEVENGO (Ingreso)</span>` 
                                : `<span class="badge bg-danger text-white" style="font-size: 11px; padding: 4px 8px;">DEDUCCIÓN</span>`;

                            // Renderizar afectaciones legales
                            let affectations = [];
                            if (parseInt(rubro.AfectaCCSS) === 1) affectations.push('<span class="badge bg-info text-dark" style="font-size: 9px; font-weight:700;">CCSS</span>');
                            if (parseInt(rubro.AfectaRenta) === 1) affectations.push('<span class="badge bg-warning text-dark" style="font-size: 9px; font-weight:700;">RENTA</span>');
                            if (parseInt(rubro.AfectaVacaciones) === 1) affectations.push('<span class="badge bg-primary text-white" style="font-size: 9px; font-weight:700;">VAC</span>');
                            if (parseInt(rubro.AfectaAguinaldo) === 1) affectations.push('<span class="badge bg-secondary text-white" style="font-size: 9px; font-weight:700;">AGUI</span>');
                            const legalHtml = affectations.length > 0 ? `<div class="d-flex gap-1 flex-wrap">${affectations.join(' ')}</div>` : '<span class="text-muted" style="font-size: 11.5px;">Ninguna</span>';

                            tableRubrosContables.row.add([
                                `<strong class="text-primary font-monospace">${rubro.Codigo}</strong>`,
                                `<span class="text-white fw-semibold">${rubro.Descripcion}</span>`,
                                badgeTipo,
                                legalHtml,
                                inputHtml,
                                actionGroup
                            ]);
                        });
                        tableRubrosContables.draw();
                    }
                }
            });
        }

        // Editar Rubro
        window.editRubro = function(id) {
            $.ajax({
                url: 'ajax/configuracion.php',
                type: 'GET',
                data: { action: 'get_rubro', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        const d = res.data;
                        $('#txtCodRubro').val(d.CodRubro);
                        $('#txtCodigoRubro').val(d.Codigo).prop('disabled', true);
                        $('#txtDescripcionRubro').val(d.Descripcion);
                        $('#ddlTipoRubro').val(d.Tipo);
                        $('#txtPrioridadRubro').val(d.Prioridad);
                        $('#txtCuentaContableRubro').val(d.CuentaContable || '');
                        
                        $('#chkAfectaCCSS').prop('checked', parseInt(d.AfectaCCSS) === 1);
                        $('#chkAfectaRenta').prop('checked', parseInt(d.AfectaRenta) === 1);
                        $('#chkAfectaVacaciones').prop('checked', parseInt(d.AfectaVacaciones) === 1);
                        $('#chkAfectaAguinaldo').prop('checked', parseInt(d.AfectaAguinaldo) === 1);

                        $('#ddlEstadoRubro').val(d.Estado);
                        $('#wrapperEstadoRubro').show();
                        
                        $('#modalRubroTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Rubro de Planilla');
                        $('#modalRubro').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        };

        // Inactivar Rubro
        window.deleteRubro = function(id) {
            Swal.fire({
                title: '¿Está seguro de inactivar?',
                text: "El rubro ya no aparecerá como opción elegible para nuevos cálculos o configuraciones de planilla.",
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
                        url: 'ajax/configuracion.php',
                        type: 'POST',
                        data: { action: 'delete_rubro', id: id },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: '¡Inactivado!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadRubrosAccounting();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        }
                    });
                }
            });
        };

        // Guardar cuenta contable de rubro
        window.saveRubroAccount = function(codRubro, btnElement) {
            const inputVal = $(`.rubro-cuenta-input[data-cod="${codRubro}"]`).val().trim();
            
            $(btnElement).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            $.ajax({
                url: 'ajax/configuracion.php',
                type: 'POST',
                data: {
                    action: 'save_rubro_cuenta',
                    cod_rubro: codRubro,
                    cuenta_contable: inputVal
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: '¡Cuenta Guardada!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            background: 'var(--bg-surface)',
                            color: 'var(--text-primary)'
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Fallo de conexión al guardar la cuenta contable.', 'error');
                },
                complete: function() {
                    $(btnElement).prop('disabled', false).html('<i class="fa-solid fa-floppy-disk"></i>');
                }
            });
        };

        // Cargar Listado de Empresas
        function loadEmpresas() {
            $.ajax({
                url: 'ajax/configuracion.php?action=list_empresas',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableEmpresas.clear();
                        res.data.forEach(function(emp) {
                            let badgeClass = emp.Estado === 'ACTIVO' ? 'badge bg-success' : 'badge bg-danger';
                            let actionsHtml = `
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-empresa d-flex align-items-center gap-1" data-id="${emp.CodEmpresa}" style="font-size: 11.5px; padding: 4px 10px; border-radius: 5px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </button>
                                    ${emp.Estado === 'ACTIVO' ? `
                                    <button class="btn btn-sm btn-outline-danger btn-delete-empresa d-flex align-items-center gap-1" data-id="${emp.CodEmpresa}" data-nombre="${emp.Nombre}" style="font-size: 11.5px; padding: 4px 10px; border-radius: 5px;">
                                        <i class="fa-solid fa-trash-can"></i> Inactivar
                                    </button>
                                    ` : `
                                    <button class="btn btn-sm btn-outline-success btn-activate-empresa d-flex align-items-center gap-1" data-id="${emp.CodEmpresa}" data-nombre="${emp.Nombre}" style="font-size: 11.5px; padding: 4px 10px; border-radius: 5px;">
                                        <i class="fa-solid fa-check"></i> Activar
                                    </button>
                                    `}
                                </div>
                            `;
                            
                            tableEmpresas.row.add([
                                `<strong>${emp.CodEmpresa}</strong>`,
                                emp.NombreComercial || `<span class="text-muted">N/A</span>`,
                                emp.Nombre,
                                `<code style="font-size:12px; font-weight:bold;">${emp.CedulaJuridica}</code>`,
                                emp.Telefono || `<span class="text-muted">-</span>`,
                                `<strong>${emp.Moneda}</strong>`,
                                `<span class="${badgeClass}" style="font-size:11px; padding: 4px 8px; border-radius: 4px;">${emp.Estado}</span>`,
                                actionsHtml
                            ]);
                        });
                        tableEmpresas.draw();
                    } else {
                        console.error("Error al cargar empresas:", res.message);
                    }
                }
            });
        }
    </script>
</body>
</html>
