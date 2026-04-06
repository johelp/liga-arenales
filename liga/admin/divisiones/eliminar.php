<?php
require_once '../../config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$id_division = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_division > 0) {
    // Verificar si la división tiene partidos o clubes asignados
    $stmt_partidos = $pdo->prepare("SELECT COUNT(*) FROM partidos WHERE id_division = :id");
    $stmt_partidos->bindParam(':id', $id_division, PDO::PARAM_INT);
    $stmt_partidos->execute();
    $count_partidos = $stmt_partidos->fetchColumn();

    $stmt_clubes_en_division = $pdo->prepare("SELECT COUNT(*) FROM clubes_en_division WHERE id_division = :id");
    $stmt_clubes_en_division->bindParam(':id', $id_division, PDO::PARAM_INT);
    $stmt_clubes_en_division->execute();
    $count_clubes_en_division = $stmt_clubes_en_division->fetchColumn();

    if ($count_partidos > 0 || $count_clubes_en_division > 0) {
        echo "<script>alert('No se puede eliminar la división porque tiene partidos o clubes asignados.'); window.location.href = 'index.php';</script>";
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM divisiones WHERE id_division = :id");
    $stmt->bindParam(':id', $id_division, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Location: index.php');
        exit();
    } else {
        die("Error al eliminar la división.");
    }
} else {
    die("ID de división inválido.");
}
?>