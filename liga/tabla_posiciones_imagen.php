<?php
// Asegúrate de que la biblioteca GD esté habilitada en tu servidor PHP

// Incluir el archivo de configuración de la base de datos
require_once 'config.php';

// Establecer el tipo de contenido a imagen PNG
header('Content-Type: image/png');

// Definir las dimensiones de la imagen
$ancho_imagen = 1080;
$alto_imagen = 1350;

// Crear la imagen en blanco
$imagen = imagecreatetruecolor($ancho_imagen, $alto_imagen);

// Asignar colores
$blanco = imagecolorallocate($imagen, 255, 255, 255);
$gris_claro = imagecolorallocate($imagen, 220, 220, 220);
$negro = imagecolorallocate($imagen, 0, 0, 0);
$azul = imagecolorallocate($imagen, 70, 130, 180); // Un tono de azul para encabezados

// Rellenar el fondo de blanco
imagefill($imagen, 0, 0, $blanco);

// --- Obtener los parámetros del torneo y la división de la URL ---
$torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);

// --- Verificar que los parámetros sean válidos ---
if (!$torneo_id || !$division_id) {
    // Mostrar un mensaje de error si los parámetros no son válidos
    $fuente_error = 5;
    $mensaje_error = "Error: Torneo o División no especificados.";
    $bbox_error = imagestring($imagen, $fuente_error, 0, 0, $mensaje_error, $negro);
    $x_error = ($ancho_imagen - $bbox_error[2]) / 2;
    $y_error = $alto_imagen / 2;
    imagestring($imagen, $fuente_error, $x_error, $y_error, $mensaje_error, $negro);
    imagepng($imagen);
    imagedestroy($imagen);
    exit();
}

// --- Conectar a la base de datos ---
$pdo = conectarDB();

// --- Obtener el nombre del torneo y la división ---
try {
    $stmt_info = $pdo->prepare("SELECT t.nombre AS torneo_nombre, d.nombre AS division_nombre
                                 FROM torneos t
                                 JOIN divisiones d ON t.id_torneo = :torneo_id AND d.id_division = :division_id");
    $stmt_info->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt_info->bindParam(':division_id', $division_id, PDO::PARAM_INT);
    $stmt_info->execute();
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar el error de la base de datos
    $fuente_error = 5;
    $mensaje_error = "Error al obtener información del torneo/división.";
    $bbox_error = imagestring($imagen, $fuente_error, 0, 0, $mensaje_error, $negro);
    $x_error = ($ancho_imagen - $bbox_error[2]) / 2;
    $y_error = $alto_imagen / 2;
    imagestring($imagen, $fuente_error, $x_error, $y_error, $mensaje_error, $negro);
    imagepng($imagen);
    imagedestroy($imagen);
    exit();
}

if (!$info) {
    $fuente_error = 5;
    $mensaje_error = "No se encontró información para el torneo y división especificados.";
    $bbox_error = imagestring($imagen, $fuente_error, 0, 0, $mensaje_error, $negro);
    $x_error = ($ancho_imagen - $bbox_error[2]) / 2;
    $y_error = $alto_imagen / 2;
    imagestring($imagen, $fuente_error, $x_error, $y_error, $mensaje_error, $negro);
    imagepng($imagen);
    imagedestroy($imagen);
    exit();
}

$torneo_nombre = $info['torneo_nombre'];
$division_nombre = $info['division_nombre'];

// --- Obtener los datos de la tabla de posiciones desde la base de datos ---
try {
    $stmt_tabla = $pdo->prepare("SELECT
                                    c.nombre_corto AS club,
                                    c.escudo_url,
                                    COUNT(p.id_partido) AS PJ,
                                    SUM(CASE WHEN (p.id_club_local = c.id_club AND p.goles_local > p.goles_visitante) OR (p.id_club_visitante = c.id_club AND p.goles_visitante > p.goles_local) THEN 1 ELSE 0 END) AS PG,
                                    SUM(CASE WHEN p.goles_local = p.goles_visitante THEN 1 ELSE 0 END) AS PE,
                                    SUM(CASE WHEN (p.id_club_local = c.id_club AND p.goles_local < p.goles_visitante) OR (p.id_club_visitante = c.id_club AND p.goles_visitante < p.goles_local) THEN 1 ELSE 0 END) AS PP,
                                    SUM(CASE WHEN p.id_club_local = c.id_club THEN p.goles_local ELSE p.goles_visitante END) AS GF,
                                    SUM(CASE WHEN p.id_club_local = c.id_club THEN p.goles_visitante ELSE p.goles_local END) AS GC,
                                    SUM(CASE WHEN (p.id_club_local = c.id_club AND p.goles_local > p.goles_visitante) OR (p.id_club_visitante = c.id_club AND p.goles_visitante > p.goles_local) THEN 3 WHEN p.goles_local = p.goles_visitante THEN 1 ELSE 0 END) AS Pts
                                FROM clubes c
                                LEFT JOIN partidos p ON (p.id_club_local = c.id_club OR p.id_club_visitante = c.id_club)
                                WHERE p.id_torneo = :torneo_id AND p.id_division = :division_id AND p.jugado = 1
                                GROUP BY c.id_club
                                ORDER BY Pts DESC, (SUM(CASE WHEN p.id_club_local = c.id_club THEN p.goles_local ELSE p.goles_visitante END) - SUM(CASE WHEN p.id_club_local = c.id_club THEN p.goles_visitante ELSE p.goles_local END)) DESC, c.nombre_corto ASC");
    $stmt_tabla->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt_tabla->bindParam(':division_id', $division_id, PDO::PARAM_INT);
    $stmt_tabla->execute();
    $tabla_data = $stmt_tabla->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar el error de la base de datos
    $fuente_error = 5;
    $mensaje_error = "Error al obtener la tabla de posiciones.";
    $bbox_error = imagestring($imagen, $fuente_error, 0, 0, $mensaje_error, $negro);
    $x_error = ($ancho_imagen - $bbox_error[2]) / 2;
    $y_error = $alto_imagen / 2;
    imagestring($imagen, $fuente_error, $x_error, $y_error, $mensaje_error, $negro);
    imagepng($imagen);
    imagedestroy($imagen);
    exit();
}

// --- Dibujar el encabezado ---
$fuente_titulo = 5; // Tamaño de fuente predeterminado
$titulo = "Tabla de Posiciones";
$subtitulo = htmlspecialchars($torneo_nombre) . " - " . htmlspecialchars($division_nombre);
$bbox_titulo = imagestring($imagen, $fuente_titulo, 0, 0, $titulo, $azul);
$x_titulo = ($ancho_imagen - $bbox_titulo[2]) / 2;
$y_titulo = 50;
imagestring($imagen, $fuente_titulo, $x_titulo, $y_titulo, $titulo, $azul);

$fuente_subtitulo = 3;
$bbox_subtitulo = imagestring($imagen, $fuente_subtitulo, 0, 0, $subtitulo, $negro);
$x_subtitulo = ($ancho_imagen - $bbox_subtitulo[2]) / 2;
$y_subtitulo = $y_titulo + imagefontheight($fuente_titulo) + 10;
imagestring($imagen, $fuente_subtitulo, $x_subtitulo, $y_subtitulo, $subtitulo, $negro);

// --- Dibujar los encabezados de la tabla ---
$fuente_encabezado = 3;
$y_encabezado = 180;
$columnas = ['Pos', '', 'Club', 'PJ', 'PG', 'PE', 'PP', 'GF', 'GC', 'Pts'];
$num_columnas = count($columnas);
$ancho_columnas = $ancho_imagen / $num_columnas;
$padding_celda = 5;

for ($i = 0; $i < $num_columnas; $i++) {
    $x = $i * $ancho_columnas + $padding_celda;
    imagestring($imagen, $fuente_encabezado, $x, $y_encabezado, $columnas[$i], $negro);
}
imageline($imagen, 10, $y_encabezado + imagefontheight($fuente_encabezado) + $padding_celda, $ancho_imagen - 10, $y_encabezado + imagefontheight($fuente_encabezado) + $padding_celda, $gris_claro);

// --- Dibujar los datos de la tabla ---
$fuente_datos = 3;
$y_datos = $y_encabezado + imagefontheight($fuente_encabezado) + 2 * $padding_celda;
$altura_fila = imagefontheight($fuente_datos) + 2 * $padding_celda;
$posicion = 1;

foreach ($tabla_data as $fila) {
    $x = 0;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $posicion++, $negro);
    $x++;

    // Dibujar el escudo si la URL está disponible
    if ($fila['escudo_url']) {
        $ruta_escudo = $fila['escudo_url'];
        if (file_exists($ruta_escudo)) {
            $info_escudo = getimagesize($ruta_escudo);
            $escudo_ancho_orig = $info_escudo[0];
            $escudo_alto_orig = $info_escudo[1];
            $escudo_ancho_deseado = 30;
            $escudo_alto_deseado = 30;
            $x_escudo = $x * $ancho_columnas + $padding_celda;
            $y_escudo = $y_datos - $padding_celda / 2;

            $imagen_escudo = null;
            $tipo_mime = $info_escudo['mime'];
            if ($tipo_mime == 'image/png') {
                $imagen_escudo = imagecreatefrompng($ruta_escudo);
            } elseif ($tipo_mime == 'image/jpeg') {
                $imagen_escudo = imagecreatefromjpeg($ruta_escudo);
            } elseif ($tipo_mime == 'image/gif') {
                $imagen_escudo = imagecreatefromgif($ruta_escudo);
            }

            if ($imagen_escudo) {
                imagecopyresampled($imagen, $imagen_escudo, $x_escudo, $y_escudo, 0, 0, $escudo_ancho_deseado, $escudo_alto_deseado, $escudo_ancho_orig, $escudo_alto_orig);
                imagedestroy($imagen_escudo);
            }
        } else {
            // Si el escudo no se encuentra, mostrar un marcador
            imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, '-', $negro);
        }
    } else {
        // Si no hay URL del escudo, mostrar un marcador
        imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, '-', $negro);
    }
    $x++;

    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda * 2 + 30, $y_datos, $fila['club'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['PJ'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['PG'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['PE'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['PP'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['GF'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['GC'], $negro);
    $x++;
    imagestring($imagen, $fuente_datos, $x * $ancho_columnas + $padding_celda, $y_datos, $fila['Pts'], $negro);

    $y_datos += $altura_fila;
    imageline($imagen, 10, $y_datos - $padding_celda, $ancho_imagen - 10, $y_datos - $padding_celda, $gris_claro);
}

// --- Aquí iría la parte para cargar y dibujar los logos y el banner ---
// Asegúrate de que las rutas a tus imágenes sean correctas
$ruta_banner = 'ruta/a/tu/banner.png'; // Reemplaza con la ruta real
$ruta_logo1 = 'ruta/a/tu/logo1.png';   // Reemplaza con la ruta real
$ruta_logo2 = 'ruta/a/tu/logo2.png';   // Reemplaza con la ruta real

$y_logos = $alto_imagen - 150;
$x_logo1 = 50;
$x_logo2 = $ancho_imagen - 200;
$ancho_logo = 150;
$alto_logo = 100;

if (file_exists($ruta_banner)) {
    $imagen_banner = imagecreatefrompng($ruta_banner);
    if ($imagen_banner) {
        $ancho_banner_orig = imagesx($imagen_banner);
        $alto_banner_orig = imagesy($imagen_banner);
        $alto_banner_deseado = 200;
        $ancho_banner_deseado = $ancho_imagen;
        imagecopyresampled($imagen, $imagen_banner, 0, 0, 0, 0, $ancho_banner_deseado, $alto_banner_deseado, $ancho_banner_orig, $alto_banner_orig);
        imagedestroy($imagen_banner);
        $y_encabezado = 180 + $alto_banner_deseado;
        $y_titulo = 50 + $alto_banner_deseado;
    }
}

if (file_exists($ruta_logo1)) {
    $imagen_logo1 = imagecreatefrompng($ruta_logo1);
    if ($imagen_logo1) {
        $ancho_logo_orig = imagesx($imagen_logo1);
        $alto_logo_orig = imagesy($imagen_logo1);
        imagecopyresampled($imagen, $imagen_logo1, $x_logo1, $y_logos, 0, 0, $ancho_logo, $alto_logo, $ancho_logo_orig, $alto_logo_orig);
        imagedestroy($imagen_logo1);
    }
}

if (file_exists($ruta_logo2)) {
    $imagen_logo2 = imagecreatefrompng($ruta_logo2);
    if ($imagen_logo2) {
        $ancho_logo_orig = imagesx($imagen_logo2);
        $alto_logo_orig = imagesy($imagen_logo2);
        imagecopyresampled($imagen, $imagen_logo2, $x_logo2, $y_logos, 0, 0, $ancho_logo, $alto_logo, $ancho_logo_orig, $alto_logo_orig);
        imagedestroy($imagen_logo2);
    }
}

// Enviar la imagen PNG al navegador
imagepng($imagen);

// Liberar la memoria
imagedestroy($imagen);
?>