<?php
require_once 'config.php';

/**
 * Obtiene la URL del escudo de un club
 * * @param PDO $pdo Conexión a la base de datos
 * @param int $id_club ID del club
 * @return string|null URL del escudo o null si no tiene
 */
function obtenerEscudoURL(PDO $pdo, int $id_club): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT escudo_url FROM clubes WHERE id_club = :id_club");
        $stmt->bindParam(':id_club', $id_club, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['escudo_url'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener escudo: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene el ID del torneo activo
 * * @param PDO $pdo Conexión a la base de datos
 * @return int|null ID del torneo activo o null si no hay
 */
function obtenerTorneoActivo(PDO $pdo): ?int
{
    try {
        $stmt = $pdo->prepare("SELECT id_torneo FROM torneos WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['id_torneo'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener torneo activo: " . $e->getMessage());
        return null;
    }
}

/**
 * Muestra los próximos partidos en un widget estilizado
 * * @param PDO $pdo Conexión a la base de datos
 * @param int|null $id_division ID de la división a filtrar (opcional)
 * @param int|null $id_torneo ID del torneo a filtrar (opcional)
 * @param int $limit Número máximo de partidos a mostrar
 * @return void
 */
function mostrarProximosPartidosWidget(PDO $pdo, ?int $id_division = null, ?int $id_torneo = null, int $limit = 5): void
{
    try {
        // Si no se proporciona un torneo, intentar obtener el torneo activo
        if ($id_torneo === null) {
            $id_torneo = obtenerTorneoActivo($pdo);
        }
        
        // Construir la consulta con condiciones opcionales
        $sql = "SELECT p.id_partido, p.fecha_hora, p.estadio, p.fase,  /* <-- Se añadió p.fase */
                       tl.id_club AS local_id, tl.nombre_corto AS local, tl.escudo_url AS escudo_local,
                       tv.id_club AS visitante_id, tv.nombre_corto AS visitante, tv.escudo_url AS escudo_visitante,
                       d.nombre AS division, d.id_division,
                       t.nombre AS torneo_nombre
                FROM partidos p
                JOIN clubes tl ON p.id_club_local = tl.id_club
                JOIN clubes tv ON p.id_club_visitante = tv.id_club
                JOIN divisiones d ON p.id_division = d.id_division
                JOIN torneos t ON p.id_torneo = t.id_torneo
                WHERE p.jugado = 0 AND p.fecha_hora > CURRENT_TIMESTAMP()";
        
        $params = [];
        
        // Aplicar filtro de torneo si está disponible
        if ($id_torneo !== null) {
            $sql .= " AND p.id_torneo = :id_torneo";
            $params[':id_torneo'] = $id_torneo;
        }
        
        // Aplicar filtro de división si está disponible
        if ($id_division !== null) {
            $sql .= " AND p.id_division = :id_division";
            $params[':id_division'] = $id_division;
        }
        
        // Ordenar y limitar resultados
        $sql .= " ORDER BY p.fecha_hora ASC LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        
        // Vincular parámetros
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $proximos_partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener nombre del torneo y división para el título si tenemos filtros
        $titulo_adicional = '';
        if ($id_torneo !== null || $id_division !== null) {
            $partes_titulo = [];
            
            if ($id_torneo !== null) {
                $stmt_torneo = $pdo->prepare("SELECT nombre FROM torneos WHERE id_torneo = :id_torneo");
                $stmt_torneo->bindValue(':id_torneo', $id_torneo, PDO::PARAM_INT);
                $stmt_torneo->execute();
                $torneo = $stmt_torneo->fetch(PDO::FETCH_ASSOC);
                if ($torneo) {
                    $partes_titulo[] = $torneo['nombre'];
                }
            }
            
            if ($id_division !== null) {
                $stmt_division = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id_division");
                $stmt_division->bindValue(':id_division', $id_division, PDO::PARAM_INT);
                $stmt_division->execute();
                $division = $stmt_division->fetch(PDO::FETCH_ASSOC);
                if ($division) {
                    $partes_titulo[] = $division['nombre'];
                }
            }
            
            if (!empty($partes_titulo)) {
                $titulo_adicional = ' - ' . implode(' - ', $partes_titulo);
            }
        }

        // Mostrar los resultados con diseño moderno
        if (empty($proximos_partidos)):
            echo '<div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <div>No hay próximos partidos programados.</div>
                  </div>';
        else:
            echo '<div class="card shadow-sm mb-4">';
            echo '<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Próximos Partidos' . htmlspecialchars($titulo_adicional) . '</h5>
                  </div>';
            
            echo '<ul class="list-group list-group-flush">';
            
            foreach ($proximos_partidos as $partido):
                $fecha = new DateTime($partido['fecha_hora']);
                $hoy = new DateTime('today');
                $manana = new DateTime('tomorrow');
                
                // Determinar si es hoy, mañana u otro día
                $es_hoy = $fecha->format('Y-m-d') === $hoy->format('Y-m-d');
                $es_manana = $fecha->format('Y-m-d') === $manana->format('Y-m-d');
                
                $clase_item = $es_hoy ? 'list-group-item-warning' : '';
                
                echo '<li class="list-group-item ' . $clase_item . ' p-3">';
                echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                
                // Fecha y hora con badges para hoy y mañana
                echo '<div>';
                if ($es_hoy) {
                    echo '<span class="badge bg-success me-1">HOY</span> ';
                } elseif ($es_manana) {
                    echo '<span class="badge bg-primary me-1">MAÑANA</span> ';
                } else {
                    echo '<span class="badge bg-secondary me-1">' . $fecha->format('d/m') . '</span> ';
                }
                echo '<span class="text-muted">' . $fecha->format('H:i') . ' hs</span>';
                echo '</div>';
                
                // Mostrar división o fase
                echo '<div>';
                if (!empty($partido['fase']) && strtolower($partido['fase']) !== 'primera fase') {
                    // Destacar fases importantes
                    echo '<span class="badge bg-danger me-1">' . htmlspecialchars($partido['fase']) . '</span>';
                } else if ($id_division === null) {
                    // Mostrar división si no se está filtrando por ella y no hay una fase destacada
                    echo '<span class="badge bg-info">' . htmlspecialchars($partido['division']) . '</span>';
                }
                echo '</div>'; // Cierre del div que contiene división/fase
                echo '</div>';
                
                // Equipos con escudos
                echo '<div class="row align-items-center text-center g-0">';
                // Equipo Local
                echo '<div class="col-5">';
                echo '<div class="d-flex flex-column align-items-center">';
                if (!empty($partido['escudo_local'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_local']) . '" alt="Escudo" class="mb-2" style="width: 40px; height: 40px; object-fit: contain;">';
                } else {
                    echo '<div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">';
                    echo '<i class="bi bi-shield text-secondary small"></i>';
                    echo '</div>';
                }
                echo '<strong>' . htmlspecialchars($partido['local']) . '</strong>';
                echo '</div>';
                echo '</div>';
                
                // VS central
                echo '<div class="col-2">';
                echo '<div class="d-flex flex-column align-items-center justify-content-center h-100">';
                echo '<span class="text-secondary fw-bold">VS</span>';
                echo '</div>';
                echo '</div>';
                
                // Equipo Visitante
                echo '<div class="col-5">';
                echo '<div class="d-flex flex-column align-items-center">';
                if (!empty($partido['escudo_visitante'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_visitante']) . '" alt="Escudo" class="mb-2" style="width: 40px; height: 40px; object-fit: contain;">';
                } else {
                    echo '<div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">';
                    echo '<i class="bi bi-shield text-secondary small"></i>';
                    echo '</div>';
                }
                echo '<strong>' . htmlspecialchars($partido['visitante']) . '</strong>';
                echo '</div>';
                echo '</div>';
                echo '</div>'; // row
                
                // Estadio
                echo '<div class="text-center mt-2">';
                echo '<span class="text-muted small"><i class="bi bi-geo-alt-fill me-1"></i>' . htmlspecialchars($partido['estadio']) . '</span>';
                echo '</div>';
                
                echo '</li>';
            endforeach;
            
            echo '</ul>';
            echo '</div>'; // card
        endif;
        
    } catch (PDOException $e) {
        error_log("Error en mostrarProximosPartidosWidget: " . $e->getMessage());
        echo '<div class="alert alert-danger" role="alert">
                Error al cargar los próximos partidos. Por favor, inténtelo de nuevo más tarde.
              </div>';
    }
}

// Ejecutar directamente si se llama al archivo
if (basename($_SERVER['PHP_SELF']) === 'proximos_partidos.php' || 
    basename($_SERVER['PHP_SELF']) === 'proximos_partidos_widget.php') {
    
    $pdo = conectarDB();
    $division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
    $torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 5;
    
    $es_widget = filter_input(INPUT_GET, 'widget', FILTER_VALIDATE_BOOLEAN) || 
                basename($_SERVER['PHP_SELF']) === 'proximos_partidos_widget.php';
    
    // Este archivo ahora solo se encarga de mostrar el widget HTML.
    // La generación de imágenes se movió a proximos_partidos.php
    
    // Si es un widget (o se llama directamente a proximos_partidos_widget.php),
    // mostramos la salida HTML.
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próximos Partidos - Liga Deportiva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; }
        .card-header { border-bottom: none; }
        .list-group-item { border-color: #eee; }
        .list-group-item-warning { background-color: #fff3cd !important; }
        .badge { font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php mostrarProximosPartidosWidget($pdo, $division_id, $torneo_id, $limit); ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>