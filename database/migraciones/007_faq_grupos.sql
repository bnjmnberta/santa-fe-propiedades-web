-- 007 · Preguntas frecuentes agrupadas por tema.
-- Agrega el campo "grupo" y ordena las 8 preguntas de partida en tres grupos. Se aplica después de 006_horario_estructurado.sql.
-- Las preguntas nuevas que se carguen sin grupo (vacío) se muestran al final, en "Otras preguntas".

SET NAMES utf8mb4;

ALTER TABLE faq ADD COLUMN grupo VARCHAR(60) NOT NULL DEFAULT '' AFTER respuesta;

UPDATE faq SET grupo = 'Consultar y visitar', orden = 1 WHERE pregunta = '¿Cómo consulto por una propiedad?';
UPDATE faq SET grupo = 'Consultar y visitar', orden = 2 WHERE pregunta = '¿Puedo coordinar una visita?';
UPDATE faq SET grupo = 'Consultar y visitar', orden = 3 WHERE pregunta = '¿Qué es el código de la propiedad?';
UPDATE faq SET grupo = 'Consultar y visitar', orden = 4 WHERE pregunta = '¿Cuál es el horario de atención?';
UPDATE faq SET grupo = 'Alquilar o comprar',  orden = 5 WHERE pregunta = '¿Qué requisitos piden para alquilar?';
UPDATE faq SET grupo = 'Alquilar o comprar',  orden = 6 WHERE pregunta = '¿Qué significa que una propiedad esté «Reservada»?';
UPDATE faq SET grupo = 'Si sos propietario',  orden = 7 WHERE pregunta = '¿Hacen tasaciones?';
UPDATE faq SET grupo = 'Si sos propietario',  orden = 8 WHERE pregunta = '¿Pueden administrar mi propiedad en alquiler?';
