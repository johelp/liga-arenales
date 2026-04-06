<?php
ob_start();
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = conectarDB();

// Obtener torneos disponibles
$torneos = $pdo->query("SELECT id_torneo, nombre FROM torneos ORDER BY activo DESC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$mensaje = '';
$tipo_msg = '';

// ── Crear usuario ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $password = $_POST['password'] ?? '';
    $torneos_perm = array_map('intval', (array)($_POST['torneos_permitidos'] ?? []));

    if ($nombre === '' || strlen($password) < 6) {
        $mensaje = 'El nombre y la contraseña (mín. 6 caracteres) son obligatorios.';
        $tipo_msg = 'danger';
    } else {
        $existe = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?");
        $existe->execute([$nombre]);
        if ($existe->fetchColumn() > 0) {
            $mensaje  = 'Ya existe un usuario con ese nombre.';
            $tipo_msg = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO usuarios (nombre_usuario, password_hash, torneos_permitidos, activo) VALUES (?,?,?,1)")
                ->execute([$nombre, $hash, implode(',', $torneos_perm)]);
            $mensaje  = 'Usuario creado correctamente.';
            $tipo_msg = 'success';
        }
    }
}

// ── Editar usuario ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar') {
    $id_usuario   = (int)($_POST['id_usuario'] ?? 0);
    $password     = $_POST['password'] ?? '';
    $torneos_perm = array_map('intval', (array)($_POST['torneos_permitidos'] ?? []));
    $activo       = isset($_POST['activo']) ? 1 : 0;

    if ($password !== '') {
        if (strlen($password) < 6) {
            $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
            $tipo_msg = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE usuarios SET password_hash=?, torneos_permitidos=?, activo=? WHERE id_usuario=?")
                ->execute([$hash, implode(',', $torneos_perm), $activo, $id_usuario]);
        }
    } else {
        $pdo->prepare("UPDATE usuarios SET torneos_permitidos=?, activo=? WHERE id_usuario=?")
            ->execute([implode(',', $torneos_perm), $activo, $id_usuario]);
    }
    if ($tipo_msg !== 'danger') {
        $mensaje  = 'Usuario actualizado correctamente.';
        $tipo_msg = 'success';
    }
}

// ── Eliminar usuario ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $pdo->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$id_usuario]);
    $mensaje  = 'Usuario eliminado.';
    $tipo_msg = 'warning';
}

// Obtener usuarios
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre_usuario ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="container-fluid px-3 px-md-4 py-3" style="max-width:900px; margin:0 auto;">
    <div class="d-flex align-items-center gap-2 mb-4">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-1"></i> Gestión de Usuarios</h5>
        <span class="badge bg-secondary rounded-pill">Acceso por torneo</span>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_msg ?> alert-dismissible fade show py-2" role="alert">
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- ── Crear usuario ── -->
        <div class="col-lg-4">
            <div class="bg-white rounded-3 shadow-sm p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-plus-fill me-1 text-primary"></i> Nuevo Usuario</h6>
                <form method="post">
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Nombre de usuario</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" required
                               pattern="[a-zA-Z0-9_]{3,30}" title="Solo letras, números y guión bajo (3-30 caracteres)">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Contraseña <span class="text-muted fw-normal">(mín. 6 caracteres)</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Torneos con acceso</label>
                        <div class="border rounded-2 p-2" style="max-height:160px; overflow-y:auto; background:#f8f9fa;">
                            <?php if (empty($torneos)): ?>
                                <small class="text-muted">No hay torneos disponibles.</small>
                            <?php else: ?>
                                <?php foreach ($torneos as $t): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox"
                                           name="torneos_permitidos[]" value="<?= $t['id_torneo'] ?>"
                                           id="nuevo_t<?= $t['id_torneo'] ?>">
                                    <label class="form-check-label small" for="nuevo_t<?= $t['id_torneo'] ?>">
                                        <?= htmlspecialchars($t['nombre']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-1">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="todos_torneos" onchange="toggleTodos(this,'nuevo_t')">
                                <label class="form-check-label small" for="todos_torneos">Todos los torneos</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-person-plus-fill me-1"></i> Crear Usuario
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-3 shadow-sm p-3 mt-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle text-muted me-1"></i> ¿Para qué sirve?</h6>
                <p class="small text-muted mb-0">
                    Los usuarios creados aquí pueden iniciar sesión en el admin y cargar resultados.
                    Su acceso está limitado a los torneos que se les asignen. El usuario
                    <strong>admin</strong> principal tiene acceso total siempre.
                </p>
            </div>
        </div>

        <!-- ── Lista de usuarios ── -->
        <div class="col-lg-8">
            <div class="bg-white rounded-3 shadow-sm">
                <div class="p-3 border-bottom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-1 text-primary"></i> Usuarios registrados</h6>
                </div>
                <?php if (empty($usuarios)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-person-x" style="font-size:2rem; opacity:.3;"></i>
                    <p class="mt-2 mb-0 small">No hay usuarios registrados aún.</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush rounded-bottom-3">
                    <?php foreach ($usuarios as $u):
                        $perms = array_filter(explode(',', $u['torneos_permitidos'] ?? ''));
                        $perms_nombres = [];
                        foreach ($torneos as $t) {
                            if (in_array($t['id_torneo'], $perms)) $perms_nombres[] = $t['nombre'];
                        }
                    ?>
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold"><?= htmlspecialchars($u['nombre_usuario']) ?></span>
                                <?php if ($u['activo']): ?>
                                    <span class="badge bg-success-subtle text-success" style="font-size:.7rem;">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:.7rem;">Inactivo</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                        onclick="abrirEditar(<?= htmlspecialchars(json_encode($u)) ?>, <?= htmlspecialchars(json_encode($torneos)) ?>)">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form method="post" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['nombre_usuario']) ?>?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="small text-muted">
                            <?php if (empty($perms_nombres)): ?>
                                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Sin torneos asignados
                            <?php else: ?>
                                <i class="bi bi-trophy-fill text-primary me-1" style="font-size:.7rem;"></i>
                                <?= htmlspecialchars(implode(', ', $perms_nombres)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal editar usuario -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold mb-0"><i class="bi bi-pencil-fill me-1"></i> Editar usuario</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="form-editar">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_usuario" id="edit-id">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Usuario</label>
                        <input type="text" id="edit-nombre" class="form-control form-control-sm" disabled>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Nueva contraseña <span class="text-muted fw-normal">(dejar vacío para no cambiar)</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" minlength="6">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Torneos con acceso</label>
                        <div id="edit-torneos" class="border rounded-2 p-2" style="max-height:150px; overflow-y:auto; background:#f8f9fa;"></div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="edit-activo" value="1">
                        <label class="form-check-label small" for="edit-activo">Usuario activo</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTodos(cb, prefix) {
    document.querySelectorAll('[id^="' + prefix + '"]').forEach(c => c.checked = cb.checked);
}

function abrirEditar(usuario, torneos) {
    document.getElementById('edit-id').value     = usuario.id_usuario;
    document.getElementById('edit-nombre').value = usuario.nombre_usuario;
    document.getElementById('edit-activo').checked = usuario.activo == 1;

    const perms = (usuario.torneos_permitidos || '').split(',').map(Number).filter(Boolean);
    const container = document.getElementById('edit-torneos');
    container.innerHTML = torneos.map(t => `
        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="torneos_permitidos[]"
                   value="${t.id_torneo}" id="edit_t${t.id_torneo}"
                   ${perms.includes(parseInt(t.id_torneo)) ? 'checked' : ''}>
            <label class="form-check-label small" for="edit_t${t.id_torneo}">${t.nombre}</label>
        </div>
    `).join('') || '<small class="text-muted">No hay torneos.</small>';

    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php include 'footer.php'; ?>
