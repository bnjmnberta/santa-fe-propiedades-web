<?php
declare(strict_types=1);

/*
 * Correcciones de edición sobre lo que trae la web vieja: títulos con tildes, mayúsculas y
 * abreviaturas parejas, y la zona de las propiedades que llegaban sin barrio.
 * migrar_catalogo.php las aplica después de las de Instagram (pisan a esas si coinciden).
 *
 * Zonas: se usó la dirección, el barrio de OpenStreetMap para esas coordenadas y la zona que
 * la inmobiliaria ya les da a las propiedades vecinas. Las marcadas "a confirmar" son estimadas.
 * 'alberdi' y 'sargento-cabral' vienen de database/migraciones/003_ajustes_auditoria.sql.
 */
return [
    32  => ['titulo' => 'Muy buen local'],
    51  => ['titulo' => 'Gran galpón con 810 m² cubiertos'],
    75  => ['titulo' => 'Oportunidad de inversión', 'zona' => 'centro'], // Eva Perón 2400. A confirmar.
    78  => ['titulo' => 'Excelente lote en el mejor lugar de Colastiné'],
    136 => ['titulo' => 'Galpón sobre Ruta 11'],
    152 => ['titulo' => 'Dpto. de 2 dormitorios en Las Flores I'],
    172 => ['titulo' => 'Dpto. de 2 dormitorios en el centro'],
    177 => ['titulo' => 'Casa quinta en Sauce Viejo'],
    185 => ['titulo' => 'Casa en zona sur', 'zona' => 'sur'], // Saavedra 1300; el título ya decía "zona sur".
    202 => ['titulo' => 'Gran galpón sobre Ruta 11'],
    208 => ['titulo' => 'Gran galpón y terreno'],
    234 => ['titulo' => 'Dpto. de 3 dormitorios con cochera'],
    238 => ['titulo' => 'Dpto. de 2 dormitorios con cochera exclusiva'],
    239 => ['zona' => 'candioti-norte'], // Las Heras 3900, al lado de la #51.
    240 => ['titulo' => 'Torre Ele – CAM 91'], // Qué es "CAM 91": a confirmar con la inmobiliaria.
    244 => ['titulo' => 'Local en pleno Recoleta'],
    247 => ['titulo' => 'Dpto. amoblado con cochera'],
    248 => ['titulo' => 'Casa en PH, zona centro'],
    249 => ['titulo' => 'Hermosa casa en barrio Roma'],
    252 => ['titulo' => 'Dpto. a estrenar, zona Constituyentes'],
    254 => ['zona' => 'centro-sur'], // Dr. Zavalla 1800, a 400 m de la #234. A confirmar.
    255 => ['titulo' => 'Galpón con oficina y dpto. de 1 dormitorio', 'zona' => 'sargento-cabral'], // Güemes 5700
    257 => ['titulo' => 'Dpto. de 1 dormitorio al frente', 'zona' => 'centro-sur'], // Crespo 3200, a 150 m de la #252.
    258 => ['titulo' => 'Local sobre Av. Galicia', 'zona' => 'alberdi'], // Av. Galicia 1500
];
