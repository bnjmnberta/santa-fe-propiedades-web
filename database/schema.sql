-- Santa Fe Propiedades — Web Etapa 1
-- Esquema MySQL 8.0.16+ / MariaDB 10.6+ (los CHECK se validan desde esas versiones).
-- Reglas de negocio referenciadas por letra: ver docs/ANALISIS.md, sección 6.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Seguridad
-- ---------------------------------------------------------------------------

-- Regla l: un rol por usuario. Regla m: bloqueo tras 5 intentos fallidos.
CREATE TABLE usuario (
    id_usuario        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre            VARCHAR(80)   NOT NULL,
    email             VARCHAR(120)  NOT NULL,
    contrasena_hash   VARCHAR(255)  NOT NULL,
    rol               ENUM('administrador', 'editor') NOT NULL DEFAULT 'editor',
    activo            TINYINT(1)    NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME      NULL,
    ultimo_acceso     DATETIME      NULL,
    fecha_alta        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Catálogo
-- ---------------------------------------------------------------------------

-- Regla b: es_comercial arma el listado "Comerciales".
CREATE TABLE tipo_propiedad (
    id_tipo      SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(60)  NOT NULL,
    slug         VARCHAR(60)  NOT NULL,
    es_comercial TINYINT(1)   NOT NULL DEFAULT 0,
    orden        SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (id_tipo),
    UNIQUE KEY uq_tipo_nombre (nombre),
    UNIQUE KEY uq_tipo_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Latitud/longitud: centro de la zona, para el mapa de la Etapa 2.
CREATE TABLE zona (
    id_zona  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre   VARCHAR(80)  NOT NULL,
    slug     VARCHAR(80)  NOT NULL,
    latitud  DECIMAL(9,6) NULL,
    longitud DECIMAL(9,6) NULL,
    PRIMARY KEY (id_zona),
    UNIQUE KEY uq_zona_nombre (nombre),
    UNIQUE KEY uq_zona_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE propiedad (
    id_propiedad       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    -- Regla h: código público. Las migradas conservan el id de la web vieja.
    codigo             INT UNSIGNED  NOT NULL,
    slug               VARCHAR(160)  NOT NULL,
    titulo             VARCHAR(150)  NOT NULL,
    -- Regla a: una sola operación por ficha.
    operacion          ENUM('venta', 'alquiler') NOT NULL,
    id_tipo            SMALLINT UNSIGNED NOT NULL,
    id_zona            SMALLINT UNSIGNED NULL,
    direccion          VARCHAR(150)  NULL,
    mostrar_direccion  TINYINT(1)    NOT NULL DEFAULT 1,
    -- Regla c: precio opcional; si hay precio, hay moneda.
    moneda             ENUM('ARS', 'USD') NULL,
    precio             DECIMAL(14,2) NULL,
    expensas           DECIMAL(12,2) NULL,
    -- Texto libre cuando no hay monto: "Bajas expensas", "Sin expensas".
    expensas_detalle   VARCHAR(60)   NULL,
    -- Alquiler: "5 recibos de sueldo". Disponibilidad: "Ingresá en octubre".
    requisitos         VARCHAR(150)  NULL,
    disponible_desde   DATE          NULL,
    amoblado           TINYINT(1)    NOT NULL DEFAULT 0,
    dormitorios        TINYINT UNSIGNED NULL,
    banos              TINYINT UNSIGNED NULL,
    cocheras           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sup_cubierta       DECIMAL(10,2) NULL,
    sup_terreno        DECIMAL(10,2) NULL,
    frente_m           DECIMAL(6,2)  NULL,
    fondo_m            DECIMAL(6,2)  NULL,
    regimen            VARCHAR(60)   NULL,
    planta             VARCHAR(30)   NULL,
    ubicacion_unidad   ENUM('frente', 'contrafrente', 'interno', 'lateral') NULL,
    -- Una característica por línea; se muestran como lista (formato de Instagram).
    caracteristicas    TEXT          NULL,
    servicios          VARCHAR(300)  NULL,
    -- "Cerca de Bv. Gálvez y la Costanera": SEO y mapa de la Etapa 2.
    referencias        VARCHAR(250)  NULL,
    descripcion        TEXT          NULL,
    instagram_url      VARCHAR(255)  NULL,
    latitud            DECIMAL(9,6)  NULL,
    longitud           DECIMAL(9,6)  NULL,
    -- Regla d: solo disponible y reservado se publican.
    estado             ENUM('disponible', 'reservado', 'alquilado', 'vendido', 'pausado')
                       NOT NULL DEFAULT 'disponible',
    destacada          TINYINT(1)    NOT NULL DEFAULT 0,
    creado_por         INT UNSIGNED  NULL,
    fecha_alta         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_cierre       DATETIME      NULL,
    -- Regla k: baja lógica.
    eliminado_en       DATETIME      NULL,
    PRIMARY KEY (id_propiedad),
    UNIQUE KEY uq_propiedad_codigo (codigo),
    KEY ix_propiedad_catalogo (eliminado_en, estado, operacion, id_tipo, id_zona),
    KEY ix_propiedad_destacada (destacada, estado),
    CONSTRAINT fk_propiedad_tipo    FOREIGN KEY (id_tipo)    REFERENCES tipo_propiedad (id_tipo),
    CONSTRAINT fk_propiedad_zona    FOREIGN KEY (id_zona)    REFERENCES zona (id_zona) ON DELETE SET NULL,
    CONSTRAINT fk_propiedad_usuario FOREIGN KEY (creado_por) REFERENCES usuario (id_usuario) ON DELETE SET NULL,
    CONSTRAINT ck_propiedad_precio_moneda CHECK (precio IS NULL OR moneda IS NOT NULL),
    CONSTRAINT ck_propiedad_precio_positivo CHECK (precio IS NULL OR precio > 0),
    -- Regla e: alquilado solo en alquiler, vendido solo en venta.
    CONSTRAINT ck_propiedad_estado_operacion CHECK (
        (estado <> 'alquilado' OR operacion = 'alquiler')
        AND (estado <> 'vendido' OR operacion = 'venta')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regla g: la foto con menor orden es la portada. "archivo" es el nombre base;
-- en disco existen {archivo}-1600.webp y {archivo}-480.webp.
CREATE TABLE foto (
    id_foto      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_propiedad INT UNSIGNED NOT NULL,
    archivo      VARCHAR(120) NOT NULL,
    orden        SMALLINT     NOT NULL DEFAULT 0,
    ancho        SMALLINT UNSIGNED NULL,
    alto         SMALLINT UNSIGNED NULL,
    fecha_alta   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_foto),
    KEY ix_foto_propiedad_orden (id_propiedad, orden),
    CONSTRAINT fk_foto_propiedad FOREIGN KEY (id_propiedad) REFERENCES propiedad (id_propiedad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regla f: todo cambio de estado queda registrado.
CREATE TABLE historial_estado (
    id_historial    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_propiedad    INT UNSIGNED NOT NULL,
    estado_anterior ENUM('disponible', 'reservado', 'alquilado', 'vendido', 'pausado') NOT NULL,
    estado_nuevo    ENUM('disponible', 'reservado', 'alquilado', 'vendido', 'pausado') NOT NULL,
    id_usuario      INT UNSIGNED NULL,
    nota            VARCHAR(200) NULL,
    fecha           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_historial),
    KEY ix_historial_propiedad (id_propiedad, fecha),
    CONSTRAINT fk_historial_propiedad FOREIGN KEY (id_propiedad) REFERENCES propiedad (id_propiedad) ON DELETE CASCADE,
    CONSTRAINT fk_historial_usuario   FOREIGN KEY (id_usuario)   REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Contenido institucional
-- ---------------------------------------------------------------------------

CREATE TABLE servicio (
    id_servicio SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo      VARCHAR(80)  NOT NULL,
    descripcion VARCHAR(600) NOT NULL,
    icono       VARCHAR(40)  NULL,
    orden       SMALLINT     NOT NULL DEFAULT 0,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion (
    clave       VARCHAR(60)  NOT NULL,
    valor       TEXT         NOT NULL,
    descripcion VARCHAR(150) NULL,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Datos base
-- ---------------------------------------------------------------------------

INSERT INTO tipo_propiedad (nombre, slug, es_comercial, orden) VALUES
    ('Departamento',  'departamento',  0, 1),
    ('Casa',          'casa',          0, 2),
    ('Casa quinta',   'casa-quinta',   0, 3),
    ('Lote / terreno','lote-terreno',  0, 4),
    ('Salón / local', 'salon-local',   1, 5),
    ('Galpón',        'galpon',        1, 6),
    ('Cochera',       'cochera',       1, 7),
    ('Fideicomiso',   'fideicomiso',   0, 8);

INSERT INTO zona (nombre, slug) VALUES
    ('Candioti Norte', 'candioti-norte'),
    ('Centro',         'centro'),
    ('Centro Sur',     'centro-sur'),
    ('Colastiné',      'colastine'),
    ('Constituyentes', 'constituyentes'),
    ('Las Flores I',   'las-flores-i'),
    ('Recreo',         'recreo'),
    ('Roma',           'roma'),
    ('Sauce Viejo',    'sauce-viejo'),
    ('Sur',            'sur');

INSERT INTO servicio (titulo, descripcion, icono, orden) VALUES
    ('Tasaciones',
     'Somos profesionales capacitados para tasar con responsabilidad e incrementar su posibilidad de venta.',
     'tasacion', 1),
    ('Administración de alquileres',
     'Aseguramos su renta y la correcta administración de su inmueble trabajando con eficacia y celeridad para optimizar sus beneficios.',
     'llave', 2),
    ('Asesoramiento en compraventa',
     'Estudiamos y conocemos el mercado de la ciudad y sus alrededores, su constante actualización y dinamismo. Estamos preparados para asesorarlo en la compraventa de inmuebles según el destino del mismo.',
     'casa', 3),
    ('Difusión comercial',
     'Promocionamos su propiedad con una web moderna y actualizada, cartelería institucional, redes sociales y publicaciones en distintos medios.',
     'megafono', 4),
    ('Asesoramiento y ejecución de obras',
     'Un staff de profesionales matriculados en construcción lo asesora en proyectos y ejecución de obras según los requerimientos de cada cliente.',
     'obra', 5);

INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('whatsapp_numero', '5493424219298',              'Número de WhatsApp en formato internacional, sin + ni espacios'),
    ('whatsapp_visible','0342 4-219298',              'Número de WhatsApp como se muestra en la web'),
    ('telefono_fijo',   '0342 4-559-864',             'Teléfono de la oficina'),
    ('email',           'santafepropiedades@hotmail.com', 'Email de contacto'),
    ('direccion',       '4 de Enero 2328, Santa Fe',  'Dirección de la oficina'),
    ('matricula',       'CCI 099 | CCI 731',          'Matrículas del Colegio de Corredores Inmobiliarios'),
    ('horario',         'Lunes a viernes de 8 a 12 y de 16 a 19 hs', 'Horario de atención'),
    ('instagram_url',   'https://www.instagram.com/santafepropiedadesinmobiliaria/', 'Perfil de Instagram'),
    ('ga_measurement_id','',                          'ID de Google Analytics 4 (G-XXXXXXX); vacío desactiva el seguimiento');
