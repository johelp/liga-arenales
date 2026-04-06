<?php
require_once 'config.php';

// Parámetros GET
$torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);

// Definir los IDs de las divisiones
$divisiones = [
    1 => 'Primera División',
    2 => 'División Reserva'
];

// Validación de parámetros
if (!$torneo_id) {
    mostrarError("Error: Torneo no especificado.");
    exit;
}

// Conexión a la base de datos
try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    mostrarError("Error de conexión a la base de datos: " . $e->getMessage());
    exit;
}

// Mostrar el resumen
try {
    mostrarResumenTorneos($pdo, $torneo_id, $divisiones);
} catch (PDOException $e) {
    mostrarError("Error al obtener el resumen: " . $e->getMessage());
}

/**
 * Muestra un mensaje de error con formato
 */
function mostrarError($mensaje) {
    echo "<div class='error-mensaje'>" . htmlspecialchars($mensaje) . "</div>";
}

/**
 * Muestra el resumen completo de los torneos para las divisiones especificadas
 */
function mostrarResumenTorneos($pdo, $torneo_id, $divisiones) {
    // Incluir los estilos CSS
    incluirEstilos();
    
    echo "<div class='resumen-container'>";
    
    foreach ($divisiones as $division_id => $nombre_default) {
        $nombre_division = obtenerNombreDivision($pdo, $division_id) ?: $nombre_default;
        
        // Obtener datos de partidos
        $siguiente_fecha = obtenerSiguienteFechaProximosPartidos($pdo, $torneo_id, $division_id);
        $proximos_partidos = ($siguiente_fecha) ? 
            obtenerProximosPartidosPorFechaYDivision($pdo, $torneo_id, $division_id, $siguiente_fecha) : [];
        $partidos_recientes = obtenerPartidosJugadosRecientementePorDivision($pdo, $torneo_id, $division_id, 5);
        
        // Mostrar sección de división solo si hay datos
        if (!empty($proximos_partidos) || !empty($partidos_recientes)) {
            mostrarSeccionDivision($nombre_division, $proximos_partidos, $partidos_recientes, $siguiente_fecha);
        }
    }
    
    echo "</div>";
}

/**
 * Muestra la sección de una división específica
 */
function mostrarSeccionDivision($nombre_division, $proximos_partidos, $partidos_recientes, $siguiente_fecha) {
    echo "<div class='resumen-categoria'>";
    echo "<h4>" . htmlspecialchars($nombre_division) . "</h4>";
    
    // Mostrar partidos recientes PRIMERO
    if (!empty($partidos_recientes)) {
        echo "<h5 class='partidos-recientes-titulo'>Últimos Partidos</h5>";
        echo "<ul class='resumen-partido-lista'>";
        foreach ($partidos_recientes as $partido) {
            mostrarPartido($partido, true); // true = partido jugado
        }
        echo "</ul>";
    }
    
    // Mostrar próximos partidos DESPUÉS
    if (!empty($proximos_partidos)) {
        echo "<h5 class='proxima-fecha-titulo'>Próxima Fecha: " . 
             date('d/m/Y', strtotime($proximos_partidos[0]['fecha_hora'])) . "</h5>";
        echo "<ul class='resumen-partido-lista'>";
        foreach ($proximos_partidos as $partido) {
            mostrarPartido($partido, false); // false = próximo partido
        }
        echo "</ul>";
    }
    
    echo "</div>";
}

/**
 * Muestra un partido individual (jugado o próximo)
 */
function mostrarPartido($partido, $es_jugado) {
    echo "<li class='resumen-partido-item'>";
    
    // NOMBRE del equipo local primero
    echo "<span class='nombre-club nombre-local'>" . htmlspecialchars($partido['local']) . "</span>";
    
    // ESCUDO del equipo local
    if (!is_null($partido['escudo_local'])) {
        echo "<img src='" . htmlspecialchars($partido['escudo_local']) . "' alt='" . 
             htmlspecialchars($partido['local']) . "' class='escudo escudo-local'>";
    } else {
        echo "<span class='sin-escudo'></span>";
    }
    
    // RESULTADO o "vs" en el centro
    if ($es_jugado) {
        echo "<span class='resultado'>" . htmlspecialchars($partido['goles_local']) . 
             "-" . htmlspecialchars($partido['goles_visitante']) . "</span>";
    } else {
        echo "<span class='proximo'>vs</span>";
    }
    
    // ESCUDO del equipo visitante
    if (!is_null($partido['escudo_visitante'])) {
        echo "<img src='" . htmlspecialchars($partido['escudo_visitante']) . "' alt='" . 
             htmlspecialchars($partido['visitante']) . "' class='escudo escudo-visitante'>";
    } else {
        echo "<span class='sin-escudo'></span>";
    }
    
    // NOMBRE del equipo visitante
    echo "<span class='nombre-club nombre-visitante'>" . htmlspecialchars($partido['visitante']) . "</span>";
    
    // Fecha y hora al final
    echo "<span class='fecha-hora'>" . htmlspecialchars($partido['fecha_hora_formateada']) . "</span>";
    
    echo "</li>";
}

/**
 * Incluye los estilos CSS
 */
function incluirEstilos() {
    echo "<style>
        /* Estilos generales */
        .resumen-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.9em;
            color: #333;
            margin: 0;
            padding: 0;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Contenedor de categoría */
        .resumen-categoria {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            background-color: #fafafa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        /* Títulos */
        .resumen-categoria h4 {
            margin-top: 0;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
            margin-bottom: 10px;
            color: #444;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .proxima-fecha-titulo, 
        .partidos-recientes-titulo {
            font-size: 0.95em;
            font-weight: 500;
            color: #666;
            margin: 12px 0 8px;
            padding-left: 5px;
            border-left: 3px solid #007bff;
        }
        
        /* Listas y elementos */
        .resumen-partido-lista {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .resumen-partido-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 6px 2px;
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            transition: background-color 0.2s;
        }
        
        .resumen-partido-item:hover {
            background-color: #f5f5f5;
        }
        
        .resumen-partido-item:last-child {
            border-bottom: none;
        }
        
        /* Escudos y equipos */
        .escudo {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            border-radius: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            object-fit: contain;
            background-color: #fff;
            flex-shrink: 0;
        }
        
        .escudo-local {
            margin-left: 4px;
            margin-right: 4px;
        }
        
        .escudo-visitante {
            margin-left: 4px;
            margin-right: 4px;
        }
        
        .nombre-club {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.85em;
        }
        
        .nombre-local {
            text-align: right;
            min-width: 24%;
            max-width: 24%;
        }
        
        .nombre-visitante {
            text-align: left;
            min-width: 24%;
            max-width: 24%;
        }
        
        /* Resultado y marcadores */
        .resultado, .proximo {
            font-weight: 600;
            width: 40px;
            text-align: center;
            padding: 1px 4px;
            border-radius: 3px;
            margin: 0 2px;
            font-size: 0.85em;
            flex-shrink: 0;
        }
        
        .resultado {
            color: #fff;
            background-color: #28a745;
        }
        
        .proximo {
            color: #fff;
            background-color: #007bff;
        }
        
        /* Fecha y hora */
        .fecha-hora {
            color: #777;
            font-size: 0.75em;
            white-space: nowrap;
            margin-left: auto;
            padding-left: 4px;
            flex-shrink: 0;
        }
        
        /* Placeholder para escudos ausentes */
        .sin-escudo {
            width: 20px;
            height: 20px;
            display: inline-block;
            margin: 0 4px;
            background-color: #f0f0f0;
            border-radius: 3px;
            flex-shrink: 0;
        }
        
        /* Mensaje de error */
        .error-mensaje {
            color: #dc3545;
            font-weight: bold;
            padding: 8px;
            margin: 8px 0;
            background-color: #f8d7da;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .resumen-categoria {
                padding: 8px;
            }
            
            .resumen-partido-item {
                padding: 5px 2px;
            }
            
            .nombre-club {
                font-size: 0.8em;
            }
            
            .nombre-local, .nombre-visitante {
                min-width: 22%;
                max-width: 22%;
            }
            
            .escudo {
                width: 18px;
                height: 18px;
            }
            
            .sin-escudo {
                width: 18px;
                height: 18px;
            }
            
            .resultado, .proximo {
                width: 36px;
                font-size: 0.8em;
                padding: 1px 2px;
            }
            
            .fecha-hora {
                font-size: 0.7em;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 360px) {
            .resumen-categoria {
                padding: 6px;
            }
            
            .nombre-local, .nombre-visitante {
                min-width: 20%;
                max-width: 20%;
                font-size: 0.75em;
            }
            
            .resultado, .proximo {
                width: 32px;
                font-size: 0.75em;
                padding: 1px;
                margin: 0 1px;
            }
            
            .escudo, .sin-escudo {
                width: 16px;
                height: 16px;
                margin: 0 2px;
            }
            
            .fecha-hora {
                font-size: 0.65em;
            }
        }
    </style>";
}

/**
 * Obtiene el nombre de la división
 */
function obtenerNombreDivision($pdo, $division_id) {
    $stmt = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id");
    $stmt->bindParam(':id', $division_id, PDO::PARAM_INT);
    $stmt->execute();
    $division = $stmt->fetch(PDO::FETCH_ASSOC);
    return $division ? $division['nombre'] : '';
}

/**
 * Obtiene la siguiente fecha con próximos partidos para una división
 */
function obtenerSiguienteFechaProximosPartidos($pdo, $torneo_id, $division_id) {
    $stmt = $pdo->prepare("SELECT DATE(MIN(fecha_hora)) AS siguiente_fecha
                           FROM partidos
                           WHERE id_torneo = :torneo_id
                             AND id_division = :division_id
                             AND jugado = 0
                             AND fecha_hora > NOW()");
    $stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['siguiente_fecha'];
}

/**
 * Obtiene los próximos partidos para una fecha y división específicas
 */
function obtenerProximosPartidosPorFechaYDivision($pdo, $torneo_id, $division_id, $fecha) {
    $stmt = $pdo->prepare("SELECT
                p.id_partido,
                p.fecha_hora,
                DATE_FORMAT(p.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_hora_formateada,
                clocal.nombre_corto AS local,
                clocal.escudo_url AS escudo_local,
                cvisitante.nombre_corto AS visitante,
                cvisitante.escudo_url AS escudo_visitante
            FROM partidos p
            JOIN clubes clocal ON p.id_club_local = clocal.id_club
            JOIN clubes cvisitante ON p.id_club_visitante = cvisitante.id_club
            WHERE p.id_torneo = :torneo_id
              AND p.id_division = :division_id
              AND p.jugado = 0
              AND DATE(p.fecha_hora) = :fecha
            ORDER BY p.fecha_hora ASC");
    $stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene los partidos jugados recientemente para una división
 */
function obtenerPartidosJugadosRecientementePorDivision($pdo, $torneo_id, $division_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT
                p.id_partido,
                p.fecha_hora,
                DATE_FORMAT(p.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_hora_formateada,
                clocal.nombre_corto AS local,
                clocal.escudo_url AS escudo_local,
                p.goles_local,
                cvisitante.nombre_corto AS visitante,
                cvisitante.escudo_url AS escudo_visitante,
                p.goles_visitante
            FROM partidos p
            JOIN clubes clocal ON p.id_club_local = clocal.id_club
            JOIN clubes cvisitante ON p.id_club_visitante = cvisitante.id_club
            WHERE p.id_torneo = :torneo_id
              AND p.id_division = :division_id
              AND p.jugado = 1
            ORDER BY p.fecha_hora DESC
            LIMIT :limit");
    $stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>