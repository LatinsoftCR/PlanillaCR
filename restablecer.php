<?php
/**
 * SISTEMA ERP PLANILLAS COSTA RICA
 * PANTALLA DE RESTABLECIMIENTO DE CONTRASEÑA SEGURO
 */
require_once 'config/conexion.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$token_valido = false;
$usuario_info = null;
$error_mensaje = '';

if (empty($token)) {
    $error_mensaje = 'La URL de recuperación no es válida. Falta el parámetro de seguridad (token).';
} else {
    try {
        // Ejecutamos el Stored Procedure para validar el token
        $stmt = $pdo->prepare("EXEC sp_UsuarioValidarToken @Token = :token");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        
        if ($row) {
            $token_valido = true;
            $usuario_info = $row;
        } else {
            $error_mensaje = 'El enlace de recuperación ha expirado, ya ha sido utilizado o no es válido.';
        }
    } catch (PDOException $e) {
        $error_mensaje = 'Fallo de infraestructura: No se pudo verificar la validez del token de seguridad en SQL Server. Detalle: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - PlanillaCR ERP</title>
    
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
                <p>Establecer Nueva Contraseña</p>
            </div>

            <!-- Contenedor dinámico de alertas -->
            <div id="alertContainer"></div>

            <?php if ($token_valido): ?>
                <p class="text-secondary mb-4" style="font-size: 13px; text-align: center;">
                    Restableciendo contraseña para el usuario: <strong class="text-light font-monospace"><?php echo htmlspecialchars($usuario_info['Username']); ?></strong>
                </p>

                <!-- Formulario de Cambio de Clave -->
                <form id="resetForm" autocomplete="off">
                    <!-- Token Oculto -->
                    <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <!-- Input Nueva Contraseña -->
                    <div class="form-group mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <div class="input-container">
                            <input type="password" id="new_password" name="new_password" class="auth-input" placeholder="Min. 8 caracteres" required style="padding-right: 44px;">
                            <i class="fa-solid fa-key input-icon"></i>
                            <span class="password-toggle-icon toggle-password" data-target="#new_password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); transition: color var(--transition-speed) ease; z-index: 5;">
                                <i class="fa-solid fa-eye-slash"></i>
                            </span>
                        </div>
                        
                        <!-- Medidor de fuerza dinámico -->
                        <div class="password-strength-meter">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <span class="password-strength-text" id="strengthText">Seguridad: No evaluada</span>
                    </div>

                    <!-- Input Confirmar Contraseña -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <div class="input-container">
                            <input type="password" id="confirm_password" name="confirm_password" class="auth-input" placeholder="Repita la contraseña" required style="padding-right: 44px;">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <span class="password-toggle-icon toggle-password" data-target="#confirm_password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); transition: color var(--transition-speed) ease; z-index: 5;">
                                <i class="fa-solid fa-eye-slash"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Botón de Envío Premium -->
                    <button type="submit" class="btn-auth mt-4" id="btnSubmit">
                        <span id="btnText">Restablecer y Guardar</span>
                        <i class="fa-solid fa-circle-check" id="btnIcon"></i>
                        <div class="auth-spinner" id="btnSpinner"></div>
                    </button>
                </form>
            <?php else: ?>
                <!-- Panel de Error cuando el Token es Inválido -->
                <div class="text-center my-4 py-2">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold text-danger mb-2">Enlace de Seguridad Inválido</h5>
                    <p class="text-secondary" style="font-size: 13px; line-height: 1.6;">
                        <?php echo htmlspecialchars($error_mensaje); ?>
                    </p>
                    <a href="recuperar.php" class="btn btn-outline-primary btn-sm mt-3 px-4 py-2" style="border-radius: var(--border-radius-md); font-weight:600;">
                        Solicitar Nuevo Enlace
                    </a>
                </div>
            <?php endif; ?>

            <!-- Retorno al Login -->
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
            // Analizador Dinámico de Fuerza de Clave
            // ==========================================
            $('#new_password').on('input', function() {
                const pass = $(this).val();
                let score = 0;
                
                if (pass.length === 0) {
                    updateStrengthBar(0, 'No evaluada', '#22222a');
                    return;
                }
                
                if (pass.length >= 8) score += 20;
                if (pass.match(/[a-z]/)) score += 20;
                if (pass.match(/[A-Z]/)) score += 20;
                if (pass.match(/[0-9]/)) score += 20;
                if (pass.match(/[^a-zA-Z0-9]/)) score += 20;
                
                let text = 'Muy Débil';
                let color = '#ef4444'; // Rojo
                
                if (score >= 40 && score < 60) {
                    text = 'Débil';
                    color = '#f59e0b'; // Naranja/Ambar
                } else if (score >= 60 && score < 80) {
                    text = 'Mediana';
                    color = '#3b82f6'; // Azul
                } else if (score >= 80) {
                    text = 'Excelente y Segura';
                    color = '#10b981'; // Verde Esmeralda
                }
                
                updateStrengthBar(score, text, color);
            });

            function updateStrengthBar(pct, txt, col) {
                $('#strengthBar').css({
                    'width': pct + '%',
                    'background-color': col
                });
                $('#strengthText').text('Seguridad: ' + txt).css('color', col);
            }

            // Alternar visibilidad de contraseña (Ojo interactivo genérico)
            $('.toggle-password').on('click', function() {
                const targetSelector = $(this).attr('data-target');
                const passwordField = $(targetSelector);
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                
                const icon = $(this).find('i');
                if (type === 'text') {
                    icon.removeClass('fa-eye-slash').addClass('fa-eye').css('color', 'var(--primary)');
                } else {
                    icon.removeClass('fa-eye').addClass('fa-eye-slash').css('color', 'var(--text-muted)');
                }
            });

            // ==========================================
            // Procesamiento de Restablecimiento vía AJAX
            // ==========================================
            $('#resetForm').on('submit', function(e) {
                e.preventDefault();
                
                const token = $('#token').val();
                const newPassword = $('#new_password').val();
                const confirmPassword = $('#confirm_password').val();

                // Limpiar alertas previas
                $('#alertContainer').empty();

                // Validaciones iniciales en frontend
                if (newPassword.length < 8) {
                    showAlert('La contraseña debe tener un mínimo de 8 caracteres de longitud.', 'danger');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showAlert('Las contraseñas ingresadas no coinciden. Verifique por favor.', 'danger');
                    return;
                }

                // Interfaz de carga
                $('#btnSubmit').prop('disabled', true);
                $('#btnText').text('Actualizando clave...');
                $('#btnIcon').hide();
                $('#btnSpinner').show();

                $.ajax({
                    url: 'ajax/seguridad.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'restablecer',
                        token: token,
                        password: newPassword
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert(`
                                <strong>¡Contraseña Restablecida!</strong><br>
                                Su nueva contraseña ha sido cifrada y guardada. Redireccionando al portal de acceso...
                            `, 'success');
                            
                            // Redireccionar al login tras 2.5 segundos
                            setTimeout(function() {
                                window.location.href = 'login.php';
                            }, 2500);
                        } else {
                            showAlert(`
                                <strong>Error al restablecer</strong><br>
                                ${response.message}
                            `, 'danger');
                            
                            restoreSubmitButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'Error al comunicarse con el servidor local.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        
                        showAlert(`
                            <strong>Fallo del Sistema</strong><br>
                            ${errorMsg}
                        `, 'danger');
                        
                        restoreSubmitButton();
                    }
                });
            });

            function showAlert(msg, type) {
                const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
                $('#alertContainer').html(`
                    <div class="auth-alert auth-alert-${type}">
                        <i class="fa-solid ${icon} fs-5 mt-1"></i>
                        <div>${msg}</div>
                    </div>
                `);
            }

            function restoreSubmitButton() {
                $('#btnSubmit').prop('disabled', false);
                $('#btnText').text('Restablecer y Guardar');
                $('#btnSpinner').hide();
                $('#btnIcon').show();
            }
        });
    </script>
</body>
</html>
