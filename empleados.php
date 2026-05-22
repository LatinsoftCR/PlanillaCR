<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * MANTENIMIENTO COMPLETO DE EMPLEADOS (EMPLEADOS.PHP)
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
    <title>Mantenimiento de Empleados - PlanillaCR ERP</title>
    
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

        /* Tabs en Modales */
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

        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            font-size: 13.5px;
            font-weight: 600;
            padding: 12px 20px;
            transition: all var(--transition-speed) ease;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary) !important;
            background-color: transparent !important;
            border-bottom: 3px solid var(--primary) !important;
        }

        .nav-tabs .nav-link:hover {
            color: var(--text-primary);
            border-bottom: 3px solid var(--border-color);
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

        .btn-table-docs:hover {
            background-color: var(--info) !important;
            border-color: var(--info) !important;
            color: #ffffff !important;
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
                <li class="sidebar-item active">
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
                    <h1>Recursos Humanos</h1>
                    <p>Gestión integral del personal de planilla y expedientes</p>
                </div>
                
                <div class="header-actions">
                    <!-- Botón para Registrar Nuevo Empleado -->
                    <button class="btn btn-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnAddNewEmployee" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Registrar Empleado</span>
                    </button>

                    <!-- Botón para Abrir Modal de Reportes -->
                    <button class="btn btn-outline-primary px-4 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnOpenReportModal" data-bs-toggle="modal" data-bs-target="#employeeReportModal" style="border-radius: var(--border-radius-md); border-color: rgba(var(--primary-rgb), 0.4); transition: all var(--transition-speed);">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Reportes y Listados</span>
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

            <!-- KPIs de Recursos Humanos -->
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Total de Colaboradores</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiTotal">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-circle-check"></i> Activos</span>
                        <span class="kpi-desc" id="kpiActivosDesc">0 en nómina</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Salario Promedio</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiPromedio">₡0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral"><i class="fa-solid fa-coins"></i> CRC</span>
                        <span class="kpi-desc">Sueldo medio actual</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Sueldo Máximo</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiMaximo">₡0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-crown"></i> Techo</span>
                        <span class="kpi-desc">Mayor remuneración</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Inactivos / Bajas</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="kpiInactivos">0</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral"><i class="fa-solid fa-history"></i> Histórico</span>
                        <span class="kpi-desc">Bajas de personal</span>
                    </div>
                </div>
            </section>



            <!-- Tabla de Empleados con DataTables -->
            <section class="table-responsive">
                <table id="employeesTable" class="table table-hover w-100" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Identificación</th>
                            <th>Nombre Completo</th>
                            <th>Departamento</th>
                            <th>Puesto</th>
                            <th>Sucursal</th>
                            <th>Salario Base</th>
                            <th>F. Ingreso</th>
                            <th>Estado</th>
                            <th class="text-center" style="width: 80px;">Acciones</th>
                            <th style="display: none;">CodEmpresa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Carga dinámica vía AJAX -->
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <!-- ==========================================
         MODAL DE REGISTRO / EDICIÓN DE EMPLEADOS
         ========================================== -->
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="employeeModalLabel"><i class="fa-solid fa-user-plus text-primary me-2"></i> Registrar Nuevo Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="employeeForm" autocomplete="off">
                    <div class="modal-body px-4 py-3">
                        
                        <!-- Contenedor dinámico de alertas internas del modal -->
                        <div id="modalAlertContainer"></div>
                        
                        <!-- Selector de código oculto para edición -->
                        <input type="hidden" id="cod_empleado" name="cod_empleado" value="0">

                        <!-- Pestañas Lógicas -->
                        <ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal-pane" type="button" role="tab" aria-controls="personal-pane" aria-selected="true">
                                    <i class="fa-solid fa-id-card me-1"></i> Datos Personales
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="organizational-tab" data-bs-toggle="tab" data-bs-target="#organizational-pane" type="button" role="tab" aria-controls="organizational-pane" aria-selected="false">
                                    <i class="fa-solid fa-sitemap me-1"></i> Datos Laborales
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial-pane" type="button" role="tab" aria-controls="financial-pane" aria-selected="false">
                                    <i class="fa-solid fa-sack-dollar me-1"></i> Nómina & Salario
                                </button>
                            </li>
                        </ul>

                        <!-- Contenido de las Pestañas -->
                        <div class="tab-content" id="employeeTabsContent">
                            
                            <!-- PESTAÑA 1: DATOS PERSONALES -->
                            <div class="tab-pane fade show active" id="personal-pane" role="tabpanel" aria-labelledby="personal-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="identificacion" class="form-label form-label-desc">Cédula / Identificación <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="ej: 1-1234-5678" required>
                                            <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center" type="button" id="btnSearchCedula" title="Consultar Cédula GoMeta" style="border-color: var(--border-color); color: var(--text-secondary); background-color: var(--bg-card); transition: all 0.2s ease;">
                                                <i class="fa-solid fa-magnifying-glass" id="searchCedulaIcon"></i>
                                                <div class="spinner-border spinner-border-sm text-primary" id="searchCedulaSpinner" style="display: none; width: 0.9rem; height: 0.9rem;" role="status"></div>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback d-block" id="cedulaFeedback" style="font-size: 11.5px; display: none;"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="correo" class="form-label form-label-desc">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="correo" name="correo" placeholder="empleado@planillacr.com" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="nombre" class="form-label form-label-desc">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Primer Nombre" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="apellido1" class="form-label form-label-desc">Primer Apellido <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="apellido1" name="apellido1" placeholder="Primer Apellido" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="apellido2" class="form-label form-label-desc">Segundo Apellido</label>
                                        <input type="text" class="form-control" id="apellido2" name="apellido2" placeholder="Opcional">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fecha_nacimiento" class="form-label form-label-desc">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="telefono" class="form-label form-label-desc">Teléfono</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="ej: 8888-8888">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fecha_ingreso" class="form-label form-label-desc">Fecha de Ingreso <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="direccion" class="form-label form-label-desc">Dirección de Habitación</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="2" placeholder="Dirección física exacta del colaborador"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- PESTAÑA 2: INFORMACIÓN LABORAL -->
                            <div class="tab-pane fade" id="organizational-pane" role="tabpanel" aria-labelledby="organizational-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="cod_empresa" class="form-label form-label-desc">Empresa Asociada <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cod_empresa" name="cod_empresa" required>
                                            <option value="">Cargando empresas...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cod_sucursal" class="form-label form-label-desc">Sucursal Destino <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cod_sucursal" name="cod_sucursal" required>
                                            <option value="">Seleccione una empresa primero...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cod_departamento" class="form-label form-label-desc">Departamento <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cod_departamento" name="cod_departamento" required>
                                            <option value="">Seleccione una empresa primero...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cod_puesto" class="form-label form-label-desc">Puesto Asignado <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cod_puesto" name="cod_puesto" required>
                                            <option value="">Seleccione una empresa primero...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cod_horario" class="form-label form-label-desc">Horario / Jornada <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cod_horario" name="cod_horario" required>
                                            <option value="">Seleccione una empresa primero...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12" id="estadoContainer" style="display: none;">
                                        <label for="estado" class="form-label form-label-desc">Estado Laboral</label>
                                        <select class="form-select" id="estado" name="estado">
                                            <option value="ACTIVO">ACTIVO</option>
                                            <option value="INACTIVO">INACTIVO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- PESTAÑA 3: CONFIGURACIÓN FINANCIERA -->
                            <div class="tab-pane fade" id="financial-pane" role="tabpanel" aria-labelledby="financial-tab" tabindex="0">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="tipo_salario" class="form-label form-label-desc">Tipo de Salario <span class="text-danger">*</span></label>
                                        <select class="form-select" id="tipo_salario" name="tipo_salario" required>
                                            <option value="">Seleccione...</option>
                                            <option value="MENSUAL">MENSUAL (Nómina Estándar)</option>
                                            <option value="QUINCENAL">QUINCENAL</option>
                                            <option value="SEMANAL">SEMANAL</option>
                                            <option value="HORA">POR HORAS</option>
                                            <option value="DESTAJO">POR DESTAJO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="salario_base" class="form-label form-label-desc">Salario Base (₡) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text" style="background-color: var(--bg-main); border-color: var(--border-color); color: var(--text-secondary);">₡</span>
                                            <input type="number" step="0.01" class="form-control" id="salario_base" name="salario_base" placeholder="ej: 450000" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="banco" class="form-label form-label-desc">Banco de Pago</label>
                                        <input type="text" class="form-control" id="banco" name="banco" placeholder="ej: BAC Credomatic, Banco Nacional">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cuenta_iban" class="form-label form-label-desc">Cuenta IBAN Costa Rica</label>
                                        <input type="text" class="form-control" id="cuenta_iban" name="cuenta_iban" placeholder="CR03010203040506070809">
                                    </div>
                                    <div class="col-md-12">
                                        <label for="cuenta_contable" class="form-label form-label-desc">Cuenta Contable (Pasivo de Nómina)</label>
                                        <input type="text" class="form-control" id="cuenta_contable" name="cuenta_contable" placeholder="ej: 2-01-01-01">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md);">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2 fw-semibold" id="btnSaveEmployee" style="border-radius: var(--border-radius-md);">
                            <span id="saveBtnText">Guardar Colaborador</span>
                            <div class="auth-spinner" id="saveSpinner"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL DE CONFIRMACIÓN DE BAJA (INACTIVAR)
         ========================================== -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom: none; padding-bottom: 0;">
                    <h5 class="modal-title fw-bold text-danger" id="deleteModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i> Dar de Baja Colaborador</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="deleteForm">
                    <div class="modal-body px-4 py-3">
                        <p class="text-secondary" style="font-size: 13.5px; line-height: 1.5;">
                            ¿Está seguro de que desea inhabilitar (baja lógica) a <strong class="text-light" id="deleteEmployeeName">este empleado</strong>?
                        </p>
                        
                        <input type="hidden" id="delete_cod_empleado" name="id" value="0">
                        
                        <div class="form-group mt-3">
                            <label for="fecha_salida" class="form-label form-label-desc">Fecha de Salida Oficial <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_salida" name="fecha_salida" required>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="border-top: none; padding-top: 0;">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal" style="border-radius: var(--border-radius-sm);">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-2 fw-semibold" id="btnConfirmDelete" style="border-radius: var(--border-radius-sm);">
                            <span>Confirmar Baja</span>
                            <div class="auth-spinner" id="deleteSpinner"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL DE REPORTE Y EXPORTACIÓN DE COLABORADORES
         ========================================== -->
    <div class="modal fade" id="employeeReportModal" tabindex="-1" aria-labelledby="employeeReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content" style="background-color: var(--bg-surface); border: 1px solid var(--border-light); box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-radius: var(--border-radius-lg);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-light); padding: 16px 24px;">
                    <h5 class="modal-title fw-bold text-primary" id="employeeReportModalLabel">
                        <i class="fa-solid fa-file-invoice me-2"></i> Reportes y Exportación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    <p class="text-secondary mb-4" style="font-size: 13.5px; line-height: 1.5;">
                        Seleccione la compañía para generar los listados correspondientes o descargarlos en el formato deseado:
                    </p>
                    
                    <div class="form-group mb-4">
                        <label for="reportCodEmpresa" class="form-label form-label-desc fw-semibold text-secondary mb-2" style="font-size: 12.5px;">Filtrar por Compañía:</label>
                        <select class="form-select w-100" id="reportCodEmpresa" style="border-radius: var(--border-radius-md); background-color: var(--bg-main); color: var(--text-primary); border-color: var(--border-color); padding: 10px 14px; font-size: 14px;">
                            <option value="ALL">-- Todas las Compañías --</option>
                            <!-- Se poblará dinámicamente -->
                        </select>
                    </div>

                    <div class="d-flex flex-column gap-3 mt-4">
                        <button type="button" class="btn btn-outline-info py-2.5 d-flex align-items-center justify-content-center gap-2 fw-semibold w-100" id="btnReportPrint" style="border-radius: var(--border-radius-md); font-size: 14px;">
                            <i class="fa-solid fa-print"></i>
                            <span>Imprimir Reporte Completo</span>
                        </button>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-success py-2.5 d-flex align-items-center justify-content-center gap-2 fw-semibold w-100" id="btnReportExcel" style="border-radius: var(--border-radius-md); font-size: 14px;">
                                    <i class="fa-solid fa-file-excel"></i>
                                    <span>Exportar a Excel</span>
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-warning py-2.5 d-flex align-items-center justify-content-center gap-2 fw-semibold w-100" id="btnReportCsv" style="border-radius: var(--border-radius-md); font-size: 14px;">
                                    <i class="fa-solid fa-file-csv"></i>
                                    <span>Exportar a CSV</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL DE DOCUMENTOS Y ACCIONES DE PERSONAL
         ========================================== -->
    <div class="modal fade" id="employeeDocsModal" tabindex="-1" aria-labelledby="employeeDocsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content" style="background-color: var(--bg-surface); border: 1px solid var(--border-light); box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-radius: var(--border-radius-lg);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-light); padding: 16px 24px;">
                    <h5 class="modal-title fw-bold text-primary" id="employeeDocsModalLabel">
                        <i class="fa-solid fa-file-contract me-2"></i> Documentación: <span id="docEmployeeName" class="text-light"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    <!-- Nav Tabs para los dos documentos -->
                    <ul class="nav nav-pills nav-fill gap-2 p-1 bg-main rounded-pill mb-4" id="docTabs" role="tablist" style="border: 1px solid var(--border-light);">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2" id="personalAction-tab" data-bs-toggle="tab" data-bs-target="#personalActionPanel" type="button" role="tab" aria-controls="personalActionPanel" aria-selected="true">
                                <i class="fa-solid fa-file-invoice"></i> Acción de Personal
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2" id="salaryLetter-tab" data-bs-toggle="tab" data-bs-target="#salaryLetterPanel" type="button" role="tab" aria-controls="salaryLetterPanel" aria-selected="false">
                                <i class="fa-solid fa-file-signature"></i> Constancia Laboral
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-2" id="docTabsContent">
                        <!-- PANEL 1: ACCIÓN DE PERSONAL -->
                        <div class="tab-pane fade show active" id="personalActionPanel" role="tabpanel" aria-labelledby="personalAction-tab">
                            <form id="personalActionForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="paArea" class="form-label form-label-desc">Área de la Acción <span class="text-danger">*</span></label>
                                        <select class="form-select" id="paArea" required>
                                            <option value="Administrativa">Administrativa</option>
                                            <option value="Operativa" selected>Operativa</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="paMotivo" class="form-label form-label-desc">Motivo de la Acción <span class="text-danger">*</span></label>
                                        <select class="form-select" id="paMotivo" required>
                                            <option value="Ingreso">Ingreso</option>
                                            <option value="Cambio de cargo">Cambio de cargo</option>
                                            <option value="Ascenso">Ascenso</option>
                                            <option value="Cambio Moneda Salario">Cambio Moneda Salario</option>
                                            <option value="Aumento Salario">Aumento Salario</option>
                                            <option value="Despido">Despido</option>
                                            <option value="Renuncia">Renuncia</option>
                                            <option value="Vacaciones">Vacaciones</option>
                                            <option value="Otros">Otros</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="paFechaConfeccion" class="form-label form-label-desc">Fecha de Confección <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="paFechaConfeccion" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="paFechaEfectiva" class="form-label form-label-desc">Efectiva a partir de <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="paFechaEfectiva" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="paNombramiento" class="form-label form-label-desc">Tipo de Nombramiento <span class="text-danger">*</span></label>
                                        <select class="form-select" id="paNombramiento" required>
                                            <option value="Temporal">Temporal</option>
                                            <option value="Indefinido" selected>Indefinido</option>
                                            <option value="No aplica">No aplica</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="paContratoAt" class="form-label form-label-desc">Jornada / Horario <span class="text-danger">*</span></label>
                                        <select class="form-select" id="paContratoAt" required>
                                            <option value="Tiempo Completo" selected>Tiempo Completo</option>
                                            <option value="Medio Tiempo">Medio Tiempo</option>
                                            <option value="Por horas">Por horas</option>
                                        </select>
                                    </div>

                                    <!-- Separador Situación Propuesta -->
                                    <div class="col-12 mt-4" id="paPropuestaHeader">
                                        <h6 class="text-primary fw-bold mb-2" style="font-size: 13.5px;"><i class="fa-solid fa-arrow-trend-up me-1"></i> Situación Propuesta (Opcional para Cambios/Aumentos)</h6>
                                        <div class="border-top border-light pt-2"></div>
                                    </div>

                                    <div class="col-md-6 pa-propuesta-field">
                                        <label for="paNuevoPuesto" class="form-label form-label-desc">Nuevo Puesto (Si aplica)</label>
                                        <select class="form-select" id="paNuevoPuesto">
                                            <!-- Cargado dinámicamente -->
                                        </select>
                                    </div>
                                    <div class="col-md-6 pa-propuesta-field">
                                        <label for="paNuevoDepto" class="form-label form-label-desc">Nuevo Departamento (Si aplica)</label>
                                        <select class="form-select" id="paNuevoDepto">
                                            <!-- Cargado dinámicamente -->
                                        </select>
                                    </div>

                                    <div class="col-md-6 pa-propuesta-field">
                                        <label for="paNuevaSucursal" class="form-label form-label-desc">Nueva Sucursal (Si aplica)</label>
                                        <select class="form-select" id="paNuevaSucursal">
                                            <!-- Cargado dinámicamente -->
                                        </select>
                                    </div>
                                    <div class="col-md-6 pa-propuesta-field">
                                        <label for="paNuevoHorario" class="form-label form-label-desc">Nuevo Horario / Jornada (Si aplica)</label>
                                        <select class="form-select" id="paNuevoHorario">
                                            <!-- Cargado dinámicamente -->
                                        </select>
                                    </div>

                                    <div class="col-md-6 pa-propuesta-field">
                                        <label for="paNuevoEstado" class="form-label form-label-desc">Nuevo Estado Laboral (Si aplica)</label>
                                        <select class="form-select" id="paNuevoEstado">
                                            <!-- Cargado dinámicamente -->
                                        </select>
                                    </div>
                                    <div class="col-md-6 pa-propuesta-field">
                                        <label for="paNuevoSalario" class="form-label form-label-desc">Nuevo Salario Bruto (₡)</label>
                                        <input type="number" step="0.01" class="form-control" id="paNuevoSalario" placeholder="₡ Dejar vacío si no cambia">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="paNacionalidad" class="form-label form-label-desc">Nacionalidad</label>
                                        <input type="text" class="form-control" id="paNacionalidad" value="Costarricense">
                                    </div>

                                    <div class="col-12">
                                        <label for="paObservaciones" class="form-label form-label-desc">Observaciones / Detalle <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="paObservaciones" rows="2" placeholder="Ej. Toma vacaciones los días 25, 26, 27 y 28 de julio" required></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top border-light d-flex justify-content-end">
                                    <button type="submit" class="btn btn-info px-4 py-2 d-flex align-items-center gap-2 fw-semibold" style="border-radius: var(--border-radius-md);">
                                        <i class="fa-solid fa-print"></i>
                                        <span>Generar Acción de Personal</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- PANEL 2: CONSTANCIA LABORAL -->
                        <div class="tab-pane fade" id="salaryLetterPanel" role="tabpanel" aria-labelledby="salaryLetter-tab">
                            <form id="salaryLetterForm">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="clFirmaNombre" class="form-label form-label-desc">Nombre del Firmante (Gerente/Representante) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="clFirmaNombre" value="Tulio Monestel" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="clFirmaPuesto" class="form-label form-label-desc">Puesto del Firmante <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="clFirmaPuesto" value="Gerente General" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="clFirmaCorreo" class="form-label form-label-desc">Correo de Contacto <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="clFirmaCorreo" value="tmonestel@latinsoftcr.net" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="clDetalleAdicional" class="form-label form-label-desc">Detalle de Embargos/Gravámenes <span class="text-danger">*</span></label>
                                        <select class="form-select" id="clDetalleAdicional">
                                            <option value="libre" selected>Se encuentra libre de embargos y gravámenes</option>
                                            <option value="posee">Posee embargos vigentes de ley</option>
                                            <option value="ninguno">No aplica</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="clDirigidoA" class="form-label form-label-desc">Dirigido a <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="clDirigidoA" value="A quien interese" required>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top border-light d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-4 py-2 d-flex align-items-center gap-2 fw-semibold" style="border-radius: var(--border-radius-md);">
                                        <i class="fa-solid fa-file-invoice"></i>
                                        <span>Generar Constancia Laboral</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const currentUserFullname = <?php echo json_encode($fullname); ?>;
        
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
            let employeesTable;
            let catalogsLoaded = false;
            let rawCatalogs = {};

            // ==========================================
            // 1. Control de Tema Visual Unificado (Light/Dark)
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

            // Fecha superior dinámica
            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const hoy = new Date();
            const fechaEsp = hoy.toLocaleDateString('es-CR', opciones);
            const fechaCap = fechaEsp.charAt(0).toUpperCase() + fechaEsp.slice(1);
            $('#currentDate').text(fechaCap);

            // ==========================================
            // 2. Inicialización de DataTables de Empleados
            // ==========================================
            employeesTable = $('#employeesTable').DataTable({
                ajax: {
                    url: 'ajax/recursos_humanos.php?action=list_employees',
                    type: 'GET',
                    dataSrc: function(json) {
                        // Calcular y rellenar KPIs en caliente basados en los datos del servidor
                        calculateKpis(json.data);
                        return json.data;
                    }
                },
                columns: [
                    { data: 'CodEmpleado' },
                    { data: 'Identificacion' },
                    { data: 'NombreCompleto', className: 'fw-semibold text-light' },
                    { data: 'Departamento' },
                    { data: 'Puesto' },
                    { data: 'Sucursal' },
                    { 
                        data: 'SalarioBase',
                        render: function(data, type, row) {
                            // Formatear moneda en colones costarricenses
                            const num = parseFloat(data);
                            return '₡' + num.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    },
                    { 
                        data: 'FechaIngreso',
                        render: function(data) {
                            if (!data) return '';
                            const parts = data.split('-');
                            return parts[2] + '/' + parts[1] + '/' + parts[0];
                        }
                    },
                    { 
                        data: 'Estado',
                        className: 'text-center',
                        render: function(data) {
                            const statusClass = data.toLowerCase();
                            const icon = data === 'ACTIVO' ? 'fa-circle-check' : 'fa-circle-xmark';
                            return `<span class="badge-status badge-status-${statusClass}"><i class="fa-solid ${icon}"></i>${data}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            const activeBtnClass = row.Estado === 'ACTIVO' ? '' : 'disabled';
                            return `
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <button type="button" class="btn-table-action btn-table-edit" onclick="editEmployee(${row.CodEmpleado})" title="Editar Expediente">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn-table-action btn-table-delete ${activeBtnClass}" onclick="deleteEmployee(${row.CodEmpleado}, '${row.NombreCompleto}')" title="Dar de Baja">
                                        <i class="fa-solid fa-user-minus"></i>
                                    </button>
                                    <button type="button" class="btn-table-action btn-table-docs" onclick="openDocsModal(${row.CodEmpleado})" title="Documentos / Acción de Personal">
                                        <i class="fa-solid fa-file-contract"></i>
                                    </button>
                                </div>
                            `;
                        }
                    },
                    { data: 'CodEmpresa', visible: false }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                responsive: true
            });

            // Cargar catálogos inmediatamente al inicio para poblar combos de reportes y filtros
            loadCatalogs();

            // ==========================================
            // 3. Calculador Dinámico de KPIs Organizacionales
            // ==========================================
            function calculateKpis(data) {
                let total = data.length;
                let activos = 0;
                let inactivos = 0;
                let salarioSuma = 0;
                let salarioMax = 0;

                data.forEach(function(emp) {
                    const sal = parseFloat(emp.SalarioBase);
                    if (emp.Estado === 'ACTIVO') {
                        activos++;
                        salarioSuma += sal;
                        if (sal > salarioMax) {
                            salarioMax = sal;
                        }
                    } else {
                        inactivos++;
                    }
                });

                const salarioProm = activos > 0 ? (salarioSuma / activos) : 0;

                // Actualizar interfaz
                $('#kpiTotal').text(total);
                $('#kpiActivosDesc').text(activos + ' activos en nómina');
                $('#kpiInactivos').text(inactivos);
                $('#kpiPromedio').text('₡' + salarioProm.toLocaleString('es-CR', { maximumFractionDigits: 0 }));
                $('#kpiMaximo').text('₡' + salarioMax.toLocaleString('es-CR', { maximumFractionDigits: 0 }));
            }

            // ==========================================
            // 4. Carga Dinámica de Catálogos de Base de Datos
            // ==========================================
            function loadCatalogs(callback) {
                if (catalogsLoaded) {
                    if (callback) callback();
                    return;
                }

                $.ajax({
                    url: 'ajax/recursos_humanos.php?action=get_catalogs',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            rawCatalogs = response;

                            // Poblar combo de Empresas del formulario de edición
                            const selectEmpresa = $('#cod_empresa');
                            selectEmpresa.empty().append('<option value="">Seleccione...</option>');
                            
                            response.empresas.forEach(function(emp) {
                                selectEmpresa.append(`<option value="${emp.CodEmpresa}">${emp.Nombre}</option>`);
                            });

                            // Poblar combo de Empresas de la barra de Reportes
                            const selectReportEmpresa = $('#reportCodEmpresa');
                            selectReportEmpresa.find('option:not(:first)').remove();
                            response.empresas.forEach(function(emp) {
                                selectReportEmpresa.append(`<option value="${emp.CodEmpresa}">${emp.Nombre}</option>`);
                            });

                            catalogsLoaded = true;
                            if (callback) callback();
                        } else {
                            showAlert('Fallo de catálogos: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('Error de conexión al recuperar catálogos organizacionales.', 'danger');
                    }
                });
            }

            // Filtrado dinámico de catálogos dependientes al cambiar de Empresa
            $('#cod_empresa').on('change', function() {
                const codEmpresa = parseInt($(this).val()) || 0;
                
                const selectSucursal = $('#cod_sucursal');
                const selectDepto = $('#cod_departamento');
                const selectPuesto = $('#cod_puesto');
                const selectHorario = $('#cod_horario');

                // Limpiar
                selectSucursal.empty().append('<option value="">Seleccione...</option>');
                selectDepto.empty().append('<option value="">Seleccione...</option>');
                selectPuesto.empty().append('<option value="">Seleccione...</option>');
                selectHorario.empty().append('<option value="">Seleccione...</option>');

                if (codEmpresa <= 0) return;

                // Filtrar sucursales
                rawCatalogs.sucursales.forEach(function(suc) {
                    if (suc.CodEmpresa == codEmpresa) {
                        selectSucursal.append(`<option value="${suc.CodSucursal}">${suc.Nombre}</option>`);
                    }
                });

                // Filtrar departamentos
                rawCatalogs.departamentos.forEach(function(dep) {
                    if (dep.CodEmpresa == codEmpresa) {
                        selectDepto.append(`<option value="${dep.CodDepartamento}">${dep.Descripcion}</option>`);
                    }
                });

                // Filtrar puestos
                rawCatalogs.puestos.forEach(function(pue) {
                    if (pue.CodEmpresa == codEmpresa) {
                        selectPuesto.append(`<option value="${pue.CodPuesto}">${pue.Descripcion}</option>`);
                    }
                });

                // Filtrar horarios
                rawCatalogs.horarios.forEach(function(hor) {
                    if (hor.CodEmpresa == codEmpresa) {
                        selectHorario.append(`<option value="${hor.CodHorario}">${hor.Descripcion} (${hor.HoraEntrada} - ${hor.HoraSalida})</option>`);
                    }
                });
            });

            // ==========================================
            // 4.1 Consulta de Cédulas Costarricenses (GoMeta API)
            // ==========================================
            function queryCedulaGoMeta(cedulaRaw) {
                // Limpiar la cédula para extraer solo dígitos numéricos
                const cedula = cedulaRaw.replace(/[^0-9]/g, '');
                const feedback = $('#cedulaFeedback');
                const btnSearch = $('#btnSearchCedula');
                const searchIcon = $('#searchCedulaIcon');
                const searchSpinner = $('#searchCedulaSpinner');

                if (cedula.length < 9) {
                    feedback.html('<i class="fa-solid fa-circle-exclamation me-1"></i> La identificación debe contener al menos 9 dígitos.').show();
                    return;
                }

                // Configurar animación de carga
                feedback.hide().text('');
                searchIcon.hide();
                searchSpinner.show();
                btnSearch.prop('disabled', true);

                fetch(`https://apis.gometa.org/cedulas/${cedula}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al conectar con la red del servicio.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos recibidos:', data);

                        if (data.resultcount === 0 || !data.results || data.results.length === 0) {
                            feedback.html('<i class="fa-solid fa-triangle-exclamation me-1"></i> La cédula es inválida o no fue encontrada.').show();
                            $('#nombre').val(''); 
                            $('#apellido1').val(''); 
                            $('#apellido2').val(''); 
                        } else {
                            const result = data.results[0];
                            const firstname = result.firstname || 'Nombre no disponible';
                            let lastname = result.lastname || 'Apellido no disponible';
                            
                            // Carlos: Si el nombre y el apellido son iguales, asignar el valor de la variable 'temp'
                            if (firstname === lastname) {
                                lastname = result.temp || 'Apellido no disponible';
                            }
                            
                            // Asignar el primer nombre limpio
                            $('#nombre').val(firstname.trim());
                            
                            // Mapear los apellidos de forma inteligente a apellido1 y apellido2
                            const lastnameParts = lastname.trim().split(/\s+/);
                            if (lastnameParts.length > 0) {
                                $('#apellido1').val(lastnameParts[0]);
                                if (lastnameParts.length > 1) {
                                    $('#apellido2').val(lastnameParts.slice(1).join(' '));
                                } else {
                                    $('#apellido2').val('');
                                }
                            }
                        }
                    })
                    .catch(error => {
                        feedback.html('<i class="fa-solid fa-circle-exclamation me-1"></i> Error al buscar la cédula. Digite el nombre manualmente.').show();
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        searchSpinner.hide();
                        searchIcon.show();
                        btnSearch.prop('disabled', false);
                    });
            }

            // Disparadores interactivos del buscador de cédulas
            $('#btnSearchCedula').on('click', function() {
                queryCedulaGoMeta($('#identificacion').val());
            });

            $('#identificacion').on('blur', function() {
                // Solo gatillar la búsqueda automática si es un registro nuevo (CodEmpleado === 0) y no está vacío
                if ($('#cod_empleado').val() == '0' && $(this).val().trim() !== '') {
                    queryCedulaGoMeta($(this).val());
                }
            });

            // ==========================================
            // 5. Alta de Empleado (Muestra Modal Vacío)
            // ==========================================
            $('#btnAddNewEmployee').on('click', function() {
                // Limpiar alertas previas
                $('#modalAlertContainer').empty();
                
                // Cargar catálogos y rellenar formulario vacío
                loadCatalogs(function() {
                    $('#employeeForm')[0].reset();
                    $('#cod_empleado').val('0');
                    $('#estadoContainer').hide();
                    $('#estado').val('ACTIVO');
                    
                    // Regresar a la primera pestaña
                    $('#employeeTabs button:first').tab('show');
                    
                    // Actualizar título de modal
                    $('#employeeModalLabel').html('<i class="fa-solid fa-user-plus text-primary me-2"></i> Registrar Nuevo Empleado');
                    $('#saveBtnText').text('Guardar Colaborador');
                    
                    // Cargar fecha actual por defecto en fecha_ingreso
                    const todayStr = new Date().toISOString().substring(0, 10);
                    $('#fecha_ingreso').val(todayStr);

                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
                    modal.show();
                });
            });

            // ==========================================
            // 6. Guardar / Editar Empleado (Submit Form)
            // ==========================================
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();

                // Interfaz de carga
                $('#btnSaveEmployee').prop('disabled', true);
                $('#saveBtnText').text('Procesando transacciones...');
                $('#saveSpinner').show();
                $('#modalAlertContainer').empty();

                $.ajax({
                    url: 'ajax/recursos_humanos.php?action=save_employee',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            // Alerta de éxito e inicialización
                            showAlert('<strong>¡Operación Exitosa!</strong><br>' + response.message, 'success', '#modalAlertContainer');
                            
                            // Recargar DataTable al instante
                            employeesTable.ajax.reload(null, false);
                            
                            // Verificar si hay cambios en datos laborales/nómina para generar Acción de Personal
                            const empId = parseInt($('#cod_empleado').val());
                            if (empId > 0 && activeEditEmployeeBefore) {
                                let hasLaborChanges = false;
                                let changesSummary = [];

                                const oldPuesto = activeEditEmployeeBefore.CodPuesto;
                                const newPuesto = $('#cod_puesto').val();
                                if (oldPuesto != newPuesto) {
                                    hasLaborChanges = true;
                                    changesSummary.push('Puesto');
                                }

                                const oldDepto = activeEditEmployeeBefore.CodDepartamento;
                                const newDepto = $('#cod_departamento').val();
                                if (oldDepto != newDepto) {
                                    hasLaborChanges = true;
                                    changesSummary.push('Departamento');
                                }

                                const oldSuc = activeEditEmployeeBefore.CodSucursal;
                                const newSuc = $('#cod_sucursal').val();
                                if (oldSuc != newSuc) {
                                    hasLaborChanges = true;
                                    changesSummary.push('Sucursal');
                                }

                                const oldHor = activeEditEmployeeBefore.CodHorario;
                                const newHor = $('#cod_horario').val();
                                if (oldHor != newHor) {
                                    hasLaborChanges = true;
                                    changesSummary.push('Horario');
                                }

                                const oldEst = activeEditEmployeeBefore.Estado;
                                const newEst = $('#estado').val();
                                if (oldEst != newEst) {
                                    hasLaborChanges = true;
                                    changesSummary.push('Estado Laboral');
                                }

                                const oldSal = parseFloat(activeEditEmployeeBefore.SalarioBase);
                                const newSal = parseFloat($('#salario_base').val());
                                if (oldSal != newSal) {
                                    hasLaborChanges = true;
                                    changesSummary.push('Salario');
                                }

                                if (hasLaborChanges) {
                                    // Cerrar modal al instante
                                    bootstrap.Modal.getInstance(document.getElementById('employeeModal')).hide();

                                    Swal.fire({
                                        title: '¿Generar Acción de Personal?',
                                        html: `Se detectaron cambios en: <b>${changesSummary.join(', ')}</b>.<br><br>¿Desea generar e imprimir el documento de Acción de Personal para registrar este movimiento?`,
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonColor: '#1e3a8a',
                                        cancelButtonColor: '#64748b',
                                        confirmButtonText: '<i class="fa-solid fa-print"></i> Sí, generar e imprimir',
                                        cancelButtonText: 'No, solo guardar'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            // 1. Establecer activeDocEmployee con los datos originales (Actuales)
                                            activeDocEmployee = activeEditEmployeeBefore;

                                            // 2. Llenar los campos propuestos en el modal de documentos
                                            $('#paNuevoPuesto').val(newPuesto == oldPuesto ? '' : newPuesto);
                                            $('#paNuevoDepto').val(newDepto == oldDepto ? '' : newDepto);
                                            $('#paNuevaSucursal').val(newSuc == oldSuc ? '' : newSuc);
                                            $('#paNuevoHorario').val(newHor == oldHor ? '' : newHor);
                                            $('#paNuevoEstado').val(newEst == oldEst ? '' : newEst);
                                            $('#paNuevoSalario').val(newSal == oldSal ? '' : newSal);
                                            
                                            // Configurar fechas y observaciones por defecto
                                            const todayStr = new Date().toISOString().substring(0, 10);
                                            $('#paFechaConfeccion').val(todayStr);
                                            $('#paFechaEfectiva').val(todayStr);
                                            $('#paObservaciones').val('Generado automáticamente desde actualización de expediente.');

                                            // Lógica para auto-seleccionar el motivo
                                            if (newPuesto != oldPuesto) {
                                                $('#paMotivo').val('Cambio de cargo').trigger('change');
                                            } else if (newEst === 'DESPIDO' && oldEst !== 'DESPIDO') {
                                                $('#paMotivo').val('Despido').trigger('change');
                                            } else if (newEst === 'RENUNCIA' && oldEst !== 'RENUNCIA') {
                                                $('#paMotivo').val('Renuncia').trigger('change');
                                            } else if (newSuc != oldSuc || newDepto != oldDepto || newHor != oldHor) {
                                                $('#paMotivo').val('Otros').trigger('change');
                                            } else if (newSal != oldSal) {
                                                $('#paMotivo').val('Otros').trigger('change');
                                            } else {
                                                $('#paMotivo').val('Otros').trigger('change');
                                            }

                                            // 3. Gatillar el submit de impresión del documento
                                            setTimeout(function() {
                                                $('#personalActionForm').trigger('submit');
                                            }, 200);
                                        }
                                    });
                                    return;
                                }
                            }

                            // Cerrar modal tras 1.5 segundos
                            setTimeout(function() {
                                bootstrap.Modal.getInstance(document.getElementById('employeeModal')).hide();
                            }, 1500);
                        } else {
                            // Mostrar error de base de datos controlado
                            showAlert('<strong>Error transaccional en SQL Server:</strong><br>' + response.message, 'danger', '#modalAlertContainer');
                            restoreSaveButton();
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error al comunicarse con el controlador PHP AJAX de Recursos Humanos.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showAlert('<strong>Fallo de Infraestructura:</strong><br>' + errorMsg, 'danger', '#modalAlertContainer');
                        restoreSaveButton();
                    }
                });
            });

            function restoreSaveButton() {
                $('#btnSaveEmployee').prop('disabled', false);
                $('#saveBtnText').text('Guardar Colaborador');
                $('#saveSpinner').hide();
            }

            // ==========================================
            // 7. Baja Lógica de Empleado (Submit Form)
            // ==========================================
            $('#deleteForm').on('submit', function(e) {
                e.preventDefault();

                $('#btnConfirmDelete').prop('disabled', true);
                $('#deleteSpinner').show();

                $.ajax({
                    url: 'ajax/recursos_humanos.php?action=delete_employee',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                            employeesTable.ajax.reload(null, false);
                        } else {
                            alert('Error al inactivar: ' + response.message);
                        }
                        restoreDeleteButton();
                    },
                    error: function() {
                        alert('Fallo de infraestructura al procesar la baja del empleado.');
                        restoreDeleteButton();
                    }
                });
            });

            function restoreDeleteButton() {
                $('#btnConfirmDelete').prop('disabled', false);
                $('#deleteSpinner').hide();
            }

            // ==========================================
            // 8. Funciones Globales para Botones de Tabla
            // ==========================================
            let activeEditEmployeeBefore = null;

            window.editEmployee = function(id) {
                $('#modalAlertContainer').empty();
                activeEditEmployeeBefore = null;

                loadCatalogs(function() {
                    $.ajax({
                        url: 'ajax/recursos_humanos.php?action=get_employee&id=' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                const emp = response.data;
                                activeEditEmployeeBefore = emp;
                                
                                // Rellenar inputs
                                $('#cod_empleado').val(emp.CodEmpleado);
                                $('#identificacion').val(emp.Identificacion);
                                $('#correo').val(emp.Correo);
                                $('#nombre').val(emp.Nombre);
                                $('#apellido1').val(emp.Apellido1);
                                $('#apellido2').val(emp.Apellido2);
                                $('#fecha_nacimiento').val(emp.FechaNacimiento);
                                $('#fecha_ingreso').val(emp.FechaIngreso);
                                $('#telefono').val(emp.Telefono);
                                $('#direccion').val(emp.Direccion);
                                
                                // Rellenar financieros
                                $('#tipo_salario').val(emp.TipoSalario);
                                $('#salario_base').val(emp.SalarioBase);
                                $('#banco').val(emp.Banco);
                                $('#cuenta_iban').val(emp.CuentaIBAN);
                                $('#cuenta_contable').val(emp.CuentaContable);

                                // Rellenar relacionales con filtro dependiente gatillado manualmente
                                $('#cod_empresa').val(emp.CodEmpresa).trigger('change');
                                $('#cod_sucursal').val(emp.CodSucursal);
                                $('#cod_departamento').val(emp.CodDepartamento);
                                $('#cod_puesto').val(emp.CodPuesto);
                                $('#cod_horario').val(emp.CodHorario);

                                // Configurar Estado
                                $('#estadoContainer').show();
                                $('#estado').val(emp.Estado);
                                
                                // Modificar títulos
                                $('#employeeModalLabel').html('<i class="fa-solid fa-user-pen text-primary me-2"></i> Editar Expediente de Empleado');
                                $('#saveBtnText').text('Guardar Cambios');
                                
                                // Resetear tabs
                                $('#employeeTabs button:first').tab('show');
                                
                                // Mostrar modal
                                restoreSaveButton();
                                const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
                                modal.show();
                            } else {
                                alert('Error al cargar empleado: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Fallo de conexión al cargar el expediente.');
                        }
                    });
                });
            };

            window.deleteEmployee = function(id, name) {
                $('#delete_cod_empleado').val(id);
                $('#deleteEmployeeName').text(name);
                
                // Colocar fecha actual como fecha de salida por defecto
                const todayStr = new Date().toISOString().substring(0, 10);
                $('#fecha_salida').val(todayStr);
                
                restoreDeleteButton();
                const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                modal.show();
            };

            // ==========================================
            // DOCUMENT GENERATORS: ACCIÓN DE PERSONAL & CONSTANCIA
            // ==========================================
            let activeDocEmployee = null;

            window.openDocsModal = function(id) {
                loadCatalogs(function() {
                    $.ajax({
                        url: 'ajax/recursos_humanos.php?action=get_employee&id=' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                activeDocEmployee = response.data;
                                
                                const fullName = activeDocEmployee.Nombre + ' ' + activeDocEmployee.Apellido1 + ' ' + (activeDocEmployee.Apellido2 || '');
                                $('#docEmployeeName').text(fullName);
                                
                                const todayStr = new Date().toISOString().substring(0, 10);
                                $('#paFechaConfeccion').val(todayStr);
                                $('#paFechaEfectiva').val(todayStr);
                                
                                // Popular combos de Situación Propuesta basados en la Empresa del empleado
                                const empCompanyId = activeDocEmployee.CodEmpresa;
                                
                                // Nueva Sucursal
                                const ddlSucursal = $('#paNuevaSucursal');
                                ddlSucursal.empty().append('<option value="">-- Sin cambio --</option>');
                                rawCatalogs.sucursales.forEach(function(suc) {
                                    if (suc.CodEmpresa == empCompanyId) {
                                        ddlSucursal.append(`<option value="${suc.CodSucursal}" data-name="${suc.Nombre}">${suc.Nombre}</option>`);
                                    }
                                });
                                
                                // Nuevo Departamento
                                const ddlDepto = $('#paNuevoDepto');
                                ddlDepto.empty().append('<option value="">-- Sin cambio --</option>');
                                rawCatalogs.departamentos.forEach(function(dep) {
                                    if (dep.CodEmpresa == empCompanyId) {
                                        ddlDepto.append(`<option value="${dep.CodDepartamento}" data-name="${dep.Descripcion}">${dep.Descripcion}</option>`);
                                    }
                                });
                                
                                // Nuevo Puesto
                                const ddlPuesto = $('#paNuevoPuesto');
                                ddlPuesto.empty().append('<option value="">-- Sin cambio --</option>');
                                rawCatalogs.puestos.forEach(function(pue) {
                                    if (pue.CodEmpresa == empCompanyId) {
                                        ddlPuesto.append(`<option value="${pue.CodPuesto}" data-name="${pue.Descripcion}">${pue.Descripcion}</option>`);
                                    }
                                });
                                
                                // Nuevo Horario
                                const ddlHorario = $('#paNuevoHorario');
                                ddlHorario.empty().append('<option value="">-- Sin cambio --</option>');
                                rawCatalogs.horarios.forEach(function(hor) {
                                    if (hor.CodEmpresa == empCompanyId) {
                                        ddlHorario.append(`<option value="${hor.CodHorario}" data-name="${hor.Descripcion}">${hor.Descripcion}</option>`);
                                    }
                                });
                                
                                // Nuevo Estado Laboral
                                const ddlEstado = $('#paNuevoEstado');
                                ddlEstado.empty().append('<option value="">-- Sin cambio --</option>');
                                ddlEstado.append('<option value="ACTIVO">ACTIVO</option>');
                                ddlEstado.append('<option value="DESPIDO">DESPIDO</option>');
                                ddlEstado.append('<option value="RENUNCIA">RENUNCIA</option>');
                                ddlEstado.append('<option value="INACTIVO">INACTIVO</option>');

                                $('#paNuevoSalario').val('');
                                $('#paObservaciones').val('');
                                $('#paNacionalidad').val('Costarricense');
                                
                                // Seleccionar automáticamente si posee embargos activos de ley o pensiones alimenticias
                                $('#clDetalleAdicional').val(activeDocEmployee.TieneEmbargos || 'libre');
                                
                                // Resetear motivo por defecto a Ingreso y disparar cambio
                                $('#paMotivo').val('Ingreso').trigger('change');
                                
                                // Resetear tabs a primera
                                $('#docTabs button:first').tab('show');
                                
                                const modal = new bootstrap.Modal(document.getElementById('employeeDocsModal'));
                                modal.show();
                            } else {
                                Swal.fire('Error', 'No se pudo cargar la información del colaborador: ' + response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Fallo de conexión al cargar la información.', 'error');
                        }
                    });
                });
            };

            // Triggers de cambio automático de motivo en caliente
            $('#paNuevaSucursal, #paNuevoDepto, #paNuevoHorario').on('change', function() {
                if ($(this).val()) {
                    $('#paMotivo').val('Otros').trigger('change');
                }
            });
            
            $('#paNuevoPuesto').on('change', function() {
                if ($(this).val()) {
                    $('#paMotivo').val('Cambio de cargo').trigger('change');
                }
            });
            
            $('#paNuevoEstado').on('change', function() {
                const val = $(this).val();
                if (val === 'DESPIDO') {
                    $('#paMotivo').val('Despido').trigger('change');
                } else if (val === 'RENUNCIA') {
                    $('#paMotivo').val('Renuncia').trigger('change');
                } else if (val === 'INACTIVO') {
                    $('#paMotivo').val('Otros').trigger('change');
                }
            });

            // Control dinámico de visibilidad para Situación Propuesta en Acción de Personal
            $('#paMotivo').on('change', function() {
                const motivo = $(this).val();
                if (motivo === 'Ingreso') {
                    $('#paPropuestaHeader').hide();
                    $('.pa-propuesta-field').hide();
                    $('#paNuevoPuesto').val('');
                    $('#paNuevoDepto').val('');
                    $('#paNuevoSalario').val('');
                } else {
                    $('#paPropuestaHeader').show();
                    $('.pa-propuesta-field').show();
                }
            });

            $('#personalActionForm').on('submit', function(e) {
                e.preventDefault();
                if (!activeDocEmployee) return;

                const area = $('#paArea').val();
                const motivo = $('#paMotivo').val();
                const fechaConf = $('#paFechaConfeccion').val();
                const fechaEfec = $('#paFechaEfectiva').val();
                const nombramiento = $('#paNombramiento').val();
                const contratoAt = $('#paContratoAt').val();
                
                const nuevoPuestoVal = $('#paNuevoPuesto').val();
                const nuevoPuestoText = nuevoPuestoVal ? $('#paNuevoPuesto option:selected').data('name') : '';
                
                const nuevoDeptoVal = $('#paNuevoDepto').val();
                const nuevoDeptoText = nuevoDeptoVal ? $('#paNuevoDepto option:selected').data('name') : '';

                const nuevaSucursalVal = $('#paNuevaSucursal').val();
                const nuevaSucursalText = nuevaSucursalVal ? $('#paNuevaSucursal option:selected').data('name') : '';

                const nuevoHorarioVal = $('#paNuevoHorario').val();
                const nuevoHorarioText = nuevoHorarioVal ? $('#paNuevoHorario option:selected').data('name') : '';

                const nuevoEstadoVal = $('#paNuevoEstado').val();
                
                const nuevoSalarioRaw = $('#paNuevoSalario').val();
                const nacionalidad = $('#paNacionalidad').val().trim();
                const observaciones = $('#paObservaciones').val().trim();

                const empName = activeDocEmployee.Nombre + ' ' + activeDocEmployee.Apellido1 + ' ' + (activeDocEmployee.Apellido2 || '');
                const empCedula = activeDocEmployee.Identificacion;
                const empNacimiento = activeDocEmployee.FechaNacimiento;
                const empTelefono = activeDocEmployee.Telefono || 'N/A';
                const empDireccion = activeDocEmployee.Direccion || 'N/A';
                const empPuesto = activeDocEmployee.Puesto || 'N/A';
                const empDepto = activeDocEmployee.Departamento || 'N/A';
                const empSucursal = activeDocEmployee.Sucursal || 'N/A';
                const empHorario = activeDocEmployee.Horario || 'N/A';
                const empEstado = activeDocEmployee.Estado || 'ACTIVO';
                const empSalarioActual = parseFloat(activeDocEmployee.SalarioBase);

                // helper to clean double UTF-8 encoding in database and deduplicate Costa Rica suffix
                const sanitizeAddress = (addr) => {
                    if (!addr) return 'San José, Costa Rica';
                    let clean = addr.replace(/Ã©/g, 'é')
                                    .replace(/Ã¡/g, 'á')
                                    .replace(/Ã­/g, 'í')
                                    .replace(/Ã³/g, 'ó')
                                    .replace(/Ãº/g, 'ú')
                                    .replace(/Ã±/g, 'ñ');
                    if (clean.toLowerCase().includes('costa rica')) {
                        return clean;
                    }
                    return clean + ', Costa Rica';
                };

                // dynamic company variables retrieved from database left join
                const empEmpresa = activeDocEmployee.EmpresaNombre || 'Corporación Allison S.A.';
                const empEmpresaComercial = activeDocEmployee.EmpresaNombreComercial || activeDocEmployee.EmpresaNombre || 'LATINSOFT COSTA RICA';
                const empEmpresaCedula = activeDocEmployee.EmpresaCedulaJuridica || '3-101-998877';
                const empEmpresaDireccion = sanitizeAddress(activeDocEmployee.EmpresaDireccion);

                const propPuesto = nuevoPuestoText || empPuesto;
                const propDepto = nuevoDeptoText || empDepto;
                const propSucursal = nuevaSucursalText || empSucursal;
                const propHorario = nuevoHorarioText || empHorario;
                const propEstado = nuevoEstadoVal || empEstado;
                const propSalario = nuevoSalarioRaw ? parseFloat(nuevoSalarioRaw) : empSalarioActual;

                const ccssActual = empSalarioActual * 0.1067;
                const netoActual = empSalarioActual - ccssActual;

                const ccssProp = propSalario * 0.1067;
                const netoProp = propSalario - ccssProp;

                let pctAumento = '0.00%';
                if (propSalario > empSalarioActual) {
                    pctAumento = (((propSalario - empSalarioActual) / empSalarioActual) * 100).toFixed(2) + '%';
                }

                const formatDate = (dateStr) => {
                    if (!dateStr) return '';
                    const p = dateStr.split('-');
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
                            .logo-icon {
                                color: #2563eb;
                                font-size: 32px;
                                font-weight: bold;
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
                                margin-bottom: 18px;
                                box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.2);
                            }
                            @media print {
                                .btn-print-action, .no-print-toolbar {
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
                            <button class="btn-print-action" id="btnToggleBW" style="margin-bottom: 0;"><i class="fa-solid fa-circle-half-stroke"></i> Modo Blanco y Negro</button>
                            <button class="btn-print-action" onclick="window.print()" style="margin-bottom: 0;"><i class="fa-solid fa-print"></i> Imprimir Documento</button>
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
                                <td class="value">${formatDate(activeDocEmployee.FechaIngreso)}</td>
                            </tr>
                        </table>

                        ${motivo === 'Ingreso' ? `
                            <div style="margin-top: 10px;">
                                <div class="section-header" style="margin-top: 0;">Situación Laboral de Ingreso</div>
                                <table class="no-border">
                                    <tr>
                                        <td class="label" style="width: 20%">Puesto:</td>
                                        <td class="value" style="width: 30%">${empPuesto}</td>
                                        <td class="label" style="width: 20%">Departamento:</td>
                                        <td class="value" style="width: 30%">${empDepto}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Sucursal:</td>
                                        <td class="value">${empSucursal}</td>
                                        <td class="label">Horario/Jornada:</td>
                                        <td class="value">${empHorario}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Estado Laboral:</td>
                                        <td class="value">${empEstado}</td>
                                        <td class="label">Salario Bruto:</td>
                                        <td class="value" style="font-weight: 700;">${formatColones(empSalarioActual)}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">CCSS 10.67%:</td>
                                        <td class="value">${formatColones(ccssActual)}</td>
                                        <td class="label">Salario Neto:</td>
                                        <td class="value" style="font-weight: 700;">${formatColones(netoActual)}</td>
                                    </tr>
                                </table>
                            </div>
                        ` : `
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
                                                <td class="value" style="${nuevoPuestoVal ? 'font-weight: 700; color: #1e3a8a;' : ''}">${propPuesto}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Departamento:</td>
                                                <td class="value" style="${nuevoDeptoVal ? 'font-weight: 700; color: #1e3a8a;' : ''}">${propDepto}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Sucursal:</td>
                                                <td class="value" style="${nuevaSucursalVal ? 'font-weight: 700; color: #1e3a8a;' : ''}">${propSucursal}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Horario/Jornada:</td>
                                                <td class="value" style="${nuevoHorarioVal ? 'font-weight: 700; color: #1e3a8a;' : ''}">${propHorario}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Estado Laboral:</td>
                                                <td class="value" style="${nuevoEstadoVal ? 'font-weight: 700; color: #1e3a8a;' : ''}">${propEstado}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Salario Bruto:</td>
                                                <td class="value" style="${nuevoSalarioRaw ? 'font-weight: 700; color: #1e3a8a;' : ''}">${formatColones(propSalario)}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">CCSS 10.67%:</td>
                                                <td class="value" style="${nuevoSalarioRaw ? 'font-weight: 700; color: #1e3a8a;' : ''}">${formatColones(ccssProp)}</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Salario Neto:</td>
                                                <td class="value" style="font-weight: 700; ${nuevoSalarioRaw ? 'color: #1e3a8a;' : ''}">${formatColones(netoProp)}</td>
                                            </tr>
                                            <tr>
                                                <td class="label" style="color: #b30000;">Aumento:</td>
                                                <td class="value" style="font-weight: 700; color: #b30000;">${pctAumento}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        `}

                        <div class="section-header">Observaciones</div>
                        <div class="observations-box">
                            ${observaciones}
                        </div>

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

                bootstrap.Modal.getInstance(document.getElementById('employeeDocsModal')).hide();
                Swal.fire({
                    title: '¡Acción Generada!',
                    text: 'La acción de personal se ha abierto en una pestaña lista para imprimir.',
                    icon: 'success',
                    confirmButtonText: 'Entendido'
                });
            });

            $('#salaryLetterForm').on('submit', function(e) {
                e.preventDefault();
                if (!activeDocEmployee) return;

                const firmNombre = $('#clFirmaNombre').val().trim();
                const firmPuesto = $('#clFirmaPuesto').val().trim();
                const firmCorreo = $('#clFirmaCorreo').val().trim();
                const detAdicional = $('#clDetalleAdicional').val();
                const dirigidoA = $('#clDirigidoA').val().trim();

                const empName = activeDocEmployee.Nombre + ' ' + activeDocEmployee.Apellido1 + ' ' + (activeDocEmployee.Apellido2 || '');
                const empCedula = activeDocEmployee.Identificacion;
                const empPuesto = activeDocEmployee.Puesto || 'N/A';
                const empSalario = parseFloat(activeDocEmployee.SalarioBase);
                const empIngreso = activeDocEmployee.FechaIngreso;
                
                // helper to clean double UTF-8 encoding in database and deduplicate Costa Rica suffix
                const sanitizeAddress = (addr) => {
                    if (!addr) return 'San José, Costa Rica';
                    let clean = addr.replace(/Ã©/g, 'é')
                                    .replace(/Ã¡/g, 'á')
                                    .replace(/Ã­/g, 'í')
                                    .replace(/Ã³/g, 'ó')
                                    .replace(/Ãº/g, 'ú')
                                    .replace(/Ã±/g, 'ñ');
                    if (clean.toLowerCase().includes('costa rica')) {
                        return clean;
                    }
                    return clean + ', Costa Rica';
                };

                // dynamic company variables retrieved from database left join
                const empEmpresa = activeDocEmployee.EmpresaNombre || 'Corporación Allison S.A.';
                const empEmpresaComercial = activeDocEmployee.EmpresaNombreComercial || activeDocEmployee.EmpresaNombre || 'LATINSOFT COSTA RICA';
                const empEmpresaCedula = activeDocEmployee.EmpresaCedulaJuridica || '3-101-998877';
                const empEmpresaDireccion = sanitizeAddress(activeDocEmployee.EmpresaDireccion);
                const empEmpresaTelefono = activeDocEmployee.EmpresaTelefono || '2255-0000';
                const empEmpresaCorreo = activeDocEmployee.EmpresaCorreo || 'contacto@latinsoftcr.net';

                const formatColones = (num) => {
                    return '₡' + num.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                const formatFechaIngreso = (dateStr) => {
                    if (!dateStr) return '';
                    const parts = dateStr.split('-');
                    const months = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
                    const day = parseInt(parts[2]);
                    const month = months[parseInt(parts[1]) - 1];
                    const year = parts[0];
                    return `${day} de ${month} del ${year}`;
                };

                let embargoText = 'se encuentra libre de embargos y gravámenes';
                if (detAdicional === 'posee') {
                    embargoText = 'posee retenciones y embargos vigentes según regulaciones de ley';
                } else if (detAdicional === 'ninguno') {
                    embargoText = 'no registra retenciones adicionales a los rebajos obligatorios de ley';
                }

                const dateNow = new Date();
                const dayNum = dateNow.getDate();
                const yearNum = dateNow.getFullYear();
                const monthsText = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
                const monthText = monthsText[dateNow.getMonth()];

                const numbersToText = (num) => {
                    const units = ['cero', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 
                                   'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte',
                                   'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintiocho', 'veintinueve', 'treinta', 'treinta y uno'];
                    return units[num] || num.toString();
                };
                
                const yearToText = (yr) => {
                    if (yr === 2026) return "dos mil veintiséis";
                    if (yr === 2025) return "dos mil veinticinco";
                    if (yr === 2024) return "dos mil veinticuatro";
                    return yr.toString();
                };

                const diaEnLetras = numbersToText(dayNum);
                const anioEnLetras = yearToText(yearNum);

                const printWindow = window.open('', '_blank', 'width=900,height=900');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Constancia Laboral - ${empName}</title>
                        <meta charset="utf-8">
                        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                        <style>
                            body {
                                font-family: 'Montserrat', sans-serif;
                                color: #222222;
                                background-color: #ffffff;
                                padding: 60px 80px;
                                margin: 0;
                                font-size: 14px;
                                line-height: 1.8;
                            }
                            .letterhead {
                                display: flex;
                                justify-content: flex-start;
                                align-items: center;
                                margin-bottom: 40px;
                                border: 1.5px solid #cbd5e1;
                                border-radius: 12px;
                                padding: 18px 25px;
                                background-color: #f8fafc;
                                width: fit-content;
                                max-width: 100%;
                            }
                            .logo-container {
                                display: flex;
                                align-items: center;
                                gap: 16px;
                            }
                            .company-details {
                                font-size: 11px;
                                color: #475569;
                                margin-top: 4px;
                                border-top: 1px solid #e2e8f0;
                                padding-top: 4px;
                                line-height: 1.4;
                            }
                            .logo-icon {
                                color: #ff6600;
                                font-size: 36px;
                                font-weight: bold;
                            }
                            .logo-text {
                                font-weight: 700;
                                font-size: 26px;
                                text-transform: uppercase;
                                letter-spacing: 1px;
                            }
                            .logo-subtext {
                                font-size: 12px;
                                color: #666;
                                font-weight: 600;
                            }
                            .document-title {
                                text-align: center;
                                margin-bottom: 50px;
                                font-weight: 700;
                                font-size: 18px;
                                letter-spacing: 1px;
                            }
                            .letter-body {
                                text-align: justify;
                                margin-bottom: 40px;
                            }
                            .letter-paragraph {
                                text-indent: 40px;
                                margin-bottom: 24px;
                            }
                            .signature-container {
                                margin-top: 60px;
                                text-align: left;
                            }
                            .signature-block {
                                display: inline-block;
                            }
                            .signature-line {
                                border-top: 1px solid #333333;
                                margin-top: 40px;
                                padding-top: 8px;
                                font-weight: 700;
                            }
                            .signature-sub {
                                font-size: 13px;
                                color: #555;
                                line-height: 1.4;
                            }
                            .btn-print-action {
                                background-color: #198754;
                                color: white;
                                border: none;
                                padding: 10px 20px;
                                font-size: 13px;
                                font-weight: bold;
                                border-radius: 4px;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                                margin-bottom: 30px;
                            }
                            @media print {
                                .btn-print-action {
                                    display: none;
                                }
                                body {
                                    padding: 0;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <button class="btn-print-action" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Constancia</button>
                        
                        <div class="letterhead">
                            <div class="logo-container">
                                <img src="img/logo_bahia.png" alt="Logo" style="height: 60px; max-height: 60px; object-fit: contain;">
                                <div>
                                    <div class="logo-text">${empEmpresaComercial}</div>
                                    <div class="logo-subtext" style="font-weight: 700; color: #1e3a8a;">${empEmpresa}</div>
                                    <div class="company-details">
                                        <i class="fa-solid fa-location-dot"></i> ${empEmpresaDireccion}<br>
                                        <i class="fa-solid fa-phone"></i> Tel: ${empEmpresaTelefono} &nbsp;|&nbsp; 
                                        <i class="fa-solid fa-envelope"></i> ${empEmpresaCorreo}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="document-title">
                            CONSTANCIA LABORAL
                        </div>

                        <div class="letter-body">
                            <p class="letter-paragraph">
                                Se hace constar que el Sr(a). <strong>${empName}</strong>, con cédula de identidad <strong>#${empCedula}</strong>, trabaja para la empresa <strong>${empEmpresa}</strong> (con cédula jurídica ${empEmpresaCedula}), desde el <strong>${formatFechaIngreso(empIngreso)}</strong> a la fecha, desempeñando actualmente el puesto de <strong>${empPuesto}</strong>, devengando un salario base bruto mensual de <strong>${formatColones(empSalario)}</strong>.
                            </p>
                            
                            <p class="letter-paragraph">
                                Así mismo se hace constar que dicho ingreso a la fecha <strong>${embargoText}</strong>.
                            </p>
                            
                            <p class="letter-paragraph">
                                Se extiende la presente a solicitud de la parte interesada y para los efectos legales que estime convenientes, a los <strong>${diaEnLetras}</strong> días del mes de <strong>${monthText}</strong> de <strong>${anioEnLetras}</strong>, en la ciudad de ${empEmpresaDireccion}.
                            </p>
                        </div>

                        <div class="signature-container">
                            <div class="signature-block">
                                <br><br>
                                <div class="signature-line">
                                    ${firmNombre}
                                </div>
                                <div class="signature-sub">
                                    ${firmPuesto}<br>
                                    Contacto: <a href="mailto:${firmCorreo}" style="color: inherit; text-decoration: none;">${firmCorreo}</a><br>
                                    <strong>${empEmpresa}</strong>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();

                bootstrap.Modal.getInstance(document.getElementById('employeeDocsModal')).hide();
                Swal.fire({
                    title: '¡Constancia Generada!',
                    text: 'La constancia laboral se ha abierto en una pestaña lista para imprimir.',
                    icon: 'success',
                    confirmButtonText: 'Entendido'
                });
            });

            // ==========================================

            // ==========================================
            // REPORT & EXPORT HANDLERS BY COMPANY
            // ==========================================
            $('#reportCodEmpresa').on('change', function() {
                const val = $(this).val();
                if (val === 'ALL') {
                    employeesTable.column(10).search('').draw();
                } else {
                    employeesTable.column(10).search('^' + val + '$', true, false).draw();
                }
            });

            // 1. IMPRIMIR REPORTE
            $('#btnReportPrint').on('click', function() {
                const filteredData = employeesTable.rows({ filter: 'applied' }).data().toArray();
                if (filteredData.length === 0) {
                    Swal.fire({
                        title: 'Reporte Vacío',
                        text: 'No hay colaboradores visibles bajo el filtro seleccionado para imprimir.',
                        icon: 'info',
                        confirmButtonColor: '#0056b3',
                        background: 'var(--bg-surface)',
                        color: 'var(--text-primary)'
                    });
                    return;
                }

                const companyName = $('#reportCodEmpresa option:selected').text();
                const printWindow = window.open('', '_blank');
                
                let rowsHtml = '';
                filteredData.forEach(function(row) {
                    const salFormatted = '₡' + parseFloat(row.SalarioBase).toLocaleString('es-CR', { minimumFractionDigits: 2 });
                    const fIngreso = row.FechaIngreso ? row.FechaIngreso.split('-').reverse().join('/') : '';
                    
                    rowsHtml += `
                        <tr>
                            <td>${row.CodEmpleado}</td>
                            <td>${row.Identificacion}</td>
                            <td style="font-weight: 600;">${row.NombreCompleto}</td>
                            <td>${row.Empresa || companyName}</td>
                            <td>${row.Departamento}</td>
                            <td>${row.Puesto}</td>
                            <td>${row.Sucursal}</td>
                            <td align="right">${salFormatted}</td>
                            <td align="center">${fIngreso}</td>
                            <td align="center">${row.Estado}</td>
                        </tr>
                    `;
                });

                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <title>Reporte de Colaboradores - PlanillaCR</title>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; padding: 30px; line-height: 1.5; }
                            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0056b3; padding-bottom: 20px; }
                            .header h1 { margin: 0; font-size: 26px; color: #0056b3; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
                            .header p { margin: 8px 0 0 0; font-size: 14px; color: #555; }
                            .meta-info { display: flex; justify-content: space-between; margin-top: 15px; font-size: 13px; color: #666; font-weight: 500; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
                            th { background-color: #0056b3; color: #ffffff; font-weight: 600; border: 1px solid #ddd; padding: 10px 12px; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
                            td { border: 1px solid #ddd; padding: 10px 12px; color: #444; }
                            tr:nth-child(even) td { background-color: #f9f9f9; }
                            tr:hover td { background-color: #f1f5f9; }
                            .footer { margin-top: 50px; font-size: 11px; text-align: center; color: #888; border-top: 1px solid #e2e8f0; padding-top: 15px; }
                            @media print {
                                body { padding: 0; }
                                .header { border-bottom-color: #000; }
                                th { background-color: #f2f2f2 !important; color: #000 !important; }
                                button { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>Reporte de Colaboradores</h1>
                            <div class="meta-info">
                                <span><strong>Filtro de Compañía:</strong> ${companyName}</span>
                                <span><strong>Generado por:</strong> <?php echo htmlspecialchars($fullname); ?></span>
                                <span><strong>Fecha de Emisión:</strong> ${new Date().toLocaleDateString('es-CR')} ${new Date().toLocaleTimeString('es-CR')}</span>
                            </div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Cód</th>
                                    <th>Identificación</th>
                                    <th>Nombre Completo</th>
                                    <th>Compañía</th>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
                                    <th>Sucursal</th>
                                    <th align="right">Salario Base</th>
                                    <th align="center">F. Ingreso</th>
                                    <th align="center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                        <div class="footer">
                            PlanillaCR ERP Costa Rica - Sistema Avanzado de Recursos Humanos
                        </div>
                        <script>
                            window.onload = function() {
                                window.print();
                                setTimeout(function() { window.close(); }, 500);
                            };
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            });

            // 2. EXPORTAR CSV
            $('#btnReportCsv').on('click', function() {
                const filteredData = employeesTable.rows({ filter: 'applied' }).data().toArray();
                if (filteredData.length === 0) {
                    Swal.fire({
                        title: 'Reporte Vacío',
                        text: 'No hay colaboradores visibles bajo el filtro seleccionado para exportar.',
                        icon: 'info',
                        confirmButtonColor: '#0056b3',
                        background: 'var(--bg-surface)',
                        color: 'var(--text-primary)'
                    });
                    return;
                }

                let csvContent = "Cód,Identificación,Nombre Completo,Compañía,Departamento,Puesto,Sucursal,Salario Base,Fecha Ingreso,Estado\n";
                filteredData.forEach(function(row) {
                    const companyName = row.Empresa ? row.Empresa.replace(/"/g, '""') : '';
                    const depto = row.Departamento ? row.Departamento.replace(/"/g, '""') : '';
                    const puesto = row.Puesto ? row.Puesto.replace(/"/g, '""') : '';
                    const sucursal = row.Sucursal ? row.Sucursal.replace(/"/g, '""') : '';
                    const nombre = row.NombreCompleto ? row.NombreCompleto.replace(/"/g, '""') : '';
                    
                    csvContent += `${row.CodEmpleado},"${row.Identificacion}","${nombre}","${companyName}","${depto}","${puesto}","${sucursal}",${row.SalarioBase},"${row.FechaIngreso}","${row.Estado}"\n`;
                });

                const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `Reporte_Empleados_${new Date().toISOString().slice(0,10)}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                Swal.fire({
                    title: '¡CSV Generado!',
                    text: 'El archivo CSV de colaboradores ha sido descargado correctamente con codificación UTF-8 compatible con acentos.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                });
            });

            // 3. EXPORTAR EXCEL
            $('#btnReportExcel').on('click', function() {
                const filteredData = employeesTable.rows({ filter: 'applied' }).data().toArray();
                if (filteredData.length === 0) {
                    Swal.fire({
                        title: 'Reporte Vacío',
                        text: 'No hay colaboradores visibles bajo el filtro seleccionado para exportar.',
                        icon: 'info',
                        confirmButtonColor: '#0056b3',
                        background: 'var(--bg-surface)',
                        color: 'var(--text-primary)'
                    });
                    return;
                }

                const companySelected = $('#reportCodEmpresa option:selected').text();
                
                let tableHtml = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <meta charset="utf-8">
                        <!--[if gte mso 9]>
                        <xml>
                            <x:ExcelWorkbook>
                                <x:ExcelWorksheets>
                                    <x:ExcelWorksheet>
                                        <x:Name>Colaboradores</x:Name>
                                        <x:WorksheetOptions>
                                            <x:DisplayGridlines/>
                                        </x:WorksheetOptions>
                                    </x:ExcelWorksheet>
                                </x:ExcelWorksheets>
                            </x:ExcelWorkbook>
                        </xml>
                        <![endif]-->
                        <style>
                            th { background-color: #0056b3; color: #ffffff; font-weight: bold; border: 1px solid #dddddd; padding: 8px; }
                            td { border: 1px solid #dddddd; padding: 6px; }
                        </style>
                    </head>
                    <body>
                        <h2>Reporte de Colaboradores - PlanillaCR ERP</h2>
                        <p><b>Filtro Compañía:</b> ${companySelected} | <b>Generado por:</b> ${<?php echo json_encode($fullname); ?>} | <b>Fecha:</b> ${new Date().toLocaleDateString('es-CR')}</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Cód</th>
                                    <th>Identificación</th>
                                    <th>Nombre Completo</th>
                                    <th>Compañía</th>
                                    <th>Departamento</th>
                                    <th>Puesto</th>
                                    <th>Sucursal</th>
                                    <th>Salario Base</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                filteredData.forEach(function(row) {
                    const sal = parseFloat(row.SalarioBase);
                    tableHtml += `
                        <tr>
                            <td>${row.CodEmpleado}</td>
                            <td style="mso-number-format:'\\@';">${row.Identificacion}</td>
                            <td>${row.NombreCompleto}</td>
                            <td>${row.Empresa || ''}</td>
                            <td>${row.Departamento}</td>
                            <td>${row.Puesto}</td>
                            <td>${row.Sucursal}</td>
                            <td style="mso-number-format:'\\#\\,\\#\\#0\\.00';">${sal}</td>
                            <td>${row.FechaIngreso}</td>
                            <td>${row.Estado}</td>
                        </tr>
                    `;
                });

                tableHtml += `
                            </tbody>
                        </table>
                    </body>
                    </html>
                `;

                const blob = new Blob(["\uFEFF" + tableHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `Reporte_Empleados_${new Date().toISOString().slice(0,10)}.xls`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                Swal.fire({
                    title: '¡Excel Generado!',
                    text: 'El archivo Excel de colaboradores ha sido exportado correctamente con formato numérico y gridlines.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                });
            });

            // ==========================================
            // 9. Auxiliar de Alertas Premium
            // ==========================================
            function showAlert(msg, type, container) {
                const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
                $(container).html(`
                    <div class="auth-alert auth-alert-${type}">
                        <i class="fa-solid ${icon} fs-5 mt-1"></i>
                        <div>${msg}</div>
                    </div>
                `);
            }
        });
    </script>
</body>
</html>
