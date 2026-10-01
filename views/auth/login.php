<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Control de Ingresos SENA CTA Cartago</title>
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
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: var(--radius-lg, 16px);
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
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35);
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
    <div class="auth-header" style="text-align:center; margin-bottom:2rem;">
        <div class="auth-logo-badge">
            <i class="bi bi-building-check"></i>
        </div>
        <h1 class="auth-title" style="font-size:1.45rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.02em;">
            Sistema de Ingreso SENA
        </h1>
        <div class="auth-subtitle" style="font-size:0.875rem; color:#475569; margin-top:0.375rem; font-weight:500;">
            Centro de Tecnologías Agroindustriales — Cartago
        </div>
    </div>

    <!-- Muestra mensaje de error si las credenciales fallan -->
    <?php if (!empty($error)): ?>
    <div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.25); display:flex; align-items:center; gap:0.625rem; font-weight:500;">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- Formulario de inicio de sesión -->
    <form method="POST" action="index.php?action=login">
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
            <label style="display:block; font-size:0.8125rem; font-weight:600; margin-bottom:0.375rem; color:#1e293b;">
                Contraseña
            </label>
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
    </form>

    <div style="margin-top:1.75rem; text-align:center; font-size:0.75rem; color:#64748b; border-top:1px solid rgba(0,0,0,0.06); padding-top:1.25rem;">
        <i class="bi bi-shield-check me-1" style="color:#059669;"></i>
        SENA — CTA Cartago • Control de Ingreso y Formación
    </div>
</div>

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
</script>

</body>
</html>
