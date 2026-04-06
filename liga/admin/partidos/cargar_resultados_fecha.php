<?php
session_start();
require_once '../../config.php';
include '../header.php';

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$errores = [];
$mensaje = '';
$divisiones = [];
$partidos = [];
$torneos = [];

// Obtener todos los torneos para el formulario de selección
$stmt_torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
$torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las divisiones para el formulario de selección
$stmt_divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY nombre");
$divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario para filtrar partidos
if (isset($_POST['filtrar_partidos'])) {
    $id_torneo_filtrar = $_POST['id_torneo'] ?? null; // Obtener el ID del torneo
    $id_division_filtrar = $_POST['id_division'] ?? null;
    $fecha_numero_filtrar = $_POST['fecha_numero'] ?? null;

    if (empty($id_torneo_filtrar) || !is_numeric($id_torneo_filtrar)) {
        $errores['filtrado'] = 'Por favor, selecciona un torneo.';
    }
    if (empty($id_division_filtrar) || !is_numeric($id_division_filtrar)) {
        $errores['filtrado'] = 'Por favor, selecciona una división.';
    }
    if (empty($fecha_numero_filtrar) || !is_numeric($fecha_numero_filtrar)) {
        $errores['filtrado'] = 'Por favor, ingresa el número de fecha.';
    }

    if (empty($errores['filtrado'])) {
        $stmt_partidos = $pdo->prepare("SELECT
                    p.id_partido,
                    cl.nombre_corto AS nombre_corto_local,
                    cv.nombre_corto AS nombre_corto_visitante,
                    p.goles_local,
                    p.goles_visitante,
                    p.jugado
                FROM partidos p
                JOIN clubes cl ON p.id_club_local = cl.id_club
                JOIN clubes cv ON p.id_club_visitante = cv.id_club
                WHERE p.id_torneo = :id_torneo AND p.id_division = :id_division AND p.fecha_numero = :fecha_numero
                ORDER BY p.fecha_hora");
        $stmt_partidos->bindParam(':id_torneo', $id_torneo_filtrar, PDO::PARAM_INT);
        $stmt_partidos->bindParam(':id_division', $id_division_filtrar, PDO::PARAM_INT);
        $stmt_partidos->bindParam(':fecha_numero', $fecha_numero_filtrar, PDO::PARAM_INT);
        $stmt_partidos->execute();
        $partidos = $stmt_partidos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($partidos)) {
            $mensaje = '<div class="alert alert-info">No se encontraron partidos para el torneo, división y fecha seleccionadas.</div>';
        }
    }
}

// Procesar el formulario para guardar los resultados y marcar como jugados
if (isset($_POST['guardar_resultados'])) {
    $resultados_guardados = 0;
    $errores_guardado = [];

    foreach ($_POST['id_partido'] as $key => $id_partido) {
        $goles_local = $_POST['goles_local'][$key] ?? null;
        $goles_visitante = $_POST['goles_visitante'][$key] ?? null;
        $jugado = isset($_POST['jugado'][$key]) ? 1 : 0; // Obtener el estado del checkbox

        if (!is_numeric($goles_local) || $goles_local < 0 || !is_numeric($goles_visitante) || $goles_visitante < 0) {
            $errores_guardado[] = "Por favor, ingresa resultados válidos (números no negativos) para todos los partidos.";
            break; // Si hay un error en algún partido, detenemos la validación para mostrar el mensaje
        }

        $stmt_update = $pdo->prepare("UPDATE partidos SET goles_local = :goles_local, goles_visitante = :goles_visitante, jugado = :jugado WHERE id_partido = :id_partido");
        $stmt_update->bindParam(':goles_local', $goles_local, PDO::PARAM_INT);
        $stmt_update->bindParam(':goles_visitante', $goles_visitante, PDO::PARAM_INT);
        $stmt_update->bindParam(':jugado', $jugado, PDO::PARAM_INT);
        $stmt_update->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            $resultados_guardados++;
        } else {
            $errores_guardado[] = "Hubo un error al guardar el resultado del partido con ID: " . $id_partido;
        }
    }

    if (empty($errores_guardado) && $resultados_guardados > 0) {
        $mensaje = '<div class="alert alert-success">Se guardaron los resultados y el estado de ' . $resultados_guardados . ' partidos correctamente.</div>';
        // Recargar los partidos para mostrar los resultados guardados y el estado "jugado"
        if (isset($_POST['division_actual']) && isset($_POST['fecha_actual'])) {
            $id_division_filtrar = $_POST['division_actual'];
            $fecha_numero_filtrar = $_POST['fecha_actual'];
            $stmt_partidos = $pdo->prepare("SELECT
                    p.id_partido,
                    cl.nombre_corto AS nombre_corto_local,
                    cv.nombre_corto AS nombre_corto_visitante,
                    p.goles_local,
                    p.goles_visitante,
                    p.jugado
                FROM partidos p
                JOIN clubes cl ON p.id_club_local = cl.id_club
                JOIN clubes cv ON p.id_club_visitante = cv.id_club
                WHERE p.id_division = :id_division AND p.fecha_numero = :fecha_numero
                ORDER BY p.fecha_hora");
            $stmt_partidos->bindParam(':id_division', $id_division_filtrar, PDO::PARAM_INT);
            $stmt_partidos->bindParam(':fecha_numero', $fecha_numero_filtrar, PDO::PARAM_INT);
            $stmt_partidos->execute();
            $partidos = $stmt_partidos->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (!empty($errores_guardado)) {
        $mensaje = '<div class="alert alert-danger">Hubo errores al guardar los resultados:<ul><li>' . implode('</li><li>', $errores_guardado) . '</li></ul></div>';
    } elseif (isset($_POST['guardar_resultados']) && $resultados_guardados === 0) {
        $mensaje = '<div class="alert alert-info">No se realizaron cambios en los resultados.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Resultados por Fecha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="container my-5">
        <h1>Cargar Resultados por Fecha</h1>

        <nav class="mb-3">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Volver a la Lista de Partidos</a>
                </li>
            </ul>
        </nav>

        <main>
            <?php if ($mensaje): ?>
                <?= $mensaje; ?>
            <?php endif; ?>

            <form method="post" class="mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-md-auto">
            <label for="id_torneo" class="form-label">Torneo:</label>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="id_torneo" name="id_torneo" required>
                <option value="">Seleccionar Torneo</option>
                <?php foreach ($torneos as $torneo): ?>
                    <option value="<?= $torneo['id_torneo']; ?>" <?= (isset($_POST['id_torneo']) && $_POST['id_torneo'] == $torneo['id_torneo']) ? 'selected' : ''; ?>><?= htmlspecialchars($torneo['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <label for="id_division" class="form-label">División:</label>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="id_division" name="id_division" required>
                <option value="">Seleccionar División</option>
                <?php foreach ($divisiones as $division): ?>
                    <option value="<?= $division['id_division']; ?>" <?= (isset($_POST['id_division']) && $_POST['id_division'] == $division['id_division']) ? 'selected' : ''; ?>><?= htmlspecialchars($division['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <label for="fecha_numero" class="form-label">Número de Fecha:</label>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" id="fecha_numero" name="fecha_numero" required value="<?= $_POST['fecha_numero'] ?? ''; ?>">
        </div>
        <div class="col-md-auto">
            <button type="submit" name="filtrar_partidos" class="btn btn-outline-primary">Mostrar Partidos</button>
        </div>
    </div>
    <?php if (!empty($errores['filtrado'])): ?>
        <div class="alert alert-danger mt-2"><?= $errores['filtrado']; ?></div>
    <?php endif; ?>
</form>

            <?php if (!empty($partidos)): ?>
                <h2>Ingresar Resultados</h2>
                <form method="post">
                    <input type="hidden" name="division_actual" value="<?= $_POST['id_division'] ?? ''; ?>">
                    <input type="hidden" name="fecha_actual" value="<?= $_POST['fecha_numero'] ?? ''; ?>">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Local</th>
                                <th>Visitante</th>
                                <th class="text-center">Goles Local</th>
                                <th class="text-center">Goles Visitante</th>
                                <th class="text-center">Jugado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partidos as $partido): ?>
                                <tr>
                                    <td><?= htmlspecialchars($partido['nombre_corto_local']); ?></td>
                                    <td><?= htmlspecialchars($partido['nombre_corto_visitante']); ?></td>
                                    <td class="text-center">
                                        <input type="hidden" name="id_partido[]" value="<?= $partido['id_partido']; ?>">
                                        <input type="number" class="form-control form-control-sm mx-auto" style="width: 60px;" name="goles_local[]" value="<?= htmlspecialchars($partido['goles_local']); ?>" min="0">
                                    </td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm mx-auto" style="width: 60px;" name="goles_visitante[]" value="<?= htmlspecialchars($partido['goles_visitante']); ?>" min="0">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="jugado[]" value="<?= $partido['id_partido']; ?>" <?= $partido['jugado'] ? 'checked' : ''; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="guardar_resultados" class="btn btn-success">Guardar Resultados y Estado</button>
                </form>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>