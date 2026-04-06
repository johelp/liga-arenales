<?php
require_once '../../config.php'; // Incluir config.php PRIMERO
include '../header.php';       // Luego incluir el header
$pdo = conectarDB();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$errores = [];
$nombre = '';
$fecha_inicio = '';
$fecha_fin = '';
$descripcion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_fin = trim($_POST['fecha_fin']);
    $descripcion = trim($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre del torneo es obligatorio.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("INSERT INTO torneos (nombre, fecha_inicio, fecha_fin, activo, descripcion)
                               VALUES (:nombre, :fecha_inicio, :fecha_fin, :activo, :descripcion)");
        $stmt->bindParam(':nombre', $nombre);

        // Corrected handling for fecha_inicio
        $fecha_inicio_param = $fecha_inicio ?: null;
        $stmt->bindParam(':fecha_inicio', $fecha_inicio_param);

        // Corrected handling for fecha_fin
        $fecha_fin_param = $fecha_fin ?: null;
        $stmt->bindParam(':fecha_fin', $fecha_fin_param);

        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion', $descripcion);

        if ($stmt->execute()) {
            header('Location: index.php');
            exit();
        } else {
            $errores['general'] = 'Hubo un error al guardar el torneo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Torneo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../header.php'; ?>

    <div class="container my-5">
        <h1>Crear Nuevo Torneo</h1>

        <nav class="mb-3">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Volver a la Lista de Torneos</a>
                </li>
            </ul>
        </nav>

        <main>
            <?php if (!empty($errores['general'])): ?>
                <div class="alert alert-danger"><?= $errores['general']; ?></div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre del Torneo:</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre); ?>" required>
                    <?php if (!empty($errores['nombre'])): ?>
                        <div class="text-danger"><?= $errores['nombre']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio (opcional):</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio); ?>">
                </div>

                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin (opcional):</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin); ?>">
                </div>

                <div class="col-12">
                    <label for="descripcion" class="form-label">Descripción (opcional):</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($descripcion); ?></textarea>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar Torneo</button>
                </div>
            </form>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>