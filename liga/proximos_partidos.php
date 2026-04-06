<?php
// Habilitar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

/**
 * Verifica si la extensión GD está habilitada
 * @return bool
 */
function verificarGD(): bool {
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        header('Content-Type: text/html');
        echo '<div style="color:red; font-family: Arial; padding: 20px;">
              <h2>Error: Extensión GD no disponible</h2>
              <p>Este script requiere la extensión GD de PHP para generar imágenes.</p>
              <p>Por favor, contacte con el administrador del servidor para habilitar esta extensión.</p>
              </div>';
        return false;
    }
    return true;
}

/**
 * Obtiene la URL del escudo de un club
 * * @param PDO $pdo Conexión a la base de datos
 * @param int $id_club ID del club
 * @return string|null URL del escudo o null si no tiene
 */
function obtenerEscudoURL(PDO $pdo, int $id_club): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT escudo_url FROM clubes WHERE id_club = :id_club");
        $stmt->bindParam(':id_club', $id_club, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['escudo_url'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener escudo: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene el ID del torneo activo
 * * @param PDO $pdo Conexión a la base de datos
 * @return int|null ID del torneo activo o null si no hay
 */
function obtenerTorneoActivo(PDO $pdo): ?int
{
    try {
        $stmt = $pdo->prepare("SELECT id_torneo FROM torneos WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['id_torneo'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener torneo activo: " . $e->getMessage());
        return null;
    }
}

/**
 * Genera una imagen con los próximos partidos para compartir en redes sociales
 * * @param PDO $pdo Conexión a la base de datos
 * @param int|null $id_division ID de la división a filtrar (opcional)
 * @param int|null $id_torneo ID del torneo a filtrar (opcional)
 * @param int $limit Número máximo de partidos a mostrar
 * @param string $formato Formato de salida de la imagen (png, jpg)
 * @param bool $debug Modo depuración para mostrar errores
 * @return void
 */
function generarImagenProximosPartidos(PDO $pdo, ?int $id_division = null, ?int $id_torneo = null, int $limit = 5, string $formato = 'png', bool $debug = false): void
{
    // Verificar si GD está disponible
    if (!verificarGD()) {
        return;
    }
    
    try {
        // Si no se proporciona un torneo, intentar obtener el torneo activo
        if ($id_torneo === null) {
            $id_torneo = obtenerTorneoActivo($pdo);
            if ($debug && $id_torneo === null) {
                echo "Advertencia: No se encontró un torneo activo.<br>";
            }
        }
        
        // Construir la consulta con condiciones opcionales
        $sql = "SELECT p.id_partido, p.fecha_hora, p.estadio, p.fase,
                       tl.id_club AS local_id, tl.nombre_corto AS local, tl.escudo_url AS escudo_local,
                       tv.id_club AS visitante_id, tv.nombre_corto AS visitante, tv.escudo_url AS escudo_visitante,
                       d.nombre AS division, d.id_division,
                       t.nombre AS torneo_nombre
                FROM partidos p
                JOIN clubes tl ON p.id_club_local = tl.id_club
                JOIN clubes tv ON p.id_club_visitante = tv.id_club
                JOIN divisiones d ON p.id_division = d.id_division
                JOIN torneos t ON p.id_torneo = t.id_torneo
                WHERE p.jugado = 0 AND p.fecha_hora > CURRENT_TIMESTAMP()";
        
        $params = [];
        
        // Aplicar filtro de torneo si está disponible
        if ($id_torneo !== null) {
            $sql .= " AND p.id_torneo = :id_torneo";
            $params[':id_torneo'] = $id_torneo;
        }
        
        // Aplicar filtro de división si está disponible
        if ($id_division !== null) {
            $sql .= " AND p.id_division = :id_division";
            $params[':id_division'] = $id_division;
        }
        
        // Ordenar y limitar resultados
        $sql .= " ORDER BY p.fecha_hora ASC LIMIT :limit";
        
        if ($debug) {
            echo "SQL: " . $sql . "<br>";
            echo "Parámetros: " . print_r($params, true) . "<br>";
        }
        
        $stmt = $pdo->prepare($sql);
        
        // Vincular parámetros
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $proximos_partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($debug) {
            echo "Partidos encontrados: " . count($proximos_partidos) . "<br>";
            if (empty($proximos_partidos)) {
                echo "No se encontraron partidos con los filtros aplicados.<br>";
            }
        }
        
        // Obtener información adicional para los títulos
        $titulo_principal = 'PRÓXIMOS PARTIDOS';
        $subtitulo = '';
        
        if ($id_torneo !== null) {
            $stmt_torneo = $pdo->prepare("SELECT nombre FROM torneos WHERE id_torneo = :id_torneo");
            $stmt_torneo->bindValue(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt_torneo->execute();
            $torneo = $stmt_torneo->fetch(PDO::FETCH_ASSOC);
            if ($torneo) {
                $subtitulo = $torneo['nombre'];
            }
        }
        
        if ($id_division !== null) {
            $stmt_division = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id_division");
            $stmt_division->bindValue(':id_division', $id_division, PDO::PARAM_INT);
            $stmt_division->execute();
            $division = $stmt_division->fetch(PDO::FETCH_ASSOC);
            if ($division) {
                $subtitulo = $subtitulo ? $subtitulo . ' - ' . $division['nombre'] : $division['nombre'];
            }
        }
        
        // Configuración de la imagen
        $ancho = 1080;  // Ancho estándar para redes sociales
        $margen = 40;   // Margen general
        $espacioPartido = 200; // Altura para cada partido
        $alturaBanner = 150;  // Altura de los banners superior e inferior
        
        // Calcular altura total basada en la cantidad de partidos
        // Mínimo 1 partido, incluso si está vacío para mostrar el mensaje
        $numPartidos = max(1, count($proximos_partidos));
        $altura = $alturaBanner * 2 + $numPartidos * $espacioPartido;
        
        // Crear lienzo de imagen
        $imagen = imagecreatetruecolor($ancho, $altura);
        if (!$imagen) {
            if ($debug) {
                echo "Error: No se pudo crear la imagen base.<br>";
            }
            throw new Exception("Error al crear la imagen base");
        }
        
        // Definir colores básicos
        $colorFondo = imagecolorallocate($imagen, 245, 245, 245);  // Fondo gris claro
        $colorBanner = imagecolorallocate($imagen, 0, 67, 134);    // Azul oscuro para los banners
        $colorTexto = imagecolorallocate($imagen, 255, 255, 255);  // Texto blanco
        $colorTextoOscuro = imagecolorallocate($imagen, 33, 33, 33); // Texto oscuro
        $colorBorde = imagecolorallocate($imagen, 220, 220, 220);  // Borde gris
        $colorHoy = imagecolorallocate($imagen, 40, 167, 69);      // Verde para partidos de hoy
        $colorManana = imagecolorallocate($imagen, 0, 123, 255);   // Azul para partidos de mañana
        $colorFechaNormal = imagecolorallocate($imagen, 108, 117, 125); // Gris para otras fechas
        $colorFaseDestacada = imagecolorallocate($imagen, 220, 53, 69); // Rojo para fases destacadas
        
        // Rellenar fondo
        imagefill($imagen, 0, 0, $colorFondo);
        
        // Banner superior
        imagefilledrectangle($imagen, 0, 0, $ancho, $alturaBanner, $colorBanner);
        
        // Cargar logo de la liga (opcional)
        $logoPath = __DIR__ . '/assets/logo_liga.png';  // Ajusta la ruta al logo
        $logo = null;
        
        if (file_exists($logoPath)) {
            $infoLogo = getimagesize($logoPath);
            if ($infoLogo) {
                $formatoLogo = $infoLogo[2];
                
                switch ($formatoLogo) {
                    case IMAGETYPE_JPEG:
                        $logo = imagecreatefromjpeg($logoPath);
                        break;
                    case IMAGETYPE_PNG:
                        $logo = imagecreatefrompng($logoPath);
                        break;
                    case IMAGETYPE_GIF:
                        $logo = imagecreatefromgif($logoPath);
                        break;
                }
                
                if ($logo) {
                    // Redimensionar y mostrar logo en el banner
                    $alturaLogo = $alturaBanner - 40;
                    $anchoLogo = ($infoLogo[0] / $infoLogo[1]) * $alturaLogo;
                    
                    imagecopyresampled(
                        $imagen, 
                        $logo, 
                        $margen, 
                        ($alturaBanner - $alturaLogo) / 2, 
                        0, 
                        0, 
                        $anchoLogo, 
                        $alturaLogo, 
                        $infoLogo[0], 
                        $infoLogo[1]
                    );
                } elseif ($debug) {
                    echo "Advertencia: Logo encontrado pero no se pudo cargar.<br>";
                }
            } elseif ($debug) {
                echo "Advertencia: No se pudo obtener información del logo.<br>";
            }
        } elseif ($debug) {
            echo "Advertencia: Logo no encontrado en: $logoPath<br>";
        }
        
        // Verificar si tenemos fuentes disponibles
        $usarFuentesPredeterminadas = true;
        $fuente = __DIR__ . '/assets/fonts/Montserrat-Bold.ttf';
        $fuenteRegular = __DIR__ . '/assets/fonts/Montserrat-Regular.ttf';
        
        if (file_exists($fuente) && file_exists($fuenteRegular) && function_exists('imagettftext')) {
            $usarFuentesPredeterminadas = false;
        } elseif ($debug) {
            echo "Advertencia: Usando fuentes predeterminadas. TTF no encontradas o función no disponible.<br>";
        }
        
        // Título en el banner superior
        if ($usarFuentesPredeterminadas) {
            // Usar fuentes integradas
            imagestring($imagen, 5, $margen + 100, $alturaBanner / 2 - 10, $titulo_principal, $colorTexto);
            if ($subtitulo) {
                imagestring($imagen, 3, $margen + 100, $alturaBanner / 2 + 10, $subtitulo, $colorTexto);
            }
        } else {
            // Usar fuentes TrueType
            imagettftext($imagen, 36, 0, $margen + 120, $alturaBanner / 2 + 10, $colorTexto, $fuente, $titulo_principal);
            if ($subtitulo) {
                imagettftext($imagen, 24, 0, $margen + 120, $alturaBanner / 2 + 45, $colorTexto, $fuenteRegular, $subtitulo);
            }
        }
        
        // Si no hay partidos, mostrar mensaje
        if (empty($proximos_partidos)) {
            $mensaje = "No hay próximos partidos programados";
            
            if ($usarFuentesPredeterminadas) {
                $anchoTexto = strlen($mensaje) * imagefontwidth(5);
                imagestring(
                    $imagen,
                    5,
                    ($ancho - $anchoTexto) / 2,
                    $alturaBanner + 50,
                    $mensaje,
                    $colorTextoOscuro
                );
            } else {
                $bbox = imagettfbbox(24, 0, $fuenteRegular, $mensaje);
                $anchoTexto = $bbox[2] - $bbox[0];
                imagettftext(
                    $imagen,
                    24,
                    0,
                    ($ancho - $anchoTexto) / 2,
                    $alturaBanner + 80,
                    $colorTextoOscuro,
                    $fuenteRegular,
                    $mensaje
                );
            }
        } else {
            // Dibujar cada partido
            $posY = $alturaBanner;
            
            foreach ($proximos_partidos as $index => $partido) {
                $fecha = new DateTime($partido['fecha_hora']);
                $hoy = new DateTime('today');
                $manana = new DateTime('tomorrow');
                
                // Determinar si es hoy, mañana u otro día
                $es_hoy = $fecha->format('Y-m-d') === $hoy->format('Y-m-d');
                $es_manana = $fecha->format('Y-m-d') === $manana->format('Y-m-d');
                
                // Fondo para separar partidos con líneas
                if ($index % 2 == 0) {
                    imagefilledrectangle($imagen, 0, $posY, $ancho, $posY + $espacioPartido, imagecolorallocate($imagen, 255, 255, 255));
                } else {
                    imagefilledrectangle($imagen, 0, $posY, $ancho, $posY + $espacioPartido, imagecolorallocate($imagen, 248, 249, 250));
                }
                
                // Determinar color de la fecha según si es hoy o mañana
                $colorFecha = $es_hoy ? $colorHoy : ($es_manana ? $colorManana : $colorFechaNormal);
                
                // Dibujar información de fecha y hora
                $fechaTexto = $es_hoy ? 'HOY' : ($es_manana ? 'MAÑANA' : $fecha->format('d/m'));
                $horaTexto = $fecha->format('H:i') . ' hs';
                
                if ($usarFuentesPredeterminadas) {
                    imagestring($imagen, 5, $margen, $posY + 25, $fechaTexto, $colorFecha);
                    imagestring($imagen, 3, $margen, $posY + 45, $horaTexto, $colorTextoOscuro);
                } else {
                    imagettftext($imagen, 18, 0, $margen, $posY + 35, $colorFecha, $fuente, $fechaTexto);
                    imagettftext($imagen, 16, 0, $margen, $posY + 60, $colorTextoOscuro, $fuenteRegular, $horaTexto);
                }
                
                // Mostrar división y fase
                $textoDerecha = '';
                $colorTextoDerecha = $colorTextoOscuro;

                if (!empty($partido['fase']) && strtolower($partido['fase']) !== 'primera fase') {
                    $textoDerecha = htmlspecialchars($partido['fase']);
                    $colorTextoDerecha = $colorFaseDestacada; // Usar color rojo para fases destacadas
                } else if ($id_division === null && !empty($partido['division'])) {
                    $textoDerecha = htmlspecialchars($partido['division']);
                }

                if (!empty($textoDerecha)) {
                    if ($usarFuentesPredeterminadas) {
                        $anchoTexto = strlen($textoDerecha) * imagefontwidth(3);
                        imagestring(
                            $imagen,
                            3,
                            $ancho - $margen - $anchoTexto,
                            $posY + 25,
                            $textoDerecha,
                            $colorTextoDerecha
                        );
                    } else {
                        $bbox = imagettfbbox(14, 0, $fuenteRegular, $textoDerecha);
                        $anchoTexto = $bbox[2] - $bbox[0];
                        imagettftext(
                            $imagen,
                            14,
                            0,
                            $ancho - $margen - $anchoTexto,
                            $posY + 30,
                            $colorTextoDerecha,
                            $fuenteRegular,
                            $textoDerecha
                        );
                    }
                }
                
                // Calcular posiciones para los equipos
                $centroY = $posY + $espacioPartido / 2 + 20;
                $centroX = $ancho / 2;
                $espacioEquipo = 140;  // Espacio para cada equipo desde el centro
                
                // Cargar y mostrar escudos
                $tamanoEscudo = 80;  // Tamaño de los escudos
                
                // Equipo Local
                $escudoLocalX = $centroX - $espacioEquipo - $tamanoEscudo/2;
                $escudoLocalY = $centroY - $tamanoEscudo/2 - 10;
                
                // Extraer ruta física de la URL almacenada en la BD
                $escudoLocalUrl = $partido['escudo_local'] ?? null;
                $escudoLocalPath = null;
                
                if (!empty($escudoLocalUrl)) {
                    // Convertir URL a ruta física
                    $escudoLocalPath = str_replace('/assets/escudos/', __DIR__ . '/assets/escudos/', $escudoLocalUrl);
                    
                    if ($debug && !file_exists($escudoLocalPath)) {
                        echo "Advertencia: Escudo local no encontrado en: $escudoLocalPath<br>";
                        echo "URL original: $escudoLocalUrl<br>";
                    }
                }
                
                if (!empty($escudoLocalPath) && file_exists($escudoLocalPath)) {
                    $infoEscudo = @getimagesize($escudoLocalPath);
                    if ($infoEscudo) {
                        $formatoEscudo = $infoEscudo[2];
                        $escudoLocal = null;
                        
                        switch ($formatoEscudo) {
                            case IMAGETYPE_JPEG:
                                $escudoLocal = imagecreatefromjpeg($escudoLocalPath);
                                break;
                            case IMAGETYPE_PNG:
                                $escudoLocal = imagecreatefrompng($escudoLocalPath);
                                break;
                            case IMAGETYPE_GIF:
                                $escudoLocal = imagecreatefromgif($escudoLocalPath);
                                break;
                        }
                        
                        if ($escudoLocal) {
                            // Preservar transparencia para PNG
                            if ($formatoEscudo == IMAGETYPE_PNG) {
                                imagealphablending($escudoLocal, true);
                                imagesavealpha($escudoLocal, true);
                            }
                            
                            imagecopyresampled(
                                $imagen,
                                $escudoLocal,
                                $escudoLocalX,
                                $escudoLocalY,
                                0,
                                0,
                                $tamanoEscudo,
                                $tamanoEscudo,
                                $infoEscudo[0],
                                $infoEscudo[1]
                            );
                            
                            imagedestroy($escudoLocal);
                        } elseif ($debug) {
                            echo "Error: No se pudo crear la imagen del escudo local.<br>";
                        }
                    } elseif ($debug) {
                        echo "Error: No se pudo obtener información del escudo local: $escudoLocalPath<br>";
                    }
                } else {
                    // Dibujar círculo como placeholder
                    imagefilledellipse($imagen, $escudoLocalX + $tamanoEscudo/2, $escudoLocalY + $tamanoEscudo/2, $tamanoEscudo, $tamanoEscudo, $colorBorde);
                }
                
                // Nombre del equipo local
                $nombreLocal = $partido['local'];
                
                if ($usarFuentesPredeterminadas) {
                    $anchoTextoLocal = strlen($nombreLocal) * imagefontwidth(3);
                    imagestring(
                        $imagen,
                        3,
                        $escudoLocalX + ($tamanoEscudo - $anchoTextoLocal) / 2,
                        $escudoLocalY + $tamanoEscudo + 10,
                        $nombreLocal,
                        $colorTextoOscuro
                    );
                } else {
                    $bbox = imagettfbbox(16, 0, $fuente, $nombreLocal);
                    $anchoTextoLocal = $bbox[2] - $bbox[0];
                    imagettftext(
                        $imagen,
                        16,
                        0,
                        $escudoLocalX + ($tamanoEscudo - $anchoTextoLocal) / 2,
                        $escudoLocalY + $tamanoEscudo + 25,
                        $colorTextoOscuro,
                        $fuente,
                        $nombreLocal
                    );
                }
                
                // VS en el centro
                if ($usarFuentesPredeterminadas) {
                    imagestring($imagen, 5, $centroX - imagefontwidth(5), $centroY - imagefontheight(5)/2, "VS", $colorTextoOscuro);
                } else {
                    $vsTexto = "VS";
                    $bbox = imagettfbbox(20, 0, $fuente, $vsTexto);
                    $anchoVS = $bbox[2] - $bbox[0];
                    $altoVS = $bbox[1] - $bbox[7];
                    // Círculo detrás del VS
                    imagefilledellipse($imagen, $centroX, $centroY, 40, 40, $colorBorde);
                    imagettftext(
                        $imagen,
                        20,
                        0,
                        $centroX - $anchoVS/2,
                        $centroY + $altoVS/2,
                        $colorTextoOscuro,
                        $fuente,
                        $vsTexto
                    );
                }
                
                // Equipo Visitante
                $escudoVisitanteX = $centroX + $espacioEquipo - $tamanoEscudo/2;
                $escudoVisitanteY = $centroY - $tamanoEscudo/2 - 10;
                
                // Extraer ruta física de la URL almacenada en la BD
                $escudoVisitanteUrl = $partido['escudo_visitante'] ?? null;
                $escudoVisitantePath = null;
                
                if (!empty($escudoVisitanteUrl)) {
                    // Convertir URL a ruta física
                    $escudoVisitantePath = str_replace('/assets/escudos/', __DIR__ . '/assets/escudos/', $escudoVisitanteUrl);
                    
                    if ($debug && !file_exists($escudoVisitantePath)) {
                        echo "Advertencia: Escudo visitante no encontrado en: $escudoVisitantePath<br>";
                        echo "URL original: $escudoVisitanteUrl<br>";
                    }
                }
                
                if (!empty($escudoVisitantePath) && file_exists($escudoVisitantePath)) {
                    $infoEscudo = @getimagesize($escudoVisitantePath);
                    if ($infoEscudo) {
                        $formatoEscudo = $infoEscudo[2];
                        $escudoVisitante = null;
                        
                        switch ($formatoEscudo) {
                            case IMAGETYPE_JPEG:
                                $escudoVisitante = imagecreatefromjpeg($escudoVisitantePath);
                                break;
                            case IMAGETYPE_PNG:
                                $escudoVisitante = imagecreatefrompng($escudoVisitantePath);
                                break;
                            case IMAGETYPE_GIF:
                                $escudoVisitante = imagecreatefromgif($escudoVisitantePath);
                                break;
                        }
                        
                        if ($escudoVisitante) {
                            // Preservar transparencia para PNG
                            if ($formatoEscudo == IMAGETYPE_PNG) {
                                imagealphablending($escudoVisitante, true);
                                imagesavealpha($escudoVisitante, true);
                            }
                            
                            imagecopyresampled(
                                $imagen,
                                $escudoVisitante,
                                $escudoVisitanteX,
                                $escudoVisitanteY,
                                0,
                                0,
                                $tamanoEscudo,
                                $tamanoEscudo,
                                $infoEscudo[0],
                                $infoEscudo[1]
                            );
                            
                            imagedestroy($escudoVisitante);
                        } elseif ($debug) {
                            echo "Error: No se pudo crear la imagen del escudo visitante.<br>";
                        }
                    } elseif ($debug) {
                        echo "Error: No se pudo obtener información del escudo visitante: $escudoVisitantePath<br>";
                    }
                } else {
                    // Dibujar círculo como placeholder
                    imagefilledellipse($imagen, $escudoVisitanteX + $tamanoEscudo/2, $escudoVisitanteY + $tamanoEscudo/2, $tamanoEscudo, $tamanoEscudo, $colorBorde);
                }
                
                // Nombre del equipo visitante
                $nombreVisitante = $partido['visitante'];
                
                if ($usarFuentesPredeterminadas) {
                    $anchoTextoVisitante = strlen($nombreVisitante) * imagefontwidth(3);
                    imagestring(
                        $imagen,
                        3,
                        $escudoVisitanteX + ($tamanoEscudo - $anchoTextoVisitante) / 2,
                        $escudoVisitanteY + $tamanoEscudo + 10,
                        $nombreVisitante,
                        $colorTextoOscuro
                    );
                } else {
                    $bbox = imagettfbbox(16, 0, $fuente, $nombreVisitante);
                    $anchoTextoVisitante = $bbox[2] - $bbox[0];
                    imagettftext(
                        $imagen,
                        16,
                        0,
                        $escudoVisitanteX + ($tamanoEscudo - $anchoTextoVisitante) / 2,
                        $escudoVisitanteY + $tamanoEscudo + 25,
                        $colorTextoOscuro,
                        $fuente,
                        $nombreVisitante
                    );
                }
                
                // Estadio
                $estadioTexto = $partido['estadio'];
                if ($usarFuentesPredeterminadas) {
                    $anchoEstadio = strlen($estadioTexto) * imagefontwidth(3);
                    imagestring(
                        $imagen,
                        3,
                        ($ancho - $anchoEstadio) / 2,
                        $centroY + $tamanoEscudo / 2 + 40,
                        $estadioTexto,
                        $colorTextoOscuro
                    );
                } else {
                    $bbox = imagettfbbox(14, 0, $fuenteRegular, $estadioTexto);
                    $anchoEstadio = $bbox[2] - $bbox[0];
                    imagettftext(
                        $imagen,
                        14,
                        0,
                        ($ancho - $anchoEstadio) / 2,
                        $centroY + $tamanoEscudo / 2 + 55,
                        $colorTextoOscuro,
                        $fuenteRegular,
                        $estadioTexto
                    );
                }
                
                $posY += $espacioPartido;
            }
        }
        
        // Banner inferior
        imagefilledrectangle($imagen, 0, $altura - $alturaBanner, $ancho, $altura, $colorBanner);
        
        // Texto inferior (ej. derechos de autor o web)
        $textoInferior = "Liga Deportiva - [TuWeb.com]"; // Reemplaza con tu información
        if ($usarFuentesPredeterminadas) {
            $anchoTextoInf = strlen($textoInferior) * imagefontwidth(3);
            imagestring(
                $imagen,
                3,
                ($ancho - $anchoTextoInf) / 2,
                $altura - $alturaBanner / 2 - 10,
                $textoInferior,
                $colorTexto
            );
        } else {
            $bbox = imagettfbbox(18, 0, $fuenteRegular, $textoInferior);
            $anchoTextoInf = $bbox[2] - $bbox[0];
            imagettftext(
                $imagen,
                18,
                0,
                ($ancho - $anchoTextoInf) / 2,
                $altura - $alturaBanner / 2 + 5,
                $colorTexto,
                $fuenteRegular,
                $textoInferior
            );
        }
        
        // Salida de la imagen
        header('Content-Type: image/' . $formato);
        switch ($formato) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($imagen);
                break;
            case 'png':
            default:
                imagepng($imagen);
                break;
        }
        
        imagedestroy($imagen);
        
    } catch (Exception $e) {
        if ($debug) {
            header('Content-Type: text/html');
            echo "Error al generar imagen: " . $e->getMessage();
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            // En producción, solo mostrar un mensaje genérico o registrar el error
            header('Content-Type: image/png');
            // Puedes generar una imagen de error simple si lo deseas
            $error_imagen = imagecreatetruecolor(400, 100);
            $bg = imagecolorallocate($error_imagen, 255, 200, 200);
            $text_color = imagecolorallocate($error_imagen, 255, 0, 0);
            imagefill($error_imagen, 0, 0, $bg);
            imagestring($error_imagen, 5, 10, 10, "Error al generar la imagen.", $text_color);
            imagestring($error_imagen, 3, 10, 40, "Consulte los logs para mas detalles.", $text_color);
            imagepng($error_imagen);
            imagedestroy($error_imagen);
        }
    }
}

// Ejecutar la función si se llama directamente al script
if (basename($_SERVER['PHP_SELF']) === 'proximos_partidos.php') {
    $pdo = conectarDB();
    $division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
    $torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 5;
    $formato = filter_input(INPUT_GET, 'formato', FILTER_SANITIZE_STRING) ?: 'png';
    $debug = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
    
    // Si se pasa debug=true, cambiamos el header para poder ver los mensajes de error.
    if ($debug) {
        header('Content-Type: text/html');
        echo "Modo Depuración Activo.<br>";
    }

    generarImagenProximosPartidos($pdo, $division_id, $torneo_id, $limit, $formato, $debug);
}

// Para usar la función desde otros scripts, simplemente incluir este archivo
// y llamar a generarImagenProximosPartidos(...) con los parámetros deseados.

/*
// Ejemplo de uso en otro archivo PHP:
require_once 'proximos_partidos.php';
$pdo = conectarDB(); // Asegúrate de tener tu función conectarDB() disponible
generarImagenProximosPartidos($pdo, 1, null, 3, 'png'); // Genera imagen para división 1, 3 partidos
*/
?>