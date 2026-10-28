<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::getToken(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= $pageTitle ?? 'Sistema de Ingreso SENA — Shadcn Admin' ?></title>
    
    <!-- Favicon Oficial SENA -->
    <link rel="icon" type="image/svg+xml" href="public/img/sena-logo.svg">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Centro de alertas del sistema (switchAlert) -->
    <?php $alertasVersion = file_exists(__DIR__ . '/../../public/js/alertas.js') ? filemtime(__DIR__ . '/../../public/js/alertas.js') : time(); ?>
    <script src="public/js/alertas.js?v=<?= $alertasVersion ?>"></script>
    <!-- CSS Shadcn Admin -->
    <?php $stylesVersion = file_exists(__DIR__ . '/../../public/css/styles.css') ? filemtime(__DIR__ . '/../../public/css/styles.css') : time(); ?>
    <link href="public/css/styles.css?v=<?= $stylesVersion ?>" rel="stylesheet">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>

<div class="shadcn-app">

    <!-- Overlay móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ──  NAVEGACIÓN Y SIDEBAR SHADCN ────────────────────────────── -->
    <aside class="shadcn-sidebar" id="shadcnSidebar">
        <div class="sidebar-header" style="display:flex; align-items:center; gap:0.75rem; padding:1.25rem 1rem; border-bottom:1px solid var(--border);">
            <div class="sidebar-logo-icon" style="background:linear-gradient(135deg, #39A900 0%, #2e8b00 100%); padding:7px; display:flex; align-items:center; justify-content:center; border-radius:12px; width:42px; height:42px; box-shadow:0 4px 12px rgba(57,169,0,0.28); flex-shrink:0;">
                <img src="public/img/sena-logo-white.svg" alt="SENA" style="width:100%; height:100%; object-fit:contain;">
            </div>
            <div style="min-width:0; line-height:1.2;">
                <!-- Título del sistema -->
                <div class="sidebar-brand-name" style="font-weight:800; font-size:1.05rem; letter-spacing:-0.02em; color:var(--foreground);">SENA Ingreso</div>
                <div class="sidebar-brand-sub" style="font-size:0.75rem; font-weight:600; color:#39A900;">CTA Cartago</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php if (in_array($_SESSION['rol'] ?? '', ['Administrador', 'Instructor'])): ?>
            <div class="nav-section-title">General</div>
            
            <a href="index.php?action=dashboard" class="nav-link-item <?= (($_GET['action'] ?? '') === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>

            <a href="index.php?action=asistencia" class="nav-link-item <?= (($_GET['action'] ?? '') === 'asistencia') ? 'active' : '' ?>">
                <i class="bi bi-qr-code-scan"></i>
                <span>Terminal RFID</span>
            </a>

            <a href="index.php?action=historial" class="nav-link-item <?= (($_GET['action'] ?? '') === 'historial') ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Asistencias</span>
            </a>

            <div class="nav-section-title">Gestión</div>

            <a href="index.php?action=usuarios" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['usuarios', 'usuario-crear', 'usuario-editar'])) ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Usuarios & Aprendices</span>
            </a>

            <a href="index.php?action=fichas" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['fichas', 'ficha-crear', 'ficha-editar'])) ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark"></i>
                <span>Fichas de Formación</span>
            </a>

            <a href="index.php?action=ficha-horario-importar" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['ficha-horario', 'ficha-horario-importar', 'ficha-horario-imprimir'])) ? 'active' : '' ?>">
                <i class="bi bi-calendar-week"></i>
                <span>Horarios & Escáner Excel</span>
            </a>

            <a href="index.php?action=ficha-aprendices-importar" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['ficha-aprendices-importar', 'aprendices-importar'])) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-person"></i>
                <span>Importar Aprendices Excel</span>
            </a>

            <a href="index.php?action=desercion" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['desercion', 'desercion-citacion'])) ? 'active' : '' ?>">
                <i class="bi bi-person-exclamation"></i>
                <span>Alertas de Deserción</span>
            </a>

            <a href="index.php?action=reportes" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['reportes', 'excusas-admin'])) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reportes / Horarios</span>
            </a>
            <?php else: ?>
            <div class="nav-section-title">Portal Aprendiz</div>

            <a href="index.php?action=mi-perfil" class="nav-link-item <?= (in_array($_GET['action'] ?? '', ['mi-perfil', ''])) ? 'active' : '' ?>">
                <i class="bi bi-person-badge"></i>
                <span>Mi Perfil & Asistencias</span>
            </a>

            <a href="index.php?action=mis-excusas" class="nav-link-item <?= (($_GET['action'] ?? '') === 'mis-excusas') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-medical"></i>
                <span>Mis Excusas Médicas</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer" style="display:flex; flex-direction:column; gap:0.25rem;">
            <a href="index.php?action=mi-perfil" class="nav-link-item <?= (($_GET['action'] ?? '') === 'mi-perfil') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i>
                <span>Mi Perfil</span>
            </a>
                <form method="POST" action="index.php?action=logout" style="margin:0;">
            <?= Csrf::campoHtml() ?>
            <button type="submit" class="nav-link-item text-danger"
            style="width:100%; background:none; border:0; text-align:left; cursor:pointer; font:inherit;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </button>
        </form>
        </div>
    </aside>

    <!-- ── BARRA SUPERIOR (TOPBAR) ────────────────────────────────── -->
    <div class="shadcn-main">
        <header class="shadcn-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Abrir menú">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-md-flex align-items-center gap-2 text-muted" style="font-size:0.875rem;">
                    <img src="public/img/sena-logo.svg" alt="SENA" style="width:20px; height:20px; object-fit:contain;">
                    <span style="font-weight:600; color:var(--foreground);">Control de Ingresos — CTA Cartago</span>
                </div>
            </div>

           <div class="topbar-right">
    
   

    <!-- Modo oscuro / claro -->
    <button type="button" class="btn-shadcn btn-shadcn-outline topbar-icon-btn" id="themeToggleBtn" title="Cambiar tema">
        <i class="bi bi-moon-stars" id="themeIcon"></i>
    </button>

    <!-- Usuario -->
    <div class="user-pill">
        <div class="user-avatar-badge">
            <?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></div>
            <div class="user-role"><?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?></div>
        </div>
    </div>
</div>
        </header>

        <!-- Main Content Area -->
        <main class="page-container">
