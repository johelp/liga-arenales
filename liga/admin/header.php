<?php
$admin_nombre  = isset($_SESSION['admin_nombre'])  ? htmlspecialchars($_SESSION['admin_nombre'])  : 'Admin';
$es_superadmin = $_SESSION['es_superadmin'] ?? true;
$app_root      = '/liga/';
$base_path     = $app_root . 'admin/';
$current_file  = basename($_SERVER['PHP_SELF']);
$current_dir   = basename(dirname($_SERVER['PHP_SELF']));

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
        --nav-h:          56px;
        --bot-nav-h:      58px;
        --safe-b:         env(safe-area-inset-bottom, 0px);
    }

    /* ── Base ─────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body { background: #f0f4f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; }

    /* ── Top bar ───────────────────────────────────────────── */
    .admin-topbar {
        background: var(--liga-blue);
        color: #fff;
        height: var(--nav-h);
        display: flex;
        align-items: center;
        padding: 0 1rem;
        position: sticky;
        top: 0;
        z-index: 1040;
        box-shadow: 0 2px 8px rgba(0,0,0,.25);
    }
    .admin-topbar .brand {
        font-weight: 700; font-size: 1rem;
        color: #fff; text-decoration: none;
        display: flex; align-items: center; gap: .4rem;
    }
    .admin-topbar .brand i { color: var(--liga-accent); font-size: 1.2rem; }
    .admin-topbar .spacer  { flex: 1; }
    .admin-topbar .user-pill {
        font-size: .75rem; opacity: .85;
        display: flex; align-items: center; gap: .3rem;
    }
    .admin-topbar .btn-logout {
        color: rgba(255,255,255,.75); font-size: .85rem;
        padding: .3rem .6rem; border-radius: 8px;
        text-decoration: none; transition: background .2s;
        -webkit-tap-highlight-color: transparent;
    }
    .admin-topbar .btn-logout:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ── Desktop sub-nav ───────────────────────────────────── */
    .admin-sidenav { display: none; }

    /* ── Content area ──────────────────────────────────────── */
    .admin-content {
        padding-bottom: calc(var(--bot-nav-h) + var(--safe-b) + 4px);
    }

    /* ── Mobile bottom nav ─────────────────────────────────── */
    .admin-bottomnav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: calc(var(--bot-nav-h) + var(--safe-b));
        padding-bottom: var(--safe-b);
        background: #fff;
        border-top: 1px solid #e0e0e0;
        z-index: 1035;
        display: flex;
        box-shadow: 0 -2px 10px rgba(0,0,0,.10);
    }
    .admin-bottomnav .bn-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .06rem;
        text-decoration: none;
        color: #999;
        font-size: .58rem;
        font-weight: 600;
        padding: .3rem .1rem;
        border: none;
        background: none;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: color .1s;
        line-height: 1;
    }
    .admin-bottomnav .bn-item i  { font-size: 1.3rem; display: block; }
    .admin-bottomnav .bn-item.active, .admin-bottomnav .bn-item.active i { color: var(--liga-blue); }
    .admin-bottomnav .bn-item.bn-primary {
        color: #fff; background: var(--liga-blue);
    }
    .admin-bottomnav .bn-item.bn-primary i { color: #fff; }

    /* ── Global mobile tweaks ──────────────────────────────── */
    @media (max-width: 767px) {
        /* Evita zoom al enfocar inputs en iOS */
        input, select, textarea { font-size: 16px !important; }

        /* Botones de acción más grandes */
        .action-btn { min-width: 34px; min-height: 34px; }

        /* Guardar flotante sube sobre el bottom nav */
        .guardar-fab {
            bottom: calc(var(--bot-nav-h) + var(--safe-b) + .75rem) !important;
        }

        /* Columnas ocultas en tablas */
        .col-hide-mobile { display: none !important; }

        /* Padding de contenedores */
        .container-fluid, .container { padding-left: .75rem; padding-right: .75rem; }

        /* Tabla scroll horizontal */
        .table-responsive { -webkit-overflow-scrolling: touch; }
    }

    /* ── Offcanvas "Más" ───────────────────────────────────── */
    .mas-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .6rem;
        padding: .5rem 0 .5rem;
    }
    .mas-btn {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: .3rem; padding: .75rem .3rem;
        border-radius: 14px;
        background: #f0f4f8;
        color: #333; text-decoration: none;
        font-size: .68rem; font-weight: 600;
        border: 1px solid transparent;
        -webkit-tap-highlight-color: transparent;
        transition: background .15s;
    }
    .mas-btn i    { font-size: 1.5rem; color: var(--liga-blue); }
    .mas-btn:active { background: #dde6f5; }
    .mas-btn.active { background: #dde6f5; border-color: var(--liga-blue); color: var(--liga-blue); }
    .mas-btn.active i { color: var(--liga-blue); }
    .mas-btn.danger   { color: #dc3545; }
    .mas-btn.danger i { color: #dc3545; }

    /* ── Desktop overrides ─────────────────────────────────── */
    @media (min-width: 768px) {
        .admin-bottomnav { display: none; }
        .admin-content   { padding-bottom: 2rem; }

        .admin-sidenav {
            display: block;
            background: #fff;
            border-bottom: 1px solid #e8e8e8;
            padding: 0 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .admin-sidenav .nav-link {
            color: #555; font-size: .88rem; font-weight: 500;
            padding: .65rem .85rem;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            display: flex; align-items: center; gap: .4rem;
        }
        .admin-sidenav .nav-link:hover  { color: var(--liga-blue); }
        .admin-sidenav .nav-link.active {
            color: var(--liga-blue);
            border-bottom-color: var(--liga-blue);
            font-weight: 600;
        }
        .admin-sidenav .nav-link.destacado {
            color: #fff; background: var(--liga-blue);
            border-radius: 8px; margin: .25rem .2rem;
        }
        .col-hide-mobile { display: table-cell !important; }
    }
    </style>
</head>
<body>

<!-- ── Top bar ──────────────────────────────────────────────── -->
<header class="admin-topbar">
    <a href="<?= $app_root ?>admin/index.php" class="brand">
        <i class="bi bi-trophy-fill"></i>
        <span class="d-none d-sm-inline">Liga Deportiva de General Arenales</span>
        <span class="d-sm-none">LDGA</span>
    </a>
    <span class="spacer"></span>
    <span class="user-pill d-none d-md-flex me-2">
        <i class="bi bi-person-circle"></i> <?= $admin_nombre ?>
        <?php if ($es_superadmin): ?>
        <span style="background:rgba(240,165,0,.25);color:var(--liga-accent);font-size:.62rem;padding:.1rem .4rem;border-radius:10px;font-weight:700;">ADMIN</span>
        <?php endif; ?>
    </span>
    <a href="<?= $app_root ?>instructivo-liga.html" target="_blank" class="btn-logout" title="Ayuda / Instructivo">
        <i class="bi bi-question-circle"></i>
    </a>
    <a href="<?= $base_path ?>logout.php" class="btn-logout" title="Salir">
        <i class="bi bi-box-arrow-right"></i>
        <span class="d-none d-sm-inline ms-1" style="font-size:.8rem;">Salir</span>
    </a>
</header>

<!-- ── Desktop nav ─────────────────────────────────────────── -->
<nav class="admin-sidenav">
    <ul class="nav">
        <?php
        $dnav = [
            ['', 'index.php', 'admin', 'bi-house-fill',         'Inicio'],
            ['', 'cargar_resultados_fecha.php', '', 'bi-clipboard-check-fill', 'Cargar Resultados', 'destacado'],
            ['partidos',  '', '', 'bi-calendar2-week-fill', 'Partidos'],
            ['torneos',   '', '', 'bi-trophy-fill',         'Torneos'],
            ['divisiones','', '', 'bi-list-ol',             'Divisiones'],
            ['clubes',    '', '', 'bi-shield-fill',         'Clubes'],
            ['', 'compartir.php', 'admin', 'bi-share-fill',  'Compartir'],
            ['', 'widgets.php',   'admin', 'bi-window-stack', 'Widgets'],
            ['', 'usuarios.php',  'admin', 'bi-people-fill',  'Usuarios'],
        ];
        foreach ($dnav as $item) {
            [$dir, $file, $chk_dir, $ico, $lbl] = $item;
            $extra  = $item[5] ?? '';
            $active = '';
            if ($dir && strpos(dirname($_SERVER['PHP_SELF']), $dir) !== false) $active = 'active';
            elseif ($file && $current_file === $file && (!$chk_dir || $current_dir === $chk_dir)) $active = 'active';
            $href = $file
                ? $app_root . 'admin/' . ($dir ? "$dir/" : '') . $file
                : $app_root . 'admin/' . $dir . '/';
            echo "<li class='nav-item'><a class='nav-link $active $extra' href='$href'><i class='bi $ico'></i> $lbl</a></li>";
        }
        ?>
        <li class="nav-item ms-auto">
            <a class="nav-link" href="<?= $app_root ?>instructivo-liga.html" target="_blank" style="color:#888;">
                <i class="bi bi-question-circle-fill"></i> Ayuda
            </a>
        </li>
    </ul>
</nav>

<!-- ── Mobile bottom nav ───────────────────────────────────── -->
<nav class="admin-bottomnav">
    <a class="bn-item <?= ($current_file === 'index.php' && $current_dir === 'admin') ? 'active' : '' ?>"
       href="<?= $app_root ?>admin/index.php">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a class="bn-item bn-primary <?= $current_file === 'cargar_resultados_fecha.php' ? 'active' : '' ?>"
       href="<?= $app_root ?>admin/partidos/cargar_resultados_fecha.php">
        <i class="bi bi-clipboard-check-fill"></i>Resultados
    </a>
    <a class="bn-item <?= (nav_active('partidos') && $current_file !== 'cargar_resultados_fecha.php') ? 'active' : '' ?>"
       href="<?= $app_root ?>admin/partidos/">
        <i class="bi bi-calendar2-week-fill"></i>Partidos
    </a>
    <a class="bn-item <?= nav_active('torneos') ? 'active' : '' ?>"
       href="<?= $app_root ?>admin/torneos/">
        <i class="bi bi-trophy-fill"></i>Torneos
    </a>
    <!-- Más → abre offcanvas -->
    <button class="bn-item" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#offcanvasMas"
            aria-controls="offcanvasMas">
        <i class="bi bi-grid-2x2-fill"></i>Más
    </button>
</nav>

<!-- ── Offcanvas "Más" ─────────────────────────────────────── -->
<div class="offcanvas offcanvas-bottom rounded-top-4" tabindex="-1"
     id="offcanvasMas" aria-labelledby="offcanvasMasLabel"
     style="max-height:55vh;">
    <div class="offcanvas-header pb-1 pt-3">
        <h6 class="offcanvas-title fw-bold small text-muted text-uppercase letter-spacing-1" id="offcanvasMasLabel">
            <i class="bi bi-grid-fill me-1" style="color:var(--liga-blue);"></i> Más secciones
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <div class="mas-grid">
            <a href="<?= $app_root ?>admin/clubes/" class="mas-btn <?= nav_active('clubes') ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-shield-fill"></i>Clubes
            </a>
            <a href="<?= $app_root ?>admin/divisiones/" class="mas-btn <?= nav_active('divisiones') ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-list-ol"></i>Divisiones
            </a>
            <a href="<?= $app_root ?>admin/partidos/reprogramar_fecha.php" class="mas-btn <?= $current_file === 'reprogramar_fecha.php' ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-calendar-x-fill"></i>Reprogramar
            </a>
            <a href="<?= $app_root ?>admin/partidos/cargar_fecha.php" class="mas-btn <?= $current_file === 'cargar_fecha.php' ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-calendar-plus-fill"></i>Cargar Fecha
            </a>
            <a href="<?= $app_root ?>admin/compartir.php" class="mas-btn <?= ($current_file === 'compartir.php' && $current_dir === 'admin') ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-share-fill"></i>Compartir
            </a>
            <a href="<?= $app_root ?>admin/widgets.php" class="mas-btn <?= ($current_file === 'widgets.php' && $current_dir === 'admin') ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-window-stack"></i>Widgets
            </a>
            <a href="<?= $app_root ?>admin/usuarios.php" class="mas-btn <?= ($current_file === 'usuarios.php' && $current_dir === 'admin') ? 'active' : '' ?>" data-bs-dismiss="offcanvas">
                <i class="bi bi-people-fill"></i>Usuarios
            </a>
            <a href="<?= $app_root ?>instructivo-liga.html" target="_blank" class="mas-btn" data-bs-dismiss="offcanvas">
                <i class="bi bi-question-circle-fill"></i>Ayuda
            </a>
            <a href="<?= $base_path ?>logout.php" class="mas-btn danger" data-bs-dismiss="offcanvas">
                <i class="bi bi-box-arrow-right"></i>Salir
            </a>
        </div>
        <div class="text-center text-muted pb-1" style="font-size:.65rem;">
            <i class="bi bi-person-circle me-1"></i><?= $admin_nombre ?>
            <?php if ($es_superadmin): ?>· <span style="color:var(--liga-accent);font-weight:700;">ADMIN</span><?php endif; ?>
        </div>
    </div>
</div>

<div class="admin-content">
