<?php
ob_start();
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = conectarDB();

$torneo_id   = filter_input(INPUT_GET, 'torneo_id',   FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$tipo        = in_array($_GET['tipo'] ?? '', ['tabla','resultados','proximos']) ? $_GET['tipo'] : 'tabla';

$torneos    = $pdo->query("SELECT id_torneo, nombre, activo FROM torneos ORDER BY activo DESC, fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$divisiones = [];

if (!$torneo_id) {
    $torneo_id = $pdo->query("SELECT id_torneo FROM torneos WHERE activo=1 ORDER BY fecha_inicio DESC LIMIT 1")->fetchColumn() ?: null;
}
if ($torneo_id) {
    $stmt = $pdo->prepare("SELECT DISTINCT d.id_division, d.nombre FROM divisiones d JOIN clubes_en_division ced ON d.id_division=ced.id_division WHERE ced.id_torneo=? ORDER BY d.orden, d.nombre");
    $stmt->execute([$torneo_id]);
    $divisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$division_id && !empty($divisiones)) $division_id = $divisiones[0]['id_division'];
}

$torneo_nombre = $division_nombre = '';
foreach ($torneos   as $t) { if ($t['id_torneo']   == $torneo_id)   $torneo_nombre   = $t['nombre']; }
foreach ($divisiones as $d) { if ($d['id_division'] == $division_id) $division_nombre = $d['nombre']; }

// ── Fetch data ────────────────────────────────────────────────────────────────
$tabla_rows     = [];
$resultados_rows = [];
$proximos_rows   = [];

if ($torneo_id && $division_id) {
    require_once '../tabla_posiciones.php';

    if ($tipo === 'tabla') {
        $tabla_rows = generarTablaPosiciones($pdo, $torneo_id, $division_id);
    }
    if ($tipo === 'resultados') {
        $s = $pdo->prepare("SELECT p.fecha_hora, p.fase, p.goles_local, p.goles_visitante,
               cl.nombre_corto AS local_nombre, cl.escudo_url AS local_escudo,
               cv.nombre_corto AS visitante_nombre, cv.escudo_url AS visitante_escudo
        FROM partidos p
        JOIN clubes cl ON p.id_club_local     = cl.id_club
        JOIN clubes cv ON p.id_club_visitante = cv.id_club
        WHERE p.id_torneo=? AND p.id_division=? AND p.jugado=1
        ORDER BY p.fecha_hora DESC LIMIT 12");
        $s->execute([$torneo_id, $division_id]);
        $resultados_rows = $s->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($tipo === 'proximos') {
        $s = $pdo->prepare("SELECT p.fecha_hora, p.fase,
               cl.nombre_corto AS local_nombre, cl.escudo_url AS local_escudo,
               cv.nombre_corto AS visitante_nombre, cv.escudo_url AS visitante_escudo
        FROM partidos p
        JOIN clubes cl ON p.id_club_local     = cl.id_club
        JOIN clubes cv ON p.id_club_visitante = cv.id_club
        WHERE p.id_torneo=? AND p.id_division=? AND p.jugado=0 AND p.fecha_hora>=NOW()
        ORDER BY p.fecha_hora ASC LIMIT 10");
        $s->execute([$torneo_id, $division_id]);
        $proximos_rows = $s->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function escudoHtml(string $url, string $nombre, int $size): string {
    $f = round($size * 0.35) . 'px';
    if ($url) {
        return "<img src=\"" . htmlspecialchars($url) . "\"
                     style=\"width:{$size}px;height:{$size}px;object-fit:contain;border-radius:50%;background:#fff;flex-shrink:0;\"
                     crossorigin=\"anonymous\">";
    }
    $ini = mb_strtoupper(mb_substr(trim($nombre), 0, 2));
    $paleta = ['#004386','#1565c0','#2e7d32','#b71c1c','#6a1599','#00695c','#e65100'];
    $bg = $paleta[abs(crc32($nombre)) % count($paleta)];
    return "<div style=\"width:{$size}px;height:{$size}px;border-radius:50%;background:{$bg};
                          display:flex;align-items:center;justify-content:center;
                          color:#fff;font-size:{$f};font-weight:900;flex-shrink:0;\">{$ini}</div>";
}

function px(int $n, float $scale): string { return round($n * $scale) . 'px'; }

include 'header.php';
?>
<style>
.gen-wrap { max-width:1160px; margin:0 auto; padding:1rem 1rem 4rem; }
.panel    { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.08); padding:1.2rem; margin-bottom:1.2rem; }
.preview-box {
    background:#111827; border-radius:12px; display:flex;
    align-items:center; justify-content:center;
    min-height:220px; position:relative; padding:12px;
}
.preview-loading {
    position:absolute; inset:0; display:flex; align-items:center;
    justify-content:center; flex-direction:column; gap:.5rem;
    color:rgba(255,255,255,.5); font-size:.85rem; border-radius:12px;
}
.img-tpl { position:fixed; left:-99999px; top:0; font-family:Arial,Helvetica,sans-serif; }
</style>

<div class="gen-wrap">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="../index.php" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0 fw-bold"><i class="bi bi-share-fill text-primary me-1"></i> Generar Imágenes para Redes</h5>
    </div>

    <!-- Config -->
    <div class="panel">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Tipo de imagen</label>
                <div class="d-flex gap-1 flex-wrap">
                    <?php foreach (['tabla'=>['Tabla','bi-table'],'resultados'=>['Resultados','bi-check2-circle'],'proximos'=>['Próximos','bi-calendar-event']] as $k=>[$lbl,$ic]): ?>
                    <a href="?torneo_id=<?= $torneo_id ?>&division_id=<?= $division_id ?>&tipo=<?= $k ?>"
                       class="btn btn-sm <?= $tipo===$k?'btn-primary':'btn-outline-secondary' ?> rounded-pill">
                        <i class="bi <?= $ic ?>"></i> <?= $lbl ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-semibold mb-1">Torneo</label>
                <select class="form-select form-select-sm" onchange="location='?torneo_id='+this.value+'&tipo=<?= $tipo ?>'">
                    <?php foreach ($torneos as $t): ?>
                    <option value="<?= $t['id_torneo'] ?>" <?= $t['id_torneo']==$torneo_id?'selected':'' ?>>
                        <?= htmlspecialchars($t['nombre']) ?><?= $t['activo']?' ✓':'' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-semibold mb-1">División</label>
                <select class="form-select form-select-sm" onchange="location='?torneo_id=<?= $torneo_id ?>&division_id='+this.value+'&tipo=<?= $tipo ?>'">
                    <?php foreach ($divisiones as $d): ?>
                    <option value="<?= $d['id_division'] ?>" <?= $d['id_division']==$division_id?'selected':'' ?>>
                        <?= htmlspecialchars($d['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>Los escudos deben estar en el mismo servidor.</div>
            </div>
        </div>
    </div>

    <?php if (!$torneo_id || !$division_id): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-funnel" style="font-size:3rem;opacity:.2;"></i>
        <p class="mt-2">Seleccioná torneo y división.</p>
    </div>
    <?php else: ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="panel">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small"><i class="bi bi-square me-1 text-primary"></i> Feed · 1080 × 1080</span>
                    <button class="btn btn-sm btn-primary" onclick="descargar('feed')">
                        <i class="bi bi-download me-1"></i> Descargar
                    </button>
                </div>
                <div class="preview-box" id="preview-feed">
                    <div class="preview-loading" id="loading-feed">
                        <div class="spinner-border spinner-border-sm text-light"></div>
                        Generando…
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small"><i class="bi bi-phone me-1 text-primary"></i> Stories · 1080 × 1920</span>
                    <button class="btn btn-sm btn-primary" onclick="descargar('stories')">
                        <i class="bi bi-download me-1"></i> Descargar
                    </button>
                </div>
                <div class="preview-box" id="preview-stories">
                    <div class="preview-loading" id="loading-stories">
                        <div class="spinner-border spinner-border-sm text-light"></div>
                        Generando…
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OFFSCREEN IMAGE TEMPLATES -->
    <?php
    $tipo_label = ['tabla'=>'TABLA DE POSICIONES','resultados'=>'ÚLTIMOS RESULTADOS','proximos'=>'PRÓXIMOS PARTIDOS'][$tipo];
    $hoy = date('d/m/Y');

    foreach (['feed'=>[1080,1080,14,1.0], 'stories'=>[1080,1920,22,1.3]] as $fmt=>[$W,$H,$maxRows,$sc]):
        $data = $tipo==='tabla' ? $tabla_rows : ($tipo==='resultados' ? $resultados_rows : $proximos_rows);
        $rows = array_slice($data, 0, $maxRows);
        $pad  = $fmt==='stories' ? 65 : 48;
        $iw   = $W - $pad*2;

        // Sizes scaled
        $font_lg  = (int)round(30*$sc);
        $font_md  = (int)round(18*$sc);
        $font_sm  = (int)round(13*$sc);
        $font_xs  = (int)round(11*$sc);
        $logo_sz  = (int)round(62*$sc);
        $shield_s = $fmt==='stories' ? 42 : 34;
        $row_h    = $fmt==='stories' ? ($tipo==='tabla' ? 66 : 90) : ($tipo==='tabla' ? 54 : 74);
        $hdr_h    = $fmt==='stories' ? 80 : 64;
        $col_h    = $fmt==='stories' ? 50 : 40;
        $gap      = $fmt==='stories' ? 22 : 14;
    ?>
    <div id="tpl-<?= $fmt ?>" class="img-tpl" style="width:<?= $W ?>px;height:<?= $H ?>px;background:linear-gradient(155deg,#004386 0%,#001d3d 55%,#003870 100%);overflow:hidden;position:relative;">

        <!-- Deco circles -->
        <div style="position:absolute;width:700px;height:700px;border-radius:50%;background:rgba(255,255,255,0.04);top:-280px;right:-220px;"></div>
        <div style="position:absolute;width:350px;height:350px;border-radius:50%;background:rgba(240,165,0,0.05);bottom:-80px;left:-80px;"></div>

        <!-- Content -->
        <div style="position:absolute;left:<?= $pad ?>px;top:<?= $pad ?>px;width:<?= $iw ?>px;display:flex;flex-direction:column;gap:<?= $gap ?>px;">

            <!-- Header row -->
            <div style="display:flex;align-items:center;gap:<?= round(16*$sc) ?>px;">
                <!-- Logo badge -->
                <div style="width:<?= $logo_sz ?>px;height:<?= $logo_sz ?>px;background:linear-gradient(135deg,#f0a500,#c77800);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 18px rgba(240,165,0,0.45);flex-shrink:0;">
                    <span style="font-size:<?= round(28*$sc) ?>px;">&#9733;</span>
                </div>
                <!-- League name -->
                <div style="flex:1;line-height:1.15;">
                    <div style="color:#fff;font-size:<?= $font_lg ?>px;font-weight:900;letter-spacing:0.5px;">LIGA DEPORTIVA</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:<?= round(13*$sc) ?>px;letter-spacing:2px;">DE GENERAL ARENALES</div>
                </div>
                <!-- Torneo + division badges -->
                <div style="text-align:right;">
                    <div style="background:rgba(240,165,0,0.18);border:1.5px solid rgba(240,165,0,0.55);color:#f0a500;padding:<?= round(5*$sc) ?>px <?= round(14*$sc) ?>px;border-radius:20px;font-size:<?= $font_xs ?>px;font-weight:700;display:inline-block;max-width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                        <?= htmlspecialchars($torneo_nombre) ?>
                    </div>
                    <div style="color:rgba(255,255,255,0.5);font-size:<?= $font_xs ?>px;margin-top:4px;">
                        <?= htmlspecialchars($division_nombre) ?>
                    </div>
                </div>
            </div>

            <!-- Main card -->
            <div style="background:rgba(255,255,255,0.97);border-radius:<?= round(16*$sc) ?>px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.35);">

                <!-- Card header -->
                <div style="background:linear-gradient(90deg,#004386,#0057b8);padding:0 <?= round(26*$sc) ?>px;height:<?= $hdr_h ?>px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="color:#fff;font-size:<?= round(20*$sc) ?>px;font-weight:900;letter-spacing:1.5px;"><?= $tipo_label ?></div>
                    <div style="color:rgba(255,255,255,0.55);font-size:<?= $font_sm ?>px;"><?= $hoy ?></div>
                </div>

                <?php if ($tipo === 'tabla'): ?>
                <!-- Table column headers -->
                <div style="display:flex;align-items:center;height:<?= $col_h ?>px;padding:0 <?= round(14*$sc) ?>px;background:#f4f6fa;border-bottom:2px solid #e8ecf0;font-size:<?= $font_xs ?>px;font-weight:700;color:#999;letter-spacing:0.8px;text-transform:uppercase;">
                    <div style="width:<?= round(40*$sc) ?>px;flex-shrink:0;">#</div>
                    <div style="width:<?= round(($shield_s+10)) ?>px;flex-shrink:0;"></div>
                    <div style="flex:1;">EQUIPO</div>
                    <?php foreach (['PJ','G','E','P','DIF','PTS'] as $col): ?>
                    <div style="width:<?= ($col==='DIF'||$col==='PTS' ? round(52*$sc) : round(40*$sc)) ?>px;text-align:center;flex-shrink:0;"><?= $col ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Table rows -->
                <?php
                $total = count($rows);
                $top_z = max(1, (int)ceil($total * 0.28));
                $rel_z = max(1, (int)floor($total * 0.15));
                foreach ($rows as $i => $r):
                    $pos     = $i + 1;
                    $bg_row  = $i % 2 === 0 ? '#ffffff' : '#f9fafc';
                    $zone_c  = $pos <= $top_z ? '#16a34a' : ($pos > $total - $rel_z ? '#dc2626' : 'transparent');
                    $dg      = isset($r['DG']) ? $r['DG'] : (($r['GF'] ?? 0) - ($r['GC'] ?? 0));
                    $dg_str  = ($dg > 0 ? '+' : '') . $dg;

                    // Position badge
                    if ($pos === 1)      { $pos_bg = '#f0a500'; $pos_c = '#fff'; }
                    elseif ($pos === 2)  { $pos_bg = '#b0b8c4'; $pos_c = '#fff'; }
                    elseif ($pos === 3)  { $pos_bg = '#c87832'; $pos_c = '#fff'; }
                    else                 { $pos_bg = 'transparent'; $pos_c = '#999'; }
                    $pos_badge_r = $pos <= 3 ? '50%' : '0';

                    $name_len  = mb_strlen($r['nombre_corto'] ?? '');
                    $name_font = $name_len > 16 ? round(12*$sc) : ($name_len > 12 ? round(14*$sc) : round(16*$sc));
                    $pts_w     = round(52*$sc);
                ?>
                <div style="display:flex;align-items:center;height:<?= $row_h ?>px;padding:0 <?= round(14*$sc) ?>px;background:<?= $bg_row ?>;border-left:4px solid <?= $zone_c ?>;border-bottom:1px solid #f0f2f5;">
                    <!-- Pos -->
                    <div style="width:<?= round(40*$sc) ?>px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                        <div style="width:<?= round(28*$sc) ?>px;height:<?= round(28*$sc) ?>px;border-radius:<?= $pos_badge_r ?>;background:<?= $pos_bg ?>;color:<?= $pos_c ?>;display:flex;align-items:center;justify-content:center;font-size:<?= round(13*$sc) ?>px;font-weight:900;"><?= $pos ?></div>
                    </div>
                    <!-- Shield -->
                    <div style="width:<?= $shield_s+10 ?>px;flex-shrink:0;display:flex;justify-content:center;">
                        <?= escudoHtml($r['escudo_url'] ?? '', $r['nombre_corto'] ?? '', $shield_s) ?>
                    </div>
                    <!-- Name -->
                    <div style="flex:1;font-size:<?= $name_font ?>px;font-weight:700;color:#1a1a2e;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                        <?= htmlspecialchars($r['nombre_corto'] ?? '') ?>
                    </div>
                    <!-- Stats -->
                    <?php foreach ([
                        ['w'=>round(40*$sc), 'v'=>$r['PJ']??0, 'bold'=>false],
                        ['w'=>round(40*$sc), 'v'=>$r['PG']??0, 'bold'=>false],
                        ['w'=>round(40*$sc), 'v'=>$r['PE']??0, 'bold'=>false],
                        ['w'=>round(40*$sc), 'v'=>$r['PP']??0, 'bold'=>false],
                        ['w'=>round(52*$sc), 'v'=>$dg_str,      'bold'=>false],
                        ['w'=>round(52*$sc), 'v'=>$r['Pts']??0, 'bold'=>true],
                    ] as $col): ?>
                    <div style="width:<?= $col['w'] ?>px;text-align:center;flex-shrink:0;font-size:<?= round(14*$sc) ?>px;font-weight:<?= $col['bold']?'900':'500' ?>;color:<?= $col['bold']?'#004386':'#555' ?>;">
                        <?= $col['v'] ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- Zone legend -->
                <?php if ($total > 3): ?>
                <div style="display:flex;gap:<?= round(16*$sc) ?>px;padding:<?= round(10*$sc) ?>px <?= round(20*$sc) ?>px;background:#f4f6fa;border-top:1px solid #e8ecf0;">
                    <div style="display:flex;align-items:center;gap:5px;font-size:<?= $font_xs ?>px;color:#666;">
                        <div style="width:10px;height:10px;background:#16a34a;border-radius:2px;"></div> Ascenso/Clasificado
                    </div>
                    <div style="display:flex;align-items:center;gap:5px;font-size:<?= $font_xs ?>px;color:#666;">
                        <div style="width:10px;height:10px;background:#dc2626;border-radius:2px;"></div> Descenso/Eliminado
                    </div>
                </div>
                <?php endif; ?>

                <?php elseif ($tipo === 'resultados'): ?>

                <?php foreach ($rows as $i => $r):
                    $bg = $i % 2 === 0 ? '#fff' : '#f9fafc';
                    $gl = $r['goles_local'];
                    $gv = $r['goles_visitante'];
                    $local_win = $gl > $gv;
                    $visit_win = $gv > $gl;
                    $fecha = date('d/m', strtotime($r['fecha_hora']));
                ?>
                <div style="display:flex;align-items:center;height:<?= $row_h ?>px;padding:0 <?= round(14*$sc) ?>px;background:<?= $bg ?>;border-bottom:1px solid #f0f2f5;gap:<?= round(8*$sc) ?>px;">
                    <!-- Date -->
                    <div style="width:<?= round(50*$sc) ?>px;flex-shrink:0;text-align:center;">
                        <div style="font-size:<?= round(12*$sc) ?>px;font-weight:700;color:#004386;"><?= $fecha ?></div>
                        <?php if ($r['fase']): ?><div style="font-size:<?= round(10*$sc) ?>px;color:#aaa;"><?= htmlspecialchars(mb_strimwidth($r['fase'],0,10,'…')) ?></div><?php endif; ?>
                    </div>
                    <!-- Local -->
                    <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;gap:<?= round(8*$sc) ?>px;overflow:hidden;">
                        <div style="font-size:<?= round(15*$sc) ?>px;font-weight:<?= $local_win?'900':'600' ?>;color:<?= $local_win?'#004386':'#555' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= round(200*$sc) ?>px;">
                            <?= htmlspecialchars($r['local_nombre']) ?>
                        </div>
                        <?= escudoHtml($r['local_escudo']??'', $r['local_nombre'], $shield_s) ?>
                    </div>
                    <!-- Score -->
                    <div style="flex-shrink:0;text-align:center;">
                        <div style="background:#004386;color:#fff;border-radius:<?= round(8*$sc) ?>px;padding:<?= round(5*$sc) ?>px <?= round(10*$sc) ?>px;font-size:<?= round(22*$sc) ?>px;font-weight:900;letter-spacing:2px;min-width:<?= round(70*$sc) ?>px;display:inline-block;">
                            <?= $gl ?> : <?= $gv ?>
                        </div>
                    </div>
                    <!-- Visitante -->
                    <div style="flex:1;display:flex;align-items:center;gap:<?= round(8*$sc) ?>px;overflow:hidden;">
                        <?= escudoHtml($r['visitante_escudo']??'', $r['visitante_nombre'], $shield_s) ?>
                        <div style="font-size:<?= round(15*$sc) ?>px;font-weight:<?= $visit_win?'900':'600' ?>;color:<?= $visit_win?'#004386':'#555' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= round(200*$sc) ?>px;">
                            <?= htmlspecialchars($r['visitante_nombre']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php elseif ($tipo === 'proximos'): ?>

                <?php foreach ($rows as $i => $r):
                    $bg    = $i % 2 === 0 ? '#fff' : '#f9fafc';
                    $fecha = date('d/m', strtotime($r['fecha_hora']));
                    $hora  = date('H:i', strtotime($r['fecha_hora']));
                ?>
                <div style="display:flex;align-items:center;height:<?= $row_h ?>px;padding:0 <?= round(14*$sc) ?>px;background:<?= $bg ?>;border-bottom:1px solid #f0f2f5;gap:<?= round(8*$sc) ?>px;">
                    <!-- Date/time -->
                    <div style="width:<?= round(58*$sc) ?>px;flex-shrink:0;text-align:center;line-height:1.3;">
                        <div style="font-size:<?= round(14*$sc) ?>px;font-weight:900;color:#004386;"><?= $fecha ?></div>
                        <div style="font-size:<?= round(12*$sc) ?>px;color:#888;"><?= $hora ?></div>
                    </div>
                    <!-- Local -->
                    <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;gap:<?= round(8*$sc) ?>px;overflow:hidden;">
                        <div style="font-size:<?= round(15*$sc) ?>px;font-weight:700;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= round(200*$sc) ?>px;">
                            <?= htmlspecialchars($r['local_nombre']) ?>
                        </div>
                        <?= escudoHtml($r['local_escudo']??'', $r['local_nombre'], $shield_s) ?>
                    </div>
                    <!-- VS -->
                    <div style="flex-shrink:0;padding:0 <?= round(10*$sc) ?>px;font-size:<?= round(13*$sc) ?>px;font-weight:900;color:#aaa;letter-spacing:2px;">VS</div>
                    <!-- Visitante -->
                    <div style="flex:1;display:flex;align-items:center;gap:<?= round(8*$sc) ?>px;overflow:hidden;">
                        <?= escudoHtml($r['visitante_escudo']??'', $r['visitante_nombre'], $shield_s) ?>
                        <div style="font-size:<?= round(15*$sc) ?>px;font-weight:700;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= round(200*$sc) ?>px;">
                            <?= htmlspecialchars($r['visitante_nombre']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php endif; ?>

                <?php if (empty($rows)): ?>
                <div style="padding:<?= round(60*$sc) ?>px;text-align:center;color:#bbb;font-size:<?= round(18*$sc) ?>px;">Sin datos disponibles.</div>
                <?php endif; ?>

            </div><!-- /card -->

            <!-- Footer -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0 <?= round(6*$sc) ?>px;">
                <div style="color:rgba(255,255,255,0.35);font-size:<?= round(12*$sc) ?>px;letter-spacing:1px;">ascensiondigital.ar · FM ENCUENTRO 103.1</div>
                <div style="color:rgba(255,255,255,0.35);font-size:<?= round(12*$sc) ?>px;"><?= $hoy ?></div>
            </div>

        </div><!-- /content -->
    </div><!-- /tpl -->
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
const _canvases = {};

async function renderear(fmt) {
    const tpl  = document.getElementById('tpl-' + fmt);
    const box  = document.getElementById('preview-' + fmt);
    const load = document.getElementById('loading-' + fmt);
    const W    = fmt === 'feed' ? 1080 : 1080;
    const H    = fmt === 'feed' ? 1080 : 1920;

    try {
        const canvas = await html2canvas(tpl, {
            scale: 1, useCORS: true, allowTaint: false,
            backgroundColor: null, logging: false,
            width: W, height: H,
        });
        _canvases[fmt] = canvas;

        // Scale to fit preview box
        const boxW = box.clientWidth - 24;
        const scale = Math.min(boxW / W, fmt === 'stories' ? 0.38 : 0.48);
        const img   = document.createElement('img');
        img.src     = canvas.toDataURL('image/png');
        img.style.cssText = `width:${Math.round(W*scale)}px;height:${Math.round(H*scale)}px;display:block;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.6);`;

        box.innerHTML = '';
        box.style.minHeight = '';
        box.style.padding   = '10px';
        box.appendChild(img);
    } catch(e) {
        load.innerHTML = `<span style="color:#f87171;text-align:center;padding:1rem;">Error al generar.<br><small>${e.message}</small></span>`;
    }
}

function descargar(fmt) {
    if (!_canvases[fmt]) { alert('Esperá a que termine de generar la imagen.'); return; }
    const tipo = '<?= $tipo ?>';
    const div  = '<?= addslashes(preg_replace('/\s+/', '-', strtolower($division_nombre))) ?>';
    const a    = document.createElement('a');
    a.download  = `liga-${tipo}-${fmt}-${div}.png`;
    a.href      = _canvases[fmt].toDataURL('image/png');
    a.click();
}

window.addEventListener('load', async () => {
    <?php if ($torneo_id && $division_id): ?>
    await renderear('feed');
    await renderear('stories');
    <?php endif; ?>
});
</script>

<?php include 'footer.php'; ?>
