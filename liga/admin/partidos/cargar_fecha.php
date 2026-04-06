<?php
ob_start(); // Garantizar que no haya problemas con los headers
session_start();
require_once '../../config.php'; // Incluir config.php PRIMERO
$pdo = conectarDB();

$errores = [];
$mensaje = '';
$clubes = [];
$divisiones = [];
$torneos = []; // Array para almacenar los torneos
$fases = ['Primera Fase', 'Octavos de Final', 'Cuartos de Final', 'Semifinal', 'Final', 'Tercer Puesto']; // **NUEVO: Fases disponibles**

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Obtener todos los clubes
try {
    $stmt_clubes = $pdo->query("SELECT id_club, nombre_corto, nombre_completo FROM clubes ORDER BY nombre_corto");
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['database'] = "Error al cargar clubes: " . $e->getMessage();
}

// Obtener todas las divisiones
try {
    $stmt_divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY nombre");
    $divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['database'] = "Error al cargar divisiones: " . $e->getMessage();
}

// Obtener todos los torneos
try {
    $stmt_torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
    $torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['database'] = "Error al cargar torneos: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_division = filter_input(INPUT_POST, 'id_division', FILTER_VALIDATE_INT);
    $fecha_numero = filter_input(INPUT_POST, 'fecha_numero', FILTER_VALIDATE_INT);
    $fecha_base = filter_input(INPUT_POST, 'fecha_base', FILTER_SANITIZE_STRING);
    $id_torneo = filter_input(INPUT_POST, 'id_torneo', FILTER_VALIDATE_INT);
    $fase_partido = filter_input(INPUT_POST, 'fase_partido', FILTER_SANITIZE_STRING); // **NUEVO: Obtener la fase**

    if (!$id_division) {
        $errores['general'] = 'Por favor, selecciona una división válida.';
    }
    if (!$fecha_numero) {
        $errores['general'] = 'Por favor, ingresa un número de fecha válido.';
    }
    if (empty($fecha_base)) {
        $errores['general'] = 'Por favor, selecciona la fecha base para los partidos.';
    }
    if (!$id_torneo) {
        $errores['general'] = 'Por favor, selecciona un torneo válido.';
    }
    // **NUEVO: Validar que se haya seleccionado una fase**
    if (empty($fase_partido) || !in_array($fase_partido, $fases)) {
        $errores['general'] = 'Por favor, selecciona una fase válida para los partidos.';
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            $partidos_cargados = 0;
            foreach ($_POST['local'] as $key => $id_club_local) {
                $id_club_visitante = filter_var($_POST['visitante'][$key] ?? null, FILTER_VALIDATE_INT);
                $hora = filter_var($_POST['hora'][$key] ?? null, FILTER_SANITIZE_STRING);

                if ($id_club_local &&
                    $id_club_visitante &&
                    $id_club_local != $id_club_visitante &&
                    !empty($hora)) {

                    $fecha_hora = $fecha_base . ' ' . $hora . ':00'; // Combinar fecha base y hora

                    // **MODIFICACIÓN: Incluir la columna 'fase' en el INSERT**
                    $stmt_insert = $pdo->prepare("INSERT INTO partidos (id_division, fecha_numero, id_club_local, id_club_visitante, fecha_hora, id_torneo, jugado, fase) VALUES (:id_division, :fecha_numero, :id_club_local, :id_club_visitante, :fecha_hora, :id_torneo, 0, :fase)");
                    $stmt_insert->bindParam(':id_division', $id_division, PDO::PARAM_INT);
                    $stmt_insert->bindParam(':fecha_numero', $fecha_numero, PDO::PARAM_INT);
                    $stmt_insert->bindParam(':id_club_local', $id_club_local, PDO::PARAM_INT);
                    $stmt_insert->bindParam(':id_club_visitante', $id_club_visitante, PDO::PARAM_INT);
                    $stmt_insert->bindParam(':fecha_hora', $fecha_hora);
                    $stmt_insert->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
                    $stmt_insert->bindParam(':fase', $fase_partido, PDO::PARAM_STR); // **NUEVO: Bind de la fase**
                    $stmt_insert->execute();
                    $partidos_cargados++;
                } elseif (!empty($id_club_local) || !empty($id_club_visitante) || !empty($hora)) {
                    $errores['partidos'][] = "Por favor, completa todos los datos (local, visitante y hora) para el partido en la fila " . ($key + 1) . ".";
                }
            }

            $pdo->commit();
            if ($partidos_cargados > 0) {
                $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill"></i> Se cargaron ' . $partidos_cargados . ' partidos correctamente.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            if (!empty($errores['partidos'])) {
                $mensaje .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i> Se encontraron problemas en algunos partidos. Por favor, revisa los datos:
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                <ul class="mt-2 mb-0">';
                foreach ($errores['partidos'] as $error) {
                    $mensaje .= '<li>' . $error . '</li>';
                }
                $mensaje .= '</ul></div>';
            } elseif ($partidos_cargados == 0 && empty($errores['general'])) {
                $mensaje = '<div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="bi bi-info-circle-fill"></i> No se cargaron partidos. Asegúrate de completar al menos un partido correctamente.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores['general'] = 'Hubo un error al cargar los partidos: ' . $e->getMessage();
        }
    }
}

// Incluir header después de todas las redirecciones
include '../header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Fecha Completa - Liga Deportiva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        .btn-secondary {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        .partido-row {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background-color: #fff;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            align-items: center;
            transition: all 0.2s ease;
        }
        .partido-row:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .partido-row label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        .match-preview {
            margin-top: 10px;
            padding: 8px;
            border-radius: 8px;
            background-color: #e9ecef;
            text-align: center;
        }
        .remove-btn {
            color: #dc3545;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .remove-btn:hover {
            color: #bd2130;
            transform: scale(1.1);
        }
        .fecha-info {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .fecha-info h5 {
            color: #004386;
            margin-bottom: 10px;
        }
        .fecha-info p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-calendar-plus"></i> Cargar Fecha Completa</h1>
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

        <?php if ($mensaje): ?>
            <?= $mensaje; ?>
        <?php endif; ?>
        
        <?php if (!empty($errores['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $errores['general']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Información de la Fecha
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
                                        <option value="<?= $torneo['id_torneo']; ?>"><?= htmlspecialchars($torneo['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor, selecciona un torneo.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_division" class="form-label">División</label>
                                <select class="form-select" id="id_division" name="id_division" required>
                                    <option value="">Seleccionar División</option>
                                    <?php foreach ($divisiones as $division): ?>
                                        <option value="<?= $division['id_division']; ?>"><?= htmlspecialchars($division['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor, selecciona una división.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="fecha_numero" class="form-label">Número de Fecha</label>
                                <input type="number" class="form-control" id="fecha_numero" name="fecha_numero" min="1" required>
                                <div class="invalid-feedback">Por favor, ingresa un número de fecha válido.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="fecha_base" class="form-label">Fecha Base para los Partidos</label>
                                <input type="date" class="form-control" id="fecha_base" name="fecha_base" required>
                                <div class="invalid-feedback">Por favor, selecciona la fecha base.</div>
                                <div class="form-text">Esta será la fecha para todos los partidos.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="fase_partido" class="form-label">Fase del Partido</label>
                                <select class="form-select" id="fase_partido" name="fase_partido" required>
                                    <option value="">Seleccionar Fase</option>
                                    <?php foreach ($fases as $fase_opcion): ?>
                                        <option value="<?= htmlspecialchars($fase_opcion); ?>" <?= ($fase_opcion === 'Primera Fase') ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($fase_opcion); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor, selecciona una fase.</div>
                                <div class="form-text">Indica si es un partido de fase regular o playoff.</div>
                            </div>
                        </div>
                        </div>
                    
                    <div class="fecha-info mt-3 d-none" id="fecha-preview">
                        <h5><i class="bi bi-calendar-check"></i> Resumen de la Fecha</h5>
                        <p><strong>Torneo:</strong> <span id="preview-torneo">-</span></p>
                        <p><strong>División:</strong> <span id="preview-division">-</span></p>
                        <p><strong>Número de Fecha:</strong> <span id="preview-numero">-</span></p>
                        <p><strong>Fecha Base:</strong> <span id="preview-fecha">-</span></p>
                        <p><strong>Fase:</strong> <span id="preview-fase">-</span></p> </div>

                    <h4 class="mt-4 mb-3"><i class="bi bi-list-check"></i> Definir Partidos</h4>
                    
                    <div id="partidos-container">
                        <div class="partido-row position-relative">
                            <div>
                                <label for="local_0">Equipo Local:</label>
                                <select class="form-select select-local" id="local_0" name="local[]" data-index="0">
                                    <option value="">Seleccionar Local</option>
                                    <?php foreach ($clubes as $club): ?>
                                        <option value="<?= $club['id_club']; ?>" data-nombre="<?= htmlspecialchars($club['nombre_corto']); ?>"><?= htmlspecialchars($club['nombre_corto']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="visitante_0">Equipo Visitante:</label>
                                <select class="form-select select-visitante" id="visitante_0" name="visitante[]" data-index="0">
                                    <option value="">Seleccionar Visitante</option>
                                    <?php foreach ($clubes as $club): ?>
                                        <option value="<?= $club['id_club']; ?>" data-nombre="<?= htmlspecialchars($club['nombre_corto']); ?>"><?= htmlspecialchars($club['nombre_corto']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="hora_0">Hora del Partido:</label>
                                <input type="time" class="form-control input-hora" id="hora_0" name="hora[]" data-index="0">
                            </div>
                            
                            <div class="match-preview mt-2 d-none" id="preview-0">
                                <span class="local-name">Local</span> vs <span class="visitante-name">Visitante</span> - <span class="hora-partido">--:--</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center my-3">
                        <button type="button" id="add-partido" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Añadir Otro Partido
                        </button>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Cargar Partidos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const partidosContainer = document.getElementById('partidos-container');
            const addPartidoButton = document.getElementById('add-partido');
            let partidoCount = 1;

            // Función para generar HTML de un nuevo partido
            function createPartidoHTML(index) {
                return `
                    <div class="partido-row position-relative">
                        <button type="button" class="remove-btn" title="Eliminar partido">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                        
                        <div>
                            <label for="local_${index}">Equipo Local:</label>
                            <select class="form-select select-local" id="local_${index}" name="local[]" data-index="${index}">
                                <option value="">Seleccionar Local</option>
                                <?php foreach ($clubes as $club): ?>
                                    <option value="<?= $club['id_club']; ?>" data-nombre="<?= htmlspecialchars($club['nombre_corto']); ?>"><?= htmlspecialchars($club['nombre_corto']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="visitante_${index}">Equipo Visitante:</label>
                            <select class="form-select select-visitante" id="visitante_${index}" name="visitante[]" data-index="${index}">
                                <option value="">Seleccionar Visitante</option>
                                <?php foreach ($clubes as $club): ?>
                                    <option value="<?= $club['id_club']; ?>" data-nombre="<?= htmlspecialchars($club['nombre_corto']); ?>"><?= htmlspecialchars($club['nombre_corto']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="hora_${index}">Hora del Partido:</label>
                            <input type="time" class="form-control input-hora" id="hora_${index}" name="hora[]" data-index="${index}">
                        </div>
                        
                        <div class="match-preview mt-2 d-none" id="preview-${index}">
                            <span class="local-name">Local</span> vs <span class="visitante-name">Visitante</span> - <span class="hora-partido">--:--</span>
                        </div>
                    </div>
                `;
            }

            // Añadir partido
            addPartidoButton.addEventListener('click', function() {
                const partidoHTML = createPartidoHTML(partidoCount);
                const tempContainer = document.createElement('div');
                tempContainer.innerHTML = partidoHTML;
                const newPartidoRow = tempContainer.firstElementChild;
                
                partidosContainer.appendChild(newPartidoRow);
                
                // Inicializar el evento de cambio para las selecciones
                initializeSelectHandlers(newPartidoRow);
                
                // Inicializar botón de eliminar
                initializeRemoveButton(newPartidoRow);
                
                partidoCount++;
            });

            // Inicializar handlers para primera fila
            initializeSelectHandlers(document.querySelector('.partido-row'));
            
            // Handler para ver información de la fecha
            document.getElementById('id_torneo').addEventListener('change', updateFechaPreview);
            document.getElementById('id_division').addEventListener('change', updateFechaPreview);
            document.getElementById('fecha_numero').addEventListener('input', updateFechaPreview);
            document.getElementById('fecha_base').addEventListener('change', updateFechaPreview);
            document.getElementById('fase_partido').addEventListener('change', updateFechaPreview); // **NUEVO: Listener para la fase**
            
            // Función para actualizar la vista previa de la fecha
            function updateFechaPreview() {
                const torneoSelect = document.getElementById('id_torneo');
                const divisionSelect = document.getElementById('id_division');
                const fechaNumero = document.getElementById('fecha_numero').value;
                const fechaBase = document.getElementById('fecha_base').value;
                const fasePartidoSelect = document.getElementById('fase_partido'); // **NUEVO: Obtener la fase**
                
                const fechaPreview = document.getElementById('fecha-preview');
                const previewTorneo = document.getElementById('preview-torneo');
                const previewDivision = document.getElementById('preview-division');
                const previewNumero = document.getElementById('preview-numero');
                const previewFecha = document.getElementById('preview-fecha');
                const previewFase = document.getElementById('preview-fase'); // **NUEVO: Elemento de preview de fase**
                
                if (torneoSelect.value || divisionSelect.value || fechaNumero || fechaBase || fasePartidoSelect.value) { // **MODIFICADO: Incluir fase en la condición**
                    fechaPreview.classList.remove('d-none');
                    previewTorneo.textContent = torneoSelect.options[torneoSelect.selectedIndex]?.text || '-';
                    previewDivision.textContent = divisionSelect.options[divisionSelect.selectedIndex]?.text || '-';
                    previewNumero.textContent = fechaNumero || '-';
                    previewFase.textContent = fasePartidoSelect.options[fasePartidoSelect.selectedIndex]?.text || '-'; // **NUEVO: Actualizar preview de fase**
                    
                    // Formatear fecha
                    if (fechaBase) {
                        const fecha = new Date(fechaBase);
                        previewFecha.textContent = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    } else {
                        previewFecha.textContent = '-';
                    }
                } else {
                    fechaPreview.classList.add('d-none');
                }
            }
            
            // Función para inicializar botón de eliminar
            function initializeRemoveButton(partidoRow) {
                const removeBtn = partidoRow.querySelector('.remove-btn');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function() {
                        partidoRow.remove();
                    });
                }
            }
            
            // Función para inicializar handlers de selección
            function initializeSelectHandlers(partidoRow) {
                const localSelect = partidoRow.querySelector('.select-local');
                const visitanteSelect = partidoRow.querySelector('.select-visitante');
                const horaInput = partidoRow.querySelector('.input-hora');
                const index = localSelect.dataset.index;
                const preview = document.getElementById('preview-' + index);
                const localName = preview.querySelector('.local-name');
                const visitanteName = preview.querySelector('.visitante-name');
                const horaPartido = preview.querySelector('.hora-partido');
                
                // Actualizar vista previa cuando cambia el equipo local
                localSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    localName.textContent = selectedOption.dataset.nombre || 'Local';
                    updateMatchPreview();
                });
                
                // Actualizar vista previa cuando cambia el equipo visitante
                visitanteSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    visitanteName.textContent = selectedOption.dataset.nombre || 'Visitante';
                    updateMatchPreview();
                    
                    // Verificar si los equipos son iguales
                    if (localSelect.value && visitanteSelect.value && localSelect.value === visitanteSelect.value) {
                        alert("Error: El equipo local y visitante no pueden ser el mismo");
                        this.value = '';
                        visitanteName.textContent = 'Visitante';
                    }
                });
                
                // Actualizar vista previa cuando cambia la hora
                horaInput.addEventListener('input', function() {
                    horaPartido.textContent = this.value || '--:--';
                    updateMatchPreview();
                });
                
                // Función para mostrar/ocultar vista previa
                function updateMatchPreview() {
                    if (localSelect.value || visitanteSelect.value || horaInput.value) {
                        preview.classList.remove('d-none');
                    } else {
                        preview.classList.add('d-none');
                    }
                }
            }
            
            // Validación del formulario
            (() => {
                'use strict';
                const forms = document.querySelectorAll('.needs-validation');
                Array.from(forms).forEach(form => {
                    form.addEventListener('submit', event => {
                        // Verificar pares de equipos
                        const localSelects = form.querySelectorAll('.select-local');
                        const visitanteSelects = form.querySelectorAll('.select-visitante');
                        
                        let isValid = true;
                        localSelects.forEach((local, index) => {
                            const visitante = visitanteSelects[index];
                            if (local.value && visitante.value && local.value === visitante.value) {
                                isValid = false;
                                alert(`Error: El equipo local y visitante no pueden ser el mismo en el partido ${index + 1}`);
                            }
                        });
                        
                        if (!isValid || !form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
        });
    </script>
</body>
</html>