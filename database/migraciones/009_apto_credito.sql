-- 009 · Propiedades en venta "aptas crédito" hipotecario.
-- Una propiedad es apta crédito si tiene escritura, plano de mensura y plano de arquitectura.
-- Se tilda al cargar la propiedad en el panel y solo se guarda para ventas. Se aplica después de 008_condiciones_alquiler.sql.

ALTER TABLE propiedad ADD COLUMN apto_credito TINYINT(1) NOT NULL DEFAULT 0 AFTER requisitos;
