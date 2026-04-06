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

// Obtener todos los torneos ordenados por nombre
try {
    $stmtTorneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY nombre");
    $torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al obtener torneos: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    $torneos = [];
}

// Función para obtener los clubes participantes en un torneo con sus escudos
function obtenerClubesParticipantesConEscudos(PDO $pdo, int $id_torneo): array
{
    try {
        $stmt = $pdo->prepare("SELECT c.id_club, c.nombre_completo, c.nombre_corto, c.escudo_url
                                FROM clubes c
                                JOIN clubes_en_division ced ON c.id_club = ced.id_club
                                WHERE ced.id_torneo = :id_torneo
                                GROUP BY c.id_club
                                ORDER BY c.nombre_completo");
        $stmt->bindParam(':id_torneo', $id_torneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Registrar el error pero devolver un array vacío para no interrumpir la página
        error_log('Error al obtener clubes participantes: ' . $e->getMessage());
        return [];
    }
}

// Incluir header después de todas las redirecciones potenciales
include '../header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Torneos - Liga Deportiva</title>
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
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
        .btn-outline-primary {
            color: #004386;
            border-color: #004386;
        }
        .btn-outline-primary:hover {
            background-color: #004386;
            color: white;
        }
        .btn-outline-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        .torneo-card {
            border-left: 5px solid #004386;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .club-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        .tooltip-inner {
            max-width: 200px;
            padding: 8px 10px;
            background-color: #004386;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-trophy"></i> Gestión de Torneos</h1>
            <a href="crear.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Torneo
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

        <?php if (empty($torneos)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>No hay torneos registrados</h3>
                <p>Para comenzar, haga clic en el botón "Crear Nuevo Torneo"</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($torneos as $torneo): ?>
                    <div class="col">
                        <div class="card torneo-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= htmlspecialchars($torneo['nombre']); ?></h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $clubesParticipantes = obtenerClubesParticipantesConEscudos($pdo, $torneo['id_torneo']);
                                ?>
                                <div>
                                    <h6><i class="bi bi-people-fill me-2"></i>Clubes Participantes (<?= count($clubesParticipantes); ?>)</h6>
                                    
                                    <?php if (!empty($clubesParticipantes)): ?>
                                        <div class="club-container">
                                            <?php foreach ($clubesParticipantes as $club): ?>
                                                <?php if (!empty($club['escudo_url'])): ?>
                                                    <img src="<?= htmlspecialchars($club['escudo_url']); ?>" 
                                                         alt="<?= htmlspecialchars($club['nombre_corto']); ?>" 
                                                         class="club-badge rounded-circle" 
                                                         data-bs-toggle="tooltip" 
                                                         data-bs-placement="top" 
                                                         title="<?= htmlspecialchars($club['nombre_completo']); ?>">
                                                <?php else: ?>
                                                    <span class="badge bg-secondary me-1" 
                                                          data-bs-toggle="tooltip" 
                                                          data-bs-placement="top" 
                                                          title="<?= htmlspecialchars($club['nombre_completo']); ?>">
                                                        <?= htmlspecialchars($club['nombre_corto']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted fst-italic small">No hay clubes participantes registrados</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-flex justify-content-between">
                                    <a href="ver.php?id=<?= $torneo['id_torneo']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                    <div>
                                        <a href="editar.php?id=<?= $torneo['id_torneo']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmarEliminar" 
                                                data-id="<?= $torneo['id_torneo']; ?>" 
                                                data-nombre="<?= htmlspecialchars($torneo['nombre']); ?>">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="confirmarEliminar" tabindex="-1" aria-labelledby="confirmarEliminarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmarEliminarLabel"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar el torneo <strong id="nombreTorneoEliminar"></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer y eliminará todos los datos asociados al torneo.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <a href="#" id="btnConfirmarEliminar" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Configurar modal de eliminación
            const confirmarEliminarModal = document.getElementById('confirmarEliminar');
            if (confirmarEliminarModal) {
                confirmarEliminarModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const nombre = button.getAttribute('data-nombre');
                    
                    document.getElementById('nombreTorneoEliminar').textContent = nombre;
                    document.getElementById('btnConfirmarEliminar').href = 'eliminar.php?id=' + id;
                });
            }
        });
    </script>
</body>
</html>

<?php include '../footer.php'; ?>