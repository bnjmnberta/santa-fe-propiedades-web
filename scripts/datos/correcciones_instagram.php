<?php
declare(strict_types=1);

/*
 * Correcciones tomadas de Instagram (@santafepropiedadesinmobiliaria), que está más
 * actualizado que la web vieja. Relevado el 30/09/2026 sobre los 12 posts más recientes.
 * Se aplican encima de lo que trae la web vieja. Ver docs/ANALISIS.md, sección 1.
 *
 * Claves especiales: 'zona' es el slug de la tabla zona; '_fecha_instagram' es la fecha
 * del post que informa el cambio de estado (va a la nota del historial).
 */
return [
    // ❌VENDIDA❌ el 27/09. La web vieja la sigue mostrando.
    172 => [
        'estado'           => 'vendido',
        'moneda'           => 'USD',
        'precio'           => 82000,
        'sup_cubierta'     => 75,
        'ubicacion_unidad' => 'contrafrente',
        'planta'           => '1.º piso',
        'expensas_detalle' => 'Bajas expensas',
        'referencias'      => 'Muy cerca del casco histórico, la Facultad de Ciencias Económicas y la peatonal.',
        'instagram_url'    => 'https://www.instagram.com/p/DdznWOKlaza/',
        '_fecha_instagram' => '2026-09-27',
    ],
    // ❌NO DISPONIBLE❌ el 01/09 (semipiso amoblado, 4 de Enero 3041).
    247 => [
        'estado'           => 'alquilado',
        'amoblado'         => 1,
        'instagram_url'    => 'https://www.instagram.com/p/DcwLENTlRHi/',
        '_fecha_instagram' => '2026-09-01',
    ],
    // Pendiente 12: Instagram dice Centro Sur; el título viejo dice "z/ Constituyentes".
    252 => [
        'zona'          => 'centro-sur',
        'instagram_url' => 'https://www.instagram.com/p/Dc1mcRiFGsw/',
    ],
    257 => [
        'sup_cubierta'     => 52,
        'requisitos'       => '5 recibos de sueldo',
        'ubicacion_unidad' => 'frente',
        'instagram_url'    => 'https://www.instagram.com/reel/DdSJ1HBOXY5/',
    ],
    238 => [
        'planta'           => '2.º piso',
        'ubicacion_unidad' => 'frente',
        'banos'            => 2,
        'referencias'      => 'Sobre Jujuy entre 1 de Mayo y 4 de Enero, a metros del Parque Sur. Orientación norte.',
        'instagram_url'    => 'https://www.instagram.com/reel/DdCzdTgNOpa/',
    ],
    254 => [
        'sup_cubierta'  => 84,
        'sup_terreno'   => 250,
        'instagram_url' => 'https://www.instagram.com/p/DcUVY-JlYlH/',
    ],
    // Coincide por título y zona con el lote de Aldea Setúbal de Instagram: confirmar con el cliente.
    78 => [
        'frente_m'      => 22,
        'fondo_m'       => 34,
        'referencias'   => 'Aldea Setúbal (unidad U11), detrás de UPCN. Entorno de viviendas permanentes y casas quintas, a pocos minutos de la ciudad.',
        'instagram_url' => 'https://www.instagram.com/p/DcO813uFdj0/',
    ],
    258 => [
        'expensas_detalle' => 'Sin expensas',
        'instagram_url'    => 'https://www.instagram.com/p/DduNMFNifTB/',
    ],
    126 => [
        'expensas_detalle' => 'Sin expensas',
    ],
];
