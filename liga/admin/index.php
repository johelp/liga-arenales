<?php
session_start();
require_once '../config.php';
include 'header.php'; // Asegúrate de que header.php tenga la navegación
$pdo = conectarDB();

// --- Lógica para obtener datos para los paneles ---
// Próximos Partidos
$stmt_proximos = $pdo->query("SELECT p.fecha_hora, tl.nombre_corto AS local, tv.nombre_corto AS visitante, d.nombre AS division FROM partidos p JOIN clubes tl ON p.id_club_local = tl.id_club JOIN clubes tv ON p.id_club_visitante = tv.id_club JOIN divisiones d ON p.id_division = d.id_division WHERE p.fecha_hora > NOW() ORDER BY p.fecha_hora ASC LIMIT 5");
$proximos_partidos = $stmt_proximos->fetchAll();

// Últimos Resultados
$stmt_ultimos = $pdo->query("SELECT p.fecha_hora, tl.nombre_corto AS local, tv.nombre_corto AS visitante, p.goles_local, p.goles_visitante FROM partidos p JOIN clubes tl ON p.id_club_local = tl.id_club JOIN clubes tv ON p.id_club_visitante = tv.id_club WHERE p.goles_local IS NOT NULL ORDER BY p.fecha_hora DESC LIMIT 5");
$ultimos_resultados = $stmt_ultimos->fetchAll();

// Resumen de Torneos (ejemplo: torneos con fecha de fin futura)
$stmt_torneos_activos = $pdo->query("SELECT nombre FROM torneos WHERE fecha_fin >= CURDATE()");
$torneos_activos = $stmt_torneos_activos->fetchAll(PDO::FETCH_COLUMN);

?>

<div class="container my-5">
    <h1>Panel de Administración LDGA - AscensionDigital.ar</h1>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">Próximos Partidos</div>
                <div class="card-body">
                    <?php if (empty($proximos_partidos)): ?>
                        <p class="card-text">No hay próximos partidos programados.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($proximos_partidos as $partido): ?>
                                <li class="list-group-item"><?= date('d/m/Y H:i', strtotime($partido['fecha_hora'])); ?> - <?= htmlspecialchars($partido['local']); ?> vs. <?= htmlspecialchars($partido['visitante']); ?> (<?= htmlspecialchars($partido['division']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Últimos Resultados</div>
                <div class="card-body">
                    <?php if (empty($ultimos_resultados)): ?>
                        <p class="card-text">No hay resultados cargados recientemente.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($ultimos_resultados as $resultado): ?>
                                <li class="list-group-item"><?= date('d/m/Y', strtotime($resultado['fecha_hora'])); ?> - <?= htmlspecialchars($resultado['local']); ?> <?= htmlspecialchars($resultado['goles_local'] !== null ? $resultado['goles_local'] : '-'); ?> - <?= htmlspecialchars($resultado['goles_visitante'] !== null ? $resultado['goles_visitante'] : '-'); ?> <?= htmlspecialchars($resultado['visitante']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">Acciones Rápidas</div>
                <div class="card-body">
                    <a href="partidos/cargar_resultados_fecha.php" class="btn btn-primary mb-2 w-100">Cargar Resultados por Fecha</a>
                    <a href="../clubes/crear.php" class="btn btn-secondary mb-2 w-100">Crear Nuevo Club</a>
                    <a href="partidos/cargar_fecha.php" class="btn btn-info mb-2 w-100">Cargar Nueva Fecha de Partidos</a>
                    <a href="../torneos/crear.php" class="btn btn-success mb-2 w-100">Crear Nuevo Torneo</a>
                    <a href="partidos/reprogramar_fecha.php" class="btn btn-info mb-2 w-100">Reprogramar Fecha</a>
            
                    </div>
            </div>

            <div class="card">
                <div class="card-header">Torneos Activos</div>
                <div class="card-body">
                    <?php if (empty($torneos_activos)): ?>
                        <p class="card-text">No hay torneos activos en este momento.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($torneos_activos as $torneo): ?>
                                <li class="list-group-item"><?= htmlspecialchars($torneo); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; // Si tienes un footer ?>