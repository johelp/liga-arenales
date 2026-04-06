<?php
session_start();
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Iframe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container my-5">
        <h1>Prueba de Iframe</h1>

        <form method="POST" class="mb-3">
            <div class="mb-3">
                <label for="iframe_url" class="form-label">URL del Iframe:</label>
                <input type="url" class="form-control" id="iframe_url" name="iframe_url" placeholder="Introduce la URL a mostrar en el iframe">
            </div>
            <button type="submit" class="btn btn-primary">Mostrar Iframe</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iframe_url']) && !empty($_POST['iframe_url'])) {
            $iframe_url = htmlspecialchars($_POST['iframe_url']);
            echo '<iframe src="' . $iframe_url . '" width="100%" height="600px" frameborder="0"></iframe>';
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>