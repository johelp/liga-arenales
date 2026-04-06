<?php
require_once '../../config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

// Obtener todos los torneos
try {
    $stmt_torneos = $pdo->prepare("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
    $stmt_torneos->execute();
    $torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al obtener los torneos: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
}

$torneo_seleccionado = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
$division_seleccionada = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$fecha_numero_seleccionada = filter_input(INPUT_GET, 'fecha_numero', FILTER_VALIDATE_INT);
$nueva_fecha_hora = filter_input(INPUT_POST, 'nueva_fecha_hora', FILTER_SANITIZE_STRING);

$divisiones = [];
if ($torneo_seleccionado) {
    try {
        $stmt_divisiones = $pdo->prepare("
            SELECT DISTINCT d.id_division, d.nombre
            FROM partidos p
            JOIN divisiones d ON p.id_division = d.id_division
            WHERE p.id_torneo = :torneo_id
            ORDER BY d.nombre
        ");
        $stmt_divisiones->bindParam(':torneo_id', $torneo_seleccionado, PDO::PARAM_INT);
        $stmt_divisiones->execute();
        $divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al obtener las divisiones: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

$fechas_numero = [];
if ($torneo_seleccionado && $division_seleccionada) {
    try {
        $stmt_fechas = $pdo->prepare("SELECT DISTINCT fecha_numero FROM partidos WHERE id_torneo = :torneo_id AND id_division = :division_id ORDER BY fecha_numero");
        $stmt_fechas->bindParam(':torneo_id', $torneo_seleccionado, PDO::PARAM_INT);
        $stmt_fechas->bindParam(':division_id', $division_seleccionada, PDO::PARAM_INT);
        $stmt_fechas->execute();
        $fechas_numero = $stmt_fechas->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al obtener los números de fecha: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $torneo_seleccionado && $division_seleccionada && $fecha_numero_seleccionada && $nueva_fecha_hora) {
    try {
        $pdo->beginTransaction();
        $stmt_update = $pdo->prepare("UPDATE partidos SET fecha_hora = :nueva_fecha_hora WHERE id_torneo = :torneo_id AND id_division = :division_id AND fecha_numero = :fecha_numero");
        $stmt_update->bindParam(':nueva_fecha_hora', $nueva_fecha_hora, PDO::PARAM_STR);
        $stmt_update->bindParam(':torneo_id', $torneo_seleccionado, PDO::PARAM_INT);
        $stmt_update->bindParam(':division_id', $division_seleccionada, PDO::PARAM_INT);
        $stmt_update->bindParam(':fecha_numero', $fecha_numero_seleccionada, PDO::PARAM_INT);
        $stmt_update->execute();

        $filas_afectadas = $stmt_update->rowCount();
        $pdo->commit();

        $_SESSION['mensaje'] = "Se reprogramaron {$filas_afectadas} partidos para la fecha número {$fecha_numero_seleccionada}.";
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: index.php'); // Redirigir a la lista de partidos
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['mensaje'] = 'Error al reprogramar la fecha: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

include '../header.php';

?>
<style>

        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background-color: #004386;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }
        .btn-primary {
            background-color: #004386;
            border-color: #004386;
        }
        .btn-primary:hover {
            background-color: #003366;
            border-color: #003366;
        }
        .form-select, .form-control {
            border-radius: 8px;
        }
    
</style>
<div class="container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-event"></i> Reprogramar Fecha Completa
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i> <?= $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['tipo_mensaje']);
                    ?>
                <?php endif; ?>

                <form method="get" class="mb-3">
                    <div class="mb-3">
                        <label for="torneo_id" class="form-label">Torneo:</label>
                        <select class="form-select" id="torneo_id" name="torneo_id" onchange="this.form.submit()">
                            <option value="">Seleccionar Torneo</option>
                            <?php foreach ($torneos as $torneo): ?>
                                <option value="<?= $torneo['id_torneo']; ?>" <?= ($torneo_seleccionado == $torneo['id_torneo']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($torneo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($torneo_seleccionado): ?>
                        <div class="mb-3">
                            <label for="division_id" class="form-label">División:</label>
                            <select class="form-select" id="division_id" name="division_id" onchange="this.form.submit()">
                                <option value="">Seleccionar División</option>
                                <?php foreach ($divisiones as $division): ?>
                                    <option value="<?= $division['id_division']; ?>" <?= ($division_seleccionada == $division['id_division']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($division['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ($torneo_seleccionado && $division_seleccionada): ?>
                        <div class="mb-3">
                            <label for="fecha_numero" class="form-label">Número de Fecha:</label>
                            <select class="form-select" id="fecha_numero" name="fecha_numero" onchange="this.form.submit()">
                                <option value="">Seleccionar Número de Fecha</option>
                                <?php foreach ($fechas_numero as $numero): ?>
                                    <option value="<?= $numero; ?>" <?= ($fecha_numero_seleccionada == $numero) ? 'selected' : ''; ?>>
                                        <?= $numero; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>

                <?php if ($torneo_seleccionado && $division_seleccionada && $fecha_numero_seleccionada): ?>
                    <hr>
                    <h3>Reprogramar Partidos de la Fecha Número <?= $fecha_numero_seleccionada ?></h3>
                    <form method="post">
                        <div class="mb-3">
                            <label for="nueva_fecha_hora" class="form-label">Nueva Fecha y Hora de Inicio (para el primer partido):</label>
                            <input type="datetime-local" class="form-control" id="nueva_fecha_hora" name="nueva_fecha_hora" required>
                            <small class="form-text text-muted">Se ajustará la hora de inicio de todos los partidos de esta fecha.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise"></i> Reprogramar Fecha
                        </button>
                        <input type="hidden" name="torneo_id" value="<?= $torneo_seleccionado ?>">
                        <input type="hidden" name="division_id" value="<?= $division_seleccionada ?>">
                        <input type="hidden" name="fecha_numero" value="<?= $fecha_numero_seleccionada ?>">
                    </form>
                <?php elseif ($torneo_seleccionado && $division_seleccionada): ?>
                    <p class="text-info"><i class="bi bi-info-circle-fill me-2"></i> Selecciona un número de fecha para reprogramar.</p>
                <?php elseif ($torneo_seleccionado): ?>
                    <p class="text-info"><i class="bi bi-info-circle-fill me-2"></i> Selecciona una división para ver los números de fecha.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a la Gestión de Partidos</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../footer.php'; ?>
