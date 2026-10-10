-- =============================================================
-- OficioRed V2 — Fase 1-B: fecha_actualizacion en profesionales
-- Aplica sobre: v1 + Fase 1-A
-- Requisito: backup completado antes de ejecutar.
-- =============================================================

USE oficiored;

-- -------------------------------------------------------
-- PRE-VERIFICACIÓN
-- -------------------------------------------------------
-- Confirmar que la columna no existe todavía:
-- SHOW COLUMNS FROM profesionales WHERE Field = 'fecha_actualizacion';
-- Esperado: Empty set (0 rows)

-- -------------------------------------------------------
-- MIGRACIÓN
-- -------------------------------------------------------

ALTER TABLE profesionales
    ADD COLUMN fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
    AFTER fecha_registro;

-- NULL DEFAULT NULL es intencional:
--   - Los registros existentes quedan con NULL (valor honesto: se desconoce
--     cuándo fue la última edición, no se inventa una fecha).
--   - NO se usa DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP porque
--     eso dispararía en cualquier UPDATE sobre la fila, incluyendo cambios
--     de estado administrativos, produciendo señales falsas de actividad.
--   - El campo se actualiza de forma explícita solamente desde editar-perfil.php
--     con SET fecha_actualizacion = NOW().
-- ADVERTENCIA: este campo NO confirma disponibilidad del profesional.
--   Solo indica cuándo actualizó sus datos por última vez.

-- -------------------------------------------------------
-- POST-VERIFICACIÓN
-- -------------------------------------------------------
-- SHOW COLUMNS FROM profesionales WHERE Field = 'fecha_actualizacion';
-- Esperado: Field=fecha_actualizacion, Type=timestamp, Null=YES, Default=NULL

-- SELECT COUNT(*) AS total_sin_fecha
-- FROM profesionales
-- WHERE fecha_actualizacion IS NULL;
-- Esperado: igual al total de filas en profesionales (todos NULL al inicio)


-- =============================================================
-- ROLLBACK
-- =============================================================
-- ALTER TABLE profesionales DROP COLUMN fecha_actualizacion;
