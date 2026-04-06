<?php
ob_start(); // Garantizar que no haya problemas con los headers
require_once '../../config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

$errores = [];
$id_club = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$club = null;

if ($id_club > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clubes WHERE id_club = :id");
        $stmt->bindParam(':id', $id_club, PDO::PARAM_INT);
        $stmt->execute();
        $club = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($club) {
            $nombre_corto = $club['nombre_corto'];
            $nombre_completo = $club['nombre_completo'];
            $escudo_url = $club['escudo_url'];
            $fecha_fundacion = $club['fecha_fundacion'];
            $direccion = $club['direccion'];
            $telefono = $club['telefono'];
            $sitio_web = $club['sitio_web'];
        } else {
            $_SESSION['mensaje'] = 'Club no encontrado.';
            $_SESSION['tipo_mensaje'] = 'warning';
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al obtener datos del club: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: index.php');
        exit();
    }
} else {
    $_SESSION['mensaje'] = 'ID de club inválido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_corto = filter_input(INPUT_POST, 'nombre_corto', FILTER_SANITIZE_STRING);
    $nombre_completo = filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_STRING);
    $escudo_url = filter_input(INPUT_POST, 'escudo_url', FILTER_SANITIZE_URL);
    $fecha_fundacion = filter_input(INPUT_POST, 'fecha_fundacion') ?: null;
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $sitio_web = filter_input(INPUT_POST, 'sitio_web', FILTER_SANITIZE_URL);

    if (empty($nombre_corto)) {
        $errores['nombre_corto'] = 'El nombre corto es obligatorio.';
    }
    if (empty($nombre_completo)) {
        $errores['nombre_completo'] = 'El nombre completo es obligatorio.';
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("UPDATE clubes SET 
                nombre_corto = :nombre_corto, 
                nombre_completo = :nombre_completo, 
                escudo_url = :escudo_url,
                fecha_fundacion = :fecha_fundacion,
                direccion = :direccion,
                telefono = :telefono,
                sitio_web = :sitio_web
                WHERE id_club = :id");
            
            $stmt->bindParam(':nombre_corto', $nombre_corto);
            $stmt->bindParam(':nombre_completo', $nombre_completo);
            $stmt->bindParam(':escudo_url', $escudo_url);
            $stmt->bindParam(':fecha_fundacion', $fecha_fundacion);
            $stmt->bindParam(':direccion', $direccion);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':sitio_web', $sitio_web);
            $stmt->bindParam(':id', $id_club, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = 'Club actualizado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit();
            } else {
                $errores['general'] = 'Hubo un error al actualizar el club.';
            }
        } catch (PDOException $e) {
            $errores['general'] = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
}

// Incluir header después de procesar formularios y posibles redirecciones
include '../header.php';

// Función para formatear fecha
function formatearFecha($fecha) {
    if (empty($fecha)) return '';
    return date('Y-m-d', strtotime($fecha));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Club - <?= htmlspecialchars($nombre_corto); ?> - Liga Deportiva</title>
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
        .preview-panel {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .club-escudo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
            background-color: white;
            border-radius: 50%;
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        .club-escudo-placeholder {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: #6c757d;
            font-size: 2.5rem;
            margin: 0 auto 15px;
            border-radius: 50%;
            border: 1px solid #dee2e6;
        }
        .club-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #004386;
            margin-bottom: 5px;
        }
        .club-fullname {
            color: #6c757d;
        }
        .invalid-feedback {
            font-size: 0.875rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }
        .url-helper {
            margin-top: 5px;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            margin-bottom: -1px;
            border: 1px solid transparent;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            color: #004386;
        }
        .nav-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: 600;
            color: #004386;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-pencil-square"></i> Editar Club</h1>
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
            <div id="escudo-preview" class="<?= !empty($escudo_url) ? '' : 'club-escudo-placeholder' ?>">
                <?php if (!empty($escudo_url)): ?>
                    <img src="<?= htmlspecialchars($escudo_url); ?>" alt="Escudo del club" class="club-escudo">
                <?php else: ?>
                    <i class="bi bi-shield"></i>
                <?php endif; ?>
            </div>
            <div id="preview-nombre" class="club-name"><?= htmlspecialchars($nombre_corto); ?></div>
            <div id="preview-nombre-completo" class="club-fullname"><?= htmlspecialchars($nombre_completo); ?></div>
            <?php if (!empty($sitio_web)): ?>
                <div class="mt-2">
                    <a href="<?= htmlspecialchars($sitio_web); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-globe"></i> Sitio Web
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Información del Club
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                            <i class="bi bi-card-text"></i> Información General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" data-bs-target="#contacto" type="button" role="tab" aria-controls="contacto" aria-selected="false">
                            <i class="bi bi-telephone"></i> Contacto
                        </button>
                    </li>
                </ul>
                
                <form method="post" class="needs-validation" novalidate>
                    <div class="tab-content" id="myTabContent">
                        <!-- Pestaña de Información General -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row mt-3">
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
                                        Nombre abreviado o siglas del club.
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
                                        Nombre oficial completo del club.
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="escudo_url" class="form-label">URL del Escudo (opcional):</label>
                                    <input type="url" class="form-control" id="escudo_url" name="escudo_url" 
                                        value="<?= htmlspecialchars($escudo_url); ?>" placeholder="https://ejemplo.com/escudo.png">
                                    <div class="url-helper">
                                        <i class="bi bi-info-circle me-1"></i> Ingresa la URL completa de la imagen del escudo.
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="fecha_fundacion" class="form-label">Fecha de Fundación (opcional):</label>
                                    <input type="date" class="form-control" id="fecha_fundacion" name="fecha_fundacion" 
                                        value="<?= formatearFecha($fecha_fundacion); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña de Contacto -->
                        <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="contacto-tab">
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="direccion" class="form-label">Dirección (opcional):</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" 
                                        value="<?= htmlspecialchars($direccion); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono (opcional):</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                        value="<?= htmlspecialchars($telefono); ?>">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="sitio_web" class="form-label">Sitio Web (opcional):</label>
                                    <input type="url" class="form-control" id="sitio_web" name="sitio_web" 
                                        value="<?= htmlspecialchars($sitio_web); ?>" placeholder="https://www.ejemplo.com">
                                </div>
                            </div>
                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Vista previa en tiempo real
            const nombreCortoInput = document.getElementById('nombre_corto');
            const nombreCompletoInput = document.getElementById('nombre_completo');
            const escudoUrlInput = document.getElementById('escudo_url');
            const sitioWebInput = document.getElementById('sitio_web');
            
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
            
            // Actualizar sitio web
            sitioWebInput.addEventListener('input', function() {
                const contenedorSitioWeb = document.querySelector('.preview-panel .mt-2');
                if (this.value.trim()) {
                    if (!contenedorSitioWeb) {
                        const div = document.createElement('div');
                        div.className = 'mt-2';
                        div.innerHTML = `<a href="${this.value}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-globe"></i> Sitio Web
                        </a>`;
                        document.querySelector('.preview-panel').appendChild(div);
                    } else {
                        contenedorSitioWeb.querySelector('a').href = this.value;
                    }
                } else if (contenedorSitioWeb) {
                    contenedorSitioWeb.remove();
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
</body>
</html>