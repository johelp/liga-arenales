<?php
ob_start();
session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();
$id_torneo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_torneo <= 0) {
    $_SESSION['mensaje']      = 'ID de torneo inválido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

// Verificar dependencias
$count_partidos = $pdo->prepare("SELECT COUNT(*) FROM partidos WHERE id_torneo = ?");
$count_partidos->execute([$id_torneo]);

$count_clubes = $pdo->prepare("SELECT COUNT(*) FROM clubes_en_division WHERE id_torneo = ?");
$count_clubes->execute([$id_torneo]);

if ($count_partidos->fetchColumn() > 0 || $count_clubes->fetchColumn() > 0) {
    $_SESSION['mensaje']      = 'No se puede eliminar el torneo porque tiene partidos o clubes asignados.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare("DELETE FROM torneos WHERE id_torneo = ?");
if ($stmt->execute([$id_torneo])) {
    $_SESSION['mensaje']      = 'Torneo eliminado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje']      = 'Error al eliminar el torneo.';
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: index.php');
exit();
