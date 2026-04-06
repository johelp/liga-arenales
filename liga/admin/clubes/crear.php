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
$nombre_corto = '';
$nombre_completo = '';
$escudo_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_corto    = trim($_POST['nombre_corto']    ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $escudo_url      = trim($_POST['escudo_url']      ?? '');

    if (empty($nombre_corto)) {
        $errores['nombre_corto'] = 'El nombre corto es obligatorio.';
    }
    if (empty($nombre_completo)) {
        $errores['nombre_completo'] = 'El nombre completo es obligatorio.';
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO clubes (nombre_corto, nombre_completo, escudo_url) VALUES (:nombre_corto, :nombre_completo, :escudo_url)");
            $stmt->bindParam(':nombre_corto', $nombre_corto);
            $stmt->bindParam(':nombre_completo', $nombre_completo);
            $stmt->bindParam(':escudo_url', $escudo_url);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = 'Club creado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit();
            } else {
                $errores['general'] = 'Hubo un error al guardar el club.';
            }
        } catch (PDOException $e) {
            $errores['general'] = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
}

// Incluir header después de procesar formularios y posibles redirecciones
include '../header.php';
?>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-plus-circle"></i> Crear Nuevo Club</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        </div>

        <?php if (!empty($errores['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $errores['general']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Vista previa del club -->
        <div class="preview-panel">
            <div id="escudo-preview" class="club-escudo-placeholder">
                <i class="bi bi-shield"></i>
            </div>
            <div id="preview-nombre" class="club-name">Nombre del Club</div>
            <div id="preview-nombre-completo" class="club-fullname">Nombre Completo del Club</div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Información del Club
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre_corto" class="form-label">Nombre Corto:</label>
                            <input type="text" class="form-control <?= !empty($errores['nombre_corto']) ? 'is-invalid' : ''; ?>" 
                                id="nombre_corto" name="nombre_corto" value="<?= htmlspecialchars($nombre_corto); ?>" required>
                            <?php if (!empty($errores['nombre_corto'])): ?>
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i> <?= $errores['nombre_corto']; ?>
                                </div>
                            <?php else: ?>
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i> El nombre corto es obligatorio.
                                </div>
                            <?php endif; ?>
                            <small class="form-text text-muted">
                                Nombre abreviado o siglas del club (ej. "FCB", "River", "Boca").
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="nombre_completo" class="form-label">Nombre Completo:</label>
                            <input type="text" class="form-control <?= !empty($errores['nombre_completo']) ? 'is-invalid' : ''; ?>"
                                id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($nombre_completo); ?>" required>
                            <?php if (!empty($errores['nombre_completo'])): ?>
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i> <?= $errores['nombre_completo']; ?>
                                </div>
                            <?php else: ?>
                                <div class="invalid-feedback">
                                    <i class="bi bi-exclamation-circle me-1"></i> El nombre completo es obligatorio.
                                </div>
                            <?php endif; ?>
                            <small class="form-text text-muted">
                                Nombre oficial completo del club (ej. "Fútbol Club Barcelona", "Club Atlético River Plate").
                            </small>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="escudo_url" class="form-label">URL del Escudo (opcional):</label>
                            <input type="url" class="form-control" id="escudo_url" name="escudo_url" 
                                value="<?= htmlspecialchars($escudo_url); ?>" placeholder="https://ejemplo.com/escudo.png">
                            <div class="url-helper">
                                <i class="bi bi-info-circle me-1"></i> Ingresa la URL completa de la imagen del escudo. 
                                Formatos recomendados: PNG o SVG con fondo transparente.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Club
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Vista previa en tiempo real
            const nombreCortoInput = document.getElementById('nombre_corto');
            const nombreCompletoInput = document.getElementById('nombre_completo');
            const escudoUrlInput = document.getElementById('escudo_url');
            
            const previewNombre = document.getElementById('preview-nombre');
            const previewNombreCompleto = document.getElementById('preview-nombre-completo');
            const escudoPreview = document.getElementById('escudo-preview');
            
            // Actualizar nombre corto
            nombreCortoInput.addEventListener('input', function() {
                previewNombre.textContent = this.value || 'Nombre del Club';
            });
            
            // Actualizar nombre completo
            nombreCompletoInput.addEventListener('input', function() {
                previewNombreCompleto.textContent = this.value || 'Nombre Completo del Club';
            });
            
            // Actualizar escudo
            escudoUrlInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    // Crear una imagen para la vista previa
                    escudoPreview.innerHTML = '';
                    escudoPreview.className = '';
                    
                    const img = document.createElement('img');
                    img.src = this.value;
                    img.alt = 'Escudo del club';
                    img.className = 'club-escudo';
                    
                    // Si la imagen no carga, mostrar icono placeholder
                    img.onerror = function() {
                        escudoPreview.innerHTML = '<i class="bi bi-shield"></i>';
                        escudoPreview.className = 'club-escudo-placeholder';
                    };
                    
                    escudoPreview.appendChild(img);
                } else {
                    // Mostrar placeholder si no hay URL
                    escudoPreview.innerHTML = '<i class="bi bi-shield"></i>';
                    escudoPreview.className = 'club-escudo-placeholder';
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
