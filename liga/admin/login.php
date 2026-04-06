<?php
ob_start();
session_start();
require_once '../config.php';

// Ya autenticado → redirigir
if (isset($_SESSION['admin_autenticado']) && $_SESSION['admin_autenticado'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Completá usuario y contraseña.';
    } else {

        // ── 1. Superadmin ────────────────────────────────────────────────────
        if ($usuario === SUPERADMIN_USER && $password === SUPERADMIN_PASS) {
            $_SESSION['admin_autenticado']    = true;
            $_SESSION['admin_nombre']         = 'Administrador';
            $_SESSION['es_superadmin']        = true;
            $_SESSION['torneos_permitidos']   = [];   // vacío = todos
            header('Location: index.php');
            exit();
        }

        // ── 2. Usuarios de la tabla ──────────────────────────────────────────
        try {
            $pdo  = conectarDB();
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Actualizar último acceso
                $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?")
                    ->execute([$user['id_usuario']]);

                $perms = array_filter(array_map('intval', explode(',', $user['torneos_permitidos'] ?? '')));

                $_SESSION['admin_autenticado']  = true;
                $_SESSION['admin_nombre']       = $user['nombre_usuario'];
                $_SESSION['es_superadmin']      = false;
                $_SESSION['torneos_permitidos'] = array_values($perms);
                $_SESSION['id_usuario']         = $user['id_usuario'];

                header('Location: index.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            // Si la tabla usuarios no existe aún, solo fallamos silenciosamente
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · Liga Arenales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root { --blue:#004386; --gold:#f0a500; }
    * { box-sizing:border-box; }

    body {
        background: linear-gradient(135deg, #004386 0%, #001d3d 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', system-ui, sans-serif;
        padding: 1rem;
    }

    .login-wrap {
        width: 100%;
        max-width: 380px;
    }

    /* Logo area */
    .login-logo {
        text-align: center;
        margin-bottom: 1.8rem;
    }
    .login-logo .badge-icon {
        width: 72px; height: 72px;
        background: linear-gradient(135deg, var(--gold), #c77800);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        box-shadow: 0 6px 20px rgba(240,165,0,.4);
        margin-bottom: .8rem;
    }
    .login-logo h1 {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: .5px;
        margin: 0;
        line-height: 1.3;
    }
    .login-logo p {
        color: rgba(255,255,255,.5);
        font-size: .78rem;
        margin: .2rem 0 0;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    /* Card */
    .login-card {
        background: rgba(255,255,255,.97);
        border-radius: 20px;
        padding: 2rem 1.8rem;
        box-shadow: 0 20px 60px rgba(0,0,0,.35);
    }
    .login-card h2 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 1.4rem;
        text-align: center;
    }

    /* Inputs */
    .input-group-text {
        background: #f0f4f8;
        border-color: #dde3ec;
        color: #888;
    }
    .form-control {
        border-color: #dde3ec;
        font-size: .95rem;
        padding: .6rem .85rem;
    }
    .form-control:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 .2rem rgba(0,67,134,.15);
    }
    .input-group > .form-control { border-left: 0; }
    .input-group > .input-group-text { border-right: 0; }

    /* Button */
    .btn-login {
        background: var(--blue);
        border: none;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        padding: .7rem;
        border-radius: 10px;
        width: 100%;
        letter-spacing: .3px;
        transition: background .2s, transform .1s;
    }
    .btn-login:hover  { background: #003370; }
    .btn-login:active { transform: scale(.98); }

    /* Error */
    .alert-login {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        border-radius: 10px;
        padding: .65rem .9rem;
        font-size: .88rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1rem;
    }

    /* Password toggle */
    .toggle-pass {
        cursor: pointer;
        background: #f0f4f8;
        border: 1px solid #dde3ec;
        border-left: 0;
        padding: 0 .75rem;
        color: #888;
        border-radius: 0 8px 8px 0;
        display: flex;
        align-items: center;
    }
    .toggle-pass:hover { color: var(--blue); }
    </style>
</head>
<body>

<div class="login-wrap">

    <!-- Logo -->
    <div class="login-logo">
        <div class="badge-icon">&#9733;</div>
        <h1>Liga Deportiva<br>de General Arenales</h1>
        <p>Panel de administración</p>
    </div>

    <!-- Card -->
    <div class="login-card">
        <h2><i class="bi bi-lock-fill me-2 text-primary" style="font-size:.9rem;"></i>Iniciar sesión</h2>

        <?php if ($error): ?>
        <div class="alert-login">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="post" autocomplete="on" novalidate>
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1" for="usuario">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control" id="usuario" name="usuario"
                           value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                           autocomplete="username" required autofocus
                           placeholder="Nombre de usuario">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold mb-1" for="password">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           autocomplete="current-password" required
                           placeholder="Contraseña">
                    <button type="button" class="toggle-pass" onclick="togglePass(this)" tabindex="-1">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
            </button>
        </form>
    </div>

    <p class="text-center mt-3" style="color:rgba(255,255,255,.3); font-size:.73rem;">
        ascensiondigital.ar &middot; Liga Deportiva Arenales
    </p>
</div>

<script>
function togglePass(btn) {
    const input = document.getElementById('password');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash-fill';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-fill';
    }
}
</script>
</body>
</html>
