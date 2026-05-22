<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * DASHBOARD CORPORATIVO PRINCIPAL (INDEX.PHP)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control fuerte de Acceso y Seguridad: Si no hay sesión, redirecciona al login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];

// Obtener la inicial para el avatar rápido
$avatar_initial = strtoupper(substr($fullname, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Principal - PlanillaCR ERP</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Dark/Light Executive CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
</head>
<body class="dashboard-body">

    <div class="dashboard-layout">
        
        <!-- ==========================================
             BARRA LATERAL (SIDEBAR) FIJA
             ========================================== -->
        <aside class="sidebar">
            <!-- Logo Corporativo -->
            <div class="sidebar-header">
                <h3>Planilla<span>CR</span></h3>
            </div>
            
            <!-- Menú de Navegación Principal -->
            <ul class="sidebar-menu">
                <li class="sidebar-item active">
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
                <li class="sidebar-item">
                    <a href="configuracion.php">
                        <i class="fa-solid fa-gears"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>
            
            <!-- Footer del Sidebar: Perfil del Usuario -->
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
                
                <!-- Botón Cerrar Sesión Seguro -->
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
            
            <!-- Header superior del Dashboard -->
            <header class="dashboard-header">
                <div class="welcome-msg">
                    <h1>¡Bienvenido al Sistema, <?php echo htmlspecialchars(explode(' ', $fullname)[0]); ?>!</h1>
                    <p>ERP de Planillas y Recursos Humanos de Costa Rica</p>
                </div>
                
                <div class="header-actions">
                    <!-- Fecha Actual Dinámica en Español -->
                    <div class="date-badge">
                        <i class="fa-regular fa-calendar"></i>
                        <span id="currentDate"><?php echo date('d/m/Y'); ?></span>
                    </div>
                    
                    <!-- Botón para alternar el tema visual en caliente -->
                    <button class="theme-toggle-btn" id="themeToggle" style="position: static;" title="Cambiar Tema Visual">
                        <i class="fa-solid fa-moon" id="themeIcon"></i>
                    </button>
                </div>
            </header>

            <!-- Grid de KPIs Analíticos Rápidos -->
            <section class="kpi-grid">
                <!-- KPI 1: Empleados Registrados -->
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Empleados Activos</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <div class="kpi-value">1</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-circle-check"></i> Estable</span>
                        <span class="kpi-desc">Administrador Inicial</span>
                    </div>
                </div>
                
                <!-- KPI 2: Departamentos Activos -->
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Departamentos</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-building"></i>
                        </div>
                    </div>
                    <div class="kpi-value">5</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-circle-check"></i> Activos</span>
                        <span class="kpi-desc">Estructura Base</span>
                    </div>
                </div>
                
                <!-- KPI 3: Moneda y Parámetros -->
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Región Legal</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                    </div>
                    <div class="kpi-value">CRC (₡)</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-neutral"><i class="fa-solid fa-clock"></i> 2026 Vigente</span>
                        <span class="kpi-desc">Parámetros CCSS / Renta</span>
                    </div>
                </div>
                
                <!-- KPI 4: Estado de Auditoría SQL Server -->
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-title">Auditoría / Bitácora</span>
                        <div class="kpi-icon-wrapper">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    </div>
                    <div class="kpi-value">Activa</div>
                    <div class="kpi-footer">
                        <span class="kpi-trend-up"><i class="fa-solid fa-database"></i> SQL Server</span>
                        <span class="kpi-desc">Registro Transaccional</span>
                    </div>
                </div>
            </section>

            <!-- Banner Informativo Premium de Inicio de Fase 2 -->
            <section class="welcome-banner">
                <div class="banner-content">
                    <span class="banner-badge">Siguiente Módulo</span>
                    <h2>Fase 2: Gestión de Recursos Humanos (RRHH)</h2>
                    <p>
                        Estamos listos para iniciar con la administración del personal. Diseñaremos un CRUD premium para empleados, puestos, departamentos y horarios. Las altas, modificaciones y bajas consumirán Stored Procedures fuertemente tipados en SQL Server, manteniendo el registro automático de auditoría en la bitácora transaccional.
                    </p>
                    <a href="empleados.php" class="btn-banner-action">
                        <span>Comenzar Gestión de Personal</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="banner-graphic">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
            </section>

        </main>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

            $('#themeToggle').on('click', function() {
                const currentTheme = localStorage.getItem('theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });

            // ==========================================
            // Fecha en Formato Amigable en Español
            // ==========================================
            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const hoy = new Date();
            const fechaEsp = hoy.toLocaleDateString('es-CR', opciones);
            // Capitalizar la primera letra
            const fechaCap = fechaEsp.charAt(0).toUpperCase() + fechaEsp.slice(1);
            $('#currentDate').text(fechaCap);
        });
    </script>
</body>
</html>
