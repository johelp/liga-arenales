-- ============================================================
-- ACTUALIZACIÓN BASE DE DATOS · Liga Deportiva Arenales
-- Ejecutar en phpMyAdmin sobre la base de datos existente
-- ============================================================

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

-- 3. Verificación: mostrar estructura resultante
-- (opcional, para confirmar que todo quedó bien)
-- DESCRIBE partidos;
-- DESCRIBE usuarios;
