<?php
/**
 * Script para obtener divisiones por torneo vía AJAX
 * Este archivo devuelve un JSON con las divisiones disponibles para un torneo específico
 */

require_once 'config.php';
header('Content-Type: application/json');

// Verificar si se recibió el ID del torneo
$torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);

if (!$torneo_id) {
    echo json_encode([
        'error' => 'ID de torneo no válido'
    ]);
    exit;
}

try {
    $pdo = conectarDB();
    
    // Consultar las divisiones relacionadas con el torneo seleccionado
    $stmt = $pdo->prepare("
        SELECT DISTINCT d.id_division, d.nombre
        FROM divisiones d
        JOIN clubes_en_division ced ON d.id_division = ced.id_division
        WHERE ced.id_torneo = :id_torneo
        ORDER BY d.orden, d.nombre
    ");
    
    $stmt->bindParam(':id_torneo', $torneo_id, PDO::PARAM_INT);
    $stmt->execute();
    $divisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver JSON con las divisiones
    echo json_encode($divisiones);
    
} catch (PDOException $e) {
    // Devolver error en formato JSON
    echo json_encode([
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>