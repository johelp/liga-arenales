<?php
$admin_nombre    = isset($_SESSION['admin_nombre'])  ? htmlspecialchars($_SESSION['admin_nombre'])  : 'Admin';
$es_superadmin   = $_SESSION['es_superadmin'] ?? true;
$torneos_perm    = $_SESSION['torneos_permitidos'] ?? [];
$app_root     = '/liga/';
$base_path    = $app_root . 'admin/';
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Detectar sección activa
function nav_active(string $dir, string $file = ''): string {
    global $current_dir, $current_file;
    if ($dir && strpos(dirname($_SERVER['PHP_SELF']), $dir) !== false) return 'active';
    if ($file && $current_file === $file) return 'active';
    return '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Liga Deportiva Arenales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root {
        --liga-blue:      #004386;
        --liga-blue-dark: #003066;
        --liga-accent:    #f0a500;
        --nav-height:     56px;
    }

    /* ── Top bar ── */
    .admin-topbar {
        background: var(--liga-blue);
        color: #fff;
        height: var(--nav-height);
        display: flex;
        align-items: center;
        padding: 0 1rem;
        position: sticky;
        top: 0;
        z-index: 1030;
        box-shadow: 0 2px 8px rgba(0,0,0,.25);
    }
    .admin-topbar .brand {
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: .02em;
        color: #fff;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .admin-topbar .brand i { color: var(--liga-accent); font-size: 1.2rem; }
    .admin-topbar .spacer { flex: 1; }
    .admin-topbar .user-badge {
        font-size: .78rem;
        opacity: .85;
        display: flex;
        align-items: center;
        gap: .3rem;
    }
    .admin-topbar .btn-logout {
        color: rgba(255,255,255,.75);
        font-size: .85rem;
        padding: .3rem .6rem;
        border-radius: 8px;
        text-decoration: none;
        transition: background .2s;
    }
    .admin-topbar .btn-logout:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ── Bottom nav (mobile) ── */
    .admin-bottomnav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: #fff;
        border-top: 1px solid #e0e0e0;
        z-index: 1029;
        display: flex;
        justify-content: space-around;
        padding: .3rem 0 calc(.3rem + env(safe-area-inset-bottom));
        box-shadow: 0 -2px 10px rgba(0,0,0,.08);
    }
    .admin-bottomnav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .1rem;
        color: #888;
        text-decoration: none;
        font-size: .65rem;
        font-weight: 500;
        padding: .2rem .5rem;
        border-radius: 10px;
        transition: color .15s;
        min-width: 52px;
    }
    .admin-bottomnav a i { font-size: 1.4rem; }
    .admin-bottomnav a.active { color: var(--liga-blue); }
    .admin-bottomnav a.active i { color: var(--liga-blue); }
    .admin-bottomnav a.destacado { color: #fff; background: var(--liga-blue); border-radius: 12px; }
    .admin-bottomnav a.destacado i { color: #fff; }

    /* ── Desktop sidebar nav ── */
    .admin-sidenav {
        display: none;
    }

    /* ── Main content offset ── */
    .admin-content {
        padding-bottom: 80px; /* espacio para bottom nav */
    }

    @media (min-width: 768px) {
        .admin-bottomnav { display: none; }
        .admin-sidenav {
            display: block;
            background: #fff;
            border-bottom: 1px solid #e8e8e8;
            padding: 0 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .admin-sidenav .nav-link {
            color: #555;
            font-size: .88rem;
            font-weight: 500;
            padding: .65rem .85rem;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .admin-sidenav .nav-link:hover { color: var(--liga-blue); }
        .admin-sidenav .nav-link.active {
            color: var(--liga-blue);
            border-bottom-color: var(--liga-blue);
            font-weight: 600;
        }
        .admin-sidenav .nav-link.destacado {
            color: #fff;
            background: var(--liga-blue);
            border-radius: 8px;
            margin: .25rem .2rem;
        }
        .admin-content { padding-bottom: 2rem; }
    }
    </style>
</head>
<body>

<!-- Top bar -->
<header class="admin-topbar">
    <a href="<?= $app_root ?>admin/index.php" class="brand">
        <i class="bi bi-trophy-fill"></i>
        <span class="d-none d-sm-inline">Liga Arenales</span>
        <span class="d-sm-none">LDGA</span>
    </a>
    <span class="spacer"></span>
    <span class="user-badge d-none d-md-flex">
        <i class="bi bi-person-circle"></i> <?= $admin_nombre ?>
        <?php if ($es_superadmin): ?>
            <span style="background:rgba(240,165,0,.25);color:#f0a500;font-size:.65rem;padding:.1rem .4rem;border-radius:10px;font-weight:700;letter-spacing:.5px;">ADMIN</span>
        <?php else: ?>
            <span style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.7);font-size:.65rem;padding:.1rem .4rem;border-radius:10px;">editor</span>
        <?php endif; ?>
    </span>
    <a href="<?= $base_path ?>logout.php" class="btn-logout ms-3" title="Cerrar sesión">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</header>

<!-- Desktop nav -->
<nav class="admin-sidenav">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link <?= nav_active('', 'index.php') && $current_dir === 'admin' ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/index.php">
               <i class="bi bi-house-fill"></i> Inicio
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link destacado"
               href="<?= $app_root ?>admin/partidos/cargar_resultados_fecha.php">
               <i class="bi bi-clipboard-check-fill"></i> Cargar Resultados
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('partidos') ?>"
               href="<?= $app_root ?>admin/partidos/">
               <i class="bi bi-calendar2-week-fill"></i> Partidos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('torneos') ?>"
               href="<?= $app_root ?>admin/torneos/">
               <i class="bi bi-trophy-fill"></i> Torneos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('divisiones') ?>"
               href="<?= $app_root ?>admin/divisiones/">
               <i class="bi bi-list-ol"></i> Divisiones
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('clubes') ?>"
               href="<?= $app_root ?>admin/clubes/">
               <i class="bi bi-shield-fill"></i> Clubes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('', 'widgets.php') && $current_dir === 'admin' ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/widgets.php">
               <i class="bi bi-window-stack"></i> Widgets
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('', 'compartir.php') && $current_dir === 'admin' ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/compartir.php">
               <i class="bi bi-share-fill"></i> Compartir
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= nav_active('usuarios') || ($current_file === 'usuarios.php' && $current_dir === 'admin') ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/usuarios.php">
               <i class="bi bi-people-fill"></i> Usuarios
            </a>
        </li>
    </ul>
</nav>

<!-- Mobile bottom nav -->
<nav class="admin-bottomnav">
    <a href="<?= $app_root ?>admin/index.php" <?= nav_active('', 'index.php') && $current_dir === 'admin' ? 'class="active"' : '' ?>>
        <i class="bi bi-house-fill"></i> Inicio
    </a>
    <a href="<?= $app_root ?>admin/partidos/cargar_resultados_fecha.php"
       class="destacado <?= $current_file === 'cargar_resultados_fecha.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check-fill"></i> Resultados
    </a>
    <a href="<?= $app_root ?>admin/partidos/cargar_fecha.php"
       class="<?= $current_file === 'cargar_fecha.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar-plus-fill"></i> Fecha
    </a>
    <a href="<?= $app_root ?>admin/partidos/" <?= nav_active('partidos') ? 'class="active"' : '' ?>>
        <i class="bi bi-calendar2-week-fill"></i> Partidos
    </a>
    <a href="<?= $app_root ?>admin/torneos/" <?= nav_active('torneos') ? 'class="active"' : '' ?>>
        <i class="bi bi-trophy-fill"></i> Torneos
    </a>
</nav>

<div class="admin-content">
