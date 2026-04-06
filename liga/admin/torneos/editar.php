<?php
ob_start();session_start();
require_once '../../config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$errores = [];
$id_torneo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$torneo = null;
$clubes_asignados = [];

if ($id_torneo > 0) {
    try {
        // Obtener la información del torneo
        $stmt_torneo = $pdo->prepare("SELECT * FROM torneos WHERE id_torneo = :id");
        $stmt_torneo->bindParam(':id', $id_torneo, PDO::PARAM_INT);
        $stmt_torneo->execute();
        $torneo = $stmt_torneo->fetch(PDO::FETCH_ASSOC);

        if ($torneo) {
            // Obtener los clubes asignados a este torneo (en todas las divisiones)
            $stmt_clubes_asignados = $pdo->prepare("
                SELECT c.id_club, c.nombre_corto, c.nombre_completo, c.escudo_url, d.nombre AS nombre_division, d.id_division
                FROM clubes_en_division ced
                JOIN clubes c ON ced.id_club = c.id_club
                JOIN divisiones d ON ced.id_division = d.id_division
                WHERE ced.id_torneo = :id_torneo
                ORDER BY d.orden, c.nombre_corto
            ");
            $stmt_clubes_asignados->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt_clubes_asignados->execute();
            $clubes_asignados = $stmt_clubes_asignados->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['mensaje'] = 'Torneo no encontrado.';
            $_SESSION['tipo_mensaje'] = 'warning';
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al obtener datos del torneo: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: index.php');
        exit();
    }
} else {
    $_SESSION['mensaje'] = 'ID de torneo inválido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

// Procesar el formulario de edición del torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_torneo'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $fecha_inicio = empty($_POST['fecha_inicio']) ? null : $_POST['fecha_inicio'];
    $fecha_fin = empty($_POST['fecha_fin']) ? null : $_POST['fecha_fin'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre del torneo es obligatorio.';
    }

    if (empty($errores)) {
        try {
            $stmt_update = $pdo->prepare("
                UPDATE torneos
                SET nombre = :nombre,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    activo = :activo,
                    descripcion = :descripcion
                WHERE id_torneo = :id
            ");
            $stmt_update->bindParam(':nombre', $nombre);
            $stmt_update->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt_update->bindParam(':fecha_fin', $fecha_fin);
            $stmt_update->bindParam(':activo', $activo, PDO::PARAM_INT);
            $stmt_update->bindParam(':descripcion', $descripcion);
            $stmt_update->bindParam(':id', $id_torneo, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                $_SESSION['mensaje'] = 'Torneo actualizado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                
                // Recargar la información del torneo
                $stmt_torneo->execute();
                $torneo = $stmt_torneo->fetch(PDO::FETCH_ASSOC);
            } else {
                $errores['general'] = 'Hubo un error al guardar los cambios del torneo.';
            }
        } catch (PDOException $e) {
            $errores['general'] = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar'])) {
    header('Location: index.php');
    exit();
}

// Incluir header después de procesar formularios y posibles redirecciones
include '../header.php';

// Función para agrupar clubes por división
function agruparClubesPorDivision($clubes) {
    $resultado = [];
    foreach ($clubes as $club) {
        $division_id = $club['id_division'];
        $division_nombre = $club['nombre_division'];
        
        if (!isset($resultado[$division_id])) {
            $resultado[$division_id] = [
                'nombre' => $division_nombre,
                'clubes' => []
            ];
        }
        
        $resultado[$division_id]['clubes'][] = $club;
    }
    return $resultado;
}

$clubes_por_division = agruparClubesPorDivision($clubes_asignados);

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
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        .form-control:focus {
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
        .club-badge {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            transition: transform 0.2s;
        }
        .club-badge:hover {
            transform: scale(1.2);
            z-index: 10;
        }
        .division-title {
            background-color: #e9ecef;
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .club-item {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
        }
        .club-item:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .badge-division {
            background-color: #004386;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .form-switch .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }
        .torneo-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #842029;
        }
        .preview-panel {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .torneo-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: #004386;
            margin-bottom: 5px;
        }
        .torneo-dates {
            color: #6c757d;
            margin-bottom: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .empty-state i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
    
</style>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-pencil-square"></i> Editar Torneo</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        </div>

        <?php if (!empty($errores['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $errores['general']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill"></i> <?= $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            // Limpiar el mensaje después de mostrarlo
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        <?php endif; ?>

        <!-- Vista previa del torneo -->
        <div class="preview-panel">
            <div class="torneo-name" id="preview-name"><?= htmlspecialchars($torneo['nombre']); ?></div>
            <div class="torneo-dates" id="preview-dates">
                <?php if (!empty($torneo['fecha_inicio']) || !empty($torneo['fecha_fin'])): ?>
                    <?= !empty($torneo['fecha_inicio']) ? date('d/m/Y', strtotime($torneo['fecha_inicio'])) : 'No definida'; ?> 
                    - 
                    <?= !empty($torneo['fecha_fin']) ? date('d/m/Y', strtotime($torneo['fecha_fin'])) : 'No definida'; ?>
                <?php else: ?>
                    Fechas no definidas
                <?php endif; ?>
            </div>
            <div class="torneo-status <?= $torneo['activo'] ? 'status-active' : 'status-inactive'; ?>" id="preview-status">
                <i class="bi <?= $torneo['activo'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-2"></i>
                <?= $torneo['activo'] ? 'Torneo Activo' : 'Torneo Inactivo'; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <!-- Formulario de edición -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-pencil"></i> Información del Torneo
                    </div>
                    <div class="card-body">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="guardar_torneo" value="1">

                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Torneo:</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($torneo['nombre']); ?>" required>
                                <?php if (!empty($errores['nombre'])): ?>
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle"></i> <?= $errores['nombre']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, ingresa un nombre para el torneo.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio:</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($torneo['fecha_inicio'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="fecha_fin" class="form-label">Fecha de Fin:</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($torneo['fecha_fin'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción (opcional):</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($torneo['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" <?= ($torneo['activo'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label ms-2" for="activo">
                                    Torneo Activo
                                </label>
                                <div class="form-text">Los torneos inactivos no se mostrarán en el sitio público.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" name="cancelar" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Clubes asignados al torneo -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-people-fill"></i> Clubes Participantes
                        </div>
                        <a href="../clubes_en_division.php?torneo_id=<?= $id_torneo; ?>" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-pencil-square"></i> Gestionar Participantes
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($clubes_asignados)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h5>Sin clubes asignados</h5>
                                <p class="text-muted">No hay clubes participantes en este torneo.</p>
                                <a href="../clubes_en_division.php?torneo_id=<?= $id_torneo; ?>" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Agregar Clubes
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="divisionesAccordion">
                                <?php foreach ($clubes_por_division as $division_id => $division): ?>
                                    <div class="accordion-item mb-3 border-0 shadow-sm rounded">
                                        <h2 class="accordion-header" id="heading<?= $division_id ?>">
                                            <button class="accordion-button rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $division_id ?>" aria-expanded="true" aria-controls="collapse<?= $division_id ?>">
                                                <i class="bi bi-diagram-3 me-2"></i> <?= htmlspecialchars($division['nombre']); ?> 
                                                <span class="badge bg-secondary ms-2"><?= count($division['clubes']); ?> clubes</span>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $division_id ?>" class="accordion-collapse collapse show" aria-labelledby="heading<?= $division_id ?>">
                                            <div class="accordion-body">
                                                <div class="list-group">
                                                    <?php foreach ($division['clubes'] as $club): ?>
                                                        <div class="club-item d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($club['escudo_url'])): ?>
                                                                    <img src="<?= htmlspecialchars($club['escudo_url']); ?>" 
                                                                        alt="<?= htmlspecialchars($club['nombre_corto']); ?>" 
                                                                        class="club-badge rounded-circle me-3"
                                                                        title="<?= htmlspecialchars($club['nombre_completo']); ?>">
                                                                <?php else: ?>
                                                                    <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                                                        <i class="bi bi-person-fill"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <span><?= htmlspecialchars($club['nombre_corto']); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="mt-3 text-end">
                                                    <a href="../clubes_en_division.php?torneo_id=<?= $id_torneo; ?>&division_id=<?= $division_id; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil-square"></i> Editar División
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Vista previa en tiempo real
            const nameInput = document.getElementById('nombre');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');
            const activoInput = document.getElementById('activo');
            
            const previewName = document.getElementById('preview-name');
            const previewDates = document.getElementById('preview-dates');
            const previewStatus = document.getElementById('preview-status');
            
            // Actualizar nombre
            nameInput.addEventListener('input', function() {
                previewName.textContent = this.value || 'Nombre del Torneo';
            });
            
            // Actualizar fechas
            function actualizarFechas() {
                const fechaInicio = fechaInicioInput.value ? new Date(fechaInicioInput.value) : null;
                const fechaFin = fechaFinInput.value ? new Date(fechaFinInput.value) : null;
                
                let fechasTexto = '';
                if (fechaInicio || fechaFin) {
                    const formatearFecha = (fecha) => {
                        if (!fecha) return 'No definida';
                        return fecha.toLocaleDateString('es-ES');
                    };
                    fechasTexto = `${formatearFecha(fechaInicio)} - ${formatearFecha(fechaFin)}`;
                } else {
                    fechasTexto = 'Fechas no definidas';
                }
                
                previewDates.textContent = fechasTexto;
            }
            
            fechaInicioInput.addEventListener('change', actualizarFechas);
            fechaFinInput.addEventListener('change', actualizarFechas);
            
            // Actualizar estado activo
            activoInput.addEventListener('change', function() {
                if (this.checked) {
                    previewStatus.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Torneo Activo';
                    previewStatus.classList.remove('status-inactive');
                    previewStatus.classList.add('status-active');
                } else {
                    previewStatus.innerHTML = '<i class="bi bi-x-circle-fill me-2"></i> Torneo Inactivo';
                    previewStatus.classList.remove('status-active');
                    previewStatus.classList.add('status-inactive');
                }
            });
            
            // Validación del formulario
            (function () {
                'use strict'
                
                // Obtener todos los formularios que necesitan validación
                const forms = document.querySelectorAll('.needs-validation')
                
                // Iterar sobre ellos y prevenir la sumisión si no son válidos
                Array.from(forms).forEach(form => {
                    form.addEventListener('submit', event => {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
        });
    </script>

<?php include '../footer.php'; ?>
