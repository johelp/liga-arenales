<?php
require_once '../../config.php';
$pdo = conectarDB();

if (isset($_GET['term']) && isset($_GET['club_id'])) {
    $term = $_GET['term'];
    $club_id = (int)$_GET['club_id'];

    $stmt = $pdo->prepare("SELECT id_jugador, nombre FROM jugadores WHERE id_club = :id_club AND nombre LIKE :term ORDER BY nombre");
    $stmt->bindParam(':id_club', $club_id, PDO::PARAM_INT);
    $stmt->bindValue(':term', '%' . $term . '%', PDO::PARAM_STR);
    $stmt->execute();
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($jugadores);
}
?>