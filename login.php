<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - PlanillaCR ERP</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Dark/Light Executive CSS -->
    <link href="assets/css/theme.css" rel="stylesheet">
</head>
<body>

    <!-- Botón flotante para alternar entre Tema Claro y Oscuro -->
    <button class="theme-toggle-btn" id="themeToggle" title="Cambiar Tema Visual">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
    </button>

    <div class="auth-wrapper">
        <div class="auth-card">
            
            <!-- Encabezado de Identificación -->
            <div class="auth-logo">
                <h2>Planilla<span>CR</span></h2>
                <p>ERP de Planillas & Recursos Humanos</p>
            </div>

            <!-- Contenedor dinámico de alertas de error o éxito -->
            <div id="alertContainer"></div>

            <!-- Formulario de Acceso -->
            <form id="loginForm" autocomplete="off">
                
                <!-- Input Usuario / Correo -->
                <div class="form-group">
                    <label for="username" class="form-label">Usuario o Correo Electrónico</label>
                    <div class="input-container">
                        <input type="text" id="username" name="username" class="auth-input" placeholder="ej: admin o admin@planillacr.com" required>
                        <i class="fa-solid fa-user input-icon"></i>
                    </div>
                </div>

                <!-- Input Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña de Acceso</label>
                    <div class="input-container">
                        <input type="password" id="password" name="password" class="auth-input" placeholder="••••••••" required style="padding-right: 44px;">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <span class="password-toggle-icon" id="togglePassword" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); transition: color var(--transition-speed) ease; z-index: 5;">
                            <i class="fa-solid fa-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <!-- Botón de Envío Premium -->
                <button type="submit" class="btn-auth mt-4" id="btnSubmit">
                    <span id="btnText">Iniciar Sesión</span>
                    <i class="fa-solid fa-right-to-bracket" id="btnIcon"></i>
                    <div class="auth-spinner" id="btnSpinner"></div>
                </button>
            </form>

            <!-- Links de Navegación Rápida -->
            <div class="auth-footer-links">
                <a href="recuperar.php" class="text-secondary"><i class="fa-solid fa-key me-1"></i> ¿Olvidó su contraseña?</a>
                <span class="text-muted" style="font-size: 11px;">v1.0.0</span>
            </div>

        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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
            // Procesamiento de Login vía AJAX
            // ==========================================
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const username = $('#username').val().trim();
                const password = $('#password').val();

                // Limpiar alertas previas
                $('#alertContainer').empty();

                // Interfaz de carga
                $('#btnSubmit').prop('disabled', true);
                $('#btnText').text('Autenticando...');
                $('#btnIcon').hide();
                $('#btnSpinner').show();

                $.ajax({
                    url: 'ajax/seguridad.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'login',
                        username: username,
                        password: password
                    },
                    success: function(response) {
                        if (response.success) {
                            // Alerta de éxito elegante
                            $('#alertContainer').html(`
                                <div class="auth-alert auth-alert-success">
                                    <i class="fa-solid fa-circle-check fs-5 mt-1"></i>
                                    <div>
                                        <strong>¡Acceso Autorizado!</strong><br>
                                        ${response.message}
                                    </div>
                                </div>
                            `);
                            
                            // Redireccionar al dashboard tras 1.5 segundos
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 1500);
                        } else {
                            // Alerta de error controlada
                            $('#alertContainer').html(`
                                <div class="auth-alert auth-alert-danger">
                                    <i class="fa-solid fa-circle-exclamation fs-5 mt-1"></i>
                                    <div>
                                        <strong>Error de Acceso</strong><br>
                                        ${response.message}
                                    </div>
                                </div>
                            `);
                            
                            // Restaurar botón de envío
                            restoreSubmitButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        // Error de conexión o de servidor
                        let errorMsg = 'No se pudo conectar con el servidor. Inténtelo de nuevo.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        
                        $('#alertContainer').html(`
                            <div class="auth-alert auth-alert-danger">
                                <i class="fa-solid fa-triangle-exclamation fs-5 mt-1"></i>
                                <div>
                                    <strong>Fallo del Sistema</strong><br>
                                    ${errorMsg}
                                </div>
                            </div>
                        `);
                        
                        restoreSubmitButton();
                    }
                });
            });

            // Alternar visibilidad de la contraseña (Ojo interactivo)
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                
                const icon = $(this).find('i');
                if (type === 'text') {
                    icon.removeClass('fa-eye-slash').addClass('fa-eye').css('color', 'var(--primary)');
                } else {
                    icon.removeClass('fa-eye').addClass('fa-eye-slash').css('color', 'var(--text-muted)');
                }
            });

            function restoreSubmitButton() {
                $('#btnSubmit').prop('disabled', false);
                $('#btnText').text('Iniciar Sesión');
                $('#btnSpinner').hide();
                $('#btnIcon').show();
            }
        });
    </script>
</body>
</html>
