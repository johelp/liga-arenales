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
$nombre = '';
$orden = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $orden = filter_input(INPUT_POST, 'orden', FILTER_VALIDATE_INT);

    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre de la división es obligatorio.';
    }
    if ($orden === false || $orden === null) {
        $errores['orden'] = 'El orden debe ser un número entero válido.';
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO divisiones (nombre, orden) VALUES (:nombre, :orden)");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':orden', $orden, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = 'División creada correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit();
            } else {
                $errores['general'] = 'Hubo un error al guardar la división.';
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
            <h1><i class="bi bi-plus-circle"></i> Crear Nueva División</h1>
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

        <!-- Vista previa de la división -->
        <div class="preview-panel">
            <div class="division-name" id="preview-name"><?= htmlspecialchars($nombre) ?: 'Nueva División'; ?></div>
            <div>Orden: <span class="division-order" id="preview-order"><?= htmlspecialchars($orden) ?: '0'; ?></span></div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Información de la División
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la División:</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre); ?>" required>
                                <?php if (!empty($errores['nombre'])): ?>
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle"></i> <?= $errores['nombre']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, ingresa un nombre para la división.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="orden" class="form-label">Orden (numérico):</label>
                                <input type="number" class="form-control" id="orden" name="orden" value="<?= htmlspecialchars($orden); ?>" required min="1">
                                <?php if (!empty($errores['orden'])): ?>
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle"></i> <?= $errores['orden']; ?></div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Por favor, ingresa un número válido para el orden.</div>
                                <div class="form-text">El orden determina la posición en que aparecerá la división en las listas.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar División
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar vista previa cuando cambien los campos
            const nameInput = document.getElementById('nombre');
            const orderInput = document.getElementById('orden');
            const previewName = document.getElementById('preview-name');
            const previewOrder = document.getElementById('preview-order');
            
            nameInput.addEventListener('input', function() {
                previewName.textContent = this.value || 'Nueva División';
            });
            
            orderInput.addEventListener('input', function() {
                previewOrder.textContent = this.value || '0';
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
