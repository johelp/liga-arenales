<?php
require_once '../../config.php';
$pdo = conectarDB();

$id_jugador = $_GET['id'] ?? null;

if (!$id_jugador || !is_numeric($id_jugador)) {
    header('Location: jugadores.php?error=ID de jugador no válido');
    exit();
}

$stmt = $pdo->prepare("DELETE FROM jugadores WHERE id_jugador = :id_jugador");
$stmt->bindParam(':id_jugador', $id_jugador, PDO::PARAM_INT);

if ($stmt->execute()) {
    header('Location: jugadores.php?mensaje=Jugador eliminado correctamente');
    exit();
} else {
    header('Location: jugadores.php?error=Error al eliminar el jugador');
    exit();
}
?>