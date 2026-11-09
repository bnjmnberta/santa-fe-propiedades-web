<?php
declare(strict_types=1);

/**
 * Importa a WordPress los datos de la web original (propiedades con sus fotos, tipos, zonas, servicios,
 * motivos, preguntas frecuentes, puntos del mapa y configuración).
 *
 * Uso (desde la carpeta de WordPress):
 *   php -c php.ini wp-content/plugins/santa-fe-propiedades/importar.php datos.json carpeta-de-fotos
 *
 * datos.json sale de la base original; la carpeta de fotos es public/uploads/propiedades de la web original
 * (una subcarpeta por código, con {archivo}-grande.webp).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
$json = $argv[1] ?? '';
$fotosDir = rtrim($argv[2] ?? '', '/\\');
if (!is_file($json)) {
    fwrite(STDERR, "Falta el archivo de datos.\n");
    exit(1);
}
define('WP_USE_THEMES', false);
require getcwd() . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

use App\Repositorios\ConfiguracionRepositorio;

$d = json_decode((string) file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);
$usuario = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;
if ($usuario) {
    wp_set_current_user($usuario->ID);
}

// Configuración del sitio
ConfiguracionRepositorio::guardar(array_map('strval', $d['config']));
echo "Configuración: " . count($d['config']) . " valores\n";

// Tipos y zonas
$tipos = [];
foreach ($d['tipos'] as $t) {
    $existente = get_term_by('slug', $t['slug'], 'sfp_tipo');
    $id = $existente ? $existente->term_id : wp_insert_term($t['nombre'], 'sfp_tipo', ['slug' => $t['slug']])['term_id'];
    update_term_meta($id, 'sfp_es_comercial', (string) (int) $t['es_comercial']);
    update_term_meta($id, 'sfp_orden', (int) $t['orden']);
    $tipos[(int) $t['id_tipo']] = (int) $id;
}
$zonas = [];
foreach ($d['zonas'] as $z) {
    $existente = get_term_by('slug', $z['slug'], 'sfp_zona');
    $id = $existente ? $existente->term_id : wp_insert_term($z['nombre'], 'sfp_zona', ['slug' => $z['slug']])['term_id'];
    if ($z['latitud'] !== null) {
        update_term_meta($id, 'sfp_latitud', $z['latitud']);
        update_term_meta($id, 'sfp_longitud', $z['longitud']);
    }
    $zonas[(int) $z['id_zona']] = (int) $id;
}
echo 'Tipos: ' . count($tipos) . ', zonas: ' . count($zonas) . "\n";

/** Crea o reutiliza un contenido por una clave propia (así se puede volver a correr sin duplicar). */
function sfp_importar_post(string $tipo, string $clave, array $datos, array $meta): int
{
    $existentes = get_posts(['post_type' => $tipo, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'sfp_importado', 'meta_value' => $clave]);
    $datos += ['post_type' => $tipo, 'post_status' => 'publish'];
    if ($existentes) {
        $datos['ID'] = $existentes[0];
        wp_update_post($datos);
        $id = $existentes[0];
    } else {
        $id = wp_insert_post($datos, true);
        if (is_wp_error($id)) {
            fwrite(STDERR, $id->get_error_message() . "\n");
            exit(1);
        }
        update_post_meta($id, 'sfp_importado', $clave);
    }
    foreach ($meta as $k => $v) {
        update_post_meta($id, 'sfp_' . $k, $v === null ? '' : $v);
    }
    return (int) $id;
}

// Servicios, motivos, preguntas y puntos del mapa
foreach ($d['servicios'] as $s) {
    if (!(int) $s['activo']) {
        continue;
    }
    sfp_importar_post('sfp_servicio', 's' . $s['id_servicio'], ['post_title' => $s['titulo'], 'post_excerpt' => $s['descripcion'], 'menu_order' => (int) $s['orden']], ['icono' => $s['icono']]);
}
foreach ($d['motivos'] as $m) {
    if (!(int) $m['activo']) {
        continue;
    }
    sfp_importar_post('sfp_motivo', 'm' . $m['id_motivo'], ['post_title' => $m['titulo'], 'menu_order' => (int) $m['orden']], ['dato' => $m['dato'], 'descripcion' => $m['descripcion'], 'icono' => $m['icono']]);
}
foreach ($d['faqs'] as $f) {
    if (!(int) $f['activo']) {
        continue;
    }
    sfp_importar_post('sfp_faq', 'f' . $f['id_faq'], ['post_title' => $f['pregunta'], 'post_content' => $f['respuesta'], 'menu_order' => (int) $f['orden']], ['grupo' => $f['grupo']]);
}
foreach ($d['puntos'] as $p) {
    if (!(int) $p['activo']) {
        continue;
    }
    sfp_importar_post('sfp_punto', 'p' . $p['id_punto'], ['post_title' => $p['nombre']], ['categoria' => $p['categoria'], 'latitud' => $p['latitud'], 'longitud' => $p['longitud']]);
}
echo 'Servicios: ' . count($d['servicios']) . ', motivos: ' . count($d['motivos']) . ', preguntas: ' . count($d['faqs']) . ', puntos: ' . count($d['puntos']) . "\n";

// Fotos de cada propiedad: se suben a la biblioteca de medios (la grande de 1600 px; la chica la genera WordPress)
$fotosPorPropiedad = [];
foreach ($d['fotos'] as $f) {
    $fotosPorPropiedad[(int) $f['id_propiedad']][] = $f;
}
$subidas = 0;
$columnas = ['codigo', 'slug', 'operacion', 'direccion', 'mostrar_direccion', 'moneda', 'precio', 'expensas', 'expensas_detalle', 'requisitos', 'apto_credito', 'disponible_desde', 'amoblado', 'dormitorios', 'banos', 'cocheras', 'sup_cubierta', 'sup_terreno', 'frente_m', 'fondo_m', 'regimen', 'planta', 'ubicacion_unidad', 'caracteristicas', 'servicios', 'referencias', 'descripcion', 'instagram_url', 'latitud', 'longitud', 'estado', 'destacada', 'fecha_modificacion', 'fecha_cierre'];
foreach ($d['propiedades'] as $p) {
    $meta = [];
    foreach ($columnas as $c) {
        $meta[$c] = $p[$c] ?? '';
    }
    $id = sfp_importar_post('sfp_propiedad', 'c' . $p['codigo'], ['post_title' => $p['titulo'], 'post_name' => $p['codigo'] . '-' . $p['slug'], 'post_date' => $p['fecha_alta']], $meta);
    wp_set_object_terms($id, [$tipos[(int) $p['id_tipo']]], 'sfp_tipo');
    wp_set_object_terms($id, isset($zonas[(int) $p['id_zona']]) ? [$zonas[(int) $p['id_zona']]] : [], 'sfp_zona');

    $ids = [];
    foreach ($fotosPorPropiedad[(int) $p['id_propiedad']] ?? [] as $f) {
        $origen = $fotosDir . '/' . $p['codigo'] . '/' . $f['archivo'] . '-grande.webp';
        if (!is_file($origen)) {
            continue;
        }
        $previo = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'sfp_foto_origen', 'meta_value' => $p['codigo'] . '/' . $f['archivo']]);
        if ($previo) {
            $ids[] = (int) $previo[0];
            continue;
        }
        $destino = wp_upload_dir()['path'] . '/propiedad-' . $p['codigo'] . '-' . $f['archivo'] . '.webp';
        copy($origen, $destino);
        $adjunto = wp_insert_attachment([
            'post_mime_type' => 'image/webp',
            'post_title'     => $p['titulo'],
            'post_status'    => 'inherit',
        ], $destino, $id);
        wp_update_attachment_metadata($adjunto, wp_generate_attachment_metadata($adjunto, $destino));
        update_post_meta($adjunto, 'sfp_foto_origen', $p['codigo'] . '/' . $f['archivo']);
        $ids[] = $adjunto;
        $subidas++;
    }
    update_post_meta($id, 'sfp_fotos', $ids);
    echo '.';
}
echo "\nPropiedades: " . count($d['propiedades']) . ", fotos nuevas: $subidas\n";

// Páginas fijas de WordPress que no se usan: se deja la portada como está (la atiende el plugin)
flush_rewrite_rules();
