<?php
require_once 'config.php';
session_start();

// Función para obtener todos los torneos
function obtenerTorneos(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SELECT id_torneo, nombre, activo FROM torneos ORDER BY activo DESC, nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener torneos: " . $e->getMessage());
        return [];
    }
}

// Función para obtener el torneo activo por defecto
function obtenerTorneoActivo(PDO $pdo): ?array
{
    try {
        $stmt = $pdo->query("SELECT id_torneo, nombre FROM torneos WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Error al obtener torneo activo: " . $e->getMessage());
        return null;
    }
}

// Función para obtener todas las divisiones de un torneo específico
function obtenerDivisionesPorTorneo(PDO $pdo, int $id_torneo): array
{
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT d.id_division, d.nombre
                                FROM divisiones d
                                JOIN clubes_en_division ced ON d.id_division = ced.id_division
                                WHERE ced.id_torneo = :id_torneo
                                ORDER BY d.orden, d.nombre");
        $stmt->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener divisiones: " . $e->getMessage());
        return [];
    }
}

// Función para obtener últimos resultados
function obtenerUltimosResultados(PDO $pdo, ?int $torneo_id = null, ?int $division_id = null, int $limite = 5): array
{
    try {
        $params = [];
        $sql = "SELECT p.id_partido, p.fecha_hora, p.goles_local, p.goles_visitante,
                       cl.nombre_corto AS club_local, cl.escudo_url AS escudo_local,
                       cv.nombre_corto AS club_visitante, cv.escudo_url AS escudo_visitante,
                       d.nombre AS division_nombre, t.nombre AS torneo_nombre
                FROM partidos p
                JOIN clubes cl ON p.id_club_local = cl.id_club
                JOIN clubes cv ON p.id_club_visitante = cv.id_club
                JOIN divisiones d ON p.id_division = d.id_division
                JOIN torneos t ON p.id_torneo = t.id_torneo
                WHERE p.jugado = 1";
        
        if ($torneo_id) {
            $sql .= " AND p.id_torneo = :torneo_id";
            $params[':torneo_id'] = $torneo_id;
        }
        
        if ($division_id) {
            $sql .= " AND p.id_division = :division_id";
            $params[':division_id'] = $division_id;
        }
        
        $sql .= " ORDER BY p.fecha_hora DESC LIMIT :limite";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener últimos resultados: " . $e->getMessage());
        return [];
    }
}



function mostrarProximosPartidos($pdo, $division_id = null, $torneo_id = null, $limit = 5) {
    try {
        $sql = "SELECT
                    p.fecha_hora,
                    tl.nombre_corto AS local_nombre,
                    tv.nombre_corto AS visitante_nombre,
                    tl.escudo_url AS local_escudo,
                    tv.escudo_url AS visitante_escudo,
                    d.nombre AS division_nombre,
                    t.nombre AS torneo_nombre
                FROM partidos p
                JOIN clubes tl ON p.id_club_local = tl.id_club
                JOIN clubes tv ON p.id_club_visitante = tv.id_club
                JOIN divisiones d ON p.id_division = d.id_division
                JOIN torneos t ON p.id_torneo = t.id_torneo
                WHERE p.jugado = 0 AND p.fecha_hora > NOW()";
        
        $params = [];
        if ($division_id) {
            $sql .= " AND p.id_division = :division_id";
            $params[':division_id'] = $division_id;
        }
        if ($torneo_id) {
            $sql .= " AND p.id_torneo = :torneo_id";
            $params[':torneo_id'] = $torneo_id;
        }
        
        $sql .= " ORDER BY p.fecha_hora ASC LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $proximos_partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($proximos_partidos)) {
            echo '<div class="card proximos-partidos-card">';
            echo '<div class="card-header"><i class="bi bi-calendar-event"></i> Próximos Partidos</div>';
            echo '<div class="card-body p-0">';
            echo '<div class="table-responsive">';
            echo '<table class="table table-hover mb-0">';
            echo '<tbody>';
            
            foreach ($proximos_partidos as $partido) {
                $fecha_formateada = date('d/m/Y', strtotime($partido['fecha_hora']));
                $hora_formateada = date('H:i', strtotime($partido['fecha_hora']));
                
                echo '<tr>';
                echo '<td class="text-center" style="width: 90px;">';
                echo '<div class="small fw-bold">' . $fecha_formateada . '</div>';
                echo '<div class="small text-muted">' . $hora_formateada . '</div>';
                echo '</td>';
                
                echo '<td>';
                echo '<div class="d-flex align-items-center justify-content-end">';
                echo '<span class="me-2">' . htmlspecialchars($partido['local_nombre']) . '</span>';
                if ($partido['local_escudo']) {
                    echo '<img src="' . htmlspecialchars($partido['local_escudo']) . '" alt="' . htmlspecialchars($partido['local_nombre']) . '" class="club-badge">';
                } else {
                    echo '<div class="club-badge"></div>';
                }
                echo '</div>';
                echo '</td>';
                
                echo '<td class="text-center" style="width: 40px;"><span class="resultado">VS</span></td>';
                
                echo '<td>';
                echo '<div class="d-flex align-items-center">';
                if ($partido['visitante_escudo']) {
                    echo '<img src="' . htmlspecialchars($partido['visitante_escudo']) . '" alt="' . htmlspecialchars($partido['visitante_nombre']) . '" class="club-badge">';
                } else {
                    echo '<div class="club-badge"></div>';
                }
                echo '<span class="ms-2">' . htmlspecialchars($partido['visitante_nombre']) . '</span>';
                echo '</div>';
                echo '</td>';
                
                echo '<td class="text-end">';
                if (!$division_id) {
                    echo '<span class="badge bg-primary">' . htmlspecialchars($partido['division_nombre']) . '</span> ';
                }
                if (!$torneo_id) {
                    echo '<span class="badge bg-secondary">' . htmlspecialchars($partido['torneo_nombre']) . '</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>'; // table-responsive end
            echo '</div>'; // card-body end
            echo '</div>'; // card end
        } else {
            echo '<div class="card">';
            echo '<div class="card-header"><i class="bi bi-calendar-event"></i> Próximos Partidos</div>';
            echo '<div class="card-body">';
            echo '<p class="mb-0 text-center">No hay próximos partidos programados.</p>';
            echo '</div>';
            echo '</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger" role="alert">';
        echo '<i class="bi bi-exclamation-triangle-fill me-2"></i> Error al cargar los próximos partidos: ' . $e->getMessage();
        echo '</div>';
    }
}


// Función para obtener noticias
function obtenerNoticias(PDO $pdo, int $limite = 3): array
{
    try {
        $stmt = $pdo->prepare("SELECT id, titulo, contenido, fecha_publicacion, imagen_url 
                               FROM noticias 
                               WHERE activo = 1 
                               ORDER BY fecha_publicacion DESC 
                               LIMIT :limite");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener noticias: " . $e->getMessage());
        // Si la tabla no existe, devolver una noticia predeterminada
        if (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
            return [
                [
                    'id' => 1,
                    'titulo' => 'Bienvenidos a la nueva web',
                    'contenido' => 'Este es el nuevo portal oficial de la Liga Deportiva. Aquí encontrarás toda la información sobre torneos, resultados y próximos partidos.',
                    'fecha_publicacion' => date('Y-m-d H:i:s'),
                    'imagen_url' => null
                ]
            ];
        }
        return [];
    }
}

// Inicializar conexión a la base de datos
try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener datos para la página
$torneos = obtenerTorneos($pdo);

// Obtener el ID del torneo seleccionado (si existe) o usar el torneo activo por defecto
$torneo_seleccionado_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
$division_seleccionada_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);

// Si no hay torneo seleccionado, intentar usar el torneo activo
if (!$torneo_seleccionado_id) {
    $torneoActivo = obtenerTorneoActivo($pdo);
    if ($torneoActivo) {
        $torneo_seleccionado_id = $torneoActivo['id_torneo'];
    }
}

// Obtener divisiones para el torneo seleccionado
$divisiones = [];
if ($torneo_seleccionado_id) {
    $divisiones = obtenerDivisionesPorTorneo($pdo, $torneo_seleccionado_id);
    
    // Si hay divisiones pero no hay división seleccionada, usar la primera
    if (!empty($divisiones) && !$division_seleccionada_id) {
        $division_seleccionada_id = $divisiones[0]['id_division'];
    }
}

// Obtener últimos resultados
$ultimos_resultados = obtenerUltimosResultados($pdo, $torneo_seleccionado_id, $division_seleccionada_id);

// Obtener noticias
$noticias = obtenerNoticias($pdo);

// Establecer el título de la página
$titulo_pagina = "Liga Deportiva de General Arenales";

// Obtener la fecha actual para el copyright
$anio_actual = date('Y');

// Determinar si el usuario está autenticado para mostrar el enlace de administración
$mostrar_admin = isset($_SESSION['admin_autenticado']) && $_SESSION['admin_autenticado'] === true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal oficial de la Liga Deportiva de General Arenales - Resultados, tablas de posiciones y próximos partidos.">
    <title><?= $titulo_pagina ?></title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #004386;
            --secondary-color: #E63946;
            --accent-color: #FFD700;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --text-color: #333;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            margin: 0;
            padding-bottom: 30px;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #002752 100%);
            color: white;
            padding: 30px 0 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            position: relative;
        }
        
        .header h1 {
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .logo {
            max-width: 80px;
            margin-right: 15px;
        }
        
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-link {
            position: absolute;
            top: 15px;
            right: 20px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .admin-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .card {
            background-color: #fff;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .card-header .bi {
            margin-right: 8px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-select, .btn {
            border-radius: var(--border-radius);
            padding: 10px 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #003366;
            border-color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #d62b3a;
            border-color: #d62b3a;
        }
        
        iframe {
            width: 100%;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        iframe:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }
        
        .resultado {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 6px 12px;
            font-weight: 600;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .footer {
            background-color: var(--dark-bg);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
            font-size: 0.9rem;
        }
        
        .footer a {
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .spinner-border {
            display: none;
        }
        
        .loading .spinner-border {
            display: inline-block;
        }
        
        .club-badge {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            object-fit: contain;
        }
        
        .news-card {
            border-left: 4px solid var(--primary-color);
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .news-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .news-card h5 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .news-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 8px;
            display: block;
        }
        
        .skeleton-loading {
            position: relative;
            background-color: #f6f7f8;
            overflow: hidden;
        }
        
        .skeleton-loading::after {
            content: "";
            display: block;
            position: absolute;
            top: 0;
            width: 100%;
            height: 100%;
            animation: skeleton-loading 1.5s infinite;
            background: linear-gradient(90deg, #f6f7f8, #edeef1, #f6f7f8);
            background-size: 200% 100%;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 20px 0 15px;
            }
            
            .logo {
                max-width: 60px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .admin-link {
                top: 10px;
                right: 10px;
                font-size: 0.8rem;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <!-- Puedes añadir aquí un logo si está disponible -->
            <h1><?= htmlspecialchars($titulo_pagina) ?></h1>
        </div>
        <p class="lead">AscensionDigital.ar | FM Enceuntro 103.1Mhz</p>
        <?php if ($mostrar_admin || true): ?>
            <a href="admin/index.php" class="admin-link"><i class="bi bi-gear-fill"></i> Administración</a>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Tabla de Posiciones -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-table"></i> Tabla de Posiciones</div>
                        <?php if ($torneo_seleccionado_id && $division_seleccionada_id): ?>
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars(array_column(array_filter($torneos, fn($t) => $t['id_torneo'] == $torneo_seleccionado_id), 'nombre')[0] ?? ''); ?>
                                - 
                                <?= htmlspecialchars(array_column(array_filter($divisiones, fn($d) => $d['id_division'] == $division_seleccionada_id), 'nombre')[0] ?? ''); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="get" id="filtro-form" class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label for="torneo_id" class="form-label">Torneo:</label>
                                <select class="form-select" id="torneo_id" name="torneo_id">
                                    <option value="">-- Seleccionar Torneo --</option>
                                    <?php foreach ($torneos as $torneo): ?>
                                        <option value="<?= $torneo['id_torneo']; ?>" 
                                                <?= ($torneo['id_torneo'] == $torneo_seleccionado_id) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($torneo['nombre']); ?>
                                            <?= $torneo['activo'] ? ' (Activo)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="division_id" class="form-label">División:</label>
                                <select class="form-select" id="division_id" name="division_id" <?= empty($divisiones) ? 'disabled' : ''; ?>>
                                    <option value="">-- Seleccionar División --</option>
                                    <?php foreach ($divisiones as $division): ?>
                                        <option value="<?= $division['id_division']; ?>" 
                                                <?= ($division_seleccionada_id == $division['id_division']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($division['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="submit" id="mostrar_tabla" class="btn btn-primary w-100">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <span class="btn-text">Mostrar</span>
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 tabla-container">
                            <?php if (!$torneo_seleccionado_id || !$division_seleccionada_id): ?>
                                <div id="mensaje_tabla" class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    <?php if (!$torneo_seleccionado_id): ?>
                                        Selecciona un torneo y una división para ver la tabla de posiciones.
                                    <?php elseif (empty($divisiones)): ?>
                                        No hay divisiones disponibles para este torneo.
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div id="iframe-container" class="position-relative" style="min-height: 400px;">
                                    <div class="skeleton-loading" style="height: 400px; border-radius: 8px;"></div>
                                    <iframe id="tabla_posiciones_frame" 
                                            src="tabla_posiciones_iframe.php?torneo_id=<?= $torneo_seleccionado_id ?>&division_id=<?= $division_seleccionada_id ?>" 
                                            style="position: absolute; top: 0; left: 0; height: 500px; width: 100%;"
                                            onload="this.previousElementSibling.style.display='none';"
                                            loading="lazy"></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
        
    <div class="card-body">
        <div id="proximos_partidos">
            <?php
            // Mostrar los próximos partidos del torneo/división seleccionados, o todos si no se ha seleccionado ninguno
            mostrarProximosPartidos($pdo, $division_seleccionada_id, $torneo_seleccionado_id);
            ?>
        </div>
    </div>

            </div>
            
            <div class="col-lg-4">
                <!-- Últimos Resultados -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-trophy"></i> Últimos Resultados
                    </div>
                    <div class="card-body">
                        <?php if (empty($ultimos_resultados)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No hay resultados recientes disponibles.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <tbody>
                                        <?php foreach ($ultimos_resultados as $resultado): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?= date('d/m/Y', strtotime($resultado['fecha_hora'])); ?>
                                                    <div><small class="text-muted"><?= htmlspecialchars($resultado['division_nombre']); ?></small></div>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="text-end" style="width: 45%;">
                                                            <div class="d-flex align-items-center justify-content-end">
                                                                <span><?= htmlspecialchars($resultado['club_local']); ?></span>
                                                                <?php if (!empty($resultado['escudo_local'])): ?>
                                                                    <img src="<?= htmlspecialchars($resultado['escudo_local']); ?>" alt="Escudo" class="club-badge ms-2" style="width: 24px; height: 24px;">
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="resultado">
                                                            <?= $resultado['goles_local']; ?> - <?= $resultado['goles_visitante']; ?>
                                                        </div>
                                                        <div class="text-start" style="width: 45%;">
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($resultado['escudo_visitante'])): ?>
                                                                    <img src="<?= htmlspecialchars($resultado['escudo_visitante']); ?>" alt="Escudo" class="club-badge me-2" style="width: 24px; height: 24px;">
                                                                <?php endif; ?>
                                                                <span><?= htmlspecialchars($resultado['club_visitante']); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="resultados.php<?= $torneo_seleccionado_id ? '?torneo_id=' . $torneo_seleccionado_id . ($division_seleccionada_id ? '&division_id=' . $division_seleccionada_id : '') : ''; ?>" class="btn btn-outline-primary btn-sm">
                                    Ver todos los resultados <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Noticias o Anuncios -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-newspaper"></i> Noticias
                    </div>
                    <div class="card-body">
                        <?php if (empty($noticias)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No hay noticias disponibles.
                            </div>
                        <?php else: ?>
                            <?php foreach ($noticias as $noticia): ?>
                                <div class="news-card">
                                    <span class="news-date">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?>
                                    </span>
                                    <h5><i class="bi bi-star me-1"></i> <?= htmlspecialchars($noticia['titulo']); ?></h5>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars(substr($noticia['contenido'], 0, 150) . (strlen($noticia['contenido']) > 150 ? '...' : ''))); ?></p>
                                    <?php if (strlen($noticia['contenido']) > 150): ?>
                                        <a href="noticia.php?id=<?= $noticia['id']; ?>" class="btn btn-sm btn-link px-0 mt-2">Leer más</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($noticias) > 1): ?>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="https://ascensiondigital.ar/deportes/" class="btn btn-outline-primary btn-sm">Ver todas las noticias <i class="bi bi-arrow-right"></i></a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p>&copy; <?= $anio_actual ?> Liga Deportiva de General Arenales. Todos los derechos reservados.</p>
                    <p>Desarrollado por <a href="https://ascensiondigital.ar" target="_blank" rel="noopener noreferrer">AscensionDigital.ar</a></p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejador para el select de torneos
            const torneoSelect = document.getElementById('torneo_id');
            const divisionSelect = document.getElementById('division_id');
            const mostrarTablaBtn = document.getElementById('mostrar_tabla');
            const tablaFrame = document.getElementById('tabla_posiciones_frame');
            const filtroForm = document.getElementById('filtro-form');
            
            // Función para cargar divisiones según el torneo seleccionado
            torneoSelect.addEventListener('change', function() {
                const torneoId = this.value;
                
                if (!torneoId) {
                    // Si no hay torneo seleccionado, deshabilitar el select de divisiones
                    divisionSelect.innerHTML = '<option value="">-- Seleccionar División --</option>';
                    divisionSelect.disabled = true;
                    return;
                }
                
                // Mostrar indicador de carga
                divisionSelect.disabled = true;
                
                // Realizar petición AJAX para obtener las divisiones del torneo
                fetch('get_divisiones.php?torneo_id=' + torneoId)
                    .then(response => response.json())
                    .then(data => {
                        // Limpiar y llenar el select de divisiones
                        divisionSelect.innerHTML = '<option value="">-- Seleccionar División --</option>';
                        
                        if (data.length > 0) {
                            data.forEach(division => {
                                const option = document.createElement('option');
                                option.value = division.id_division;
                                option.textContent = division.nombre;
                                divisionSelect.appendChild(option);
                            });
                            divisionSelect.disabled = false;
                            
                            // Seleccionar automáticamente la primera división si no hay ninguna seleccionada
                            if (divisionSelect.value === "") {
                                divisionSelect.selectedIndex = 1;
                            }
                        } else {
                            // No hay divisiones para este torneo
                            divisionSelect.innerHTML = '<option value="">No hay divisiones disponibles</option>';
                            divisionSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar divisiones:', error);
                        divisionSelect.innerHTML = '<option value="">Error al cargar divisiones</option>';
                        divisionSelect.disabled = true;
                    });
            });
            
            // Auto-submit cuando cambian los select
            torneoSelect.addEventListener('change', function() {
                const torneoId = this.value;
                
                // Si hay división seleccionada después del cambio, enviar el formulario
                setTimeout(function() {
                    if (torneoId && divisionSelect.value) {
                        filtroForm.submit();
                    }
                }, 500); // Pequeño retraso para permitir que se carguen las divisiones
            });
            
            divisionSelect.addEventListener('change', function() {
                const divisionId = this.value;
                const torneoId = torneoSelect.value;
                
                if (torneoId && divisionId) {
                    filtroForm.submit();
                }
            });
            
            // Manejar el indicador de carga cuando se envía el formulario
            filtroForm.addEventListener('submit', function() {
                if (mostrarTablaBtn) {
                    mostrarTablaBtn.classList.add('loading');
                    mostrarTablaBtn.disabled = true;
                }
            });
            
            // Detección de iframe cargado para ocultar cargadores
            if (tablaFrame) {
                tablaFrame.onload = function() {
                    if (mostrarTablaBtn) {
                        mostrarTablaBtn.classList.remove('loading');
                        mostrarTablaBtn.disabled = false;
                    }
                };
            }
            
            // Función para actualizar los próximos partidos vía AJAX
            function actualizarProximosPartidos(torneoId, divisionId) {
                const proximosPartidosContainer = document.getElementById('proximos_partidos');
                if (!proximosPartidosContainer) return;
                
                // Mostrar indicador de carga
                proximosPartidosContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando próximos partidos...</p></div>';
                
                // Construir URL con parámetros
                let url = 'proximos_partidos_widget.php';
                const params = new URLSearchParams();
                if (torneoId) params.append('torneo_id', torneoId);
                if (divisionId) params.append('division_id', divisionId);
                if (params.toString()) url += '?' + params.toString();
                
                // Realizar petición AJAX
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red o servidor');
                        }
                        return response.text();
                    })
                    .then(html => {
                        proximosPartidosContainer.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error al actualizar próximos partidos:', error);
                        proximosPartidosContainer.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error al cargar los próximos partidos.</div>';
                    });
            }
            
            // Inicializar la página si ya hay selecciones
            if (torneoSelect.value && !divisionSelect.value && !divisionSelect.disabled) {
                // Si hay torneo pero no división seleccionada, seleccionar la primera
                if (divisionSelect.options.length > 1) {
                    divisionSelect.selectedIndex = 1;
                    filtroForm.submit();
                }
            }
        });
    </script>