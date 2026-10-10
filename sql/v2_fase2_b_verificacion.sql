-- =============================================================
-- v2_fase2_b_verificacion.sql
-- Sistema de verificación de identidad: OTP WhatsApp + foto DNI
-- Los profesionales existentes con estado='activo' NO se modifican.
-- =============================================================

-- 1. Modificar tabla profesionales
ALTER TABLE profesionales
  MODIFY COLUMN estado ENUM('pendiente','activo','inactivo') NOT NULL DEFAULT 'pendiente',
  ADD COLUMN telefono_verificado TINYINT(1)   NOT NULL DEFAULT 0   AFTER estado,
  ADD COLUMN foto_dni            VARCHAR(255)  NULL    DEFAULT NULL AFTER telefono_verificado,
  ADD COLUMN dni_verificado      TINYINT(1)   NOT NULL DEFAULT 0   AFTER foto_dni,
  ADD COLUMN dni_revisado_por    INT           NULL    DEFAULT NULL AFTER dni_verificado,
  ADD COLUMN dni_revisado_en     DATETIME      NULL    DEFAULT NULL AFTER dni_revisado_por;

-- Profesionales ya activos se consideran teléfono verificado (no retroceden a pendiente).
UPDATE profesionales SET telefono_verificado = 1 WHERE estado = 'activo';

-- 2. Tabla OTP WhatsApp
CREATE TABLE IF NOT EXISTS otp_whatsapp (
  id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  profesional_id INT(11)          NOT NULL,
  codigo         CHAR(6)          NOT NULL,
  expira_en      DATETIME         NOT NULL,
  usado          TINYINT(1)       NOT NULL DEFAULT 0,
  intentos       TINYINT          NOT NULL DEFAULT 0,
  creado_en      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_otp_profesional
    FOREIGN KEY (profesional_id)
    REFERENCES profesionales(id_profesional)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
