-- ============================================================
-- ACTUALIZACIÓN BASE DE DATOS · Liga Deportiva Arenales
-- Ejecutar en phpMyAdmin sobre la base de datos existente
-- ============================================================

-- 0. Formato/tipo del torneo
-- NOTA: Si el campo ya existe, podés omitir esta línea.
ALTER TABLE `torneos`
  ADD COLUMN `formato` ENUM('liga','playoff','grupos_playoff') NOT NULL DEFAULT 'liga'
    COMMENT 'liga=tabla posiciones; playoff=eliminatorias; grupos_playoff=fase grupos + eliminatoria';

-- 0b. Etapas del Play Off (para partidos de fase eliminatoria)
-- El campo fase en partidos ya existe. Si no, agregarlo:
-- ALTER TABLE `partidos` ADD COLUMN `fase` VARCHAR(50) NULL DEFAULT NULL;

-- 1. Tabla de usuarios del panel de administración
-- (acceso limitado por torneo para carga de resultados)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre_usuario`     VARCHAR(50)     NOT NULL,
  `password_hash`      VARCHAR(255)    NOT NULL,
  `torneos_permitidos` VARCHAR(500)    NOT NULL DEFAULT '',
  `activo`             TINYINT(1)      NOT NULL DEFAULT 1,
  `ultimo_acceso`      DATETIME                 DEFAULT NULL,
  `creado_en`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_nombre_usuario` (`nombre_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Campo de notas/comentario por partido
-- (para registrar goles, amonestados, incidencias, etc.)
-- NOTA: Si ya ejecutaste esto antes y el campo existe, podés omitir esta línea.
ALTER TABLE `partidos`
  ADD COLUMN `comentario` TEXT NULL DEFAULT NULL COMMENT 'Notas del partido: goles, amonestados, incidencias';

-- 3. Estado "En juego" por partido
-- NOTA: Si el campo ya existe, podés omitir esta línea.
ALTER TABLE `partidos`
  ADD COLUMN `en_juego` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = partido en curso actualmente';

-- 4. Reprogramación y suspensiones
-- NOTA: Si los campos ya existen, podés omitir estas líneas.
ALTER TABLE `partidos`
  ADD COLUMN `estado` ENUM('programado','reprogramado','suspendido') NOT NULL DEFAULT 'programado'
    COMMENT 'Estado de programación del partido';
ALTER TABLE `partidos`
  ADD COLUMN `fecha_hora_original` DATETIME NULL DEFAULT NULL
    COMMENT 'Fecha/hora original antes de reprogramar';
ALTER TABLE `partidos`
  ADD COLUMN `motivo_reprogramacion` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Razón de la reprogramación o suspensión';

-- 3. Verificación: mostrar estructura resultante
-- (opcional, para confirmar que todo quedó bien)
-- DESCRIBE partidos;
-- DESCRIBE usuarios;
