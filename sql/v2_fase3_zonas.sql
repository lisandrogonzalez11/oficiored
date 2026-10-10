-- ================================================================
-- v2_fase3_zonas.sql
-- Zonas de cobertura gestionables desde el admin.
-- Ejecutar UNA sola vez sobre la base de datos de OficioRed.
-- ================================================================

-- 1. Tabla de zonas (catálogo)
CREATE TABLE IF NOT EXISTS zonas (
    id_zona INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre  VARCHAR(80)      NOT NULL,
    orden   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    activa  TINYINT(1)       NOT NULL DEFAULT 1,
    es_todo TINYINT(1)       NOT NULL DEFAULT 0,
    PRIMARY KEY (id_zona),
    UNIQUE KEY uq_zona_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Zonas iniciales (modificables desde el panel admin)
INSERT IGNORE INTO zonas (nombre, orden, activa, es_todo) VALUES
    ('Todo El Colorado', 0, 1, 1),
    ('Centro',           1, 1, 0),
    ('Barrio Norte',     2, 1, 0),
    ('Barrio Sur',       3, 1, 0),
    ('Villa 20 de Junio',4, 1, 0),
    ('Barrio Obrero',    5, 1, 0),
    ('Barrio Libertad',  6, 1, 0),
    ('Zona Rural',       7, 1, 0);

-- 3. Tabla pivot profesional ↔ zona (muchos a muchos)
CREATE TABLE IF NOT EXISTS profesional_zonas (
    profesional_id INT UNSIGNED NOT NULL,
    zona_id        INT UNSIGNED NOT NULL,
    PRIMARY KEY (profesional_id, zona_id),
    CONSTRAINT fk_pz_profesional
        FOREIGN KEY (profesional_id) REFERENCES profesionales(id_profesional)
        ON DELETE CASCADE,
    CONSTRAINT fk_pz_zona
        FOREIGN KEY (zona_id) REFERENCES zonas(id_zona)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
