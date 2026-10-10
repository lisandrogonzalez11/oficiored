-- =============================================================
-- v2_fase2_c_en_revision.sql
-- Agrega estado 'en_revision': teléfono verificado pero pendiente de aprobación admin.
-- =============================================================

ALTER TABLE profesionales
  MODIFY COLUMN estado
    ENUM('pendiente','en_revision','activo','inactivo')
    NOT NULL DEFAULT 'pendiente';
