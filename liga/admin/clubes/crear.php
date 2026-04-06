<?php
ob_start();session_start();
require_once '../../config.php';

if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pdo = conectarDB();
$errores = [];
$nombre_corto = $nombre_completo = $escudo_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_corto    = trim($_POST['nombre_corto']    ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $escudo_url      = trim($_POST['escudo_url']      ?? '');

    // ── Subida de archivo ──────────────────────────────────────────────────
    if (!empty($_FILES['escudo_file']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['escudo_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','svg'];
        $maxsize = 2 * 1024 * 1024; // 2 MB

        if (!in_array($ext, $allowed)) {
            $errores['escudo'] = 'Formato no permitido. Usá JPG, PNG, WEBP o SVG.';
        } elseif ($_FILES['escudo_file']['size'] > $maxsize) {
            $errores['escudo'] = 'El archivo supera los 2 MB.';
        } elseif ($_FILES['escudo_file']['error'] !== UPLOAD_ERR_OK) {
            $errores['escudo'] = 'Error al subir el archivo.';
        } else {
            $upload_dir = dirname(__DIR__, 2) . '/uploads/escudos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $filename = 'escudo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['escudo_file']['tmp_name'], $upload_dir . $filename)) {
                $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $escudo_url = $base . '/liga/uploads/escudos/' . $filename;
            } else {
                $errores['escudo'] = 'No se pudo guardar el archivo. Verificá permisos de la carpeta uploads/.';
            }
        }
    }

    if (empty($nombre_corto))    $errores['nombre_corto']    = 'El nombre corto es obligatorio.';
    if (empty($nombre_completo)) $errores['nombre_completo'] = 'El nombre completo es obligatorio.';

    if (empty($errores)) {
        $pdo->prepare("INSERT INTO clubes (nombre_corto, nombre_completo, escudo_url) VALUES (?,?,?)")
            ->execute([$nombre_corto, $nombre_completo, $escudo_url ?: null]);
        $_SESSION['mensaje']      = 'Club creado correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: index.php');
        exit();
    }
}

include '../header.php';
?>
<style>
.escudo-preview-wrap {
    width:80px; height:80px; border-radius:50%;
    border:2px dashed #d0d8e4;
    display:flex; align-items:center; justify-content:center;
    background:#f8f9fc; overflow:hidden; flex-shrink:0;
}
.escudo-preview-wrap img { width:100%; height:100%; object-fit:contain; }
.upload-tab .nav-link { font-size:.82rem; padding:.35rem .8rem; }
</style>

<div class="container-fluid px-3 px-md-4 py-3" style="max-width:680px; margin:0 auto;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle text-primary me-1"></i> Nuevo Club</h5>
    </div>

    <?php if (!empty($errores['general'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($errores['general']) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-3 shadow-sm p-3">
        <form method="post" enctype="multipart/form-data">

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Nombre corto <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_corto" class="form-control form-control-sm <?= !empty($errores['nombre_corto'])?'is-invalid':'' ?>"
                           value="<?= htmlspecialchars($nombre_corto) ?>" placeholder="Ej: River, Belgrano…" required>
                    <?php if (!empty($errores['nombre_corto'])): ?>
                    <div class="invalid-feedback"><?= $errores['nombre_corto'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold mb-1">Nombre completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_completo" class="form-control form-control-sm <?= !empty($errores['nombre_completo'])?'is-invalid':'' ?>"
                           value="<?= htmlspecialchars($nombre_completo) ?>" placeholder="Nombre oficial completo" required>
                    <?php if (!empty($errores['nombre_completo'])): ?>
                    <div class="invalid-feedback"><?= $errores['nombre_completo'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Escudo -->
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1">Escudo <span class="text-muted fw-normal">(opcional)</span></label>

                <div class="d-flex align-items-start gap-3">
                    <!-- Preview -->
                    <div class="escudo-preview-wrap" id="preview-wrap">
                        <i class="bi bi-shield text-muted" style="font-size:1.8rem;"></i>
                    </div>

                    <div class="flex-grow-1">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs upload-tab mb-2">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" id="tab-subir" onclick="switchTab('subir')">
                                    <i class="bi bi-upload me-1"></i>Subir archivo
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" id="tab-url" onclick="switchTab('url')">
                                    <i class="bi bi-link-45deg me-1"></i>URL externa
                                </button>
                            </li>
                        </ul>

                        <div id="panel-subir">
                            <input type="file" name="escudo_file" id="escudo_file"
                                   class="form-control form-control-sm <?= !empty($errores['escudo'])?'is-invalid':'' ?>"
                                   accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
                            <?php if (!empty($errores['escudo'])): ?>
                            <div class="invalid-feedback d-block"><?= $errores['escudo'] ?></div>
                            <?php endif; ?>
                            <small class="text-muted">JPG, PNG, WEBP o SVG. Máx. 2 MB.</small>
                        </div>

                        <div id="panel-url" style="display:none;">
                            <input type="url" name="escudo_url" id="escudo_url"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($escudo_url) ?>"
                                   placeholder="https://ejemplo.com/escudo.png">
                            <small class="text-muted">Si subís un archivo, se ignora esta URL.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Club
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('panel-subir').style.display = tab === 'subir' ? '' : 'none';
    document.getElementById('panel-url').style.display   = tab === 'url'   ? '' : 'none';
    document.getElementById('tab-subir').classList.toggle('active', tab === 'subir');
    document.getElementById('tab-url').classList.toggle('active', tab === 'url');
}

// Preview en tiempo real
document.getElementById('escudo_file').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const wrap = document.getElementById('preview-wrap');
        wrap.innerHTML = '<img src="' + e.target.result + '" alt="preview">';
    };
    reader.readAsDataURL(file);
});

document.getElementById('escudo_url').addEventListener('input', function() {
    const wrap = document.getElementById('preview-wrap');
    if (this.value.trim()) {
        wrap.innerHTML = '<img src="' + this.value + '" onerror="this.parentElement.innerHTML=\'<i class=\\\"bi bi-shield text-muted\\\" style=\\\"font-size:1.8rem;\\\"></i>\'">';
    } else {
        wrap.innerHTML = '<i class="bi bi-shield text-muted" style="font-size:1.8rem;"></i>';
    }
});
</script>

<?php include '../footer.php'; ?>
