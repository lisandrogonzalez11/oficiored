-- =============================================================
-- OficioRed V2 — Fase 1-C: tabla servicios
-- Aplica sobre: v1 + Fase 1-A + Fase 1-B
-- Requisito: backup completado antes de ejecutar.
-- =============================================================

USE oficiored;

-- -------------------------------------------------------
-- PRE-VERIFICACIÓN (ejecutar ANTES del CREATE)
-- -------------------------------------------------------

-- Confirmar que la tabla NO existe (si existe con estructura distinta,
-- esta migración debe fallar explícitamente, no silenciarse).
-- SHOW TABLES LIKE 'servicios';
-- Esperado: Empty set (0 rows)

-- Confirmar tipos de columnas referenciadas para compatibilidad de FK:
-- SHOW COLUMNS FROM profesionales WHERE Field = 'id_profesional';
-- Esperado: Type = int(11), Null = NO — signed INT(11)
-- profesional_id en servicios se define igual: INT(11) NOT NULL

-- -------------------------------------------------------
-- MIGRACIÓN
-- -------------------------------------------------------

-- CREATE TABLE sin IF NOT EXISTS: si la tabla ya existe con otra estructura,
-- la migración falla visible y explícitamente en lugar de aparentar éxito.

CREATE TABLE servicios (
    id             INT(11) UNSIGNED  NOT NULL AUTO_INCREMENT,
    profesional_id INT(11)           NOT NULL,
    nombre         VARCHAR(100)      NOT NULL,
    activo         TINYINT(1)        NOT NULL DEFAULT 1,
    created_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uk_prof_servicio (profesional_id, nombre),
    KEY             idx_prof_activo (profesional_id, activo),
    CONSTRAINT fk_servicios_profesional
        FOREIGN KEY (profesional_id)
        REFERENCES profesionales (id_profesional)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Notas de diseño:
--   - profesional_id: INT(11) signed, igual que profesionales.id_profesional.
--   - uk_prof_servicio: UNIQUE (profesional_id, nombre) impide duplicados por
--     profesional a nivel de BD. utf8mb4_unicode_ci hace el match
--     case-insensitive. Es una red de seguridad, NO reemplaza la validación PHP.
--   - La validación obligatoria en PHP antes de cada INSERT debe incluir:
--       1. trim() del nombre
--       2. Rechazo si nombre vacío tras trim
--       3. Rechazo si strlen(nombre) > 100
--       4. Verificación de duplicado (SELECT COUNT antes de INSERT)
--       5. Verificación de límite: máx. 15 servicios por profesional
--   - ON DELETE CASCADE: eliminar un profesional limpia sus servicios.
--   - Sin columna de precio: fuera del alcance actual de OficioRed.

-- -------------------------------------------------------
-- POST-VERIFICACIÓN
-- -------------------------------------------------------
-- SHOW TABLES LIKE 'servicios';
-- DESCRIBE servicios;
-- SELECT COUNT(*) FROM servicios;  -- Esperado: 0


-- =============================================================
-- ROLLBACK
-- =============================================================
-- DROP TABLE servicios;
