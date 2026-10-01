-- 003 · Ajustes de la auditoría del 30/09/2026. Se aplica después de 002_boceto_y_mapa.sql.

SET NAMES utf8mb4;

-- Zonas que faltaban para ubicar el catálogo migrado (ver scripts/datos/correcciones_edicion.php).
INSERT INTO zona (nombre, slug) VALUES
    ('Alberdi',         'alberdi'),
    ('Sargento Cabral', 'sargento-cabral');

-- La portada decía "desde 2007" en el antetítulo y otra vez en la bajada.
-- Solo cambia el texto si nadie lo editó desde el panel.
UPDATE configuracion
SET valor = 'Alquileres, ventas y locales comerciales, con atención personalizada.'
WHERE clave = 'eslogan_bajada'
  AND valor = 'Alquileres, ventas y tasaciones con atención personalizada desde 2007.';
