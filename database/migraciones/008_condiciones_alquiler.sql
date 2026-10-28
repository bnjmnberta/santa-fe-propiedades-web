-- 008 · Condiciones del alquiler para inquilinos.
-- Se muestran en la ficha de cada propiedad en alquiler. Se editan desde Panel > Configuración, una condición por línea.
-- Se aplica después de 007_faq_grupos.sql.

SET NAMES utf8mb4;

INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('condiciones_alquiler', CONCAT('Garantía propietaria o seguro de caución', CHAR(10), '3 recibos de sueldo'),
     'Condiciones del alquiler para inquilinos, una por línea. Se ven en la ficha de cada propiedad en alquiler.')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);
