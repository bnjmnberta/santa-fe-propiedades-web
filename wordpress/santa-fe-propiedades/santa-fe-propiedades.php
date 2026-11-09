<?php
/**
 * Plugin Name: Santa Fe Propiedades
 * Description: Sitio de Santa Fe Propiedades (portada, catálogo con filtros y mapa, fichas y páginas institucionales). Las propiedades, servicios y preguntas se administran desde el escritorio de WordPress.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Text Domain: santa-fe-propiedades
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('SFP_DIR', __DIR__);
define('SFP_URL', plugin_dir_url(__FILE__));
define('SFP_VERSION', '1.0.0');

// Mismas clases que la web PHP original: App\Core\Router -> includes/Core/Router.php, etc.
spl_autoload_register(static function (string $clase): void {
    if (!str_starts_with($clase, 'App\\')) {
        return;
    }
    $ruta = SFP_DIR . '/includes/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});

require SFP_DIR . '/includes/helpers.php';
require SFP_DIR . '/includes/datos.php';
require SFP_DIR . '/includes/admin.php';
require SFP_DIR . '/includes/sitio.php';
