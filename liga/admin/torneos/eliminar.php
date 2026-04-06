<?php
require_once '../../config.php'; // Incluir config.php PRIMERO
include '../header.php';       // Luego incluir el header
$pdo = conectarDB();


$id_torneo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_torneo > 0) {
    // Verificar si el torneo tiene partidos o clubes asignados
    $stmt_partidos = $pdo->prepare("SELECT COUNT(*) FROM partidos WHERE id_torneo = :id");
    $stmt_partidos->bindParam(':id', $id_torneo, PDO::PARAM_INT);
    $stmt_partidos->execute();
    $count_partidos = $stmt_partidos->fetchColumn();

    $stmt_clubes_en_division = $pdo->prepare("SELECT COUNT(*) FROM clubes_en_division WHERE id_torneo = :id");
    $stmt_clubes_en_division->bindParam(':id', $id_torneo, PDO::PARAM_INT);
    $stmt_clubes_en_division->execute();
    $count_clubes_en_division = $stmt_clubes_en_division->fetchColumn();

    if ($count_partidos > 0 || $count_clubes_en_division > 0) {
        echo "<script>alert('No se puede eliminar el torneo porque tiene partidos o clubes asignados.'); window.location.href = 'index.php';</script>";
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM torneos WHERE id_torneo = :id");
    $stmt->bindParam(':id', $id_torneo, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Location: index.php');
        exit();
    } else {
        die("Error al eliminar el torneo.");
    }
} else {
    die("ID de torneo inválido.");
}
?>