# Liga Deportiva de General Arenales — Sistema de Gestión

Portal web completo para la gestión de torneos de fútbol: tabla de posiciones, resultados, fixture, panel de administración y widgets embebibles.

**URL:** `https://ascensiondigital.ar/liga`
**Admin:** `https://ascensiondigital.ar/liga/admin`

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 7.4+ / 8.x |
| Base de datos | MySQL 5.7+ (PDO) |
| Frontend | Bootstrap 5.3, Bootstrap Icons 1.11 |
| Hosting | cPanel / Apache + PHP-FPM |

---

## Estructura de directorios

```
liga/
├── index.php                    # Portal público principal
├── widget.php                   # Widget embebible (iframe)
├── config.php                   # Configuración BD y constantes
├── get_divisiones.php           # AJAX: divisiones por torneo
├── tabla_posiciones.php         # Vista tabla pública
├── resultados.php               # Vista resultados pública
├── proximos_partidos.php        # Vista próximos pública
├── tabla_imagen.php             # Genera imagen de tabla (PNG)
├── resumen_imagen.php           # Genera imagen de resultados (PNG)
├── .htaccess                    # output_buffering + rewrites
├── .user.ini                    # output_buffering para PHP-FPM
├── modelo-bd.sql                # Estructura base de datos inicial
├── actualizacion-bd.sql         # Migraciones / ALTER TABLE
│
├── admin/
│   ├── index.php                # Dashboard del panel
│   ├── login.php                # Autenticación
│   ├── logout.php               # Cierre de sesión
│   ├── header.php               # Layout header + nav
│   ├── footer.php               # Layout footer
│   ├── usuarios.php             # ABM de usuarios operadores
│   ├── widgets.php              # Generador de códigos embed
│   ├── compartir.php            # Compartir resultados (imagen/texto)
│   │
│   ├── clubes/                  # CRUD de clubes
│   ├── divisiones/              # CRUD de divisiones
│   ├── torneos/                 # CRUD de torneos
│   ├── jugadores/               # CRUD de jugadores
│   └── partidos/
│       ├── index.php            # Listado + filtros + estado en vivo
│       ├── crear.php            # Nuevo partido
│       ├── editar.php           # Editar partido / resultado
│       ├── cargar_fecha.php     # Alta masiva por fecha
│       ├── cargar_resultados_fecha.php  # Carga rápida de resultados
│       ├── reprogramar_fecha.php        # Reprogramar / suspender
│       ├── copiar_partidos.php          # Copiar fixture a nueva fecha
│       └── set_estado.php              # AJAX: iniciar/detener/pendiente
│
└── css/ / img/ / cache/         # Assets estáticos y caché de imágenes
```

---

## Base de datos

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `torneos` | Torneos (liga / playoff / grupos_playoff) |
| `divisiones` | Categorías (Primera, Reserva, Femenino…) |
| `clubes` | Clubes participantes con escudo |
| `clubes_en_division` | Relación club↔división↔torneo |
| `partidos` | Fixture, resultados, estado |
| `jugadores` | Plantel por club |
| `goles` | Goles por partido y jugador |
| `tarjetas` | Amarillas y rojas |
| `usuarios` | Operadores del panel admin |

### Columnas extendidas (actualizacion-bd.sql)

```sql
-- Partidos
en_juego              TINYINT(1)     -- partido en curso
estado                ENUM('programado','reprogramado','suspendido')
fecha_hora_original   DATETIME       -- fecha antes de reprogramar
motivo_reprogramacion VARCHAR(255)
comentario            TEXT

-- Torneos
formato               ENUM('liga','playoff','grupos_playoff')
```

### Instalación

1. Crear base de datos MySQL con charset `utf8mb4`
2. Importar `modelo-bd.sql`
3. Ejecutar sentencias de `actualizacion-bd.sql` (una a una en phpMyAdmin si es MySQL 5.7)

---

## Configuración

Editar `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_bd');
define('DB_PASS', 'contraseña_bd');
define('DB_NAME', 'nombre_bd');

define('SUPERADMIN_USER', 'admin');
define('SUPERADMIN_PASS', 'tu_contraseña_segura');
```

> **Importante:** Cambiar las credenciales por defecto antes de subir a producción. Nunca exponer `config.php` en el repositorio público.

La ruta `/liga/` está hardcodeada en `admin/header.php` (`$app_root = '/liga/'`). Si se despliega en otro subdirectorio, actualizar esa variable.

---

## Despliegue

### Requisitos del servidor
- PHP 7.4+ con extensiones: `pdo_mysql`, `gd` (para generación de imágenes)
- MySQL 5.7+ o MariaDB 10.3+
- Apache con `mod_rewrite` habilitado (o Nginx equivalente)

### Pasos
1. Subir todos los archivos a `public_html/liga/` (o el subdirectorio elegido)
2. Crear BD en cPanel → MySQL Databases
3. Importar SQL en phpMyAdmin
4. Editar `config.php` con credenciales reales
5. Verificar que `.htaccess` y `.user.ini` están en la raíz del proyecto
6. Acceder a `/liga/admin/login.php` y hacer login con superadmin

### Output buffering (crítico en hosting compartido)

El proyecto incluye dos mecanismos para evitar el error `headers already sent`:
- `.htaccess` → para hosting con PHP como módulo Apache
- `.user.ini` → para hosting con PHP-FPM (cPanel moderno)

Ambos setean `output_buffering = 4096`.

---

## Funcionalidades principales

### Portal público
- Tabla de posiciones en tiempo real
- Resultados por fecha con goles y tarjetas
- Fixture completo / próximos partidos
- Filtro por torneo y división
- Responsive mobile-first con bottom tab bar

### Panel de administración
- **Dashboard:** resumen del torneo activo
- **Torneos:** crear/editar con formato (liga, playoff, grupos+playoff)
- **Partidos:** gestión completa con estado en vivo
  - Marcar "En juego" con un toque
  - Reprogramar / suspender con registro del motivo y fecha original
- **Resultados:** carga rápida por fecha con goles y tarjetas
- **Clubs / Divisiones:** ABM con escudos
- **Compartir:** genera imagen o texto para redes sociales
- **Widgets:** código embed para sitios de terceros
- **Usuarios:** operadores con acceso limitado por torneo
- UI optimizada para celular (bottom nav + offcanvas)

### Widget embebible
URL: `https://ascensiondigital.ar/liga/widget.php?tipo=tabla&torneo=1&division=1&theme=light`

Parámetros: `tipo` (tabla / resultados / proximos / fixture), `torneo`, `division`, `theme` (light/dark), `limite`, `mostrar_header`

---

## Seguridad

- Autenticación por sesión PHP (`session_start()`)
- Contraseñas hasheadas con `password_hash()` / `password_verify()`
- Consultas con PDO preparadas (sin concatenación de strings de usuario)
- `htmlspecialchars()` en toda salida HTML
- Validación de permisos por torneo para usuarios operadores

---

## Créditos

Desarrollado por **AscensionDigital.ar** para la Liga Deportiva de General Arenales.
