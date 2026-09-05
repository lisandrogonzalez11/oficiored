-- =============================================================
-- OficioRed — Script de base de datos v1
-- Proyecto: OficioRed
-- Grupo 13 — Practicas Profesionalizantes II
-- Charset: utf8mb4 / Collation: utf8mb4_general_ci
-- Engine: InnoDB
-- =============================================================

-- --------------------------------------------
-- Base de datos: oficiored
-- --------------------------------------------
CREATE DATABASE IF NOT EXISTS oficiored
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE oficiored;

-- --------------------------------------------
-- Tabla: categorias
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id_categoria   INT(11)      NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(80)  NOT NULL,
  descripcion    TEXT         NULL,
  icono_lucide   VARCHAR(50)  NOT NULL DEFAULT 'briefcase',
  PRIMARY KEY (id_categoria),
  UNIQUE KEY idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Tabla: profesionales
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS profesionales (
  id_profesional   INT(11)        NOT NULL AUTO_INCREMENT,
  nombre           VARCHAR(50)    NOT NULL,
  apellido         VARCHAR(50)    NOT NULL,
  email            VARCHAR(100)   NOT NULL,
  password         VARCHAR(255)   NOT NULL,
  telefono         VARCHAR(20)    NULL,
  foto_perfil      VARCHAR(255)   NULL,
  biografia        TEXT           NULL,
  fecha_registro   DATETIME       DEFAULT CURRENT_TIMESTAMP,
  oficio           VARCHAR(80)    NOT NULL,
  zona_cobertura   VARCHAR(80)    NOT NULL,
  estado           ENUM('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (id_profesional),
  UNIQUE KEY idx_email (email),
  KEY idx_oficio (oficio),
  KEY idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Tabla: usuarios (reservada, sin uso funcional en v1)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario       INT(11)        NOT NULL AUTO_INCREMENT,
  nombre           VARCHAR(50)    NOT NULL,
  apellido         VARCHAR(50)    NOT NULL,
  email            VARCHAR(100)   NOT NULL,
  password         VARCHAR(255)   NOT NULL,
  telefono         VARCHAR(20)    NULL,
  direccion        VARCHAR(150)   NULL,
  fecha_registro   DATETIME       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Datos iniciales: categorias (seed data)
-- --------------------------------------------
INSERT INTO categorias (nombre, descripcion, icono_lucide) VALUES
  ('Electricista', 'Instalaciones y reparaciones eléctricas',        'zap'),
  ('Plomero',      'Instalaciones y reparaciones de agua y gas',     'droplets'),
  ('Albañil',      'Construcción y reformas',                        'hard-hat'),
  ('Carpintero',   'Trabajos en madera y muebles',                   'hammer'),
  ('Costurera',    'Arreglos y confección de ropa',                  'scissors'),
  ('Pintor',       'Pintura de interiores y exteriores',             'paintbrush'),
  ('Gasista',      'Instalaciones y reparaciones de gas',            'flame'),
  ('Jardinero',    'Mantenimiento de jardines y espacios verdes',    'sprout'),
  ('Limpieza',     'Servicios de limpieza general',                  'sparkles'),
  ('Otros',        'Otros oficios y servicios',                      'briefcase');
