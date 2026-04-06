<?php
ob_start();
session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$errores   = [];
$mensaje   = '';
$partidos  = [];
$torneos   = [];
$divisiones = [];

// Obtener torneos y divisiones para los selects
$torneos    = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY activo DESC, fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY orden, nombre")->fetchAll(PDO::FETCH_ASSOC);

// Valores actuales de filtros (POST tiene prioridad, luego GET)
$id_torneo_sel  = (int)($_POST['id_torneo']  ?? $_GET['id_torneo']  ?? 0);
$id_division_sel = (int)($_POST['id_division'] ?? $_GET['id_division'] ?? 0);
$fase_sel        = $_POST['fase'] ?? $_GET['fase'] ?? '';
$fecha_num_sel   = (int)($_POST['fecha_numero'] ?? $_GET['fecha_numero'] ?? 0);

// AJAX: devolver fechas disponibles para torneo+division+fase
if (isset($_GET['ajax_fechas'])) {
    header('Content-Type: application/json');
    if ($id_torneo_sel && $id_division_sel) {
        $sql = "SELECT DISTINCT fecha_numero, MIN(fecha_hora) as fecha_dia
                FROM partidos
                WHERE id_torneo = ? AND id_division = ? AND fecha_numero IS NOT NULL";
        $params = [$id_torneo_sel, $id_division_sel];
        if ($fase_sel !== '') {
            $sql .= " AND fase = ?";
            $params[] = $fase_sel;
        }
        $sql .= " GROUP BY fecha_numero ORDER BY fecha_numero";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($fechas);
    } else {
        echo json_encode([]);
    }
    exit();
}

// AJAX: devolver fases disponibles para torneo+division
if (isset($_GET['ajax_fases'])) {
    header('Content-Type: application/json');
    if ($id_torneo_sel && $id_division_sel) {
        $stmt = $pdo->prepare("SELECT DISTINCT fase FROM partidos WHERE id_torneo = ? AND id_division = ? AND fase IS NOT NULL ORDER BY fase");
        $stmt->execute([$id_torneo_sel, $id_division_sel]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        echo json_encode([]);
    }
    exit();
}

// ---- Guardar resultados ----
if (isset($_POST['guardar_resultados'])) {
    $resultados_guardados = 0;
    $errores_guardado = [];

    foreach ($_POST['id_partido'] as $key => $id_partido) {
        $id_partido      = (int)$id_partido;
        $goles_local     = $_POST['goles_local'][$key] ?? '';
        $goles_visitante = $_POST['goles_visitante'][$key] ?? '';
        $jugado          = isset($_POST['jugado'][$id_partido]) ? 1 : 0;

        // Si está marcado jugado, los goles son obligatorios y >= 0
        if ($jugado && (!is_numeric($goles_local) || $goles_local < 0 || !is_numeric($goles_visitante) || $goles_visitante < 0)) {
            $errores_guardado[] = "Resultado inválido en partido ID $id_partido.";
            continue;
        }

        $gl          = $jugado ? (int)$goles_local     : null;
        $gv          = $jugado ? (int)$goles_visitante : null;
        $comentario  = trim($_POST['comentario'][$key] ?? '');
        $comentario  = $comentario === '' ? null : $comentario;

        $stmt = $pdo->prepare("UPDATE partidos SET goles_local = ?, goles_visitante = ?, jugado = ?, comentario = ? WHERE id_partido = ?");
        if ($stmt->execute([$gl, $gv, $jugado, $comentario, $id_partido])) {
            $resultados_guardados++;
        }
    }

    if (empty($errores_guardado) && $resultados_guardados > 0) {
        $mensaje = ['tipo' => 'success', 'texto' => "✓ $resultados_guardados resultado(s) guardado(s) correctamente."];
    } elseif (!empty($errores_guardado)) {
        $mensaje = ['tipo' => 'danger', 'texto' => implode('<br>', $errores_guardado)];
    } else {
        $mensaje = ['tipo' => 'info', 'texto' => 'No se realizaron cambios.'];
    }
}

// ---- Cargar partidos para mostrar ----
if ($id_torneo_sel && $id_division_sel) {
    $sql = "SELECT p.id_partido, p.fecha_hora, p.fase, p.fecha_numero,
                   p.goles_local, p.goles_visitante, p.jugado, p.comentario,
                   cl.nombre_corto AS local_nombre, cl.escudo_url AS local_escudo,
                   cv.nombre_corto AS visitante_nombre, cv.escudo_url AS visitante_escudo
            FROM partidos p
            JOIN clubes cl ON p.id_club_local  = cl.id_club
            JOIN clubes cv ON p.id_club_visitante = cv.id_club
            WHERE p.id_torneo = ? AND p.id_division = ?";
    $params = [$id_torneo_sel, $id_division_sel];

    if ($fase_sel !== '') {
        $sql .= " AND p.fase = ?";
        $params[] = $fase_sel;
    }
    if ($fecha_num_sel) {
        $sql .= " AND p.fecha_numero = ?";
        $params[] = $fecha_num_sel;
    }
    $sql .= " ORDER BY p.fecha_hora, p.id_partido";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../header.php';

?>
<style>

:root {
    --liga-blue: #004386;
    --liga-blue-dark: #003066;
}
body { background: #f0f4f8; }

/* ── Filtros ── */
.filtros-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
}
.filtros-card .form-select,
.filtros-card .form-control {
    border-radius: 10px;
    font-size: .95rem;
}

/* ── Partido card ── */
.partido-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    margin-bottom: .85rem;
    overflow: hidden;
    transition: box-shadow .2s;
}
.partido-card.jugado-ok  { border-left: 4px solid #198754; }
.partido-card.pendiente  { border-left: 4px solid #ffc107; }

.partido-header {
    background: #f8f9fa;
    padding: .45rem 1rem;
    font-size: .78rem;
    color: #6c757d;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.partido-body {
    padding: .75rem 1rem;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: .5rem;
}
.equipo-bloque {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
}
.equipo-escudo {
    width: 46px; height: 46px;
    object-fit: contain;
}
.equipo-nombre {
    font-size: .82rem;
    font-weight: 600;
    text-align: center;
    line-height: 1.2;
}
.marcador-bloque {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
}
.marcador-bloque .vs-text {
    font-size: .75rem;
    color: #aaa;
    font-weight: 500;
}
.score-inputs {
    display: flex;
    align-items: center;
    gap: .4rem;
}
.score-input {
    width: 52px !important;
    height: 52px !important;
    text-align: center;
    font-size: 1.4rem !important;
    font-weight: 700;
    border-radius: 10px !important;
    border: 2px solid #dee2e6;
    padding: 0 !important;
    -moz-appearance: textfield;
}
.score-input::-webkit-inner-spin-button,
.score-input::-webkit-outer-spin-button { -webkit-appearance: none; }
.score-input:focus { border-color: var(--liga-blue); box-shadow: 0 0 0 .2rem rgba(0,67,134,.2); }
.score-sep { font-size: 1.4rem; font-weight: 700; color: #333; }

.jugado-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .3rem .6rem;
    font-size: .78rem;
    gap: .3rem;
}
.jugado-toggle input[type=checkbox] {
    width: 1.1rem; height: 1.1rem; cursor: pointer;
}

/* ── Botón guardar fijo en mobile ── */
.guardar-fab {
    position: fixed;
    bottom: 1.2rem;
    right: 1.2rem;
    z-index: 1000;
    border-radius: 50px !important;
    padding: .7rem 1.4rem;
    font-size: 1rem;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(0,67,134,.4);
    background: var(--liga-blue);
    border-color: var(--liga-blue);
}
.guardar-fab:hover { background: var(--liga-blue-dark); }

/* ── Header de sección ── */
.seccion-header {
    background: var(--liga-blue);
    color: #fff;
    border-radius: 12px;
    padding: .6rem 1rem;
    margin-bottom: .75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
}
.badge-pendientes { background: #ffc107; color: #212529; }
.badge-jugados    { background: #198754; color: #fff; }

/* ── Comentario ── */
.comentario-bloque {
    padding: .4rem .75rem .6rem;
    border-top: 1px solid #f0f2f5;
}
.comentario-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
    margin-bottom: .35rem;
}
.chip {
    font-size: .7rem;
    padding: .15rem .5rem;
    border-radius: 20px;
    background: #e8f0fb;
    color: var(--liga-blue);
    cursor: pointer;
    font-weight: 600;
    border: 1px solid #c5d8f5;
    user-select: none;
    transition: background .15s;
}
.chip:hover { background: #c5d8f5; }
.comentario-input {
    width: 100%;
    font-size: .8rem;
    border: 1px solid #dde3ec;
    border-radius: 8px;
    padding: .35rem .6rem;
    resize: none;
    color: #333;
    background: #fafbfc;
    font-family: inherit;
    line-height: 1.4;
}
.comentario-input:focus {
    outline: none;
    border-color: var(--liga-blue);
    box-shadow: 0 0 0 .15rem rgba(0,67,134,.12);
    background: #fff;
}

</style>
<div class="container-fluid px-2 px-md-4 py-3" style="max-width:680px; margin: 0 auto;">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-check text-primary me-1"></i> Cargar Resultados</h5>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensaje['tipo'] ?> alert-dismissible rounded-3 py-2" role="alert">
        <?= $mensaje['texto'] ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ── Filtros ── -->
    <div class="filtros-card">
        <div class="row g-2">
            <div class="col-12">
                <label class="form-label small fw-semibold mb-1">Torneo</label>
                <select id="sel-torneo" class="form-select form-select-sm">
                    <option value="">Seleccionar torneo…</option>
                    <?php foreach ($torneos as $t): ?>
                        <option value="<?= $t['id_torneo'] ?>" <?= $id_torneo_sel == $t['id_torneo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold mb-1">División</label>
                <select id="sel-division" class="form-select form-select-sm">
                    <option value="">División…</option>
                    <?php foreach ($divisiones as $d): ?>
                        <option value="<?= $d['id_division'] ?>" <?= $id_division_sel == $d['id_division'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Fase</label>
                <select id="sel-fase" class="form-select form-select-sm">
                    <option value="">Todas…</option>
                    <?php
                    // Pre-cargar fases si ya hay torneo+division seleccionados
                    if ($id_torneo_sel && $id_division_sel) {
                        $stmt_fases = $pdo->prepare("SELECT DISTINCT fase FROM partidos WHERE id_torneo=? AND id_division=? AND fase IS NOT NULL ORDER BY fase");
                        $stmt_fases->execute([$id_torneo_sel, $id_division_sel]);
                        foreach ($stmt_fases->fetchAll(PDO::FETCH_COLUMN) as $f):
                    ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= $fase_sel === $f ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f) ?>
                        </option>
                    <?php endforeach; } ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold mb-1">Fecha / Jornada</label>
                <select id="sel-fecha" class="form-select form-select-sm">
                    <option value="">Todas las fechas</option>
                    <?php
                    // Pre-cargar fechas si ya hay torneo+division
                    if ($id_torneo_sel && $id_division_sel) {
                        $sql_f = "SELECT DISTINCT fecha_numero, MIN(fecha_hora) as fecha_dia FROM partidos WHERE id_torneo=? AND id_division=? AND fecha_numero IS NOT NULL";
                        $p_f = [$id_torneo_sel, $id_division_sel];
                        if ($fase_sel !== '') { $sql_f .= " AND fase=?"; $p_f[] = $fase_sel; }
                        $sql_f .= " GROUP BY fecha_numero ORDER BY fecha_numero";
                        $stmt_f = $pdo->prepare($sql_f);
                        $stmt_f->execute($p_f);
                        foreach ($stmt_f->fetchAll(PDO::FETCH_ASSOC) as $frow):
                            $label = 'Fecha ' . $frow['fecha_numero'] . ' — ' . date('d/m/Y', strtotime($frow['fecha_dia']));
                    ?>
                        <option value="<?= $frow['fecha_numero'] ?>" <?= $fecha_num_sel == $frow['fecha_numero'] ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; } ?>
                </select>
            </div>
        </div>
    </div>

    <!-- ── Lista de partidos ── -->
    <?php if ($id_torneo_sel && $id_division_sel): ?>
    <form method="post" id="form-resultados">
        <input type="hidden" name="id_torneo"   value="<?= $id_torneo_sel ?>">
        <input type="hidden" name="id_division"  value="<?= $id_division_sel ?>">
        <input type="hidden" name="fase"         value="<?= htmlspecialchars($fase_sel) ?>">
        <input type="hidden" name="fecha_numero" value="<?= $fecha_num_sel ?>">

        <?php if (empty($partidos)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar-x" style="font-size:2.5rem"></i>
                <p class="mt-2">No hay partidos para los filtros seleccionados.</p>
            </div>
        <?php else:
            $total_jugados   = count(array_filter($partidos, fn($p) => $p['jugado']));
            $total_pendientes = count($partidos) - $total_jugados;
        ?>
        <div class="seccion-header">
            <span><?= count($partidos) ?> partido(s)</span>
            <div class="d-flex gap-1">
                <?php if ($total_jugados): ?>
                <span class="badge badge-jugados rounded-pill"><?= $total_jugados ?> jugados</span>
                <?php endif; ?>
                <?php if ($total_pendientes): ?>
                <span class="badge badge-pendientes rounded-pill"><?= $total_pendientes ?> pendientes</span>
                <?php endif; ?>
            </div>
        </div>

        <?php foreach ($partidos as $p):
            $card_class = $p['jugado'] ? 'jugado-ok' : 'pendiente';
            $gl = $p['goles_local'] ?? '';
            $gv = $p['goles_visitante'] ?? '';
        ?>
        <div class="partido-card <?= $card_class ?>">
            <div class="partido-header">
                <span>
                    <?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?>
                    <?php if ($p['fecha_numero']): ?> · Fecha <?= $p['fecha_numero'] ?><?php endif; ?>
                </span>
                <span class="text-uppercase" style="font-size:.7rem; letter-spacing:.05em">
                    <?= htmlspecialchars($p['fase'] ?? '') ?>
                </span>
            </div>
            <div class="partido-body">
                <!-- Local -->
                <div class="equipo-bloque">
                    <?php if ($p['local_escudo']): ?>
                        <img src="<?= htmlspecialchars($p['local_escudo']) ?>" class="equipo-escudo" alt="">
                    <?php else: ?>
                        <i class="bi bi-shield-fill text-secondary" style="font-size:2.2rem"></i>
                    <?php endif; ?>
                    <span class="equipo-nombre"><?= htmlspecialchars($p['local_nombre']) ?></span>
                </div>

                <!-- Marcador -->
                <div class="marcador-bloque">
                    <div class="score-inputs">
                        <input type="number" class="form-control score-input score-local"
                               name="goles_local[]" value="<?= $gl ?>"
                               min="0" max="99" inputmode="numeric"
                               data-id="<?= $p['id_partido'] ?>">
                        <span class="score-sep">:</span>
                        <input type="number" class="form-control score-input score-visitante"
                               name="goles_visitante[]" value="<?= $gv ?>"
                               min="0" max="99" inputmode="numeric"
                               data-id="<?= $p['id_partido'] ?>">
                    </div>
                    <div class="jugado-toggle">
                        <input type="checkbox" class="chk-jugado" name="jugado[<?= $p['id_partido'] ?>]"
                               id="jug_<?= $p['id_partido'] ?>"
                               value="1" <?= $p['jugado'] ? 'checked' : '' ?>>
                        <label for="jug_<?= $p['id_partido'] ?>" class="form-check-label small">
                            <i class="bi bi-check-circle text-success me-1"></i>Jugado
                        </label>
                    </div>
                    <input type="hidden" name="id_partido[]" value="<?= $p['id_partido'] ?>">
                </div>

                <!-- Visitante -->
                <div class="equipo-bloque">
                    <?php if ($p['visitante_escudo']): ?>
                        <img src="<?= htmlspecialchars($p['visitante_escudo']) ?>" class="equipo-escudo" alt="">
                    <?php else: ?>
                        <i class="bi bi-shield-fill text-secondary" style="font-size:2.2rem"></i>
                    <?php endif; ?>
                    <span class="equipo-nombre"><?= htmlspecialchars($p['visitante_nombre']) ?></span>
                </div>
            </div>
            <!-- Comentario / notas del partido -->
            <div class="comentario-bloque">
                <div class="comentario-chips">
                    <span class="chip" data-texto="Goles: ">Goles</span>
                    <span class="chip" data-texto="Amonestados: ">Amonestados</span>
                    <span class="chip" data-texto="Expulsado: ">Expulsado</span>
                    <span class="chip" data-texto="Penal: ">Penal</span>
                    <span class="chip" data-texto="Sin incidencias">Sin incidencias</span>
                </div>
                <textarea class="comentario-input" name="comentario[]"
                          placeholder="Notas del partido…" rows="2"><?= htmlspecialchars($p['comentario'] ?? '') ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" name="guardar_resultados" class="btn btn-primary guardar-fab">
            <i class="bi bi-save2-fill me-1"></i> Guardar
        </button>
        <!-- Espacio para que el FAB no tape el último partido -->
        <div style="height:80px"></div>

        <?php endif; ?>
    </form>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-funnel" style="font-size:2.5rem"></i>
            <p class="mt-2">Seleccioná torneo y división para ver los partidos.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const selTorneo   = document.getElementById('sel-torneo');
const selDivision = document.getElementById('sel-division');
const selFase     = document.getElementById('sel-fase');
const selFecha    = document.getElementById('sel-fecha');

function buildURL() {
    const base = window.location.pathname;
    const params = new URLSearchParams();
    if (selTorneo.value)   params.set('id_torneo',   selTorneo.value);
    if (selDivision.value) params.set('id_division',  selDivision.value);
    if (selFase.value)     params.set('fase',         selFase.value);
    if (selFecha.value)    params.set('fecha_numero', selFecha.value);
    return base + (params.toString() ? '?' + params.toString() : '');
}

function navigate() { window.location.href = buildURL(); }

async function cargarFases() {
    const t = selTorneo.value, d = selDivision.value;
    selFase.innerHTML = '<option value="">Todas…</option>';
    selFecha.innerHTML = '<option value="">Todas las fechas</option>';
    if (!t || !d) return;

    const url = `?ajax_fases=1&id_torneo=${t}&id_division=${d}`;
    const fases = await fetch(url).then(r => r.json()).catch(() => []);
    fases.forEach(f => {
        const o = document.createElement('option');
        o.value = f; o.textContent = f;
        selFase.appendChild(o);
    });
    await cargarFechas();
}

async function cargarFechas() {
    const t = selTorneo.value, d = selDivision.value, f = selFase.value;
    selFecha.innerHTML = '<option value="">Todas las fechas</option>';
    if (!t || !d) return;

    const url = `?ajax_fechas=1&id_torneo=${t}&id_division=${d}&fase=${encodeURIComponent(f)}`;
    const fechas = await fetch(url).then(r => r.json()).catch(() => []);
    fechas.forEach(row => {
        const o = document.createElement('option');
        o.value = row.fecha_numero;
        const d = new Date(row.fecha_dia);
        o.textContent = `Fecha ${row.fecha_numero} — ${d.toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit',year:'numeric'})}`;
        selFecha.appendChild(o);
    });
}

selTorneo.addEventListener('change', cargarFases);
selDivision.addEventListener('change', cargarFases);
selFase.addEventListener('change', async () => { await cargarFechas(); navigate(); });
selFecha.addEventListener('change', navigate);

// Chips de comentario predefinidos
document.querySelectorAll('.chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const textarea = this.closest('.comentario-bloque').querySelector('.comentario-input');
        const txt = this.dataset.texto;
        const cur = textarea.value.trim();
        // Si el chip es "Sin incidencias", reemplaza todo; si no, agrega al final
        if (txt === 'Sin incidencias') {
            textarea.value = txt;
        } else {
            textarea.value = cur ? cur + (cur.endsWith('.') || cur.endsWith(',') ? ' ' : '. ') + txt : txt;
        }
        textarea.focus();
    });
});

// Auto-check "jugado" cuando se ingresan ambos goles
document.querySelectorAll('.partido-card').forEach(card => {
    const inputLocal     = card.querySelector('.score-local');
    const inputVisitante = card.querySelector('.score-visitante');
    const chk            = card.querySelector('.chk-jugado');
    if (!inputLocal || !inputVisitante || !chk) return;

    function autoCheck() {
        if (inputLocal.value !== '' && inputVisitante.value !== '') {
            chk.checked = true;
        }
    }
    inputLocal.addEventListener('input', autoCheck);
    inputVisitante.addEventListener('input', autoCheck);
});
</script>

<?php include '../footer.php'; ?>
