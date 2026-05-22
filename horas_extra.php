<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO Y REGISTRO DE HORAS EXTRA (HORAS_EXTRA.PHP)
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
    <title>Ingresos por Horas Extra - PlanillaCR ERP</title>
    
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
        
        .badge-status-pendiente {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .badge-status-procesado {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-status-anulado {
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

        /* Preview card de cálculo */
        .preview-calc-card {
            background-color: rgba(var(--primary-rgb), 0.06);
            border: 1px dashed var(--primary);
            border-radius: var(--border-radius-md);
            padding: 15px 20px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-calc-amount {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
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
                <li class="sidebar-item active">
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
                    <h1>Ingresos por Horas Extra</h1>
                    <p>Reportes de horas extraordinarias ordinarias (1.5x) y dobles (2.0x)</p>
                </div>
                
                <div class="header-actions">
                    <!-- Registrar Horas Extra -->
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewOvertime" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-clock-plus"></i>
                        <span>Registrar Horas Extra</span>
                    </button>

                    <!-- Cargar desde Excel (Lote) -->
                    <button class="btn btn-success px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnImportExcelOvertime" style="border-radius: var(--border-radius-md); background-color: #107c41; border-color: #107c41; box-shadow: 0 4px 12px rgba(16, 124, 65, 0.25);">
                        <i class="fa-solid fa-file-excel"></i>
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

            <!-- KPIs de Horas Extra -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Total Horas Reportadas</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiTotalHoras">0.00</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-plus-circle"></i> Acumulado</span>
                        <span class="kpi-desc">Horas extraordinarias totales</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Monto Total Estimado</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiMontoTotal">₡0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-coins"></i> CRC</span>
                        <span class="kpi-desc">Suma total de horas extra</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Horas por Procesar</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiPendientes">0.00</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral" style="color: var(--warning);"><i class="fa-solid fa-circle-exclamation"></i> Pendientes</span>
                        <span class="kpi-desc">A aplicar en planilla activa</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Horas Liquidadas</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiProcesadas">0.00</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up" style="color: #8b5cf6;"><i class="fa-solid fa-circle-check"></i> Procesadas</span>
                        <span class="kpi-desc">Pagadas históricamente</span>
                    </div>
                </div>
            </section>

            <!-- Tabla de Datos -->
            <div class="table-responsive">
                <table id="tblOvertime" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Colaborador</th>
                            <th>Fecha</th>
                            <th>Horas</th>
                            <th>Multiplicador</th>
                            <th>Monto Calculado</th>
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

    <!-- MODAL DE REGISTRO / EDICIÓN DE HORA EXTRA -->
    <div class="modal fade" id="modalOvertime" tabindex="-1" aria-labelledby="modalOvertimeTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalOvertimeTitle"><i class="fa-solid fa-clock-plus text-primary me-2"></i>Registrar Horas Extra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmOvertime" novalidate>
                    <div class="modal-body px-4 py-3">
                        <input type="hidden" name="cod_hora_extra" id="txtCodHoraExtra" value="0">
                        <input type="hidden" name="action" id="txtAction" value="save_overtime">
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
                                <label for="txtFecha" class="form-label-desc">Fecha Reportada <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha" id="txtFecha" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="txtCantidadHoras" class="form-label-desc">Cantidad de Horas <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="cantidad_horas" id="txtCantidadHoras" placeholder="Ej: 2.50" step="0.25" min="0.1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ddlFactor" class="form-label-desc">Multiplicador de Ley <span class="text-danger">*</span></label>
                                <select class="form-select" name="factor_multiplicador" id="ddlFactor" required>
                                    <option value="1.50" selected>1.50x (Hora Extra Ordinaria - 1.5x)</option>
                                    <option value="2.00">2.00x (Hora Extra Doble / Descanso / Feriado - 2x)</option>
                                    <option value="2.20">2.20x (Hora Extra Convenio Especial - 2.2x)</option>
                                    <option value="3.00">3.00x (Hora Extra Triple - Domingo y Feriado Ley - 3x)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="wrapperEstado" style="display: none;">
                                <label for="ddlEstado" class="form-label-desc">Estado de Pago</label>
                                <select class="form-select" name="estado" id="ddlEstado">
                                    <option value="PENDIENTE">PENDIENTE</option>
                                    <option value="PROCESADO" disabled>PROCESADO</option>
                                    <option value="ANULADO">ANULADO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="txtObservacion" class="form-label-desc">Observación / Justificación</label>
                            <textarea class="form-control" name="observacion" id="txtObservacion" placeholder="Detalle el motivo de las horas extraordinarias ejecutadas..." rows="3" maxlength="500"></textarea>
                        </div>

                        <!-- Preview Card Estimación -->
                        <div class="preview-calc-card" id="previewCalculo">
                            <div>
                                <div class="fw-semibold text-secondary" style="font-size: 11.5px; text-transform: uppercase;">Salario por Hora Estimado</div>
                                <div class="fw-bold" id="lblSalarioHora" style="font-size: 15px;">₡0.00</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold text-secondary" style="font-size: 11.5px; text-transform: uppercase;">Pago Extra Estimado</div>
                                <div class="preview-calc-amount" id="lblPreviewMonto">₡0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: var(--border-radius-md); font-size: 13.5px;" id="btnSaveOvertime">Guardar Reporte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE IMPORTACIÓN MASIVA DESDE EXCEL -->
    <div class="modal fade" id="modalImportarExtras" tabindex="-1" aria-labelledby="modalImportarExtrasTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color); background-color: rgba(16, 124, 65, 0.05);">
                    <h5 class="modal-title fw-bold text-success" id="modalImportarExtrasTitle">
                        <i class="fa-solid fa-file-excel me-2"></i>Carga Masiva de Horas Extra desde Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <!-- Paso 1: Instrucciones y pegar -->
                    <div id="stepExcelPaste">
                        <div class="alert alert-info d-flex align-items-start gap-3 mb-3" style="background-color: rgba(var(--primary-rgb), 0.05); border: 1px dashed var(--primary); color: var(--text-primary); border-radius: var(--border-radius-md);">
                            <i class="fa-solid fa-circle-info mt-1 text-primary" style="font-size: 18px;"></i>
                            <div>
                                <span class="fw-bold text-primary d-block mb-1">Instrucciones de Carga Rápida:</span>
                                Abra su archivo de Excel y copie las celdas deseadas. Asegúrese de que tengan las columnas en este orden lógico:
                                <code class="d-block mt-1 font-monospace" style="color: var(--primary); font-size: 13px;">Cédula / Identificación [Tabulación] Cantidad de Horas [Tabulación] Fecha (Opcional) [Tabulación] Multiplicador (Opcional) [Tabulación] Observación</code>
                                *Si no especifica la fecha, se tomará el día de **hoy**. Si no especifica el multiplicador, se usará **1.50x** por defecto.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="txtExcelPaste" class="form-label-desc">Pegue las celdas de Excel aquí <span class="text-danger">*</span></label>
                            <textarea class="form-control font-monospace" id="txtExcelPaste" rows="8" placeholder="Pegue el bloque de columnas de Excel aquí...&#10;Ejemplo:&#10;102340567	2.50	2026-05-17	1.50	Horas extra soporte servidor&#10;203450987	4.00	2026-05-17	2.00	Feriado de ley trabajado" style="font-size: 13px; background-color: var(--bg-surface-elevated); border-color: var(--border-color); color: var(--text-primary);"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-primary px-4 py-2 fw-semibold" id="btnParseExcel" style="border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Analizar y Validar Datos
                            </button>
                        </div>
                    </div>

                    <!-- Paso 2: Vista previa interactiva con checklist -->
                    <div id="stepExcelPreview" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-secondary uppercase-title" style="letter-spacing: 0.5px; font-size: 12px;"><i class="fa-solid fa-table-list me-2"></i>Registros Encontrados e Inclusiones</h6>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnBackToPaste" style="border-radius: var(--border-radius-sm); font-size: 12px;">
                                <i class="fa-solid fa-arrow-left me-1"></i> Cambiar Datos / Pegar de nuevo
                            </button>
                        </div>

                        <div class="table-responsive mb-3" style="max-height: 380px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--border-radius-md);">
                            <table class="table table-hover table-striped w-100 mb-0 align-middle" id="tblExcelPreview">
                                <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">
                                            <input type="checkbox" class="form-check-input" id="chkSelectAllExcel" checked>
                                        </th>
                                        <th style="width: 130px;">Estado</th>
                                        <th>Colaborador</th>
                                        <th style="width: 120px;">Fecha</th>
                                        <th style="width: 90px;">Horas</th>
                                        <th style="width: 100px;">Multiplicador</th>
                                        <th>Monto Estimado</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyExcelPreview">
                                    <!-- Inyectado dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <div class="row align-items-center" style="background-color: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 15px; margin: 0;">
                            <div class="col-md-7">
                                <div class="d-flex gap-4">
                                    <div>
                                        <span class="text-secondary d-block fw-semibold" style="font-size: 11px; text-transform: uppercase;">Total Registrados</span>
                                        <span class="fw-bold text-primary" style="font-size: 18px;" id="lblTotalParsed">0</span>
                                    </div>
                                    <div style="border-left: 1px solid var(--border-color); padding-left: 20px;">
                                        <span class="text-secondary d-block fw-semibold" style="font-size: 11px; text-transform: uppercase;">Listos para Importar</span>
                                        <span class="fw-bold text-success" style="font-size: 18px;" id="lblReadyToImport">0</span>
                                    </div>
                                    <div style="border-left: 1px solid var(--border-color); padding-left: 20px;">
                                        <span class="text-secondary d-block fw-semibold" style="font-size: 11px; text-transform: uppercase;">Errores Encontrados</span>
                                        <span class="fw-bold text-danger" style="font-size: 18px;" id="lblErrorCount">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 text-end">
                                <div class="d-inline-block text-start me-4">
                                    <span class="text-secondary d-block fw-semibold text-end" style="font-size: 11px; text-transform: uppercase;">Monto Total Estimado</span>
                                    <span class="fw-bold text-success d-block text-end" style="font-size: 20px;" id="lblMontoTotalBatch">₡0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cerrar</button>
                    <button type="button" class="btn btn-success px-4 py-2 fw-semibold" id="btnProcessImport" disabled style="border-radius: var(--border-radius-md); font-size: 13.5px; background-color: #107c41; border-color: #107c41;">
                        <i class="fa-solid fa-file-import me-2"></i>Procesar Importación
                    </button>
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

        // CONTROLADOR DE DATOS DE HORAS EXTRA
        let tableOvertime;
        let employeesList = [];

        $(document).ready(function() {
            // Inicializar DataTables
            tableOvertime = $('#tblOvertime').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: 7 }
                ]
            });

            // Cargar colaboradores y tabla al inicio
            loadEmployees();
            loadOvertimeData();

            // Abrir Modal de Registro
            $('#btnAddNewOvertime').click(function() {
                $('#frmOvertime')[0].reset();
                $('#txtCodHoraExtra').val('0');
                $('#ddlEmpleado').prop('disabled', false);
                $('#wrapperEstado').hide();
                $('#lblSalarioHora').text('₡0.00');
                $('#lblPreviewMonto').text('₡0.00');
                $('#modalOvertimeTitle').html('<i class="fa-solid fa-clock-plus text-primary me-2"></i>Registrar Horas Extra');
                $('#modalOvertime').modal('show');
            });

            // ==========================================================================
            // LOGIC FOR EXCEL IMPORT AND BATCH PARSING
            // ==========================================================================
            // Abrir Modal de Importación Masiva
            $('#btnImportExcelOvertime').click(function() {
                $('#txtExcelPaste').val('');
                $('#stepExcelPaste').show();
                $('#stepExcelPreview').hide();
                $('#btnProcessImport').prop('disabled', true);
                $('#lblImportCount').text('0');
                $('#modalImportarExtras').modal('show');
            });

            // Volver al Paso 1 (Pegado)
            $('#btnBackToPaste').click(function() {
                $('#stepExcelPaste').show();
                $('#stepExcelPreview').hide();
            });

            let parsedRows = []; // Almacenar filas analizadas

            // Parsear y Validar Datos Pegados de Excel
            $('#btnParseExcel').click(function() {
                let pasteText = $('#txtExcelPaste').val().trim();
                if (pasteText === '') {
                    Swal.fire('Atención', 'Por favor pegue algún dato desde Excel antes de continuar.', 'warning');
                    return;
                }

                // Mostrar Loader de análisis
                Swal.fire({
                    title: 'Analizando datos...',
                    text: 'Validando colaboradores en base de datos...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Parsear filas
                let lines = pasteText.split('\n');
                parsedRows = [];
                let cedulasToValidate = [];

                lines.forEach(function(line) {
                    if (line.trim() === '') return;

                    // Excel usa tabulación (\t) para separar celdas. Si no hay tab, buscar comas o punto y coma
                    let cols = [];
                    if (line.indexOf('\t') > -1) {
                        cols = line.split('\t');
                    } else if (line.indexOf(';') > -1) {
                        cols = line.split(';');
                    } else {
                        cols = line.split(',');
                    }

                    // Limpiar columnas
                    let cedula = cols[0] ? cols[0].trim() : '';
                    let horas = cols[1] ? parseFloat(cols[1].trim()) : 0;
                    let fecha = (cols[2] && cols[2].trim() !== '') ? cols[2].trim() : '<?php echo date("Y-m-d"); ?>';
                    let factor = (cols[3] && cols[3].trim() !== '') ? parseFloat(cols[3].trim()) : 1.50;
                    let obs = cols[4] ? cols[4].trim() : 'Carga masiva Excel';

                    if (cedula !== '' && !isNaN(horas) && horas > 0) {
                        parsedRows.push({
                            cedula: cedula,
                            horas: horas,
                            fecha: fecha,
                            factor: factor,
                            observacion: obs,
                            valido: false,
                            cod_empleado: 0,
                            colaborador: '',
                            salario_hora: 0,
                            monto: 0
                        });
                        cedulasToValidate.push(cedula);
                    }
                });

                if (parsedRows.length === 0) {
                    Swal.close();
                    Swal.fire('Error', 'No se detectó ninguna fila válida. Asegúrese de incluir al menos Cédula y Cantidad de Horas en columnas de Excel.', 'error');
                    return;
                }

                // Llamar al backend para validar Cédulas masivamente
                $.ajax({
                    url: 'ajax/horas_extra.php?action=validate_cedulas',
                    type: 'POST',
                    data: { cedulas: JSON.stringify(cedulasToValidate) },
                    dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        if (res.success) {
                            let mappedData = res.data;
                            let tbody = $('#tbodyExcelPreview');
                            tbody.empty();

                            let totalCount = parsedRows.length;
                            let errorCount = 0;
                            let readyCount = 0;
                            let totalMonto = 0;

                            parsedRows.forEach(function(row, index) {
                                let match = mappedData[row.cedula];
                                if (match) {
                                    row.valido = true;
                                    row.cod_empleado = match.CodEmpleado;
                                    row.cod_empresa = match.CodEmpresa;
                                    row.colaborador = match.Colaborador;
                                    row.salario_hora = match.SalarioHora;
                                    row.monto = Math.round(row.horas * row.factor * match.SalarioHora * 100) / 100;
                                    
                                    readyCount++;
                                    totalMonto += row.monto;
                                } else {
                                    row.valido = false;
                                    errorCount++;
                                }

                                let badge = row.valido 
                                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Válido</span>' 
                                    : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="Cédula no registrada o inactiva"><i class="fa-solid fa-circle-xmark me-1"></i> No registrado</span>';
                                
                                let chk = row.valido 
                                    ? `<input type="checkbox" class="form-check-input chk-excel-row" data-index="${index}" checked>` 
                                    : `<input type="checkbox" class="form-check-input" disabled>`;

                                let colName = row.valido ? `<span class="fw-semibold">${row.colaborador}</span>` : `<span class="text-danger font-monospace fw-bold">${row.cedula}</span>`;
                                let calculatedAmount = row.valido ? `₡${row.monto.toLocaleString('es-CR', { minimumFractionDigits: 2 })}` : '₡0.00';

                                tbody.append(`
                                    <tr class="${!row.valido ? 'table-danger-subtle opacity-75' : ''}">
                                        <td class="text-center">${chk}</td>
                                        <td>${badge}</td>
                                        <td>${colName}</td>
                                        <td>${row.fecha}</td>
                                        <td>${row.horas.toFixed(2)} hrs</td>
                                        <td>${row.factor.toFixed(2)}x</td>
                                        <td class="fw-bold ${row.valido ? 'text-success' : 'text-muted'}">${calculatedAmount}</td>
                                        <td class="text-truncate" style="max-width: 150px;" title="${row.observacion}">${row.observacion}</td>
                                    </tr>
                                `);
                            });

                            // Actualizar contadores
                            $('#lblTotalParsed').text(totalCount);
                            $('#lblReadyToImport').text(readyCount);
                            $('#lblErrorCount').text(errorCount);
                            $('#lblMontoTotalBatch').text('₡' + totalMonto.toLocaleString('es-CR', { minimumFractionDigits: 2 }));

                            // Activar botón de procesamiento si hay listos
                            $('#btnProcessImport').prop('disabled', readyCount === 0);

                            // Mostrar sección de vista previa
                            $('#stepExcelPaste').hide();
                            $('#stepExcelPreview').show();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(err) {
                        Swal.close();
                        Swal.fire('Error', 'Fallo al validar las cédulas: ' + err.statusText, 'error');
                    }
                });
            });

            // Seleccionar/Deseleccionar Todo en Excel Preview
            $('#chkSelectAllExcel').change(function() {
                let isChecked = $(this).is(':checked');
                $('.chk-excel-row').prop('checked', isChecked).trigger('change');
            });

            // Recalcular montos al marcar/desmarcar individualmente
            $(document).on('change', '.chk-excel-row', function() {
                let readyCount = 0;
                let totalMonto = 0;

                $('.chk-excel-row:checked').each(function() {
                    let idx = $(this).data('index');
                    let r = parsedRows[idx];
                    readyCount++;
                    totalMonto += r.monto;
                });

                $('#lblReadyToImport').text(readyCount);
                $('#lblMontoTotalBatch').text('₡' + totalMonto.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                $('#btnProcessImport').prop('disabled', readyCount === 0);
            });

            // Procesar Importación Masiva (AJAX Batch Insert)
            $('#btnProcessImport').click(function() {
                let selectedRows = [];
                $('.chk-excel-row:checked').each(function() {
                    let idx = $(this).data('index');
                    let r = parsedRows[idx];
                    selectedRows.push({
                        cod_empleado: r.cod_empleado,
                        cod_empresa: r.cod_empresa || 1,
                        fecha: r.fecha,
                        cantidad_horas: r.horas,
                        factor_multiplicador: r.factor,
                        observacion: r.observacion
                    });
                });

                if (selectedRows.length === 0) {
                    Swal.fire('Atención', 'Seleccione al menos un registro válido para importar.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Procesando importación masiva...',
                    text: 'Insertando reportes en la base de datos de manera transaccional...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'ajax/horas_extra.php?action=save_batch_overtime',
                    type: 'POST',
                    data: { batch_data: JSON.stringify(selectedRows) },
                    dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        if (res.success) {
                            $('#modalImportarExtras').modal('hide');
                            Swal.fire({
                                title: '¡Carga Masiva Exitosa!',
                                text: res.message,
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: true,
                                confirmButtonText: 'Excelente'
                            });
                            loadOvertimeData();
                        } else {
                            Swal.fire('Error de Importación', res.message, 'error');
                        }
                    },
                    error: function(err) {
                        Swal.close();
                        Swal.fire('Error', 'Fallo al procesar lote: ' + err.statusText, 'error');
                    }
                });
            });

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
                        $('#txtCantidadHoras').focus().select();
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
                                    $('#txtCantidadHoras').focus().select();
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

            // Reseteo de Cédula Rápida al registrar nuevo
            $('#btnAddNewOvertime').click(function() {
                $('#txtCedulaRapida').val('');
                $('#lblCedulaStatus').text('');
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

            // Escuchar cambios para actualizar cálculo dinámico
            $('#ddlEmpleado, #txtCantidadHoras, #ddlFactor').on('change keyup', function() {
                updateCalculoPreview();
            });

            // Envío del Formulario
            $('#frmOvertime').on('submit', function(e) {
                e.preventDefault();
                
                // Validaciones
                let emp = $('#ddlEmpleado').val();
                let fecha = $('#txtFecha').val();
                let horas = parseFloat($('#txtCantidadHoras').val());
                
                if (!emp || !fecha || isNaN(horas) || horas <= 0) {
                    Swal.fire('Atención', 'Complete los campos obligatorios con valores correctos.', 'warning');
                    return;
                }

                let formData = $(this).serialize();

                $.ajax({
                    url: 'ajax/horas_extra.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalOvertime').modal('hide');
                            Swal.fire({
                                title: '¡Éxito!',
                                text: res.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadOvertimeData();
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

        // Cargar colaboradores para el selector
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

        // Cargar los reportes de horas extra a la tabla
        function loadOvertimeData() {
            $.ajax({
                url: 'ajax/horas_extra.php?action=list_overtime',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableOvertime.clear();
                        
                        let totalHoras = 0;
                        let totalMonto = 0;
                        let pendientes = 0;
                        let procesadas = 0;

                        res.data.forEach(function(r) {
                            let badgeClass = 'badge-status-pendiente';
                            if (r.Estado === 'PROCESADO') badgeClass = 'badge-status-procesado';
                            if (r.Estado === 'ANULADO') badgeClass = 'badge-status-anulado';

                            let badge = `<span class="badge-status ${badgeClass}">${r.Estado}</span>`;
                            
                            // Acumular KPIs
                            let horas = parseFloat(r.CantidadHoras);
                            let monto = parseFloat(r.MontoCalculado);
                            
                            totalHoras += horas;
                            totalMonto += monto;

                            if (r.Estado === 'PENDIENTE') pendientes += horas;
                            if (r.Estado === 'PROCESADO') procesadas += horas;

                            let btnEdit = '';
                            let btnDelete = '';
                            
                            if (r.Estado === 'PENDIENTE') {
                                btnEdit = `<button class="btn-table-action btn-table-edit" onclick="editOvertime(${r.CodHoraExtra})" title="Editar"><i class="fa-solid fa-pen"></i></button>`;
                                btnDelete = `<button class="btn-table-action btn-table-delete" onclick="deleteOvertime(${r.CodHoraExtra})" title="Anular"><i class="fa-solid fa-ban"></i></button>`;
                            } else {
                                btnEdit = `<button class="btn-table-action" disabled title="No editable en estado procesado"><i class="fa-solid fa-lock"></i></button>`;
                            }

                            let acciones = `<div class="d-flex gap-2 justify-content-center">${btnEdit}${btnDelete}</div>`;

                            tableOvertime.row.add([
                                r.CodHoraExtra,
                                `<span class="fw-semibold">${r.Colaborador}</span>`,
                                r.Fecha,
                                `${horas.toFixed(2)} hrs`,
                                `${parseFloat(r.FactorMultiplicador).toFixed(2)}x`,
                                `<span class="fw-bold">₡${monto.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`,
                                badge,
                                acciones
                            ]);
                        });

                        tableOvertime.draw();

                        // Actualizar tarjetas de KPI
                        $('#kpiTotalHoras').text(totalHoras.toFixed(2));
                        $('#kpiMontoTotal').text('₡' + totalMonto.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpiPendientes').text(pendientes.toFixed(2));
                        $('#kpiProcesadas').text(procesadas.toFixed(2));
                    }
                }
            });
        }

        // Dinámico: estimación del cálculo
        function updateCalculoPreview() {
            let emp = $('#ddlEmpleado').val();
            let horas = parseFloat($('#txtCantidadHoras').val());
            let factor = $('#ddlFactor').val();

            if (!emp || isNaN(horas) || horas <= 0) {
                $('#lblSalarioHora').text('₡0.00');
                $('#lblPreviewMonto').text('₡0.00');
                return;
            }

            $.ajax({
                url: 'ajax/horas_extra.php',
                type: 'GET',
                data: {
                    action: 'calculate_preview',
                    cod_empleado: emp,
                    horas: horas,
                    factor: factor
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#lblSalarioHora').text('₡' + parseFloat(res.salario_hora).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#lblPreviewMonto').text('₡' + parseFloat(res.monto).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    }
                }
            });
        }

        // Editar Registro
        function editOvertime(id) {
            $.ajax({
                url: 'ajax/horas_extra.php',
                type: 'GET',
                data: { action: 'get_overtime', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let d = res.data;
                        $('#txtCodHoraExtra').val(d.CodHoraExtra);
                        $('#ddlEmpleado').val(d.CodEmpleado).prop('disabled', true); // No cambiar colaborador en edición
                        $('#txtFecha').val(d.Fecha);
                        $('#txtCantidadHoras').val(d.CantidadHoras);
                        $('#ddlFactor').val(parseFloat(d.FactorMultiplicador).toFixed(2));
                        $('#ddlEstado').val(d.Estado);
                        $('#txtObservacion').val(d.Observacion);
                        
                        $('#wrapperEstado').show();
                        $('#modalOvertimeTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Reporte de Horas Extra');
                        
                        updateCalculoPreview();
                        $('#modalOvertime').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }

        // Eliminar/Anular Registro
        function deleteOvertime(id) {
            Swal.fire({
                title: '¿Está seguro?',
                text: "El reporte de horas extra se anulará lógicamente y no se tomará en cuenta para el cálculo de planillas.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/horas_extra.php',
                        type: 'POST',
                        data: { action: 'delete_overtime', id: id },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: '¡Anulado!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadOvertimeData();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
