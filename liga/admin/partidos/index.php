<?php
ob_start();session_start();
require_once '../../config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Inicializar conexión a la base de datos
$pdo = conectarDB();

// Procesar filtros
$filters = [];
$params = [];
$whereClause = '';

// Filtro de torneo
$torneo_id = filter_input(INPUT_GET, 'torneo_filter', FILTER_VALIDATE_INT);
if ($torneo_id) {
    $filters[] = "p.id_torneo = :torneo_id";
    $params[':torneo_id'] = $torneo_id;
}

// Filtro de división
$division_id = filter_input(INPUT_GET, 'division_filter', FILTER_VALIDATE_INT);
if ($division_id) {
    $filters[] = "p.id_division = :division_id";
    $params[':division_id'] = $division_id;
}

// Filtro de fecha
$fecha = filter_input(INPUT_GET, 'fecha_filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if ($fecha) {
    $filters[] = "DATE(p.fecha_hora) = :fecha";
    $params[':fecha'] = $fecha;
}

// Filtro de club
$club_id = filter_input(INPUT_GET, 'club_filter', FILTER_VALIDATE_INT);
if ($club_id) {
    $filters[] = "(p.id_club_local = :club_id OR p.id_club_visitante = :club_id)";
    $params[':club_id'] = $club_id;
}

// Filtro de estado
$estado = filter_input(INPUT_GET, 'estado_filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if ($estado !== null && $estado !== '') {
    if ($estado === 'jugado') {
        $filters[] = "p.jugado = 1";
    } elseif ($estado === 'en_juego') {
        $filters[] = "p.en_juego = 1 AND p.jugado = 0";
    } elseif ($estado === 'pendiente') {
        $filters[] = "p.jugado = 0 AND p.en_juego = 0 AND COALESCE(p.estado,'programado') = 'programado'";
    } elseif ($estado === 'reprogramado') {
        $filters[] = "COALESCE(p.estado,'programado') = 'reprogramado'";
    } elseif ($estado === 'suspendido') {
        $filters[] = "COALESCE(p.estado,'programado') = 'suspendido'";
    }
}

// Filtro de fase
$fase_nombre_filter = filter_input(INPUT_GET, 'fase_filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if ($fase_nombre_filter) {
    $filters[] = "p.fase = :fase_nombre_filter";
    $params[':fase_nombre_filter'] = $fase_nombre_filter;
}


// Construir cláusula WHERE si hay filtros
if (!empty($filters)) {
    $whereClause = " WHERE " . implode(" AND ", $filters);
}

// Consultas preparadas para obtener datos
try {
    // Obtener todos los torneos
    $stmt_torneos = $pdo->prepare("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
    $stmt_torneos->execute();
    $torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las divisiones
    $stmt_divisiones = $pdo->prepare("SELECT id_division, nombre FROM divisiones ORDER BY nombre");
    $stmt_divisiones->execute();
    $divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los clubes
    $stmt_clubes = $pdo->prepare("SELECT id_club, nombre_corto, escudo_url FROM clubes ORDER BY nombre_corto");
    $stmt_clubes->execute();
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: Obtener todas las fases directamente de la tabla partidos
    $stmt_fases = $pdo->prepare("SELECT DISTINCT fase FROM partidos WHERE fase IS NOT NULL AND fase != '' ORDER BY fase");
    $stmt_fases->execute();
    $fases_distintas = $stmt_fases->fetchAll(PDO::FETCH_ASSOC);

    // Obtener partidos con filtros aplicados
    $sql = "SELECT p.*,
        COALESCE(p.estado, 'programado') AS estado,
        tl.nombre_corto AS local_nombre_corto,
        tl.escudo_url AS local_escudo_url,
        tv.nombre_corto AS visitante_nombre_corto,
        tv.escudo_url AS visitante_escudo_url,
        d.nombre AS division_nombre,
        t.nombre AS torneo_nombre
    FROM partidos p
    JOIN clubes tl ON p.id_club_local = tl.id_club
    JOIN clubes tv ON p.id_club_visitante = tv.id_club
    JOIN divisiones d ON p.id_division = d.id_division
    JOIN torneos t ON p.id_torneo = t.id_torneo
    {$whereClause}
    ORDER BY p.fecha_hora DESC";
    
    $stmt_partidos = $pdo->prepare($sql);
    $stmt_partidos->execute($params);
    $partidos = $stmt_partidos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error en la base de datos: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
}

// Función para formatear fechas (ya existente)
function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

// Función para obtener la cantidad de partidos por estado
function obtenerEstadisticasPartidos($pdo) {
    try {
        $stmt = $pdo->query("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN jugado = 1 THEN 1 ELSE 0 END) as jugados,
            SUM(CASE WHEN en_juego = 1 AND jugado = 0 THEN 1 ELSE 0 END) as en_juego,
            SUM(CASE WHEN jugado = 0 AND en_juego = 0 THEN 1 ELSE 0 END) as pendientes
            FROM partidos");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['total' => 0, 'jugados' => 0, 'en_juego' => 0, 'pendientes' => 0];
    }
}

$estadisticas = obtenerEstadisticasPartidos($pdo);

// Incluir header después de procesar los datos
include '../header.php';

?>
<style>

        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: #004386;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }
        .btn-primary {
            background-color: #004386;
            border-color: #004386;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #003366;
            border-color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-select, .form-control {
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #004386;
            color: white;
            border: none;
            white-space: nowrap;
        }
        .club-badge {
            width: 24px;
            height: 24px;
            object-fit: cover;
            margin-right: 5px;
            vertical-align: middle;
        }
        .score {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .vs {
            color: #6c757d;
            font-weight: 300;
            padding: 0 5px;
        }
        .badge-status {
            border-radius: 20px;
            padding: 5px 12px;
            font-weight: 500;
            width: 100px;
            text-align: center;
            white-space: nowrap;
        }
        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 6px;
            margin: 0 2px;
        }
        .estadisticas-card {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            display: block;
            color: #004386;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .date-badge {
            background-color: #e9ecef;
            color: #212529;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 5px;
        }
        .filter-badge {
            background-color: #004386;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin: 0 5px 5px 0;
            display: inline-block;
        }
        .filter-badge .close-icon {
            cursor: pointer;
            margin-left: 5px;
            opacity: 0.8;
        }
        .filter-badge .close-icon:hover {
            opacity: 1;
        }
        .pagination-container {
            margin-top: 20px;
        }
        .pagination .page-link {
            color: #004386;
        }
        @keyframes pulse-dot {
            0%,100% { transform:scale(1); opacity:1; }
            50%      { transform:scale(1.6); opacity:.5; }
        }
        @keyframes pulse-badge {
            0%,100% { box-shadow:0 0 0 0 rgba(220,53,69,.5); }
            70%      { box-shadow:0 0 0 6px rgba(220,53,69,0); }
        }
        .estado-en-juego { animation: pulse-badge 1.4s infinite; }
        .pagination .page-item.active .page-link {
            background-color: #004386;
            border-color: #004386;
        }
    
</style>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-whistle"></i> Gestión de Partidos</h1>
            <div class="btn-group">
                <a href="crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Cargar Partido
                </a>
                <a href="cargar_fecha.php" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-plus"></i> Cargar Fecha
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?= $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            // Limpiar el mensaje después de mostrarlo
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        <?php endif; ?>

        <div class="row estadisticas-card g-2">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <span class="stat-number"><?= $estadisticas['total'] ?? 0 ?></span>
                    <span class="stat-label">Total</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <a href="?estado_filter=en_juego" class="text-decoration-none">
                <div class="stat-item border <?= ($estadisticas['en_juego'] ?? 0) > 0 ? 'border-danger' : 'border-transparent' ?>" style="position:relative;overflow:hidden;">
                    <?php if (($estadisticas['en_juego'] ?? 0) > 0): ?>
                    <span style="position:absolute;top:6px;right:8px;width:8px;height:8px;border-radius:50%;background:#dc3545;animation:pulse-dot 1.2s infinite;"></span>
                    <?php endif; ?>
                    <span class="stat-number" style="color:#dc3545;"><?= $estadisticas['en_juego'] ?? 0 ?></span>
                    <span class="stat-label">En juego</span>
                </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <span class="stat-number text-success"><?= $estadisticas['jugados'] ?? 0 ?></span>
                    <span class="stat-label">Jugados</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <span class="stat-number text-warning"><?= $estadisticas['pendientes'] ?? 0 ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-filter"></i> Filtrar Partidos
            </div>
            <div class="card-body">
                <form method="get" id="filtroForm" class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <label for="torneo_filter" class="form-label">Torneo:</label>
                        <select class="form-select" id="torneo_filter" name="torneo_filter">
                            <option value="">Todos los torneos</option>
                            <?php foreach ($torneos as $torneo): ?>
                                <option value="<?= $torneo['id_torneo']; ?>" <?= ($torneo_id == $torneo['id_torneo']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($torneo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label for="division_filter" class="form-label">División:</label>
                        <select class="form-select" id="division_filter" name="division_filter">
                            <option value="">Todas las divisiones</option>
                            <?php foreach ($divisiones as $division): ?>
                                <option value="<?= $division['id_division']; ?>" <?= ($division_id == $division['id_division']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($division['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label for="fecha_filter" class="form-label">Fecha:</label>
                        <input type="date" class="form-control" id="fecha_filter" name="fecha_filter" 
                               value="<?= $fecha ? htmlspecialchars($fecha) : ''; ?>">
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label for="club_filter" class="form-label">Club:</label>
                        <select class="form-select" id="club_filter" name="club_filter">
                            <option value="">Todos los clubes</option>
                            <?php foreach ($clubes as $club): ?>
                                <option value="<?= $club['id_club']; ?>" <?= ($club_id == $club['id_club']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($club['nombre_corto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label for="estado_filter" class="form-label">Estado:</label>
                        <select class="form-select" id="estado_filter" name="estado_filter">
                            <option value="">Todos</option>
                            <option value="en_juego"     <?= ($estado === 'en_juego')     ? 'selected' : ''; ?>>🔴 En juego</option>
                            <option value="jugado"        <?= ($estado === 'jugado')       ? 'selected' : ''; ?>>Jugados</option>
                            <option value="pendiente"     <?= ($estado === 'pendiente')    ? 'selected' : ''; ?>>Pendientes</option>
                            <option value="reprogramado"  <?= ($estado === 'reprogramado') ? 'selected' : ''; ?>>🟠 Reprogramados</option>
                            <option value="suspendido"    <?= ($estado === 'suspendido')   ? 'selected' : ''; ?>>⚫ Suspendidos</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label for="fase_filter" class="form-label">Fase:</label>
                        <select class="form-select" id="fase_filter" name="fase_filter">
                            <option value="">Todas las fases</option>
                            <?php foreach ($fases_distintas as $fase): ?>
                                <option value="<?= htmlspecialchars($fase['fase']); ?>" <?= ($fase_nombre_filter == $fase['fase']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($fase['fase']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($filters)): ?>
            <div class="mb-3">
                <span class="text-muted me-2">Filtros activos:</span>
                <?php if ($torneo_id): ?>
                    <?php $torneo_nombre = array_values(array_filter($torneos, fn($t) => $t['id_torneo'] == $torneo_id))[0]['nombre'] ?? ''; ?>
                    <span class="filter-badge">
                        <i class="bi bi-trophy"></i> <?= htmlspecialchars($torneo_nombre); ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['torneo_filter' => ''])); ?>" class="text-white">
                            <i class="bi bi-x-circle close-icon"></i>
                        </a>
                    </span>
                <?php endif; ?>
                
                <?php if ($division_id): ?>
                    <?php $division_nombre = array_values(array_filter($divisiones, fn($d) => $d['id_division'] == $division_id))[0]['nombre'] ?? ''; ?>
                    <span class="filter-badge">
                        <i class="bi bi-diagram-3"></i> <?= htmlspecialchars($division_nombre); ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['division_filter' => ''])); ?>" class="text-white">
                            <i class="bi bi-x-circle close-icon"></i>
                        </a>
                    </span>
                <?php endif; ?>
                
                <?php if ($fecha): ?>
                    <span class="filter-badge">
                        <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($fecha)); ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['fecha_filter' => ''])); ?>" class="text-white">
                            <i class="bi bi-x-circle close-icon"></i>
                        </a>
                    </span>
                <?php endif; ?>
                
                <?php if ($club_id): ?>
                    <?php $club_nombre = array_values(array_filter($clubes, fn($c) => $c['id_club'] == $club_id))[0]['nombre_corto'] ?? ''; ?>
                    <span class="filter-badge">
                        <i class="bi bi-shield"></i> <?= htmlspecialchars($club_nombre); ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['club_filter' => ''])); ?>" class="text-white">
                            <i class="bi bi-x-circle close-icon"></i>
                        </a>
                    </span>
                <?php endif; ?>
                
                <?php if ($estado !== null && $estado !== ''): ?>
                    <span class="filter-badge">
                        <i class="bi bi-flag"></i> <?= $estado === '1' ? 'Jugados' : 'Pendientes'; ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['estado_filter' => ''])); ?>" class="text-white">
                            <i class="bi bi-x-circle close-icon"></i>
                        </a>
                    </span>
                <?php endif; ?>

                <?php if ($fase_nombre_filter): ?>
                    <span class="filter-badge">
                        <i class="bi bi-shuffle"></i> <?= htmlspecialchars($fase_nombre_filter); ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, ['fase_filter' => ''])); ?>" class="text-white">
                            <i class="bi bi-x-circle close-icon"></i>
                        </a>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($partidos)): ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2" style="font-size: 1.5rem;"></i>
                <span>No hay partidos que coincidan con los criterios de búsqueda.</span>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tabla-partidos">
                    <thead>
    <tr>
        <th class="col-hide-mobile">Fecha</th>
        <th class="col-hide-mobile">Nº</th>
        <th class="col-hide-mobile">Torneo / Div.</th>
        <th class="col-hide-mobile">Fase</th>
        <th>Equipos</th>
        <th class="text-center">Resultado</th>
        <th class="text-center">Estado</th>
        <th class="text-end">Acciones</th>
    </tr>
</thead>
                        <tbody>
                            <?php foreach ($partidos as $partido): ?>
                                <tr>
                                    <td class="align-middle col-hide-mobile">
                                        <span class="date-badge">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('d/m/Y', strtotime($partido['fecha_hora'])); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i', strtotime($partido['fecha_hora'])); ?>
                                        </small>
                                    </td>
                                    <td class="align-middle text-center col-hide-mobile">
                                        <?= htmlspecialchars($partido['fecha_numero']); ?>
                                    </td>
                                    <td class="align-middle col-hide-mobile">
                                        <strong><?= htmlspecialchars($partido['torneo_nombre']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($partido['division_nombre']); ?></small>
                                    </td>
                                    <td class="align-middle col-hide-mobile">
                                        <small class="text-muted"><?= htmlspecialchars($partido['fase'] ?? '—'); ?></small>
                                    </td>

                                    <td class="align-middle">
                                        <!-- Compact date/fecha for mobile only -->
                                        <div class="d-md-none mb-1" style="font-size:.68rem;color:#888;">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('d/m H:i', strtotime($partido['fecha_hora'])); ?>
                                            <?php if ($partido['fecha_numero']): ?> · F<?= $partido['fecha_numero'] ?><?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <?php if (!empty($partido['local_escudo_url'])): ?>
                                                <img src="<?= htmlspecialchars($partido['local_escudo_url']); ?>" alt="<?= htmlspecialchars($partido['local_nombre_corto']); ?>" class="club-badge rounded-circle">
                                            <?php else: ?>
                                                <span class="club-badge d-inline-flex align-items-center justify-content-center bg-light rounded-circle">
                                                    <i class="bi bi-shield text-secondary small"></i>
                                                </span>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($partido['local_nombre_corto']); ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($partido['visitante_escudo_url'])): ?>
                                                <img src="<?= htmlspecialchars($partido['visitante_escudo_url']); ?>" alt="<?= htmlspecialchars($partido['visitante_nombre_corto']); ?>" class="club-badge rounded-circle">
                                            <?php else: ?>
                                                <span class="club-badge d-inline-flex align-items-center justify-content-center bg-light rounded-circle">
                                                    <i class="bi bi-shield text-secondary small"></i>
                                                </span>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($partido['visitante_nombre_corto']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if ($partido['jugado']): ?>
                                            <div class="score">
                                                <?= $partido['goles_local']; ?> - <?= $partido['goles_visitante']; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="vs">VS</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php
                                    $ahora        = time();
                                    $hora_partido = strtotime($partido['fecha_hora']);
                                    $est_prog     = $partido['estado'] ?? 'programado';
                                    $por_iniciar  = !$partido['jugado'] && !$partido['en_juego']
                                                    && $est_prog === 'programado'
                                                    && $hora_partido <= $ahora
                                                    && $hora_partido >= ($ahora - 3600 * 4);
                                    ?>
                                    <td class="text-center align-middle">
                                        <?php if ($est_prog === 'suspendido'): ?>
                                            <span class="badge-status bg-dark text-white" title="<?= htmlspecialchars($partido['motivo_reprogramacion'] ?? '') ?>">
                                                <i class="bi bi-slash-circle-fill me-1"></i> Suspendido
                                            </span>
                                        <?php elseif ($est_prog === 'reprogramado'): ?>
                                            <span class="badge-status text-white" style="background:#fd7e14;"
                                                  title="<?= $partido['fecha_hora_original'] ? 'Antes: '.date('d/m/Y H:i', strtotime($partido['fecha_hora_original'])) : '' ?>">
                                                <i class="bi bi-arrow-repeat me-1"></i> Reprogramado
                                            </span>
                                        <?php elseif ($partido['jugado']): ?>
                                            <span class="badge-status bg-success text-white">
                                                <i class="bi bi-check-circle-fill me-1"></i> Jugado
                                            </span>
                                        <?php elseif ($partido['en_juego']): ?>
                                            <span class="badge-status bg-danger text-white estado-en-juego">
                                                <i class="bi bi-broadcast me-1"></i> En juego
                                            </span>
                                        <?php elseif ($por_iniciar): ?>
                                            <span class="badge-status bg-warning text-dark">
                                                <i class="bi bi-alarm-fill me-1"></i> Por iniciar
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status bg-secondary text-white">
                                                <i class="bi bi-clock me-1"></i> Pendiente
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($partido['motivo_reprogramacion'] && in_array($est_prog, ['reprogramado','suspendido'])): ?>
                                        <div style="font-size:.65rem; color:#888; margin-top:.15rem; white-space:normal; max-width:110px;">
                                            <?= htmlspecialchars($partido['motivo_reprogramacion']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end align-middle">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">

                                        <?php if ($partido['en_juego'] && !$partido['jugado']): ?>
                                            <!-- EN JUEGO: botón detener + cargar resultado -->
                                            <button class="btn btn-sm btn-danger btn-estado action-btn"
                                                    data-id="<?= $partido['id_partido'] ?>"
                                                    data-accion="detener"
                                                    title="Detener (vuelve a Pendiente)">
                                                <i class="bi bi-stop-fill"></i>
                                            </button>
                                            <a href="cargar_resultados_fecha.php?fecha_numero=<?= urlencode($partido['fecha_numero']) ?>&torneo_id=<?= $partido['id_torneo'] ?>&division_id=<?= $partido['id_division'] ?>"
                                               class="btn btn-sm btn-success action-btn" title="Cargar Resultado">
                                                <i class="bi bi-clipboard-check"></i>
                                            </a>
                                        <?php elseif (!$partido['jugado']): ?>
                                            <!-- PENDIENTE / POR INICIAR: botón iniciar -->
                                            <button class="btn btn-sm <?= $por_iniciar ? 'btn-warning' : 'btn-outline-warning' ?> btn-estado action-btn"
                                                    data-id="<?= $partido['id_partido'] ?>"
                                                    data-accion="iniciar"
                                                    title="Marcar como En juego">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                            <a href="cargar_resultados_fecha.php?fecha_numero=<?= urlencode($partido['fecha_numero']) ?>&torneo_id=<?= $partido['id_torneo'] ?>&division_id=<?= $partido['id_division'] ?>"
                                               class="btn btn-sm btn-outline-success action-btn" title="Cargar Resultado directo">
                                                <i class="bi bi-clipboard-check"></i>
                                            </a>
                                        <?php endif; ?>

                                            <a href="editar.php?id=<?= $partido['id_partido']; ?>"
                                               class="btn btn-sm btn-outline-primary action-btn" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="reprogramar_fecha.php?modo=individual&id=<?= $partido['id_partido'] ?>"
                                               class="btn btn-sm btn-outline-warning action-btn" title="Reprogramar / Suspender">
                                                <i class="bi bi-calendar-x"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger action-btn"
                                                    onclick="confirmarEliminar(<?= $partido['id_partido']; ?>, '<?= htmlspecialchars(addslashes($partido['local_nombre_corto'])); ?> vs <?= htmlspecialchars(addslashes($partido['visitante_nombre_corto'])); ?>')"
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <p class="text-muted">Mostrando <?= count($partidos); ?> partidos</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmarEliminarModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el partido <strong id="partidoEliminar"></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer y eliminará también todos los goles y tarjetas asociados a este partido.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <a href="#" id="btnEliminar" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Eliminar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confirmar eliminación
        function confirmarEliminar(id, nombre) {
            document.getElementById('partidoEliminar').textContent = nombre;
            document.getElementById('btnEliminar').href = 'eliminar.php?id=' + id;
            new bootstrap.Modal(document.getElementById('confirmarEliminarModal')).show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Autoenvío del formulario al cambiar filtros
            const filtroForm = document.getElementById('filtroForm');
            filtroForm.querySelectorAll('select').forEach(s => s.addEventListener('change', () => filtroForm.submit()));
            document.getElementById('fecha_filter').addEventListener('change', () => filtroForm.submit());

            // Botones de estado rápido (Iniciar / Detener)
            document.querySelectorAll('.btn-estado').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id     = this.dataset.id;
                    const accion = this.dataset.accion;
                    const fila   = this.closest('tr');
                    const orig   = this.innerHTML;

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    const fd = new FormData();
                    fd.append('id', id);
                    fd.append('accion', accion);

                    fetch('set_estado.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.ok) {
                                // Recarga la página para reflejar el nuevo estado
                                location.reload();
                            } else {
                                alert('Error: ' + (data.error || 'desconocido'));
                                this.disabled = false;
                                this.innerHTML = orig;
                            }
                        })
                        .catch(() => {
                            alert('Error de conexión.');
                            this.disabled = false;
                            this.innerHTML = orig;
                        });
                });
            });
        });
    </script>

<?php include '../footer.php'; ?>
