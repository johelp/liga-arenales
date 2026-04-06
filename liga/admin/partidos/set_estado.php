<?php
ob_start();session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

$id     = filter_input(INPUT_POST, 'id',     FILTER_VALIDATE_INT);
$accion = trim($_POST['accion'] ?? '');

if (!$id || !in_array($accion, ['iniciar', 'detener', 'pendiente'])) {
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
    exit();
}

$pdo = conectarDB();

try {
    if ($accion === 'iniciar') {
        // Marcar en juego, asegurarse de que no está marcado como jugado
        $pdo->prepare("UPDATE partidos SET en_juego = 1, jugado = 0 WHERE id_partido = ?")
            ->execute([$id]);
    } elseif ($accion === 'detener') {
        // Quitar en juego (sin marcar como jugado; el admin carga el resultado aparte)
        $pdo->prepare("UPDATE partidos SET en_juego = 0 WHERE id_partido = ?")
            ->execute([$id]);
    } elseif ($accion === 'pendiente') {
        // Resetear a pendiente
        $pdo->prepare("UPDATE partidos SET en_juego = 0, jugado = 0, goles_local = NULL, goles_visitante = NULL WHERE id_partido = ?")
            ->execute([$id]);
    }
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
