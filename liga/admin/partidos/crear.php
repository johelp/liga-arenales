<?php
ob_start(); // Garantizar que no haya problemas con los headers
session_start(); // Iniciar sesión para usar mensajes flash
require_once '../../config.php'; // Incluir config.php PRIMERO
$pdo = conectarDB();

$errores = [];
$fecha_hora = '';
$id_division = '';
$id_club_local = '';
$id_club_visitante = '';
$id_torneo = '';
$arbitro = '';
$estadio = '';
$observaciones = '';
$fecha_numero = null; // Se inicializa como null
$fase = ''; // Nueva variable para la fase del partido
$jugado = 0; // Por defecto el partido no ha sido jugado

// Definir las opciones para la fase del partido
$fases_partido_opciones = [
    '' => 'Seleccionar Fase',
    'Primera Fase' => 'Primera Fase', // O 'Fase Regular', 'Temporada Regular'
    'Octavos de Final' => 'Octavos de Final',
    'Cuartos de Final' => 'Cuartos de Final',
    'Semifinal' => 'Semifinal',
    'Final' => 'Final',
    'Tercer Puesto' => 'Tercer Puesto'
];

// Obtener todos los torneos
try {
    $stmt_torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY activo DESC, nombre");
    $torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);

    // Seleccionar el torneo activo por defecto (si existe)
    $stmt_torneo_activo = $pdo->query("SELECT id_torneo FROM torneos WHERE activo = 1 ORDER BY id_torneo DESC LIMIT 1");
    $torneo_activo = $stmt_torneo_activo->fetch(PDO::FETCH_ASSOC);
    $id_torneo = $torneo_activo ? $torneo_activo['id_torneo'] : '';
} catch (PDOException $e) {
    $errores['database'] = "Error al obtener torneos: " . $e->getMessage();
    $torneos = [];
}

// Obtener todas las divisiones
try {
    $stmt_divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY nombre");
    $divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['database'] = "Error al obtener divisiones: " . $e->getMessage();
    $divisiones = [];
}

// Obtener todos los clubes
try {
    $stmt_clubes = $pdo->query("SELECT id_club, nombre_corto, nombre_completo FROM clubes ORDER BY nombre_corto");
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['database'] = "Error al obtener clubes: " . $e->getMessage();
    $clubes = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y filtrar los datos del formulario
    $fecha_hora = filter_input(INPUT_POST, 'fecha_hora', FILTER_SANITIZE_STRING);
    $id_division = filter_input(INPUT_POST, 'id_division', FILTER_VALIDATE_INT);
    $id_club_local = filter_input(INPUT_POST, 'id_club_local', FILTER_VALIDATE_INT);
    $id_club_visitante = filter_input(INPUT_POST, 'id_club_visitante', FILTER_VALIDATE_INT);
    $id_torneo = filter_input(INPUT_POST, 'id_torneo', FILTER_VALIDATE_INT);
    $arbitro = filter_input(INPUT_POST, 'arbitro', FILTER_SANITIZE_STRING) ?? '';
    $estadio = filter_input(INPUT_POST, 'estadio', FILTER_SANITIZE_STRING) ?? '';
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING) ?? '';
    $jugado = isset($_POST['jugado']) ? 1 : 0;
    
    // Capturar la fase del partido
    $fase = filter_input(INPUT_POST, 'fase', FILTER_SANITIZE_STRING) ?? '';
    
    // Si la fase seleccionada es 'Primera Fase', entonces el número de fecha puede tener un valor.
    // De lo contrario, si es una fase de playoff, el número de fecha debe ser NULL.
    if ($fase === 'Primera Fase') {
        $fecha_numero = filter_input(INPUT_POST, 'fecha_numero', FILTER_VALIDATE_INT) ?? null;
    } else {
        $fecha_numero = null; // Forzar a NULL si es una fase de playoff
    }

    // Validar los datos
    if (empty($fecha_hora)) {
        $errores['fecha_hora'] = 'La fecha y hora del partido son obligatorias.';
    }
    if (empty($id_division)) {
        $errores['id_division'] = 'La división es obligatoria.';
    }
    if (empty($id_club_local)) {
        $errores['id_club_local'] = 'El club local es obligatorio.';
    }
    if (empty($id_club_visitante)) {
        $errores['id_club_visitante'] = 'El club visitante es obligatorio.';
    }
    if (empty($id_torneo)) {
        $errores['id_torneo'] = 'El torneo es obligatorio.';
    }
    if ($id_club_local === $id_club_visitante) {
        $errores['clubes'] = 'El club local no puede ser el mismo que el club visitante.';
    }
    if (empty($fase)) {
        $errores['fase'] = 'La fase del partido es obligatoria.';
    }
    // Si la fase es 'Primera Fase' y no hay número de fecha, o el número es inválido
    if ($fase === 'Primera Fase' && ($fecha_numero === null || $fecha_numero <= 0)) {
        $errores['fecha_numero'] = 'El número de fecha es obligatorio para la Primera Fase y debe ser mayor que 0.';
    }


    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO partidos (id_torneo, id_division, fecha_hora, id_club_local, id_club_visitante, arbitro, estadio, observaciones, fecha_numero, jugado, fase)
                                   VALUES (:id_torneo, :id_division, :fecha_hora, :id_club_local, :id_club_visitante, :arbitro, :estadio, :observaciones, :fecha_numero, :jugado, :fase)");
            $stmt->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt->bindParam(':id_division', $id_division, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_hora', $fecha_hora);
            $stmt->bindParam(':id_club_local', $id_club_local, PDO::PARAM_INT);
            $stmt->bindParam(':id_club_visitante', $id_club_visitante, PDO::PARAM_INT);
            $stmt->bindParam(':arbitro', $arbitro);
            $stmt->bindParam(':estadio', $estadio);
            $stmt->bindParam(':observaciones', $observaciones);
            $stmt->bindParam(':fecha_numero', $fecha_numero, PDO::PARAM_INT); // Puede ser null
            $stmt->bindParam(':jugado', $jugado, PDO::PARAM_INT);
            $stmt->bindParam(':fase', $fase); // El nuevo campo fase

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = 'Partido guardado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit();
            } else {
                $errores['general'] = 'Hubo un error al guardar el partido.';
            }
        } catch (PDOException $e) {
            $errores['general'] = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
}

// Incluir header después de todas las redirecciones potenciales
include '../header.php';
?>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-calendar-plus"></i> Cargar Nuevo Partido</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        </div>

        <?php if (!empty($errores['database'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $errores['database']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errores['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $errores['general']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="match-preview">
            <h4 class="mb-3"><i class="bi bi-trophy"></i> Vista Previa del Partido</h4>
            <div class="row align-items-center">
                <div class="col-md-5 text-md-end">
                    <div class="team-name" id="preview-local">Equipo Local</div>
                </div>
                <div class="col-md-2">
                    <div class="score">VS</div>
                </div>
                <div class="col-md-5 text-md-start">
                    <div class="team-name" id="preview-visitante">Equipo Visitante</div>
                </div>
            </div>
            <div class="mt-2 text-muted">
                <span id="preview-fecha-hora">Fecha y hora a definir</span>
                <span class="ms-3" id="preview-torneo">Torneo no seleccionado</span>
                <span class="ms-3" id="preview-division">División no seleccionada</span>
                <span class="ms-3" id="preview-fase"></span> </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Información del Partido
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_torneo" class="form-label">Torneo</label>
                                <select class="form-select" id="id_torneo" name="id_torneo" required>
                                    <option value="">Seleccionar Torneo</option>
                                    <?php foreach ($torneos as $torneo): ?>
                                        <option value="<?= $torneo['id_torneo']; ?>" <?= ($id_torneo == $torneo['id_torneo']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($torneo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errores['id_torneo'])): ?>
                                    <div class="error-text"><?= $errores['id_torneo']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, selecciona un torneo.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_division" class="form-label">División</label>
                                <select class="form-select" id="id_division" name="id_division" required>
                                    <option value="">Seleccionar División</option>
                                    <?php foreach ($divisiones as $division): ?>
                                        <option value="<?= $division['id_division']; ?>" <?= ($id_division == $division['id_division']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($division['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errores['id_division'])): ?>
                                    <div class="error-text"><?= $errores['id_division']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, selecciona una división.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_hora" class="form-label">Fecha y Hora</label>
                        <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" value="<?= htmlspecialchars($fecha_hora); ?>" required>
                        <?php if (!empty($errores['fecha_hora'])): ?>
                            <div class="error-text"><?= $errores['fecha_hora']; ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback">Por favor, selecciona la fecha y hora del partido.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_club_local" class="form-label">Club Local</label>
                                <select class="form-select" id="id_club_local" name="id_club_local" required>
                                    <option value="">Seleccionar Club Local</option>
                                    <?php foreach ($clubes as $club): ?>
                                        <option value="<?= $club['id_club']; ?>" <?= ($id_club_local == $club['id_club']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($club['nombre_corto']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errores['id_club_local'])): ?>
                                    <div class="error-text"><?= $errores['id_club_local']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, selecciona el club local.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_club_visitante" class="form-label">Club Visitante</label>
                                <select class="form-select" id="id_club_visitante" name="id_club_visitante" required>
                                    <option value="">Seleccionar Club Visitante</option>
                                    <?php foreach ($clubes as $club): ?>
                                        <option value="<?= $club['id_club']; ?>" <?= ($id_club_visitante == $club['id_club']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($club['nombre_corto']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errores['id_club_visitante'])): ?>
                                    <div class="error-text"><?= $errores['id_club_visitante']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, selecciona el club visitante.</div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($errores['clubes'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?= $errores['clubes']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="fase" class="form-label">Fase del Partido</label>
                        <select class="form-select" id="fase" name="fase" required>
                            <?php foreach ($fases_partido_opciones as $value => $label): ?>
                                <option value="<?= $value; ?>" <?= ($fase == $value) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errores['fase'])): ?>
                            <div class="error-text"><?= $errores['fase']; ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback">Por favor, selecciona la fase del partido.</div>
                    </div>

                    <div id="fecha-numero-field" class="mb-3 <?= ($fase !== 'Primera Fase') ? 'hidden-field' : ''; ?>">
                        <label for="fecha_numero" class="form-label">Número de Fecha</label>
                        <input type="number" class="form-control" id="fecha_numero" name="fecha_numero" value="<?= htmlspecialchars($fecha_numero ?? ''); ?>" min="1">
                        <div class="form-text">Obligatorio para la 'Primera Fase'. Se ignorará para las fases de playoff.</div>
                         <?php if (!empty($errores['fecha_numero'])): ?>
                            <div class="error-text"><?= $errores['fecha_numero']; ?></div>
                        <?php endif; ?>
                         <div class="invalid-feedback">El número de fecha es obligatorio para la 'Primera Fase'.</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="jugado" name="jugado" <?= ($jugado == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="jugado">
                                <i class="bi bi-check-circle"></i> Partido Jugado
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="arbitro" class="form-label">Árbitro (opcional)</label>
                                <input type="text" class="form-control" id="arbitro" name="arbitro" value="<?= htmlspecialchars($arbitro); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estadio" class="form-label">Estadio (opcional)</label>
                                <input type="text" class="form-control" id="estadio" name="estadio" value="<?= htmlspecialchars($estadio); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones (opcional)</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= htmlspecialchars($observaciones); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Partido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Referencias a los elementos DOM
            const clubLocalSelect = document.getElementById('id_club_local');
            const clubVisitanteSelect = document.getElementById('id_club_visitante');
            const fechaHoraInput = document.getElementById('fecha_hora');
            const torneoSelect = document.getElementById('id_torneo');
            const divisionSelect = document.getElementById('id_division');
            const faseSelect = document.getElementById('fase'); // Nuevo select para la fase
            const fechaNumeroFieldDiv = document.getElementById('fecha-numero-field');
            const fechaNumeroInput = document.getElementById('fecha_numero');
            
            // Referencias a los elementos de la vista previa
            const previewLocal = document.getElementById('preview-local');
            const previewVisitante = document.getElementById('preview-visitante');
            const previewFechaHora = document.getElementById('preview-fecha-hora');
            const previewTorneo = document.getElementById('preview-torneo');
            const previewDivision = document.getElementById('preview-division');
            const previewFase = document.getElementById('preview-fase'); // Nuevo para la fase en preview
            
            // Función para actualizar la vista previa
            function updatePreview() {
                previewLocal.textContent = clubLocalSelect.options[clubLocalSelect.selectedIndex]?.text || 'Equipo Local';
                previewVisitante.textContent = clubVisitanteSelect.options[clubVisitanteSelect.selectedIndex]?.text || 'Equipo Visitante';
                
                if (fechaHoraInput.value) {
                    const fechaHora = new Date(fechaHoraInput.value);
                    previewFechaHora.textContent = fechaHora.toLocaleString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } else {
                    previewFechaHora.textContent = 'Fecha y hora a definir';
                }
                
                previewTorneo.textContent = torneoSelect.options[torneoSelect.selectedIndex]?.text || 'Torneo no seleccionado';
                previewDivision.textContent = divisionSelect.options[divisionSelect.selectedIndex]?.text || 'División no seleccionada';

                // Actualizar la fase en la vista previa
                let faseTexto = faseSelect.options[faseSelect.selectedIndex]?.text || '';
                if (faseSelect.value === 'Primera Fase' && fechaNumeroInput.value) {
                    faseTexto += ` (Fecha ${fechaNumeroInput.value})`;
                }
                previewFase.textContent = faseTexto;
            }
            
            // Función para alternar la visibilidad del campo "Número de Fecha"
            function toggleFechaNumeroField() {
                if (faseSelect.value === 'Primera Fase') {
                    fechaNumeroFieldDiv.classList.remove('hidden-field');
                    fechaNumeroInput.setAttribute('required', 'required'); // Hacer obligatorio si es Primera Fase
                } else {
                    fechaNumeroFieldDiv.classList.add('hidden-field');
                    fechaNumeroInput.removeAttribute('required'); // No obligatorio para otras fases
                    fechaNumeroInput.value = ''; // Limpiar el valor si no es Primera Fase
                }
                updatePreview(); // Actualizar vista previa después de cambiar la visibilidad
            }

            // Asignar event listeners
            clubLocalSelect.addEventListener('change', updatePreview);
            clubVisitanteSelect.addEventListener('change', updatePreview);
            fechaHoraInput.addEventListener('change', updatePreview);
            torneoSelect.addEventListener('change', updatePreview);
            divisionSelect.addEventListener('change', updatePreview);
            faseSelect.addEventListener('change', toggleFechaNumeroField); // Evento para el select de fase
            fechaNumeroInput.addEventListener('input', updatePreview); // Actualizar preview al escribir número de fecha
            
            // Validación de formulario
            (function () {
                'use strict'
                
                const forms = document.querySelectorAll('.needs-validation')
                
                Array.from(forms).forEach(form => {
                    form.addEventListener('submit', event => {
                        const localId = clubLocalSelect.value;
                        const visitanteId = clubVisitanteSelect.value;
                        
                        // Validación: Club local y visitante no pueden ser el mismo
                        if (localId && visitanteId && localId === visitanteId) {
                            event.preventDefault();
                            event.stopPropagation();
                            alert("Error: El club local y el club visitante no pueden ser el mismo.");
                            return;
                        }

                        // Validación: Número de fecha obligatorio para 'Primera Fase'
                        if (faseSelect.value === 'Primera Fase' && (!fechaNumeroInput.value || parseInt(fechaNumeroInput.value) <= 0)) {
                             event.preventDefault();
                             event.stopPropagation();
                             fechaNumeroInput.classList.add('is-invalid');
                             alert("Error: El número de fecha es obligatorio para la 'Primera Fase' y debe ser mayor que 0.");
                             return;
                        } else {
                            fechaNumeroInput.classList.remove('is-invalid');
                        }
                        
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
            
            // Inicializar la vista previa y la visibilidad de los campos al cargar la página
            toggleFechaNumeroField(); // Llamar para establecer el estado inicial de los campos
            updatePreview();
        });
    </script>


<?php include '../footer.php'; ?>
