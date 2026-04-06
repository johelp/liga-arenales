<?php
ob_start();
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = conectarDB();

$torneos   = $pdo->query("SELECT id_torneo, nombre, activo FROM torneos ORDER BY activo DESC, fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY orden, nombre")->fetchAll(PDO::FETCH_ASSOC);

// Torneo y división por defecto (activo)
$torneo_default   = $pdo->query("SELECT id_torneo FROM torneos WHERE activo=1 ORDER BY fecha_inicio DESC LIMIT 1")->fetchColumn();
$division_default = $pdo->query("SELECT d.id_division FROM divisiones d JOIN clubes_en_division ced ON d.id_division=ced.id_division WHERE ced.id_torneo=" . (int)$torneo_default . " ORDER BY d.orden LIMIT 1")->fetchColumn();

$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$widget_base = $base_url . '/liga/widget.php';

include 'header.php';
?>
<style>
.widget-preview { background:#f0f4f8; border-radius:14px; padding:1rem; }
.widget-preview iframe { border:none; border-radius:10px; width:100%; display:block; box-shadow:0 2px 12px rgba(0,0,0,.1); }
.code-block { background:#1e2430; color:#a8d8a8; border-radius:10px; padding:1rem; font-size:.8rem; font-family:monospace; overflow-x:auto; white-space:pre-wrap; word-break:break-all; }
.copy-btn { cursor:pointer; }
.tipo-card { cursor:pointer; border:2px solid #e0e0e0; border-radius:12px; padding:.7rem .9rem; transition:all .15s; }
.tipo-card:hover, .tipo-card.active { border-color:#004386; background:#e8f0fb; }
.tipo-card.active { border-color:#004386; }
</style>

<div class="container-fluid px-3 px-md-4 py-3" style="max-width:1100px; margin:0 auto;">
    <div class="d-flex align-items-center gap-2 mb-4">
        <h5 class="mb-0 fw-bold"><i class="bi bi-window-stack text-primary me-1"></i> Generador de Widgets</h5>
        <span class="badge bg-primary rounded-pill">Embebible en WordPress</span>
    </div>

    <div class="row g-3">
        <!-- ── Configurador ── -->
        <div class="col-lg-4">
            <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
                <h6 class="fw-bold mb-3">1. Tipo de widget</h6>
                <div class="d-flex flex-column gap-2" id="tipo-selector">
                    <?php
                    $tipos = [
                        'tabla'      => ['Tabla de Posiciones', 'bi-table'],
                        'resultados' => ['Últimos Resultados',  'bi-check-circle-fill'],
                        'proximos'   => ['Próximos Partidos',   'bi-calendar-event-fill'],
                        'fixture'    => ['Fixture Completo',    'bi-calendar3-range-fill'],
                        'live'       => ['En Vivo / Hoy',       'bi-broadcast'],
                    ];
                    foreach ($tipos as $k => [$label, $icon]):
                    ?>
                    <div class="tipo-card <?= $k === 'tabla' ? 'active' : '' ?>" data-tipo="<?= $k ?>">
                        <i class="bi <?= $icon ?> me-2 text-primary"></i>
                        <span class="fw-semibold"><?= $label ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
                <h6 class="fw-bold mb-3">2. Configuración</h6>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Torneo</label>
                    <select class="form-select form-select-sm" id="cfg-torneo">
                        <?php foreach ($torneos as $t): ?>
                        <option value="<?= $t['id_torneo'] ?>" <?= $t['id_torneo'] == $torneo_default ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre']) ?> <?= $t['activo'] ? '✓' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">División</label>
                    <select class="form-select form-select-sm" id="cfg-division">
                        <?php foreach ($divisiones as $d): ?>
                        <option value="<?= $d['id_division'] ?>" <?= $d['id_division'] == $division_default ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Fase <span class="text-muted fw-normal">(opcional)</span></label>
                    <select class="form-select form-select-sm" id="cfg-fase">
                        <option value="">Todas</option>
                        <option>Primera Fase</option>
                        <option>Cuartos de Final</option>
                        <option>Semifinal</option>
                        <option>Final</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold mb-1">Límite</label>
                        <input type="number" class="form-control form-control-sm" id="cfg-limite" value="10" min="1" max="50">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold mb-1">Tema</label>
                        <select class="form-select form-select-sm" id="cfg-theme">
                            <option value="light">Claro</option>
                            <option value="dark">Oscuro</option>
                        </select>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="cfg-header" checked>
                        <label class="form-check-label small" for="cfg-header">Mostrar encabezado</label>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3 shadow-sm p-3">
                <h6 class="fw-bold mb-2">3. Código para WordPress</h6>
                <div class="code-block mb-2" id="codigo-iframe">Generando…</div>
                <button class="btn btn-sm btn-outline-primary w-100 copy-btn" id="btn-copiar">
                    <i class="bi bi-clipboard me-1"></i> Copiar código
                </button>
                <hr class="my-2">
                <p class="small text-muted mb-1">Para auto-resize, agregá esto una vez en WordPress (Header/Footer plugin):</p>
                <div class="code-block" style="font-size:.72rem">&lt;script src="<?= $base_url ?>/liga/widget-resize.js"&gt;&lt;/script&gt;</div>
            </div>
        </div>

        <!-- ── Preview ── -->
        <div class="col-lg-8">
            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Vista previa</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="small text-muted">Ancho:</span>
                        <select class="form-select form-select-sm" id="preview-width" style="width:auto">
                            <option value="100%">Completo</option>
                            <option value="400px">400px (móvil)</option>
                            <option value="600px">600px</option>
                            <option value="320px">320px</option>
                        </select>
                        <a id="btn-nueva-tab" href="#" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
                <div class="widget-preview" id="preview-container">
                    <iframe id="preview-iframe" src="" height="500" scrolling="no"
                            style="width:100%; border:none; border-radius:10px;"></iframe>
                </div>
                <div class="mt-2 text-end">
                    <small class="text-muted" id="preview-url-label"></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const WIDGET_BASE = '<?= $widget_base ?>';
let tipoActual = 'tabla';

function getParams() {
    return {
        tipo:           tipoActual,
        torneo:         document.getElementById('cfg-torneo').value,
        division:       document.getElementById('cfg-division').value,
        fase:           document.getElementById('cfg-fase').value,
        limite:         document.getElementById('cfg-limite').value,
        theme:          document.getElementById('cfg-theme').value,
        mostrar_header: document.getElementById('cfg-header').checked ? '1' : '0',
    };
}

function buildURL(params) {
    const p = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
        if (v !== '' && v !== null) p.set(k, v);
    }
    return WIDGET_BASE + '?' + p.toString();
}

function update() {
    const params = getParams();
    const url = buildURL(params);

    // Preview
    const iframe = document.getElementById('preview-iframe');
    const pw = document.getElementById('preview-width').value;
    const container = document.getElementById('preview-container');
    container.style.display = 'flex';
    container.style.justifyContent = pw !== '100%' ? 'center' : '';
    iframe.style.width = pw;
    iframe.src = url;

    // URL label
    document.getElementById('preview-url-label').textContent = url;
    document.getElementById('btn-nueva-tab').href = url;

    // Código iframe
    const code = `<iframe src="${url}"\n  width="100%" height="520" frameborder="0" scrolling="no"\n  style="border:none; width:100%;"></iframe>`;
    document.getElementById('codigo-iframe').textContent = code;
}

// Tipo selector
document.querySelectorAll('.tipo-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        tipoActual = this.dataset.tipo;
        update();
    });
});

// Config changes
['cfg-torneo','cfg-division','cfg-fase','cfg-limite','cfg-theme','cfg-header','preview-width'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', update);
});

// Copiar
document.getElementById('btn-copiar').addEventListener('click', function() {
    const code = document.getElementById('codigo-iframe').textContent;
    navigator.clipboard.writeText(code).then(() => {
        this.innerHTML = '<i class="bi bi-check-circle-fill me-1 text-success"></i> ¡Copiado!';
        setTimeout(() => this.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copiar código', 2000);
    });
});

// Auto-resize listener from iframe
window.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'ligaWidgetResize') {
        document.getElementById('preview-iframe').style.height = (e.data.height + 8) + 'px';
    }
});

// Init
update();
</script>

<?php include 'footer.php'; ?>
