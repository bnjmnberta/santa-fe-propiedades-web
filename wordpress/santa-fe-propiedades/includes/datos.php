<?php
declare(strict_types=1);

// Tipos de contenido de WordPress: propiedades, servicios, motivos, preguntas frecuentes y puntos del mapa.
// Se administran desde el escritorio; el sitio los muestra con las vistas de vistas/.

/**
 * Campos de una propiedad (cada uno se guarda como dato "sfp_<clave>", igual que las columnas de la base original).
 * Tipos: texto, numero, seleccion, casilla, area, fecha.
 *
 * @return array<string, array{titulo: string, campos: array<string, array<string, mixed>>}>
 */
function sfp_campos_propiedad(): array
{
    return [
        'basico' => ['titulo' => 'Operación y estado', 'campos' => [
            'codigo'    => ['Código público (número que se ve en la web)', 'numero', 'ayuda' => 'Si lo dejás vacío se asigna el siguiente libre.'],
            'operacion' => ['Operación', 'seleccion', 'opciones' => ['alquiler' => 'Alquiler', 'venta' => 'Venta']],
            'estado'    => ['Estado', 'seleccion', 'opciones' => ['disponible' => 'Disponible', 'reservado' => 'Reservado', 'alquilado' => 'Alquilado', 'vendido' => 'Vendido', 'pausado' => 'Pausado'], 'ayuda' => 'Solo "Disponible" y "Reservado" se ven en la web. Alquilado solo vale para alquileres y Vendido para ventas.'],
            'destacada' => ['Destacar en la portada', 'casilla'],
        ]],
        'ubicacion' => ['titulo' => 'Ubicación', 'campos' => [
            'direccion'         => ['Dirección', 'texto'],
            'mostrar_direccion' => ['Mostrar la dirección en la web', 'casilla'],
            'latitud'           => ['Latitud (para el mapa)', 'texto'],
            'longitud'          => ['Longitud (para el mapa)', 'texto'],
            'referencias'       => ['Referencias ("Cerca de Bv. Gálvez y la Costanera")', 'texto'],
        ]],
        'precio' => ['titulo' => 'Precio y condiciones', 'campos' => [
            'moneda'           => ['Moneda', 'seleccion', 'opciones' => ['' => '—', 'ARS' => '$ (pesos)', 'USD' => 'U$S (dólares)']],
            'precio'           => ['Precio (vacío = «Consultar precio»)', 'numero'],
            'expensas'         => ['Expensas ($ por mes)', 'numero'],
            'expensas_detalle' => ['Expensas en palabras (ej: Sin expensas)', 'texto'],
            'requisitos'       => ['Requisitos (ej: 5 recibos de sueldo)', 'texto'],
            'apto_credito'     => ['Apto crédito hipotecario (solo ventas)', 'casilla', 'ayuda' => 'Escritura, plano de mensura y plano de arquitectura.'],
            'disponible_desde' => ['Disponible desde', 'fecha'],
            'amoblado'         => ['Amoblado', 'casilla'],
        ]],
        'caracteristicas' => ['titulo' => 'Características', 'campos' => [
            'dormitorios'      => ['Dormitorios (0 = monoambiente)', 'numero'],
            'banos'            => ['Baños', 'numero'],
            'cocheras'         => ['Cocheras', 'numero'],
            'sup_cubierta'     => ['Superficie cubierta (m²)', 'numero'],
            'sup_terreno'      => ['Superficie del terreno (m²)', 'numero'],
            'frente_m'         => ['Frente (m)', 'numero'],
            'fondo_m'          => ['Fondo (m)', 'numero'],
            'regimen'          => ['Régimen', 'texto'],
            'planta'           => ['Planta', 'texto'],
            'ubicacion_unidad' => ['Ubicación de la unidad', 'seleccion', 'opciones' => ['' => '—', 'frente' => 'Frente', 'contrafrente' => 'Contrafrente', 'interno' => 'Interno', 'lateral' => 'Lateral']],
            'caracteristicas'  => ['Características (una por línea)', 'area'],
            'servicios'        => ['Servicios', 'texto'],
        ]],
        'texto' => ['titulo' => 'Descripción', 'campos' => [
            'descripcion'   => ['Descripción', 'area'],
            'instagram_url' => ['Publicación de Instagram (enlace)', 'texto'],
        ]],
    ];
}

/** Campos sueltos de los otros tipos de contenido, además del título. */
function sfp_campos_auxiliares(): array
{
    return [
        'sfp_servicio' => [
            'icono' => ['Ícono (tasacion, llave, casa, megafono, obra)', 'texto'],
        ],
        'sfp_motivo' => [
            'dato'        => ['Dato grande (ej: +30 años)', 'texto'],
            'descripcion' => ['Descripción', 'area'],
            'icono'       => ['Ícono', 'texto'],
        ],
        'sfp_faq' => [
            'grupo' => ['Grupo de preguntas (ej: Alquilar)', 'texto'],
        ],
        'sfp_punto' => [
            'categoria' => ['Categoría', 'seleccion', 'opciones' => ['facultad' => 'Facultad', 'transporte' => 'Transporte', 'puerto' => 'Puerto', 'costanera' => 'Costanera', 'otro' => 'Otro']],
            'latitud'   => ['Latitud', 'texto'],
            'longitud'  => ['Longitud', 'texto'],
        ],
    ];
}

add_action('init', static function (): void {
    $etiquetas = static fn (string $singular, string $plural, string $articulo = 'la'): array => [
        'name'               => $plural,
        'singular_name'      => $singular,
        'add_new'            => $articulo === 'la' ? 'Agregar nueva' : 'Agregar nuevo',
        'add_new_item'       => ($articulo === 'la' ? 'Agregar nueva ' : 'Agregar nuevo ') . mb_strtolower($singular),
        'edit_item'          => 'Editar ' . mb_strtolower($singular),
        'new_item'           => ($articulo === 'la' ? 'Nueva ' : 'Nuevo ') . mb_strtolower($singular),
        'view_item'          => 'Ver ' . mb_strtolower($singular),
        'search_items'       => 'Buscar ' . mb_strtolower($plural),
        'not_found'          => 'No hay ' . mb_strtolower($plural) . '.',
        'not_found_in_trash' => 'No hay ' . mb_strtolower($plural) . ' en la papelera.',
        'all_items'          => $plural,
        'menu_name'          => $plural,
    ];
    // Los datos se muestran con las vistas del sitio, no con las plantillas de WordPress.
    $comun = ['public' => false, 'show_ui' => true, 'show_in_rest' => false, 'has_archive' => false, 'rewrite' => false, 'query_var' => false, 'capability_type' => 'post'];

    register_post_type('sfp_propiedad', $comun + [
        'labels'       => $etiquetas('Propiedad', 'Propiedades'),
        'menu_icon'    => 'dashicons-admin-home',
        'menu_position' => 5,
        'supports'     => ['title'],
        'taxonomies'   => ['sfp_tipo', 'sfp_zona'],
    ]);
    foreach ([
        'sfp_servicio' => ['Servicio', 'Servicios', 'dashicons-hammer', ['title', 'excerpt', 'page-attributes'], 'el'],
        'sfp_motivo'   => ['Motivo', 'Motivos para elegirnos', 'dashicons-awards', ['title', 'page-attributes'], 'el'],
        'sfp_faq'      => ['Pregunta frecuente', 'Preguntas frecuentes', 'dashicons-editor-help', ['title', 'editor', 'page-attributes'], 'la'],
        'sfp_punto'    => ['Punto del mapa', 'Puntos del mapa', 'dashicons-location', ['title'], 'el'],
    ] as $tipo => [$singular, $plural, $icono, $soporta, $articulo]) {
        register_post_type($tipo, $comun + [
            'labels'        => $etiquetas($singular, $plural, $articulo),
            'menu_icon'     => $icono,
            'show_in_menu'  => 'edit.php?post_type=sfp_propiedad',
            'supports'      => $soporta,
        ]);
    }

    $taxonomia = static fn (string $singular, string $plural): array => [
        'labels'            => ['name' => $plural, 'singular_name' => $singular, 'add_new_item' => 'Agregar ' . mb_strtolower($singular), 'edit_item' => 'Editar ' . mb_strtolower($singular), 'all_items' => $plural, 'menu_name' => $plural, 'search_items' => 'Buscar ' . mb_strtolower($plural)],
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => false,
        'hierarchical'      => false,
        'rewrite'           => false,
        'query_var'         => false,
        'meta_box_cb'       => false, // el tipo y la zona se eligen en el cuadro "Datos de la propiedad"
    ];
    register_taxonomy('sfp_tipo', ['sfp_propiedad'], $taxonomia('Tipo de propiedad', 'Tipos de propiedad'));
    register_taxonomy('sfp_zona', ['sfp_propiedad'], $taxonomia('Zona', 'Zonas'));
});
