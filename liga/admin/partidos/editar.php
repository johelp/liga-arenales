<?php
ob_start(); // Garantizar que no haya problemas con los headers
require_once '../../config.php'; // Incluir config.php PRIMERO

// Inicializar conexión a la base de datos
$pdo = conectarDB();

// Función para obtener el nombre de un club por su ID
function getNombreClub($clubes, $id_club) {
    foreach ($clubes as $club) {
        if ($club['id_club'] == $id_club) {
            return htmlspecialchars($club['nombre_completo']); // Usamos nombre_completo
        }
    }
    return 'Club no encontrado';
}

// Verificar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_partido = filter_input(INPUT_POST, 'id_partido', FILTER_VALIDATE_INT);
    $id_torneo = filter_input(INPUT_POST, 'id_torneo', FILTER_VALIDATE_INT);
    $id_division = filter_input(INPUT_POST, 'id_division', FILTER_VALIDATE_INT);
    $fecha_hora = filter_input(INPUT_POST, 'fecha_hora', FILTER_SANITIZE_STRING);
    $id_club_local = filter_input(INPUT_POST, 'id_club_local', FILTER_VALIDATE_INT);
    $goles_local = filter_input(INPUT_POST, 'goles_local', FILTER_VALIDATE_INT);
    $id_club_visitante = filter_input(INPUT_POST, 'id_club_visitante', FILTER_VALIDATE_INT);
    $goles_visitante = filter_input(INPUT_POST, 'goles_visitante', FILTER_VALIDATE_INT);
    $arbitro = filter_input(INPUT_POST, 'arbitro', FILTER_SANITIZE_STRING);
    $estadio = filter_input(INPUT_POST, 'estadio', FILTER_SANITIZE_STRING);
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING);
    $jugado = isset($_POST['jugado']) ? 1 : 0; // Añadido el campo jugado

    // Validar que los equipos no sean iguales
    if ($id_club_local === $id_club_visitante) {
        $error_message = "El equipo local y visitante no pueden ser el mismo.";
    } else {
        // Actualizar el partido
        try {
            $stmt = $pdo->prepare("UPDATE partidos SET 
                id_torneo = :id_torneo, 
                id_division = :id_division, 
                fecha_hora = :fecha_hora, 
                id_club_local = :id_club_local, 
                goles_local = :goles_local, 
                id_club_visitante = :id_club_visitante, 
                goles_visitante = :goles_visitante, 
                arbitro = :arbitro, 
                estadio = :estadio, 
                observaciones = :observaciones,
                jugado = :jugado
                WHERE id_partido = :id_partido");
                
            $stmt->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
            $stmt->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt->bindParam(':id_division', $id_division, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_hora', $fecha_hora, PDO::PARAM_STR);
            $stmt->bindParam(':id_club_local', $id_club_local, PDO::PARAM_INT);
            $stmt->bindParam(':goles_local', $goles_local, PDO::PARAM_INT);
            $stmt->bindParam(':id_club_visitante', $id_club_visitante, PDO::PARAM_INT);
            $stmt->bindParam(':goles_visitante', $goles_visitante, PDO::PARAM_INT);
            $stmt->bindParam(':arbitro', $arbitro, PDO::PARAM_STR);
            $stmt->bindParam(':estadio', $estadio, PDO::PARAM_STR);
            $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmt->bindParam(':jugado', $jugado, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Si queremos también actualizar goles y tarjetas, hacerlo aquí
                
                // Redirigir con mensaje de éxito
                $_SESSION['mensaje'] = 'Partido actualizado correctamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit();
            } else {
                $error_message = "Error al actualizar el partido.";
            }
        } catch (PDOException $e) {
            $error_message = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// Obtener el ID del partido de la URL
$id_partido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_partido) {
    $_SESSION['mensaje'] = 'ID de partido no válido';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

// Obtener los datos del partido
try {
    $stmt = $pdo->prepare("SELECT * FROM partidos WHERE id_partido = :id_partido");
    $stmt->bindParam(':id_partido', $id_partido, PDO::PARAM_INT);
    $stmt->execute();
    $partido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partido) {
        $_SESSION['mensaje'] = 'Partido no encontrado';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar el partido: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

// Obtener la lista de torneos
try {
    $stmt = $pdo->prepare("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
    $stmt->execute();
    $torneos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $torneos = [];
}

// Obtener la lista de divisiones
try {
    $stmt = $pdo->prepare("SELECT id_division, nombre FROM divisiones ORDER BY nombre");
    $stmt->execute();
    $divisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $divisiones = [];
}

// Obtener la lista de clubes
try {
    $stmt = $pdo->prepare("SELECT id_club, nombre_completo, nombre_corto FROM clubes ORDER BY nombre_completo");
    $stmt->execute();
    $clubes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $clubes = [];
}

// Incluir el header después de verificar posibles redirecciones
include '../header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Partido - Liga Deportiva</title>
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
        .match-preview {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .team-name {
            font-weight: 600;
            font-size: 18px;
            margin: 10px 0;
        }
        .score {
            font-size: 24px;
            font-weight: 700;
            background-color: #fff;
            border-radius: 8px;
            padding: 5px 15px;
            display: inline-block;
            margin: 0 10px;
            min-width: 60px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-pencil-square"></i> Editar Partido</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Vista previa del partido -->
        <div class="match-preview">
            <div class="row align-items-center">
                <div class="col-md-5 text-md-end">
                    <div class="team-name"><?= getNombreClub($clubes, $partido['id_club_local']); ?></div>
                </div>
                <div class="col-md-2">
                    <div class="score"><?= $partido['goles_local'] ?? '0'; ?> - <?= $partido['goles_visitante'] ?? '0'; ?></div>
                </div>
                <div class="col-md-5 text-md-start">
                    <div class="team-name"><?= getNombreClub($clubes, $partido['id_club_visitante']); ?></div>
                </div>
            </div>
            <div class="mt-2 text-muted">
                <?= date('d/m/Y H:i', strtotime($partido['fecha_hora'])); ?>
                <?php if (!empty($partido['estadio'])): ?>
                    <span class="ms-2"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($partido['estadio']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Información del Partido
                    </div>
                    <div class="card-body">
                        <form action="editar.php" method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="id_partido" value="<?= $partido['id_partido']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_torneo" class="form-label">Torneo</label>
                                        <select class="form-select" id="id_torneo" name="id_torneo" required>
                                            <option value="">Seleccionar torneo</option>
                                            <?php foreach ($torneos as $torneo): ?>
                                                <option value="<?= $torneo['id_torneo']; ?>" <?= ($partido['id_torneo'] == $torneo['id_torneo']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($torneo['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor, selecciona un torneo.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_division" class="form-label">División</label>
                                        <select class="form-select" id="id_division" name="id_division" required>
                                            <option value="">Seleccionar división</option>
                                            <?php foreach ($divisiones as $division): ?>
                                                <option value="<?= $division['id_division']; ?>" <?= ($partido['id_division'] == $division['id_division']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($division['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor, selecciona una división.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_hora" class="form-label">Fecha y Hora</label>
                                <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" value="<?= date('Y-m-d\TH:i', strtotime($partido['fecha_hora'])); ?>" required>
                                <div class="invalid-feedback">Por favor, introduce la fecha y hora del partido.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_club_local" class="form-label">Equipo Local</label>
                                        <select class="form-select" id="id_club_local" name="id_club_local" required>
                                            <option value="">Seleccionar equipo local</option>
                                            <?php foreach ($clubes as $club): ?>
                                                <option value="<?= $club['id_club']; ?>" <?= ($partido['id_club_local'] == $club['id_club']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($club['nombre_completo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor, selecciona el equipo local.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_club_visitante" class="form-label">Equipo Visitante</label>
                                        <select class="form-select" id="id_club_visitante" name="id_club_visitante" required>
                                            <option value="">Seleccionar equipo visitante</option>
                                            <?php foreach ($clubes as $club): ?>
                                                <option value="<?= $club['id_club']; ?>" <?= ($partido['id_club_visitante'] == $club['id_club']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($club['nombre_completo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor, selecciona el equipo visitante.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="goles_local" class="form-label">Goles Local</label>
                                        <input type="number" class="form-control" id="goles_local" name="goles_local" value="<?= $partido['goles_local'] ?? ''; ?>" min="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="goles_visitante" class="form-label">Goles Visitante</label>
                                        <input type="number" class="form-control" id="goles_visitante" name="goles_visitante" value="<?= $partido['goles_visitante'] ?? ''; ?>" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="jugado" name="jugado" <?= $partido['jugado'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="jugado">
                                        <i class="bi bi-check-circle"></i> Partido Jugado
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="arbitro" class="form-label">Árbitro</label>
                                        <input type="text" class="form-control" id="arbitro" name="arbitro" value="<?= htmlspecialchars($partido['arbitro'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="estadio" class="form-label">Estadio</label>
                                        <input type="text" class="form-control" id="estadio" name="estadio" value="<?= htmlspecialchars($partido['estadio'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= htmlspecialchars($partido['observaciones'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formulario Bootstrap
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        // Verificar que los equipos no sean iguales
                        const localId = document.getElementById('id_club_local').value;
                        const visitanteId = document.getElementById('id_club_visitante').value;
                        
                        if (localId && visitanteId && localId === visitanteId) {
                            event.preventDefault();
                            event.stopPropagation();
                            alert("Error: El equipo local y visitante no pueden ser el mismo");
                            return;
                        }
                        
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
                
            // Actualizar la vista previa cuando cambian los valores
            const golesLocalInput = document.getElementById('goles_local');
            const golesVisitanteInput = document.getElementById('goles_visitante');
            const score = document.querySelector('.score');
            
            function updateScore() {
                const golesLocal = golesLocalInput.value || '0';
                const golesVisitante = golesVisitanteInput.value || '0';
                score.textContent = golesLocal + ' - ' + golesVisitante;
            }
            
            golesLocalInput.addEventListener('input', updateScore);
            golesVisitanteInput.addEventListener('input', updateScore);
            
            // Verificar cambios en los equipos
            const clubLocalSelect = document.getElementById('id_club_local');
            const clubVisitanteSelect = document.getElementById('id_club_visitante');
            const teamNames = document.querySelectorAll('.team-name');
            
            clubLocalSelect.addEventListener('change', function() {
                teamNames[0].textContent = this.options[this.selectedIndex].text;
            });
            
            clubVisitanteSelect.addEventListener('change', function() {
                teamNames[1].textContent = this.options[this.selectedIndex].text;
            });
        })();
    </script>
</body>
</html>