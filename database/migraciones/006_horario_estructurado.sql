-- 006 · Horario de atención con datos que la web puede calcular (indicador "Abierto ahora / Cerrado").
-- El texto "horario" sigue mostrándose tal cual; estos dos campos alimentan el indicador de la página de Contacto.
-- Se aplica después de 005_motivos_datos.sql.

SET NAMES utf8mb4;

INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('horario_dias',   '1-5',        'Días de atención para el indicador Abierto/Cerrado: de 1 (lunes) a 7 (domingo), ej: 1-5'),
    ('horario_turnos', '8-12, 16-19', 'Turnos de atención para el indicador Abierto/Cerrado, en horas, ej: 8-12, 16-19');
