<?php
require_once '../../config.php';
$pdo = conectarDB();

$id_jugador = $_GET['id'] ?? null;

if (!$id_jugador || !is_numeric($id_jugador)) {
    header('Location: jugadores.php?error=ID de jugador no válido');
    exit();
}

// Obtener la información del jugador
$stmtJugador = $pdo->prepare("SELECT id_jugador, nombre, id_club FROM jugadores WHERE id_jugador = :id_jugador");
$stmtJugador->bindParam(':id_jugador', $id_jugador, PDO::PARAM_INT);
$stmtJugador->execute();
$jugador = $stmtJugador->fetch(PDO::FETCH_ASSOC);

if (!$jugador) {
    header('Location: jugadores.php?error=Jugador no encontrado');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_club = $_POST['id_club'];

    if (!empty($nombre) && is_numeric($id_club)) {
        $stmt = $pdo->prepare("UPDATE jugadores SET nombre = :nombre, id_club = :id_club WHERE id_jugador = :id_jugador");
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':id_club', $id_club, PDO::PARAM_INT);
        $stmt->bindParam(':id_jugador', $id_jugador, PDO::PARAM_INT);
        if ($stmt->execute()) {
            header('Location: jugadores.php?mensaje=Jugador actualizado correctamente');
            exit();
        } else {
            $error = "Error al actualizar el jugador.";
        }
    } else {
        $error = "Por favor, introduce el nombre del jugador y selecciona un club.";
    }
}

$stmtClubes = $pdo->prepare("SELECT id_club, nombre_completo FROM clubes ORDER BY nombre_completo");
$stmtClubes->execute();
$clubes = $stmtClubes->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Jugador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="bi bi-pencil"></i> Editar Jugador</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="id_jugador" value="<?= $jugador['id_jugador']; ?>">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Jugador:</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($jugador['nombre']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="id_club" class="form-label">Club:</label>
                <select class="form-select" id="id_club" name="id_club" required>
                    <option value="">Seleccionar Club</option>
                    <?php foreach ($clubes as $club): ?>
                        <option value="<?= $club['id_club']; ?>" <?= ($jugador['id_club'] == $club['id_club']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($club['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cambios</button>
            <a href="jugadores.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>