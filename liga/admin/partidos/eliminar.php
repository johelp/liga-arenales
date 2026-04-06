<?php
require_once '../../config.php'; // Adjust path as needed
$pdo = conectarDB();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_partido = (int)$_GET['id'];

    try {
        // Start a transaction to ensure atomicity (all or nothing)
        $pdo->beginTransaction();

        // 1. Delete related records from the 'tarjetas' table
        $stmt_delete_tarjetas = $pdo->prepare("DELETE FROM tarjetas WHERE id_partido = :id_partido");
        $stmt_delete_tarjetas->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
        $stmt_delete_tarjetas->execute();

        // 2. Delete the record from the 'partidos' table
        $stmt_delete_partido = $pdo->prepare("DELETE FROM partidos WHERE id_partido = :id_partido");
        $stmt_delete_partido->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
        $stmt_delete_partido->execute();

        // Commit the transaction
        $pdo->commit();

        // Redirect or display a success message
        header("Location: index.php?mensaje=Partido eliminado correctamente");
        exit();

    } catch (PDOException $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        die("Error al eliminar el partido: " . $e->getMessage());
    }
} else {
    die("ID de partido inválido.");
}
?>