<?php
require_once 'config.php';

// Parámetros GET
$torneo_id = filter_input(INPUT_GET, 'torneo_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$tipo = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'all'; // all, resultados, proximos

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
    generarPaginaCompleta($pdo, $torneo_id, $division_id, $tipo);
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
function generarPaginaCompleta($pdo, $torneo_id, $division_id = null, $tipo = 'all') {
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
        <title>Generador de Imágenes - ' . htmlspecialchars($nombre_torneo) . '</title>
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
            
            .proximos-header {
                background-color: #007bff;
            }
            
            .fecha-global {
                background-color: #f8f9fa;
                color: #343a40;
                padding: 10px;
                font-size: 24px;
                font-weight: 600;
                text-align: center;
                border-bottom: 1px solid #e9ecef;
            }
            
            .match-list {
                padding: 10px;
                list-style: none;
                flex: 1;
                overflow-y: auto;
            }
            
            .match-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 10px;
                border-bottom: 1px solid #e0e0e0;
                position: relative;
            }
            
            .match-item:last-child {
                border-bottom: none;
            }
            
            .team {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 50%;
            }
            
            .team-logo {
                width: 130px;  /* Escudos aún más grandes */
   
                border-radius: 8px;
                object-fit: contain;
                margin-bottom: 5px;

                padding: 5px;
            }
            
            .score {
                background-color: #28a745;
                color: white;
                font-size: 36px;
                font-weight: 700;
                min-width: 120px;
                text-align: center;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .vs {
                background-color: #007bff;
                color: white;
                font-size: 36px;
                font-weight: 700;
                min-width: 120px;
                text-align: center;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .website-promo {
                text-align: center;
                padding: 15px;
                background-color: #f8f9fa;
                color: #343a40;
                font-size: 30px;
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
                padding: 5px;
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
                <h1>Generador de Imágenes para Redes Sociales</h1>
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
    
    // Si hay una división seleccionada, mostrar las opciones de tipo
    if ($division_id && !empty($divisiones)) {
        echo '<div class="tabs">
                <button class="tab-btn ' . ($tipo === 'all' ? 'active' : '') . '" data-tipo="all">Todos</button>
                <button class="tab-btn ' . ($tipo === 'resultados' ? 'active' : '') . '" data-tipo="resultados">Resultados</button>
                <button class="tab-btn ' . ($tipo === 'proximos' ? 'active' : '') . '" data-tipo="proximos">Próximos Partidos</button>
            </div>';
        
        // Obtener datos necesarios
        $nombre_division = obtenerNombreDivision($pdo, $division_id);
        $nombre_torneo_completo = $nombre_torneo;
        $siguiente_fecha = obtenerSiguienteFechaProximosPartidos($pdo, $torneo_id, $division_id);
        $proximos_partidos = ($siguiente_fecha) ? 
            obtenerProximosPartidosPorFechaYDivision($pdo, $torneo_id, $division_id, $siguiente_fecha) : [];
        $partidos_recientes = obtenerPartidosJugadosRecientementePorDivision($pdo, $torneo_id, $division_id, 5);
        
        // Calcular qué tarjetas ocultas necesitamos crear para generar imágenes
        $crear_resultados_stories = ($tipo === 'all' || $tipo === 'resultados') && !empty($partidos_recientes);
        $crear_resultados_feed = ($tipo === 'all' || $tipo === 'resultados') && !empty($partidos_recientes);
        $crear_proximos_stories = ($tipo === 'all' || $tipo === 'proximos') && !empty($proximos_partidos);
        $crear_proximos_feed = ($tipo === 'all' || $tipo === 'proximos') && !empty($proximos_partidos);
        
        // Crear tarjetas ocultas para generación de imágenes (sin vistas previas)
        echo '<div class="hidden-cards">';
        
        // Tarjeta oculta para resultados en formato stories
        if ($crear_resultados_stories) {
            echo '<div id="resultadosStoriesPreview" class="hidden-card">
                    <div class="social-card stories">
                        <div class="card-header">
                            <div class="card-title">' . htmlspecialchars($nombre_torneo_completo) . '</div>
                            <div class="card-subtitle">' . htmlspecialchars($nombre_division) . '</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="section">
                                <div class="section-header">ÚLTIMOS RESULTADOS</div>';
            
            // Si hay partidos, mostrar la fecha global
            if (!empty($partidos_recientes)) {
                $fecha_formateada = date('d/m/Y', strtotime($partidos_recientes[0]['fecha_hora']));
                echo '<div class="fecha-global">' . htmlspecialchars($fecha_formateada) . '</div>';
            }
            
            echo '<div class="match-list">';
                
            foreach ($partidos_recientes as $partido) {
                echo '<div class="match-item">
                        <div class="team">';
                
                // Escudo local
                if (!is_null($partido['escudo_local'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_local']) . '" alt="' . htmlspecialchars($partido['local']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                        <div class="score">' . htmlspecialchars($partido['goles_local']) . '-' . htmlspecialchars($partido['goles_visitante']) . '</div>
                        <div class="team">';
                
                // Escudo visitante
                if (!is_null($partido['escudo_visitante'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_visitante']) . '" alt="' . htmlspecialchars($partido['visitante']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                    </div>';
            }
            
            echo '</div>
                            </div>
                        </div>
                        
                        <div class="website-promo">Seguí el torneo en ascensiondigital.ar/ldga</div>
                        
                        <div class="card-footer">
                            <img src="logos.png" alt="Logo" class="footer-logo">
                        </div>
                    </div>
                </div>';
        }
        
        // Tarjeta oculta para resultados en formato feed
        if ($crear_resultados_feed) {
            echo '<div id="resultadosFeedPreview" class="hidden-card">
                    <div class="social-card feed">
                        <div class="card-header">
                            <div class="card-title">' . htmlspecialchars($nombre_torneo_completo) . '</div>
                            <div class="card-subtitle">' . htmlspecialchars($nombre_division) . '</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="section">
                                <div class="section-header">ÚLTIMOS RESULTADOS</div>';
            
            // Si hay partidos, mostrar la fecha global
            if (!empty($partidos_recientes)) {
                $fecha_formateada = date('d/m/Y', strtotime($partidos_recientes[0]['fecha_hora']));
                echo '<div class="fecha-global">' . htmlspecialchars($fecha_formateada) . '</div>';
            }
            
            echo '<div class="match-list">';
                
            foreach ($partidos_recientes as $partido) {
                echo '<div class="match-item">
                        <div class="team">';
                
                // Escudo local
                if (!is_null($partido['escudo_local'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_local']) . '" alt="' . htmlspecialchars($partido['local']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                        <div class="score">' . htmlspecialchars($partido['goles_local']) . '-' . htmlspecialchars($partido['goles_visitante']) . '</div>
                        <div class="team">';
                
                // Escudo visitante
                if (!is_null($partido['escudo_visitante'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_visitante']) . '" alt="' . htmlspecialchars($partido['visitante']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                    </div>';
            }
            
            echo '</div>
                            </div>
                        </div>
                        
                        <div class="website-promo">Seguí el torneo en ascensiondigital.ar/ldga</div>
                        
                        <div class="card-footer">
                            <img src="logos.png" alt="Logo" class="footer-logo">
                        </div>
                    </div>
                </div>';
        }
        
        // Tarjeta oculta para próximos partidos en formato stories
        if ($crear_proximos_stories) {
            echo '<div id="proximosStoriesPreview" class="hidden-card">
                    <div class="social-card stories">
                        <div class="card-header">
                            <div class="card-title">' . htmlspecialchars($nombre_torneo_completo) . '</div>
                            <div class="card-subtitle">' . htmlspecialchars($nombre_division) . '</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="section">
                                <div class="section-header proximos-header">PRÓXIMOS PARTIDOS</div>';
            
            // Si hay partidos, mostrar la fecha global
            if (!empty($proximos_partidos)) {
                $fecha_formateada = date('d/m/Y', strtotime($proximos_partidos[0]['fecha_hora']));
                echo '<div class="fecha-global">' . htmlspecialchars($fecha_formateada) . '</div>';
            }
            
            echo '<div class="match-list">';
                
            foreach ($proximos_partidos as $partido) {
                echo '<div class="match-item">
                        <div class="team">';
                
                // Escudo local
                if (!is_null($partido['escudo_local'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_local']) . '" alt="' . htmlspecialchars($partido['local']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                        <div class="vs">VS</div>
                        <div class="team">';
                
                // Escudo visitante
                if (!is_null($partido['escudo_visitante'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_visitante']) . '" alt="' . htmlspecialchars($partido['visitante']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                    </div>';
            }
            
            echo '</div>
                            </div>
                        </div>
                        
                        <div class="website-promo">Seguí el torneo en ascensiondigital.ar/ldga</div>
                        
                        <div class="card-footer">
                            <img src="logos.png" alt="Logo" class="footer-logo">
                        </div>
                    </div>
                </div>';
        }
        
        // Tarjeta oculta para próximos partidos en formato feed
        if ($crear_proximos_feed) {
            echo '<div id="proximosFeedPreview" class="hidden-card">
                    <div class="social-card feed">
                        <div class="card-header">
                            <div class="card-title">' . htmlspecialchars($nombre_torneo_completo) . '</div>
                            <div class="card-subtitle">' . htmlspecialchars($nombre_division) . '</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="section">
                                <div class="section-header proximos-header">PRÓXIMOS PARTIDOS</div>';
            
            // Si hay partidos, mostrar la fecha global
            if (!empty($proximos_partidos)) {
                $fecha_formateada = date('d/m/Y', strtotime($proximos_partidos[0]['fecha_hora']));
                echo '<div class="fecha-global">' . htmlspecialchars($fecha_formateada) . '</div>';
            }
            
            echo '<div class="match-list">';
                
            foreach ($proximos_partidos as $partido) {
                echo '<div class="match-item">
                        <div class="team">';
                
                // Escudo local
                if (!is_null($partido['escudo_local'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_local']) . '" alt="' . htmlspecialchars($partido['local']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                        <div class="vs">VS</div>
                        <div class="team">';
                
                // Escudo visitante
                if (!is_null($partido['escudo_visitante'])) {
                    echo '<img src="' . htmlspecialchars($partido['escudo_visitante']) . '" alt="' . htmlspecialchars($partido['visitante']) . '" class="team-logo">';
                } else {
                    echo '<div class="team-logo"></div>';
                }
                
                echo '</div>
                    </div>';
            }
            
            echo '</div>
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
                <p style="font-size: 18px; color: #007bff; margin-bottom: 15px; font-weight: 500;">Selecciona qué tipo de imágenes quieres generar</p>
                <p style="color: #6c757d;">Las imágenes se generarán con escudos más grandes y mostrarán la dirección web del torneo.</p>
            </div>';
        
        // Botones de acción para generar imágenes
        echo '<div class="actions-row">';
        
        if ($tipo === 'all') {
            // Si es "Todos", mostrar botón para generar todas las imágenes
            if (($crear_resultados_stories || $crear_resultados_feed) && ($crear_proximos_stories || $crear_proximos_feed)) {
                echo '<button id="btnGenerarTodos" class="btn btn-large btn-success">Generar Todas las Imágenes</button>';
            }
        } else if ($tipo === 'resultados') {
            // Si es "Resultados", mostrar botones para historias y feed
            if (!empty($partidos_recientes)) {
                echo '<button id="btnGenerarResultadosStories" class="btn btn-large">Generar Resultados para Stories</button>';
                echo '<button id="btnGenerarResultadosFeed" class="btn btn-large">Generar Resultados para Feed</button>';
            } else {
                echo '<p style="text-align: center; color: #dc3545; font-weight: 500;">No hay resultados recientes para generar imágenes.</p>';
            }
        } else if ($tipo === 'proximos') {
// Si es "Próximos Partidos", mostrar botones para historias y feed
if (!empty($proximos_partidos)) {
    echo '<button id="btnGenerarProximosStories" class="btn btn-large">Generar Próximos para Stories</button>';
    echo '<button id="btnGenerarProximosFeed" class="btn btn-large">Generar Próximos para Feed</button>';
} else {
    echo '<p style="text-align: center; color: #dc3545; font-weight: 500;">No hay próximos partidos programados para generar imágenes.</p>';
}
}

echo '</div>';

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
    <p style="font-size: 18px; color: #666;">👆 Selecciona una división para ver las opciones y generar imágenes.</p>
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

// Manejar la selección de tipo
document.querySelectorAll(".tab-btn").forEach(function(btn) {
    btn.addEventListener("click", function() {
        const tipo = this.getAttribute("data-tipo");
        window.location.href = "?torneo_id=' . $torneo_id . '&division_id=' . $division_id . '&tipo=" + tipo;
    });
});

// Botones para generar imágenes
const btnGenerarTodos = document.getElementById("btnGenerarTodos");
const btnGenerarResultadosStories = document.getElementById("btnGenerarResultadosStories");
const btnGenerarResultadosFeed = document.getElementById("btnGenerarResultadosFeed");
const btnGenerarProximosStories = document.getElementById("btnGenerarProximosStories");
const btnGenerarProximosFeed = document.getElementById("btnGenerarProximosFeed");

// Configurar los botones según estén disponibles
if (btnGenerarTodos) {
    btnGenerarTodos.addEventListener("click", function() {
        generarTodasLasImagenes();
    });
}

if (btnGenerarResultadosStories) {
    btnGenerarResultadosStories.addEventListener("click", function() {
        generarImagen("resultadosStoriesPreview", 1080, 1920, "resultados-stories");
    });
}

if (btnGenerarResultadosFeed) {
    btnGenerarResultadosFeed.addEventListener("click", function() {
        generarImagen("resultadosFeedPreview", 1080, 1350, "resultados-feed");
    });
}

if (btnGenerarProximosStories) {
    btnGenerarProximosStories.addEventListener("click", function() {
        generarImagen("proximosStoriesPreview", 1080, 1920, "proximos-stories");
    });
}

if (btnGenerarProximosFeed) {
    btnGenerarProximosFeed.addEventListener("click", function() {
        generarImagen("proximosFeedPreview", 1080, 1350, "proximos-feed");
    });
}

// Función para generar todas las imágenes
function generarTodasLasImagenes() {
    // Mostrar indicador de carga
    document.getElementById("loadingIndicator").style.display = "flex";
    
    // Lista de previsualizaciones disponibles
    const previews = [];
    
    if (document.getElementById("resultadosStoriesPreview")) {
        previews.push({id: "resultadosStoriesPreview", width: 1080, height: 1920, tipo: "resultados-stories"});
    }
    
    if (document.getElementById("resultadosFeedPreview")) {
        previews.push({id: "resultadosFeedPreview", width: 1080, height: 1350, tipo: "resultados-feed"});
    }
    
    if (document.getElementById("proximosStoriesPreview")) {
        previews.push({id: "proximosStoriesPreview", width: 1080, height: 1920, tipo: "proximos-stories"});
    }
    
    if (document.getElementById("proximosFeedPreview")) {
        previews.push({id: "proximosFeedPreview", width: 1080, height: 1350, tipo: "proximos-feed"});
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
                case "resultados-stories":
                    titleText = "Resultados - Instagram Stories (1080×1920px)";
                    break;
                case "resultados-feed":
                    titleText = "Resultados - Instagram Feed (1080×1350px)";
                    break;
                case "proximos-stories":
                    titleText = "Próximos Partidos - Instagram Stories (1080×1920px)";
                    break;
                case "proximos-feed":
                    titleText = "Próximos Partidos - Instagram Feed (1080×1350px)";
                    break;
                default:
                    titleText = "Imagen Generada";
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
            let fileName = "' . strtolower(str_replace(' ', '-', $nombre_division ?: 'division')) . '-" + resultado.tipo + "-' . date('Y-m-d') . '.png";
            
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
* Obtiene la siguiente fecha con próximos partidos para una división
*/
function obtenerSiguienteFechaProximosPartidos($pdo, $torneo_id, $division_id) {
$stmt = $pdo->prepare("SELECT DATE(MIN(fecha_hora)) AS siguiente_fecha
               FROM partidos
               WHERE id_torneo = :torneo_id
                 AND id_division = :division_id
                 AND jugado = 0
                 AND fecha_hora > NOW()");
$stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
$stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
$stmt->execute();
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
return $resultado['siguiente_fecha'];
}

/**
* Obtiene los próximos partidos para una fecha y división específicas
*/
function obtenerProximosPartidosPorFechaYDivision($pdo, $torneo_id, $division_id, $fecha) {
$stmt = $pdo->prepare("SELECT
    p.id_partido,
    p.fecha_hora,
    DATE_FORMAT(p.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_hora_formateada,
    clocal.nombre_corto AS local,
    clocal.escudo_url AS escudo_local,
    cvisitante.nombre_corto AS visitante,
    cvisitante.escudo_url AS escudo_visitante
FROM partidos p
JOIN clubes clocal ON p.id_club_local = clocal.id_club
JOIN clubes cvisitante ON p.id_club_visitante = cvisitante.id_club
WHERE p.id_torneo = :torneo_id
  AND p.id_division = :division_id
  AND p.jugado = 0
  AND DATE(p.fecha_hora) = :fecha
ORDER BY p.fecha_hora ASC
LIMIT 5");
$stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
$stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
$stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
$stmt->execute();
return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
* Obtiene los partidos jugados recientemente para una división
*/
function obtenerPartidosJugadosRecientementePorDivision($pdo, $torneo_id, $division_id, $limit = 5) {
$stmt = $pdo->prepare("SELECT
    p.id_partido,
    p.fecha_hora,
    DATE_FORMAT(p.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_hora_formateada,
    clocal.nombre_corto AS local,
    clocal.escudo_url AS escudo_local,
    p.goles_local,
    cvisitante.nombre_corto AS visitante,
    cvisitante.escudo_url AS escudo_visitante,
    p.goles_visitante
FROM partidos p
JOIN clubes clocal ON p.id_club_local = clocal.id_club
JOIN clubes cvisitante ON p.id_club_visitante = cvisitante.id_club
WHERE p.id_torneo = :torneo_id
  AND p.id_division = :division_id
  AND p.jugado = 1
ORDER BY p.fecha_hora DESC
LIMIT :limit");
$stmt->bindParam(':torneo_id', $torneo_id, PDO::PARAM_INT);
$stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>