<?php
ob_start();session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = conectarDB();

$torneo_id   = filter_input(INPUT_GET, 'torneo_id',   FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$tipo        = in_array($_GET['tipo'] ?? '', ['tabla','resultados','proximos']) ? $_GET['tipo'] : 'tabla';

$torneos = $pdo->query("SELECT id_torneo, nombre, activo FROM torneos ORDER BY activo DESC, fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$divisiones = [];

if (!$torneo_id) {
    $torneo_id = $pdo->query("SELECT id_torneo FROM torneos WHERE activo=1 ORDER BY fecha_inicio DESC LIMIT 1")->fetchColumn() ?: null;
}
if ($torneo_id) {
    $s = $pdo->prepare("SELECT DISTINCT d.id_division, d.nombre FROM divisiones d JOIN clubes_en_division ced ON d.id_division=ced.id_division WHERE ced.id_torneo=? ORDER BY d.orden, d.nombre");
    $s->execute([$torneo_id]);
    $divisiones = $s->fetchAll(PDO::FETCH_ASSOC);
    if (!$division_id && !empty($divisiones)) $division_id = $divisiones[0]['id_division'];
}

$division_nombre = '';
foreach ($divisiones as $d) { if ($d['id_division'] == $division_id) $division_nombre = $d['nombre']; }

// Base URL para el render
$render_base = 'compartir_render.php?torneo_id=' . (int)$torneo_id . '&division_id=' . (int)$division_id . '&tipo=' . $tipo;

include 'header.php';
?>
<style>
.gen-wrap { max-width:1100px; margin:0 auto; padding:1rem 1rem 4rem; }
.panel    { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.08); padding:1.2rem; }

/* Preview iframe container */
.preview-outer {
    background: #111827;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow: hidden;
}
.preview-scale {
    transform-origin: top left;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,.5);
    flex-shrink: 0;
    display: block;
}
.preview-scale iframe {
    display: block;
    border: none;
}
.preview-loading {
    color: rgba(255,255,255,.4);
    font-size: .85rem;
    padding: 3rem;
    text-align: center;
}
</style>

<div class="gen-wrap">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0 fw-bold"><i class="bi bi-share-fill text-primary me-1"></i> Generar Imágenes para Redes</h5>
    </div>

    <!-- Config -->
    <div class="panel mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Tipo</label>
                <div class="d-flex gap-1 flex-wrap">
                    <?php foreach ([
                        'tabla'      => ['Tabla',      'bi-table'],
                        'resultados' => ['Resultados', 'bi-check2-circle'],
                        'proximos'   => ['Próximos',   'bi-calendar-event'],
                    ] as $k => [$lbl, $ic]): ?>
                    <a href="?torneo_id=<?= $torneo_id ?>&division_id=<?= $division_id ?>&tipo=<?= $k ?>"
                       class="btn btn-sm <?= $tipo===$k ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill">
                        <i class="bi <?= $ic ?>"></i> <?= $lbl ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-semibold mb-1">Torneo</label>
                <select class="form-select form-select-sm"
                        onchange="location='?torneo_id='+this.value+'&tipo=<?= $tipo ?>'">
                    <?php foreach ($torneos as $t): ?>
                    <option value="<?= $t['id_torneo'] ?>" <?= $t['id_torneo']==$torneo_id?'selected':'' ?>>
                        <?= htmlspecialchars($t['nombre']) ?><?= $t['activo']?' ✓':'' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small fw-semibold mb-1">División</label>
                <select class="form-select form-select-sm"
                        onchange="location='?torneo_id=<?= $torneo_id ?>&division_id='+this.value+'&tipo=<?= $tipo ?>'">
                    <?php foreach ($divisiones as $d): ?>
                    <option value="<?= $d['id_division'] ?>" <?= $d['id_division']==$division_id?'selected':'' ?>>
                        <?= htmlspecialchars($d['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <small class="text-muted d-block">
                    <i class="bi bi-info-circle me-1"></i>
                    Escudos deben estar alojados en el mismo servidor.
                </small>
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

        <!-- ── Feed 1080×1080 ── -->
        <div class="col-md-6">
            <div class="panel">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small">
                        <i class="bi bi-square me-1 text-primary"></i> Feed · 1080 × 1080
                    </span>
                    <div class="d-flex gap-1">
                        <a href="<?= $render_base ?>&fmt=feed" target="_blank"
                           class="btn btn-sm btn-outline-secondary" title="Ver a tamaño real">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <a href="<?= $render_base ?>&fmt=feed&autodownload=1" target="_blank"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-download me-1"></i> Descargar
                        </a>
                    </div>
                </div>
                <?php
                // Preview: 1080 → fit in ~440px → scale ≈ 0.407
                $feed_preview_w = 440;
                $feed_scale = round($feed_preview_w / 1080, 4);
                $feed_preview_h = round(1080 * $feed_scale);
                ?>
                <div class="preview-outer" style="min-height:<?= $feed_preview_h + 28 ?>px;">
                    <div class="preview-scale"
                         style="width:<?= $feed_preview_w ?>px; height:<?= $feed_preview_h ?>px; position:relative;">
                        <div style="transform:scale(<?= $feed_scale ?>); transform-origin:0 0; width:1080px; height:1080px;">
                            <iframe src="<?= htmlspecialchars($render_base) ?>&fmt=feed"
                                    width="1080" height="1080" scrolling="no"
                                    onload="this.previousElementSibling && (this.previousElementSibling.style.display='none')">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Stories 1080×1920 ── -->
        <div class="col-md-6">
            <div class="panel">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small">
                        <i class="bi bi-phone me-1 text-primary"></i> Stories · 1080 × 1920
                    </span>
                    <div class="d-flex gap-1">
                        <a href="<?= $render_base ?>&fmt=stories" target="_blank"
                           class="btn btn-sm btn-outline-secondary" title="Ver a tamaño real">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <a href="<?= $render_base ?>&fmt=stories&autodownload=1" target="_blank"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-download me-1"></i> Descargar
                        </a>
                    </div>
                </div>
                <?php
                // Preview: 1080×1920 → width=440 → scale≈0.407 → height≈780
                $stories_scale = $feed_scale; // mismo ancho
                $stories_preview_h = round(1920 * $stories_scale);
                ?>
                <div class="preview-outer" style="height:<?= $stories_preview_h + 28 ?>px; align-items:flex-start;">
                    <div class="preview-scale"
                         style="width:<?= $feed_preview_w ?>px; height:<?= $stories_preview_h ?>px; position:relative; overflow:hidden;">
                        <div style="transform:scale(<?= $stories_scale ?>); transform-origin:0 0; width:1080px; height:1920px;">
                            <iframe src="<?= htmlspecialchars($render_base) ?>&fmt=stories"
                                    width="1080" height="1920" scrolling="no">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /row -->

    <div class="alert alert-info mt-3 py-2 small">
        <i class="bi bi-lightbulb me-1"></i>
        <strong>Cómo usar:</strong> Hacé clic en <strong>Descargar</strong> — se abrirá una nueva pestaña que genera y descarga el PNG automáticamente.
        También podés usar <i class="bi bi-box-arrow-up-right"></i> para ver la imagen a tamaño completo y guardarla con clic derecho.
    </div>

    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
