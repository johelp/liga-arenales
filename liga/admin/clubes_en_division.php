<?php
ob_start();session_start();
require_once '../config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = conectarDB();

$errores = [];
$id_torneo_seleccionado = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT) ?: 0;
$id_division_seleccionada = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT) ?: 0;

// Obtener información sobre el torneo seleccionado
$torneo_seleccionado = null;
if ($id_torneo_seleccionado > 0) {
    try {
        $stmt_torneo = $pdo->prepare("SELECT id_torneo, nombre FROM torneos WHERE id_torneo = :id");
        $stmt_torneo->bindParam(':id', $id_torneo_seleccionado, PDO::PARAM_INT);
        $stmt_torneo->execute();
        $torneo_seleccionado = $stmt_torneo->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errores['torneo'] = 'Error al obtener información del torneo: ' . $e->getMessage();
    }
}

// Obtener información sobre la división seleccionada
$division_seleccionada = null;
if ($id_division_seleccionada > 0) {
    try {
        $stmt_division = $pdo->prepare("SELECT id_division, nombre FROM divisiones WHERE id_division = :id");
        $stmt_division->bindParam(':id', $id_division_seleccionada, PDO::PARAM_INT);
        $stmt_division->execute();
        $division_seleccionada = $stmt_division->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errores['division'] = 'Error al obtener información de la división: ' . $e->getMessage();
    }
}

// Obtener todos los torneos
try {
    $stmt_torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
    $torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['torneos'] = 'Error al obtener torneos: ' . $e->getMessage();
    $torneos = [];
}

// Obtener todas las divisiones
try {
    $stmt_divisiones = $pdo->query("SELECT id_division, nombre FROM divisiones ORDER BY orden");
    $divisiones = $stmt_divisiones->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['divisiones'] = 'Error al obtener divisiones: ' . $e->getMessage();
    $divisiones = [];
}

// Obtener todos los clubes con escudos
try {
    $stmt_clubes = $pdo->query("SELECT id_club, nombre_corto, nombre_completo, escudo_url FROM clubes ORDER BY nombre_corto");
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['clubes'] = 'Error al obtener clubes: ' . $e->getMessage();
    $clubes = [];
}

// Obtener los clubes asignados a la división y torneo seleccionados
$clubes_asignados = [];
if ($id_torneo_seleccionado > 0 && $id_division_seleccionada > 0) {
    try {
        $stmt_asignados = $pdo->prepare("SELECT id_club FROM clubes_en_division WHERE id_torneo = :id_torneo AND id_division = :id_division");
        $stmt_asignados->bindParam(':id_torneo', $id_torneo_seleccionado, PDO::PARAM_INT);
        $stmt_asignados->bindParam(':id_division', $id_division_seleccionada, PDO::PARAM_INT);
        $stmt_asignados->execute();
        while ($row = $stmt_asignados->fetch(PDO::FETCH_ASSOC)) {
            $clubes_asignados[] = $row['id_club'];
        }
    } catch (PDOException $e) {
        $errores['asignados'] = 'Error al obtener clubes asignados: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asignaciones'])) {
    $id_torneo_post = filter_input(INPUT_POST, 'id_torneo', FILTER_VALIDATE_INT);
    $id_division_post = filter_input(INPUT_POST, 'id_division', FILTER_VALIDATE_INT);
    $clubes_seleccionados = isset($_POST['clubes']) ? $_POST['clubes'] : [];

    if ($id_torneo_post > 0 && $id_division_post > 0) {
        try {
            // Comenzar transacción
            $pdo->beginTransaction();
            
            // Eliminar las asignaciones existentes para este torneo y división
            $stmt_eliminar = $pdo->prepare("DELETE FROM clubes_en_division WHERE id_torneo = :id_torneo AND id_division = :id_division");
            $stmt_eliminar->bindParam(':id_torneo', $id_torneo_post, PDO::PARAM_INT);
            $stmt_eliminar->bindParam(':id_division', $id_division_post, PDO::PARAM_INT);
            $stmt_eliminar->execute();

            // Insertar las nuevas asignaciones
            if (!empty($clubes_seleccionados)) {
                $stmt_insertar = $pdo->prepare("INSERT INTO clubes_en_division (id_torneo, id_division, id_club) VALUES (:id_torneo, :id_division, :id_club)");
                foreach ($clubes_seleccionados as $id_club) {
                    $id_club = filter_var($id_club, FILTER_VALIDATE_INT);
                    if ($id_club) {
                        $stmt_insertar->bindParam(':id_torneo', $id_torneo_post, PDO::PARAM_INT);
                        $stmt_insertar->bindParam(':id_division', $id_division_post, PDO::PARAM_INT);
                        $stmt_insertar->bindParam(':id_club', $id_club, PDO::PARAM_INT);                
                        $stmt_insertar->execute();
                    }
                }
            }
            
            // Confirmar transacción
            $pdo->commit();
            
            $_SESSION['mensaje'] = 'Asignaciones guardadas correctamente.';
            $_SESSION['tipo_mensaje'] = 'success';
            header("Location: clubes_en_division.php?torneo_id=$id_torneo_post&division_id=$id_division_post");
            exit();
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $pdo->rollBack();
            $errores['guardar'] = 'Error al guardar asignaciones: ' . $e->getMessage();
        }
    } else {
        $errores['seleccion'] = 'Por favor, selecciona un torneo y una división válidos.';
    }
}

// Incluir header después de procesar formularios y posibles redirecciones
include 'header.php';

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
        .btn-success {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .club-list {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
            background-color: white;
        }
        .club-item {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .club-item:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        .club-badge {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            margin-right: 10px;
        }
        .form-check-input:checked + .form-check-label {
            font-weight: 600;
            color: #004386;
        }
        .search-box {
            position: relative;
            margin-bottom: 15px;
        }
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        .search-box input {
            padding-left: 40px;
        }
        .filter-badge {
            background-color: #004386;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            display: inline-block;
        }
        .selected-count {
            font-weight: 600;
            color: #004386;
            background-color: #e9ecef;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        .seleccionar-todos {
            cursor: pointer;
            color: #004386;
            text-decoration: underline;
            margin-left: 15px;
        }
        .club-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }
        .action-buttons {
            position: sticky;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }
    
</style>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-diagram-3"></i> Asignar Clubes</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?= $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            // Limpiar el mensaje después de mostrarlo
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        <?php endif; ?>

        <?php foreach ($errores as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>

        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-filter"></i> Filtrar Asignaciones
            </div>
            <div class="card-body">
                <form method="get" id="filtroForm" class="row g-3">
                    <div class="col-md-6">
                        <label for="torneo_id" class="form-label">Seleccionar Torneo:</label>
                        <select class="form-select" id="torneo_id" name="torneo_id" required>
                            <option value="">-- Seleccionar Torneo --</option>
                            <?php foreach ($torneos as $torneo): ?>
                                <option value="<?= $torneo['id_torneo']; ?>" <?= ($id_torneo_seleccionado == $torneo['id_torneo']) ? 'selected' : ''; ?>><?= htmlspecialchars($torneo['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="division_id" class="form-label">Seleccionar División:</label>
                        <select class="form-select" id="division_id" name="division_id" required>
                            <option value="">-- Seleccionar División --</option>
                            <?php foreach ($divisiones as $division): ?>
                                <option value="<?= $division['id_division']; ?>" <?= ($id_division_seleccionada == $division['id_division']) ? 'selected' : ''; ?>><?= htmlspecialchars($division['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($id_torneo_seleccionado > 0 && $id_division_seleccionada > 0 && $torneo_seleccionado && $division_seleccionada): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-people-fill"></i> Asignar Clubes a la División
                    </div>
                    <div>
                        <span class="filter-badge">
                            <i class="bi bi-trophy"></i> <?= htmlspecialchars($torneo_seleccionado['nombre']); ?>
                        </span>
                        <span class="filter-badge">
                            <i class="bi bi-diagram-3"></i> <?= htmlspecialchars($division_seleccionada['nombre']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="asignacionForm">
                        <input type="hidden" name="id_torneo" value="<?= $id_torneo_seleccionado; ?>">
                        <input type="hidden" name="id_division" value="<?= $id_division_seleccionada; ?>">
                        <input type="hidden" name="guardar_asignaciones" value="1">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="selected-count">
                                <span id="contadorSeleccionados"><?= count($clubes_asignados); ?></span> clubes seleccionados
                            </div>
                            <div>
                                <span class="seleccionar-todos" id="seleccionarTodos">Seleccionar todos</span>
                                <span class="seleccionar-todos" id="deseleccionarTodos">Deseleccionar todos</span>
                            </div>
                        </div>

                        <div class="search-box">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control" id="buscarClub" placeholder="Buscar club..." autocomplete="off">
                        </div>

                        <div class="club-list">
                            <?php if (empty($clubes)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-exclamation-circle text-muted" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No hay clubes registrados. <a href="clubes/crear.php">Registrar un club</a>.</p>
                                </div>
                            <?php else: ?>
                                <div class="club-grid">
                                    <?php foreach ($clubes as $club): ?>
                                        <div class="club-item">
                                            <div class="form-check d-flex align-items-center">
                                                <input class="form-check-input club-checkbox" type="checkbox" name="clubes[]" value="<?= $club['id_club']; ?>" id="club_<?= $club['id_club']; ?>" <?= in_array($club['id_club'], $clubes_asignados) ? 'checked' : ''; ?>>
                                                <label class="form-check-label ms-2 d-flex align-items-center" for="club_<?= $club['id_club']; ?>">
                                                    <?php if (!empty($club['escudo_url'])): ?>
                                                        <img src="<?= htmlspecialchars($club['escudo_url']); ?>" alt="<?= htmlspecialchars($club['nombre_corto']); ?>" class="club-badge rounded-circle">
                                                    <?php else: ?>
                                                        <div class="club-badge rounded-circle bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-person-fill text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($club['nombre_corto']); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="action-buttons">
                                    <div>
                                        <span class="text-muted">Cambios no guardados</span>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-save"></i> Guardar Asignaciones
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($id_torneo_seleccionado > 0 || $id_division_seleccionada > 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                Por favor, selecciona tanto un torneo como una división para ver y asignar clubes.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Contador de seleccionados
            const checkboxes = document.querySelectorAll('.club-checkbox');
            const contadorSeleccionados = document.getElementById('contadorSeleccionados');
            
            if (checkboxes.length > 0 && contadorSeleccionados) {
                function actualizarContador() {
                    const seleccionados = document.querySelectorAll('.club-checkbox:checked').length;
                    contadorSeleccionados.textContent = seleccionados;
                }
                
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', actualizarContador);
                });
                
                // Seleccionar/Deseleccionar todos
                const seleccionarTodos = document.getElementById('seleccionarTodos');
                const deseleccionarTodos = document.getElementById('deseleccionarTodos');
                
                if (seleccionarTodos && deseleccionarTodos) {
                    seleccionarTodos.addEventListener('click', function() {
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = true;
                        });
                        actualizarContador();
                    });
                    
                    deseleccionarTodos.addEventListener('click', function() {
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        actualizarContador();
                    });
                }
                
                // Buscador de clubes
                const buscarInput = document.getElementById('buscarClub');
                const clubItems = document.querySelectorAll('.club-item');
                
                if (buscarInput) {
                    buscarInput.addEventListener('input', function() {
                        const busqueda = this.value.toLowerCase().trim();
                        
                        clubItems.forEach(item => {
                            const nombreClub = item.querySelector('label').textContent.toLowerCase();
                            if (busqueda === '' || nombreClub.includes(busqueda)) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                }
                
                // Formulario de filtro
                const torneo_select = document.getElementById('torneo_id');
                const division_select = document.getElementById('division_id');
                
                if (torneo_select && division_select) {
                    torneo_select.addEventListener('change', function() {
                        // Si cambia el torneo y ya hay una división seleccionada, enviar el formulario
                        if (division_select.value) {
                            document.getElementById('filtroForm').submit();
                        }
                    });
                    
                    division_select.addEventListener('change', function() {
                        // Si cambia la división y ya hay un torneo seleccionado, enviar el formulario
                        if (torneo_select.value) {
                            document.getElementById('filtroForm').submit();
                        }
                    });
                }
            }
        });
    </script>

<?php include 'footer.php'; ?>
