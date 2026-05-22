<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - PlanillaCR ERP</title>
    
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
            
            <!-- Encabezado -->
            <div class="auth-logo">
                <h2>Planilla<span>CR</span></h2>
                <p>Restablecimiento de Credenciales</p>
            </div>

            <!-- Contenedor dinámico de alertas -->
            <div id="alertContainer"></div>

            <div id="recoveryContent">
                <p class="text-secondary mb-4" style="font-size: 14px; text-align: center; line-height: 1.5;">
                    Ingrese el correo electrónico registrado en su cuenta. Le enviaremos un enlace seguro para restablecer su contraseña de inmediato.
                </p>

                <!-- Formulario de Recuperación -->
                <form id="recoveryForm" autocomplete="off">
                    
                    <!-- Input Correo Electrónico -->
                    <div class="form-group">
                        <label for="correo" class="form-label">Correo Electrónico Corporativo</label>
                        <div class="input-container">
                            <input type="email" id="correo" name="correo" class="auth-input" placeholder="ejemplo@planillacr.com" required>
                            <i class="fa-solid fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <!-- Botón de Envío Premium -->
                    <button type="submit" class="btn-auth mt-4" id="btnSubmit">
                        <span id="btnText">Enviar Enlace de Recuperación</span>
                        <i class="fa-solid fa-paper-plane" id="btnIcon"></i>
                        <div class="auth-spinner" id="btnSpinner"></div>
                    </button>
                </form>
            </div>

            <!-- Link de Retorno al Login -->
            <div class="text-center">
                <a href="login.php" class="auth-back-link"><i class="fa-solid fa-arrow-left"></i> Volver al Inicio de Sesión</a>
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
            // Procesamiento de Recuperación vía AJAX
            // ==========================================
            $('#recoveryForm').on('submit', function(e) {
                e.preventDefault();
                
                const correo = $('#correo').val().trim();

                // Limpiar alertas previas
                $('#alertContainer').empty();

                // Interfaz de carga
                $('#btnSubmit').prop('disabled', true);
                $('#btnText').text('Procesando solicitud...');
                $('#btnIcon').hide();
                $('#btnSpinner').show();

                $.ajax({
                    url: 'ajax/seguridad.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'recuperar',
                        correo: correo
                    },
                    success: function(response) {
                        if (response.success) {
                            // Alerta de éxito elegante
                            $('#alertContainer').html(`
                                <div class="auth-alert auth-alert-success">
                                    <i class="fa-solid fa-circle-check fs-5 mt-1"></i>
                                    <div>
                                        <strong>¡Enlace Enviado con Éxito!</strong><br>
                                        ${response.message}
                                    </div>
                                </div>
                            `);
                            
                            // Reemplazar contenido con un mensaje claro y detallado de éxito
                            $('#recoveryContent').html(`
                                <div class="text-center my-4 py-3">
                                    <i class="fa-solid fa-envelope-open-text text-success fs-1 mb-3"></i>
                                    <h5 class="fw-bold mb-2">Revise su bandeja de entrada</h5>
                                    <p class="text-secondary" style="font-size: 13px; line-height:1.6;">
                                        Hemos generado y registrado un token seguro de recuperación. En un entorno real, recibiría un correo en <strong>${correo}</strong> con un enlace para restablecer su clave.
                                    </p>
                                    
                                    <div class="p-3 my-3 rounded" style="background-color: var(--bg-surface-elevated); border: 1px solid var(--border-color); text-align: left;">
                                        <small class="text-muted d-block mb-1 font-monospace" style="font-size:10px; text-transform:uppercase;">Demostración de Enlace Seguro:</small>
                                        <a href="${response.demo_link}" class="font-monospace text-break" style="font-size:12px; font-weight:600; text-decoration: underline; color: var(--primary);">${response.demo_link}</a>
                                    </div>
                                    <p class="text-muted" style="font-size:11px;">(Haga clic en el enlace simulado arriba para proceder con la recuperación de la contraseña de prueba en esta fase de desarrollo).</p>
                                </div>
                            `);
                        } else {
                            // Alerta de error controlada (ej: correo no registrado)
                            $('#alertContainer').html(`
                                <div class="auth-alert auth-alert-danger">
                                    <i class="fa-solid fa-circle-exclamation fs-5 mt-1"></i>
                                    <div>
                                        <strong>No se pudo enviar</strong><br>
                                        ${response.message}
                                    </div>
                                </div>
                            `);
                            
                            restoreSubmitButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'Ocurrió un error inesperado. Por favor, vuelva a intentarlo.';
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

            function restoreSubmitButton() {
                $('#btnSubmit').prop('disabled', false);
                $('#btnText').text('Enviar Enlace de Recuperación');
                $('#btnSpinner').hide();
                $('#btnIcon').show();
            }
        });
    </script>
</body>
</html>
