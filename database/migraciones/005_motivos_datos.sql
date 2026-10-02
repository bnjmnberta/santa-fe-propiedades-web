-- 005 · "¿Por qué elegirnos?" pasa a una franja con un dato grande por motivo.
-- Se aplica después de 004_contacto.sql.

SET NAMES utf8mb4;

ALTER TABLE motivo ADD COLUMN dato VARCHAR(40) NULL AFTER titulo;

UPDATE motivo SET dato = 'Desde 2007'    WHERE titulo = 'Trayectoria';
UPDATE motivo SET dato = 'CCI 099 · 731' WHERE titulo = 'Corredores matriculados';
UPDATE motivo SET dato = 'WhatsApp'      WHERE titulo = 'Atención personalizada';
