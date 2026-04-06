<?php
require_once '../../config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$id_club = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_club > 0) {
    // Verificar si el club está asociado a algún partido
    $stmt_check_partidos = $pdo->prepare("SELECT COUNT(*) FROM partidos WHERE id_club_local = :id OR id_club_visitante = :id");
    $stmt_check_partidos->bindParam(':id', $id_club, PDO::PARAM_INT);
    $stmt_check_partidos->execute();
    $partidos_asociados = $stmt_check_partidos->fetchColumn();

    if ($partidos_asociados > 0) {
        $mensaje = '<div class="alert alert-danger">No se puede eliminar el club porque está asociado a ' . $partidos_asociados . ' partidos.</div>';
    } else {
        // Verificar si el club tiene jugadores asociados
        $stmt_check_jugadores = $pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE id_club = :id");
        $stmt_check_jugadores->bindParam(':id', $id_club, PDO::PARAM_INT);
        $stmt_check_jugadores->execute();
        $jugadores_asociados = $stmt_check_jugadores->fetchColumn();

        if ($jugadores_asociados > 0) {
            $mensaje = '<div class="alert alert-danger">No se puede eliminar el club porque tiene ' . $jugadores_asociados . ' jugadores asociados.</div>';
        } else {
            $stmt_delete = $pdo->prepare("DELETE FROM clubes WHERE id_club = :id");
            $stmt_delete->bindParam(':id', $id_club, PDO::PARAM_INT);

            if ($stmt_delete->execute()) {
                header('Location: index.php?mensaje=club_eliminado');
                exit();
            } else {
                $mensaje = '<div class="alert alert-danger">Hubo un error al eliminar el club.</div>';
            }
        }
    }
} else {
    $mensaje = '<div class="alert alert-warning">ID de club inválido.</div>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../header.php'; ?>

    <div class="container my-5">
        <h1>Eliminar Club</h1>

        <nav class="mb-3">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Volver a la Lista de Clubes</a>
                </li>
            </ul>
        </nav>

        <main>
            <?php if (isset($mensaje)): ?>
                <?= $mensaje; ?>
            <?php else: ?>
                <div class="alert alert-info">¿Estás seguro de que deseas eliminar este club? Esta acción no se puede deshacer.</div>
                <p><a href="eliminar.php?id=<?= $id_club; ?>&confirmar=1" class="btn btn-danger">Confirmar Eliminación</a>
                   <a href="index.php" class="btn btn-secondary">Cancelar</a></p>
            <?php endif; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>