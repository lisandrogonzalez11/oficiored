-- =============================================================
-- v2_fase2_a_tokens_recuperacion.sql
-- Tabla para tokens de recuperación de contraseña (profesionales y admin).
-- =============================================================

CREATE TABLE IF NOT EXISTS tokens_recuperacion (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(100) NOT NULL,
    token       CHAR(64)     NOT NULL,
    expira_en   DATETIME     NOT NULL,
    usado       TINYINT(1)   NOT NULL DEFAULT 0,
    creado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
