<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Control de Ingresos SENA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="public/css/styles.css" rel="stylesheet">
</head>
<body class="shadcn-auth-layout">

<div class="auth-card">
    <div class="auth-header">
        <div class="auth-logo">
            <i class="bi bi-building-check"></i>
        </div>
        <h1 class="auth-title">Sistema de Ingreso SENA</h1>
        <p class="auth-subtitle">Ingresa tus credenciales para acceder a la plataforma</p>
    </div>

    <!-- 🟢 EDITAR AQUÍ: Muestra mensaje de error si las credenciales fallan -->
    <?php if (!empty($error)): ?>
    <div style="background-color:rgba(239,68,68,0.1); color:#dc2626; padding:0.75rem 1rem; border-radius:var(--radius); font-size:0.875rem; margin-bottom:1.25rem; border:1px solid rgba(239,68,68,0.2); display:flex; align-items:center; gap:0.5rem;">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- 🟢 EDITAR AQUÍ: Formulario de inicio de sesión -->
    <form method="POST" action="index.php?action=login">
        <div style="margin-bottom: 1.25rem;">
            <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Usuario, correo o identificación</label>
            <input type="text" name="correo" class="shadcn-input" placeholder="ej. ejemplo@sena.edu.co o 123456" required autofocus value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display:block; font-size:0.875rem; font-weight:500; margin-bottom:0.375rem;">Contraseña</label>
            <input type="password" name="password" class="shadcn-input" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-shadcn btn-shadcn-primary" style="width: 100%; padding:0.625rem;">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Iniciar Sesión</span>
        </button>
    </form>

    <div style="margin-top:1.5rem; text-align:center; font-size:0.75rem; color:var(--muted-foreground);">
        SENA ADSO — Control de Ingreso de Aprendices
    </div>
</div>

</body>
</html>
