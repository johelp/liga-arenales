<?php
ob_start();
require_once 'config.php';
session_start();

$pdo = conectarDB();

// Torneo activo por defecto
$torneo_id   = filter_input(INPUT_GET, 'torneo_id',   FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$tab         = in_array($_GET['tab'] ?? '', ['tabla','resultados','proximos','fixture']) ? $_GET['tab'] : 'tabla';

$torneos = $pdo->query("SELECT id_torneo, nombre, activo FROM torneos ORDER BY activo DESC, fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);

if (!$torneo_id) {
    $torneo_id = $pdo->query("SELECT id_torneo FROM torneos WHERE activo=1 ORDER BY fecha_inicio DESC LIMIT 1")->fetchColumn() ?: null;
}

$divisiones = [];
if ($torneo_id) {
    $stmt = $pdo->prepare("SELECT DISTINCT d.id_division, d.nombre FROM divisiones d JOIN clubes_en_division ced ON d.id_division=ced.id_division WHERE ced.id_torneo=? ORDER BY d.orden, d.nombre");
    $stmt->execute([$torneo_id]);
    $divisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$division_id && !empty($divisiones)) {
        $division_id = $divisiones[0]['id_division'];
    }
}

$torneo_nombre   = '';
$division_nombre = '';
foreach ($torneos as $t)   { if ($t['id_torneo']   == $torneo_id)   $torneo_nombre   = $t['nombre']; }
foreach ($divisiones as $d) { if ($d['id_division'] == $division_id) $division_nombre = $d['nombre']; }

$base_url    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$widget_base = $base_url . '/liga/widget.php';

$mostrar_admin = isset($_SESSION['admin_autenticado']) && $_SESSION['admin_autenticado'] === true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal oficial de la Liga Deportiva de General Arenales - Resultados, tablas de posiciones y próximos partidos.">
    <title>Liga Deportiva de General Arenales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root {
        --blue:  #004386;
        --blue2: #002f63;
        --gold:  #f0a500;
        --bg:    #f0f4f8;
    }
    * { box-sizing: border-box; }
    body { background: var(--bg); font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; }

    /* ── Header ── */
    .site-header {
        background: linear-gradient(135deg, var(--blue) 0%, var(--blue2) 100%);
        color: #fff;
        padding: 1.4rem 1rem 1rem;
        position: relative;
    }
    .site-header .brand-title {
        font-size: clamp(1.1rem, 4vw, 1.6rem);
        font-weight: 800;
        letter-spacing: -.01em;
        line-height: 1.2;
    }
    .site-header .brand-sub { font-size: .82rem; opacity: .75; }
    .site-header .header-badge {
        background: var(--gold);
        color: #1a1a1a;
        font-size: .72rem;
        font-weight: 700;
        padding: .18rem .55rem;
        border-radius: 20px;
    }
    .admin-link {
        position: absolute;
        top: .8rem; right: 1rem;
        color: rgba(255,255,255,.7);
        font-size: .78rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .5rem;
        border-radius: 6px;
        transition: background .2s;
    }
    .admin-link:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ── Filter bar ── */
    .filter-bar {
        background: #fff;
        border-bottom: 1px solid #e0e6ee;
        padding: .6rem 1rem;
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 6px rgba(0,0,0,.07);
    }
    .filter-bar select {
        font-size: .84rem;
        border-radius: 8px;
        border: 1px solid #d0d8e4;
        padding: .35rem .7rem;
        color: #333;
        background: #f7f9fc;
        flex: 1;
        min-width: 120px;
        max-width: 260px;
    }
    .filter-bar select:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(0,67,134,.12); }

    /* ── Tab nav ── */
    .tab-nav {
        background: #fff;
        border-bottom: 1px solid #e0e6ee;
        display: flex;
        overflow-x: auto;
        scrollbar-width: none;
        padding: 0 .5rem;
        gap: .1rem;
    }
    .tab-nav::-webkit-scrollbar { display: none; }
    .tab-nav a {
        white-space: nowrap;
        padding: .75rem 1rem;
        font-size: .85rem;
        font-weight: 600;
        color: #666;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        display: flex;
        align-items: center;
        gap: .35rem;
        transition: color .15s;
    }
    .tab-nav a:hover { color: var(--blue); }
    .tab-nav a.active { color: var(--blue); border-bottom-color: var(--blue); }

    /* ── Widget container ── */
    .widget-wrap {
        max-width: 900px;
        margin: 1.2rem auto;
        padding: 0 .75rem;
    }
    .widget-wrap iframe {
        width: 100%;
        border: none;
        border-radius: 14px;
        background: #fff;
        display: block;
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
        min-height: 200px;
    }

    /* ── Footer ── */
    .site-footer {
        text-align: center;
        padding: 2rem 1rem 3rem;
        color: #888;
        font-size: .8rem;
    }
    .site-footer a { color: var(--blue); text-decoration: none; }
    </style>
</head>
<body>

<!-- Header -->
<header class="site-header">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi bi-trophy-fill" style="color:var(--gold); font-size:1.5rem;"></i>
        <div>
            <div class="brand-title">Liga Deportiva de General Arenales</div>
            <div class="brand-sub">AscensionDigital.ar &middot; FM Encuentro 103.1 Mhz</div>
        </div>
    </div>
    <?php if ($torneo_nombre): ?>
    <span class="header-badge mt-1"><i class="bi bi-circle-fill" style="font-size:.5rem; vertical-align:middle;"></i> <?= htmlspecialchars($torneo_nombre) ?><?= $division_nombre ? ' · ' . htmlspecialchars($division_nombre) : '' ?></span>
    <?php endif; ?>
    <a href="admin/index.php" class="admin-link"><i class="bi bi-gear-fill"></i> <span class="d-none d-sm-inline">Admin</span></a>
</header>

<!-- Filter bar -->
<div class="filter-bar">
    <i class="bi bi-funnel text-muted" style="font-size:.9rem;"></i>
    <select id="sel-torneo" onchange="actualizarDivisiones(this.value)">
        <?php foreach ($torneos as $t): ?>
        <option value="<?= $t['id_torneo'] ?>" <?= $t['id_torneo'] == $torneo_id ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['nombre']) ?><?= $t['activo'] ? ' ✓' : '' ?>
        </option>
        <?php endforeach; ?>
    </select>
    <select id="sel-division" onchange="navegarFiltros()">
        <?php foreach ($divisiones as $d): ?>
        <option value="<?= $d['id_division'] ?>" <?= $d['id_division'] == $division_id ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Tab nav -->
<nav class="tab-nav">
    <?php
    $tabs = [
        'tabla'      => ['Tabla',     'bi-table'],
        'resultados' => ['Resultados','bi-check-circle-fill'],
        'proximos'   => ['Próximos',  'bi-calendar-event-fill'],
        'fixture'    => ['Fixture',   'bi-calendar3-range-fill'],
    ];
    foreach ($tabs as $k => [$label, $icon]):
        $href = '?torneo_id=' . (int)$torneo_id . '&division_id=' . (int)$division_id . '&tab=' . $k;
    ?>
    <a href="<?= $href ?>" class="<?= $tab === $k ? 'active' : '' ?>">
        <i class="bi <?= $icon ?>"></i> <?= $label ?>
    </a>
    <?php endforeach; ?>
</nav>

<!-- Widget iframe -->
<div class="widget-wrap">
    <?php if ($torneo_id && $division_id): ?>
    <?php
    $wparams = http_build_query([
        'tipo'           => $tab,
        'torneo'         => $torneo_id,
        'division'       => $division_id,
        'theme'          => 'light',
        'mostrar_header' => '0',
        'limite'         => $tab === 'fixture' ? 200 : 20,
    ]);
    $widget_url = $widget_base . '?' . $wparams;
    ?>
    <iframe id="widget-frame" src="<?= htmlspecialchars($widget_url) ?>"
            height="520" scrolling="no"
            style="transition: height .2s ease;"></iframe>
    <?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-funnel" style="font-size:2.5rem; opacity:.3;"></i>
        <p class="mt-2">Seleccioná un torneo y una división para ver el contenido.</p>
    </div>
    <?php endif; ?>
</div>

<footer class="site-footer">
    &copy; <?= date('Y') ?> Liga Deportiva de General Arenales &mdash;
    Desarrollado por <a href="https://ascensiondigital.ar" target="_blank" rel="noopener">AscensionDigital.ar</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-resize iframe from widget postMessage
window.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'ligaWidgetResize') {
        const f = document.getElementById('widget-frame');
        if (f) f.style.height = (e.data.height + 16) + 'px';
    }
});

function navegarFiltros() {
    const t = document.getElementById('sel-torneo').value;
    const d = document.getElementById('sel-division').value;
    const tab = new URLSearchParams(window.location.search).get('tab') || 'tabla';
    window.location.href = '?torneo_id=' + t + '&division_id=' + d + '&tab=' + tab;
}

function actualizarDivisiones(torneoId) {
    const sel = document.getElementById('sel-division');
    sel.innerHTML = '<option>Cargando…</option>';
    sel.disabled = true;
    fetch('get_divisiones.php?torneo_id=' + torneoId)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '';
            data.forEach(d => {
                const o = document.createElement('option');
                o.value = d.id_division;
                o.textContent = d.nombre;
                sel.appendChild(o);
            });
            sel.disabled = false;
            navegarFiltros();
        })
        .catch(() => { sel.innerHTML = '<option value="">Sin divisiones</option>'; sel.disabled = false; });
}
</script>
</body>
</html>
