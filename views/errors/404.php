<?php
http_response_code(404);
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
    <link href="public/css/styles.css" rel="stylesheet">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--bg-body, #f8fafc);
            color: var(--text-color, #0f172a);
            font-family: 'Inter', sans-serif;
        }
        .error-card {
            background: var(--bg-card, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        .error-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            font-size: 2.25rem;
            margin-bottom: 1.25rem;
        }
        .error-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }
        .error-desc {
            color: var(--text-muted, #64748b);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.75rem;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: var(--primary, #39a900);
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-home:hover {
            opacity: 0.9;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <i class="bi bi-file-earmark-x-fill"></i>
        </div>
        <div style="font-size:0.875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#f59e0b; margin-bottom:0.25rem;">
            Error 404
        </div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-desc">
            La ruta o recurso que estás intentando consultar no existe en el sistema o ha sido trasladada.
        </p>
        <a href="index.php" class="btn-home">
            <i class="bi bi-house-door-fill"></i>
            <span>Volver al Inicio</span>
        </a>
    </div>
</body>
</html>