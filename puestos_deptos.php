<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO DE PUESTOS Y DEPARTAMENTOS (PUESTOS_DEPTOS.PHP)
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
    <title>Puestos & Departamentos - PlanillaCR ERP</title>
    
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

        .nav-tabs-custom {
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .nav-tabs-custom .nav-link {
            color: var(--text-secondary);
            border: none;
            font-size: 14.5px;
            font-weight: 600;
            padding: 14px 24px;
            transition: all var(--transition-speed) ease;
            position: relative;
            background: transparent;
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--primary) !important;
            background-color: transparent !important;
        }

        .nav-tabs-custom .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary);
            border-radius: 3px 3px 0 0;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-layout">
        
        <!-- ==========================================
             BARRA LATERAL (SIDEBAR) FIJA
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
                <li class="sidebar-item active">
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
                    <h1>Puestos & Departamentos</h1>
                    <p>Gestión y mantenimiento de la estructura de cargos de la planilla</p>
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

            <!-- KPIs de Estructura -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Total de Cargos / Puestos</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiTotalPuestos">0</div>
                    <div class="kpi-trend trend-neutral">Catálogo de Puestos</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Puestos Activos</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiActivosPuestos" style="color: var(--success);">0</div>
                    <div class="kpi-trend trend-up">Operando en nómina</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Total de Departamentos</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fa-solid fa-sitemap"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiTotalDeptos">0</div>
                    <div class="kpi-trend trend-neutral">Estructura Organizacional</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Departamentos Activos</span>
                        <div class="kpi-icon-wrapper" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiActivosDeptos" style="color: var(--success);">0</div>
                    <div class="kpi-trend trend-up">Centros de costo activos</div>
                </div>
            </section>

            <!-- Alertas dinámicas -->
            <div id="globalAlertContainer"></div>

            <!-- Panel de Mantenimiento con Tabs -->
            <div class="mt-4">
                <ul class="nav nav-tabs-custom" id="catalogTabs" role="tabpanel">
                    <li class="nav-item">
                        <button class="nav-link active" id="puestos-tab" data-bs-toggle="tab" data-bs-target="#puestos-pane" type="button" role="tab" aria-controls="puestos-pane" aria-selected="true">
                            <i class="fa-solid fa-briefcase me-2"></i> Mantenimiento de Puestos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="deptos-tab" data-bs-toggle="tab" data-bs-target="#deptos-pane" type="button" role="tab" aria-controls="deptos-pane" aria-selected="false">
                            <i class="fa-solid fa-sitemap me-2"></i> Mantenimiento de Departamentos
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="catalogTabsContent">
                    
                    <!-- TAB 1: PUESTOS -->
                    <div class="tab-pane fade show active" id="puestos-pane" role="tabpanel" aria-labelledby="puestos-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0 text-secondary fw-semibold">Puestos de Trabajo</h4>
                            <button class="btn btn-primary d-flex align-items-center gap-2 fw-semibold px-4" id="btnAddNewPosition" style="border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-plus"></i> Registrar Puesto
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="positionsTable" class="table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Empresa</th>
                                        <th>Descripción del Puesto</th>
                                        <th>Estado</th>
                                        <th style="width: 100px; text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Carga dinámica asíncrona -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: DEPARTAMENTOS -->
                    <div class="tab-pane fade" id="deptos-pane" role="tabpanel" aria-labelledby="deptos-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0 text-secondary fw-semibold">Departamentos / Áreas</h4>
                            <button class="btn btn-primary d-flex align-items-center gap-2 fw-semibold px-4" id="btnAddNewDepartment" style="border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-plus"></i> Registrar Departamento
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table id="departmentsTable" class="table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Empresa</th>
                                        <th>Descripción de Área</th>
                                        <th>Estado</th>
                                        <th style="width: 100px; text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Carga dinámica asíncrona -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- ==========================================
         MODALES DE ADMINISTRACIÓN (PUESTOS & DEPTOS)
         ========================================== -->

    <!-- MODAL 1: PUESTO -->
    <div class="modal fade" id="positionModal" tabindex="-1" aria-labelledby="positionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="positionForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="positionModalLabel">Registrar Puesto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="positionModalAlerts"></div>
                        
                        <input type="hidden" id="cod_puesto" name="cod_puesto" value="0">
                        
                        <div class="mb-3">
                            <label for="puesto_cod_empresa" class="form-label form-label-desc">Empresa Asociada <span class="text-danger">*</span></label>
                            <select class="form-select" id="puesto_cod_empresa" name="cod_empresa" required>
                                <option value="">Seleccione Empresa...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="puesto_descripcion" class="form-label form-label-desc">Descripción del Puesto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="puesto_descripcion" name="descripcion" placeholder="ej: Programador Fullstack" required>
                        </div>

                        <div class="mb-3" id="puesto_estado_container" style="display: none;">
                            <label for="puesto_estado" class="form-label form-label-desc">Estado de Operación</label>
                            <select class="form-select" id="puesto_estado" name="estado">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md);">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2" id="btnSavePosition" style="border-radius: var(--border-radius-md);">
                            <span id="puestoSaveBtnText">Guardar Puesto</span>
                            <div class="spinner-border spinner-border-sm" id="puestoSaveSpinner" style="display: none;" role="status"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: DEPARTAMENTO -->
    <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="departmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="departmentModalLabel">Registrar Departamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="departmentModalAlerts"></div>
                        
                        <input type="hidden" id="cod_departamento" name="cod_departamento" value="0">
                        
                        <div class="mb-3">
                            <label for="depto_cod_empresa" class="form-label form-label-desc">Empresa Asociada <span class="text-danger">*</span></label>
                            <select class="form-select" id="depto_cod_empresa" name="cod_empresa" required>
                                <option value="">Seleccione Empresa...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="depto_descripcion" class="form-label form-label-desc">Descripción del Departamento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="depto_descripcion" name="descripcion" placeholder="ej: Recursos Humanos" required>
                        </div>

                        <div class="mb-3" id="depto_estado_container" style="display: none;">
                            <label for="depto_estado" class="form-label form-label-desc">Estado de Operación</label>
                            <select class="form-select" id="depto_estado" name="estado">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md);">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2" id="btnSaveDepartment" style="border-radius: var(--border-radius-md);">
                            <span id="deptoSaveBtnText">Guardar Área</span>
                            <div class="spinner-border spinner-border-sm" id="deptoSaveSpinner" style="display: none;" role="status"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JQUERY, BOOTSTRAP 5 & DATATABLES JS CDNs -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Controladores Dinámicos en jQuery -->
    <script>
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

        $(document).ready(function() {
            
            // ==========================================
            // A. CONTROL DE TEMA VISUAL UNIFICADO (LIGHT / DARK)
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

            $('#themeToggle').on('click', function() {
                const currentTheme = localStorage.getItem('theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });

            // ==========================================
            // B. CATÁLOGOS BASE (EMPRESAS)
            // ==========================================
            let listEmpresas = [];
            function loadCompanies(callback) {
                $.ajax({
                    url: 'ajax/recursos_humanos.php?action=get_catalogs',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            listEmpresas = response.empresas;
                            const selectPuesto = $('#puesto_cod_empresa').empty().append('<option value="">Seleccione Empresa...</option>');
                            const selectDepto = $('#depto_cod_empresa').empty().append('<option value="">Seleccione Empresa...</option>');
                            
                            listEmpresas.forEach(function(emp) {
                                selectPuesto.append(`<option value="${emp.CodEmpresa}">${emp.Nombre}</option>`);
                                selectDepto.append(`<option value="${emp.CodEmpresa}">${emp.Nombre}</option>`);
                            });
                            
                            if (callback) callback();
                        }
                    }
                });
            }

            // ==========================================
            // C. INICIALIZACIÓN DE DATATABLES Y KPIs
            // ==========================================
            
            // DataTable Puestos
            const positionsTable = $('#positionsTable').DataTable({
                ajax: {
                    url: 'ajax/catalogos.php?action=list_positions',
                    type: 'GET'
                },
                columns: [
                    { data: 'CodPuesto' },
                    { data: 'Empresa' },
                    { data: 'Descripcion' },
                    { 
                        data: 'Estado',
                        render: function(data) {
                            if (data == 'ACTIVO') {
                                return '<span class="badge-status badge-status-activo"><i class="fa-solid fa-circle"></i> ACTIVO</span>';
                            } else {
                                return '<span class="badge-status badge-status-inactivo"><i class="fa-solid fa-circle"></i> INACTIVO</span>';
                            }
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function(row) {
                            return `
                                <button class="btn-table-action btn-table-edit btn-edit-position" data-id="${row.CodPuesto}" title="Editar Puesto">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-table-action btn-table-delete btn-delete-position" data-id="${row.CodPuesto}" title="Inactivar Puesto">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            `;
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                drawCallback: function() {
                    recalculateKPIs();
                }
            });

            // DataTable Departamentos
            const departmentsTable = $('#departmentsTable').DataTable({
                ajax: {
                    url: 'ajax/catalogos.php?action=list_departments',
                    type: 'GET'
                },
                columns: [
                    { data: 'CodDepartamento' },
                    { data: 'Empresa' },
                    { data: 'Descripcion' },
                    { 
                        data: 'Estado',
                        render: function(data) {
                            if (data == 'ACTIVO') {
                                return '<span class="badge-status badge-status-activo"><i class="fa-solid fa-circle"></i> ACTIVO</span>';
                            } else {
                                return '<span class="badge-status badge-status-inactivo"><i class="fa-solid fa-circle"></i> INACTIVO</span>';
                            }
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function(row) {
                            return `
                                <button class="btn-table-action btn-table-edit btn-edit-department" data-id="${row.CodDepartamento}" title="Editar Departamento">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-table-action btn-table-delete btn-delete-department" data-id="${row.CodDepartamento}" title="Inactivar Departamento">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            `;
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                drawCallback: function() {
                    recalculateKPIs();
                }
            });

            // Recalcular KPIs en caliente
            function recalculateKPIs() {
                // KPIs de Puestos
                const posData = positionsTable.rows().data();
                let totalP = posData.length;
                let activosP = 0;
                for (let i=0; i<totalP; i++) {
                    if (posData[i].Estado == 'ACTIVO') activosP++;
                }
                $('#kpiTotalPuestos').text(totalP);
                $('#kpiActivosPuestos').text(activosP);

                // KPIs de Departamentos
                const depData = departmentsTable.rows().data();
                let totalD = depData.length;
                let activosD = 0;
                for (let i=0; i<totalD; i++) {
                    if (depData[i].Estado == 'ACTIVO') activosD++;
                }
                $('#kpiTotalDeptos').text(totalD);
                $('#kpiActivosDeptos').text(activosD);
            }

            // ==========================================
            // D. GESTIÓN DE MODALES Y SUBMITS (PUESTOS)
            // ==========================================

            $('#btnAddNewPosition').on('click', function() {
                $('#positionModalAlerts').empty();
                loadCompanies(function() {
                    $('#positionForm')[0].reset();
                    $('#cod_puesto').val('0');
                    $('#puesto_estado_container').hide();
                    $('#puesto_estado').val('ACTIVO');
                    $('#positionModalLabel').html('<i class="fa-solid fa-plus text-primary me-2"></i> Registrar Nuevo Puesto');
                    $('#puestoSaveBtnText').text('Guardar Puesto');
                    new bootstrap.Modal(document.getElementById('positionModal')).show();
                });
            });

            $(document).on('click', '.btn-edit-position', function() {
                const id = $(this).data('id');
                $('#positionModalAlerts').empty();
                loadCompanies(function() {
                    $.ajax({
                        url: 'ajax/catalogos.php?action=get_position&id=' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#cod_puesto').val(response.data.CodPuesto);
                                $('#puesto_cod_empresa').val(response.data.CodEmpresa);
                                $('#puesto_descripcion').val(response.data.Descripcion);
                                $('#puesto_estado').val(response.data.Estado);
                                $('#puesto_estado_container').show();
                                $('#positionModalLabel').html('<i class="fa-solid fa-pen text-primary me-2"></i> Editar Puesto');
                                $('#puestoSaveBtnText').text('Actualizar Puesto');
                                new bootstrap.Modal(document.getElementById('positionModal')).show();
                            }
                        }
                    });
                });
            });

            $('#positionForm').on('submit', function(e) {
                e.preventDefault();
                $('#btnSavePosition').prop('disabled', true);
                $('#puestoSaveSpinner').show();
                $('#positionModalAlerts').empty();

                $.ajax({
                    url: 'ajax/catalogos.php?action=save_position',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            showAlert('<strong>¡Operación Exitosa!</strong><br>' + response.message, 'success', '#globalAlertContainer');
                            positionsTable.ajax.reload(null, false);
                            bootstrap.Modal.getInstance(document.getElementById('positionModal')).hide();
                        } else {
                            showAlert(response.message, 'danger', '#positionModalAlerts');
                        }
                    },
                    error: function() {
                        showAlert('Error en la comunicación con el servidor.', 'danger', '#positionModalAlerts');
                    },
                    complete: function() {
                        $('#btnSavePosition').prop('disabled', false);
                        $('#puestoSaveSpinner').hide();
                    }
                });
            });

            $(document).on('click', '.btn-delete-position', function() {
                const id = $(this).data('id');
                if (confirm('¿Está seguro de que desea inactivar este puesto en el catálogo?')) {
                    $.ajax({
                        url: 'ajax/catalogos.php?action=delete_position',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: id },
                        success: function(response) {
                            if (response.success) {
                                showAlert(response.message, 'success', '#globalAlertContainer');
                                positionsTable.ajax.reload(null, false);
                            } else {
                                showAlert(response.message, 'danger', '#globalAlertContainer');
                            }
                        }
                    });
                }
            });

            // ==========================================
            // E. GESTIÓN DE MODALES Y SUBMITS (DEPTOS)
            // ==========================================

            $('#btnAddNewDepartment').on('click', function() {
                $('#departmentModalAlerts').empty();
                loadCompanies(function() {
                    $('#departmentForm')[0].reset();
                    $('#cod_departamento').val('0');
                    $('#depto_estado_container').hide();
                    $('#depto_estado').val('ACTIVO');
                    $('#departmentModalLabel').html('<i class="fa-solid fa-plus text-primary me-2"></i> Registrar Departamento');
                    $('#deptoSaveBtnText').text('Guardar Área');
                    new bootstrap.Modal(document.getElementById('departmentModal')).show();
                });
            });

            $(document).on('click', '.btn-edit-department', function() {
                const id = $(this).data('id');
                $('#departmentModalAlerts').empty();
                loadCompanies(function() {
                    $.ajax({
                        url: 'ajax/catalogos.php?action=get_department&id=' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#cod_departamento').val(response.data.CodDepartamento);
                                $('#depto_cod_empresa').val(response.data.CodEmpresa);
                                $('#depto_descripcion').val(response.data.Descripcion);
                                $('#depto_estado').val(response.data.Estado);
                                $('#depto_estado_container').show();
                                $('#departmentModalLabel').html('<i class="fa-solid fa-pen text-primary me-2"></i> Editar Departamento');
                                $('#deptoSaveBtnText').text('Actualizar Área');
                                new bootstrap.Modal(document.getElementById('departmentModal')).show();
                            }
                        }
                    });
                });
            });

            $('#departmentForm').on('submit', function(e) {
                e.preventDefault();
                $('#btnSaveDepartment').prop('disabled', true);
                $('#deptoSaveSpinner').show();
                $('#departmentModalAlerts').empty();

                $.ajax({
                    url: 'ajax/catalogos.php?action=save_department',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            showAlert('<strong>¡Operación Exitosa!</strong><br>' + response.message, 'success', '#globalAlertContainer');
                            departmentsTable.ajax.reload(null, false);
                            bootstrap.Modal.getInstance(document.getElementById('departmentModal')).hide();
                        } else {
                            showAlert(response.message, 'danger', '#departmentModalAlerts');
                        }
                    },
                    error: function() {
                        showAlert('Error en la comunicación con el servidor.', 'danger', '#departmentModalAlerts');
                    },
                    complete: function() {
                        $('#btnSaveDepartment').prop('disabled', false);
                        $('#deptoSaveSpinner').hide();
                    }
                });
            });

            $(document).on('click', '.btn-delete-department', function() {
                const id = $(this).data('id');
                if (confirm('¿Está seguro de que desea inactivar este departamento en la base de datos?')) {
                    $.ajax({
                        url: 'ajax/catalogos.php?action=delete_department',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: id },
                        success: function(response) {
                            if (response.success) {
                                showAlert(response.message, 'success', '#globalAlertContainer');
                                departmentsTable.ajax.reload(null, false);
                            } else {
                                showAlert(response.message, 'danger', '#globalAlertContainer');
                            }
                        }
                    });
                }
            });

            // Función reutilizable para renderizar alertas Bootstrap
            function showAlert(message, type, container) {
                const html = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="border-radius: var(--border-radius-md); font-size: 13.5px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                $(container).html(html);
                if (container === '#globalAlertContainer') {
                    // Auto-cerrar alertas globales en 4 segundos
                    setTimeout(function() {
                        $(container).find('.alert').alert('close');
                    }, 4000);
                }
            }

        });
    </script>
</body>
</html>
