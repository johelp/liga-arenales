<?php
require_once '../../config.php';
$pdo = conectarDB();

// --- Lógica para Agregar Jugadores en Lote ---
if (isset($_POST['agregar_en_lote'])) {
    $club_id_lote = $_POST['club_id_lote'];
    $lista_jugadores = trim($_POST['lista_jugadores']);
    $nombres = explode("\n", $lista_jugadores);
    $errores_lote = [];
    $agregados_lote = 0;

    if (!empty($club_id_lote) && is_numeric($club_id_lote) && !empty($nombres)) {
        $pdo->beginTransaction();
        try {
            $stmtInsertar = $pdo->prepare("INSERT INTO jugadores (nombre, id_club) VALUES (:nombre, :id_club)");
            foreach ($nombres as $nombre) {
                $nombre_limpio = trim($nombre);
                if (!empty($nombre_limpio)) {
                    $stmtInsertar->bindParam(':nombre', $nombre_limpio, PDO::PARAM_STR);
                    $stmtInsertar->bindParam(':id_club', $club_id_lote, PDO::PARAM_INT);
                    $stmtInsertar->execute();
                    $agregados_lote++;
                }
            }
            $pdo->commit();
            $mensaje_lote = "Se agregaron " . $agregados_lote . " jugadores en lote al club seleccionado.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_lote = "Error al agregar jugadores en lote: " . $e->getMessage();
        }
    } else {
        $error_lote = "Por favor, selecciona un club e introduce una lista de nombres de jugadores.";
    }
}

// --- Lógica para Listar Jugadores ---
$club_filtro = $_GET['club'] ?? null;
$where_clause = $club_filtro ? ' WHERE j.id_club = :club_id' : '';
$stmt = $pdo->prepare("SELECT j.id_jugador, j.nombre AS jugador_nombre, c.nombre_completo AS club_nombre
                      FROM jugadores j
                      JOIN clubes c ON j.id_club = c.id_club" . $where_clause . " ORDER BY j.nombre");
if ($club_filtro) {
    $stmt->bindParam(':club_id', $club_filtro, PDO::PARAM_INT);
}
$stmt->execute();
$jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica para Obtener la Lista de Clubes para el Filtro y Formularios ---
$stmtClubes = $pdo->prepare("SELECT id_club, nombre_completo FROM clubes ORDER BY nombre_completo");
$stmtClubes->execute();
$clubes = $stmtClubes->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Jugadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="bi bi-people"></i> Gestión de Jugadores</h1>

        <div>
            <form method="get" class="mb-3">
                <label for="club" class="form-label">Filtrar por Club:</label>
                <select class="form-select form-select-sm" id="club" name="club" onchange="this.form.submit()">
                    <option value="">Todos los Clubes</option>
                    <?php foreach ($clubes as $club): ?>
                        <option value="<?= $club['id_club']; ?>" <?= ($club['id_club'] == $club_filtro) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($club['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <h2>Listado de Jugadores</h2>
        <?php if (empty($jugadores)): ?>
            <p>No hay jugadores registrados.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Club</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jugadores as $jugador): ?>
                        <tr>
                            <td><?= $jugador['id_jugador']; ?></td>
                            <td><?= htmlspecialchars($jugador['jugador_nombre']); ?></td>
                            <td><?= htmlspecialchars($jugador['club_nombre']); ?></td>
                            <td>
                                <a href="editar_jugador.php?id=<?= $jugador['id_jugador']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Editar</a>
                                <a href="eliminar_jugador.php?id=<?= $jugador['id_jugador']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este jugador?')"><i class="bi bi-trash"></i> Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="crear_jugador.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Añadir Nuevo Jugador</a>

        <h2>Agregar Jugadores en Lote</h2>
        <?php if (isset($mensaje_lote)): ?>
            <div class="alert alert-success"><?= $mensaje_lote; ?></div>
        <?php endif; ?>
        <?php if (isset($error_lote)): ?>
            <div class="alert alert-danger"><?= $error_lote; ?></div>
        <?php endif; ?>
        <form method="post" action="" class="mb-3">
            <div class="mb-3">
                <label for="club_id_lote" class="form-label">Seleccionar Club:</label>
                <select class="form-select" id="club_id_lote" name="club_id_lote" required>
                    <option value="">Seleccionar Club</option>
                    <?php foreach ($clubes as $club): ?>
                        <option value="<?= $club['id_club']; ?>"><?= htmlspecialchars($club['nombre_completo']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="lista_jugadores" class="form-label">Lista de Nombres de Jugadores (uno por línea):</label>
                <textarea class="form-control" id="lista_jugadores" name="lista_jugadores" rows="5" required></textarea>
            </div>
            <button type="submit" name="agregar_en_lote" class="btn btn-primary"><i class="bi bi-plus-circle-fill"></i> Agregar en Lote</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>