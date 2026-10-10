-- OficioRed v3: tabla de configuración del sitio
-- Ejecutar una sola vez en la base de datos.

CREATE TABLE IF NOT EXISTS configuracion_sitio (
  clave VARCHAR(64) NOT NULL PRIMARY KEY,
  valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracion_sitio (clave, valor) VALUES
  ('hero_tipo',              'color'),
  ('hero_imagen_url',        ''),
  ('hero_video_url',         ''),
  ('hero_overlay_opacidad',  '55'),
  ('hero_titulo',            'Encontrá el profesional que necesitás'),
  ('hero_subtitulo',         'Electricistas, plomeros, albañiles y más en El Colorado');
