<?php
ob_start();
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    http_response_code(403); exit('Sin acceso');
}

$pdo         = conectarDB();
$torneo_id   = filter_input(INPUT_GET, 'torneo_id',   FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$tipo        = in_array($_GET['tipo'] ?? '', ['tabla','resultados','proximos']) ? $_GET['tipo'] : 'tabla';
$fmt         = ($_GET['fmt'] ?? 'feed') === 'stories' ? 'stories' : 'feed';
$autodownload = !empty($_GET['autodownload']);

$W = 1080;
$H = $fmt === 'stories' ? 1920 : 1080;
$sc = $fmt === 'stories' ? 1.3 : 1.0;
$pad = $fmt === 'stories' ? 65 : 48;
$iw  = $W - $pad * 2;
$maxRows = $fmt === 'stories' ? ($tipo === 'tabla' ? 22 : 14) : ($tipo === 'tabla' ? 14 : 8);

$torneo_nombre = $division_nombre = '';
$rows = [];

if ($torneo_id && $division_id) {
    $t = $pdo->prepare("SELECT nombre FROM torneos WHERE id_torneo=?");
    $t->execute([$torneo_id]); $torneo_nombre = $t->fetchColumn() ?: '';
    $d = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division=?");
    $d->execute([$division_id]); $division_nombre = $d->fetchColumn() ?: '';

    require_once '../tabla_posiciones.php';

    if ($tipo === 'tabla') {
        $rows = array_values(generarTablaPosiciones($pdo, $torneo_id, $division_id));
    } elseif ($tipo === 'resultados') {
        $s = $pdo->prepare("SELECT p.fecha_hora, p.fase, p.goles_local, p.goles_visitante,
               cl.nombre_corto AS local_nombre, cl.escudo_url AS local_escudo,
               cv.nombre_corto AS visitante_nombre, cv.escudo_url AS visitante_escudo
        FROM partidos p
        JOIN clubes cl ON p.id_club_local=cl.id_club
        JOIN clubes cv ON p.id_club_visitante=cv.id_club
        WHERE p.id_torneo=? AND p.id_division=? AND p.jugado=1
        ORDER BY p.fecha_hora DESC LIMIT 14");
        $s->execute([$torneo_id, $division_id]);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $s = $pdo->prepare("SELECT p.fecha_hora, p.fase,
               cl.nombre_corto AS local_nombre, cl.escudo_url AS local_escudo,
               cv.nombre_corto AS visitante_nombre, cv.escudo_url AS visitante_escudo
        FROM partidos p
        JOIN clubes cl ON p.id_club_local=cl.id_club
        JOIN clubes cv ON p.id_club_visitante=cv.id_club
        WHERE p.id_torneo=? AND p.id_division=? AND p.jugado=0 AND p.fecha_hora>=NOW()
        ORDER BY p.fecha_hora ASC LIMIT 12");
        $s->execute([$torneo_id, $division_id]);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    }
}

$rows = array_slice($rows, 0, $maxRows);
$tipo_label = ['tabla'=>'TABLA DE POSICIONES','resultados'=>'ÚLTIMOS RESULTADOS','proximos'=>'PRÓXIMOS PARTIDOS'][$tipo];
$hoy = date('d/m/Y');

// Escudo helper
function esc(string $url, string $nombre, int $size): string {
    $f = round($size * 0.38) . 'px';
    if ($url) {
        return "<img src=\"" . htmlspecialchars($url) . "\"
                     style=\"width:{$size}px;height:{$size}px;object-fit:contain;border-radius:50%;background:#fff;flex-shrink:0;display:block;\"
                     crossorigin=\"anonymous\">";
    }
    $ini = mb_strtoupper(mb_substr(trim($nombre), 0, 2));
    $paleta = ['#004386','#1565c0','#2e7d32','#b71c1c','#6a1599','#00695c','#e65100'];
    $bg = $paleta[abs(crc32($nombre)) % count($paleta)];
    return "<div style=\"width:{$size}px;height:{$size}px;border-radius:50%;background:{$bg};
                          display:flex;align-items:center;justify-content:center;
                          color:#fff;font-size:{$f};font-weight:900;flex-shrink:0;line-height:1;\">{$ini}</div>";
}

function r(int $n, float $sc): int { return (int)round($n * $sc); }

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Liga · <?= $tipo_label ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { width:<?= $W ?>px; background:transparent; }
</style>
</head>
<body>

<!-- ══ IMAGE CARD ══════════════════════════════════════════════════════════ -->
<div id="img-card" style="
    width:<?= $W ?>px; height:<?= $H ?>px;
    background:linear-gradient(155deg,#004386 0%,#001d3d 55%,#003870 100%);
    position:relative; overflow:hidden;
    font-family:Arial,Helvetica,sans-serif; line-height:1.3;">

    <!-- Deco circles -->
    <div style="position:absolute;width:700px;height:700px;border-radius:50%;
                background:rgba(255,255,255,0.04);top:-280px;right:-220px;"></div>
    <div style="position:absolute;width:350px;height:350px;border-radius:50%;
                background:rgba(240,165,0,0.05);bottom:-80px;left:-80px;"></div>

    <!-- Content wrapper -->
    <div style="position:absolute;left:<?= $pad ?>px;top:<?= $pad ?>px;
                width:<?= $iw ?>px;
                display:flex;flex-direction:column;gap:<?= r(16,$sc) ?>px;">

        <!-- HEADER ROW -->
        <div style="display:flex;align-items:center;gap:<?= r(16,$sc) ?>px;">
            <div style="width:<?= r(62,$sc) ?>px;height:<?= r(62,$sc) ?>px;
                        background:linear-gradient(135deg,#f0a500,#c77800);border-radius:50%;
                        display:flex;align-items:center;justify-content:center;
                        box-shadow:0 4px 18px rgba(240,165,0,0.45);flex-shrink:0;
                        color:#fff;font-size:<?= r(28,$sc) ?>px;font-weight:900;">&#9733;</div>
            <div style="flex:1;">
                <div style="color:#fff;font-size:<?= r(28,$sc) ?>px;font-weight:900;letter-spacing:0.5px;">LIGA DEPORTIVA</div>
                <div style="color:rgba(255,255,255,0.6);font-size:<?= r(13,$sc) ?>px;letter-spacing:2px;">DE GENERAL ARENALES</div>
            </div>
            <div style="text-align:right;">
                <div style="background:rgba(240,165,0,0.18);border:1.5px solid rgba(240,165,0,0.55);
                            color:#f0a500;padding:<?= r(4,$sc) ?>px <?= r(12,$sc) ?>px;
                            border-radius:20px;font-size:<?= r(11,$sc) ?>px;font-weight:700;
                            display:inline-block;max-width:240px;overflow:hidden;
                            white-space:nowrap;text-overflow:ellipsis;">
                    <?= htmlspecialchars($torneo_nombre) ?>
                </div>
                <div style="color:rgba(255,255,255,0.5);font-size:<?= r(11,$sc) ?>px;margin-top:4px;">
                    <?= htmlspecialchars($division_nombre) ?>
                </div>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div style="background:rgba(255,255,255,0.97);border-radius:<?= r(16,$sc) ?>px;
                    overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.35);">

            <!-- Card header bar -->
            <div style="background:linear-gradient(90deg,#004386,#0057b8);
                        padding:0 <?= r(26,$sc) ?>px;height:<?= r(60,$sc) ?>px;
                        display:flex;align-items:center;justify-content:space-between;">
                <div style="color:#fff;font-size:<?= r(19,$sc) ?>px;font-weight:900;letter-spacing:1.5px;">
                    <?= $tipo_label ?>
                </div>
                <div style="color:rgba(255,255,255,0.55);font-size:<?= r(12,$sc) ?>px;"><?= $hoy ?></div>
            </div>

            <?php if ($tipo === 'tabla'): ?>
            <?php
            $total    = count($rows);
            $top_z    = max(1, (int)ceil($total * 0.28));
            $rel_z    = max(1, (int)floor($total * 0.15));
            $row_h    = r(54, $sc);
            $hdr_h    = r(38, $sc);
            $shield_s = r(34, $sc);
            $fn       = r(14, $sc);
            $fh       = r(11, $sc);
            ?>
            <!-- Column headers -->
            <div style="display:flex;align-items:center;height:<?= $hdr_h ?>px;
                        padding:0 <?= r(14,$sc) ?>px;background:#f4f6fa;
                        border-bottom:2px solid #e0e6f0;
                        font-size:<?= $fh ?>px;font-weight:700;color:#aaa;
                        letter-spacing:0.8px;text-transform:uppercase;">
                <div style="width:<?= r(38,$sc) ?>px;flex-shrink:0;">#</div>
                <div style="width:<?= $shield_s+8 ?>px;flex-shrink:0;"></div>
                <div style="flex:1;">EQUIPO</div>
                <?php foreach ([['PJ',r(38,$sc)],['G',r(38,$sc)],['E',r(38,$sc)],['P',r(38,$sc)],['DIF',r(50,$sc)],['PTS',r(52,$sc)]] as [$col,$cw]): ?>
                <div style="width:<?= $cw ?>px;text-align:center;flex-shrink:0;"><?= $col ?></div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($rows as $i => $r):
                $pos    = $i + 1;
                $bg     = $i % 2 === 0 ? '#ffffff' : '#f9fafc';
                $zone   = $pos <= $top_z ? '#16a34a' : ($pos > $total - $rel_z ? '#dc2626' : 'transparent');
                $dg     = $r['DG'] ?? ($r['GF'] - $r['GC']);
                $dg_s   = ($dg > 0 ? '+' : '') . $dg;
                $pb_bg  = $pos===1 ? '#f0a500' : ($pos===2 ? '#b0b8c4' : ($pos===3 ? '#c87832' : 'transparent'));
                $pb_c   = $pos<=3 ? '#fff' : '#aaa';
                $pb_r   = $pos<=3 ? '50%' : '0';
                $nlen   = mb_strlen($r['nombre_corto'] ?? '');
                $nf     = $nlen > 16 ? r(11,$sc) : ($nlen > 12 ? r(13,$sc) : r(15,$sc));
            ?>
            <div style="display:flex;align-items:center;height:<?= $row_h ?>px;
                        padding:0 <?= r(14,$sc) ?>px;background:<?= $bg ?>;
                        border-left:4px solid <?= $zone ?>;border-bottom:1px solid #f0f2f5;">
                <div style="width:<?= r(38,$sc) ?>px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                    <div style="width:<?= r(26,$sc) ?>px;height:<?= r(26,$sc) ?>px;border-radius:<?= $pb_r ?>;
                                background:<?= $pb_bg ?>;color:<?= $pb_c ?>;
                                display:flex;align-items:center;justify-content:center;
                                font-size:<?= r(12,$sc) ?>px;font-weight:900;"><?= $pos ?></div>
                </div>
                <div style="width:<?= $shield_s+8 ?>px;flex-shrink:0;display:flex;justify-content:center;">
                    <?= esc($r['escudo_url'] ?? '', $r['nombre_corto'] ?? '', $shield_s) ?>
                </div>
                <div style="flex:1;font-size:<?= $nf ?>px;font-weight:700;color:#1a1a2e;
                            overflow:hidden;white-space:nowrap;text-overflow:ellipsis;padding-right:4px;">
                    <?= htmlspecialchars($r['nombre_corto'] ?? '') ?>
                </div>
                <?php foreach ([
                    [r(38,$sc), $r['PJ']??0,   false],
                    [r(38,$sc), $r['PG']??0,   false],
                    [r(38,$sc), $r['PE']??0,   false],
                    [r(38,$sc), $r['PP']??0,   false],
                    [r(50,$sc), $dg_s,          false],
                    [r(52,$sc), $r['Pts']??0,   true],
                ] as [$cw, $val, $bold]): ?>
                <div style="width:<?= $cw ?>px;text-align:center;flex-shrink:0;
                            font-size:<?= r(13,$sc) ?>px;
                            font-weight:<?= $bold?'900':'500' ?>;
                            color:<?= $bold?'#004386':'#666' ?>;"><?= $val ?></div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <?php if ($total > 3): ?>
            <div style="display:flex;gap:<?= r(18,$sc) ?>px;padding:<?= r(8,$sc) ?>px <?= r(18,$sc) ?>px;
                        background:#f4f6fa;border-top:1px solid #e8ecf0;">
                <div style="display:flex;align-items:center;gap:5px;font-size:<?= r(10,$sc) ?>px;color:#888;">
                    <div style="width:10px;height:10px;background:#16a34a;border-radius:2px;flex-shrink:0;"></div>Clasifica
                </div>
                <div style="display:flex;align-items:center;gap:5px;font-size:<?= r(10,$sc) ?>px;color:#888;">
                    <div style="width:10px;height:10px;background:#dc2626;border-radius:2px;flex-shrink:0;"></div>Descenso
                </div>
            </div>
            <?php endif; ?>

            <?php elseif ($tipo === 'resultados'): ?>
            <?php
            $row_h    = r(72, $sc);
            $shield_s = r(36, $sc);
            ?>
            <?php foreach ($rows as $i => $r):
                $bg     = $i % 2 === 0 ? '#fff' : '#f9fafc';
                $gl     = $r['goles_local'];
                $gv     = $r['goles_visitante'];
                $lw     = $gl > $gv;
                $vw     = $gv > $gl;
                $fecha  = date('d/m', strtotime($r['fecha_hora']));
            ?>
            <div style="display:flex;align-items:center;height:<?= $row_h ?>px;
                        padding:0 <?= r(12,$sc) ?>px;background:<?= $bg ?>;
                        border-bottom:1px solid #f0f2f5;gap:<?= r(6,$sc) ?>px;">
                <div style="width:<?= r(50,$sc) ?>px;flex-shrink:0;text-align:center;">
                    <div style="font-size:<?= r(12,$sc) ?>px;font-weight:700;color:#004386;"><?= $fecha ?></div>
                    <?php if ($r['fase']): ?><div style="font-size:<?= r(9,$sc) ?>px;color:#bbb;"><?= htmlspecialchars(mb_strimwidth($r['fase'],0,12,'…')) ?></div><?php endif; ?>
                </div>
                <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;gap:<?= r(7,$sc) ?>px;overflow:hidden;">
                    <div style="font-size:<?= r(14,$sc) ?>px;font-weight:<?= $lw?'900':'600' ?>;
                                color:<?= $lw?'#004386':'#555' ?>;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= r(180,$sc) ?>px;">
                        <?= htmlspecialchars($r['local_nombre']) ?>
                    </div>
                    <?= esc($r['local_escudo']??'', $r['local_nombre'], $shield_s) ?>
                </div>
                <div style="flex-shrink:0;padding:0 <?= r(8,$sc) ?>px;">
                    <div style="background:#004386;color:#fff;border-radius:<?= r(7,$sc) ?>px;
                                padding:<?= r(4,$sc) ?>px <?= r(9,$sc) ?>px;
                                font-size:<?= r(20,$sc) ?>px;font-weight:900;letter-spacing:2px;
                                min-width:<?= r(64,$sc) ?>px;text-align:center;"><?= $gl ?> : <?= $gv ?></div>
                </div>
                <div style="flex:1;display:flex;align-items:center;gap:<?= r(7,$sc) ?>px;overflow:hidden;">
                    <?= esc($r['visitante_escudo']??'', $r['visitante_nombre'], $shield_s) ?>
                    <div style="font-size:<?= r(14,$sc) ?>px;font-weight:<?= $vw?'900':'600' ?>;
                                color:<?= $vw?'#004386':'#555' ?>;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= r(180,$sc) ?>px;">
                        <?= htmlspecialchars($r['visitante_nombre']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php elseif ($tipo === 'proximos'): ?>
            <?php
            $row_h    = r(72, $sc);
            $shield_s = r(36, $sc);
            ?>
            <?php foreach ($rows as $i => $r):
                $bg    = $i % 2 === 0 ? '#fff' : '#f9fafc';
                $fecha = date('d/m', strtotime($r['fecha_hora']));
                $hora  = date('H:i', strtotime($r['fecha_hora']));
            ?>
            <div style="display:flex;align-items:center;height:<?= $row_h ?>px;
                        padding:0 <?= r(12,$sc) ?>px;background:<?= $bg ?>;
                        border-bottom:1px solid #f0f2f5;gap:<?= r(6,$sc) ?>px;">
                <div style="width:<?= r(56,$sc) ?>px;flex-shrink:0;text-align:center;line-height:1.4;">
                    <div style="font-size:<?= r(13,$sc) ?>px;font-weight:900;color:#004386;"><?= $fecha ?></div>
                    <div style="font-size:<?= r(12,$sc) ?>px;color:#888;"><?= $hora ?></div>
                </div>
                <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;gap:<?= r(7,$sc) ?>px;overflow:hidden;">
                    <div style="font-size:<?= r(14,$sc) ?>px;font-weight:700;color:#333;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= r(190,$sc) ?>px;">
                        <?= htmlspecialchars($r['local_nombre']) ?>
                    </div>
                    <?= esc($r['local_escudo']??'', $r['local_nombre'], $shield_s) ?>
                </div>
                <div style="flex-shrink:0;padding:0 <?= r(8,$sc) ?>px;
                            font-size:<?= r(12,$sc) ?>px;font-weight:900;color:#ccc;letter-spacing:2px;">VS</div>
                <div style="flex:1;display:flex;align-items:center;gap:<?= r(7,$sc) ?>px;overflow:hidden;">
                    <?= esc($r['visitante_escudo']??'', $r['visitante_nombre'], $shield_s) ?>
                    <div style="font-size:<?= r(14,$sc) ?>px;font-weight:700;color:#333;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= r(190,$sc) ?>px;">
                        <?= htmlspecialchars($r['visitante_nombre']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($rows)): ?>
            <div style="padding:<?= r(50,$sc) ?>px;text-align:center;color:#ccc;font-size:<?= r(18,$sc) ?>px;">
                Sin datos disponibles.
            </div>
            <?php endif; ?>

        </div><!-- /main card -->

        <!-- Footer -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0 <?= r(6,$sc) ?>px;">
            <div style="color:rgba(255,255,255,0.35);font-size:<?= r(11,$sc) ?>px;letter-spacing:1px;">
                ascensiondigital.ar · FM ENCUENTRO 103.1
            </div>
            <div style="color:rgba(255,255,255,0.35);font-size:<?= r(11,$sc) ?>px;"><?= $hoy ?></div>
        </div>

    </div><!-- /content wrapper -->
</div><!-- /img-card -->

<?php if ($autodownload): ?>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
window.addEventListener('load', function() {
    const card = document.getElementById('img-card');
    html2canvas(card, {
        scale: 1,
        useCORS: true,
        allowTaint: false,
        backgroundColor: null,
        logging: false,
        width:  <?= $W ?>,
        height: <?= $H ?>,
    }).then(function(canvas) {
        const tipo = '<?= $tipo ?>';
        const fmt  = '<?= $fmt ?>';
        const div  = '<?= addslashes(preg_replace('/\s+/', '-', mb_strtolower($division_nombre))) ?>';
        const a    = document.createElement('a');
        a.download = 'liga-' + tipo + '-' + fmt + '-' + div + '.png';
        a.href = canvas.toDataURL('image/png');
        document.body.appendChild(a);
        a.click();
        // Cerrar pestaña automáticamente tras breve espera
        setTimeout(function() { window.close(); }, 800);
    }).catch(function(e) {
        document.body.innerHTML += '<p style="color:red;font-family:sans-serif;padding:20px;">Error: ' + e.message + '</p>';
    });
});
</script>
<?php endif; ?>

</body>
</html>
