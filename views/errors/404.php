<?php
http_response_code(404);
$basePath = (strpos($_SERVER['REQUEST_URI'] ?? '', '/views/errors/') !== false) ? '../../' : '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Página no encontrada — Control de Ingresos SENA</title>
    <!-- Google Font Inter & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- CSS específico de error 404 con resolución de ruta dinámica -->
    <link href="<?= $basePath ?>public/css/style404.css" rel="stylesheet">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <i class="bi bi-file-earmark-x-fill"></i>
        </div>
        <div class="error-code">
            Error 404
        </div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-desc">
            La ruta o recurso que estás intentando consultar no existe en el sistema o ha sido trasladada.
        </p>
        <a href="<?= $basePath ?>index.php" class="btn-home">
            <i class="bi bi-house-door-fill"></i>
            <span>Volver al Inicio</span>
        </a>
    </div>
</body>
</html>