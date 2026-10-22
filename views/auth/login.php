<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Control de Ingresos SENA CTA Cartago</title>
    <link rel="icon" type="image/svg+xml" href="public/img/sena-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="public/css/styles.css" rel="stylesheet">
    <style>
        /* ─── FONDO DIFUMINADO CTA CARTAGO & GLASSMORPHISM ─── */
        body.shadcn-auth-layout {
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background-color: #0f172a;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Capa de imagen difuminada (blur & scale) */
        .auth-bg-blur {
            position: fixed;
            top: -24px;
            left: -24px;
            right: -24px;
            bottom: -24px;
            background-image: url('public/uploads/Imagenes/ctaCartago.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(8px) brightness(0.65) saturate(1.15);
            transform: scale(1.05);
            z-index: 0;
            pointer-events: none;
        }

        /* Capa de tinte degradado corporativo SENA */
        .auth-overlay-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(15, 23, 42, 0.4) 0%, rgba(6, 78, 59, 0.72) 100%);
            z-index: 1;
            pointer-events: none;
        }

        /* Tarjeta de login flotante con efecto cristal */
        .auth-glass-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-xl, 16px);
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.35);
            animation: authFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes authFadeIn {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .auth-logo-badge {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #39A900 0%, #2e8b00 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 10px 25px rgba(57, 169, 0, 0.35);
            padding: 13px;
            border: 2px solid rgba(255, 255, 255, 0.25);
            transition: transform 0.3s ease;
        }
        .auth-logo-badge:hover {
            transform: scale(1.05);
        }
        .auth-logo-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .auth-input-group {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .auth-toggle-pwd {
            position: absolute;
            right: 12px;
            top: 36px;
            background: transparent;
            border: none;
            color: var(--muted-foreground);
            cursor: pointer;
            font-size: 1.125rem;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-toggle-pwd:hover {
            color: var(--foreground);
        }
    </style>
</head>
<body class="shadcn-auth-layout">

<!-- Fondo Difuminado de CTA Cartago -->
<div class="auth-bg-blur"></div>
<div class="auth-overlay-gradient"></div>

<!-- Tarjeta de Login Flotante -->
<div class="auth-glass-card">
    <div class="auth-header" style="text-align:center; margin-bottom:1.75rem;">
        <div class="auth-logo-badge">
            <img src="public/img/sena-logo-white.svg" alt="SENA">
        </div>
        <h1 class="auth-title" style="font-size:1.45rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.02em;">
            Sistema de Ingreso SENA
        </h1>
        <div class="auth-subtitle" style="font-size:0.875rem; color:#475569; margin-top:0.375rem; font-weight:500;">
            Centro de Tecnologías Agroindustriales — Cartago
        </div>
    </div>

    <!-- Muestra mensaje de éxito de sesión si existe (ej. contraseña actualizada) -->
    <?php if (!empty($_SESSION['mensaje'])): ?>
    <div style="background-color:rgba(16,185,129,0.1); color:#047857; padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(16,185,129,0.3); display:flex; align-items:center; gap:0.625rem; font-weight:500;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <span><?= htmlspecialchars($_SESSION['mensaje']['texto'] ?? '') ?></span>
    </div>
    <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <!-- Muestra mensaje de error si las credenciales fallan -->
    <?php if (!empty($error)): ?>
    <div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.25); display:flex; align-items:center; gap:0.625rem; font-weight:500;">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- Formulario de inicio de sesión -->
    <form method="POST" action="index.php?action=login">
        <?= Csrf::campoHtml() ?>
        <div class="auth-input-group">
            <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                Usuario, correo o identificación
            </label>
            <div style="position:relative;">
                <input type="text" name="correo" class="shadcn-input" 
                       placeholder="ej. admin@sena.edu.co o 123456" 
                       required autofocus 
                       value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                       style="padding-left:2.5rem; background:rgba(255,255,255,0.95); border:1px solid #cbd5e1;">
                <i class="bi bi-person" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.125rem;"></i>
            </div>
        </div>

        <div class="auth-input-group" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
                <label style="font-size:0.8125rem; font-weight:600; color:#1e293b; margin:0;">
                    Contraseña
                </label>
                <a href="javascript:void(0)" onclick="abrirModalRecuperar()" style="font-size:0.75rem; color:#059669; text-decoration:none; font-weight:600; cursor:pointer;">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
            <div style="position:relative;">
                <input type="password" id="loginPassword" name="password" class="shadcn-input" 
                       placeholder="••••••••" 
                       required
                       style="padding-left:2.5rem; padding-right:2.5rem; background:rgba(255,255,255,0.95); border:1px solid #cbd5e1;">
                <i class="bi bi-lock" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.125rem;"></i>
                <button type="button" class="auth-toggle-pwd" onclick="togglePasswordVisibilidad()" title="Mostrar/ocultar contraseña" style="top:50%; transform:translateY(-50%);">
                    <i id="pwdToggleIcon" class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-shadcn btn-shadcn-primary" style="width:100%; padding:0.75rem; font-size:0.9375rem; font-weight:600; background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,0.25);">
            <i class="bi bi-box-arrow-in-right me-1"></i>
            <span>Iniciar Sesión</span>
        </button>

        <div style="margin-top:1.25rem; text-align:center;">
            <a href="javascript:void(0)" onclick="abrirModalRecuperar()" style="font-size:0.8125rem; color:#047857; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 0.875rem; border-radius:8px; background:rgba(5,150,105,0.08); border:1px solid rgba(5,150,105,0.2); cursor:pointer;">
                <i class="bi bi-key-fill"></i>
                <span>¿Olvidaste tu contraseña? Recupérala aquí</span>
            </a> 
        </div>
    </form>

    <div style="margin-top:1.75rem; text-align:center; font-size:0.75rem; color:#64748b; border-top:1px solid rgba(0,0,0,0.06); padding-top:1.25rem;">
        <i class="bi bi-shield-check me-1" style="color:#059669;"></i>
        SENA — CTA Cartago • Control de Ingreso y Formación
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════════════ -->
<!--  MODAL INTERACTIVO DE RECUPERACIÓN DE CONTRASEÑA (SIN REDIRIGIR)    -->
<!-- ═════════════════════════════════════════════════════════════════════ -->
<div class="auth-modal-overlay" id="modalRecuperarOverlay" onclick="cerrarModalFondo(event)">
    <div class="auth-modal-card" id="modalRecuperarCard">
        <!-- Botón cerrar (X) -->
        <button type="button" class="auth-modal-close-btn" onclick="cerrarModalRecuperar()" title="Cerrar">&times;</button>

        <div style="text-align:center; margin-bottom:1.25rem;">
            <div style="width:52px; height:52px; background:linear-gradient(135deg, #39A900 0%, #2e8b00 100%); border-radius:14px; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem; padding:10px; box-shadow:0 6px 16px rgba(57,169,0,0.3);">
                <img src="public/img/sena-logo-white.svg" alt="SENA" style="width:100%; height:100%; object-fit:contain;">
            </div>
            <h3 id="modalTitulo" style="font-size:1.25rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.02em;">
                Recuperar Contraseña
            </h3>
            <div id="modalSubtitulo" style="font-size:0.8125rem; color:#64748b; margin-top:0.25rem;">
                Te enviaremos un código y enlace seguro a tu correo
            </div>
        </div>

        <!-- Contenedor dinámico de alertas dentro del modal -->
        <div id="modalAlerta" style="display:none; padding:0.75rem 1rem; border-radius:8px; font-size:0.8125rem; font-weight:500; margin-bottom:1rem; line-height:1.45;"></div>

        <!-- ── PASO 1: Ingresar correo/documento para enviar código ── -->
        <div id="paso1Solicitud">
            <form id="formPaso1" onsubmit="enviarSolicitudRecuperacion(event)">
                <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                <div class="auth-input-group">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                        Correo institucional o documento
                    </label>
                    <div style="position:relative;">
                        <input type="text" id="recuperarIdentificador" name="identificador" class="shadcn-input" 
                               placeholder="ej. admin@sena.edu.co o 1114..." 
                               required
                               style="padding-left:2.5rem; background:#f8fafc; border:1px solid #cbd5e1;">
                        <i class="bi bi-person-badge" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.1rem;"></i>
                    </div>
                </div>

                <button type="submit" id="btnEnviarPaso1" class="btn-shadcn btn-shadcn-primary" style="width:100%; padding:0.75rem; font-size:0.875rem; font-weight:600; background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,0.25);">
                    <i class="bi bi-send me-1"></i>
                    <span id="btnEnviarPaso1Texto">Enviar Código a mi Correo</span>
                </button>
            </form>

            <div style="text-align:center; margin-top:1rem;">
                <a href="javascript:void(0)" onclick="mostrarPaso2(null, '')" style="font-size:0.75rem; color:#059669; text-decoration:none; font-weight:600;">
                    ¿Ya tienes un código de 6 dígitos? Ingresar aquí
                </a>
            </div>
        </div>

        <!-- ── PASO 2: Ingresar código y nueva contraseña ── -->
        <div id="paso2Restablecer" style="display:none;">
            <form id="formPaso2" onsubmit="guardarNuevaPassword(event)">
                <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                <input type="hidden" id="recuperarTokenOculto" name="token" value="">

                <!-- Campo de código de 6 dígitos (oculto si viene por token de enlace directo) -->
                <div id="grupoCodigoVerificacion" class="auth-input-group">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                        Código de verificación (6 dígitos)
                    </label>
                    <div style="position:relative;">
                        <input type="text" id="recuperarCodigoInput" name="codigo" class="shadcn-input" 
                               placeholder="123456" maxlength="6" pattern="[0-9]{6}"
                               style="padding-left:2.5rem; letter-spacing:4px; font-weight:700; font-size:1.1rem; background:#f8fafc; border:1px solid #cbd5e1;">
                        <i class="bi bi-key" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.1rem;"></i>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                        Nueva Contraseña
                    </label>
                    <div style="position:relative;">
                        <input type="password" id="modalNuevaPassword" name="password" class="shadcn-input" 
                               placeholder="Mínimo 6 caracteres" minlength="6" required
                               style="padding-left:2.5rem; padding-right:2.5rem; background:#f8fafc; border:1px solid #cbd5e1;">
                        <i class="bi bi-lock" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.1rem;"></i>
                        <button type="button" class="auth-toggle-pwd" onclick="toggleVisibilidadPassword('modalNuevaPassword', 'modalEye1')" style="top:50%; transform:translateY(-50%);">
                            <i id="modalEye1" class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-input-group" style="margin-bottom:1.25rem;">
                    <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                        Confirmar Contraseña
                    </label>
                    <div style="position:relative;">
                        <input type="password" id="modalConfirmPassword" name="password_confirm" class="shadcn-input" 
                               placeholder="Repite la nueva contraseña" minlength="6" required
                               style="padding-left:2.5rem; padding-right:2.5rem; background:#f8fafc; border:1px solid #cbd5e1;">
                        <i class="bi bi-shield-check" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.1rem;"></i>
                        <button type="button" class="auth-toggle-pwd" onclick="toggleVisibilidadPassword('modalConfirmPassword', 'modalEye2')" style="top:50%; transform:translateY(-50%);">
                            <i id="modalEye2" class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div id="modalCoincideMsg" style="font-size:0.75rem; margin-top:0.35rem; display:none;"></div>
                </div>

                <button type="submit" id="btnGuardarPaso2" class="btn-shadcn btn-shadcn-primary" style="width:100%; padding:0.75rem; font-size:0.875rem; font-weight:600; background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,0.25);">
                    <i class="bi bi-check2-circle me-1"></i>
                    <span id="btnGuardarPaso2Texto">Restablecer Contraseña</span>
                </button>
            </form>

            <div style="text-align:center; margin-top:1rem;">
                <a href="javascript:void(0)" onclick="mostrarPaso1()" style="font-size:0.75rem; color:#64748b; text-decoration:none; font-weight:600;">
                    ← Solicitar otro código
                </a>
            </div>
        </div>

    </div>
</div>

<style>
/* Estilos para el Modal Flotante */
.auth-modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    animation: modalOverlayFade 0.2s ease-out;
}
@keyframes modalOverlayFade {
    from { opacity: 0; }
    to { opacity: 1; }
}
.auth-modal-card {
    background: #ffffff;
    width: 100%;
    max-width: 440px;
    border-radius: 18px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    padding: 2.25rem 2rem;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.8);
    animation: modalScaleIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalScaleIn {
    from { transform: scale(0.92) translateY(14px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
.auth-modal-close-btn {
    position: absolute;
    top: 14px;
    right: 16px;
    background: transparent;
    border: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: all 0.15s;
}
.auth-modal-close-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
}
</style>

<script>
function togglePasswordVisibilidad() {
    const input = document.getElementById('loginPassword');
    const icon = document.getElementById('pwdToggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function toggleVisibilidadPassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// ── MANEJO DEL MODAL ──────────────────────────────────────────────
function abrirModalRecuperar() {
    const modal = document.getElementById('modalRecuperarOverlay');
    modal.style.display = 'flex';
    limpiarAlertaModal();
    mostrarPaso1();
    setTimeout(() => {
        const input = document.getElementById('recuperarIdentificador');
        if (input) input.focus();
    }, 100);
}

function cerrarModalRecuperar() {
    const modal = document.getElementById('modalRecuperarOverlay');
    modal.style.display = 'none';
}

function cerrarModalFondo(event) {
    if (event.target.id === 'modalRecuperarOverlay') {
        cerrarModalRecuperar();
    }
}

function mostrarPaso1() {
    document.getElementById('modalTitulo').textContent = 'Recuperar Contraseña';
    document.getElementById('modalSubtitulo').textContent = 'Te enviaremos un código y enlace seguro a tu correo';
    document.getElementById('paso1Solicitud').style.display = 'block';
    document.getElementById('paso2Restablecer').style.display = 'none';
}

function mostrarPaso2(token = null, correoPrevio = '') {
    document.getElementById('modalTitulo').textContent = 'Establecer Nueva Contraseña';
    document.getElementById('modalSubtitulo').textContent = 'Ingresa el código de tu correo y tu nueva contraseña';
    document.getElementById('paso1Solicitud').style.display = 'none';
    document.getElementById('paso2Restablecer').style.display = 'block';

    const tokenInput = document.getElementById('recuperarTokenOculto');
    const grupoCodigo = document.getElementById('grupoCodigoVerificacion');
    const codigoInput = document.getElementById('recuperarCodigoInput');

    if (token) {
        tokenInput.value = token;
        grupoCodigo.style.display = 'none';
        codigoInput.removeAttribute('required');
    } else {
        tokenInput.value = '';
        grupoCodigo.style.display = 'block';
        codigoInput.setAttribute('required', 'required');
        setTimeout(() => codigoInput.focus(), 100);
    }
}

function mostrarAlertaModal(tipo, mensaje) {
    const alerta = document.getElementById('modalAlerta');
    alerta.style.display = 'block';
    if (tipo === 'success') {
        alerta.style.background = 'rgba(16, 185, 129, 0.12)';
        alerta.style.color = '#047857';
        alerta.style.border = '1px solid rgba(16, 185, 129, 0.3)';
        alerta.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ' + mensaje;
    } else {
        alerta.style.background = 'rgba(239, 68, 68, 0.12)';
        alerta.style.color = '#dc2626';
        alerta.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        alerta.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> ' + mensaje;
    }
}

function limpiarAlertaModal() {
    const alerta = document.getElementById('modalAlerta');
    alerta.style.display = 'none';
    alerta.innerHTML = '';
}

// ── ENVÍO AJAX PASO 1 (SOLICITAR CÓDIGO) ──────────────────────────
async function enviarSolicitudRecuperacion(e) {
    e.preventDefault();
    limpiarAlertaModal();

    const btn = document.getElementById('btnEnviarPaso1');
    const btnTexto = document.getElementById('btnEnviarPaso1Texto');
    const identificador = document.getElementById('recuperarIdentificador').value.trim();

    if (!identificador) return;

    btn.disabled = true;
    btnTexto.textContent = 'Enviando correo...';

    try {
        const formData = new FormData(document.getElementById('formPaso1'));
        const resp = await fetch('index.php?action=ajax-recuperar', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            mostrarAlertaModal('success', data.mensaje || '¡Código enviado a tu correo!');
            setTimeout(() => {
                mostrarPaso2(null, identificador);
            }, 1200);
        } else {
            mostrarAlertaModal('error', data.error || 'Ocurrió un error. Intenta de nuevo.');
        }
    } catch (err) {
        mostrarAlertaModal('error', 'Error de conexión con el servidor. Revisa tu conexión.');
    } finally {
        btn.disabled = false;
        btnTexto.textContent = 'Enviar Código a mi Correo';
    }
}

// ── ENVÍO AJAX PASO 2 (RESTABLECER CONTRASEÑA) ────────────────────
async function guardarNuevaPassword(e) {
    e.preventDefault();
    limpiarAlertaModal();

    const pass1 = document.getElementById('modalNuevaPassword').value;
    const pass2 = document.getElementById('modalConfirmPassword').value;

    if (pass1.length < 6) {
        mostrarAlertaModal('error', 'La contraseña debe tener al menos 6 caracteres.');
        return;
    }
    if (pass1 !== pass2) {
        mostrarAlertaModal('error', 'Las contraseñas no coinciden.');
        return;
    }

    const btn = document.getElementById('btnGuardarPaso2');
    const btnTexto = document.getElementById('btnGuardarPaso2Texto');
    btn.disabled = true;
    btnTexto.textContent = 'Actualizando contraseña...';

    try {
        const formData = new FormData(document.getElementById('formPaso2'));
        const ident = document.getElementById('recuperarIdentificador').value.trim();
        formData.append('identificador', ident);

        const resp = await fetch('index.php?action=ajax-restablecer', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        if (data.success) {
            mostrarAlertaModal('success', data.mensaje);
            setTimeout(() => {
                cerrarModalRecuperar();
                alert(data.mensaje);
            }, 1500);
        } else {
            mostrarAlertaModal('error', data.error || 'No se pudo actualizar la contraseña.');
        }
    } catch (err) {
        mostrarAlertaModal('error', 'Error al comunicarse con el servidor.');
    } finally {
        btn.disabled = false;
        btnTexto.textContent = 'Restablecer Contraseña';
    }
}

// Validación de coincidencia en vivo en el modal
const modalPass1 = document.getElementById('modalNuevaPassword');
const modalPass2 = document.getElementById('modalConfirmPassword');
const modalMsg   = document.getElementById('modalCoincideMsg');

function validarMatchModal() {
    if (!modalPass2.value) {
        modalMsg.style.display = 'none';
        return;
    }
    modalMsg.style.display = 'block';
    if (modalPass1.value === modalPass2.value) {
        modalMsg.textContent = '✓ Las contraseñas coinciden';
        modalMsg.style.color = '#16a34a';
    } else {
        modalMsg.textContent = '✗ Las contraseñas no coinciden';
        modalMsg.style.color = '#dc2626';
    }
}
if (modalPass1 && modalPass2) {
    modalPass1.addEventListener('input', validarMatchModal);
    modalPass2.addEventListener('input', validarMatchModal);
}

// Abrir automáticamente el modal si en la URL viene reset_token o abrir_recuperar
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const resetToken = urlParams.get('reset_token');
    const abrirRec = urlParams.get('abrir_recuperar');

    if (resetToken) {
        const modal = document.getElementById('modalRecuperarOverlay');
        modal.style.display = 'flex';
        mostrarPaso2(resetToken, '');
    } else if (abrirRec === '1') {
        abrirModalRecuperar();
    }
});
</script>

</body>
</html>
