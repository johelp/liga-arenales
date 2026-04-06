<?php
ob_start();session_start();
require_once '../../config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

try {
    $stmt = $pdo->query("SELECT * FROM clubes ORDER BY nombre_corto");
    $clubes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al obtener clubes: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
    $clubes = [];
}

// Incluir header después de posibles redirecciones
include '../header.php';
?>
<div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-shield"></i> Gestión de Clubes</h1>
            <a href="crear.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Club
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

        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control" id="buscarClub" placeholder="Buscar club..." autocomplete="off">
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="view-toggle btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="tablaViewBtn">
                        <i class="bi bi-table"></i> Tabla
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="tarjetasViewBtn">
                        <i class="bi bi-grid-3x3-gap"></i> Tarjetas
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($clubes)): ?>
            <div class="empty-state">
                <i class="bi bi-shield-x"></i>
                <h3>No hay clubes registrados</h3>
                <p>Para comenzar, haga clic en el botón "Crear Nuevo Club"</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> Crear Nuevo Club
                </a>
            </div>
        <?php else: ?>
            <!-- Vista de Tabla -->
            <div id="tablaView" class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 70px;" class="text-center">Escudo</th>
                            <th style="width: 150px;">Nombre Corto</th>
                            <th>Nombre Completo</th>
                            <th style="width: 180px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clubes as $club): ?>
                            <tr class="club-item">
                                <td class="text-center">
                                    <?php if (!empty($club['escudo_url'])): ?>
                                        <img src="<?= htmlspecialchars($club['escudo_url']); ?>" alt="<?= htmlspecialchars($club['nombre_corto']); ?>" class="club-escudo rounded-circle">
                                    <?php else: ?>
                                        <div class="club-escudo-placeholder">
                                            <i class="bi bi-shield"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($club['nombre_corto']); ?></strong></td>
                                <td><?= htmlspecialchars($club['nombre_completo']); ?></td>
                                <td class="text-center">
                                    <a href="editar.php?id=<?= $club['id_club']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#confirmarEliminar" 
                                            data-id="<?= $club['id_club']; ?>" 
                                            data-nombre="<?= htmlspecialchars($club['nombre_corto']); ?>">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Vista de Tarjetas -->
            <div id="tarjetasView" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" style="display: none;">
                <?php foreach ($clubes as $club): ?>
                    <div class="col club-item">
                        <div class="card club-card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if (!empty($club['escudo_url'])): ?>
                                        <img src="<?= htmlspecialchars($club['escudo_url']); ?>" alt="<?= htmlspecialchars($club['nombre_corto']); ?>" class="club-escudo rounded-circle me-3">
                                    <?php else: ?>
                                        <div class="club-escudo-placeholder me-3">
                                            <i class="bi bi-shield"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($club['nombre_corto']); ?></h5>
                                        <p class="card-text text-muted small">ID: <?= $club['id_club']; ?></p>
                                    </div>
                                </div>
                                <p class="card-text mb-4"><?= htmlspecialchars($club['nombre_completo']); ?></p>
                                <div class="mt-auto d-flex justify-content-between">
                                    <a href="editar.php?id=<?= $club['id_club']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#confirmarEliminar" 
                                            data-id="<?= $club['id_club']; ?>" 
                                            data-nombre="<?= htmlspecialchars($club['nombre_corto']); ?>">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
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
                    <p>¿Está seguro que desea eliminar el club <strong id="nombreClubEliminar"></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer. Al eliminar este club, se eliminarán todas sus asociaciones con torneos y divisiones.</small></p>
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
            // Cambio de vista: tabla/tarjetas
            const tablaViewBtn = document.getElementById('tablaViewBtn');
            const tarjetasViewBtn = document.getElementById('tarjetasViewBtn');
            const tablaView = document.getElementById('tablaView');
            const tarjetasView = document.getElementById('tarjetasView');
            
            if (tablaViewBtn && tarjetasViewBtn && tablaView && tarjetasView) {
                tablaViewBtn.addEventListener('click', function() {
                    tablaView.style.display = 'block';
                    tarjetasView.style.display = 'none';
                    tablaViewBtn.classList.add('active');
                    tarjetasViewBtn.classList.remove('active');
                    // Guardar preferencia en localStorage
                    localStorage.setItem('clubesViewPreference', 'tabla');
                });
                
                tarjetasViewBtn.addEventListener('click', function() {
                    tablaView.style.display = 'none';
                    tarjetasView.style.display = 'flex';
                    tarjetasViewBtn.classList.add('active');
                    tablaViewBtn.classList.remove('active');
                    // Guardar preferencia en localStorage
                    localStorage.setItem('clubesViewPreference', 'tarjetas');
                });
                
                // Cargar preferencia guardada
                const viewPreference = localStorage.getItem('clubesViewPreference');
                if (viewPreference === 'tarjetas') {
                    tarjetasViewBtn.click();
                }
            }
            
            // Buscador de clubes
            const buscarInput = document.getElementById('buscarClub');
            const clubItems = document.querySelectorAll('.club-item');
            
            if (buscarInput && clubItems.length > 0) {
                buscarInput.addEventListener('input', function() {
                    const busqueda = this.value.toLowerCase().trim();
                    
                    clubItems.forEach(item => {
                        const textosClub = item.textContent.toLowerCase();
                        if (busqueda === '' || textosClub.includes(busqueda)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            // Configurar modal de eliminación
            const confirmarEliminarModal = document.getElementById('confirmarEliminar');
            if (confirmarEliminarModal) {
                confirmarEliminarModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const nombre = button.getAttribute('data-nombre');
                    
                    document.getElementById('nombreClubEliminar').textContent = nombre;
                    document.getElementById('btnConfirmarEliminar').href = 'eliminar.php?id=' + id;
                });
            }
        });
    </script>


<?php include '../footer.php'; ?>
