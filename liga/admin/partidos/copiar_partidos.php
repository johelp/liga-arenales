<?php
require_once '../../config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

/**
 * Copia los partidos de una división a otra
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $idDivisionOrigen ID de la división de origen
 * @param int $idDivisionDestino ID de la división de destino
 * @param int|null $idTorneo ID del torneo (opcional)
 * @param bool $ajustarFechas Si se deben ajustar las fechas al copiar (opcional)
 * @param int $diasAjuste Número de días a ajustar si $ajustarFechas es true (opcional)
 * @return array Array con el resultado de la operación y detalles
 */
function copiarPartidosADivision(PDO $pdo, int $idDivisionOrigen, int $idDivisionDestino, ?int $idTorneo = null, bool $ajustarFechas = false, int $diasAjuste = 0): array
{
    $resultado = [
        'exito' => false,
        'mensaje' => '',
        'contador' => 0,
        'detalles' => []
    ];

    // Verificar que las divisiones existan
    $stmtVerificarDivisiones = $pdo->prepare("
        SELECT id_division, nombre FROM divisiones 
        WHERE id_division IN (:origen, :destino)
    ");
    $stmtVerificarDivisiones->bindParam(':origen', $idDivisionOrigen, PDO::PARAM_INT);
    $stmtVerificarDivisiones->bindParam(':destino', $idDivisionDestino, PDO::PARAM_INT);
    $stmtVerificarDivisiones->execute();
    $divisionesExistentes = $stmtVerificarDivisiones->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (count($divisionesExistentes) !== 2) {
        $resultado['mensaje'] = "Una o ambas divisiones seleccionadas no existen.";
        return $resultado;
    }
    
    // Construir la consulta para seleccionar partidos de origen
    $sql = "SELECT id_torneo, fecha_hora, id_club_local, id_club_visitante, arbitro, estadio, observaciones, fecha_numero
        FROM partidos
        WHERE id_division = :id_division_origen";
    
    $params = [':id_division_origen' => $idDivisionOrigen];
    
    // Añadir filtro de torneo si se especificó
    if ($idTorneo !== null) {
        $sql .= " AND id_torneo = :id_torneo";
        $params[':id_torneo'] = $idTorneo;
    }
    
    // Ordenar por fecha
    $sql .= " ORDER BY fecha_hora ASC";
    
    // Ejecutar la consulta
    $stmtOrigen = $pdo->prepare($sql);
    foreach ($params as $param => $value) {
        $stmtOrigen->bindValue($param, $value, PDO::PARAM_INT);
    }
    $stmtOrigen->execute();
    $partidosOrigen = $stmtOrigen->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partidosOrigen)) {
        $resultado['mensaje'] = "No hay partidos para copiar en la división de origen.";
        $resultado['exito'] = true; // Consideramos éxito aunque no haya nada para copiar
        return $resultado;
    }

    // Verificar si ya existen partidos en la división destino
    $stmtVerificar = $pdo->prepare("
        SELECT COUNT(*) FROM partidos 
        WHERE id_division = :id_division_destino" . 
        ($idTorneo !== null ? " AND id_torneo = :id_torneo" : "")
    );
    
    $stmtVerificar->bindParam(':id_division_destino', $idDivisionDestino, PDO::PARAM_INT);
    if ($idTorneo !== null) {
        $stmtVerificar->bindParam(':id_torneo', $idTorneo, PDO::PARAM_INT);
    }
    $stmtVerificar->execute();
    
    $partidosExistentes = $stmtVerificar->fetchColumn();
    if ($partidosExistentes > 0) {
        $resultado['mensaje'] = "Ya existen partidos en la división de destino. Por seguridad, no se realizó la copia.";
        return $resultado;
    }

    // Preparar la consulta de inserción
    $stmtInsertar = $pdo->prepare("
    INSERT INTO partidos (
        id_torneo, id_division, fecha_hora,
        id_club_local, id_club_visitante,
        arbitro, estadio, observaciones,
        fecha_numero, -- Añadir la columna fecha_numero
        goles_local, goles_visitante, jugado
    ) VALUES (
        :id_torneo, :id_division, :fecha_hora,
        :id_club_local, :id_club_visitante,
        :arbitro, :estadio, :observaciones,
        :fecha_numero, -- Bind del valor de fecha_numero
        NULL, NULL, 0
    )
");

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        $contador = 0;
        foreach ($partidosOrigen as $partido) {
            // Ajustar fecha si es necesario
            $fechaHora = $partido['fecha_hora'];
            if ($ajustarFechas && $diasAjuste !== 0) {
                $fecha = new DateTime($fechaHora);
                $fecha->modify("$diasAjuste days");
                $fechaHora = $fecha->format('Y-m-d H:i:s');
            }
            
            $stmtInsertar->bindValue(':id_torneo', $partido['id_torneo'], PDO::PARAM_INT);
$stmtInsertar->bindValue(':id_division', $idDivisionDestino, PDO::PARAM_INT);
$stmtInsertar->bindValue(':fecha_hora', $fechaHora, PDO::PARAM_STR);
$stmtInsertar->bindValue(':id_club_local', $partido['id_club_local'], PDO::PARAM_INT);
$stmtInsertar->bindValue(':id_club_visitante', $partido['id_club_visitante'], PDO::PARAM_INT);
$stmtInsertar->bindValue(':arbitro', $partido['arbitro'], PDO::PARAM_STR);
$stmtInsertar->bindValue(':estadio', $partido['estadio'], PDO::PARAM_STR);
$stmtInsertar->bindValue(':observaciones', $partido['observaciones'], PDO::PARAM_STR);
$stmtInsertar->bindValue(':fecha_numero', $partido['fecha_numero'], PDO::PARAM_INT); 

            $stmtInsertar->execute();
            $contador++;
            
            // Registrar detalles
            $resultado['detalles'][] = [
                'local' => $partido['id_club_local'],
                'visitante' => $partido['id_club_visitante'],
                'fecha_original' => $partido['fecha_hora'],
                'fecha_nueva' => $fechaHora
            ];
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        $resultado['exito'] = true;
        $resultado['contador'] = $contador;
        $resultado['mensaje'] = "Se han copiado $contador partidos correctamente de la división '{$divisionesExistentes[$idDivisionOrigen]}' a '{$divisionesExistentes[$idDivisionDestino]}'.";
        
        return $resultado;
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        $resultado['mensaje'] = "Error al copiar partidos: " . $e->getMessage();
        error_log("Error al copiar partidos: " . $e->getMessage());
        return $resultado;
    }
}

/**
 * Obtiene los nombres de las divisiones
 */
function obtenerNombreDivision(PDO $pdo, int $idDivision): string
{
    $stmt = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id");
    $stmt->bindParam(':id', $idDivision, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado['nombre'] : 'División desconocida';
}

// Procesar el formulario
$mensaje = null;
$tipoMensaje = null;
$resultadoDetalle = null;

if (isset($_POST['copiar_partidos'])) {
    $divisionOrigen = filter_input(INPUT_POST, 'division_origen', FILTER_VALIDATE_INT);
    $divisionDestino = filter_input(INPUT_POST, 'division_destino', FILTER_VALIDATE_INT);
    $torneo = filter_input(INPUT_POST, 'torneo', FILTER_VALIDATE_INT) ?: null;
    $ajustarFechas = filter_input(INPUT_POST, 'ajustar_fechas', FILTER_VALIDATE_BOOLEAN);
    $diasAjuste = filter_input(INPUT_POST, 'dias_ajuste', FILTER_VALIDATE_INT) ?: 0;

    if (!$divisionOrigen || !$divisionDestino) {
        $mensaje = "Por favor, selecciona las divisiones de origen y destino.";
        $tipoMensaje = "danger";
    } elseif ($divisionOrigen === $divisionDestino) {
        $mensaje = "La división de origen y destino no pueden ser la misma.";
        $tipoMensaje = "danger";
    } else {
        // Confirmar la operación
        if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
            $resultado = copiarPartidosADivision($pdo, $divisionOrigen, $divisionDestino, $torneo, $ajustarFechas, $diasAjuste);
            
            if ($resultado['exito']) {
                $mensaje = $resultado['mensaje'];
                $tipoMensaje = "success";
                $resultadoDetalle = $resultado;
            } else {
                $mensaje = $resultado['mensaje'];
                $tipoMensaje = "danger";
            }
        } else {
            // Se muestra la confirmación en el flujo del HTML
        }
    }
}

// Obtener la lista de divisiones para los selectores
$stmtDivisiones = $pdo->prepare("SELECT id_division, nombre FROM divisiones ORDER BY orden, nombre");
$stmtDivisiones->execute();
$divisiones = $stmtDivisiones->fetchAll(PDO::FETCH_ASSOC);

// Obtener la lista de torneos
$stmtTorneos = $pdo->prepare("SELECT id_torneo, nombre FROM torneos ORDER BY fecha_inicio DESC, nombre");
$stmtTorneos->execute();
$torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

// Determinar el estado del formulario
$mostrarConfirmacion = isset($_POST['copiar_partidos']) && 
                        !isset($_POST['confirmar']) && 
                        !empty($divisionOrigen) && 
                        !empty($divisionDestino) && 
                        $divisionOrigen !== $divisionDestino;

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
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
        .feature-icon {
            width: 48px;
            height: 48px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #004386;
            margin-right: 20px;
        }
        .alert-info {
            border-left: 4px solid #0dcaf0;
        }
        .alert-warning {
            border-left: 4px solid #ffc107;
        }
        .step-indicator {
            display: flex;
            margin-bottom: 20px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            position: relative;
            color: #6c757d;
        }
        .step.active {
            color: #004386;
            font-weight: bold;
        }
        .step::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: #dee2e6;
        }
        .step.active::after {
            background-color: #004386;
        }
        .form-check-input:checked {
            background-color: #004386;
            border-color: #004386;
        }
        .tooltip-inner {
            max-width: 200px;
            padding: 0.25rem 0.5rem;
            color: #fff;
            background-color: #004386;
            border-radius: 0.25rem;
        }
    </style>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-files"></i> Copiar Partidos entre Divisiones</h1>
            <a href="../" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?= $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Pasos de proceso -->
        <div class="step-indicator mb-4">
            <div class="step <?= !$mostrarConfirmacion && !$resultadoDetalle ? 'active' : ''; ?>">
                1. Seleccionar Datos
            </div>
            <div class="step <?= $mostrarConfirmacion ? 'active' : ''; ?>">
                2. Confirmar
            </div>
            <div class="step <?= $resultadoDetalle ? 'active' : ''; ?>">
                3. Resultado
            </div>
        </div>

        <?php if ($mostrarConfirmacion): ?>
            <!-- Pantalla de confirmación -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-check-circle"></i> Confirmar Operación
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Confirma que deseas realizar esta operación.</strong> Se copiarán los partidos de 
                        <strong><?= htmlspecialchars(obtenerNombreDivision($pdo, $divisionOrigen)); ?></strong> a 
                        <strong><?= htmlspecialchars(obtenerNombreDivision($pdo, $divisionDestino)); ?></strong>.
                        <?php if ($torneo): ?>
                            <br>Solo se copiarán los partidos del torneo <strong><?= htmlspecialchars(array_column(array_filter($torneos, fn($t) => $t['id_torneo'] == $torneo), 'nombre')[0] ?? 'Desconocido'); ?></strong>.
                        <?php else: ?>
                            <br>Se copiarán partidos de <strong>todos los torneos</strong>.
                        <?php endif; ?>
                        <?php if ($ajustarFechas): ?>
                            <br>Las fechas se ajustarán <?= $diasAjuste > 0 ? $diasAjuste . ' días adelante' : abs($diasAjuste) . ' días atrás'; ?>.
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="">
                        <input type="hidden" name="division_origen" value="<?= $divisionOrigen; ?>">
                        <input type="hidden" name="division_destino" value="<?= $divisionDestino; ?>">
                        <input type="hidden" name="torneo" value="<?= $torneo; ?>">
                        <input type="hidden" name="ajustar_fechas" value="<?= $ajustarFechas ? 1 : 0; ?>">
                        <input type="hidden" name="dias_ajuste" value="<?= $diasAjuste; ?>">
                        <input type="hidden" name="confirmar" value="si">
                        <input type="hidden" name="copiar_partidos" value="1">
                        
                        <div class="d-flex justify-content-between mt-3">
                            <a href="<?= $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Confirmar y Copiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($resultadoDetalle): ?>
            <!-- Pantalla de resultado -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clipboard-check"></i> Resultado de la Operación
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Operación completada exitosamente.</strong> Se copiaron <?= $resultadoDetalle['contador']; ?> partidos.
                    </div>
                    
                    <?php if (!empty($resultadoDetalle['detalles'])): ?>
                        <h5 class="mt-4 mb-3">Resumen de partidos copiados</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Equipos</th>
                                        <th>Fecha Original</th>
                                        <?php if ($ajustarFechas): ?>
                                            <th>Fecha Nueva</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $contador = 1;
                                    foreach ($resultadoDetalle['detalles'] as $partido): 
                                    
                                        // Obtener nombres de clubes
                                        $stmtLocal = $pdo->prepare("SELECT nombre_corto FROM clubes WHERE id_club = :id");
                                        $stmtLocal->bindValue(':id', $partido['local'], PDO::PARAM_INT);
                                        $stmtLocal->execute();
                                        $nombreLocal = $stmtLocal->fetchColumn() ?: 'Club #' . $partido['local'];
                                        
                                        $stmtVisitante = $pdo->prepare("SELECT nombre_corto FROM clubes WHERE id_club = :id");
                                        $stmtVisitante->bindValue(':id', $partido['visitante'], PDO::PARAM_INT);
                                        $stmtVisitante->execute();
                                        $nombreVisitante = $stmtVisitante->fetchColumn() ?: 'Club #' . $partido['visitante'];
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($nombreLocal); ?> vs <?= htmlspecialchars($nombreVisitante); ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($partido['fecha_original'])); ?></td>
                                            <?php if ($ajustarFechas): ?>
                                                <td><?= date('d/m/Y H:i', strtotime($partido['fecha_nueva'])); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulario principal -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-sliders"></i> Configuración de Copia
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Esta herramienta permite copiar los partidos de una división a otra, manteniendo las mismas fechas, equipos y otros detalles, pero sin copiar resultados.
                    </div>
                    
                    <form method="post" action="">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="division_origen" class="form-label">División de Origen</label>
                                <select class="form-select" id="division_origen" name="division_origen" required>
                                    <option value="">-- Seleccionar división --</option>
                                    <?php foreach ($divisiones as $division): ?>
                                        <option value="<?= $division['id_division']; ?>" <?= (isset($divisionOrigen) && $divisionOrigen == $division['id_division']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($division['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">División desde la que se copiarán los partidos.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="division_destino" class="form-label">División de Destino</label>
                                <select class="form-select" id="division_destino" name="division_destino" required>
                                    <option value="">-- Seleccionar división --</option>
                                    <?php foreach ($divisiones as $division): ?>
                                        <option value="<?= $division['id_division']; ?>" <?= (isset($divisionDestino) && $divisionDestino == $division['id_division']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($division['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">División a la que se copiarán los partidos.</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="torneo" class="form-label">Torneo (Opcional)</label>
                                <select class="form-select" id="torneo" name="torneo">
                                    <option value="">-- Todos los torneos --</option>
                                    <?php foreach ($torneos as $t): ?>
                                        <option value="<?= $t['id_torneo']; ?>" <?= (isset($torneo) && $torneo == $t['id_torneo']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($t['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Si se selecciona, solo se copiarán los partidos de este torneo.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3 mt-4">
                                    <input class="form-check-input" type="checkbox" id="ajustar_fechas" name="ajustar_fechas" <?= (isset($ajustarFechas) && $ajustarFechas) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ajustar_fechas">
                                        Ajustar fechas de partidos
                                    </label>
                                </div>
                                
                                <div id="dias_ajuste_container" class="<?= (isset($ajustarFechas) && $ajustarFechas) ? '' : 'd-none'; ?>">
                                    <label for="dias_ajuste" class="form-label">Ajuste de días:</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="dias_ajuste" name="dias_ajuste" value="<?= $diasAjuste ?? 0; ?>" placeholder="0">
                                        <span class="input-group-text">días</span>
                                    </div>
                                    <div class="form-text">Número positivo para adelantar, negativo para atrasar fechas.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="../admin/" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                            <button type="submit" name="copiar_partidos" class="btn btn-primary">
                                <i class="bi bi-clipboard-plus"></i> Continuar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Características y Advertencias -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-lightbulb"></i> Características
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div>
                                        <strong>Copia selectiva</strong>
                                        <p class="mb-0">Puedes filtrar por torneo para copiar solo partidos específicos.</p>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <div>
                                        <strong>Ajuste de fechas</strong>
                                        <p class="mb-0">Opción para ajustar las fechas de los partidos automáticamente.</p>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div class="feature-icon">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div>
                                        <strong>Protección de datos</strong>
                                        <p class="mb-0">Verificaciones para evitar duplicados o sobrescribir datos existentes.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-exclamation-triangle"></i> Advertencias
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item border-0 ps-0">
                                    <i class="bi bi-exclamation-circle text-warning me-2"></i>
                                    Los resultados y estadísticas (goles, tarjetas) no se copian.
                                </li>
                                <li class="list-group-item border-0 ps-0">
                                    <i class="bi bi-exclamation-circle text-warning me-2"></i>
                                    La herramienta no copiará partidos si ya existen partidos en la división de destino.
                                </li>
                                <li class="list-group-item border-0 ps-0">
                                    <i class="bi bi-exclamation-circle text-warning me-2"></i>
                                    Asegúrate de que los clubes estén asignados a la división destino antes de copiar los partidos.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar el campo de ajuste de días según el checkbox
            const ajustarFechasCheckbox = document.getElementById('ajustar_fechas');
            const diasAjusteContainer = document.getElementById('dias_ajuste_container');
            
            if (ajustarFechasCheckbox && diasAjusteContainer) {
                ajustarFechasCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        diasAjusteContainer.classList.remove('d-none');
                    } else {
                        diasAjusteContainer.classList.add('d-none');
                    }
                });
            }
            
            // Validación para evitar seleccionar la misma división
            const formElement = document.querySelector('form');
            if (formElement) {
                formElement.addEventListener('submit', function(event) {
                    const divisionOrigen = document.getElementById('division_origen').value;
                    const divisionDestino = document.getElementById('division_destino').value;
                    
                    if (divisionOrigen === divisionDestino && divisionOrigen !== '') {
                        event.preventDefault();
                        alert('La división de origen y destino no pueden ser la misma.');
                    }
                });
            }
            
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>

<?php include '../footer.php'; ?>