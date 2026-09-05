-- =============================================================
-- OficioRed — Datos de prueba v1
-- Ejecutar DESPUÉS de oficiored_v1.sql
-- Contraseña de todos los profesionales: Test1234!
-- Hash bcrypt generado con password_hash('Test1234!', PASSWORD_DEFAULT)
-- =============================================================

USE oficiored;

INSERT INTO profesionales
    (nombre, apellido, email, password, telefono, biografia, oficio, zona_cobertura, estado)
VALUES

-- Electricista
(
    'Carlos', 'Rodríguez',
    'carlos.electricista@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-412301',
    'Electricista matriculado con 10 años de experiencia. Instalaciones domiciliarias y comerciales. Certifico ante ENRE. Trabajo con garantía escrita.',
    'Electricista',
    'Centro, Barrio Norte — El Colorado',
    'activo'
),

-- Plomero
(
    'Marcos', 'Villalba',
    'marcos.plomero@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-523412',
    'Plomero especializado en instalaciones de agua y gas. Detección de pérdidas sin romper paredes. Atención de emergencias las 24 horas.',
    'Plomero',
    'El Colorado y zonas aledañas',
    'activo'
),

-- Albañil
(
    'Roberto', 'Acosta',
    'roberto.albanil@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-634523',
    '15 años en construcción y refacción. Construcción de viviendas, ampliaciones, revoques, pisos y revestimientos. Presupuesto sin cargo.',
    'Albañil',
    'El Colorado — toda la ciudad',
    'activo'
),

-- Carpintero
(
    'Diego', 'Fernández',
    'diego.carpintero@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-745634',
    'Carpintero especialista en muebles a medida, puertas, ventanas y decks. Trabajo con maderas nativas y machimbre. Diseño personalizado.',
    'Carpintero',
    'Barrio Sur, Centro — El Colorado',
    'activo'
),

-- Costurera
(
    'Luciana', 'Méndez',
    'luciana.costura@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-856745',
    'Modista y costurera con 8 años de experiencia. Confección a medida, ajustes, bordados y uniformes escolares. Atiendo a domicilio.',
    'Costurera',
    'Barrio Norte, Villa del Parque — El Colorado',
    'activo'
),

-- Pintor
(
    'Pablo', 'Giménez',
    'pablo.pintura@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-967856',
    'Pintor profesional. Interior y exterior, texturados, enduído y estucado. Trabajo con látex, esmalte y pintura epoxi para industria.',
    'Pintor',
    'El Colorado y localidades vecinas',
    'activo'
),

-- Gasista
(
    'Héctor', 'Sosa',
    'hector.gasista@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-178967',
    'Gasista matriculado. Instalación y reparación de cañerías de gas, conexión de artefactos y habilitación de instalaciones. Certificado GNC.',
    'Gasista',
    'El Colorado — toda la ciudad',
    'activo'
),

-- Jardinero
(
    'Ana', 'Bejarano',
    'ana.jardinera@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-390189',
    'Paisajista y jardinera. Diseño, poda y mantenimiento de jardines. Colocación de césped natural y artificial. Sistemas de riego por goteo.',
    'Jardinero',
    'Barrios residenciales — El Colorado',
    'activo'
),

-- Limpieza
(
    'Silvia', 'Torres',
    'silvia.limpieza@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-401290',
    'Servicio de limpieza domiciliaria y comercial. Limpieza profunda, post-obra y mantenimiento semanal. Productos incluidos.',
    'Limpieza',
    'El Colorado — toda la ciudad',
    'activo'
),

-- Otros (para probar esa categoría)
(
    'Ramón', 'Coronel',
    'ramon.refrigeracion@mail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL9Hzmq',
    '3716-289078',
    'Técnico en refrigeración y aire acondicionado. Instalación, mantenimiento y reparación de equipos split, heladeras y cámaras frigoríficas.',
    'Otros',
    'El Colorado — toda la ciudad',
    'activo'
);
