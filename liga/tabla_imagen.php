<?php
require_once 'config.php';

// Parámetros GET
$torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$formato = filter_input(INPUT_GET, 'formato', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'all'; // all, stories, feed

// Validación de parámetros
if (!$torneo_id) {
    mostrarError("Error: Torneo no especificado.");
    exit;
}

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    mostrarError("Error de conexión a la base de datos: " . $e->getMessage());
    exit;
}

// Generar la página según los parámetros
try {
    generarPaginaCompleta($pdo, $torneo_id, $division_id, $formato);
} catch (PDOException $e) {
    mostrarError("Error al generar la página: " . $e->getMessage());
}

/**
 * Muestra un mensaje de error con formato
 */
function mostrarError($mensaje) {
    echo "<div style='color: #dc3545; font-weight: bold; padding: 15px; background-color: #f8d7da; border-radius: 5px;'>" . htmlspecialchars($mensaje) . "</div>";
}

/**
 * Genera la página completa con selector de divisiones y opciones para generar imágenes
 */
function generarPaginaCompleta($pdo, $torneo_id, $division_id = null, $formato = 'all') {
    // Obtener nombre del torneo
    $nombre_torneo = obtenerNombreTorneo($pdo, $torneo_id);
    
    // Obtener todas las divisiones disponibles
    $divisiones = obtenerDivisionesDisponibles($pdo, $torneo_id);
    
    // Generar el encabezado HTML
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tabla de Posiciones - ' . htmlspecialchars($nombre_torneo) . '</title>
        <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                background-color: #f5f5f5;
                color: #333;
                line-height: 1.4;
            }
            
            .container {
                width: 100%;
                max-width: 1400px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .page-header {
                background-color: #007bff;
                color: white;
                padding: 20px;
                margin-bottom: 30px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .page-header h1 {
                margin: 0;
                font-size: 24px;
            }
            
            .page-header p {
                margin: 10px 0 0;
                opacity: 0.9;
            }
            
            .division-selector {
                margin-bottom: 30px;
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            
            .division-selector h2 {
                margin-top: 0;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 10px;
                color: #343a40;
                font-size: 20px;
            }
            
            .division-list {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-top: 20px;
            }
            
            .division-btn {
                padding: 12px 20px;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                font-size: 16px;
                font-weight: 500;
                color: #343a40;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .division-btn:hover {
                background-color: #007bff;
                color: white;
                border-color: #007bff;
            }
            
            .division-btn.active {
                background-color: #007bff;
                color: white;
                border-color: #007bff;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .tabs {
                display: flex;
                margin-bottom: 30px;
                background-color: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            
            .tab-btn {
                flex: 1;
                padding: 15px;
                text-align: center;
                font-size: 16px;
                font-weight: 500;
                background-color: #f8f9fa;
                border: none;
                border-bottom: 3px solid transparent;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .tab-btn:hover {
                background-color: #e9ecef;
            }
            
            .tab-btn.active {
                background-color: white;
                border-bottom-color: #007bff;
                color: #007bff;
            }
            
            /* Estilos ocultos para las tarjetas de vista previa */
            .hidden-card {
                position: absolute;
                top: -9999px;
                left: -9999px;
                width: 1px;
                height: 1px;
                overflow: hidden;
                opacity: 0;
                pointer-events: none;
            }
            
            /* Estilos para el diseño de las placas */
            .social-card {
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                background-color: white;
            }
            
            .card-header {
                background-color: #007bff;
                color: white;
                padding: 60px 20px;
                text-align: center;
            }
            
            .stories .card-header {
                padding-top: 120px; /* Padding extra para Instagram Stories */
            }
            
            .card-title {
                font-size: 40px;
                font-weight: 700;
                margin-bottom: 15px;
                line-height: 1.2;
            }
            
            .card-subtitle {
                font-size: 32px;
                font-weight: 500;
                opacity: 0.9;
            }
            
            .card-content {
                flex: 1;
                padding: 0;
                background-color: white;
                display: flex;
                flex-direction: column;
            }
            
            .section {
                padding: 0;
                margin: 0;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            .section-header {
                background-color: #28a745;
                color: white;
                padding: 20px;
                font-size: 36px;
                font-weight: 600;
                text-align: center;
                text-transform: uppercase;
            }
            
            .tabla-container {
                padding: 20px;
                flex: 1;
                overflow-y: auto;
            }
            
            .tabla-posiciones {
                width: 100%;
                border-collapse: collapse;
                font-size: 24px;
            }
            
            .tabla-posiciones th {
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                padding: 12px 10px;
                text-align: center;
                font-weight: 600;
                color: #495057;
            }
            
            .tabla-posiciones td {
                padding: 12px 10px;
                text-align: center;
                border-bottom: 1px solid #e9ecef;
            }
            
            .tabla-posiciones .team-col {
                text-align: left;
                display: flex;
                align-items: center;
                padding: 8px 5px;
            }
            
            .tabla-posiciones .pos-col {
                font-weight: 600;
                min-width: 60px;
            }
            
            .tabla-posiciones .pts-col {
                font-weight: 700;
                background-color: #f8f9fa;
            }
            
            .tabla-posiciones .team-logo {
                width: 60px;
                height: 60px;
                border-radius: 5px;
                margin-right: 15px;
                object-fit: contain;
                background-color: #f8f9fa;
                border: 1px solid #e0e0e0;
                padding: 3px;
            }
            
            .tabla-posiciones .team-name {
                font-weight: 600;
                flex: 1;
            }
            
            .tabla-posiciones tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            
            .tabla-posiciones tr:hover {
                background-color: #f1f3f5;
            }
            
            /* Zona de clasificación */
            .zona-clasificacion-1 {
                border-left: 6px solid #28a745;
            }
            
            .zona-clasificacion-2 {
                border-left: 6px solid #007bff;
            }
            
            .zona-clasificacion-3 {
                border-left: 6px solid #ffc107;
            }
            
            .zona-descenso {
                border-left: 6px solid #dc3545;
            }
            
            .website-promo {
                text-align: center;
                padding: 15px;
                background-color: #f8f9fa;
                color: #343a40;
                font-size: 24px;
                font-weight: 600;
                border-top: 1px solid #e9ecef;
            }
            
            .card-footer {
                background-color: #007bff;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 28px;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .footer-logo {
                height: 60px;
                display: block;
            }
            
            /* Botones y controles */
            .actions-row {
                display: flex;
                justify-content: center;
                gap: 15px;
                margin-top: 30px;
                flex-wrap: wrap;
            }
            
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                font-size: 16px;
                cursor: pointer;
                text-decoration: none;
                transition: background-color 0.2s;
            }
            
            .btn:hover {
                background-color: #0056b3;
            }
            
            .btn-large {
                padding: 12px 24px;
                font-size: 18px;
                font-weight: 500;
            }
            
            .btn-success {
                background-color: #28a745;
            }
            
            .btn-success:hover {
                background-color: #218838;
            }
            
            .loading-indicator {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.7);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                color: white;
                font-size: 20px;
                display: none;
            }
            
            .loading-spinner {
                border: 5px solid rgba(255,255,255,0.3);
                border-radius: 50%;
                border-top: 5px solid white;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin-bottom: 20px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .output-container {
                margin-top: 30px;
                padding: 20px;
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                text-align: center;
                display: none;
            }
            
            .output-title {
                margin-top: 0;
                font-size: 20px;
                color: #007bff;
                margin-bottom: 20px;
            }
            
            .output-images {
                display: flex;
                flex-wrap: wrap;
                gap: 30px;
                justify-content: center;
            }
            
            .output-image-container {
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 10px;
                text-align: center;
                max-width: 600px;
            }
            
            .output-image {
                max-width: 100%;
                height: auto;
                max-height: 500px;
                margin-bottom: 15px;
                border: 1px solid #eee;
            }
            
            .forma-indicador {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-right: 4px;
                color: white;
                font-weight: bold;
                font-size: 14px;
            }
            
            .forma-g {
                background-color: #28a745;
            }
            
            .forma-e {
                background-color: #ffc107;
            }
            
            .forma-p {
                background-color: #dc3545;
            }
            
            .forma-placeholder {
                background-color: #6c757d;
            }
            
            @media (max-width: 1200px) {
                .preview-box {
                    width: 100%;
                    max-width: 100%;
                }
            }
            
            @media (max-width: 768px) {
                .container {
                    padding: 10px;
                }
                
                .actions-row {
                    flex-direction: column;
                }
                
                .btn-large {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="loading-indicator" id="loadingIndicator">
            <div class="loading-spinner"></div>
            <p>Generando imágenes, por favor espere...</p>
        </div>
        
        <div class="container">
            <div class="page-header">
                <h1>Tablas de Posiciones para Redes Sociales</h1>
                <p>' . htmlspecialchars($nombre_torneo) . '</p>
            </div>
            
            <div class="division-selector">
                <h2>Selecciona una división</h2>
                <div class="division-list">';
    
    // Generar botones para cada división
    if (empty($divisiones)) {
        echo '<p>No hay divisiones disponibles para este torneo.</p>';
    } else {
        foreach ($divisiones as $div) {
            $active = ($div['id_division'] == $division_id) ? 'active' : '';
            echo '<button class="division-btn ' . $active . '" data-division-id="' . $div['id_division'] . '">' . 
                 htmlspecialchars($div['nombre']) . '</button>';
        }
    }
    
    echo '</div>
            </div>';
    
    // Si hay una división seleccionada, mostrar las opciones de formato
    if ($division_id && !empty($divisiones)) {
        echo '<div class="tabs">
                <button class="tab-btn ' . ($formato === 'all' ? 'active' : '') . '" data-formato="all">Todos los formatos</button>
                <button class="tab-btn ' . ($formato === 'stories' ? 'active' : '') . '" data-formato="stories">Stories</button>
                <button class="tab-btn ' . ($formato === 'feed' ? 'active' : '') . '" data-formato="feed">Feed</button>
            </div>';
        
        // Obtener datos necesarios
        $nombre_division = obtenerNombreDivision($pdo, $division_id);
        $nombre_torneo_completo = $nombre_torneo;
        $info = obtenerInfoTorneoDivision($pdo, $torneo_id, $division_id);
        $tabla_posiciones = generarTablaPosiciones($pdo, $torneo_id, $division_id);
        
        // Calcular qué tarjetas ocultas necesitamos crear para generar imágenes
        $crear_stories = ($formato === 'all' || $formato === 'stories') && !empty($tabla_posiciones);
        $crear_feed = ($formato === 'all' || $formato === 'feed') && !empty($tabla_posiciones);
        
        // Crear tarjetas ocultas para generación de imágenes (sin vistas previas)
        echo '<div class="hidden-cards">';
        
        // Tarjeta oculta para formato stories
        if ($crear_stories) {
            echo '<div id="storiesPreview" class="hidden-card">
                    <div class="social-card stories">
                        <div class="card-header">
                            <div class="card-title">' . htmlspecialchars($nombre_torneo_completo) . '</div>
                            <div class="card-subtitle">' . htmlspecialchars($nombre_division) . '</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="section">
                                <div class="section-header">TABLA DE POSICIONES</div>
                                <div class="tabla-container">
                                    <table class="tabla-posiciones">';
            
            // Cabecera de la tabla
            echo '<thead>
                    <tr>
                        <th>#</th>
                        <th>Club</th>
                        <th>PJ</th>
                        <th>G</th>
                        <th>E</th>
                        <th>P</th>
                        <th>GF</th>
                        <th>GC</th>
                        <th>DG</th>
                        <th>Pts</th>
                    </tr>
                  </thead>
                  <tbody>';
            
            // Contenido de la tabla
            if (!empty($tabla_posiciones)) {
                $pos = 1;
                foreach ($tabla_posiciones as $equipo) {
                    $clase_zona = '';
                    
                    // Asignación de clases para zonas según posición
                    if ($pos <= 1) {
                        $clase_zona = 'zona-clasificacion-1'; // Clasificación directa
                    } else if ($pos >= count($tabla_posiciones) && count($tabla_posiciones) > 8) {
                        $clase_zona = 'zona-descenso'; // Descenso
                    }
                    
                    echo '<tr class="' . $clase_zona . '">
                            <td class="pos-col">' . $pos++ . '</td>
                            <td class="team-col">
                                <img src="' . htmlspecialchars($equipo['escudo_url'] ?: 'img/default-shield.png') . '" alt="' . htmlspecialchars($equipo['nombre_corto']) . '" class="team-logo">
                                <span class="team-name">' . htmlspecialchars($equipo['nombre_corto']) . '</span>
                            </td>
                            <td>' . $equipo['PJ'] . '</td>
                            <td>' . $equipo['PG'] . '</td>
                            <td>' . $equipo['PE'] . '</td>
                            <td>' . $equipo['PP'] . '</td>
                            <td>' . $equipo['GF'] . '</td>
                            <td>' . $equipo['GC'] . '</td>
                            <td>' . ($equipo['DG'] > 0 ? '+' : '') . $equipo['DG'] . '</td>
                            <td class="pts-col">' . $equipo['Pts'] . '</td>
                          </tr>';
                }
            }
            
            echo '</tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <div class="website-promo">Seguí el torneo en ascensiondigital.ar/ldga</div>
            
            <div class="card-footer">
                <img src="logos.png" alt="Logo" class="footer-logo">
            </div>
          </div>
        </div>';
        }
        
        // Tarjeta oculta para formato feed
        if ($crear_feed) {
            echo '<div id="feedPreview" class="hidden-card">
                    <div class="social-card feed">
                        <div class="card-header">
                            <div class="card-title">' . htmlspecialchars($nombre_torneo_completo) . '</div>
                            <div class="card-subtitle">' . htmlspecialchars($nombre_division) . '</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="section">
                                <div class="section-header">TABLA DE POSICIONES</div>
                                <div class="tabla-container">
                                    <table class="tabla-posiciones">';
            
            // Cabecera de la tabla
            echo '<thead>
                    <tr>
                        <th>#</th>
                        <th>Club</th>
                        <th>PJ</th>
                        <th>G</th>
                        <th>E</th>
                        <th>P</th>
                        <th>GF</th>
                        <th>GC</th>
                        <th>DG</th>
                        <th>Pts</th>
                    </tr>
                  </thead>
                  <tbody>';
            
            // Contenido de la tabla
            if (!empty($tabla_posiciones)) {
                $pos = 1;
                foreach ($tabla_posiciones as $equipo) {
                    $clase_zona = '';
                    
                    // Asignación de clases para zonas según posición
                    if ($pos <= 1) {
                        $clase_zona = 'zona-clasificacion-1'; // Clasificación directa
                    } else if ($pos >= count($tabla_posiciones) && count($tabla_posiciones) > 8) {
                        $clase_zona = 'zona-descenso'; // Descenso
                    }
                    
                    echo '<tr class="' . $clase_zona . '">
                            <td class="pos-col">' . $pos++ . '</td>
                            <td class="team-col">
                                <img src="' . htmlspecialchars($equipo['escudo_url'] ?: 'img/default-shield.png') . '" alt="' . htmlspecialchars($equipo['nombre_corto']) . '" class="team-logo">
                                <span class="team-name">' . htmlspecialchars($equipo['nombre_corto']) . '</span>
                            </td>
                            <td>' . $equipo['PJ'] . '</td>
                            <td>' . $equipo['PG'] . '</td>
                            <td>' . $equipo['PE'] . '</td>
                            <td>' . $equipo['PP'] . '</td>
                            <td>' . $equipo['GF'] . '</td>
                            <td>' . $equipo['GC'] . '</td>
                            <td>' . ($equipo['DG'] > 0 ? '+' : '') . $equipo['DG'] . '</td>
                            <td class="pts-col">' . $equipo['Pts'] . '</td>
                          </tr>';
                }
            }
            
            echo '</tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <div class="website-promo">Seguí el torneo en ascensiondigital.ar/ldga</div>
            
            <div class="card-footer">
                <img src="logos.png" alt="Logo" class="footer-logo">
            </div>
          </div>
        </div>';
        }
        
        echo '</div>'; // Cierre de hidden-cards
        
        // Mensaje explicativo en lugar de previsualizaciones
        echo '<div style="text-align: center; background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <p style="font-size: 18px; color: #007bff; margin-bottom: 15px; font-weight: 500;">Selecciona el formato para generar la tabla de posiciones</p>';
        
        // Muestra información sobre la tabla si está disponible
        if (!empty($tabla_posiciones)) {
            echo '<p style="color: #28a745; font-weight: 500; margin-bottom: 10px;">Tabla de posiciones disponible con ' . count($tabla_posiciones) . ' equipos.</p>';
            
            // Información sobre estado del torneo
            echo '<p style="color: #6c757d; margin-bottom: 15px;">Partidos jugados: ' . $info['partidos_jugados'] . ' de ' . $info['total_partidos'] . '</p>';
            
            // Mostrar leyenda de colores
            echo '<div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 15px; height: 15px; background-color: #28a745; margin-right: 8px;"></div>
                        <span>Campeón</span>
                    </div>';
            
            if (count($tabla_posiciones) > 8) {
                echo '<div style="display: flex; align-items: center;">
                        <div style="width: 15px; height: 15px; background-color: #dc3545; margin-right: 8px;"></div>
                        <span>Descenso</span>
                      </div>';
            }
            
            echo '</div>';
        } else {
            echo '<p style="color: #dc3545; font-weight: 500;">No hay datos disponibles para la tabla de posiciones.</p>';
        }
        
        echo '</div>';
        
        // Botones de acción para generar imágenes
        if (!empty($tabla_posiciones)) {
            echo '<div class="actions-row">';
            
            if ($formato === 'all') {
                // Si es "Todos", mostrar botón para generar todos los formatos
                echo '<button id="btnGenerarTodos" class="btn btn-large btn-success">Generar Ambos Formatos</button>';
            } else if ($formato === 'stories') {
                // Si es "Stories", mostrar botón para generar formato stories
                echo '<button id="btnGenerarStories" class="btn btn-large">Generar para Stories (1080×1920px)</button>';
            } else if ($formato === 'feed') {
                // Si es "Feed", mostrar botón para generar formato feed
                echo '<button id="btnGenerarFeed" class="btn btn-large">Generar para Feed (1080×1350px)</button>';
            }
            
            echo '</div>';
        }
        
        // Contenedor para las imágenes generadas
        echo '<div id="outputContainer" class="output-container">
                <h2 class="output-title">Imágenes Generadas</h2>
                <div id="outputImages" class="output-images">
                    <!-- Las imágenes generadas se insertarán aquí -->
                </div>
            </div>';
    } else {
        // Si no hay una división seleccionada, mostrar mensaje de instrucción
        echo '<div class="preview-container" style="text-align: center; padding: 30px;">
                <p style="font-size: 18px; color: #666;">👆 Selecciona una división para generar la tabla de posiciones.</p>
            </div>';
    }
    
    echo '</div>
        
        <script>
            // Manejar la selección de divisiones
            document.querySelectorAll(".division-btn").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    const divisionId = this.getAttribute("data-division-id");
                    window.location.href = "?torneo_id=' . $torneo_id . '&division_id=" + divisionId;
                });
            });
            
            // Manejar la selección de formato
            document.querySelectorAll(".tab-btn").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    const formato = this.getAttribute("data-formato");
                    window.location.href = "?torneo_id=' . $torneo_id . '&division_id=' . $division_id . '&formato=" + formato;
                });
            });
            
            // Botones para generar imágenes
            const btnGenerarTodos = document.getElementById("btnGenerarTodos");
            const btnGenerarStories = document.getElementById("btnGenerarStories");
            const btnGenerarFeed = document.getElementById("btnGenerarFeed");
            
       // Configurar los botones según estén disponibles
            if (btnGenerarTodos) {
                btnGenerarTodos.addEventListener("click", function() {
                    generarTodasLasImagenes();
                });
            }
            
            if (btnGenerarStories) {
                btnGenerarStories.addEventListener("click", function() {
                    generarImagen("storiesPreview", 1080, 1920, "stories");
                });
            }
            
            if (btnGenerarFeed) {
                btnGenerarFeed.addEventListener("click", function() {
                    generarImagen("feedPreview", 1080, 1350, "feed");
                });
            }
            
            // Función para generar todas las imágenes
            function generarTodasLasImagenes() {
                // Mostrar indicador de carga
                document.getElementById("loadingIndicator").style.display = "flex";
                
                // Lista de previsualizaciones disponibles
                const previews = [];
                
                if (document.getElementById("storiesPreview")) {
                    previews.push({id: "storiesPreview", width: 1080, height: 1920, tipo: "stories"});
                }
                
                if (document.getElementById("feedPreview")) {
                    previews.push({id: "feedPreview", width: 1080, height: 1350, tipo: "feed"});
                }
                
                // Generar todas las imágenes con Promise.all
                Promise.all(previews.map(p => generarImagenPromise(p.id, p.width, p.height, p.tipo)))
                    .then(resultados => {
                        // Ocultar indicador de carga
                        document.getElementById("loadingIndicator").style.display = "none";
                        
                        // Mostrar todas las imágenes generadas
                        mostrarImagenesGeneradas(resultados);
                    })
                    .catch(error => {
                        console.error("Error al generar imágenes:", error);
                        document.getElementById("loadingIndicator").style.display = "none";
                        alert("Error al generar las imágenes. Por favor, intenta de nuevo.");
                    });
            }
            
            // Función que encapsula la generación de una imagen y devuelve una promesa
            function generarImagenPromise(elementId, width, height, tipo) {
                return new Promise((resolve, reject) => {
                    const element = document.getElementById(elementId);
                    
                    if (!element) {
                        reject(new Error("Elemento no encontrado: " + elementId));
                        return;
                    }
                    
                    // Crear una copia del elemento con dimensiones reales
                    const clone = element.cloneNode(true);
                    
                    // Quitar la clase hidden-card y aplicar estilos reales
                    clone.classList.remove("hidden-card");
                    
                    // Aplicar dimensiones correctas
                    clone.style.position = "absolute";
                    clone.style.top = "-9999px";
                    clone.style.left = "-9999px";
                    clone.style.width = width + "px";
                    clone.style.height = height + "px";
                    clone.style.opacity = "1";
                    clone.style.overflow = "visible";
                    clone.style.pointerEvents = "auto";
                    
                    document.body.appendChild(clone);
                    
                    // Asegurarse de que los elementos internos tengan dimensiones correctas
                    const socialCard = clone.querySelector(".social-card");
                    if (socialCard) {
                        socialCard.style.width = width + "px";
                        socialCard.style.height = height + "px";
                    }
                    
                    const cardContent = clone.querySelector(".card-content");
                    const cardHeader = clone.querySelector(".card-header");
                    const cardFooter = clone.querySelector(".card-footer");
                    const websitePromo = clone.querySelector(".website-promo");
                    
                    if (cardContent && cardHeader && cardFooter && websitePromo) {
                        const headerHeight = cardHeader.offsetHeight;
                        const footerHeight = cardFooter.offsetHeight;
                        const promoHeight = websitePromo.offsetHeight;
                        cardContent.style.maxHeight = (height - headerHeight - footerHeight - promoHeight) + "px";
                        cardContent.style.overflow = "hidden";
                    }
                    
                    // Usar setTimeout para asegurar que los estilos se apliquen antes de capturar
                    setTimeout(function() {
                        html2canvas(clone, {
                            width: width,
                            height: height,
                            scale: 1,
                            useCORS: true,
                            allowTaint: true,
                            backgroundColor: "#ffffff",
                            onclone: function(clonedDoc) {
                                // Ajustes adicionales al clon si es necesario
                            }
                        }).then(function(canvas) {
                            // Eliminar el clon
                            document.body.removeChild(clone);
                            
                            resolve({
                                canvas: canvas,
                                tipo: tipo
                            });
                        }).catch(function(error) {
                            // Eliminar el clon en caso de error
                            if (document.body.contains(clone)) {
                                document.body.removeChild(clone);
                            }
                            reject(error);
                        });
                    }, 300); // Aumentar el tiempo de espera para asegurar que todo se cargue
                });
            }
            
            // Función para mostrar todas las imágenes generadas
            function mostrarImagenesGeneradas(resultados) {
                const outputContainer = document.getElementById("outputContainer");
                const outputImages = document.getElementById("outputImages");
                
                // Limpiar cualquier contenido anterior
                outputImages.innerHTML = "";
                
                // Agregar cada imagen generada
                resultados.forEach(function(resultado) {
                    if (resultado.canvas) {
                        const imageContainer = document.createElement("div");
                        imageContainer.className = "output-image-container";
                        
                        // Establecer título según el tipo
                        const title = document.createElement("h3");
                        let titleText = "";
                        
                        switch(resultado.tipo) {
                            case "stories":
                                titleText = "Tabla de Posiciones - Instagram Stories (1080×1920px)";
                                break;
                            case "feed":
                                titleText = "Tabla de Posiciones - Instagram Feed (1080×1350px)";
                                break;
                            default:
                                titleText = "Tabla de Posiciones";
                        }
                        
                        title.textContent = titleText;
                        imageContainer.appendChild(title);
                        
                        // Establecer la imagen
                        const img = document.createElement("img");
                        img.src = resultado.canvas.toDataURL("image/png");
                        img.className = "output-image";
                        img.alt = titleText;
                        imageContainer.appendChild(img);
                        
                        // Agregar botón de descarga
                        const downloadBtn = document.createElement("a");
                        downloadBtn.href = resultado.canvas.toDataURL("image/png");
                        
                        // Nombre de archivo según el tipo
                        let fileName = "tabla-' . strtolower(str_replace(' ', '-', $nombre_division ?: 'division')) . '-" + resultado.tipo + "-' . date('Y-m-d') . '.png";
                        
                        downloadBtn.download = fileName;
                        downloadBtn.className = "btn";
                        downloadBtn.textContent = "Descargar";
                        imageContainer.appendChild(document.createElement("div")).appendChild(downloadBtn);
                        
                        outputImages.appendChild(imageContainer);
                    }
                });
                
                // Mostrar el contenedor de salida
                outputContainer.style.display = "block";
                
                // Desplazarse hasta las imágenes generadas
                outputContainer.scrollIntoView({ behavior: "smooth" });
            }
            
            // Función para generar una sola imagen
            function generarImagen(elementId, width, height, tipo) {
                // Mostrar indicador de carga
                document.getElementById("loadingIndicator").style.display = "flex";
                
                generarImagenPromise(elementId, width, height, tipo)
                    .then(resultado => {
                        // Ocultar indicador de carga
                        document.getElementById("loadingIndicator").style.display = "none";
                        
                        // Mostrar la imagen generada
                        mostrarImagenesGeneradas([resultado]);
                    })
                    .catch(error => {
                        console.error("Error al generar imagen:", error);
                        document.getElementById("loadingIndicator").style.display = "none";
                        alert("Error al generar la imagen. Por favor, intenta de nuevo.");
                    });
            }
        </script>
    </body>
    </html>';
}

/**
 * Obtiene todas las divisiones disponibles para un torneo
 */
function obtenerDivisionesDisponibles($pdo, $torneo_id) {
    $stmt = $pdo->prepare("SELECT DISTINCT d.id_division, d.nombre 
                           FROM divisiones d
                           JOIN partidos p ON d.id_division = p.id_division
                           WHERE p.id_torneo = :torneo_id
                           ORDER BY d.id_division");
    $stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene el nombre de la división
 */
function obtenerNombreDivision($pdo, $division_id) {
    $stmt = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id");
    $stmt->bindParam(':id', $division_id, PDO::PARAM_INT);
    $stmt->execute();
    $division = $stmt->fetch(PDO::FETCH_ASSOC);
    return $division ? $division['nombre'] : 'División ' . $division_id;
}

/**
 * Obtiene el nombre del torneo
 */
function obtenerNombreTorneo($pdo, $torneo_id) {
    $stmt = $pdo->prepare("SELECT nombre FROM torneos WHERE id_torneo = :id");
    $stmt->bindParam(':id', $torneo_id, PDO::PARAM_INT);
    $stmt->execute();
    $torneo = $stmt->fetch(PDO::FETCH_ASSOC);
    return $torneo ? $torneo['nombre'] : 'Torneo ' . $torneo_id;
}

/**
 * Obtener información del torneo y la división
 */
function obtenerInfoTorneoDivision($pdo, $torneo_id, $division_id) {
    $info = [
        'torneo_nombre' => '',
        'division_nombre' => '',
        'total_partidos' => 0,
        'partidos_jugados' => 0
    ];
    
    try {
        // Obtener nombre del torneo
        $stmt_torneo = $pdo->prepare("SELECT nombre FROM torneos WHERE id_torneo = :id_torneo");
        $stmt_torneo->bindParam(':id_torneo', $torneo_id, PDO::PARAM_INT);
        $stmt_torneo->execute();
        $torneo = $stmt_torneo->fetch(PDO::FETCH_ASSOC);
        $info['torneo_nombre'] = $torneo ? $torneo['nombre'] : 'Torneo no encontrado';
        
        // Obtener nombre de la división
        $stmt_division = $pdo->prepare("SELECT nombre FROM divisiones WHERE id_division = :id_division");
        $stmt_division->bindParam(':id_division', $division_id, PDO::PARAM_INT);
        $stmt_division->execute();
        $division = $stmt_division->fetch(PDO::FETCH_ASSOC);
        $info['division_nombre'] = $division ? $division['nombre'] : 'División no encontrada';
        
        // Obtener estadísticas de partidos
        $stmt_stats = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN jugado = 1 THEN 1 ELSE 0 END) as jugados
            FROM partidos 
            WHERE id_torneo = :id_torneo AND id_division = :id_division
        ");
        $stmt_stats->bindParam(':id_torneo', $torneo_id, PDO::PARAM_INT);
        $stmt_stats->bindParam(':id_division', $division_id, PDO::PARAM_INT);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $info['total_partidos'] = $stats['total'];
            $info['partidos_jugados'] = $stats['jugados'];
        }
    } catch (PDOException $e) {
        // Manejo de errores silencioso
    }
    
    return $info;
}

/**
 * Genera la tabla de posiciones usando el método que implementaste
 */
function generarTablaPosiciones($pdo, $torneo_id, $division_id) {
    $tabla = [];

    // Verificar primero si hay la tabla clubes_en_division
    try {
        $stmt_check = $pdo->prepare("SHOW TABLES LIKE 'clubes_en_division'");
        $stmt_check->execute();
        $table_exists = $stmt_check->rowCount() > 0;
        
        if ($table_exists) {
            // Método 1: Obtener clubes desde clubes_en_division
            $stmt_clubes = $pdo->prepare("SELECT c.id_club, c.nombre_corto, c.escudo_url
                                      FROM clubes c
                                      JOIN clubes_en_division ced ON c.id_club = ced.id_club
                                      WHERE ced.id_torneo = :id_torneo AND ced.id_division = :id_division
                                      ORDER BY c.nombre_corto");
        } else {
            // Método 2: Obtener clubes desde partidos
            $stmt_clubes = $pdo->prepare("SELECT DISTINCT c.id_club, c.nombre_corto as nombre_corto, c.escudo_url
                                      FROM clubes c
                                      JOIN partidos p ON (p.id_club_local = c.id_club OR p.id_club_visitante = c.id_club)
                                      WHERE p.id_torneo = :id_torneo AND p.id_division = :id_division
                                      ORDER BY c.nombre_corto");
        }
        
        $stmt_clubes->bindParam(':id_torneo', $torneo_id, PDO::PARAM_INT);
        $stmt_clubes->bindParam(':id_division', $division_id, PDO::PARAM_INT);
        $stmt_clubes->execute();
        $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);

        foreach ($clubes as $club) {
            $tabla[$club['id_club']] = [
                'id_club' => $club['id_club'],
                'nombre_corto' => $club['nombre_corto'],
                'escudo_url' => $club['escudo_url'],
                'PJ' => 0,
                'PG' => 0,
                'PE' => 0,
                'PP' => 0,
                'GF' => 0,
                'GC' => 0,
                'DG' => 0,
                'Pts' => 0,
                'ultimos_5' => [],
            ];
        }

        // Obtener los partidos jugados para este torneo y división
        $stmt_partidos = $pdo->prepare("SELECT p.id_club_local, p.goles_local, p.id_club_visitante, p.goles_visitante, p.fecha_hora
                                      FROM partidos p
                                      WHERE p.id_torneo = :id_torneo AND p.id_division = :id_division AND p.jugado = 1
                                      ORDER BY p.fecha_hora DESC");
        $stmt_partidos->bindParam(':id_torneo', $torneo_id, PDO::PARAM_INT);
        $stmt_partidos->bindParam(':id_division', $division_id, PDO::PARAM_INT);
        $stmt_partidos->execute();
        $partidos = $stmt_partidos->fetchAll(PDO::FETCH_ASSOC);

        // Tracking de últimos 5 resultados
        $ultimos_resultados = [];
        
        foreach ($partidos as $partido) {
            $local_id = $partido['id_club_local'];
            $visitante_id = $partido['id_club_visitante'];
            $goles_local = $partido['goles_local'];
            $goles_visitante = $partido['goles_visitante'];

            if (isset($tabla[$local_id]) && isset($tabla[$visitante_id])) {
                $tabla[$local_id]['PJ']++;
                $tabla[$visitante_id]['PJ']++;
                $tabla[$local_id]['GF'] += $goles_local;
                $tabla[$local_id]['GC'] += $goles_visitante;
                $tabla[$visitante_id]['GF'] += $goles_visitante;
                $tabla[$visitante_id]['GC'] += $goles_local;

                // Determinar resultado y actualizar estadísticas
                if ($goles_local > $goles_visitante) {
                    $tabla[$local_id]['PG']++;
                    $tabla[$local_id]['Pts'] += 3;
                    $tabla[$visitante_id]['PP']++;
                    
                    // Registrar para últimos 5
                    if (!isset($ultimos_resultados[$local_id])) {
                        $ultimos_resultados[$local_id] = [];
                    }
                    if (!isset($ultimos_resultados[$visitante_id])) {
                        $ultimos_resultados[$visitante_id] = [];
                    }
                    
                    if (count($ultimos_resultados[$local_id]) < 5) {
                        $ultimos_resultados[$local_id][] = 'G';
                    }
                    if (count($ultimos_resultados[$visitante_id]) < 5) {
                        $ultimos_resultados[$visitante_id][] = 'P';
                    }
                } elseif ($goles_local < $goles_visitante) {
                    $tabla[$visitante_id]['PG']++;
                    $tabla[$visitante_id]['Pts'] += 3;
                    $tabla[$local_id]['PP']++;
                    
                    // Registrar para últimos 5
                    if (!isset($ultimos_resultados[$local_id])) {
                        $ultimos_resultados[$local_id] = [];
                    }
                    if (!isset($ultimos_resultados[$visitante_id])) {
                        $ultimos_resultados[$visitante_id] = [];
                    }
                    
                    if (count($ultimos_resultados[$local_id]) < 5) {
                        $ultimos_resultados[$local_id][] = 'P';
                    }
                    if (count($ultimos_resultados[$visitante_id]) < 5) {
                        $ultimos_resultados[$visitante_id][] = 'G';
                    }
                } else {
                    $tabla[$local_id]['PE']++;
                    $tabla[$local_id]['Pts'] += 1;
                    $tabla[$visitante_id]['PE']++;
                    $tabla[$visitante_id]['Pts'] += 1;
                    
                    // Registrar para últimos 5
                    if (!isset($ultimos_resultados[$local_id])) {
                        $ultimos_resultados[$local_id] = [];
                    }
                    if (!isset($ultimos_resultados[$visitante_id])) {
                        $ultimos_resultados[$visitante_id] = [];
                    }
                    
                    if (count($ultimos_resultados[$local_id]) < 5) {
                        $ultimos_resultados[$local_id][] = 'E';
                    }
                    if (count($ultimos_resultados[$visitante_id]) < 5) {
                        $ultimos_resultados[$visitante_id][] = 'E';
                    }
                }
            }
        }
        
        // Agregar los últimos 5 resultados a cada equipo
        foreach ($tabla as $id_club => &$equipo) {
            $equipo['ultimos_5'] = $ultimos_resultados[$id_club] ?? [];
            // Rellenar con vacíos si hay menos de 5
            while (count($equipo['ultimos_5']) < 5) {
                array_unshift($equipo['ultimos_5'], '-');
            }
        }

        // Calcular la diferencia de gol
        foreach ($tabla as &$equipo) {
            $equipo['DG'] = $equipo['GF'] - $equipo['GC'];
        }

        // Convertir a array indexado para devolver
        $tabla_indexada = array_values($tabla);
        
        // Ordenar la tabla por Puntos (desc), Diferencia de Gol (desc), Goles a Favor (desc)
        usort($tabla_indexada, function ($a, $b) {
            if ($a['Pts'] != $b['Pts']) {
                return $b['Pts'] - $a['Pts'];
            }
            if ($a['DG'] != $b['DG']) {
                return $b['DG'] - $a['DG'];
            }
            return $b['GF'] - $a['GF'];
        });

        return $tabla_indexada;
    } catch (PDOException $e) {
        // En caso de error, devolver un array vacío
        return [];
    }
}
?>