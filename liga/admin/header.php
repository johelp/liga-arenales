<?php
$admin_nombre    = isset($_SESSION['admin_nombre'])  ? htmlspecialchars($_SESSION['admin_nombre'])  : 'Admin';
$es_superadmin   = $_SESSION['es_superadmin'] ?? true;
$torneos_perm    = $_SESSION['torneos_permitidos'] ?? [];
$app_root     = '/liga/';
$base_path    = $app_root . 'admin/';
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Admin · Liga Deportiva Arenales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root {
        --liga-blue:      #004386;
        --liga-blue-dark: #003066;
        --liga-accent:    #f0a500;
        --nav-height:     56px;
        --bottom-nav-h:   60px;
        --safe-bottom:    env(safe-area-inset-bottom, 0px);
    }

    /* ─── Reset & base ─────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body  { background: #f0f4f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; }

    /* ─── Top bar ───────────────────────────────────────────── */
    .admin-topbar {
        background: var(--liga-blue);
        color: #fff;
        height: var(--nav-height);
        display: flex;
        align-items: center;
        padding: 0 1rem;
        padding-left: max(1rem, env(safe-area-inset-left));
        padding-right: max(1rem, env(safe-area-inset-right));
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
    .admin-topbar .spacer  { flex: 1; }
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
        -webkit-tap-highlight-color: transparent;
    }
    .admin-topbar .btn-logout:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ─── Desktop sub-nav ───────────────────────────────────── */
    .admin-sidenav { display: none; }

    /* ─── Main content ──────────────────────────────────────── */
    .admin-content {
        padding-bottom: calc(var(--bottom-nav-h) + var(--safe-bottom) + 8px);
        min-height: calc(100vh - var(--nav-height));
    }

    /* ─── Mobile bottom nav ─────────────────────────────────── */
    .admin-bottomnav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: #fff;
        border-top: 1px solid #e0e0e0;
        z-index: 1029;
        display: flex;
        justify-content: space-around;
        align-items: stretch;
        padding-bottom: var(--safe-bottom);
        box-shadow: 0 -2px 12px rgba(0,0,0,.10);
    }
    .admin-bottomnav a,
    .admin-bottomnav button {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .08rem;
        color: #999;
        text-decoration: none;
        font-size: .6rem;
        font-weight: 500;
        padding: .45rem .2rem;
        border: none;
        background: none;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: color .12s;
        min-height: var(--bottom-nav-h);
    }
    .admin-bottomnav a i,
    .admin-bottomnav button i { font-size: 1.35rem; line-height: 1; }
    .admin-bottomnav a.active,
    .admin-bottomnav a.active i { color: var(--liga-blue); }
    .admin-bottomnav a.btn-cargar {
        color: #fff;
        background: var(--liga-blue);
        border-radius: 0;
    }
    .admin-bottomnav a.btn-cargar i { color: #fff; }
    .admin-bottomnav button.btn-mas { color: #555; }
    .admin-bottomnav button.btn-mas.open { color: var(--liga-blue); }

    /* ─── "Más" slide-up panel ──────────────────────────────── */
    .mas-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 1028;
        -webkit-tap-highlight-color: transparent;
    }
    .mas-overlay.visible { display: block; }
    .mas-panel {
        position: fixed;
        bottom: calc(var(--bottom-nav-h) + var(--safe-bottom));
        left: 0; right: 0;
        background: #fff;
        border-radius: 18px 18px 0 0;
        z-index: 1029;
        padding: .75rem 1rem 1rem;
        transform: translateY(100%);
        transition: transform .25s cubic-bezier(.4,0,.2,1);
        box-shadow: 0 -4px 20px rgba(0,0,0,.15);
    }
    .mas-panel.visible { transform: translateY(0); }
    .mas-panel .mas-title {
        font-size: .7rem;
        font-weight: 700;
        color: #aaa;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .6rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .mas-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .5rem;
    }
    .mas-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .25rem;
        padding: .6rem .3rem;
        border-radius: 12px;
        color: #444;
        text-decoration: none;
        font-size: .68rem;
        font-weight: 500;
        background: #f8f9fc;
        -webkit-tap-highlight-color: transparent;
        transition: background .15s;
    }
    .mas-item:active { background: #e8eef8; color: var(--liga-blue); }
    .mas-item i { font-size: 1.45rem; color: var(--liga-blue); }
    .mas-item.active { background: #e8eef8; color: var(--liga-blue); font-weight: 700; }

    /* ─── Global mobile helpers ─────────────────────────────── */

    /* Inputs y selects táctiles */
    @media (max-width: 767px) {
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="datetime-local"],
        input[type="url"],
        select,
        textarea {
            font-size: 16px !important; /* evita zoom automático en iOS */
        }

        /* Tablas: scroll horizontal con indicador */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
        }

        /* Botones de acción: más grandes en mobile */
        .action-btn { min-width: 36px; min-height: 36px; }

        /* Cards con menos padding */
        .card-body { padding: .85rem; }

        /* Botón flotante guardar: por encima del bottom nav */
        .guardar-fab {
            bottom: calc(var(--bottom-nav-h) + var(--safe-bottom) + .8rem) !important;
        }

        /* Ocultar columnas secundarias en tablas */
        .col-hide-mobile { display: none !important; }

        /* Ajuste contenedor */
        .container-fluid, .container { padding-left: .75rem; padding-right: .75rem; }
    }

    /* ─── Desktop overrides ─────────────────────────────────── */
    @media (min-width: 768px) {
        .admin-bottomnav { display: none; }
        .mas-overlay, .mas-panel { display: none !important; }
        .admin-content { padding-bottom: 2rem; }

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
        .col-hide-mobile { display: table-cell !important; }
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
        <span class="d-none d-sm-inline ms-1" style="font-size:.8rem;">Salir</span>
    </a>
</header>

<!-- Desktop nav -->
<nav class="admin-sidenav">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link <?= (nav_active('', 'index.php') && $current_dir === 'admin') ? 'active' : '' ?>"
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
            <a class="nav-link <?= (nav_active('', 'compartir.php') && $current_dir === 'admin') ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/compartir.php">
               <i class="bi bi-share-fill"></i> Compartir
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (nav_active('', 'widgets.php') && $current_dir === 'admin') ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/widgets.php">
               <i class="bi bi-window-stack"></i> Widgets
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_file === 'usuarios.php' && $current_dir === 'admin') ? 'active' : '' ?>"
               href="<?= $app_root ?>admin/usuarios.php">
               <i class="bi bi-people-fill"></i> Usuarios
            </a>
        </li>
    </ul>
</nav>

<!-- Mobile bottom nav (5 items + Más) -->
<nav class="admin-bottomnav" id="bottom-nav">
    <a href="<?= $app_root ?>admin/index.php"
       class="<?= ($current_file === 'index.php' && $current_dir === 'admin') ? 'active' : '' ?>">
        <i class="bi bi-house-fill"></i> Inicio
    </a>
    <a href="<?= $app_root ?>admin/partidos/cargar_resultados_fecha.php"
       class="btn-cargar <?= $current_file === 'cargar_resultados_fecha.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check-fill"></i> Resultados
    </a>
    <a href="<?= $app_root ?>admin/partidos/"
       class="<?= (nav_active('partidos') && $current_file !== 'cargar_resultados_fecha.php') ? 'active' : '' ?>">
        <i class="bi bi-calendar2-week-fill"></i> Partidos
    </a>
    <a href="<?= $app_root ?>admin/torneos/"
       class="<?= nav_active('torneos') ? 'active' : '' ?>">
        <i class="bi bi-trophy-fill"></i> Torneos
    </a>
    <button class="btn-mas" id="btn-mas" onclick="toggleMas()" aria-label="Más opciones">
        <i class="bi bi-grid-3x3-gap-fill"></i> Más
    </button>
</nav>

<!-- Overlay + slide-up panel -->
<div class="mas-overlay" id="mas-overlay" onclick="closeMas()"></div>
<div class="mas-panel" id="mas-panel">
    <div class="mas-title">Más secciones</div>
    <div class="mas-grid">
        <a href="<?= $app_root ?>admin/clubes/" class="mas-item <?= nav_active('clubes') ? 'active' : '' ?>">
            <i class="bi bi-shield-fill"></i> Clubes
        </a>
        <a href="<?= $app_root ?>admin/divisiones/" class="mas-item <?= nav_active('divisiones') ? 'active' : '' ?>">
            <i class="bi bi-list-ol"></i> Divisiones
        </a>
        <a href="<?= $app_root ?>admin/partidos/reprogramar_fecha.php" class="mas-item <?= $current_file === 'reprogramar_fecha.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-x-fill"></i> Reprogramar
        </a>
        <a href="<?= $app_root ?>admin/compartir.php" class="mas-item <?= ($current_file === 'compartir.php' && $current_dir === 'admin') ? 'active' : '' ?>">
            <i class="bi bi-share-fill"></i> Compartir
        </a>
        <a href="<?= $app_root ?>admin/widgets.php" class="mas-item <?= ($current_file === 'widgets.php' && $current_dir === 'admin') ? 'active' : '' ?>">
            <i class="bi bi-window-stack"></i> Widgets
        </a>
        <a href="<?= $app_root ?>admin/usuarios.php" class="mas-item <?= ($current_file === 'usuarios.php' && $current_dir === 'admin') ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Usuarios
        </a>
        <a href="<?= $app_root ?>admin/partidos/cargar_fecha.php" class="mas-item <?= $current_file === 'cargar_fecha.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-plus-fill"></i> Cargar Fecha
        </a>
        <a href="<?= $app_root ?>admin/logout.php" class="mas-item" style="color:#dc3545;">
            <i class="bi bi-box-arrow-right" style="color:#dc3545;"></i> Salir
        </a>
    </div>
    <div class="mt-2 pt-2 border-top">
        <div class="text-muted" style="font-size:.65rem; text-align:center;">
            <i class="bi bi-person-circle me-1"></i><?= $admin_nombre ?>
            <?php if ($es_superadmin): ?>
                <span style="color:var(--liga-accent);font-weight:700;"> · ADMIN</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleMas() {
    const panel   = document.getElementById('mas-panel');
    const overlay = document.getElementById('mas-overlay');
    const btn     = document.getElementById('btn-mas');
    const open    = panel.classList.toggle('visible');
    overlay.classList.toggle('visible', open);
    btn.classList.toggle('open', open);
}
function closeMas() {
    document.getElementById('mas-panel').classList.remove('visible');
    document.getElementById('mas-overlay').classList.remove('visible');
    document.getElementById('btn-mas').classList.remove('open');
}
// Cerrar con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMas(); });
</script>

<div class="admin-content">
