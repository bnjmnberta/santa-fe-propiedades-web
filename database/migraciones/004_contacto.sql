-- 004 · Página de Contacto propia (antes /contacto llevaba a la sección de Servicios).
-- Se aplica después de 003_ajustes_auditoria.sql.

SET NAMES utf8mb4;

-- Ubicación de la oficina (4 de Enero 2328) para el mapa de Contacto. Editable desde Panel > Configuración.
INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('oficina_coordenadas', '-31.647437, -60.712146', 'Ubicación de la oficina en el mapa de Contacto');
