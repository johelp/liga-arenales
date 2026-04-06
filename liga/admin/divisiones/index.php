<?php
ob_start(); // Asegurar que no haya problemas con los headers
require_once '../../config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();
$mensaje = '';

// Procesar mensajes desde la sesión
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'info';
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

try {
    $stmt = $pdo->query("SELECT * FROM divisiones ORDER BY orden");
    $divisiones = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al cargar divisiones: " . $e->getMessage();
}

// Incluir header después de las redirecciones
include '../header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Divisiones - Liga Deportiva</title>
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
        .btn-primary {
            background-color: #004386;
            border-color: #004386;
        }
        .btn-primary:hover {
            background-color: #003366;
            border-color: #003366;
        }
        .table th {
            background-color: #f1f1f1;
        }
        .badge-primary {
            background-color: #004386;
            color: white;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-list-ol"></i> Gestión de Divisiones</h1>
            <a href="crear.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva División
            </a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?= $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-check"></i> Lista de Divisiones
            </div>
            <div class="card-body">
                <?php if (empty($divisiones)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No hay divisiones registradas. ¡Crea una nueva!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th width="10%">ID</th>
                                    <th width="40%">Nombre</th>
                                    <th width="20%">Orden</th>
                                    <th width="30%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($divisiones as $division): ?>
                                    <tr>
                                        <td><?= $division['id_division']; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($division['nombre']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= $division['orden']; ?></span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="editar.php?id=<?= $division['id_division']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmarEliminar(<?= $division['id_division']; ?>, '<?= htmlspecialchars($division['nombre']); ?>')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmarEliminarModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas eliminar la división "<span id="division-nombre"></span>"? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEliminar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminar(id, nombre) {
            document.getElementById('division-nombre').textContent = nombre;
            document.getElementById('btnEliminar').href = 'eliminar.php?id=' + id;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
            modal.show();
        }
    </script>
</body>
</html>