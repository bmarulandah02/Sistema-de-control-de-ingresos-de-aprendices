<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistema de Ingreso SENA — Shadcn Admin' ?></title>
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- CSS Shadcn Admin -->
    <link href="public/css/styles.css" rel="stylesheet">
</head>
<body>

<div class="shadcn-app">

    <!-- Overlay móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ── 🟢 NAVEGACIÓN Y SIDEBAR SHADCN ────────────────────────────── -->
    <aside class="shadcn-sidebar" id="shadcnSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <i class="bi bi-building-check"></i>
            </div>
            <div>
                <!-- 🟢 EDITAR AQUÍ: Título de tu sistema -->
                <div class="sidebar-brand-name">Ingreso</div>
                <div class="sidebar-brand-sub">SENA ADSO</div>
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

            <a href="index.php?action=fichas" class="nav-link-item <?= (($_GET['action'] ?? '') === 'fichas') ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark"></i>
                <span>Fichas & Horarios</span>
            </a>

            <a href="index.php?action=excusas-admin" class="nav-link-item <?= (($_GET['action'] ?? '') === 'excusas-admin') ? 'active' : '' ?>">
                <i class="bi bi-file-medical"></i>
                <span>Excusas Médicas</span>
            </a>

            <a href="index.php?action=reportes" class="nav-link-item <?= (($_GET['action'] ?? '') === 'reportes') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reportes PDF/Excel</span>
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

        <div class="sidebar-footer">
            <a href="index.php?action=logout" class="nav-link-item text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- ── 🟢 BARRA SUPERIOR (TOPBAR) ────────────────────────────────── -->
    <div class="shadcn-main">
        <header class="shadcn-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Abrir menú">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-md-flex align-items-center gap-2 text-muted" style="font-size:0.875rem;">
                    <i class="bi bi-shield-check text-success"></i>
                    <span>Control de Ingresos SENA</span>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Botón de Modo Oscuro / Claro -->
                <button type="button" class="btn-shadcn btn-shadcn-outline" id="themeToggleBtn" style="padding:0.4rem 0.6rem;" title="Cambiar tema">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>

                <!-- 🟢 EDITAR AQUÍ: Muestra el nombre y rol del usuario conectado -->
                <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar-badge">
                        <?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="d-none d-sm-block text-start">
                        <div style="font-size:0.875rem; font-weight:600; line-height:1.2;">
                            <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                        </div>
                        <div style="font-size:0.75rem; color:var(--muted-foreground);">
                            <?= htmlspecialchars($_SESSION['rol'] ?? 'Rol') ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="page-container">
