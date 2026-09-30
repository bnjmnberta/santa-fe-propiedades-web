-- 002 · Boceto del 30/09/2026: páginas propias, FAQ, "¿Por qué elegirnos?", mapa con
-- puntos estratégicos e interruptores para mostrar cada módulo por etapas.
-- Se aplica después de schema.sql. Los textos son BORRADORES a aprobar por la inmobiliaria.

SET NAMES utf8mb4;

-- Etapa 3: preguntas frecuentes, editables desde el panel.
CREATE TABLE faq (
    id_faq    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta  VARCHAR(200) NOT NULL,
    respuesta TEXT         NOT NULL,
    orden     SMALLINT     NOT NULL DEFAULT 0,
    activo    TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_faq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bloque "¿Por qué elegirnos?" (tres motivos en el boceto).
CREATE TABLE motivo (
    id_motivo   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo      VARCHAR(80)  NOT NULL,
    descripcion VARCHAR(300) NOT NULL,
    icono       VARCHAR(40)  NULL,
    orden       SMALLINT     NOT NULL DEFAULT 0,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_motivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Etapa 2: puntos estratégicos de Santa Fe que se muestran en el mapa junto a las propiedades.
CREATE TABLE punto_interes (
    id_punto  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre    VARCHAR(100) NOT NULL,
    categoria ENUM('facultad', 'transporte', 'puerto', 'costanera', 'otro') NOT NULL,
    latitud   DECIMAL(9,6) NOT NULL,
    longitud  DECIMAL(9,6) NOT NULL,
    activo    TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_punto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO motivo (titulo, descripcion, icono, orden) VALUES
    ('Trayectoria',
     'Desde 2007 en Santa Fe, con un equipo que viene de más de 30 años en la construcción: sabemos evaluar cada propiedad por dentro.',
     'reloj', 1),
    ('Corredores matriculados',
     'Operaciones respaldadas por las matrículas CCI 099 y CCI 731 del Colegio de Corredores Inmobiliarios.',
     'tasacion', 2),
    ('Atención personalizada',
     'Te responde una persona del equipo, por WhatsApp o en nuestra oficina de 4 de Enero 2328.',
     'whatsapp', 3);

INSERT INTO faq (pregunta, respuesta, orden) VALUES
    ('¿Cómo consulto por una propiedad?',
     'Tocá «Consultar por WhatsApp» en la ficha: el mensaje ya incluye el código de la propiedad, así te respondemos más rápido. También podés llamarnos o acercarte a la oficina.', 1),
    ('¿Qué requisitos piden para alquilar?',
     'Dependen de cada propiedad y los indicamos en la ficha (por ejemplo, recibos de sueldo). Escribinos y te pasamos la lista completa de la que te interesa.', 2),
    ('¿Puedo coordinar una visita?',
     'Sí. Escribinos por WhatsApp con el código de la propiedad y te proponemos día y horario.', 3),
    ('¿Qué significa que una propiedad esté «Reservada»?',
     'Que ya hay un acuerdo en curso. Si te interesa igual, consultanos: si la operación no se concreta, vuelve a estar disponible.', 4),
    ('¿Hacen tasaciones?',
     'Sí. Tasamos casas, departamentos, locales, galpones y terrenos en Santa Fe y alrededores. Pedí la tuya por WhatsApp.', 5),
    ('¿Pueden administrar mi propiedad en alquiler?',
     'Sí: nos ocupamos de la difusión, la búsqueda de inquilinos y la administración durante el alquiler. Mirá «Alquilá con nosotros».', 6),
    ('¿Qué es el código de la propiedad?',
     'Un número único de cada ficha. Si lo tenés (por ejemplo, de una publicación de Instagram), escribilo en «¿Tenés el código?» y vas directo a la propiedad.', 7),
    ('¿Cuál es el horario de atención?',
     'Lunes a viernes de 8 a 12 y de 16 a 19 hs, en 4 de Enero 2328.', 8);

-- Coordenadas de OpenStreetMap (Nominatim, 30/09/2026).
INSERT INTO punto_interes (nombre, categoria, latitud, longitud) VALUES
    ('Ciudad Universitaria UNL (El Pozo)',          'facultad',   -31.640329, -60.672549),
    ('Facultad de Ciencias Económicas (UNL)',       'facultad',   -31.654838, -60.708710),
    ('Facultad de Ciencias Jurídicas y Sociales (UNL)', 'facultad', -31.634174, -60.704826),
    ('Facultad de Ingeniería Química (UNL)',        'facultad',   -31.637327, -60.707198),
    ('UTN Facultad Regional Santa Fe',              'facultad',   -31.616756, -60.675237),
    ('Universidad Católica de Santa Fe',            'facultad',   -31.606677, -60.671807),
    ('Terminal de Ómnibus',                         'transporte', -31.643246, -60.700304),
    ('Puerto de Santa Fe',                          'puerto',     -31.652290, -60.698363),
    ('Costanera Oeste (El Faro)',                   'costanera',  -31.629651, -60.677721),
    ('Costanera Este',                              'costanera',  -31.637129, -60.676793),
    ('Puente Colgante',                             'costanera',  -31.640074, -60.681234);

INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('eslogan',           'Tu lugar en Santa Fe',   'Título principal de la portada'),
    ('eslogan_bajada',    'Alquileres, ventas y tasaciones con atención personalizada desde 2007.', 'Texto debajo del título de la portada'),
    ('foto_local',        '',                        'Foto de la oficina (ruta dentro de public/), para la sección Contacto'),
    ('modulo_mapa',       '1', 'Etapa 2: mapa de propiedades y puntos estratégicos (1 = visible, 0 = oculto)'),
    ('modulo_faq',        '1', 'Etapa 3: preguntas frecuentes'),
    ('modulo_paginas',    '1', 'Etapa 3: páginas propias de Servicios y La Empresa'),
    ('modulo_captacion',  '1', 'Etapa 3: Alquilá / Vendé con nosotros');
