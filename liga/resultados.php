<?php
require_once 'config.php';

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

// Función para obtener todas las divisiones de un torneo específico
function obtenerDivisionesPorTorneo(PDO $pdo, int $id_torneo): array
{
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT d.id_division, d.nombre
                                 FROM divisiones d
                                 JOIN clubes_en_division ced ON d.id_division = ced.id_division
                                 WHERE ced.id_torneo = :id_torneo
                                 ORDER BY d.nombre");
        $stmt->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener divisiones: " . $e->getMessage());
        return [];
    }
}

// Función para obtener resultados por torneo y división
function obtenerResultados(PDO $pdo, ?int $torneo_id = null, ?int $division_id = null, int $limite = 50): array
{
    try {
        // Inicializa el array de parámetros
        $params = [];
        
        // Construye la consulta base
        $sql = "SELECT p.id_partido, p.fecha_hora, p.goles_local, p.goles_visitante,
                        cl.nombre_corto AS club_local, cv.nombre_corto AS club_visitante,
                        d.nombre AS division_nombre, d.id_division, t.nombre AS torneo_nombre, t.id_torneo,
                        p.arbitro, p.estadio, p.fase  /* AÑADIDO: Campo 'fase' */
                FROM partidos p
                JOIN clubes cl ON p.id_club_local = cl.id_club
                JOIN clubes cv ON p.id_club_visitante = cv.id_club
                JOIN divisiones d ON p.id_division = d.id_division
                JOIN torneos t ON p.id_torneo = t.id_torneo
                WHERE 1=1";
        
        // Solo mostrar partidos jugados
        $sql .= " AND p.jugado = 1"; // THIS LINE IS RESTORED
        
        // Añadir filtro de torneo si está especificado
        if ($torneo_id) {
            $sql .= " AND p.id_torneo = :torneo_id";
            $params[':torneo_id'] = $torneo_id;
        }
        
        // Añadir filtro de división si está especificado
        if ($division_id) {
            $sql .= " AND p.id_division = :division_id";
            $params[':division_id'] = $division_id;
        }
        
        // Ordenar y limitar los resultados
        $sql .= " ORDER BY p.fecha_hora DESC";
        if ($limite > 0) {
            $sql .= " LIMIT :limite";
            $params[':limite'] = $limite;
        }
        
        // Preparar y ejecutar la consulta
        $stmt = $pdo->prepare($sql);
        
        // Vincular los parámetros
        foreach ($params as $key => $value) {
            if ($key === ':limite') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindParam($key, $value, PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $resultados;
    } catch (PDOException $e) {
        error_log("Error al obtener resultados: " . $e->getMessage());
        return [];
    }
}

// Función para obtener detalles extras de un partido (goles, tarjetas)
function obtenerDetallesPartido(PDO $pdo, int $id_partido): array
{
    $detalles = [
        'goles' => [],
        'tarjetas' => []
    ];
    
    try {
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
        
    } catch (PDOException $e) {
        error_log("Error al obtener detalles del partido: " . $e->getMessage());
    }
    
    return $detalles;
}

// Inicializar conexión a la base de datos
try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener parámetros de filtrado
$torneo_seleccionado_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
$division_seleccionada_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);

// Obtener datos para la página
$torneos = obtenerTorneos($pdo);
$divisiones = [];
if ($torneo_seleccionado_id) {
    $divisiones = obtenerDivisionesPorTorneo($pdo, $torneo_seleccionado_id);
}

// Obtener resultados según los filtros
$resultados = obtenerResultados($pdo, $torneo_seleccionado_id, $division_seleccionada_id);

// Agrupar resultados por fecha (para mostrarlos por jornada)
$resultados_por_fecha = [];
foreach ($resultados as $resultado) {
    $fecha = date('Y-m-d', strtotime($resultado['fecha_hora']));
    if (!isset($resultados_por_fecha[$fecha])) {
        $resultados_por_fecha[$fecha] = [];
    }
    $resultados_por_fecha[$fecha][] = $resultado;
}

// Establecer el título de la página
$titulo_pagina = "Resultados - Liga Deportiva de General Arenales";

// Obtener la fecha actual para el copyright
$anio_actual = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Resultados de partidos de la Liga Deportiva de General Arenales.">
    <title><?= $titulo_pagina ?></title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .back-link, .admin-link {
            position: absolute;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .back-link {
            top: 15px;
            left: 20px;
        }
        
        .admin-link {
            top: 15px;
            right: 20px;
        }
        
        .back-link:hover, .admin-link:hover {
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
        
        .resultado {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 6px 12px;
            font-weight: 600;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .resultado-detallado {
            background-color: #fff;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-top: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .badge-amarilla {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-roja {
            background-color: #dc3545;
            color: white;
        }
        
        .jornada-header {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin: 20px 0 15px;
            font-weight: 600;
            color: var(--primary-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .partido-card {
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            padding: 15px;
            transition: all 0.2s ease;
        }
        
        .partido-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .partido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .partido-fecha {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .partido-division {
            font-size: 0.85rem;
            background-color: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .partido-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .equipo {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 40%;
        }
        
        .equipo-local {
            align-items: flex-end;
        }
        
        .equipo-visitante {
            align-items: flex-start;
        }
        
        .equipo-nombre {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 5px 0;
        }
        
        .marcador {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 700;
            font-size: 1.5rem;
            width: 20%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .partido-detalles {
            margin-top: 15px;
            border-top: 1px dashed #eee;
            padding-top: 15px;
            font-size: 0.9rem;
        }
        
        .detalles-toggle {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
            padding: 0;
            margin-top: 10px;
            display: block;
        }
        
        .gol-item, .tarjeta-item {
            margin-bottom: 5px;
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
        
        @media (max-width: 768px) {
            .header {
                padding: 20px 0 15px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .partido-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .equipo {
                width: 100%;
                align-items: center;
            }
            
            .equipo-local, .equipo-visitante {
                align-items: center;
            }
            
            .marcador {
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Inicio</a>
        <div class="logo-container">
            <h1>Resultados</h1>
        </div>
        <p class="lead">Liga Deportiva de General Arenales</p>
        <a href="admin/index.php" class="admin-link"><i class="bi bi-gear-fill"></i> Administración</a>
    </div>
    
    <div class="container">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-filter"></i> Filtrar Resultados
            </div>
            <div class="card-body">
                <form method="get" id="filtro-form" class="row g-3 align-items-end">
    <div class="col-md-5">
        <label for="torneo_id" class="form-label">Torneo:</label>
        <select class="form-select" id="torneo_id" name="torneo_id" onchange="this.form.submit()">
            <option value="">Todos los torneos</option>
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
        <select class="form-select" id="division_id" name="division_id" onchange="this.form.submit()" <?= empty($divisiones) && $torneo_seleccionado_id ? 'disabled' : ''; ?>>
            <option value="">Todas las divisiones</option>
            <?php foreach ($divisiones as $division): ?>
                <option value="<?= $division['id_division']; ?>" 
                        <?= ($division_seleccionada_id == $division['id_division']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($division['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filtrar
        </button>
    </div>
</form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-trophy"></i> Resultados
                <?php if ($torneo_seleccionado_id): ?>
                    <span class="float-end badge bg-light text-dark">
                        <?= htmlspecialchars(array_filter($torneos, function($t) use ($torneo_seleccionado_id) { 
                            return $t['id_torneo'] == $torneo_seleccionado_id; 
                        })[array_key_first(array_filter($torneos, function($t) use ($torneo_seleccionado_id) { 
                            return $t['id_torneo'] == $torneo_seleccionado_id; 
                        }))]['nombre'] ?? ''); ?>
                    </span>
                <?php endif; ?>
                <?php if ($division_seleccionada_id): ?>
                    <span class="float-end badge bg-light text-dark me-2">
                        <?= htmlspecialchars(array_filter($divisiones, function($d) use ($division_seleccionada_id) { 
                            return $d['id_division'] == $division_seleccionada_id; 
                        })[array_key_first(array_filter($divisiones, function($d) use ($division_seleccionada_id) { 
                            return $d['id_division'] == $division_seleccionada_id; 
                        }))]['nombre'] ?? ''); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($resultados_por_fecha)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No se encontraron resultados con los filtros seleccionados.
                    </div>
                <?php else: ?>
                    <?php foreach ($resultados_por_fecha as $fecha => $partidos): ?>
                        <div class="jornada-header">
                            <i class="bi bi-calendar-event"></i> <?= date('d/m/Y', strtotime($fecha)); ?>
                        </div>
                        
                        <?php foreach ($partidos as $partido): ?>
                            <div class="partido-card">
                                <div class="partido-header">
                                    <div class="partido-fecha">
                                        <i class="bi bi-clock"></i> <?= date('H:i', strtotime($partido['fecha_hora'])); ?>
                                        <?php if (!empty($partido['arbitro'])): ?>
                                            <span class="ms-3"><i class="bi bi-whistle"></i> <?= htmlspecialchars($partido['arbitro']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($partido['estadio'])): ?>
                                            <span class="ms-3"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($partido['estadio']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($partido['fase'])): /* AÑADIDO: Mostrar la fase */ ?>
                                            <span class="ms-3"><i class="bi bi-pin-map-fill"></i> <?= htmlspecialchars($partido['fase']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partido-division">
                                        <?= htmlspecialchars($partido['division_nombre']); ?>
                                    </div>
                                </div>
                                
                                <div class="partido-info">
                                    <div class="equipo equipo-local">
                                        <div class="equipo-nombre"><?= htmlspecialchars($partido['club_local']); ?></div>
                                    </div>
                                    
                                    <div class="marcador">
                                        <?= $partido['goles_local']; ?> - <?= $partido['goles_visitante']; ?>
                                    </div>
                                    
                                    <div class="equipo equipo-visitante">
                                        <div class="equipo-nombre"><?= htmlspecialchars($partido['club_visitante']); ?></div>
                                    </div>
                                </div>
                                
                                <?php
                                // Obtener detalles del partido solo si se hace clic (mediante AJAX)
                                // Aquí simplemente agregamos el botón para mostrar detalles
                                ?>
                                <button type="button" class="detalles-toggle" data-partido-id="<?= $partido['id_partido']; ?>">
                                    <i class="bi bi-plus-circle"></i> Ver detalles
                                </button>
                                
                                <div id="detalles-partido-<?= $partido['id_partido']; ?>" class="partido-detalles" style="display: none;">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <span class="ms-2">Cargando detalles...</span>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="tabla_posiciones_iframe.php?torneo_id=<?= $partido['id_torneo']; ?>&division_id=<?= $partido['id_division']; ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-table"></i> Ver tabla de posiciones
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p>&copy; <?= $anio_actual ?> Liga Deportiva de General Arenales. Todos los derechos reservados.</p>
                    <p>Desarrollado por <a href="https://ascensiondigital.ar" target="_blank">AscensionDigital.ar</a></p>
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
            
            // Función para cargar divisiones según el torneo seleccionado
            torneoSelect.addEventListener('change', function() {
                const torneoId = this.value;
                
                if (!torneoId) {
                    // Si no hay torneo seleccionado, mostrar todas las divisiones
                    divisionSelect.innerHTML = '<option value="">Todas las divisiones</option>';
                    divisionSelect.disabled = false;
                    return;
                }
                
                // Mostrar indicador de carga
                divisionSelect.disabled = true;
                
                // Realizar petición AJAX para obtener las divisiones del torneo
                fetch('get_divisiones.php?torneo_id=' + torneoId)
                    .then(response => response.json())
                    .then(data => {
                        // Limpiar y llenar el select de divisiones
                        divisionSelect.innerHTML = '<option value="">Todas las divisiones</option>';
                        
                        if (data.length > 0) {
                            data.forEach(division => {
                                const option = document.createElement('option');
                                option.value = division.id_division;
                                option.textContent = division.nombre;
                                divisionSelect.appendChild(option);
                            });
                            divisionSelect.disabled = false;
                        } else {
                            // No hay divisiones para este torneo
                            divisionSelect.innerHTML = '<option value="">No hay divisiones disponibles</option>';
                            divisionSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar divisiones:', error);
                        divisionSelect.innerHTML = '<option value="">Error al cargar divisiones</option>';
                    });
            });
            
            // Manejador para los botones de ver detalles
            document.querySelectorAll('.detalles-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const partidoId = this.getAttribute('data-partido-id');
                    const detallesContainer = document.getElementById('detalles-partido-' + partidoId);
                    
                    // Alternar visibilidad
                    if (detallesContainer.style.display === 'none') {
                        detallesContainer.style.display = 'block';
                        this.innerHTML = '<i class="bi bi-dash-circle"></i> Ocultar detalles';
                        
                        // Cargar detalles solo si no se han cargado antes
                        if (detallesContainer.querySelector('.spinner-border')) {
                            fetch('get_detalle_partido.php?id_partido=' + partidoId)
                                .then(response => response.json())
                                .then(data => {
                                    let html = '';
                                    
                                    // Mostrar goles
                                    if (data.goles && data.goles.length > 0) {
                                        html += '<div class="mb-3"><strong><i class="bi bi-trophy"></i> Goles:</strong><ul class="list-unstyled ms-3">';
                                        data.goles.forEach(gol => {
                                            html += `<li class="gol-item">
                                                <i class="bi bi-circle-fill text-success"></i> 
                                                ${gol.minuto}' - ${gol.jugador_nombre} (${gol.club_nombre})
                                            </li>`;
                                        });
                                        html += '</ul></div>';
                                    }
                                    
                                    // Mostrar tarjetas
                                    if (data.tarjetas && data.tarjetas.length > 0) {
                                        html += '<div><strong><i class="bi bi-card-heading"></i> Tarjetas:</strong><ul class="list-unstyled ms-3">';
                                        data.tarjetas.forEach(tarjeta => {
                                            const badgeClass = tarjeta.tipo === 'amarilla' ? 'badge-amarilla' : 'badge-roja';
                                            html += `<li class="tarjeta-item">
                                                <span class="badge ${badgeClass}"></span> 
                                                ${tarjeta.minuto}' - ${tarjeta.jugador_nombre} (${tarjeta.club_nombre})
                                            </li>`;
                                        });
                                        html += '</ul></div>';
                                    }
                                    // Si no hay detalles
                                    if (html === '') {
                                        html = '<div class="alert alert-info">No hay detalles adicionales disponibles para este partido.</div>';
                                    }
                                    
                                    detallesContainer.innerHTML = html;
                                })
                                .catch(error => {
                                    console.error('Error al cargar detalles:', error);
                                    detallesContainer.innerHTML = '<div class="alert alert-danger">Error al cargar los detalles del partido.</div>';
                                });
                        }
                    } else {
                        detallesContainer.style.display = 'none';
                        this.innerHTML = '<i class="bi bi-plus-circle"></i> Ver detalles';
                    }
                });
            });
        });
    </script>
</body>
</html>