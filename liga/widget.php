<?php
/**
 * widget.php — Widget embebible para WordPress / cualquier web
 *
 * Parámetros GET:
 *   tipo        = tabla | resultados | proximos | fixture | live   (default: tabla)
 *   torneo      = id_torneo   (default: torneo activo)
 *   division    = id_division (default: primera división disponible)
 *   limite      = N           (default: 8)
 *   fase        = "Primera Fase" | "Cuartos de Final" | …  (default: todas/clasificatoria)
 *   theme       = light | dark  (default: light)
 *   mostrar_header = 0|1       (default: 1)
 *   titulo_custom = texto libre
 *
 * Uso en WordPress:
 *   <iframe src="https://tudominio.com/liga/widget.php?tipo=tabla&division=1"
 *           width="100%" height="520" frameborder="0" scrolling="no"
 *           style="border:none; width:100%;"></iframe>
 *
 * Para auto-resize agregar al WordPress:
 *   <script src="https://tudominio.com/liga/widget-resize.js"></script>
 */

require_once 'config.php';
$pdo = conectarDB();

// ── Parámetros ──────────────────────────────────────────────────
$tipo           = in_array($_GET['tipo'] ?? '', ['tabla','resultados','proximos','fixture','live'])
                    ? $_GET['tipo'] : 'tabla';
$theme          = ($_GET['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$limite         = min(max((int)($_GET['limite'] ?? 8), 1), 50);
$mostrar_header = ($_GET['mostrar_header'] ?? '1') !== '0';
$titulo_custom  = htmlspecialchars($_GET['titulo_custom'] ?? '');
$fase_get       = $_GET['fase'] ?? '';

// ── Torneo ──────────────────────────────────────────────────────
$id_torneo = filter_input(INPUT_GET, 'torneo', FILTER_VALIDATE_INT);
if (!$id_torneo) {
    $row = $pdo->query("SELECT id_torneo FROM torneos WHERE activo=1 ORDER BY fecha_inicio DESC LIMIT 1")->fetch();
    $id_torneo = $row ? (int)$row['id_torneo'] : null;
}
$torneo_info = null;
if ($id_torneo) {
    $torneo_info = $pdo->prepare("SELECT * FROM torneos WHERE id_torneo=?");
    $torneo_info->execute([$id_torneo]);
    $torneo_info = $torneo_info->fetch(PDO::FETCH_ASSOC);
}

// ── División ────────────────────────────────────────────────────
$id_division = filter_input(INPUT_GET, 'division', FILTER_VALIDATE_INT);
if (!$id_division && $id_torneo) {
    $row = $pdo->prepare("SELECT d.id_division FROM divisiones d JOIN clubes_en_division ced ON d.id_division=ced.id_division WHERE ced.id_torneo=? ORDER BY d.orden, d.nombre LIMIT 1");
    $row->execute([$id_torneo]);
    $r = $row->fetch();
    $id_division = $r ? (int)$r['id_division'] : null;
}
$division_info = null;
if ($id_division) {
    $division_info = $pdo->prepare("SELECT * FROM divisiones WHERE id_division=?");
    $division_info->execute([$id_division]);
    $division_info = $division_info->fetch(PDO::FETCH_ASSOC);
}

// ── Datos según tipo ────────────────────────────────────────────
$datos = [];

if ($tipo === 'tabla') {
    require_once 'tabla_posiciones.php';
    if ($id_torneo && $id_division) {
        $datos = generarTablaPosiciones($pdo, $id_torneo, $id_division, $fase_get ?: null);
    }

} elseif ($tipo === 'resultados') {
    if ($id_torneo) {
        $sql = "SELECT p.id_partido, p.fecha_hora, p.goles_local, p.goles_visitante, p.fase, p.fecha_numero, p.estadio,
                       cl.nombre_corto AS local, cl.escudo_url AS escudo_local,
                       cv.nombre_corto AS visitante, cv.escudo_url AS escudo_visitante,
                       d.nombre AS division
                FROM partidos p
                JOIN clubes cl ON p.id_club_local=cl.id_club
                JOIN clubes cv ON p.id_club_visitante=cv.id_club
                JOIN divisiones d ON p.id_division=d.id_division
                WHERE p.jugado=1 AND p.id_torneo=?";
        $params = [$id_torneo];
        if ($id_division) { $sql .= " AND p.id_division=?"; $params[] = $id_division; }
        if ($fase_get)     { $sql .= " AND p.fase=?"; $params[] = $fase_get; }
        $sql .= " ORDER BY p.fecha_hora DESC LIMIT ?";
        $params[] = $limite;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} elseif ($tipo === 'proximos') {
    if ($id_torneo) {
        $sql = "SELECT p.id_partido, p.fecha_hora, p.fase, p.fecha_numero, p.estadio,
                       COALESCE(p.estado,'programado') AS estado, p.fecha_hora_original, p.motivo_reprogramacion,
                       cl.nombre_corto AS local, cl.escudo_url AS escudo_local,
                       cv.nombre_corto AS visitante, cv.escudo_url AS escudo_visitante,
                       d.nombre AS division
                FROM partidos p
                JOIN clubes cl ON p.id_club_local=cl.id_club
                JOIN clubes cv ON p.id_club_visitante=cv.id_club
                JOIN divisiones d ON p.id_division=d.id_division
                WHERE p.jugado=0 AND p.id_torneo=? AND (p.fecha_hora >= NOW() OR COALESCE(p.estado,'programado') IN ('reprogramado','suspendido'))";
        $params = [$id_torneo];
        if ($id_division) { $sql .= " AND p.id_division=?"; $params[] = $id_division; }
        if ($fase_get)     { $sql .= " AND p.fase=?"; $params[] = $fase_get; }
        $sql .= " ORDER BY p.fecha_hora ASC LIMIT ?";
        $params[] = $limite;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} elseif ($tipo === 'fixture') {
    // Fixture completo: todos los partidos (jugados y pendientes) agrupados por fecha/jornada
    if ($id_torneo) {
        $sql = "SELECT p.id_partido, p.fecha_hora, p.goles_local, p.goles_visitante, p.jugado, p.fase, p.fecha_numero, p.estadio,
                       COALESCE(p.estado,'programado') AS estado, p.fecha_hora_original, p.motivo_reprogramacion,
                       cl.nombre_corto AS local, cl.escudo_url AS escudo_local,
                       cv.nombre_corto AS visitante, cv.escudo_url AS escudo_visitante,
                       d.nombre AS division
                FROM partidos p
                JOIN clubes cl ON p.id_club_local=cl.id_club
                JOIN clubes cv ON p.id_club_visitante=cv.id_club
                JOIN divisiones d ON p.id_division=d.id_division
                WHERE p.id_torneo=?";
        $params = [$id_torneo];
        if ($id_division) { $sql .= " AND p.id_division=?"; $params[] = $id_division; }
        if ($fase_get)     { $sql .= " AND p.fase=?"; $params[] = $fase_get; }
        $sql .= " ORDER BY p.fecha_numero, p.fecha_hora";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Agrupar por fecha_numero
        foreach ($raw as $p) {
            $k = $p['fecha_numero'] ?? 'S/F';
            $datos[$k][] = $p;
        }
    }

} elseif ($tipo === 'live') {
    // Partidos del día de hoy no jugados aún (para modo live/streaming)
    if ($id_torneo) {
        $sql = "SELECT p.id_partido, p.fecha_hora, p.goles_local, p.goles_visitante, p.jugado, p.fase, p.estadio,
                       cl.nombre_corto AS local, cl.escudo_url AS escudo_local,
                       cv.nombre_corto AS visitante, cv.escudo_url AS escudo_visitante,
                       d.nombre AS division
                FROM partidos p
                JOIN clubes cl ON p.id_club_local=cl.id_club
                JOIN clubes cv ON p.id_club_visitante=cv.id_club
                JOIN divisiones d ON p.id_division=d.id_division
                WHERE p.id_torneo=? AND DATE(p.fecha_hora) = CURDATE()";
        $params = [$id_torneo];
        if ($id_division) { $sql .= " AND p.id_division=?"; $params[] = $id_division; }
        $sql .= " ORDER BY p.fecha_hora";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ── Títulos automáticos ─────────────────────────────────────────
$titulos = [
    'tabla'      => 'Tabla de Posiciones',
    'resultados' => 'Últimos Resultados',
    'proximos'   => 'Próximos Partidos',
    'fixture'    => 'Fixture',
    'live'       => 'EN VIVO · HOY',
];
$titulo = $titulo_custom ?: $titulos[$tipo];

// ── Colores por tema ────────────────────────────────────────────
$is_dark   = $theme === 'dark';
$bg        = $is_dark ? '#1a1d23' : '#ffffff';
$bg_card   = $is_dark ? '#22262e' : '#f8f9fa';
$text      = $is_dark ? '#e8eaf0' : '#212529';
$text_muted= $is_dark ? '#8a95a3' : '#6c757d';
$border    = $is_dark ? '#2e333d' : '#e9ecef';
$accent    = '#004386';
$accent2   = '#f0a500';

header('X-Frame-Options: ALLOWALL');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $titulo ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 14px; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: <?= $bg ?>;
    color: <?= $text ?>;
    line-height: 1.4;
    overflow-x: hidden;
}

/* ── Header ── */
.w-header {
    background: <?= $accent ?>;
    color: #fff;
    padding: .6rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.w-header .title  { font-weight: 700; font-size: .95rem; }
.w-header .sub    { font-size: .72rem; opacity: .75; }
.w-header .live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ff3b30;
    display: inline-block;
    margin-right: .35rem;
    animation: blink 1s infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }

/* ── Partido row ── */
.partido-row {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: .5rem;
    padding: .65rem .85rem;
    border-bottom: 1px solid <?= $border ?>;
}
.partido-row:last-child { border-bottom: none; }
.partido-row:hover { background: <?= $bg_card ?>; }

.equipo { display: flex; align-items: center; gap: .4rem; }
.equipo.local     { justify-content: flex-end; text-align: right; }
.equipo.visitante { justify-content: flex-start; text-align: left; }
.equipo img { width: 28px; height: 28px; object-fit: contain; }
.equipo .nombre { font-weight: 600; font-size: .85rem; }

.marcador {
    text-align: center;
    min-width: 56px;
}
.marcador .score {
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: .05em;
    color: <?= $is_dark ? '#fff' : '#212529' ?>;
}
.marcador .vs { font-size: .75rem; color: <?= $text_muted ?>; font-weight: 500; }
.marcador .hora { font-size: .78rem; color: <?= $text_muted ?>; }
.marcador .live-score {
    font-size: 1.25rem;
    font-weight: 900;
    color: #ff3b30;
}

/* ── Jornada header (fixture) ── */
.jornada-header {
    background: <?= $bg_card ?>;
    padding: .4rem .85rem;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: <?= $text_muted ?>;
    border-bottom: 1px solid <?= $border ?>;
    display: flex;
    justify-content: space-between;
}

/* ── Tabla posiciones ── */
.pos-table { width: 100%; border-collapse: collapse; }
.pos-table th {
    background: <?= $accent ?>;
    color: #fff;
    padding: .4rem .5rem;
    text-align: center;
    font-size: .72rem;
    font-weight: 600;
}
.pos-table th.col-club { text-align: left; }
.pos-table td {
    padding: .45rem .5rem;
    text-align: center;
    font-size: .82rem;
    border-bottom: 1px solid <?= $border ?>;
}
.pos-table td.col-club {
    text-align: left;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.pos-table td.col-club img { width: 20px; height: 20px; object-fit: contain; }
.pos-table .pts { font-weight: 800; color: <?= $accent ?>; }
.pos-table tr.pos-1 td { background: rgba(25,135,84,.12); }
.pos-table tr.pos-ult td { background: rgba(220,53,69,.07); }
.pos-table tr:hover td { background: <?= $bg_card ?>; }

/* ── Forma (últimos 5) ── */
.forma { display: flex; gap: 2px; justify-content: center; }
.forma span {
    width: 16px; height: 16px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .58rem; font-weight: 700; color: #fff;
}
.forma .G { background: #198754; }
.forma .E { background: #ffc107; color: #333; }
.forma .P { background: #dc3545; }
.forma .dash { background: #ccc; }

/* ── Empty state ── */
.empty {
    text-align: center;
    padding: 2rem 1rem;
    color: <?= $text_muted ?>;
}
.empty i { font-size: 2rem; display: block; margin-bottom: .5rem; }

/* ── Footer ── */
.w-footer {
    padding: .4rem .85rem;
    font-size: .68rem;
    color: <?= $text_muted ?>;
    text-align: center;
    border-top: 1px solid <?= $border ?>;
}
.w-footer a { color: <?= $text_muted ?>; }

/* ── Live badge ── */
.badge-live   { background: #ff3b30; color: #fff; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; font-weight: 700; }
.badge-jugado { background: #198754; color: #fff; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; }
.badge-pend   { background: #ffc107; color: #333; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; }
.badge-reprog { background: #fd7e14; color: #fff; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; font-weight: 700; }
.badge-susp   { background: #495057; color: #fff; font-size: .65rem; padding: .15rem .4rem; border-radius: 4px; }
.partido-row.is-suspendido  { opacity: .6; }
.partido-row.is-reprogramado .marcador { color: #fd7e14; }
</style>
</head>
<body>

<?php if ($mostrar_header): ?>
<div class="w-header">
    <div>
        <?php if ($tipo === 'live'): ?>
        <span class="live-dot"></span>
        <?php endif; ?>
        <span class="title"><?= $titulo ?></span>
        <?php if ($torneo_info): ?>
        <div class="sub"><?= htmlspecialchars($torneo_info['nombre']) ?>
            <?php if ($division_info): ?> · <?= htmlspecialchars($division_info['nombre']) ?><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($tipo === 'live'): ?>
    <span style="font-size:.72rem; opacity:.85"><?= date('H:i') ?></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════ TABLA -->
<?php if ($tipo === 'tabla'): ?>

<?php if (empty($datos)): ?>
<div class="empty"><i class="bi bi-table"></i>Sin datos disponibles.</div>
<?php else: ?>
<table class="pos-table">
<thead>
<tr>
    <th style="width:28px">#</th>
    <th class="col-club" style="text-align:left">Club</th>
    <th>PJ</th>
    <th>PG</th>
    <th>PE</th>
    <th>PP</th>
    <th>GF</th>
    <th>GC</th>
    <th>DG</th>
    <th>Pts</th>
    <th class="d-none d-sm-table-cell">Forma</th>
</tr>
</thead>
<tbody>
<?php $pos = 1; $total = count($datos); foreach ($datos as $eq):
    $rowclass = $pos === 1 ? 'pos-1' : ($pos === $total && $total > 6 ? 'pos-ult' : '');
?>
<tr class="<?= $rowclass ?>">
    <td><?= $pos ?></td>
    <td class="col-club">
        <?php if ($eq['escudo_url']): ?>
            <img src="<?= htmlspecialchars($eq['escudo_url']) ?>" alt="">
        <?php else: ?>
            <i class="bi bi-shield-fill" style="color:#aaa;font-size:.9rem"></i>
        <?php endif; ?>
        <?= htmlspecialchars($eq['nombre_corto']) ?>
    </td>
    <td><?= $eq['PJ'] ?></td>
    <td><?= $eq['PG'] ?></td>
    <td><?= $eq['PE'] ?></td>
    <td><?= $eq['PP'] ?></td>
    <td><?= $eq['GF'] ?></td>
    <td><?= $eq['GC'] ?></td>
    <td><?= $eq['DG'] > 0 ? '+' . $eq['DG'] : $eq['DG'] ?></td>
    <td class="pts"><?= $eq['Pts'] ?></td>
    <td class="d-none d-sm-table-cell">
        <div class="forma">
        <?php foreach ($eq['ultimos_5'] as $r):
            $cls = $r === 'G' ? 'G' : ($r === 'E' ? 'E' : ($r === 'P' ? 'P' : 'dash'));
        ?>
            <span class="<?= $cls ?>"><?= $r !== '-' ? $r : '' ?></span>
        <?php endforeach; ?>
        </div>
    </td>
</tr>
<?php $pos++; endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════ RESULTADOS -->
<?php elseif ($tipo === 'resultados'): ?>

<?php if (empty($datos)): ?>
<div class="empty"><i class="bi bi-calendar-x"></i>Sin resultados disponibles.</div>
<?php else: ?>
<?php foreach ($datos as $p): ?>
<div class="partido-row">
    <div class="equipo local">
        <span class="nombre"><?= htmlspecialchars($p['local']) ?></span>
        <?php if ($p['escudo_local']): ?>
            <img src="<?= htmlspecialchars($p['escudo_local']) ?>" alt="">
        <?php endif; ?>
    </div>
    <div class="marcador">
        <div class="score"><?= $p['goles_local'] ?> – <?= $p['goles_visitante'] ?></div>
        <div class="hora"><?= date('d/m H:i', strtotime($p['fecha_hora'])) ?></div>
        <?php if ($p['fase'] && $p['fase'] !== 'Primera Fase'): ?>
        <div style="font-size:.65rem; color:<?= $text_muted ?>; margin-top:.15rem"><?= htmlspecialchars($p['fase']) ?></div>
        <?php endif; ?>
    </div>
    <div class="equipo visitante">
        <?php if ($p['escudo_visitante']): ?>
            <img src="<?= htmlspecialchars($p['escudo_visitante']) ?>" alt="">
        <?php endif; ?>
        <span class="nombre"><?= htmlspecialchars($p['visitante']) ?></span>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════ PROXIMOS -->
<?php elseif ($tipo === 'proximos'): ?>

<?php if (empty($datos)): ?>
<div class="empty"><i class="bi bi-calendar-check"></i>Sin próximos partidos.</div>
<?php else: ?>
<?php foreach ($datos as $p):
    $ep = $p['estado'] ?? 'programado';
    $rowcls = $ep === 'suspendido' ? 'is-suspendido' : ($ep === 'reprogramado' ? 'is-reprogramado' : '');
?>
<div class="partido-row <?= $rowcls ?>">
    <div class="equipo local">
        <span class="nombre"><?= htmlspecialchars($p['local']) ?></span>
        <?php if ($p['escudo_local']): ?>
            <img src="<?= htmlspecialchars($p['escudo_local']) ?>" alt="">
        <?php endif; ?>
    </div>
    <div class="marcador">
        <?php if ($ep === 'suspendido'): ?>
            <span class="badge-susp">SUSPENDIDO</span>
            <?php if ($p['motivo_reprogramacion']): ?>
            <div style="font-size:.6rem; color:<?= $text_muted ?>; margin-top:.2rem">
                <?= htmlspecialchars($p['motivo_reprogramacion']) ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="vs">VS</div>
            <div class="hora"><?= date('d/m', strtotime($p['fecha_hora'])) ?></div>
            <div class="hora"><?= date('H:i', strtotime($p['fecha_hora'])) ?></div>
            <?php if ($ep === 'reprogramado'): ?>
            <span class="badge-reprog">REPROG.</span>
            <?php if ($p['fecha_hora_original']): ?>
            <div style="font-size:.6rem; color:<?= $text_muted ?>; margin-top:.15rem">
                antes: <?= date('d/m H:i', strtotime($p['fecha_hora_original'])) ?>
            </div>
            <?php endif; ?>
            <?php elseif ($p['estadio']): ?>
            <div style="font-size:.6rem; color:<?= $text_muted ?>; margin-top:.1rem">
                <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($p['estadio']) ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="equipo visitante">
        <?php if ($p['escudo_visitante']): ?>
            <img src="<?= htmlspecialchars($p['escudo_visitante']) ?>" alt="">
        <?php endif; ?>
        <span class="nombre"><?= htmlspecialchars($p['visitante']) ?></span>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════ FIXTURE -->
<?php elseif ($tipo === 'fixture'): ?>

<?php if (empty($datos)): ?>
<div class="empty"><i class="bi bi-calendar3"></i>Sin partidos en el fixture.</div>
<?php else: ?>
<?php foreach ($datos as $nro_fecha => $partidos_fecha): ?>
<div class="jornada-header">
    <span><?= is_numeric($nro_fecha) ? 'Fecha ' . $nro_fecha : $nro_fecha ?></span>
    <span><?= date('d/m/Y', strtotime($partidos_fecha[0]['fecha_hora'])) ?></span>
</div>
<?php foreach ($partidos_fecha as $p):
    $ep = $p['estado'] ?? 'programado';
    $rowcls = $ep === 'suspendido' ? 'is-suspendido' : ($ep === 'reprogramado' ? 'is-reprogramado' : '');
?>
<div class="partido-row <?= $rowcls ?>">
    <div class="equipo local">
        <span class="nombre"><?= htmlspecialchars($p['local']) ?></span>
        <?php if ($p['escudo_local']): ?>
            <img src="<?= htmlspecialchars($p['escudo_local']) ?>" alt="">
        <?php endif; ?>
    </div>
    <div class="marcador">
        <?php if ($ep === 'suspendido'): ?>
            <span class="badge-susp">SUSP.</span>
            <?php if ($p['motivo_reprogramacion']): ?>
            <div style="font-size:.58rem; color:<?= $text_muted ?>; margin-top:.15rem; white-space:normal">
                <?= htmlspecialchars($p['motivo_reprogramacion']) ?>
            </div>
            <?php endif; ?>
        <?php elseif ($p['jugado']): ?>
            <div class="score"><?= $p['goles_local'] ?> – <?= $p['goles_visitante'] ?></div>
        <?php else: ?>
            <div class="vs">VS</div>
            <div class="hora"><?= date('H:i', strtotime($p['fecha_hora'])) ?></div>
            <?php if ($ep === 'reprogramado'): ?>
            <span class="badge-reprog">REPROG.</span>
            <?php if ($p['fecha_hora_original']): ?>
            <div style="font-size:.58rem; color:<?= $text_muted ?>; margin-top:.1rem">
                antes: <?= date('d/m', strtotime($p['fecha_hora_original'])) ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="equipo visitante">
        <?php if ($p['escudo_visitante']): ?>
            <img src="<?= htmlspecialchars($p['escudo_visitante']) ?>" alt="">
        <?php endif; ?>
        <span class="nombre"><?= htmlspecialchars($p['visitante']) ?></span>
    </div>
</div>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════ LIVE -->
<?php elseif ($tipo === 'live'): ?>

<?php if (empty($datos)): ?>
<div class="empty">
    <i class="bi bi-broadcast"></i>
    No hay partidos programados para hoy.
</div>
<?php else: ?>
<?php foreach ($datos as $p): ?>
<div class="partido-row">
    <div class="equipo local">
        <span class="nombre"><?= htmlspecialchars($p['local']) ?></span>
        <?php if ($p['escudo_local']): ?>
            <img src="<?= htmlspecialchars($p['escudo_local']) ?>" alt="">
        <?php endif; ?>
    </div>
    <div class="marcador">
        <?php if ($p['jugado']): ?>
            <div class="live-score"><?= $p['goles_local'] ?> – <?= $p['goles_visitante'] ?></div>
            <span class="badge-jugado">FIN</span>
        <?php else: ?>
            <div class="vs">VS</div>
            <div class="hora"><?= date('H:i', strtotime($p['fecha_hora'])) ?></div>
            <span class="badge-pend">Próximo</span>
        <?php endif; ?>
        <?php if ($p['division']): ?>
        <div style="font-size:.62rem; color:<?= $text_muted ?>; margin-top:.2rem"><?= htmlspecialchars($p['division']) ?></div>
        <?php endif; ?>
    </div>
    <div class="equipo visitante">
        <?php if ($p['escudo_visitante']): ?>
            <img src="<?= htmlspecialchars($p['escudo_visitante']) ?>" alt="">
        <?php endif; ?>
        <span class="nombre"><?= htmlspecialchars($p['visitante']) ?></span>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; // fin switch tipo ?>

<div class="w-footer">
    Liga Deportiva · <a href="https://ascensiondigital.ar" target="_blank">AscensionDigital.ar</a>
</div>

<?php if ($tipo === 'live'): ?>
<!-- Auto-refresh cada 60 segundos en modo live -->
<script>setTimeout(()=>location.reload(), 60000);</script>
<?php endif; ?>

<!-- Notifica al padre el alto real para auto-resize del iframe -->
<script>
(function() {
    function notifyHeight() {
        var h = document.documentElement.scrollHeight;
        if (window.parent !== window) {
            window.parent.postMessage({ type: 'ligaWidgetResize', height: h }, '*');
        }
    }
    notifyHeight();
    new MutationObserver(notifyHeight).observe(document.body, { subtree: true, childList: true, attributes: true });
})();
</script>
</body>
</html>
