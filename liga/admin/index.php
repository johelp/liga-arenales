<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = conectarDB();

// ── Stats generales ──
$stats = $pdo->query("
    SELECT
        COUNT(*)                                           AS total,
        SUM(CASE WHEN jugado = 1 THEN 1 ELSE 0 END)       AS jugados,
        SUM(CASE WHEN jugado = 0 AND fecha_hora > NOW() THEN 1 ELSE 0 END) AS proximos
    FROM partidos
")->fetch(PDO::FETCH_ASSOC);

// ── Torneos activos ──
$torneos_activos = $pdo->query("
    SELECT id_torneo, nombre, fecha_inicio, fecha_fin
    FROM torneos
    WHERE activo = 1
    ORDER BY fecha_inicio DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Próximos 5 partidos (cualquier torneo) ──
$proximos = $pdo->query("
    SELECT p.fecha_hora, p.fase, p.estadio,
           tl.nombre_corto AS local, tl.escudo_url AS escudo_local,
           tv.nombre_corto AS visitante, tv.escudo_url AS escudo_visitante,
           d.nombre AS division, t.nombre AS torneo
    FROM partidos p
    JOIN clubes tl ON p.id_club_local = tl.id_club
    JOIN clubes tv ON p.id_club_visitante = tv.id_club
    JOIN divisiones d ON p.id_division = d.id_division
    JOIN torneos t ON p.id_torneo = t.id_torneo
    WHERE p.jugado = 0 AND p.fecha_hora >= NOW()
    ORDER BY p.fecha_hora ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ── Últimos 5 resultados ──
$ultimos = $pdo->query("
    SELECT p.fecha_hora, p.goles_local, p.goles_visitante, p.fase,
           tl.nombre_corto AS local, tl.escudo_url AS escudo_local,
           tv.nombre_corto AS visitante, tv.escudo_url AS escudo_visitante,
           d.nombre AS division
    FROM partidos p
    JOIN clubes tl ON p.id_club_local = tl.id_club
    JOIN clubes tv ON p.id_club_visitante = tv.id_club
    JOIN divisiones d ON p.id_division = d.id_division
    WHERE p.jugado = 1
    ORDER BY p.fecha_hora DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<div class="container-fluid px-3 px-md-4 py-3" style="max-width:960px; margin:0 auto;">

    <!-- Bienvenida -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0">Panel de Administración</h5>
            <small class="text-muted">Liga Deportiva General Arenales</small>
        </div>
        <span class="text-muted small"><?= date('d/m/Y') ?></span>
    </div>

    <!-- ── Acciones rápidas ── -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <a href="partidos/cargar_resultados_fecha.php"
               class="d-flex flex-column align-items-center justify-content-center p-3 rounded-3 text-white text-decoration-none h-100"
               style="background:#004386; min-height:80px;">
                <i class="bi bi-clipboard-check-fill" style="font-size:1.6rem"></i>
                <span class="mt-1 fw-semibold text-center" style="font-size:.82rem">Cargar<br>Resultados</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="partidos/cargar_fecha.php"
               class="d-flex flex-column align-items-center justify-content-center p-3 rounded-3 text-white text-decoration-none h-100"
               style="background:#0d6efd; min-height:80px;">
                <i class="bi bi-calendar-plus-fill" style="font-size:1.6rem"></i>
                <span class="mt-1 fw-semibold text-center" style="font-size:.82rem">Cargar<br>Fecha</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="partidos/index.php"
               class="d-flex flex-column align-items-center justify-content-center p-3 rounded-3 text-white text-decoration-none h-100"
               style="background:#6610f2; min-height:80px;">
                <i class="bi bi-calendar2-week-fill" style="font-size:1.6rem"></i>
                <span class="mt-1 fw-semibold text-center" style="font-size:.82rem">Ver<br>Partidos</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="torneos/"
               class="d-flex flex-column align-items-center justify-content-center p-3 rounded-3 text-white text-decoration-none h-100"
               style="background:#f0a500; min-height:80px;">
                <i class="bi bi-trophy-fill" style="font-size:1.6rem"></i>
                <span class="mt-1 fw-semibold text-center" style="font-size:.82rem">Torneos</span>
            </a>
        </div>
    </div>

    <!-- ── Stats ── -->
    <div class="row g-2 mb-4">
        <div class="col-4">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div class="fw-bold text-primary" style="font-size:1.8rem"><?= $stats['total'] ?? 0 ?></div>
                <div class="text-muted small">Partidos</div>
            </div>
        </div>
        <div class="col-4">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div class="fw-bold text-success" style="font-size:1.8rem"><?= $stats['jugados'] ?? 0 ?></div>
                <div class="text-muted small">Jugados</div>
            </div>
        </div>
        <div class="col-4">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div class="fw-bold text-warning" style="font-size:1.8rem"><?= $stats['proximos'] ?? 0 ?></div>
                <div class="text-muted small">Próximos</div>
            </div>
        </div>
    </div>

    <!-- ── Torneos activos ── -->
    <?php if (!empty($torneos_activos)): ?>
    <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-trophy-fill text-warning me-1"></i> Torneo en curso</h6>
        <?php foreach ($torneos_activos as $t): ?>
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><?= htmlspecialchars($t['nombre']) ?></span>
            <small class="text-muted">
                <?= $t['fecha_inicio'] ? date('d/m/y', strtotime($t['fecha_inicio'])) : '—' ?>
                <?= $t['fecha_fin'] ? ' → ' . date('d/m/y', strtotime($t['fecha_fin'])) : '' ?>
            </small>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Próximos partidos -->
        <div class="col-md-6">
            <div class="bg-white rounded-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-calendar-event text-primary me-1"></i> Próximos</h6>
                    <a href="partidos/index.php?estado_filter=0" class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem">Ver todos</a>
                </div>
                <div class="p-2">
                    <?php if (empty($proximos)): ?>
                        <p class="text-muted small p-2 mb-0">Sin partidos próximos.</p>
                    <?php else: ?>
                        <?php foreach ($proximos as $p): ?>
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <div class="text-center" style="min-width:38px">
                                <div class="fw-bold" style="font-size:.7rem; color:#004386"><?= date('d/m', strtotime($p['fecha_hora'])) ?></div>
                                <div class="text-muted" style="font-size:.65rem"><?= date('H:i', strtotime($p['fecha_hora'])) ?></div>
                            </div>
                            <div class="flex-grow-1" style="font-size:.82rem">
                                <div class="d-flex align-items-center gap-1">
                                    <?php if ($p['escudo_local']): ?><img src="<?= htmlspecialchars($p['escudo_local']) ?>" style="width:16px;height:16px;object-fit:contain"><?php endif; ?>
                                    <span><?= htmlspecialchars($p['local']) ?></span>
                                    <span class="text-muted mx-1">vs</span>
                                    <?php if ($p['escudo_visitante']): ?><img src="<?= htmlspecialchars($p['escudo_visitante']) ?>" style="width:16px;height:16px;object-fit:contain"><?php endif; ?>
                                    <span><?= htmlspecialchars($p['visitante']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($p['division']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimos resultados -->
        <div class="col-md-6">
            <div class="bg-white rounded-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-check-circle-fill text-success me-1"></i> Últimos resultados</h6>
                    <a href="partidos/index.php?estado_filter=1" class="btn btn-sm btn-outline-success rounded-pill" style="font-size:.75rem">Ver todos</a>
                </div>
                <div class="p-2">
                    <?php if (empty($ultimos)): ?>
                        <p class="text-muted small p-2 mb-0">Sin resultados recientes.</p>
                    <?php else: ?>
                        <?php foreach ($ultimos as $p): ?>
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <div class="text-center" style="min-width:38px">
                                <div class="fw-bold" style="font-size:.7rem; color:#555"><?= date('d/m', strtotime($p['fecha_hora'])) ?></div>
                            </div>
                            <div class="flex-grow-1" style="font-size:.82rem">
                                <div class="d-flex align-items-center gap-1">
                                    <?php if ($p['escudo_local']): ?><img src="<?= htmlspecialchars($p['escudo_local']) ?>" style="width:16px;height:16px;object-fit:contain"><?php endif; ?>
                                    <span><?= htmlspecialchars($p['local']) ?></span>
                                    <span class="fw-bold px-1"><?= $p['goles_local'] ?> - <?= $p['goles_visitante'] ?></span>
                                    <?php if ($p['escudo_visitante']): ?><img src="<?= htmlspecialchars($p['escudo_visitante']) ?>" style="width:16px;height:16px;object-fit:contain"><?php endif; ?>
                                    <span><?= htmlspecialchars($p['visitante']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($p['division']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<?php include 'footer.php'; ?>
