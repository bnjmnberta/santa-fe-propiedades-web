<?php
declare(strict_types=1);

// Arranque común para la web (public/index.php) y los scripts de consola.

define('RAIZ', dirname(__DIR__));
if (!defined('PUBLICO')) {
    define('PUBLICO', RAIZ . '/public');
}

spl_autoload_register(function (string $clase): void {
    if (!str_starts_with($clase, 'App\\')) {
        return;
    }
    $ruta = RAIZ . '/app/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});

require RAIZ . '/app/helpers.php';

App\Core\Config::cargar(require RAIZ . '/config/config.php');

date_default_timezone_set('America/Argentina/Buenos_Aires');
error_reporting(E_ALL);
ini_set('display_errors', App\Core\Config::get('app.entorno') === 'produccion' ? '0' : '1');
