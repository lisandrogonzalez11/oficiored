-- v4b: Campo instagram_url en profesionales
ALTER TABLE profesionales
    ADD COLUMN instagram_url VARCHAR(100) DEFAULT NULL AFTER telefono;
