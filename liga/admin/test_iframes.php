<?php
session_start();
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

// Asegúrate de que config.php esté incluido correctamente
require_once '../config.php';
$pdo = conectarDB();

// Obtener torneos y divisiones para los selectores (si es necesario para tus iframes)
$stmt_torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
$torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);

$stmt_divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY orden");
$divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);

// Variables para los IDs seleccionados
$torneo_seleccionado_tp = isset($_GET['torneo_tp']) ? (int)$_GET['torneo_tp'] : null;
$division_seleccionada_tp = isset($_GET['division_tp']) ? (int)$_GET['division_tp'] : null;
$partido_seleccionado_dp = isset($_GET['partido_dp']) ? (int)$_GET['partido_dp'] : null;
$torneo_seleccionado_pp = isset($_GET['torneo_pp']) ? (int)$_GET['torneo_pp'] : null;
$division_seleccionada_pp = isset($_GET['division_pp']) ? (int)$_GET['division_pp'] : null;

// Obtener algunos IDs de ejemplo si no se han seleccionado
if (!$torneo_seleccionado_tp && !empty($torneos)) {
    $torneo_seleccionado_tp = $torneos[0]['id_torneo'];
}
if (!$division_seleccionada_tp && !empty($divisiones)) {
    $division_seleccionada_tp = $divisiones[0]['id_division'];
}
// Necesitas una forma de obtener un ID de partido válido para probar detalle_partido_iframe.php
// Aquí simplemente consultamos el primer partido que encontremos como ejemplo
if (!$partido_seleccionado_dp) {
    $stmt_partido_ejemplo = $pdo->query("SELECT id_partido FROM partidos LIMIT 1");
    $partido_ejemplo = $stmt_partido_ejemplo->fetch(PDO::FETCH_ASSOC);
    if ($partido_ejemplo) {
        $partido_seleccionado_dp = $partido_ejemplo['id_partido'];
    }
}
if (!$torneo_seleccionado_pp && !empty($torneos)) {
    $torneo_seleccionado_pp = $torneos[0]['id_torneo'];
}
if (!$division_seleccionada_pp && !empty($divisiones)) {
    $division_seleccionada_pp = $divisiones[0]['id_division'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Iframes Públicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .iframe-container {
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        .iframe-title {
            background-color: #f8f9fa;
            padding: 10px;
            font-weight: bold;
        }
        .iframe-wrapper {
            width: 100%;
            overflow: auto; /* Para evitar desbordamiento horizontal */
        }
        .iframe-wrapper iframe {
            width: 100%;
            border: 0;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container my-5">
        <h1>Test de Iframes Públicos</h1>

        <p class="mb-3">Esta página te permite visualizar los diferentes iframes públicos que has creado.</p>

        <h2 class="mt-4">Tabla de Posiciones (iframe)</h2>
        <div class="iframe-container">
            <div class="iframe-title">
                Tabla de Posiciones -
                <form method="get" class="d-inline-block">
                    <input type="hidden" name="pagina" value="test_iframes">
                    <label for="torneo_tp">Torneo:</label>
                    <select name="torneo_tp" id="torneo_tp" class="form-select form-select-sm d-inline-block w-auto">
                        <?php foreach ($torneos as $torneo): ?>
                            <option value="<?= $torneo['id_torneo']; ?>" <?= ($torneo['id_torneo'] == $torneo_seleccionado_tp) ? 'selected' : ''; ?>><?= htmlspecialchars($torneo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="division_tp">División:</label>
                    <select name="division_tp" id="division_tp" class="form-select form-select-sm d-inline-block w-auto">
                        <?php foreach ($divisiones as $division): ?>
                            <option value="<?= $division['id_division']; ?>" <?= ($division['id_division'] == $division_seleccionada_tp) ? 'selected' : ''; ?>><?= htmlspecialchars($division['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Mostrar</button>
                </form>
            </div>
            <div class="iframe-wrapper">
                <?php if ($torneo_seleccionado_tp && $division_seleccionada_tp): ?>
                    <iframe src="../tabla_posiciones_iframe.php?torneo_id=<?= $torneo_seleccionado_tp; ?>&division_id=<?= $division_seleccionada_tp; ?>" height="500px"></iframe>
                <?php else: ?>
                    <p class="p-3">Selecciona un torneo y una división para visualizar la tabla de posiciones.</p>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="mt-4">Próximos Partidos (widget)</h2>
        <div class="iframe-container">
            <div class="iframe-title">
                Próximos Partidos -
                <form method="get" class="d-inline-block">
                    <input type="hidden" name="pagina" value="test_iframes">
                    <label for="torneo_pp">Torneo:</label>
                    <select name="torneo_pp" id="torneo_pp" class="form-select form-select-sm d-inline-block w-auto">
                        <?php foreach ($torneos as $torneo): ?>
                            <option value="<?= $torneo['id_torneo']; ?>" <?= ($torneo['id_torneo'] == $torneo_seleccionado_pp) ? 'selected' : ''; ?>><?= htmlspecialchars($torneo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="division_pp">División:</label>
                    <select name="division_pp" id="division_pp" class="form-select form-select-sm d-inline-block w-auto">
                        <?php foreach ($divisiones as $division): ?>
                            <option value="<?= $division['id_division']; ?>" <?= ($division['id_division'] == $division_seleccionada_pp) ? 'selected' : ''; ?>><?= htmlspecialchars($division['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Mostrar</button>
                </form>
            </div>
            <div class="iframe-wrapper">
                <?php if ($torneo_seleccionado_pp && $division_seleccionada_pp): ?>
                    <iframe src="../proximos_partidos_widget.php?torneo_id=<?= $torneo_seleccionado_pp; ?>&division_id=<?= $division_seleccionada_pp; ?>" height="400px"></iframe>
                <?php else: ?>
                    <p class="p-3">Selecciona un torneo y una división para visualizar los próximos partidos.</p>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="mt-4">Detalle de Partido (iframe)</h2>
        <div class="iframe-container">
            <div class="iframe-title">
                Detalle de Partido - ID: <?= htmlspecialchars($partido_seleccionado_dp ?? 'No Disponible'); ?>
                <?php if ($partido_seleccionado_dp): ?>
                    <form method="get" class="d-inline-block">
                        <input type="hidden" name="pagina" value="test_iframes">
                        <label for="partido_dp">ID Partido:</label>
                        <input type="number" name="partido_dp" id="partido_dp" class="form-control form-control-sm d-inline-block w-auto" value="<?= htmlspecialchars($partido_seleccionado_dp); ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Mostrar</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="iframe-wrapper">
                <?php if ($partido_seleccionado_dp): ?>
                    <iframe src="../detalle_partido_iframe.php?partido_id=<?= $partido_seleccionado_dp; ?>" height="600px"></iframe>
                <?php else: ?>
                    <p class="p-3">No se encontró un ID de partido para mostrar el detalle.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>