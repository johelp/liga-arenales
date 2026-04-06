<?php
require_once 'config.php';

// Verificar que se haya proporcionado un ID de partido
$id_partido = filter_input(INPUT_GET, 'id_partido', FILTER_VALIDATE_INT);
if (!$id_partido) {
    echo json_encode([
        'error' => 'ID de partido no válido'
    ]);
    exit;
}

try {
    $pdo = conectarDB();
    
    $detalles = [
        'goles' => [],
        'tarjetas' => []
    ];
    
    // Obtener goles
    $stmt_goles = $pdo->prepare("
        SELECT g.*, c.nombre_corto AS club_nombre, j.nombre AS jugador_nombre
        FROM goles g
        JOIN clubes c ON g.id_club = c.id_club
        LEFT JOIN jugadores j ON g.id_jugador = j.id_jugador
        WHERE g.id_partido = :id_partido
        ORDER BY g.minuto
    ");
    $stmt_goles->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
    $stmt_goles->execute();
    $detalles['goles'] = $stmt_goles->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tarjetas
    $stmt_tarjetas = $pdo->prepare("
        SELECT t.*, c.nombre_corto AS club_nombre, j.nombre AS jugador_nombre
        FROM tarjetas t
        JOIN clubes c ON t.id_club = c.id_club
        LEFT JOIN jugadores j ON t.id_jugador = j.id_jugador
        WHERE t.id_partido = :id_partido
        ORDER BY t.minuto
    ");
    $stmt_tarjetas->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
    $stmt_tarjetas->execute();
    $detalles['tarjetas'] = $stmt_tarjetas->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver los detalles en formato JSON
    header('Content-Type: application/json');
    echo json_encode($detalles);
    
} catch (PDOException $e) {
    // Devolver error en formato JSON
    error_log("Error de base de datos en get_detalle_partido.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>