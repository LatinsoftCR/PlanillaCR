<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO DE CLASIFICACIONES DE DEDUCCIÓN (CLASIFICACIONES_DEDUCCIONES.PHP)
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
    <title>Clasificaciones de Deducción - PlanillaCR ERP</title>
    
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
        
        .dataTables_filter input:focus {
            border-color: var(--primary) !important;
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
        
        .badge-status-si {
            background-color: rgba(0, 86, 179, 0.1);
            color: var(--primary);
            border: 1px solid rgba(0, 86, 179, 0.2);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
        }
        
        .badge-status-no {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--text-muted);
            border: 1px solid rgba(108, 117, 125, 0.2);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
        }

        /* Modales */
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
        
        /* Botones de acción de tabla */
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
        
        <!-- SIDEBAR LATERAL FIJA -->
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
                <li class="sidebar-item active">
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
                    <h1>Rubros de Deducción</h1>
                    <p>Mantenimiento de clasificaciones y parámetros de afectación de deducciones de ley y voluntarias</p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewRubro" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-plus"></i>
                        <span>Registrar Clasificación</span>
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

            <!-- KPIs de Rubros -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Total Clasificaciones</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                            <i class="fa-solid fa-tag"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiTotalRubros">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral"><i class="fa-solid fa-list-ul"></i> Catálogo Rubros</span>
                        <span class="kpi-desc">Deducciones parametrizadas</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Rubros Activos</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiActivosRubros" style="color: var(--success);">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up" style="color: var(--success);"><i class="fa-solid fa-toggle-on"></i> Habilitados</span>
                        <span class="kpi-desc">Disponibles en el digitado</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Afectan CCSS / Nómina</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(239, 68, 68, 0.1); color: var(--danger);">
                            <i class="fa-solid fa-shield-virus"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiAfectanCCSS" style="color: var(--danger);">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral" style="color: var(--danger);"><i class="fa-solid fa-calculator"></i> Rebajo de Ley</span>
                        <span class="kpi-desc">Afectan base de CCSS</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Afectan Renta</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiAfectanRenta" style="color: var(--warning);">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral" style="color: var(--warning);"><i class="fa-solid fa-percent"></i> Base Impositiva</span>
                        <span class="kpi-desc">Disminuyen base gravable</span>
                    </div>
                </div>
            </section>

            <!-- Tabla de Datos -->
            <div class="table-responsive">
                <table id="tblRubros" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción Clasificación</th>
                            <th>Prioridad</th>
                            <th class="text-center">Afecta CCSS</th>
                            <th class="text-center">Afecta Renta</th>
                            <th class="text-center">Afecta Vac.</th>
                            <th class="text-center">Afecta Aguinaldo</th>
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

    <!-- MODAL DE AGREGAR / EDITAR CLASIFICACIÓN (RUBRO) -->
    <div class="modal fade" id="modalRubro" tabindex="-1" aria-labelledby="modalRubroTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalRubroTitle"><i class="fa-solid fa-list-check text-primary me-2"></i>Registrar Clasificación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close);"></button>
                </div>
                <form id="frmRubro" novalidate>
                    <div class="modal-body px-4 py-3">
                        <input type="hidden" name="cod_rubro" id="txtCodRubro" value="0">
                        <input type="hidden" name="action" id="txtAction" value="save_rubro">
                        
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="txtCodigo" class="form-label-desc">Código Interno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace" name="codigo" id="txtCodigo" placeholder="Ej: CCSS, EMB_JUD" required maxlength="20" style="text-transform: uppercase;">
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Identificador único corto.</small>
                            </div>
                            <div class="col-md-7 mb-3">
                                <label for="txtDescripcion" class="form-label-desc">Descripción Clasificación <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="descripcion" id="txtDescripcion" placeholder="Ej: Deducción CCSS Obrero, Préstamos" required maxlength="150">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="txtPrioridad" class="form-label-desc">Prioridad de Aplicación</label>
                                <input type="number" class="form-control" name="prioridad" id="txtPrioridad" placeholder="Ej: 1, 2, 3" min="0" value="0">
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Determina el orden de rebajo en la nómina.</small>
                            </div>
                            <div class="col-md-6 mb-3" id="wrapperEstado" style="display: none;">
                                <label for="ddlEstado" class="form-label-desc">Estado del Rubro</label>
                                <select class="form-select" name="estado" id="ddlEstado">
                                    <option value="ACTIVO">ACTIVO (Disponible)</option>
                                    <option value="INACTIVO">INACTIVO (Suspendido)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Panel de Afectaciones Legales en Costa Rica -->
                        <div class="p-3 border rounded mb-2 mt-2" style="background-color: rgba(var(--primary-rgb), 0.02); border-color: var(--border-color) !important;">
                            <h6 class="fw-bold mb-3 text-secondary" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Parámetros de Afectación Legal (C.R.)</h6>
                            
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="form-check form-switch d-flex align-items-center gap-3">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_ccss" id="chkAfectaCCSS">
                                        <label class="form-check-label form-label-desc mb-0" style="cursor: pointer;" for="chkAfectaCCSS">¿Afecta CCSS?</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch d-flex align-items-center gap-3">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_renta" id="chkAfectaRenta">
                                        <label class="form-check-label form-label-desc mb-0" style="cursor: pointer;" for="chkAfectaRenta">¿Afecta Renta?</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch d-flex align-items-center gap-3">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_vacaciones" id="chkAfectaVacaciones">
                                        <label class="form-check-label form-label-desc mb-0" style="cursor: pointer;" for="chkAfectaVacaciones">¿Afecta Vacaciones?</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch d-flex align-items-center gap-3">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="afecta_aguinaldo" id="chkAfectaAguinaldo">
                                        <label class="form-check-label form-label-desc mb-0" style="cursor: pointer;" for="chkAfectaAguinaldo">¿Afecta Aguinaldo?</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md); font-size: 13.5px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: var(--border-radius-md); font-size: 13.5px;" id="btnSaveRubro">Guardar Clasificación</button>
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

        let tableRubros;

        $(document).ready(function() {
            // Inicializar DataTables
            tableRubros = $('#tblRubros').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: 8 }
                ]
            });

            // Cargar datos del CRUD
            loadRubrosData();

            // Abrir Modal de Creación
            $('#btnAddNewRubro').click(function() {
                $('#frmRubro')[0].reset();
                $('#txtCodRubro').val('0');
                $('#txtCodigo').prop('readonly', false);
                $('#wrapperEstado').hide();
                $('#modalRubroTitle').html('<i class="fa-solid fa-list-check text-primary me-2"></i>Registrar Clasificación de Deducción');
                $('#modalRubro').modal('show');
            });

            // Tecla Enter para navegación en formulario
            $('#frmRubro').on('keydown', 'input, select', function(e) {
                if (e.which === 13) {
                    if (this.type === 'submit') return;
                    e.preventDefault();
                    let inputs = $(this).closest('form').find(':input:visible:not([readonly]):not([disabled])');
                    inputs = inputs.filter(function() {
                        return !$(this).hasClass('btn-close') && !$(this).hasClass('btn-outline-secondary');
                    });
                    let idx = inputs.index(this);
                    if (idx > -1 && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    }
                }
            });

            // Envío del Formulario
            $('#frmRubro').on('submit', function(e) {
                e.preventDefault();

                let codigo = $('#txtCodigo').val().trim();
                let desc = $('#txtDescripcion').val().trim();

                if (!codigo || !desc) {
                    Swal.fire('Atención', 'Complete los campos requeridos.', 'warning');
                    return;
                }

                $.ajax({
                    url: 'ajax/deducciones.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#modalRubro').modal('hide');
                            Swal.fire({
                                title: '¡Éxito!',
                                text: res.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadRubrosData();
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

        // Cargar todos los rubros en el DataTable
        function loadRubrosData() {
            $.ajax({
                url: 'ajax/deducciones.php?action=list_rubros',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        tableRubros.clear();

                        let total = 0;
                        let activos = 0;
                        let ccss = 0;
                        let renta = 0;

                        res.data.forEach(function(r) {
                            total++;
                            if (r.Estado === 'ACTIVO') activos++;
                            if (r.AfectaCCSS == 1) ccss++;
                            if (r.AfectaRenta == 1) renta++;

                            let badgeClass = r.Estado === 'ACTIVO' ? 'badge-status-activo' : 'badge-status-inactivo';
                            let badge = `<span class="badge-status ${badgeClass}">${r.Estado}</span>`;

                            let valCCSS = r.AfectaCCSS == 1 ? '<span class="badge-status-si">SÍ</span>' : '<span class="badge-status-no">NO</span>';
                            let valRenta = r.AfectaRenta == 1 ? '<span class="badge-status-si">SÍ</span>' : '<span class="badge-status-no">NO</span>';
                            let valVac = r.AfectaVacaciones == 1 ? '<span class="badge-status-si">SÍ</span>' : '<span class="badge-status-no">NO</span>';
                            let valAguin = r.AfectaAguinaldo == 1 ? '<span class="badge-status-si">SÍ</span>' : '<span class="badge-status-no">NO</span>';

                            let btnEdit = `<button class="btn-table-action btn-table-edit" onclick="editRubro(${r.CodRubro})" title="Editar"><i class="fa-solid fa-pen"></i></button>`;
                            let btnDelete = '';
                            
                            if (r.Estado === 'ACTIVO') {
                                btnDelete = `<button class="btn-table-action btn-table-delete" onclick="deleteRubro(${r.CodRubro})" title="Inactivar"><i class="fa-solid fa-circle-minus"></i></button>`;
                            } else {
                                btnDelete = `<button class="btn-table-action" disabled title="Inactivo"><i class="fa-solid fa-lock"></i></button>`;
                            }

                            let acciones = `<div class="d-flex gap-2 justify-content-center">${btnEdit}${btnDelete}</div>`;

                            tableRubros.row.add([
                                `<span class="fw-bold font-monospace text-primary">[${r.Codigo}]</span>`,
                                `<span class="fw-semibold">${r.Descripcion}</span>`,
                                `<span class="badge bg-secondary-subtle text-secondary-emphasis px-2 py-1">${r.Prioridad}</span>`,
                                `<div class="text-center">${valCCSS}</div>`,
                                `<div class="text-center">${valRenta}</div>`,
                                `<div class="text-center">${valVac}</div>`,
                                `<div class="text-center">${valAguin}</div>`,
                                badge,
                                acciones
                            ]);
                        });

                        tableRubros.draw();

                        // KPIs
                        $('#kpiTotalRubros').text(total);
                        $('#kpiActivosRubros').text(activos);
                        $('#kpiAfectanCCSS').text(ccss);
                        $('#kpiAfectanRenta').text(renta);
                    }
                }
            });
        }

        // Editar Clasificación (Rubro)
        function editRubro(id) {
            $.ajax({
                url: 'ajax/deducciones.php',
                type: 'GET',
                data: { action: 'get_rubro', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let r = res.data;
                        $('#txtCodRubro').val(r.CodRubro);
                        $('#txtCodigo').val(r.Codigo).prop('readonly', true);
                        $('#txtDescripcion').val(r.Descripcion);
                        $('#txtPrioridad').val(r.Prioridad);
                        
                        $('#chkAfectaCCSS').prop('checked', r.AfectaCCSS == 1);
                        $('#chkAfectaRenta').prop('checked', r.AfectaRenta == 1);
                        $('#chkAfectaVacaciones').prop('checked', r.AfectaVacaciones == 1);
                        $('#chkAfectaAguinaldo').prop('checked', r.AfectaAguinaldo == 1);

                        $('#ddlEstado').val(r.Estado);
                        $('#wrapperEstado').show();
                        
                        $('#modalRubroTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Clasificación de Deducción');
                        $('#modalRubro').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }

        // Inactivar Clasificación (Rubro)
        function deleteRubro(id) {
            Swal.fire({
                title: '¿Está seguro de inactivar?',
                text: "La clasificación de deducción dejará de estar disponible para el digitado y asignación a nuevos colaboradores.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Inactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/deducciones.php',
                        type: 'POST',
                        data: { action: 'delete_rubro', id: id },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: 'Inactivado',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                loadRubrosData();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Fallo al procesar la inactivación en el servidor.', 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
