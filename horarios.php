<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO DE HORARIOS Y TURNOS (HORARIOS.PHP)
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
    <title>Horarios & Turnos - PlanillaCR ERP</title>
    
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

        .legal-warning-badge {
            background-color: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: #f59e0b;
            padding: 12px 16px;
            border-radius: var(--border-radius-md);
            font-size: 12.5px;
            margin-top: 15px;
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
                <li class="sidebar-item">
                    <a href="puestos_deptos.php">
                        <i class="fa-solid fa-building-user"></i>
                        <span>Puestos & Deptos</span>
                    </a>
                </li>
                <li class="sidebar-item active">
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
                    <h1>Horarios & Turnos</h1>
                    <p>Administración de jornadas laborales, horarios de entrada, salida y turnos semanales</p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-primary d-flex align-items-center gap-2 fw-semibold px-4 py-2" id="btnAddNewSchedule" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-calendar-plus"></i>
                        <span>Registrar Horario</span>
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

            <!-- KPIs de Horarios -->
            <section class="kpi-grid">
                <div class="kpi-grid-item w-100 row g-3 m-0 p-0">
                    <div class="col-md-3">
                        <div class="kpi-card h-100 m-0">
                            <div class="kpi-card-header">
                                <span class="kpi-title">Total de Horarios</span>
                                <div class="kpi-icon-wrapper">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                            </div>
                            <div class="kpi-value" id="kpiTotalSchedules">0</div>
                            <div class="kpi-trend trend-neutral">Jornadas registradas</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="kpi-card h-100 m-0">
                            <div class="kpi-card-header">
                                <span class="kpi-title">Turnos Activos</span>
                                <div class="kpi-icon-wrapper" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                            </div>
                            <div class="kpi-value" id="kpiActivosSchedules" style="color: var(--success);">0</div>
                            <div class="kpi-trend trend-up">Operando en nómina</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="kpi-card h-100 m-0">
                            <div class="kpi-card-header">
                                <span class="kpi-title">Jornadas Nocturnas</span>
                                <div class="kpi-icon-wrapper" style="background-color: rgba(59, 130, 246, 0.1); color: var(--primary);">
                                    <i class="fa-solid fa-cloud-moon"></i>
                                </div>
                            </div>
                            <div class="kpi-value" id="kpiNightSchedules">0</div>
                            <div class="kpi-trend trend-neutral">Límite legal: 6h diarias</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="kpi-card h-100 m-0">
                            <div class="kpi-card-header">
                                <span class="kpi-title">Promedio Horas / Turno</span>
                                <div class="kpi-icon-wrapper" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                            </div>
                            <div class="kpi-value" id="kpiAverageHours">0.00</div>
                            <div class="kpi-trend trend-neutral">Horas promedio</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Alertas dinámicas -->
            <div id="globalAlertContainer" class="mt-4"></div>

            <!-- Tabla de Mantenimiento de Horarios -->
            <div class="mt-4">
                <div class="table-responsive">
                    <table id="schedulesTable" class="table table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Empresa</th>
                                <th>Descripción del Turno</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas / Día</th>
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

        </main>
    </div>

    <!-- ==========================================
         MODAL DE GESTIÓN DE HORARIO
         ========================================== -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="scheduleForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="scheduleModalLabel">Registrar Horario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="scheduleModalAlerts"></div>
                        
                        <input type="hidden" id="cod_horario" name="cod_horario" value="0">
                        
                        <div class="mb-3">
                            <label for="horario_cod_empresa" class="form-label form-label-desc">Empresa Asociada <span class="text-danger">*</span></label>
                            <select class="form-select" id="horario_cod_empresa" name="cod_empresa" required>
                                <option value="">Seleccione Empresa...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="horario_descripcion" class="form-label form-label-desc">Descripción del Turno <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="horario_descripcion" name="descripcion" placeholder="ej: Jornada Ordinaria Diurna" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="horario_hora_entrada" class="form-label form-label-desc">Hora de Entrada <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="horario_hora_entrada" name="hora_entrada" required>
                            </div>
                            <div class="col-md-6">
                                <label for="horario_hora_salida" class="form-label form-label-desc">Hora de Salida <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="horario_hora_salida" name="hora_salida" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="horario_horas_dia" class="form-label form-label-desc">Horas Laboradas / Día (Auto)</label>
                            <input type="number" step="0.01" class="form-control" id="horario_horas_dia" name="horas_dia" placeholder="8.00" readonly style="background-color: var(--bg-surface-elevated) !important; font-weight: bold; color: var(--primary) !important;">
                        </div>

                        <!-- Advertencias Legales Inteligentes -->
                        <div id="hoursWarning" class="legal-warning-badge" style="display: none;"></div>

                        <div class="mb-3" id="horario_estado_container" style="display: none;">
                            <label for="horario_estado" class="form-label form-label-desc">Estado de Operación</label>
                            <select class="form-select" id="horario_estado" name="estado">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md);">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2" id="btnSaveSchedule" style="border-radius: var(--border-radius-md);">
                            <span id="horarioSaveBtnText">Guardar Horario</span>
                            <div class="spinner-border spinner-border-sm" id="horarioSaveSpinner" style="display: none;" role="status"></div>
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
                            const selectComp = $('#horario_cod_empresa').empty().append('<option value="">Seleccione Empresa...</option>');
                            listEmpresas.forEach(function(emp) {
                                selectComp.append(`<option value="${emp.CodEmpresa}">${emp.Nombre}</option>`);
                            });
                            if (callback) callback();
                        }
                    }
                });
            }

            // ==========================================
            // C. INICIALIZACIÓN DE DATATABLE Y KPIs
            // ==========================================
            const schedulesTable = $('#schedulesTable').DataTable({
                ajax: {
                    url: 'ajax/catalogos.php?action=list_schedules',
                    type: 'GET'
                },
                columns: [
                    { data: 'CodHorario' },
                    { data: 'Empresa' },
                    { data: 'Descripcion' },
                    { 
                        data: 'HoraEntrada',
                        render: function(data) {
                            return `<span class="fw-semibold text-primary"><i class="fa-regular fa-clock me-1"></i> ${data}</span>`;
                        }
                    },
                    { 
                        data: 'HoraSalida',
                        render: function(data) {
                            return `<span class="fw-semibold text-secondary"><i class="fa-regular fa-clock me-1"></i> ${data}</span>`;
                        }
                    },
                    { 
                        data: 'HorasDia',
                        render: function(data) {
                            return `<span class="badge bg-secondary px-2 py-1" style="font-size:12.5px;">${parseFloat(data).toFixed(2)} h</span>`;
                        }
                    },
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
                                <button class="btn-table-action btn-table-edit btn-edit-schedule" data-id="${row.CodHorario}" title="Editar Horario">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-table-action btn-table-delete btn-delete-schedule" data-id="${row.CodHorario}" title="Inactivar Horario">
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

            // Recalcular KPIs del Catálogo de Horarios
            function recalculateKPIs() {
                const data = schedulesTable.rows().data();
                let total = data.length;
                let activos = 0;
                let nocturnos = 0;
                let totalHours = 0;

                for (let i = 0; i < total; i++) {
                    const row = data[i];
                    if (row.Estado == 'ACTIVO') activos++;
                    
                    // Sumar horas laboradas
                    const hDia = parseFloat(row.HorasDia);
                    totalHours += hDia;

                    // Evaluar si es nocturno (cruza o inicia entre las 19:00 y las 05:00)
                    const [hEnt] = row.HoraEntrada.split(':').map(Number);
                    const [hSal] = row.HoraSalida.split(':').map(Number);
                    
                    if (hEnt >= 19 || hEnt <= 5 || hSal >= 19 || hSal <= 5) {
                        nocturnos++;
                    }
                }

                const avgHours = total > 0 ? (totalHours / total) : 0.00;

                $('#kpiTotalSchedules').text(total);
                $('#kpiActivosSchedules').text(activos);
                $('#kpiNightSchedules').text(nocturnos);
                $('#kpiAverageHours').text(avgHours.toFixed(2));
            }

            // ==========================================
            // D. CÁLCULO DE DIFERENCIA HORARIA Y ADVERTENCIA LEGAL COSTA RICA
            // ==========================================
            function calculateHours() {
                const entrada = $('#horario_hora_entrada').val();
                const salida = $('#horario_hora_salida').val();
                const hoursInput = $('#horario_horas_dia');
                const warningDiv = $('#hoursWarning');

                if (!entrada || !salida) {
                    hoursInput.val('0.00');
                    warningDiv.hide();
                    return;
                }

                const [hEnt, mEnt] = entrada.split(':').map(Number);
                const [hSal, mSal] = salida.split(':').map(Number);

                let dateEnt = new Date(2000, 0, 1, hEnt, mEnt);
                let dateSal = new Date(2000, 0, 1, hSal, mSal);

                // Si la hora de salida es menor que la de entrada, cruza la medianoche
                if (dateSal < dateEnt) {
                    dateSal.setDate(dateSal.getDate() + 1);
                }

                const diffMs = dateSal - dateEnt;
                const diffHours = diffMs / (1000 * 60 * 60);
                hoursInput.val(diffHours.toFixed(2));

                // Analizar si coincide con franja nocturna (19:00 a 05:00)
                let isNightShift = false;
                if (hEnt >= 19 || hEnt <= 5 || hSal >= 19 || hSal <= 5 || (hEnt < 19 && hSal > 19) || (hEnt < 5 && hSal > 5)) {
                    isNightShift = true;
                }

                if (isNightShift && diffHours > 6.0) {
                    warningDiv.html('<i class="fa-solid fa-circle-exclamation me-1"></i> <strong>Advertencia Legal CR:</strong> La jornada nocturna ordinaria no debe exceder las 6 horas diarias. (Calculado: ' + diffHours.toFixed(2) + ' h)').show();
                } else if (!isNightShift && diffHours > 8.0) {
                    warningDiv.html('<i class="fa-solid fa-circle-exclamation me-1"></i> <strong>Advertencia Legal CR:</strong> La jornada diurna ordinaria no debe exceder las 8 horas diarias. (Calculado: ' + diffHours.toFixed(2) + ' h)').show();
                } else {
                    warningDiv.hide();
                }
            }

            $('#horario_hora_entrada, #horario_hora_salida').on('change', calculateHours);

            // ==========================================
            // E. GESTIÓN DE MODAL Y GUARDADO (SUBMIT)
            // ==========================================

            $('#btnAddNewSchedule').on('click', function() {
                $('#scheduleModalAlerts').empty();
                loadCompanies(function() {
                    $('#scheduleForm')[0].reset();
                    $('#cod_horario').val('0');
                    $('#horario_estado_container').hide();
                    $('#horario_estado').val('ACTIVO');
                    $('#hoursWarning').hide();
                    $('#scheduleModalLabel').html('<i class="fa-solid fa-calendar-plus text-primary me-2"></i> Registrar Nuevo Horario');
                    $('#horarioSaveBtnText').text('Guardar Horario');
                    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
                });
            });

            $(document).on('click', '.btn-edit-schedule', function() {
                const id = $(this).data('id');
                $('#scheduleModalAlerts').empty();
                loadCompanies(function() {
                    $.ajax({
                        url: 'ajax/catalogos.php?action=get_schedule&id=' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#cod_horario').val(response.data.CodHorario);
                                $('#horario_cod_empresa').val(response.data.CodEmpresa);
                                $('#horario_descripcion').val(response.data.Descripcion);
                                $('#horario_hora_entrada').val(response.data.HoraEntrada);
                                $('#horario_hora_salida').val(response.data.HoraSalida);
                                $('#horario_horas_dia').val(response.data.HorasDia);
                                $('#horario_estado').val(response.data.Estado);
                                $('#horario_estado_container').show();
                                calculateHours();
                                $('#scheduleModalLabel').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i> Editar Horario');
                                $('#horarioSaveBtnText').text('Actualizar Horario');
                                new bootstrap.Modal(document.getElementById('scheduleModal')).show();
                            }
                        }
                    });
                });
            });

            $('#scheduleForm').on('submit', function(e) {
                e.preventDefault();
                $('#btnSaveSchedule').prop('disabled', true);
                $('#horarioSaveSpinner').show();
                $('#scheduleModalAlerts').empty();

                $.ajax({
                    url: 'ajax/catalogos.php?action=save_schedule',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            showAlert('<strong>¡Operación Exitosa!</strong><br>' + response.message, 'success', '#globalAlertContainer');
                            schedulesTable.ajax.reload(null, false);
                            bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
                        } else {
                            showAlert(response.message, 'danger', '#scheduleModalAlerts');
                        }
                    },
                    error: function() {
                        showAlert('Error en la comunicación asíncrona con el servidor.', 'danger', '#scheduleModalAlerts');
                    },
                    complete: function() {
                        $('#btnSaveSchedule').prop('disabled', false);
                        $('#horarioSaveSpinner').hide();
                    }
                });
            });

            $(document).on('click', '.btn-delete-schedule', function() {
                const id = $(this).data('id');
                if (confirm('¿Está seguro de que desea inactivar este horario en la base de datos de planilla?')) {
                    $.ajax({
                        url: 'ajax/catalogos.php?action=delete_schedule',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: id },
                        success: function(response) {
                            if (response.success) {
                                showAlert(response.message, 'success', '#globalAlertContainer');
                                schedulesTable.ajax.reload(null, false);
                            } else {
                                showAlert(response.message, 'danger', '#globalAlertContainer');
                            }
                        }
                    });
                }
            });

            // Función reutilizable para alertas
            function showAlert(message, type, container) {
                const html = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="border-radius: var(--border-radius-md); font-size: 13.5px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                $(container).html(html);
                if (container === '#globalAlertContainer') {
                    setTimeout(function() {
                        $(container).find('.alert').alert('close');
                    }, 4000);
                }
            }

        });
    </script>
</body>
</html>
