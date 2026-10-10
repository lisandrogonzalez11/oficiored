-- =============================================================
-- OficioRed V2 — Fase 1-A: categoria_id en profesionales
-- Aplica sobre: v1 (sql/oficiored_v1.sql ya ejecutado)
-- Requisito: backup completado antes de ejecutar.
-- =============================================================

USE oficiored;

-- -------------------------------------------------------
-- PRE-VERIFICACIÓN (ejecutar ANTES del ALTER/UPDATE)
-- -------------------------------------------------------

-- 1a. Confirmar tipos de las columnas referenciadas
--     Esperado: id_categoria INT(11) NO NULL, id_profesional INT(11) NO NULL
--     Ambas deben ser INT(11) signed. La columna nueva se define igual.
-- SHOW COLUMNS FROM categorias  WHERE Field = 'id_categoria';
-- SHOW COLUMNS FROM profesionales WHERE Field = 'id_profesional';

-- 1b. Previsualizar qué profesionales MAPEARÁN (join con TRIM)
-- SELECT p.id_profesional, p.nombre, p.apellido, p.oficio,
--        c.id_categoria, c.nombre AS categoria_nombre
-- FROM   profesionales p
-- INNER JOIN categorias c ON c.nombre = TRIM(p.oficio)
-- WHERE  p.estado = 'activo'
-- ORDER BY p.oficio;

-- 1c. Previsualizar qué profesionales NO MAPEARÁN
--     Si esta query devuelve filas, tratalas manualmente
--     con UPDATE profesionales SET categoria_id = X WHERE id_profesional = Y
--     antes de confirmar la fase como completada.
-- SELECT p.id_profesional, p.nombre, p.apellido, p.oficio
-- FROM   profesionales p
-- LEFT JOIN categorias c ON c.nombre = TRIM(p.oficio)
-- WHERE  c.id_categoria IS NULL
--   AND  p.estado = 'activo'
-- ORDER BY p.oficio;

-- -------------------------------------------------------
-- MIGRACIÓN
-- -------------------------------------------------------

-- Nota: utf8mb4_general_ci (collation del esquema) ES case-insensitive.
-- El TRIM() cubre espacios accidentales al inicio o final del campo oficio.

-- 2. Agregar columna nullable con mismo tipo que categorias.id_categoria
ALTER TABLE profesionales
    ADD COLUMN categoria_id INT(11) NULL DEFAULT NULL
    AFTER oficio;

-- 3. Índice previo a la FK (InnoDB lo requiere)
ALTER TABLE profesionales
    ADD INDEX idx_categoria_id (categoria_id);

-- 4. Constraint de FK
ALTER TABLE profesionales
    ADD CONSTRAINT fk_prof_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias (id_categoria)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

-- 5. Poblar desde coincidencia exacta con TRIM para espacios accidentales
--    La collation _ci garantiza case-insensitivity; no se necesita LOWER().
UPDATE profesionales p
    INNER JOIN categorias c ON c.nombre = TRIM(p.oficio)
SET p.categoria_id = c.id_categoria
WHERE p.categoria_id IS NULL;

-- -------------------------------------------------------
-- POST-VERIFICACIÓN (ejecutar después del UPDATE)
-- -------------------------------------------------------

-- Verificar profesionales activos sin categoria_id asignada:
-- SELECT id_profesional, nombre, apellido, oficio, categoria_id
-- FROM   profesionales
-- WHERE  categoria_id IS NULL AND estado = 'activo'
-- ORDER BY oficio;

-- Verificar conteo por categoría:
-- SELECT c.nombre, COUNT(p.id_profesional) AS total
-- FROM categorias c
-- LEFT JOIN profesionales p ON p.categoria_id = c.id_categoria AND p.estado = 'activo'
-- GROUP BY c.id_categoria, c.nombre
-- ORDER BY total DESC;


-- =============================================================
-- ROLLBACK
-- =============================================================
-- ALTER TABLE profesionales DROP FOREIGN KEY fk_prof_categoria;
-- ALTER TABLE profesionales DROP INDEX idx_categoria_id;
-- ALTER TABLE profesionales DROP COLUMN categoria_id;
