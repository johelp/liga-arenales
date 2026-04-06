<?php
ob_start();session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();

try {
    $clubes = $pdo->query("SELECT * FROM clubes ORDER BY nombre_corto")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $clubes = [];
}

include '../header.php';
?>
<style>
.club-escudo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 50%;
    background: #f0f4f8;
    flex-shrink: 0;
}
.club-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #004386;
    color: #fff;
    font-size: .75rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.search-box { position: relative; }
.search-box .bi-search {
    position: absolute;
    left: .75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    pointer-events: none;
}
.search-box input { padding-left: 2.2rem; }
</style>

<div class="container-fluid px-3 px-md-4 py-3" style="max-width:900px; margin:0 auto;">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-fill text-primary me-1"></i> Clubes</h5>
        <a href="crear.php" class="btn btn-sm btn-primary rounded-pill">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Club
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show py-2" role="alert">
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <?php if (empty($clubes)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-shield-x" style="font-size:3rem;opacity:.2;"></i>
        <p class="mt-2">No hay clubes registrados.</p>
        <a href="crear.php" class="btn btn-sm btn-primary rounded-pill mt-1">
            <i class="bi bi-plus-circle me-1"></i> Crear primer club
        </a>
    </div>
    <?php else: ?>

    <!-- Buscador -->
    <div class="mb-3 search-box" style="max-width:340px;">
        <i class="bi bi-search"></i>
        <input type="text" id="buscar" class="form-control form-control-sm"
               placeholder="Buscar club…" autocomplete="off">
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
        <table class="table table-hover align-middle mb-0" id="tabla-clubes">
            <thead style="background:#f4f6fa;">
                <tr>
                    <th style="width:56px;" class="ps-3"></th>
                    <th>Nombre</th>
                    <th class="d-none d-md-table-cell">Nombre completo</th>
                    <th style="width:120px;" class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clubes as $club):
                    $ini = mb_strtoupper(mb_substr(trim($club['nombre_corto']),0,2));
                    $paleta = ['#004386','#1565c0','#2e7d32','#b71c1c','#6a1599','#00695c'];
                    $bg = $paleta[abs(crc32($club['nombre_corto'])) % count($paleta)];
                ?>
                <tr class="club-fila">
                    <td class="ps-3">
                        <?php if (!empty($club['escudo_url'])): ?>
                            <img src="<?= htmlspecialchars($club['escudo_url']) ?>"
                                 alt="<?= htmlspecialchars($club['nombre_corto']) ?>"
                                 class="club-escudo">
                        <?php else: ?>
                            <div class="club-avatar" style="background:<?= $bg ?>;"><?= $ini ?></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($club['nombre_corto']) ?></strong></td>
                    <td class="d-none d-md-table-cell text-muted">
                        <?= htmlspecialchars($club['nombre_completo']) ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="editar.php?id=<?= $club['id_club'] ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-2 me-1" title="Editar">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger py-0 px-2"
                                title="Eliminar"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEliminar"
                                data-id="<?= $club['id_club'] ?>"
                                data-nombre="<?= htmlspecialchars($club['nombre_corto']) ?>">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="text-muted small mt-2"><?= count($clubes) ?> club(es) registrado(s).</p>
    <?php endif; ?>
</div>

<!-- Modal eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i> Eliminar club</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                ¿Eliminar <strong id="nombreClub"></strong>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnEliminar" href="#" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash-fill me-1"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Buscador
document.getElementById('buscar')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.club-fila').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Modal eliminar
document.getElementById('modalEliminar')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('nombreClub').textContent  = btn.dataset.nombre;
    document.getElementById('btnEliminar').href = 'eliminar.php?id=' + btn.dataset.id;
});
</script>

<?php include '../footer.php'; ?>
