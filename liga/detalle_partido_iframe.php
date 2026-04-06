<?php
require_once 'config.php';

// Obtener el ID del partido desde el parámetro GET
$id_partido = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function obtenerEscudoURL(PDO $pdo, int $id_club): ?string
{
    $stmt = $pdo->prepare("SELECT escudo_url FROM clubes WHERE id_club = :id_club");
    $stmt->bindParam(':id_club', $id_club, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado['escudo_url'] : null;
}

function obtenerGolesAmonestaciones(PDO $pdo, int $id_partido, array $partido): array
{
    $data = ['goles_local' => [], 'goles_visitante' => [], 'amonestaciones_local' => [], 'amonestaciones_visitante' => []];

    // Obtener goles
    $stmt_goles = $pdo->prepare("SELECT cl.nombre_corto AS club, j.nombre AS jugador, g.minuto, gc.id_club AS club_id
                                  FROM goles g
                                  JOIN partidos p ON g.id_partido = p.id_partido
                                  JOIN clubes gc ON g.id_club = gc.id_club
                                  LEFT JOIN jugadores j ON g.id_jugador = j.id_jugador
                                  JOIN clubes cl_local ON p.id_club_local = cl_local.id_club
                                  JOIN clubes cl_visitante ON p.id_club_visitante = cl_visitante.id_club
                                  LEFT JOIN clubes cl ON g.id_club = cl.id_club
                                  WHERE g.id_partido = :id_partido
                                  ORDER BY g.minuto ASC");
    $stmt_goles->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
    $stmt_goles->execute();
    while ($gol = $stmt_goles->fetch(PDO::FETCH_ASSOC)) {
        if ($gol['club_id'] == $partido['id_club_local']) {
            $data['goles_local'][] = ($gol['jugador'] ? htmlspecialchars($gol['jugador']) . ' (' . $gol['minuto'] . '\')' : 'Gol en contra (' . $gol['minuto'] . '\')');
        } else {
            $data['goles_visitante'][] = ($gol['jugador'] ? htmlspecialchars($gol['jugador']) . ' (' . $gol['minuto'] . '\')' : 'Gol en contra (' . $gol['minuto'] . '\')');
        }
    }

    // Obtener tarjetas amarillas
    $stmt_amarillas = $pdo->prepare("SELECT cl.nombre_corto AS club, j.nombre AS jugador, t.minuto, tc.id_club AS club_id
                                     FROM tarjetas t
                                     JOIN partidos p ON t.id_partido = p.id_partido
                                     JOIN clubes tc ON t.id_club = tc.id_club
                                     LEFT JOIN jugadores j ON t.id_jugador = j.id_jugador
                                     LEFT JOIN clubes cl ON t.id_club = cl.id_club
                                     WHERE t.id_partido = :id_partido AND t.tipo = 'amarilla'
                                     ORDER BY t.minuto ASC");
    $stmt_amarillas->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
    $stmt_amarillas->execute();
    while ($amarilla = $stmt_amarillas->fetch(PDO::FETCH_ASSOC)) {
        if ($amarilla['club_id'] == $partido['id_club_local']) {
            $data['amonestaciones_local'][] = htmlspecialchars($amarilla['jugador']) . ' (' . $amarilla['minuto'] . '\')';
        } else {
            $data['amonestaciones_visitante'][] = htmlspecialchars($amarilla['jugador']) . ' (' . $amarilla['minuto'] . '\')';
        }
    }

    // Obtener tarjetas rojas
    $stmt_rojas = $pdo->prepare("SELECT cl.nombre_corto AS club, j.nombre AS jugador, t.minuto, tc.id_club AS club_id
                                  FROM tarjetas t
                                  JOIN partidos p ON t.id_partido = p.id_partido
                                  JOIN clubes tc ON t.id_club = tc.id_club
                                  LEFT JOIN jugadores j ON t.id_jugador = j.id_jugador
                                  LEFT JOIN clubes cl ON t.id_club = cl.id_club
                                  WHERE t.id_partido = :id_partido AND t.tipo = 'roja'
                                  ORDER BY t.minuto ASC");
    $stmt_rojas->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
    $stmt_rojas->execute();
    while ($roja = $stmt_rojas->fetch(PDO::FETCH_ASSOC)) {
        if ($roja['club_id'] == $partido['id_club_local']) {
            $data['amonestaciones_local'][] = htmlspecialchars($roja['jugador']) . ' (Roja - ' . $roja['minuto'] . '\')';
        } else {
            $data['amonestaciones_visitante'][] = htmlspecialchars($roja['jugador']) . ' (Roja - ' . $roja['minuto'] . '\')';
        }
    }

    return $data;
}

if ($id_partido > 0) {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT p.*,
                                     tl.id_club AS local_id,
                                     tl.nombre_completo AS local_nombre_completo,
                                     tl.nombre_corto AS local_nombre_corto,
                                     tv.id_club AS visitante_id,
                                     tv.nombre_completo AS visitante_nombre_completo,
                                     tv.nombre_corto AS visitante_nombre_corto,
                                     d.nombre AS division_nombre,
                                     t.nombre AS torneo_nombre
                                FROM partidos p
                                JOIN clubes tl ON p.id_club_local = tl.id_club
                                JOIN clubes tv ON p.id_club_visitante = tv.id_club
                                JOIN divisiones d ON p.id_division = d.id_division
                                JOIN torneos t ON p.id_torneo = t.id_torneo
                                WHERE p.id_partido = :id");
    $stmt->bindParam(':id', $id_partido, PDO::PARAM_INT);
    $stmt->execute();
    $partido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($partido) {
        $escudo_local = obtenerEscudoURL($pdo, $partido['local_id']);
        $escudo_visitante = obtenerEscudoURL($pdo, $partido['visitante_id']);
        $eventos = obtenerGolesAmonestaciones($pdo, $id_partido, $partido); // Pasar $partido aquí

        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Detalle del Partido: <?= htmlspecialchars($partido['local_nombre_corto']); ?> vs <?= htmlspecialchars($partido['visitante_nombre_corto']); ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; }
                .widget-detalle-partido { background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px; }
                .header-partido { text-align: center; margin-bottom: 15px; }
                .escudo { width: 50px; height: 50px; vertical-align: middle; margin: 0 10px; }
                .vs { font-size: 1.5em; font-weight: bold; color: #555; vertical-align: middle; }
                .resultado { font-size: 1.8em; font-weight: bold; color: #333; margin-top: 10px; }
                .info-general { font-size: 0.9em; color: #777; margin-bottom: 10px; text-align: center; }
                .eventos { margin-top: 15px; }
                .eventos h4 { font-size: 1.1em; color: #333; margin-bottom: 8px; }
                .lista-eventos { list-style: none; padding: 0; }
                .lista-eventos li { padding: 5px 0; border-bottom: 1px solid #eee; }
                .lista-eventos li:last-child { border-bottom: none; }
                .gol { color: green; }
                .amarilla { color: orange; }
                .roja { color: red; }
                .lado-local { text-align: left; }
                .lado-visitante { text-align: right; }
                .alineacion-evento { display: flex; justify-content: space-between; }
                .alineacion-evento > span { flex: 1; }
            </style>
        </head>
        <body>
            <div class="widget-detalle-partido">
                <div class="info-general">
                    <?= htmlspecialchars($partido['torneo_nombre']); ?> - <?= htmlspecialchars($partido['division_nombre']); ?><br>
                    <?= date('d/m/Y H:i', strtotime($partido['fecha_hora'])); ?>
                </div>
                <div class="header-partido">
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <?php if ($escudo_local): ?>
                            <img src="<?= htmlspecialchars($escudo_local); ?>" alt="<?= htmlspecialchars($partido['local_nombre_corto']); ?>" class="escudo">
                        <?php else: ?>
                            <span style="width: 50px; height: 50px; display: inline-block; text-align: center; line-height: 50px; font-size: 1.5em; border: 1px solid #ccc; border-radius: 50%; margin: 0 10px; opacity: 0.7;">⚽</span>
                        <?php endif; ?>
                        <span class="vs">VS</span>
                        <?php if ($escudo_visitante): ?>
                            <img src="<?= htmlspecialchars($escudo_visitante); ?>" alt="<?= htmlspecialchars($partido['visitante_nombre_corto']); ?>" class="escudo">
                        <?php else: ?>
                            <span style="width: 50px; height: 50px; display: inline-block; text-align: center; line-height: 50px; font-size: 1.5em; border: 1px solid #ccc; border-radius: 50%; margin: 0 10px; opacity: 0.7;">⚽</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($partido['jugado']): ?>
                        <div class="resultado">
                            <?= htmlspecialchars($partido['goles_local'] !== null ? $partido['goles_local'] : '-'); ?> - <?= htmlspecialchars($partido['goles_visitante'] !== null ? $partido['goles_visitante'] : '-'); ?>
                        </div>
                    <?php else: ?>
                        <p class="mt-2">Partido aún no jugado.</p>
                    <?php endif; ?>
                </div>

                <?php if ($partido['jugado']): ?>
                    <div class="eventos">
                        <?php if (!empty($eventos['goles_local']) || !empty($eventos['goles_visitante'])): ?>
                            <h4>Goles</h4>
                            <ul class="lista-eventos">
                                <?php foreach ($eventos['goles_local'] as $gol): ?>
                                    <li class="gol alineacion-evento"><span class="lado-local"><?= $gol; ?></span><span></span></li>
                                <?php endforeach; ?>
                                <?php foreach ($eventos['goles_visitante'] as $gol): ?>
                                    <li class="gol alineacion-evento"><span></span><span class="lado-visitante"><?= $gol; ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($eventos['amonestaciones_local']) || !empty($eventos['amonestaciones_visitante'])): ?>
                            <h4 class="mt-3">Amonestaciones</h4>
                            <ul class="lista-eventos">
                                <?php foreach ($eventos['amonestaciones_local'] as $amonestacion): ?>
                                    <li class="amarilla alineacion-evento"><span class="lado-local"><?= $amonestacion; ?></span><span></span></li>
                                <?php endforeach; ?>
                                <?php foreach ($eventos['amonestaciones_visitante'] as $amonestacion): ?>
                                    <li class="amarilla alineacion-evento"><span></span><span class="lado-visitante"><?= $amonestacion; ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </body>
        </html>
        <?php
    } else {
        echo "<p>Partido no encontrado.</p>";
    }
} else {
    echo "<p>ID de partido no proporcionado.</p>";
}
?>