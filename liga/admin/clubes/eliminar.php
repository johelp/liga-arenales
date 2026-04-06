<?php
ob_start();
session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo    = conectarDB();
$id_club = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_club <= 0) {
    $_SESSION['mensaje']      = 'ID de club inválido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

$check_partidos = $pdo->prepare("SELECT COUNT(*) FROM partidos WHERE id_club_local = ? OR id_club_visitante = ?");
$check_partidos->execute([$id_club, $id_club]);
$partidos_n = $check_partidos->fetchColumn();

$check_jugadores = $pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE id_club = ?");
$check_jugadores->execute([$id_club]);
$jugadores_n = $check_jugadores->fetchColumn();

if ($partidos_n > 0) {
    $_SESSION['mensaje']      = "No se puede eliminar el club porque está asociado a $partidos_n partido(s).";
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}
if ($jugadores_n > 0) {
    $_SESSION['mensaje']      = "No se puede eliminar el club porque tiene $jugadores_n jugador(es) asociado(s).";
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare("DELETE FROM clubes WHERE id_club = ?");
if ($stmt->execute([$id_club])) {
    $_SESSION['mensaje']      = 'Club eliminado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje']      = 'Error al eliminar el club.';
    $_SESSION['tipo_mensaje'] = 'danger';
}
header('Location: index.php');
exit();
