<?php
// Obtener el nombre del administrador si está almacenado en la sesión
$admin_nombre = isset($_SESSION['admin_nombre']) ? htmlspecialchars($_SESSION['admin_nombre']) : 'Administrador';

// Determinar la ruta base para enlaces absolutos
$app_root = '/liga/'; // Agregar esta línea con la carpeta base de la aplicación
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', $current_path);
$depth = count(array_filter($path_parts)) - 2; // -2 para admin y la subcarpeta actual

// Modificar la construcción de $base_path para que use la ruta absoluta
$base_path = $app_root . 'admin/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de la Liga Deportivade General Arenales - AscensionDigital.ar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
        }
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
        }
        .admin-header {
            background-color: #004386;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .admin-header-info {
            text-align: left;
        }
        .admin-header h1 {
            margin-bottom: 0;
            font-size: 2.2rem;
        }
        .admin-header p {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 0;
        }
        .admin-logo {
            font-size: 2.5rem;
            margin-left: 20px;
        }
        .admin-nav {
            margin-bottom: 20px;
        }
        .admin-nav .nav-link {
            color: #004386;
        }
        .admin-nav .nav-link:hover {
            color: #003366;
        }
        /* Puedes añadir más estilos generales aquí si es necesario */
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-header-info">
                <h1>Panel de Administración - LDGA</h1>
                <p class="lead">FM Encuentro 103.1Mhz | Usuario <?= $admin_nombre; ?></p>
            </div>
            <div class="admin-logo">
                <i class="bi bi-gear-fill"></i>
            </div>
        </div>

        <nav class="admin-nav">
            <ul class="nav nav-pills nav-fill">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                       href="<?= $app_root ?>admin/index.php"><i class="bi bi-house-fill me-2"></i>Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos(dirname($_SERVER['PHP_SELF']), 'torneos') !== false ? 'active' : ''; ?>" 
                       href="<?= $app_root ?>admin/torneos/"><i class="bi bi-trophy-fill me-2"></i>Torneos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos(dirname($_SERVER['PHP_SELF']), 'divisiones') !== false ? 'active' : ''; ?>" 
                       href="<?= $app_root ?>admin/divisiones/"><i class="bi bi-list-ol me-2"></i>Divisiones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos(dirname($_SERVER['PHP_SELF']), 'clubes') !== false ? 'active' : ''; ?>" 
                       href="<?= $app_root ?>admin/clubes/"><i class="bi bi-shield-fill me-2"></i>Clubes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos(dirname($_SERVER['PHP_SELF']), 'partidos') !== false ? 'active' : ''; ?>" 
                       href="<?= $app_root ?>admin/partidos/"><i class="bi bi-calendar-event-fill me-2"></i>Partidos</a>
                </li>
            </ul>
        </nav>
