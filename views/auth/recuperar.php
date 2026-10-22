<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña — Control de Ingresos SENA CTA Cartago</title>
    <link rel="icon" type="image/svg+xml" href="public/img/sena-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="public/css/styles.css" rel="stylesheet">
    <style>
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
        .auth-bg-blur {
            position: fixed;
            top: -24px; left: -24px; right: -24px; bottom: -24px;
            background-image: url('public/uploads/Imagenes/ctaCartago.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(8px) brightness(0.65) saturate(1.15);
            transform: scale(1.05);
            z-index: 0;
            pointer-events: none;
        }
        .auth-overlay-gradient {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, rgba(15, 23, 42, 0.4) 0%, rgba(6, 78, 59, 0.72) 100%);
            z-index: 1;
            pointer-events: none;
        }
        .auth-glass-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-xl, 16px);
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.35);
            animation: authFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes authFadeIn {
            from { opacity: 0; transform: translateY(16px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .auth-logo-badge {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #39A900 0%, #2e8b00 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 10px 25px rgba(57, 169, 0, 0.35);
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.25);
        }
        .auth-logo-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .auth-input-group {
            margin-bottom: 1.25rem;
        }
        .tab-btn {
            background: transparent;
            border: none;
            padding: 0.5rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: #059669;
            border-bottom-color: #059669;
        }
    </style>
</head>
<body class="shadcn-auth-layout">

<div class="auth-bg-blur"></div>
<div class="auth-overlay-gradient"></div>

<div class="auth-glass-card">
    <div style="text-align:center; margin-bottom:1.5rem;">
        <div class="auth-logo-badge">
            <img src="public/img/sena-logo-white.svg" alt="SENA">
        </div>
        <h1 style="font-size:1.35rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.02em;">
            Recuperar Contraseña
        </h1>
        <div style="font-size:0.85rem; color:#475569; margin-top:0.375rem; font-weight:500;">
            Te enviaremos un enlace seguro a tu correo
        </div>
    </div>

    <!-- Mensajes de alerta -->
    <?php if (!empty($error)): ?>
    <div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.25); display:flex; align-items:center; gap:0.625rem; font-weight:500;">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div style="background-color:rgba(16,185,129,0.1); color:#047857; padding:0.875rem 1rem; border-radius:var(--radius-md); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(16,185,129,0.3); display:flex; align-items:flex-start; gap:0.625rem; font-weight:500; line-height:1.45;">
        <i class="bi bi-check-circle-fill fs-5" style="flex-shrink:0; margin-top:2px;"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <!-- Pestañas de modo: Por Correo o Por Código -->
    <div style="display:flex; border-bottom:1px solid #e2e8f0; margin-bottom:1.25rem;">
        <button type="button" class="tab-btn active" id="tabCorreoBtn" onclick="cambiarModo('correo')">
            <i class="bi bi-envelope me-1"></i> Enviar Enlace
        </button>
        <button type="button" class="tab-btn" id="tabCodigoBtn" onclick="cambiarModo('codigo')">
            <i class="bi bi-key me-1"></i> Ya tengo un código
        </button>
    </div>

    <!-- MODO 1: Enviar enlace al correo -->
    <div id="panelCorreo">
        <form method="POST" action="index.php?action=recuperar-password">
            <?= Csrf::campoHtml() ?>
            <div class="auth-input-group">
                <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                    Correo institucional o documento
                </label>
                <div style="position:relative;">
                    <input type="text" name="identificador" class="shadcn-input" 
                           placeholder="ej. nombre@sena.edu.co o 1114..." 
                           required autofocus 
                           value="<?= htmlspecialchars($_POST['identificador'] ?? '') ?>"
                           style="padding-left:2.5rem; background:rgba(255,255,255,0.95); border:1px solid #cbd5e1;">
                    <i class="bi bi-person-badge" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.125rem;"></i>
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.375rem;">
                    Ingresa el correo con el que inicias sesión en el sistema.
                </div>
            </div>

            <button type="submit" class="btn-shadcn btn-shadcn-primary" style="width:100%; padding:0.75rem; font-size:0.9375rem; font-weight:600; background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,0.25);">
                <i class="bi bi-send me-1"></i>
                <span>Enviar Correo de Recuperación</span>
            </button>
        </form>
    </div>

    <!-- MODO 2: Validar código de 6 dígitos -->
    <div id="panelCodigo" style="display:none;">
        <form method="POST" action="index.php?action=recuperar-password">
            <?= Csrf::campoHtml() ?>
            <input type="hidden" name="tipo_accion" value="validar_codigo">
            
            <div class="auth-input-group">
                <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                    Correo o número de documento
                </label>
                <div style="position:relative;">
                    <input type="text" name="identificador" class="shadcn-input" 
                           placeholder="ej. tu correo o documento" 
                           required
                           style="padding-left:2.5rem; background:rgba(255,255,255,0.95); border:1px solid #cbd5e1;">
                    <i class="bi bi-person" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.125rem;"></i>
                </div>
            </div>

            <div class="auth-input-group" style="margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                    Código de seguridad (6 dígitos)
                </label>
                <div style="position:relative;">
                    <input type="text" name="codigo" class="shadcn-input" 
                           placeholder="123456" 
                           maxlength="6"
                           required
                           pattern="[0-9]{6}"
                           style="padding-left:2.5rem; letter-spacing:4px; font-weight:700; font-size:1.1rem; background:rgba(255,255,255,0.95); border:1px solid #cbd5e1;">
                    <i class="bi bi-key" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748b; font-size:1.125rem;"></i>
                </div>
            </div>

            <button type="submit" class="btn-shadcn btn-shadcn-primary" style="width:100%; padding:0.75rem; font-size:0.9375rem; font-weight:600; background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,0.25);">
                <i class="bi bi-shield-lock me-1"></i>
                <span>Verificar Código</span>
            </button>
        </form>
    </div>

    <!-- Enlace de regreso al Login -->
    <div style="margin-top:1.5rem; text-align:center;">
        <a href="index.php?action=login" style="font-size:0.8125rem; color:#059669; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:0.35rem;">
            <i class="bi bi-arrow-left"></i>
            <span>Volver al Inicio de Sesión</span>
        </a>
    </div>

    <div style="margin-top:1.75rem; text-align:center; font-size:0.75rem; color:#64748b; border-top:1px solid rgba(0,0,0,0.06); padding-top:1.25rem;">
        <i class="bi bi-shield-check me-1" style="color:#059669;"></i>
        SENA — CTA Cartago • Control de Ingreso y Formación
    </div>
</div>

<script>
function cambiarModo(modo) {
    const tabCorreo = document.getElementById('tabCorreoBtn');
    const tabCodigo = document.getElementById('tabCodigoBtn');
    const panelCorreo = document.getElementById('panelCorreo');
    const panelCodigo = document.getElementById('panelCodigo');

    if (modo === 'codigo') {
        tabCorreo.classList.remove('active');
        tabCodigo.classList.add('active');
        panelCorreo.style.display = 'none';
        panelCodigo.style.display = 'block';
    } else {
        tabCodigo.classList.remove('active');
        tabCorreo.classList.add('active');
        panelCodigo.style.display = 'none';
        panelCorreo.style.display = 'block';
    }
}
</script>

</body>
</html>
