-- v4: Tabla de solicitudes de presupuesto
-- Ejecutar en phpMyAdmin o MySQL CLI sobre la base de datos de OficioRed.

CREATE TABLE IF NOT EXISTS solicitudes_presupuesto (
    id               INT(11)       NOT NULL AUTO_INCREMENT,
    profesional_id   INT(11)       NOT NULL,
    nombre_cliente   VARCHAR(100)  NOT NULL,
    telefono_cliente VARCHAR(30)   NOT NULL,
    email_cliente    VARCHAR(150)  DEFAULT NULL,
    modalidad        ENUM('presencial','digital') NOT NULL DEFAULT 'presencial',
    zona_servicio    VARCHAR(150)  DEFAULT NULL,
    descripcion      TEXT          NOT NULL,
    leida            TINYINT(1)    NOT NULL DEFAULT 0,
    fecha            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_profesional (profesional_id),
    CONSTRAINT fk_solicitud_profesional
        FOREIGN KEY (profesional_id)
        REFERENCES profesionales (id_profesional)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Si la tabla YA EXISTE (ya ejecutaste una versión anterior),
-- correr solo estas líneas para agregar las columnas faltantes:
-- -------------------------------------------------------
-- ALTER TABLE solicitudes_presupuesto
--     MODIFY COLUMN telefono_cliente VARCHAR(30) NOT NULL,
--     MODIFY COLUMN email_cliente     VARCHAR(150) DEFAULT NULL,
--     ADD COLUMN modalidad     ENUM('presencial','digital') NOT NULL DEFAULT 'presencial' AFTER email_cliente,
--     ADD COLUMN zona_servicio VARCHAR(150) DEFAULT NULL AFTER modalidad;
