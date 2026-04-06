<?php
require_once 'config.php';

function obtenerEscudoURL(PDO $pdo, int $id_club): ?string
{
    $stmt = $pdo->prepare("SELECT c.escudo_url FROM clubes c WHERE c.id_club = :id_club");
    $stmt->bindParam(':id_club', $id_club, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado['escudo_url'] : null;
}

function obtenerInfoTorneoDivision(PDO $pdo, int $id_torneo, int $id_division): array
{
    $info = [
        'torneo_nombre' => '',
        'division_nombre' => '',
        'total_partidos' => 0,
        'partidos_jugados' => 0
    ];
    
    try {
        // Obtener nombre del torneo
        $stmt_torneo = $pdo->prepare("SELECT nombre FROM torneos WHERE id_torneo = :id_torneo");
        $stmt_torneo->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt_torneo->execute();
        $torneo = $stmt_torneo->fetch(PDO::FETCH_ASSOC);
        $info['torneo_nombre'] = $torneo ? $torneo['nombre'] : 'Torneo no encontrado';
        
        // Obtener nombre de la división
        $stmt_division = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id_division");
        $stmt_division->bindParam(':id_division', $id_division, PDO::PARAM_INT);
        $stmt_division->execute();
        $division = $stmt_division->fetch(PDO::FETCH_ASSOC);
        $info['division_nombre'] = $division ? $division['nombre'] : 'División no encontrada';
        
        // Obtener estadísticas de partidos (solo fases de clasificación, no playoffs)
        $stmt_stats = $pdo->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN jugado = 1 THEN 1 ELSE 0 END) as jugados
            FROM partidos
            WHERE id_torneo = :id_torneo
              AND id_division = :id_division
              AND fase NOT IN ('Octavos de Final','Cuartos de Final','Semifinal','Final','Tercer Puesto')
        ");
        $stmt_stats->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt_stats->bindParam(':id_division', $id_division, PDO::PARAM_INT);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $info['total_partidos'] = $stats['total'];
            $info['partidos_jugados'] = $stats['jugados'];
        }
    } catch (PDOException $e) {
        // Manejo de errores silencioso
    }
    
    return $info;
}

function generarTablaPosiciones(PDO $pdo, int $id_torneo, int $id_division, ?string $fase = null): array
{
    $tabla = [];

    $stmt_clubes = $pdo->prepare("SELECT c.id_club, c.nombre_corto, c.escudo_url
                                    FROM clubes c
                                    JOIN clubes_en_division ced ON c.id_club = ced.id_club
                                    WHERE ced.id_torneo = :id_torneo AND ced.id_division = :id_division
                                    ORDER BY c.nombre_corto");
    $stmt_clubes->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
    $stmt_clubes->bindParam(':id_division', $id_division, PDO::PARAM_INT);
    $stmt_clubes->execute();
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);

    foreach ($clubes as $club) {
        $tabla[$club['id_club']] = [
            'id_club' => $club['id_club'],
            'nombre_corto' => $club['nombre_corto'],
            'escudo_url' => $club['escudo_url'],
            'PJ' => 0,
            'PG' => 0,
            'PE' => 0,
            'PP' => 0,
            'GF' => 0,
            'GC' => 0,
            'DG' => 0,
            'Pts' => 0,
            'ultimos_5' => [],
        ];
    }

    // Obtener partidos jugados (excluye playoffs por defecto, o filtra por fase específica)
    $fases_playoff = ['Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Final', 'Tercer Puesto'];
    if ($fase !== null) {
        $sql_partidos = "SELECT p.id_club_local, p.goles_local, p.id_club_visitante, p.goles_visitante, p.fecha_hora
                          FROM partidos p
                          WHERE p.id_torneo = :id_torneo
                            AND p.id_division = :id_division
                            AND p.jugado = 1
                            AND p.fase = :fase
                          ORDER BY p.fecha_hora DESC";
        $stmt_partidos = $pdo->prepare($sql_partidos);
        $stmt_partidos->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt_partidos->bindParam(':id_division', $id_division, PDO::PARAM_INT);
        $stmt_partidos->bindParam(':fase', $fase, PDO::PARAM_STR);
    } else {
        $placeholders = implode(',', array_fill(0, count($fases_playoff), '?'));
        $sql_partidos = "SELECT p.id_club_local, p.goles_local, p.id_club_visitante, p.goles_visitante, p.fecha_hora
                          FROM partidos p
                          WHERE p.id_torneo = :id_torneo
                            AND p.id_division = :id_division
                            AND p.jugado = 1
                            AND p.fase NOT IN ($placeholders)
                          ORDER BY p.fecha_hora DESC";
        $stmt_partidos = $pdo->prepare($sql_partidos);
        $stmt_partidos->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt_partidos->bindParam(':id_division', $id_division, PDO::PARAM_INT);
        foreach ($fases_playoff as $i => $fp) {
            $stmt_partidos->bindValue($i + 1, $fp, PDO::PARAM_STR);
        }
    }
    $stmt_partidos->execute();
    $partidos = $stmt_partidos->fetchAll(PDO::FETCH_ASSOC);

    // Tracking de últimos 5 resultados
    $ultimos_resultados = [];
    
    foreach ($partidos as $partido) {
        $local_id = $partido['id_club_local'];
        $visitante_id = $partido['id_club_visitante'];
        $goles_local = $partido['goles_local'];
        $goles_visitante = $partido['goles_visitante'];

        if (isset($tabla[$local_id]) && isset($tabla[$visitante_id])) {
            $tabla[$local_id]['PJ']++;
            $tabla[$visitante_id]['PJ']++;
            $tabla[$local_id]['GF'] += $goles_local;
            $tabla[$local_id]['GC'] += $goles_visitante;
            $tabla[$visitante_id]['GF'] += $goles_visitante;
            $tabla[$visitante_id]['GC'] += $goles_local;

            // Determinar resultado y actualizar estadísticas
            if ($goles_local > $goles_visitante) {
                $tabla[$local_id]['PG']++;
                $tabla[$local_id]['Pts'] += 3;
                $tabla[$visitante_id]['PP']++;
                
                // Registrar para últimos 5
                if (count($ultimos_resultados[$local_id] ?? []) < 5) {
                    $ultimos_resultados[$local_id][] = 'G';
                }
                if (count($ultimos_resultados[$visitante_id] ?? []) < 5) {
                    $ultimos_resultados[$visitante_id][] = 'P';
                }
            } elseif ($goles_local < $goles_visitante) {
                $tabla[$visitante_id]['PG']++;
                $tabla[$visitante_id]['Pts'] += 3;
                $tabla[$local_id]['PP']++;
                
                // Registrar para últimos 5
                if (count($ultimos_resultados[$local_id] ?? []) < 5) {
                    $ultimos_resultados[$local_id][] = 'P';
                }
                if (count($ultimos_resultados[$visitante_id] ?? []) < 5) {
                    $ultimos_resultados[$visitante_id][] = 'G';
                }
            } else {
                $tabla[$local_id]['PE']++;
                $tabla[$local_id]['Pts'] += 1;
                $tabla[$visitante_id]['PE']++;
                $tabla[$visitante_id]['Pts'] += 1;
                
                // Registrar para últimos 5
                if (count($ultimos_resultados[$local_id] ?? []) < 5) {
                    $ultimos_resultados[$local_id][] = 'E';
                }
                if (count($ultimos_resultados[$visitante_id] ?? []) < 5) {
                    $ultimos_resultados[$visitante_id][] = 'E';
                }
            }
        }
    }
    
    // Agregar los últimos 5 resultados a cada equipo
    foreach ($tabla as $id_club => &$equipo) {
        $equipo['ultimos_5'] = $ultimos_resultados[$id_club] ?? [];
        // Rellenar con vacíos si hay menos de 5
        while (count($equipo['ultimos_5']) < 5) {
            array_unshift($equipo['ultimos_5'], '-');
        }
    }

    // Calcular la diferencia de gol
    foreach ($tabla as &$equipo) {
        $equipo['DG'] = $equipo['GF'] - $equipo['GC'];
    }

    // Ordenar la tabla por Puntos (desc), Diferencia de Gol (desc), Goles a Favor (desc)
    usort($tabla, function ($a, $b) {
        if ($a['Pts'] != $b['Pts']) {
            return $b['Pts'] - $a['Pts'];
        }
        if ($a['DG'] != $b['DG']) {
            return $b['DG'] - $a['DG'];
        }
        return $b['GF'] - $a['GF'];
    });

    return $tabla;
}

function mostrarTablaHTML(PDO $pdo, array $tabla, array $info = []): void
{
    if (empty($tabla)) {
        echo '<div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>No hay datos para generar la tabla de posiciones.</div>
              </div>';
        return;
    }

    echo '<div class="card shadow-sm mb-4">';
    
    if (!empty($info)) {
        echo '<div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <div>
                    <h4 class="mb-0">' . htmlspecialchars($info['torneo_nombre']) . '</h4>
                    <small>' . htmlspecialchars($info['division_nombre']) . '</small>
                </div>
                <div class="text-end">
                    <small class="d-block">Partidos jugados: ' . $info['partidos_jugados'] . ' de ' . $info['total_partidos'] . '</small>
                    <div class="progress mt-1" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: ' . ($info['total_partidos'] > 0 ? ($info['partidos_jugados'] / $info['total_partidos'] * 100) : 0) . '%"></div>
                    </div>
                </div>
              </div>';
    }
    
    echo '<div class="card-body p-0">';
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover table-striped align-middle mb-0">';
    echo '<thead class="table-dark">';
    echo '<tr>
            <th scope="col" class="text-center">Pos</th>
            <th scope="col">Club</th>
            <th scope="col" class="text-center">PJ</th>
            <th scope="col" class="text-center">PG</th>
            <th scope="col" class="text-center">PE</th>
            <th scope="col" class="text-center">PP</th>
            <th scope="col" class="text-center">GF</th>
            <th scope="col" class="text-center">GC</th>
            <th scope="col" class="text-center">DG</th>
            <th scope="col" class="text-center">Pts</th>
            <th scope="col" class="text-center d-none d-md-table-cell">Forma</th>
          </tr>';
    echo '</thead>';
    echo '<tbody>';
    $pos = 1;
    foreach ($tabla as $equipo) {
        // Determinar clases para destacar posiciones (primero, descenso, etc)
        $rowClass = '';
        if ($pos <= 1) {
            $rowClass = 'table-success';
        } elseif ($pos >= count($tabla) && count($tabla) > 8) {
            $rowClass = 'table-danger';
        }
        
        echo '<tr class="' . $rowClass . '">';
        echo '<th scope="row" class="text-center">' . $pos++ . '</th>';
        echo '<td>';
        if (!empty($equipo['escudo_url'])) {
            echo '<img src="' . htmlspecialchars($equipo['escudo_url']) . '" alt="Escudo" class="me-2" style="width: 24px; height: 24px; object-fit: contain;">';
        } else {
            echo '<i class="bi bi-shield me-2 text-secondary"></i>';
        }
        echo '<span>' . htmlspecialchars($equipo['nombre_corto']) . '</span></td>';
        echo '<td class="text-center">' . $equipo['PJ'] . '</td>';
        echo '<td class="text-center">' . $equipo['PG'] . '</td>';
        echo '<td class="text-center">' . $equipo['PE'] . '</td>';
        echo '<td class="text-center">' . $equipo['PP'] . '</td>';
        echo '<td class="text-center">' . $equipo['GF'] . '</td>';
        echo '<td class="text-center">' . $equipo['GC'] . '</td>';
        echo '<td class="text-center">' . ($equipo['DG'] > 0 ? '+' . $equipo['DG'] : $equipo['DG']) . '</td>';
        echo '<td class="text-center fw-bold">' . $equipo['Pts'] . '</td>';
        
        // Mostrar últimos 5 resultados
        echo '<td class="text-center d-none d-md-table-cell">';
        echo '<div class="d-flex justify-content-center">';
        foreach ($equipo['ultimos_5'] as $resultado) {
            $resultadoClass = '';
            $resultadoIcon = '';
            
            switch ($resultado) {
                case 'G':
                    $resultadoClass = 'bg-success';
                    $resultadoIcon = 'check';
                    break;
                case 'E':
                    $resultadoClass = 'bg-warning';
                    $resultadoIcon = 'dash';
                    break;
                case 'P':
                    $resultadoClass = 'bg-danger';
                    $resultadoIcon = 'x';
                    break;
                default:
                    $resultadoClass = 'bg-secondary';
                    $resultadoIcon = '';
            }
            
            echo '<div class="rounded-circle ' . $resultadoClass . ' text-white d-flex align-items-center justify-content-center me-1" 
                      style="width: 20px; height: 20px; font-size: 10px;">';
            if ($resultadoIcon) {
                echo '<i class="bi bi-' . $resultadoIcon . '"></i>';
            } else {
                echo '-';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // table-responsive
    echo '</div>'; // card-body
    
    // Leyenda
    echo '<div class="card-footer bg-light d-flex justify-content-between align-items-center py-2">';
    echo '<div><span class="text-success fw-bold me-2">■</span> Campeón</div>';
    if (count($tabla) > 8) {
        echo '<div><span class="text-danger fw-bold me-2">■</span> Descenso</div>';
    }
    echo '<div class="d-none d-md-block">';
    echo '<small class="me-3"><span class="badge bg-success">G</span> Victoria</small>';
    echo '<small class="me-3"><span class="badge bg-warning">E</span> Empate</small>';
    echo '<small><span class="badge bg-danger">P</span> Derrota</small>';
    echo '</div>';
    echo '</div>'; // card-footer
    
    echo '</div>'; // card
}

function mostrarTablaIframe(PDO $pdo, array $tabla, array $info = []): void
{
    // ... (El contenido de esta función no cambia, ya que no afecta la lógica de cálculo)
    // El iframe solo muestra la tabla HTML que se le pasa, y esa ya fue filtrada por generarTablaPosiciones
    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Tabla de Posiciones - ' . htmlspecialchars($info['torneo_nombre'] ?? 'Liga Deportiva') . '</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">';
    echo '<style>
            body { 
                font-family: sans-serif; 
                margin: 0; 
                background-color: transparent;
                font-size: 14px; 
            }
            .table { 
                margin-bottom: 0; 
                background-color: white;
            }
            .table th, .table td {
                padding: 0.5rem;
            }
            .progress {
                height: 4px;
                border-radius: 2px;
            }
            .progress-bar {
                background-color: rgba(255,255,255,0.5);
            }
            .card {
                border: none;
                box-shadow: 0 2px 5px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            .card-header {
                background-color: #004386;
                color: white;
                padding: 10px 15px;
            }
            .card-footer {
                padding: 7px 15px;
                font-size: 12px;
            }
            .badge {
                font-size: 10px;
                padding: 3px 6px;
            }
            @media (max-width: 576px) {
                .table thead th:nth-child(4),
                .table thead th:nth-child(5),
                .table thead th:nth-child(6),
                .table tbody td:nth-child(4),
                .table tbody td:nth-child(5),
                .table tbody td:nth-child(6) {
                    display: none;
                }
                .card-header h4 {
                    font-size: 16px;
                }
                .card-header small {
                    font-size: 12px;
                }
            }
          </style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="container-fluid px-0">';
    
    if (empty($tabla)) {
        echo '<div class="alert alert-info d-flex align-items-center m-0" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>No hay datos para generar la tabla de posiciones.</div>
              </div>';
    } else {
        echo '<div class="card">';
        
        if (!empty($info)) {
            echo '<div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">' . htmlspecialchars($info['torneo_nombre']) . '</h4>
                        <small>' . htmlspecialchars($info['division_nombre']) . '</small>
                    </div>
                    <div class="text-end">
                        <small class="d-block">Partidos: ' . $info['partidos_jugados'] . '/' . $info['total_partidos'] . '</small>
                        <div class="progress mt-1">
                            <div class="progress-bar" role="progressbar" style="width: ' . ($info['total_partidos'] > 0 ? ($info['partidos_jugados'] / $info['total_partidos'] * 100) : 0) . '%"></div>
                        </div>
                    </div>
                  </div>';
        }
        
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover table-striped align-middle mb-0">';
        echo '<thead class="table-dark">';
        echo '<tr>
                <th scope="col" class="text-center">Pos</th>
                <th scope="col">Club</th>
                <th scope="col" class="text-center">PJ</th>
                <th scope="col" class="text-center">PG</th>
                <th scope="col" class="text-center">PE</th>
                <th scope="col" class="text-center">PP</th>
                <th scope="col" class="text-center">GF</th>
                <th scope="col" class="text-center">GC</th>
                <th scope="col" class="text-center">DG</th>
                <th scope="col" class="text-center">Pts</th>
              </tr>';
        echo '</thead>';
        echo '<tbody>';
        $pos = 1;
        foreach ($tabla as $equipo) {
            // Determinar clases para destacar posiciones
            $rowClass = '';
            if ($pos <= 1) {
                $rowClass = 'table-success';
            } elseif ($pos >= count($tabla) && count($tabla) > 8) {
                $rowClass = 'table-danger';
            }
            
            echo '<tr class="' . $rowClass . '">';
            echo '<th scope="row" class="text-center">' . $pos++ . '</th>';
            echo '<td>';
            if (!empty($equipo['escudo_url'])) {
                echo '<img src="' . htmlspecialchars($equipo['escudo_url']) . '" alt="Escudo" class="me-2" style="width: 20px; height: 20px; object-fit: contain;">';
            } else {
                echo '<i class="bi bi-shield me-1 text-secondary"></i>';
            }
            echo '<span>' . htmlspecialchars($equipo['nombre_corto']) . '</span></td>';
            echo '<td class="text-center">' . $equipo['PJ'] . '</td>';
            echo '<td class="text-center">' . $equipo['PG'] . '</td>';
            echo '<td class="text-center">' . $equipo['PE'] . '</td>';
            echo '<td class="text-center">' . $equipo['PP'] . '</td>';
            echo '<td class="text-center">' . $equipo['GF'] . '</td>';
            echo '<td class="text-center">' . $equipo['GC'] . '</td>';
            echo '<td class="text-center">' . ($equipo['DG'] > 0 ? '+' . $equipo['DG'] : $equipo['DG']) . '</td>';
            echo '<td class="text-center fw-bold">' . $equipo['Pts'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // table-responsive
        
        // Leyenda simplificada
        echo '<div class="card-footer bg-light d-flex justify-content-between align-items-center">';
        echo '<div><span class="text-success fw-bold me-2">■</span> Campeón</div>';
        if (count($tabla) > 8) {
            echo '<div><span class="text-danger fw-bold me-2">■</span> Descenso</div>';
        }
        echo '</div>'; // card-footer
        
        echo '</div>'; // card
    }
    
    echo '</div>'; // container
    
    echo '</body>';
    echo '</html>';
}

// Determinar si es llamada desde include o directamente
if (isset($_GET['torneo_id']) && isset($_GET['division_id'])) {
    $pdo = conectarDB();
    $torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
    $division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
    
    if ($torneo_id && $division_id) {
        $info = obtenerInfoTorneoDivision($pdo, $torneo_id, $division_id);
        $tabla = generarTablaPosiciones($pdo, $torneo_id, $division_id);
        
        // Determinar si se llama como iframe
        $es_iframe = filter_input(INPUT_GET, 'iframe', FILTER_VALIDATE_BOOLEAN) || 
                    (basename($_SERVER['PHP_SELF']) === 'tabla_posiciones_iframe.php');
        
        if ($es_iframe) {
            mostrarTablaIframe($pdo, $tabla, $info);
        } else {
            mostrarTablaHTML($pdo, $tabla, $info);
        }
    } else {
        echo '<div class="alert alert-danger">Parámetros inválidos.</div>';
    }
}