<?php
declare(strict_types=1);

// Escritorio de WordPress: cuadros de edición de propiedades, galería, columnas y configuración del sitio.

use App\Repositorios\ConfiguracionRepositorio;

// ---------------------------------------------------------------- Cuadros de edición

add_action('add_meta_boxes', static function (): void {
    add_meta_box('sfp_datos', 'Datos de la propiedad', 'sfp_dibujar_datos', 'sfp_propiedad', 'normal', 'high');
    add_meta_box('sfp_galeria', 'Fotos (la primera es la portada)', 'sfp_dibujar_galeria', 'sfp_propiedad', 'normal', 'default');
    foreach (sfp_campos_auxiliares() as $tipo => $campos) {
        add_meta_box('sfp_aux', 'Datos', static function (WP_Post $post) use ($campos): void {
            wp_nonce_field('sfp_guardar', 'sfp_nonce');
            echo '<div class="sfp-campos">';
            foreach ($campos as $clave => $campo) {
                sfp_dibujar_campo($post->ID, $clave, $campo);
            }
            echo '</div>';
        }, $tipo, 'normal', 'high');
    }
});

/** @param array<string, mixed> $campo */
function sfp_dibujar_campo(int $id, string $clave, array $campo): void
{
    [$etiqueta, $tipo] = $campo;
    $nombre = 'sfp_' . $clave;
    $valor = (string) get_post_meta($id, $nombre, true);
    // Alta nueva: las casillas "mostrar dirección" arrancan tildadas.
    if (!metadata_exists('post', $id, $nombre) && $clave === 'mostrar_direccion') {
        $valor = '1';
    }
    echo '<p class="sfp-campo sfp-campo--' . esc_attr($tipo) . '"><label for="' . esc_attr($nombre) . '"><strong>' . esc_html($etiqueta) . '</strong></label>';
    switch ($tipo) {
        case 'casilla':
            echo '<label class="sfp-casilla"><input type="checkbox" id="' . esc_attr($nombre) . '" name="' . esc_attr($nombre) . '" value="1"' . checked($valor, '1', false) . '> Sí</label>';
            break;
        case 'seleccion':
            echo '<select id="' . esc_attr($nombre) . '" name="' . esc_attr($nombre) . '">';
            foreach ($campo['opciones'] as $v => $t) {
                echo '<option value="' . esc_attr((string) $v) . '"' . selected($valor, (string) $v, false) . '>' . esc_html($t) . '</option>';
            }
            echo '</select>';
            break;
        case 'area':
            echo '<textarea id="' . esc_attr($nombre) . '" name="' . esc_attr($nombre) . '" rows="5" class="large-text">' . esc_textarea($valor) . '</textarea>';
            break;
        case 'fecha':
            echo '<input type="date" id="' . esc_attr($nombre) . '" name="' . esc_attr($nombre) . '" value="' . esc_attr($valor) . '">';
            break;
        case 'numero':
            echo '<input type="text" inputmode="decimal" id="' . esc_attr($nombre) . '" name="' . esc_attr($nombre) . '" value="' . esc_attr($valor) . '">';
            break;
        default:
            echo '<input type="text" class="large-text" id="' . esc_attr($nombre) . '" name="' . esc_attr($nombre) . '" value="' . esc_attr($valor) . '">';
    }
    if (!empty($campo['ayuda'])) {
        echo '<span class="description">' . esc_html($campo['ayuda']) . '</span>';
    }
    echo '</p>';
}

function sfp_dibujar_datos(WP_Post $post): void
{
    wp_nonce_field('sfp_guardar', 'sfp_nonce');
    // Tipo y zona (listas de opciones).
    $elegido = static fn (string $tax): int => (int) (wp_get_object_terms($post->ID, $tax, ['fields' => 'ids'])[0] ?? 0);
    echo '<style>.sfp-campos{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:4px 18px}.sfp-campo{margin:6px 0}.sfp-campo label strong{display:block;margin-bottom:3px}.sfp-campo input[type=text],.sfp-campo select,.sfp-campo input[type=date]{width:100%}.sfp-campo--area,.sfp-campo--casilla{}.sfp-campo .description{display:block;color:#646970;margin-top:2px}.sfp-seccion{margin:16px 0 4px;padding-top:10px;border-top:1px solid #dcdcde;font-size:14px}.sfp-galeria{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0}.sfp-foto{position:relative;width:120px}.sfp-foto img{width:120px;height:90px;object-fit:cover;border-radius:4px;display:block}.sfp-foto button{position:absolute;top:2px;right:2px;background:#fff;border:0;border-radius:50%;width:22px;height:22px;cursor:pointer;line-height:1}.sfp-foto--portada::after{content:"Portada";position:absolute;left:4px;bottom:4px;background:#1d5fa8;color:#fff;font-size:11px;padding:1px 6px;border-radius:3px}</style>';
    echo '<div class="sfp-campos">';
    foreach ([['sfp_tipo', 'Tipo de propiedad'], ['sfp_zona', 'Zona']] as [$tax, $etiqueta]) {
        echo '<p class="sfp-campo"><label for="' . $tax . '"><strong>' . esc_html($etiqueta) . '</strong></label><select id="' . $tax . '" name="' . $tax . '"><option value="0">—</option>';
        foreach (get_terms(['taxonomy' => $tax, 'hide_empty' => false, 'orderby' => 'name']) as $t) {
            echo '<option value="' . (int) $t->term_id . '"' . selected($elegido($tax), $t->term_id, false) . '>' . esc_html($t->name) . '</option>';
        }
        echo '</select></p>';
    }
    echo '</div>';
    foreach (sfp_campos_propiedad() as $seccion) {
        echo '<h3 class="sfp-seccion">' . esc_html($seccion['titulo']) . '</h3><div class="sfp-campos">';
        foreach ($seccion['campos'] as $clave => $campo) {
            sfp_dibujar_campo($post->ID, $clave, $campo);
        }
        echo '</div>';
    }
}

function sfp_dibujar_galeria(WP_Post $post): void
{
    $ids = array_filter(array_map('intval', (array) get_post_meta($post->ID, 'sfp_fotos', true)));
    echo '<input type="hidden" id="sfp_fotos" name="sfp_fotos" value="' . esc_attr(implode(',', $ids)) . '">';
    echo '<div class="sfp-galeria" id="sfp-galeria">';
    foreach ($ids as $id) {
        echo '<div class="sfp-foto" data-id="' . $id . '">' . wp_get_attachment_image($id, [120, 90]) . '<button type="button" aria-label="Quitar foto">×</button></div>';
    }
    echo '</div><p><button type="button" class="button" id="sfp-agregar-fotos">Agregar fotos</button> <span class="description">Arrastrá las fotos para cambiar el orden. La primera es la portada.</span></p>';
}

add_action('admin_enqueue_scripts', static function (string $pantalla): void {
    $actual = get_current_screen();
    if (!$actual || $actual->post_type !== 'sfp_propiedad' || !in_array($pantalla, ['post.php', 'post-new.php'], true)) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('sfp-admin', SFP_URL . 'assets/admin.js', ['jquery', 'jquery-ui-sortable'], SFP_VERSION, true);
});

// ---------------------------------------------------------------- Guardado

add_action('save_post', static function (int $id, WP_Post $post): void {
    if (!isset($_POST['sfp_nonce']) || !wp_verify_nonce((string) $_POST['sfp_nonce'], 'sfp_guardar')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $id)) {
        return;
    }
    $limpio = static fn (string $clave): string => trim(wp_unslash((string) ($_POST['sfp_' . $clave] ?? '')));

    if ($post->post_type === 'sfp_propiedad') {
        $campos = [];
        foreach (sfp_campos_propiedad() as $seccion) {
            $campos += $seccion['campos'];
        }
        foreach ($campos as $clave => $campo) {
            $tipo = $campo[1];
            $valor = $tipo === 'casilla' ? (isset($_POST['sfp_' . $clave]) ? '1' : '0') : $limpio($clave);
            if ($tipo === 'numero') {
                $valor = str_replace(',', '.', $valor);
                $valor = is_numeric($valor) ? $valor : '';
            }
            if ($tipo === 'area') {
                $valor = sanitize_textarea_field($valor);
            } elseif ($tipo === 'seleccion') {
                $valor = array_key_exists($valor, $campo['opciones']) ? $valor : (string) array_key_first($campo['opciones']);
            } elseif ($tipo !== 'casilla') {
                $valor = sanitize_text_field($valor);
            }
            update_post_meta($id, 'sfp_' . $clave, $valor);
        }
        // Reglas del negocio: alquilado solo en alquiler, vendido solo en venta, apto crédito solo en venta.
        $operacion = (string) get_post_meta($id, 'sfp_operacion', true);
        $estado = (string) get_post_meta($id, 'sfp_estado', true);
        if (($estado === 'alquilado' && $operacion !== 'alquiler') || ($estado === 'vendido' && $operacion !== 'venta')) {
            update_post_meta($id, 'sfp_estado', 'disponible');
        }
        if ($operacion !== 'venta') {
            update_post_meta($id, 'sfp_apto_credito', '0');
        }
        // Precio sin moneda no tiene sentido: se asume pesos.
        if ((string) get_post_meta($id, 'sfp_precio', true) !== '' && (string) get_post_meta($id, 'sfp_moneda', true) === '') {
            update_post_meta($id, 'sfp_moneda', 'ARS');
        }
        // Código público: el siguiente libre si no se completó o ya está en uso.
        $codigo = (int) get_post_meta($id, 'sfp_codigo', true);
        if ($codigo <= 0 || sfp_codigo_repetido($id, $codigo)) {
            update_post_meta($id, 'sfp_codigo', sfp_proximo_codigo());
        }
        // La dirección web sale del código y del título: /propiedad/258-local-sobre-av-galicia
        update_post_meta($id, 'sfp_slug', sanitize_title($post->post_title));
        update_post_meta($id, 'sfp_fecha_modificacion', current_time('mysql'));
        $estadoFinal = (string) get_post_meta($id, 'sfp_estado', true);
        if (in_array($estadoFinal, ['alquilado', 'vendido'], true) && !get_post_meta($id, 'sfp_fecha_cierre', true)) {
            update_post_meta($id, 'sfp_fecha_cierre', current_time('mysql'));
        }
        foreach (['sfp_tipo', 'sfp_zona'] as $tax) {
            $termino = (int) ($_POST[$tax] ?? 0);
            wp_set_object_terms($id, $termino > 0 ? [$termino] : [], $tax);
        }
        $fotos = array_values(array_filter(array_map('intval', explode(',', (string) ($_POST['sfp_fotos'] ?? '')))));
        update_post_meta($id, 'sfp_fotos', $fotos);
    } elseif (isset(sfp_campos_auxiliares()[$post->post_type])) {
        foreach (sfp_campos_auxiliares()[$post->post_type] as $clave => $campo) {
            $valor = $campo[1] === 'area' ? sanitize_textarea_field($limpio($clave)) : sanitize_text_field($limpio($clave));
            update_post_meta($id, 'sfp_' . $clave, $valor);
        }
    }
    App\Repositorios\PropiedadRepositorio::olvidar();
}, 10, 2);

add_action('deleted_post', static fn () => App\Repositorios\PropiedadRepositorio::olvidar());

function sfp_codigo_repetido(int $id, int $codigo): bool
{
    $otros = get_posts(['post_type' => 'sfp_propiedad', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'numberposts' => 1, 'exclude' => [$id], 'fields' => 'ids', 'meta_key' => 'sfp_codigo', 'meta_value' => $codigo]);
    return $otros !== [];
}

function sfp_proximo_codigo(): int
{
    global $wpdb;
    $maximo = (int) $wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'sfp_codigo'");
    return $maximo + 1;
}

// ---------------------------------------------------------------- Columnas de la lista de propiedades

add_filter('manage_sfp_propiedad_posts_columns', static function (array $columnas): array {
    return ['cb' => $columnas['cb'], 'sfp_codigo' => 'Código', 'title' => 'Título', 'sfp_operacion' => 'Operación', 'sfp_estado' => 'Estado', 'taxonomy-sfp_tipo' => 'Tipo', 'taxonomy-sfp_zona' => 'Zona', 'sfp_precio' => 'Precio'];
});
add_action('manage_sfp_propiedad_posts_custom_column', static function (string $columna, int $id): void {
    $m = static fn (string $c): string => (string) get_post_meta($id, 'sfp_' . $c, true);
    switch ($columna) {
        case 'sfp_codigo':
            echo '#' . esc_html($m('codigo'));
            break;
        case 'sfp_operacion':
            echo esc_html(ucfirst($m('operacion')));
            break;
        case 'sfp_estado':
            echo esc_html(ucfirst($m('estado'))) . ($m('destacada') === '1' ? ' ★' : '');
            break;
        case 'sfp_precio':
            echo $m('precio') === '' ? 'Consultar' : esc_html(($m('moneda') === 'USD' ? 'U$S ' : '$ ') . number_format((float) $m('precio'), 0, ',', '.'));
            break;
    }
}, 10, 2);

// ---------------------------------------------------------------- Tipo y zona: datos extra

add_action('sfp_tipo_add_form_fields', static function (): void {
    echo '<div class="form-field"><label>Es comercial</label><label><input type="checkbox" name="sfp_es_comercial" value="1"> Aparece en «Comerciales»</label></div><div class="form-field"><label>Orden</label><input type="number" name="sfp_orden" value="0"></div>';
});
add_action('sfp_tipo_edit_form_fields', static function (WP_Term $t): void {
    echo '<tr class="form-field"><th>Es comercial</th><td><label><input type="checkbox" name="sfp_es_comercial" value="1"' . checked(get_term_meta($t->term_id, 'sfp_es_comercial', true), '1', false) . '> Aparece en «Comerciales»</label></td></tr><tr class="form-field"><th>Orden</th><td><input type="number" name="sfp_orden" value="' . esc_attr((string) get_term_meta($t->term_id, 'sfp_orden', true)) . '"></td></tr>';
});
add_action('created_sfp_tipo', 'sfp_guardar_tipo');
add_action('edited_sfp_tipo', 'sfp_guardar_tipo');
function sfp_guardar_tipo(int $id): void
{
    update_term_meta($id, 'sfp_es_comercial', isset($_POST['sfp_es_comercial']) ? '1' : '0');
    update_term_meta($id, 'sfp_orden', (int) ($_POST['sfp_orden'] ?? 0));
    App\Repositorios\PropiedadRepositorio::olvidar();
}

// ---------------------------------------------------------------- Configuración del sitio

/** Claves editables, con etiqueta y tipo (texto, area, modulo). */
function sfp_campos_configuracion(): array
{
    return [
        'Contacto' => [
            'whatsapp_numero'     => ['WhatsApp (formato internacional, ej: 5493424219298)', 'texto'],
            'whatsapp_visible'    => ['WhatsApp tal como se muestra', 'texto'],
            'telefono_fijo'       => ['Teléfono de la oficina', 'texto'],
            'email'               => ['Email', 'texto'],
            'direccion'           => ['Dirección', 'texto'],
            'oficina_coordenadas' => ['Ubicación de la oficina en el mapa de Contacto (ej: -31.6474, -60.7121)', 'texto'],
            'horario'             => ['Horario de atención (el texto que se ve en la web)', 'texto'],
            'horario_dias'        => ['Días de atención para el indicador Abierto/Cerrado (1 = lunes … 7 = domingo, ej: 1-5)', 'texto'],
            'horario_turnos'      => ['Turnos de atención para el indicador Abierto/Cerrado (ej: 8-12, 16-19)', 'texto'],
            'matricula'           => ['Matrículas', 'texto'],
            'instagram_url'       => ['Instagram (enlace)', 'texto'],
        ],
        'Portada' => [
            'eslogan' => ['Título principal', 'texto'],
        ],
        'Alquileres' => [
            'condiciones_alquiler' => ['Condiciones del alquiler para inquilinos (una por línea). Se ven en la ficha de cada alquiler', 'area'],
        ],
        'Analítica' => [
            'ga_measurement_id' => ['ID de Google Analytics 4 (G-XXXXXXX). Vacío = sin medición', 'texto'],
        ],
        'Secciones visibles' => [
            'modulo_mapa'      => ['Mapa con puntos estratégicos', 'modulo'],
            'modulo_paginas'   => ['Páginas Servicios y Nosotros', 'modulo'],
            'modulo_captacion' => ['Alquilá / Vendé con nosotros', 'modulo'],
            'modulo_faq'       => ['Preguntas frecuentes', 'modulo'],
        ],
    ];
}

add_action('admin_menu', static function (): void {
    add_submenu_page('edit.php?post_type=sfp_propiedad', 'Configuración del sitio', 'Configuración del sitio', 'manage_options', 'sfp-configuracion', 'sfp_pagina_configuracion');
});

function sfp_pagina_configuracion(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    if (isset($_POST['sfp_config_nonce']) && wp_verify_nonce((string) $_POST['sfp_config_nonce'], 'sfp_config')) {
        $valores = [];
        foreach (sfp_campos_configuracion() as $grupo) {
            foreach ($grupo as $clave => [$etiqueta, $tipo]) {
                $enviado = wp_unslash((string) ($_POST['cfg_' . $clave] ?? ''));
                $valores[$clave] = match ($tipo) {
                    'modulo' => isset($_POST['cfg_' . $clave]) ? '1' : '0',
                    'area'   => implode("\n", array_filter(array_map('trim', preg_split('/\R/u', sanitize_textarea_field($enviado))))),
                    default  => sanitize_text_field($enviado),
                };
            }
        }
        ConfiguracionRepositorio::guardar($valores);
        echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
    }
    echo '<div class="wrap"><h1>Configuración del sitio</h1><form method="post">';
    wp_nonce_field('sfp_config', 'sfp_config_nonce');
    foreach (sfp_campos_configuracion() as $grupo => $campos) {
        echo '<h2>' . esc_html($grupo) . '</h2><table class="form-table" role="presentation">';
        foreach ($campos as $clave => [$etiqueta, $tipo]) {
            $valor = ConfiguracionRepositorio::get($clave);
            echo '<tr><th scope="row"><label for="cfg_' . esc_attr($clave) . '">' . esc_html($etiqueta) . '</label></th><td>';
            if ($tipo === 'modulo') {
                echo '<label><input type="checkbox" id="cfg_' . esc_attr($clave) . '" name="cfg_' . esc_attr($clave) . '" value="1"' . checked($valor, '1', false) . '> Visible</label>';
            } elseif ($tipo === 'area') {
                echo '<textarea id="cfg_' . esc_attr($clave) . '" name="cfg_' . esc_attr($clave) . '" rows="6" class="large-text">' . esc_textarea($valor) . '</textarea>';
            } else {
                echo '<input type="text" id="cfg_' . esc_attr($clave) . '" name="cfg_' . esc_attr($clave) . '" value="' . esc_attr($valor) . '" class="large-text">';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    }
    submit_button('Guardar cambios');
    echo '</form></div>';
}
