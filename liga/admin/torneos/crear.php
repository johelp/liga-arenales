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
$nombre    = '';
$fecha_inicio = '';
$fecha_fin    = '';
$descripcion  = '';
$activo       = 1;
$formato      = 'liga';

$formatos_validos = ['liga', 'playoff', 'grupos_playoff'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre']       ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin    = trim($_POST['fecha_fin']    ?? '');
    $descripcion  = trim($_POST['descripcion']  ?? '');
    $activo       = isset($_POST['activo']) ? 1 : 0;
    $formato      = in_array($_POST['formato'] ?? '', $formatos_validos) ? $_POST['formato'] : 'liga';

    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre del torneo es obligatorio.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("INSERT INTO torneos (nombre, fecha_inicio, fecha_fin, activo, descripcion, formato)
                               VALUES (:nombre, :fecha_inicio, :fecha_fin, :activo, :descripcion, :formato)");
        $stmt->bindValue(':nombre',       $nombre);
        $stmt->bindValue(':fecha_inicio', $fecha_inicio ?: null);
        $stmt->bindValue(':fecha_fin',    $fecha_fin    ?: null);
        $stmt->bindValue(':activo',       $activo, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion',  $descripcion);
        $stmt->bindValue(':formato',      $formato);

        if ($stmt->execute()) {
            $_SESSION['mensaje']      = 'Torneo creado correctamente.';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php');
            exit();
        } else {
            $errores['general'] = 'Hubo un error al guardar el torneo.';
        }
    }
}

include '../header.php';
?>

<div class="container-fluid px-3 px-md-4 py-3" style="max-width:680px; margin:0 auto;">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold"><i class="bi bi-trophy-fill text-warning me-1"></i> Crear Torneo</h5>
    </div>

    <?php if (!empty($errores['general'])): ?>
        <div class="alert alert-danger rounded-3"><?= $errores['general'] ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-3 shadow-sm p-4">
        <form method="post" novalidate>

            <div class="mb-3">
                <label for="nombre" class="form-label fw-semibold">Nombre del torneo <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= !empty($errores['nombre']) ? 'is-invalid' : '' ?>"
                       id="nombre" name="nombre"
                       value="<?= htmlspecialchars($nombre) ?>"
                       placeholder="Ej: Torneo Apertura 2025"
                       required autofocus>
                <?php if (!empty($errores['nombre'])): ?>
                    <div class="invalid-feedback"><?= $errores['nombre'] ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="fecha_inicio" class="form-label fw-semibold">Fecha inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-6">
                    <label for="fecha_fin" class="form-label fw-semibold">Fecha fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
            </div>

            <!-- Formato del torneo -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Formato del torneo <span class="text-danger">*</span></label>
                <div class="row g-2" id="formato-options">
                    <?php
                    $fmts = [
                        'liga'           => ['Liga / Clasificación', 'bi-table',         'Todos juegan contra todos. Se genera tabla de posiciones.'],
                        'playoff'        => ['Play Off',             'bi-diagram-3',      'Formato eliminatorio por etapas (Cuartos, Semi, Final, etc.).'],
                        'grupos_playoff' => ['Grupos + Play Off',    'bi-grid-3x3-gap',  'Fase de grupos con tabla, seguida de eliminatorias.'],
                    ];
                    foreach ($fmts as $val => [$lbl, $ico, $desc]):
                    ?>
                    <div class="col-sm-4">
                        <label class="d-block p-3 border rounded-3 cursor-pointer fmt-card <?= $formato === $val ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' ?>"
                               style="cursor:pointer;" for="fmt_<?= $val ?>">
                            <input type="radio" name="formato" id="fmt_<?= $val ?>" value="<?= $val ?>"
                                   class="d-none fmt-radio" <?= $formato === $val ? 'checked' : '' ?>>
                            <div class="text-center">
                                <i class="bi <?= $ico ?> fs-3 <?= $formato === $val ? 'text-primary' : 'text-muted' ?>"></i>
                                <div class="fw-semibold small mt-1"><?= $lbl ?></div>
                                <div class="text-muted" style="font-size:.72rem;"><?= $desc ?></div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Etapas del Play Off -->
            <div class="mb-3" id="panel-etapas" style="<?= $formato === 'liga' ? 'display:none;' : '' ?>">
                <label class="form-label fw-semibold small">
                    <i class="bi bi-diagram-3 text-primary me-1"></i>
                    Etapas del Play Off <span class="text-muted fw-normal">(opcional, para referencia)</span>
                </label>
                <div class="bg-light border rounded-3 p-3">
                    <p class="text-muted small mb-2">
                        Las etapas se asignan al cargar cada partido (campo <strong>Fase / Etapa</strong>).
                        Ejemplos de nombres de etapa:
                    </p>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (['Final', 'Semifinal', 'Cuartos de Final', 'Octavos de Final', 'Repechaje', 'Grupo A', 'Grupo B'] as $ej): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><?= $ej ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        La tabla de posiciones excluye automáticamente los partidos con etapa asignada.
                    </p>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label fw-semibold">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                          placeholder="Descripción o dedicatoria del torneo"><?= htmlspecialchars($descripcion) ?></textarea>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="activo" name="activo"
                           <?= $activo ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="activo">
                        Torneo activo <span class="text-muted fw-normal small">(visible en la app pública)</span>
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                    <i class="bi bi-save-fill me-1"></i> Guardar Torneo
                </button>
            </div>

        </form>
    </div>

</div>

<script>
// Formato selector
document.querySelectorAll('.fmt-radio').forEach(radio => {
    radio.closest('label').addEventListener('click', function() {
        document.querySelectorAll('.fmt-card').forEach(c => {
            c.classList.remove('border-primary','bg-primary','bg-opacity-10');
            c.classList.add('bg-light');
            c.querySelector('i').classList.remove('text-primary');
            c.querySelector('i').classList.add('text-muted');
        });
        this.classList.add('border-primary','bg-primary','bg-opacity-10');
        this.classList.remove('bg-light');
        this.querySelector('i').classList.add('text-primary');
        this.querySelector('i').classList.remove('text-muted');
        radio.checked = true;

        const panel = document.getElementById('panel-etapas');
        panel.style.display = radio.value === 'liga' ? 'none' : '';
    });
});
</script>
<?php include '../footer.php'; ?>
