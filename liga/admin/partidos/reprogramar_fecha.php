<?php
ob_start();session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

// ── Parámetros de modo ────────────────────────────────────────────────────
$modo       = in_array($_GET['modo'] ?? '', ['fecha','individual']) ? $_GET['modo'] : 'fecha';
$id_partido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);  // modo individual

// Datos del partido individual si viene con ?id=
$partido_individual = null;
if ($modo === 'individual' && $id_partido) {
    $s = $pdo->prepare("
        SELECT p.*, cl.nombre_corto AS local, cv.nombre_corto AS visitante,
               d.nombre AS division, t.nombre AS torneo
        FROM partidos p
        JOIN clubes cl ON p.id_club_local  = cl.id_club
        JOIN clubes cv ON p.id_club_visitante = cv.id_club
        JOIN divisiones d ON p.id_division = d.id_division
        JOIN torneos   t ON p.id_torneo    = t.id_torneo
        WHERE p.id_partido = ?
    ");
    $s->execute([$id_partido]);
    $partido_individual = $s->fetch(PDO::FETCH_ASSOC);
    if (!$partido_individual) {
        $_SESSION['mensaje'] = 'Partido no encontrado.';
        $_SESSION['tipo_mensaje'] = 'warning';
        header('Location: index.php');
        exit();
    }
}

// ── Torneos/divisiones para el modo batch ────────────────────────────────
$torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY activo DESC, nombre")->fetchAll(PDO::FETCH_ASSOC);

$torneo_sel   = filter_input(INPUT_GET, 'torneo_id',    FILTER_VALIDATE_INT);
$division_sel = filter_input(INPUT_GET, 'division_id',  FILTER_VALIDATE_INT);
$fecha_sel    = filter_input(INPUT_GET, 'fecha_numero', FILTER_VALIDATE_INT);

$divisiones    = [];
$fechas_numero = [];
$partidos_fecha = [];

if ($torneo_sel) {
    $s = $pdo->prepare("
        SELECT DISTINCT d.id_division, d.nombre
        FROM partidos p JOIN divisiones d ON p.id_division = d.id_division
        WHERE p.id_torneo = ? ORDER BY d.nombre
    ");
    $s->execute([$torneo_sel]);
    $divisiones = $s->fetchAll(PDO::FETCH_ASSOC);
}
if ($torneo_sel && $division_sel) {
    $s = $pdo->prepare("SELECT DISTINCT fecha_numero FROM partidos WHERE id_torneo=? AND id_division=? ORDER BY fecha_numero");
    $s->execute([$torneo_sel, $division_sel]);
    $fechas_numero = $s->fetchAll(PDO::FETCH_COLUMN);
}
if ($torneo_sel && $division_sel && $fecha_sel) {
    $s = $pdo->prepare("
        SELECT p.*, cl.nombre_corto AS local, cv.nombre_corto AS visitante
        FROM partidos p
        JOIN clubes cl ON p.id_club_local      = cl.id_club
        JOIN clubes cv ON p.id_club_visitante  = cv.id_club
        WHERE p.id_torneo=? AND p.id_division=? AND p.fecha_numero=?
        ORDER BY p.fecha_hora
    ");
    $s->execute([$torneo_sel, $division_sel, $fecha_sel]);
    $partidos_fecha = $s->fetchAll(PDO::FETCH_ASSOC);
}

// ── Procesamiento POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion']  ?? '';   // reprogramar | suspender | reactivar
    $motivo  = trim($_POST['motivo'] ?? '');
    $nueva   = trim($_POST['nueva_fecha_hora'] ?? '');

    // MODO INDIVIDUAL
    if (isset($_POST['modo']) && $_POST['modo'] === 'individual') {
        $pid = filter_input(INPUT_POST, 'id_partido', FILTER_VALIDATE_INT);
        if (!$pid) { goto redirect_back; }

        $actual = $pdo->prepare("SELECT fecha_hora, fecha_hora_original, estado FROM partidos WHERE id_partido=?");
        $actual->execute([$pid]);
        $row = $actual->fetch(PDO::FETCH_ASSOC);
        if (!$row) { goto redirect_back; }

        if ($accion === 'suspender') {
            $pdo->prepare("UPDATE partidos SET estado='suspendido', motivo_reprogramacion=? WHERE id_partido=?")
                ->execute([$motivo, $pid]);
            $_SESSION['mensaje'] = 'Partido suspendido.';
        } elseif ($accion === 'reactivar') {
            // Restituir fecha original si existe, sino no tocar fecha_hora
            $restaurar = $row['fecha_hora_original'] ?? null;
            if ($restaurar) {
                $pdo->prepare("UPDATE partidos SET estado='programado', fecha_hora=?, fecha_hora_original=NULL, motivo_reprogramacion=NULL, jugado=0, en_juego=0 WHERE id_partido=?")
                    ->execute([$restaurar, $pid]);
            } else {
                $pdo->prepare("UPDATE partidos SET estado='programado', motivo_reprogramacion=NULL WHERE id_partido=?")
                    ->execute([$pid]);
            }
            $_SESSION['mensaje'] = 'Partido reactivado.';
        } elseif ($accion === 'reprogramar' && $nueva) {
            // Guardar fecha original solo la primera vez
            $orig = $row['fecha_hora_original'] ?: $row['fecha_hora'];
            $pdo->prepare("UPDATE partidos SET estado='reprogramado', fecha_hora=?, fecha_hora_original=?, motivo_reprogramacion=?, jugado=0, en_juego=0 WHERE id_partido=?")
                ->execute([$nueva, $orig, $motivo, $pid]);
            $_SESSION['mensaje'] = 'Partido reprogramado para ' . date('d/m/Y H:i', strtotime($nueva)) . '.';
        }
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: index.php');
        exit();
    }

    // MODO BATCH
    if (isset($_POST['modo']) && $_POST['modo'] === 'fecha') {
        $t  = filter_input(INPUT_POST, 'torneo_id',    FILTER_VALIDATE_INT);
        $d  = filter_input(INPUT_POST, 'division_id',  FILTER_VALIDATE_INT);
        $fn = filter_input(INPUT_POST, 'fecha_numero', FILTER_VALIDATE_INT);
        if (!$t || !$d || !$fn) { goto redirect_back; }

        try {
            $pdo->beginTransaction();

            if ($accion === 'suspender') {
                $pdo->prepare("UPDATE partidos SET estado='suspendido', motivo_reprogramacion=? WHERE id_torneo=? AND id_division=? AND fecha_numero=? AND jugado=0")
                    ->execute([$motivo, $t, $d, $fn]);
                $n = $pdo->prepare("SELECT ROW_COUNT()")->fetchColumn() ?: 0;
                $_SESSION['mensaje'] = "Se suspendieron los partidos de la Fecha $fn.";

            } elseif ($accion === 'reactivar') {
                // Restaurar fechas originales donde existan
                $pdo->prepare("UPDATE partidos SET estado='programado', fecha_hora=COALESCE(fecha_hora_original, fecha_hora), fecha_hora_original=NULL, motivo_reprogramacion=NULL WHERE id_torneo=? AND id_division=? AND fecha_numero=?")
                    ->execute([$t, $d, $fn]);
                $_SESSION['mensaje'] = "Fecha $fn reactivada.";

            } elseif ($accion === 'reprogramar' && $nueva) {
                // Guardar fecha original + aplicar nueva
                $pdo->prepare("UPDATE partidos SET fecha_hora_original = COALESCE(fecha_hora_original, fecha_hora), estado='reprogramado', fecha_hora=?, motivo_reprogramacion=?, jugado=0, en_juego=0 WHERE id_torneo=? AND id_division=? AND fecha_numero=? AND jugado=0")
                    ->execute([$nueva, $motivo, $t, $d, $fn]);
                $_SESSION['mensaje'] = "Fecha $fn reprogramada para el " . date('d/m/Y H:i', strtotime($nueva)) . ".";
            }

            $pdo->commit();
            $_SESSION['tipo_mensaje'] = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'danger';
        }
        header('Location: index.php');
        exit();
    }

    redirect_back:
    header('Location: reprogramar_fecha.php');
    exit();
}

include '../header.php';
?>
<style>
.modo-tab { cursor:pointer; }
.modo-tab.active { border-bottom:3px solid #004386; color:#004386; font-weight:700; }
.partido-check { border-radius:8px; border:1px solid #e0e4ea; padding:.6rem .85rem; margin-bottom:.5rem; }
.badge-estado-reprog { background:#fd7e14; color:#fff; font-size:.7rem; padding:.2rem .5rem; border-radius:4px; }
.badge-estado-susp   { background:#6c757d; color:#fff; font-size:.7rem; padding:.2rem .5rem; border-radius:4px; }
.badge-estado-prog   { background:#198754; color:#fff; font-size:.7rem; padding:.2rem .5rem; border-radius:4px; }
</style>

<div class="container-fluid px-3 px-md-4 py-3" style="max-width:820px; margin:0 auto;">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-calendar-x text-warning me-1"></i> Reprogramar / Suspender
        </h5>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> py-2 alert-dismissible">
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <?php if ($modo === 'individual' && $partido_individual): ?>
    <!-- ══════════════════ MODO INDIVIDUAL ══════════════════ -->
    <?php $p = $partido_individual; ?>
    <div class="bg-white rounded-3 shadow-sm p-4">

        <!-- Cabecera del partido -->
        <div class="text-center mb-3">
            <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                <strong class="fs-5"><?= htmlspecialchars($p['local']) ?></strong>
                <span class="text-muted fw-light fs-6">vs</span>
                <strong class="fs-5"><?= htmlspecialchars($p['visitante']) ?></strong>
            </div>
            <div class="text-muted small">
                <?= htmlspecialchars($p['torneo']) ?> · <?= htmlspecialchars($p['division']) ?>
                · Fecha <?= $p['fecha_numero'] ?>
            </div>
            <div class="mt-1">
                <i class="bi bi-calendar-event me-1"></i>
                <?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?>
                <?php if ($p['estado'] === 'reprogramado' && $p['fecha_hora_original']): ?>
                <span class="text-muted small ms-2">
                    (antes: <?= date('d/m/Y H:i', strtotime($p['fecha_hora_original'])) ?>)
                </span>
                <?php endif; ?>
            </div>
            <div class="mt-1">
                <?php if ($p['estado'] === 'reprogramado'): ?>
                    <span class="badge-estado-reprog"><i class="bi bi-arrow-repeat me-1"></i>Reprogramado</span>
                <?php elseif ($p['estado'] === 'suspendido'): ?>
                    <span class="badge-estado-susp"><i class="bi bi-slash-circle me-1"></i>Suspendido</span>
                <?php else: ?>
                    <span class="badge-estado-prog"><i class="bi bi-check-circle me-1"></i>Programado</span>
                <?php endif; ?>
                <?php if ($p['motivo_reprogramacion']): ?>
                <span class="text-muted small ms-2"><?= htmlspecialchars($p['motivo_reprogramacion']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <form method="post">
            <input type="hidden" name="modo" value="individual">
            <input type="hidden" name="id_partido" value="<?= $p['id_partido'] ?>">

            <?php if ($p['estado'] !== 'suspendido'): ?>
            <!-- REPROGRAMAR -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Nueva fecha y hora</label>
                <input type="datetime-local" name="nueva_fecha_hora" class="form-control"
                       value="<?= date('Y-m-d\TH:i', strtotime($p['fecha_hora'])) ?>">
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label fw-semibold">Motivo <span class="text-muted fw-normal">(opcional)</span></label>
                <input type="text" name="motivo" class="form-control"
                       value="<?= htmlspecialchars($p['motivo_reprogramacion'] ?? '') ?>"
                       placeholder="Ej: Condiciones climáticas, cancha en mal estado…">
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <?php if ($p['estado'] !== 'suspendido'): ?>
                <button type="submit" name="accion" value="reprogramar" class="btn btn-warning fw-semibold">
                    <i class="bi bi-arrow-repeat me-1"></i> Reprogramar
                </button>
                <button type="submit" name="accion" value="suspender" class="btn btn-danger fw-semibold">
                    <i class="bi bi-slash-circle me-1"></i> Suspender
                </button>
                <?php endif; ?>
                <?php if (in_array($p['estado'], ['reprogramado','suspendido'])): ?>
                <button type="submit" name="accion" value="reactivar" class="btn btn-success fw-semibold"
                        onclick="return confirm('¿Reactivar y restaurar la fecha original?')">
                    <i class="bi bi-check-circle me-1"></i> Reactivar
                </button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary ms-auto">Cancelar</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- ══════════════════ MODO BATCH (fecha completa) ══════════════════ -->

    <!-- Selector torneo/division/fecha -->
    <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
        <form method="get" id="selectorForm">
            <input type="hidden" name="modo" value="fecha">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold mb-1">Torneo</label>
                    <select class="form-select form-select-sm" name="torneo_id" onchange="this.form.submit()">
                        <option value="">Seleccioná…</option>
                        <?php foreach ($torneos as $t): ?>
                        <option value="<?= $t['id_torneo'] ?>" <?= $torneo_sel == $t['id_torneo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($torneo_sel): ?>
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold mb-1">División</label>
                    <select class="form-select form-select-sm" name="division_id" onchange="this.form.submit()">
                        <option value="">Seleccioná…</option>
                        <?php foreach ($divisiones as $d): ?>
                        <option value="<?= $d['id_division'] ?>" <?= $division_sel == $d['id_division'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if ($torneo_sel && $division_sel): ?>
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold mb-1">Nº de Fecha</label>
                    <select class="form-select form-select-sm" name="fecha_numero" onchange="this.form.submit()">
                        <option value="">Seleccioná…</option>
                        <?php foreach ($fechas_numero as $n): ?>
                        <option value="<?= $n ?>" <?= $fecha_sel == $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($torneo_sel && $division_sel && $fecha_sel && !empty($partidos_fecha)): ?>

    <!-- Lista de partidos de la fecha seleccionada -->
    <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
        <p class="fw-semibold mb-2">
            <i class="bi bi-list-ul me-1 text-primary"></i>
            Partidos de la Fecha <?= $fecha_sel ?>
        </p>
        <?php foreach ($partidos_fecha as $p):
            $est = $p['estado'] ?? 'programado';
        ?>
        <div class="partido-check d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($p['local']) ?></strong>
                <span class="text-muted mx-1">vs</span>
                <strong><?= htmlspecialchars($p['visitante']) ?></strong>
                <span class="text-muted small ms-2">
                    <i class="bi bi-clock me-1"></i><?= date('d/m H:i', strtotime($p['fecha_hora'])) ?>
                </span>
                <?php if ($est === 'reprogramado' && $p['fecha_hora_original']): ?>
                <span class="text-muted small">(antes: <?= date('d/m', strtotime($p['fecha_hora_original'])) ?>)</span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($est === 'reprogramado'): ?>
                    <span class="badge-estado-reprog"><i class="bi bi-arrow-repeat"></i> Reprog.</span>
                <?php elseif ($est === 'suspendido'): ?>
                    <span class="badge-estado-susp"><i class="bi bi-slash-circle"></i> Suspendido</span>
                <?php endif; ?>
                <a href="reprogramar_fecha.php?modo=individual&id=<?= $p['id_partido'] ?>"
                   class="btn btn-sm btn-outline-secondary py-0 px-2" title="Editar individualmente">
                    <i class="bi bi-pencil-fill"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Acciones batch -->
    <div class="bg-white rounded-3 shadow-sm p-3">
        <p class="fw-semibold mb-3">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
            Acción en lote — Fecha <?= $fecha_sel ?>
        </p>
        <form method="post">
            <input type="hidden" name="modo"         value="fecha">
            <input type="hidden" name="torneo_id"    value="<?= $torneo_sel ?>">
            <input type="hidden" name="division_id"  value="<?= $division_sel ?>">
            <input type="hidden" name="fecha_numero" value="<?= $fecha_sel ?>">

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">
                        Nueva fecha y hora <span class="text-muted fw-normal">(solo para Reprogramar)</span>
                    </label>
                    <input type="datetime-local" name="nueva_fecha_hora" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Motivo</label>
                    <input type="text" name="motivo" class="form-control form-control-sm"
                           placeholder="Ej: Lluvia, cancha en mal estado…">
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" name="accion" value="reprogramar"
                        class="btn btn-warning btn-sm fw-semibold"
                        onclick="if(!document.querySelector('[name=nueva_fecha_hora]').value){alert('Ingresá la nueva fecha');return false;}">
                    <i class="bi bi-arrow-repeat me-1"></i> Reprogramar fecha completa
                </button>
                <button type="submit" name="accion" value="suspender"
                        class="btn btn-danger btn-sm fw-semibold"
                        onclick="return confirm('¿Suspender todos los partidos no jugados de esta fecha?')">
                    <i class="bi bi-slash-circle me-1"></i> Suspender fecha completa
                </button>
                <button type="submit" name="accion" value="reactivar"
                        class="btn btn-success btn-sm fw-semibold"
                        onclick="return confirm('¿Reactivar y restaurar las fechas originales?')">
                    <i class="bi bi-check-circle me-1"></i> Reactivar
                </button>
            </div>
        </form>
    </div>

    <?php elseif ($torneo_sel && $division_sel && $fecha_sel): ?>
    <div class="alert alert-info py-2">No hay partidos en esa fecha.</div>

    <?php elseif ($torneo_sel && $division_sel): ?>
    <p class="text-muted small"><i class="bi bi-info-circle me-1"></i>Seleccioná el número de fecha.</p>

    <?php elseif ($torneo_sel): ?>
    <p class="text-muted small"><i class="bi bi-info-circle me-1"></i>Seleccioná la división.</p>

    <?php else: ?>
    <p class="text-muted small"><i class="bi bi-info-circle me-1"></i>Seleccioná el torneo para comenzar.</p>
    <?php endif; ?>

    <?php endif; // fin modo batch ?>
</div>

<?php include '../footer.php'; ?>
