<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * GESTIÓN Y CÁLCULO DE AGUINALDOS (AGUINALDO.PHP)
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
    <title>Cálculo de Aguinaldos - PlanillaCR ERP</title>
    
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
        
        /* Configuración de filtros */
        .filter-panel {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius-lg);
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px var(--shadow-main);
        }

        .breakdown-month-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13.5px;
        }

        .breakdown-month-row:last-child {
            border-bottom: none;
        }

        .breakdown-month-name {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .breakdown-month-value {
            font-family: monospace;
            font-weight: 700;
            color: var(--text-primary);
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
                <li class="sidebar-item active">
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
                    <h1>Cálculo de Aguinaldos</h1>
                    <p>Décimo Tercer Mes según el Código de Trabajo de Costa Rica (Periodo Diciembre - Noviembre)</p>
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

            <!-- Panel de Parámetros de Cálculo -->
            <section class="filter-panel">
                <form id="frmCalculoAguinaldo" class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label for="txtYear" class="form-label fw-semibold text-secondary" style="font-size: 13px;">Año del Periodo:</label>
                        <select class="form-select" id="txtYear" name="year" style="border-radius: var(--border-radius-md);">
                            <?php 
                            $currentYear = date('Y');
                            for ($y = $currentYear + 1; $y >= 2020; $y--) {
                                $selected = ($y == $currentYear) ? 'selected' : '';
                                echo "<option value='{$y}' {$selected}>{$y} (Dic ".($y-1)." - Nov {$y})</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" id="chkIncludeOpen" name="include_open" value="1">
                            <label class="form-check-label fw-semibold text-secondary ms-2" for="chkIncludeOpen" style="font-size: 13px; cursor: pointer;">
                                Incluir Planillas Abiertas (Proyección)
                            </label>
                        </div>
                    </div>

                    <div class="col-md-5 d-flex gap-2 justify-content-end align-items-center flex-wrap">
                        <button type="submit" class="btn btn-primary px-3 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnCalculate" style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);">
                            <i class="fa-solid fa-calculator"></i>
                            <span>Calcular</span>
                        </button>

                        <button type="button" class="btn btn-success px-3 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnSaveHistory" disabled style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Guardar</span>
                        </button>

                        <button type="button" class="btn btn-secondary px-3 py-2 d-flex align-items-center gap-2 fw-semibold" id="btnPrintAll" disabled style="border-radius: var(--border-radius-md); box-shadow: 0 4px 12px rgba(108, 117, 125, 0.25);">
                            <i class="fa-solid fa-print"></i>
                            <span>Imprimir Todos</span>
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle px-3 py-2 d-flex align-items-center gap-2 fw-semibold" type="button" id="btnExportBank" data-bs-toggle="dropdown" aria-expanded="false" disabled style="border-radius: var(--border-radius-md);">
                                <i class="fa-solid fa-file-export"></i>
                                <span>Exportar Banco</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btnExportBank" style="background-color: var(--bg-surface); border: 1px solid var(--border-light);">
                                <li><a class="dropdown-item fw-semibold text-secondary-emphasis py-2" href="#" onclick="exportAguinaldoToBank('BAC')"><i class="fa-solid fa-file-csv me-2 text-info"></i>BAC (CSV)</a></li>
                                <li><a class="dropdown-item fw-semibold text-secondary-emphasis py-2" href="#" onclick="exportAguinaldoToBank('BNCR')"><i class="fa-solid fa-file-lines me-2 text-success"></i>BNCR (TXT)</a></li>
                                <li><a class="dropdown-item fw-semibold text-secondary-emphasis py-2" href="#" onclick="exportAguinaldoToBank('BCR')"><i class="fa-solid fa-file-lines me-2 text-warning"></i>BCR (TXT)</a></li>
                            </ul>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Grilla Principal de Aguinaldos -->
            <section class="table-responsive">
                <table class="table table-hover w-100" id="tblAguinaldos">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 140px;">Identificación</th>
                            <th>Colaborador</th>
                            <th class="text-end" style="width: 180px;">Salario Acumulado</th>
                            <th class="text-end" style="width: 180px;">Aguinaldo Proporcional</th>
                            <th class="text-center" style="width: 120px;">Desglose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Carga dinámica -->
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <!-- MODAL DE DESGLOSE MENSUAL -->
    <div class="modal fade" id="modalBreakdown" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border-light); border-radius: var(--border-radius-lg);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="lblBreakdownTitle">Desglose de Salarios</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="themeModalClose" style="filter: var(--close-btn-filter);"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Historial mensual de ingresos reportados que afectan el cálculo del aguinaldo.</p>
                    
                    <div id="breakdownContainer" class="border rounded px-3 py-2" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
                        <!-- Inyección de meses -->
                    </div>

                    <div class="d-flex justify-content-between mt-3 pt-3 border-top border-secondary">
                        <span class="fw-bold text-secondary">Suma Acumulada:</span>
                        <span class="fw-bold text-primary font-monospace" id="lblTotalAcumulado">₡0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="fw-bold text-secondary">Aguinaldo Proporcional (1/12):</span>
                        <span class="fw-bold text-success font-monospace" id="lblMontoCalculado" style="font-size: 16px;">₡0.00</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: var(--border-radius-md);">Cerrar</button>
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
            const closeBtn = document.getElementById('themeModalClose');
            
            if (isLight) {
                body.classList.add('theme-light');
                body.setAttribute('data-bs-theme', 'light');
                if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
                if (closeBtn) closeBtn.style.filter = 'none';
            } else {
                body.classList.remove('theme-light');
                body.setAttribute('data-bs-theme', 'dark');
                if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
                if (closeBtn) closeBtn.style.filter = 'invert(1) grayscale(1) brightness(2)';
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

        // ==========================================
        // Lógica de Negocio y Cargas de Datos
        // ==========================================
        let calculatedData = [];
        let tableAguinaldos;

        $(document).ready(function() {
            // Inicializar DataTable vacío
            tableAguinaldos = $('#tblAguinaldos').DataTable({
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                columnDefs: [
                    { className: 'text-center', targets: [0, 4] },
                    { className: 'text-end font-mono fw-semibold', targets: [2, 3] }
                ]
            });

            // Acción del formulario de Cálculo
            $('#frmCalculoAguinaldo').submit(function(e) {
                e.preventDefault();

                let year = $('#txtYear').val();
                let includeOpen = $('#chkIncludeOpen').is(':checked') ? 1 : 0;

                Swal.fire({
                    title: 'Procesando Cálculo',
                    html: `Analizando planillas y devengos del periodo fiscal <strong>${year - 1} - ${year}</strong>...`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'ajax/aguinaldo.php?action=calculate',
                    type: 'POST',
                    data: { year: year, include_open: includeOpen },
                    dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        if (res.success) {
                            calculatedData = res.data;
                            renderTable(calculatedData);
                            
                            if (calculatedData.length > 0) {
                                $('#btnSaveHistory').prop('disabled', false);
                                $('#btnPrintAll').prop('disabled', false);
                                $('#btnExportBank').prop('disabled', false);
                                Swal.fire({
                                    title: '¡Cálculo Exitoso!',
                                    text: `Se calcularon aguinaldos para ${calculatedData.length} colaboradores en el periodo ${res.periodo}.`,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    background: 'var(--bg-surface)',
                                    color: 'var(--text-primary)'
                                });
                            } else {
                                $('#btnSaveHistory').prop('disabled', true);
                                $('#btnPrintAll').prop('disabled', true);
                                $('#btnExportBank').prop('disabled', true);
                                Swal.fire({
                                    title: 'Sin datos',
                                    text: 'No se encontraron planillas aplicadas en el periodo seleccionado.',
                                    icon: 'info',
                                    background: 'var(--bg-surface)',
                                    color: 'var(--text-primary)'
                                });
                            }
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(err) {
                        Swal.close();
                        Swal.fire('Error de Conexión', 'Fallo al procesar la solicitud AJAX.', 'error');
                    }
                });
            });

            // Guardar Lote de Aguinaldos en el Histórico
            $('#btnSaveHistory').click(function() {
                if (calculatedData.length === 0) return;

                let year = $('#txtYear').val();

                Swal.fire({
                    title: '¿Confirmar Cierre y Guardado?',
                    html: `Se guardará de forma transaccional el histórico de aguinaldos para el periodo <strong>${year - 1} - ${year}</strong>.<br><br><span class='text-warning small'><i class="fa-solid fa-triangle-exclamation"></i> Esto sobreescribirá cualquier cálculo guardado previamente para este periodo.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, Guardar Histórico',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#198754',
                    background: 'var(--bg-surface)',
                    color: 'var(--text-primary)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Guardando...',
                            html: 'Registrando acumulados en SQL Server...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: 'ajax/aguinaldo.php?action=save',
                            type: 'POST',
                            data: {
                                year: year,
                                payload: JSON.stringify(calculatedData)
                            },
                            dataType: 'json',
                            success: function(res) {
                                Swal.close();
                                if (res.success) {
                                    Swal.fire({
                                        title: '¡Guardado Exitoso!',
                                        text: res.message,
                                        icon: 'success',
                                        confirmButtonColor: 'var(--primary)',
                                        background: 'var(--bg-surface)',
                                        color: 'var(--text-primary)'
                                    });
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            },
                            error: function(err) {
                                Swal.close();
                                Swal.fire('Error de Guardado', 'Fallo al conectar con la base de datos.', 'error');
                            }
                        });
                    }
                });
            });

            // Botón de Impresión Masiva Continua
            $('#btnPrintAll').click(function() {
                if (calculatedData.length === 0) return;
                let year = $('#txtYear').val();
                let includeOpen = $('#chkIncludeOpen').is(':checked') ? 1 : 0;
                let url = `imprimir_aguinaldos.php?year=${year}&include_open=${includeOpen}`;
                window.open(url, '_blank');
            });
        });

        // Renderizar DataTables con los resultados calculados
        function renderTable(data) {
            tableAguinaldos.clear();

            data.forEach(function(row, idx) {
                let btnActions = `
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-outline-info btn-sm px-2 py-1 d-inline-flex align-items-center gap-1" onclick="viewBreakdown(${idx})" style="border-radius: 4px; font-size:11.5px;" title="Ver desglose mensual de salarios">
                            <i class="fa-solid fa-eye"></i>
                            <span>Desglose</span>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm px-2 py-1 d-inline-flex align-items-center" onclick="printIndividualAguinaldo(${idx})" style="border-radius: 4px; font-size:11.5px;" title="Imprimir Comprobante de Aguinaldo">
                            <i class="fa-solid fa-print"></i>
                        </button>
                    </div>
                `;

                tableAguinaldos.row.add([
                    `<span class="badge bg-secondary-subtle text-secondary-emphasis font-monospace px-2 py-1">${row.Identificacion}</span>`,
                    `<span class="fw-semibold">${row.Colaborador}</span>`,
                    `₡${row.TotalAcumulado.toLocaleString('es-CR', { minimumFractionDigits: 2 })}`,
                    `<span class="text-success fw-bold">₡${row.MontoAguinaldo.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span>`,
                    btnActions
                ]);
            });

            tableAguinaldos.draw();
        }

        // Mostrar Modal de Desglose Mensual
        function viewBreakdown(idx) {
            let row = calculatedData[idx];
            if (!row) return;

            $('#lblBreakdownTitle').html(`Desglose de ${row.Colaborador}`);
            
            let monthsNames = {
                12: 'Diciembre',
                1: 'Enero',
                2: 'Febrero',
                3: 'Marzo',
                4: 'Abril',
                5: 'Mayo',
                6: 'Junio',
                7: 'Julio',
                8: 'Agosto',
                9: 'Septiembre',
                10: 'Octubre',
                11: 'Noviembre'
            };

            let monthsOrder = [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
            let container = $('#breakdownContainer');
            container.empty();

            monthsOrder.forEach(function(m) {
                let val = parseFloat(row.Breakdown[m]);
                let colorClass = val > 0 ? '' : 'opacity-50';
                
                container.append(`
                    <div class="breakdown-month-row ${colorClass}">
                        <span class="breakdown-month-name">${monthsNames[m]}</span>
                        <span class="breakdown-month-value">₡${val.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span>
                    </div>
                `);
            });

            $('#lblTotalAcumulado').text('₡' + row.TotalAcumulado.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
            $('#lblMontoCalculado').text('₡' + row.MontoAguinaldo.toLocaleString('es-CR', { minimumFractionDigits: 2 }));

            $('#modalBreakdown').modal('show');
        }

        // Imprimir Comprobante de Aguinaldo Individual
        function printIndividualAguinaldo(idx) {
            let row = calculatedData[idx];
            if (!row) return;
            let year = $('#txtYear').val();
            let includeOpen = $('#chkIncludeOpen').is(':checked') ? 1 : 0;
            let url = `imprimir_aguinaldos.php?year=${year}&include_open=${includeOpen}&emp=${row.CodEmpleado}`;
            window.open(url, '_blank');
        }

        // Exportar a Bancos (BAC, BNCR, BCR) con SweetAlert y Doble Guardado
        function exportAguinaldoToBank(banco) {
            if (calculatedData.length === 0) return;
            let year = $('#txtYear').val();
            let includeOpen = $('#chkIncludeOpen').is(':checked') ? 1 : 0;

            Swal.fire({
                title: `Exportación ${banco}`,
                text: 'Indique la referencia para la transferencia bancaria de Aguinaldos:',
                input: 'text',
                inputValue: `AGUINALDO ${year}`,
                showCancelButton: true,
                confirmButtonText: 'Exportar Archivo',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0056b3',
                background: 'var(--bg-surface)',
                color: 'var(--text-primary)',
                inputValidator: (value) => {
                    if (!value) {
                        return '¡Debe ingresar una referencia para continuar!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    let ref = result.value;
                    let url = `ajax/aguinaldo.php?action=export_bank&year=${year}&include_open=${includeOpen}&banco=${banco}&referencia=${encodeURIComponent(ref)}`;

                    // Iniciar descarga
                    window.location.href = url;

                    Swal.fire({
                        title: '¡Generación Exitosa!',
                        html: `El archivo para el banco <strong>${banco}</strong> ha sido descargado.<br><br><span class="text-success fw-semibold"><i class="fa-solid fa-desktop"></i> ¡También se ha guardado una copia directamente en su Escritorio listo para cargar!</span>`,
                        icon: 'success',
                        confirmButtonColor: '#0056b3',
                        background: 'var(--bg-surface)',
                        color: 'var(--text-primary)'
                    });
                }
            });
        }
    </script>
</body>
</html>
