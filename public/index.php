<?php
declare(strict_types=1);

// Servidor embebido de PHP (desarrollo): los archivos que existen se sirven directo.
// Fuentes, estilos, scripts e imágenes van con un año de caché, como hace public/.htaccess en
// el hosting. Sin eso, el navegador baja las fuentes en cada página y la letra parpadea.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($archivo) && !str_contains($archivo, '..')) {
        $tipos = [
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'text/javascript; charset=utf-8',
            'webp'  => 'image/webp',
            'svg'   => 'image/svg+xml',
        ];
        $tipo = $tipos[strtolower(pathinfo($archivo, PATHINFO_EXTENSION))] ?? null;
        if ($tipo === null) {
            return false;
        }
        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . filesize($archivo));
        header('Cache-Control: public, max-age=31536000');
        readfile($archivo);
        return true;
    }
}

define('PUBLICO', __DIR__);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\BaseNoDisponible;
use App\Core\Config;
use App\Core\NoEncontrado;
use App\Core\Router;
use App\Core\Vista;

$router = new Router();
require RAIZ . '/app/rutas.php';

try {
    $router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (NoEncontrado) {
    http_response_code(404);
    Vista::render('publico/error', [
        'titulo'  => 'Página no encontrada | Santa Fe Propiedades',
        'mensaje' => 'La página que buscás no existe o la propiedad ya no está publicada.',
    ]);
} catch (BaseNoDisponible $error) {
    // Página propia y mínima: el diseño general lee la configuración de la base.
    error_log((string) $error);
    http_response_code(503);
    header('Retry-After: 120');
    $enLocal = Config::get('app.entorno') !== 'produccion';
    require RAIZ . '/app/Vistas/publico/sin-base.php';
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    if (Config::get('app.entorno') !== 'produccion') {
        throw $error;
    }
    Vista::render('publico/error', [
        'titulo'  => 'Error | Santa Fe Propiedades',
        'mensaje' => 'Tuvimos un problema al cargar la página. Probá de nuevo en unos minutos.',
    ]);
}
