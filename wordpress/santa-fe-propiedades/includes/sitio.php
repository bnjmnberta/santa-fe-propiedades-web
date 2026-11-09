<?php
declare(strict_types=1);

// Sitio público: las direcciones de la web (/, /alquileres, /propiedad/258-..., /contacto...) las atiende este plugin
// con las mismas vistas de la web original. Lo que no coincide (escritorio, accesos, API) sigue su curso normal en WordPress.

use App\Controladores\Publico\CatalogoControlador;
use App\Controladores\Publico\FichaControlador;
use App\Controladores\Publico\InicioControlador;
use App\Controladores\Publico\PaginaControlador;
use App\Controladores\Publico\RedireccionControlador;
use App\Core\NoEncontrado;
use App\Core\Router;
use App\Core\Vista;

// Fotos: la grande es la original subida (1600 px) y la chica de 480 px para tarjetas y mapa.
add_action('after_setup_theme', static function (): void {
    add_image_size('sfp-chica', 480, 9999, false);
});
add_filter('intermediate_image_sizes_advanced', static function (array $tamanios): array {
    return array_intersect_key($tamanios, ['thumbnail' => 1, 'sfp-chica' => 1]);
});
add_filter('big_image_size_threshold', '__return_false');

function sfp_router(): Router
{
    static $router = null;
    if ($router === null) {
        $router = new Router();
        $router->get('/', [InicioControlador::class, 'mostrar']);
        $router->get('/propiedades', [CatalogoControlador::class, 'listar']);
        $router->get('/{seccion:ventas|alquileres|comerciales}', [CatalogoControlador::class, 'listar']);
        $router->get('/propiedad/{codigo:\d+}-{slug:[a-z0-9-]+}', [FichaControlador::class, 'mostrar']);
        $router->get('/propiedad/{codigo:\d+}', [FichaControlador::class, 'mostrar']);
        $router->get('/servicios', [PaginaControlador::class, 'servicios']);
        $router->get('/nosotros', [PaginaControlador::class, 'empresa']);
        $router->get('/la-empresa', [RedireccionControlador::class, 'nosotros']);
        $router->get('/{operacion:alquila|vende}-con-nosotros', [PaginaControlador::class, 'captacion']);
        $router->get('/preguntas-frecuentes', [PaginaControlador::class, 'preguntasFrecuentes']);
        $router->get('/contacto', [PaginaControlador::class, 'contacto']);
        // Direcciones de la web vieja, redirigidas con 301 para no perder enlaces ni posicionamiento.
        $router->get('/descripcion.php', [RedireccionControlador::class, 'fichaVieja']);
        $router->get('/resultados.php', [RedireccionControlador::class, 'listadoViejo']);
        $router->get('/{pagina:empresa|servicios|contacto}.php', [RedireccionControlador::class, 'paginaVieja']);
    }
    return $router;
}

// Antes de que WordPress decida qué mostrar (y de su redirección a "direcciones canónicas").
add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots()) {
        return;
    }
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $router = sfp_router();
    if (!$router->atiende($metodo, $uri)) {
        // Dirección que no existe en ninguna parte: la misma página de error del sitio, no la del tema de WordPress.
        if (is_404()) {
            status_header(404);
            Vista::render('publico/error', [
                'titulo'  => 'Página no encontrada | Santa Fe Propiedades',
                'mensaje' => 'La página que buscás no existe o la propiedad ya no está publicada.',
            ]);
            exit;
        }
        return;
    }
    // WordPress agrega barras a $_GET ("O\'Brien"); las vistas esperan el texto tal cual.
    $_GET = wp_unslash($_GET);
    // WordPress no conoce estas direcciones y ya marcó la respuesta como 404: acá se corrige.
    global $wp_query;
    $wp_query->is_404 = false;
    status_header(200);
    try {
        $router->despachar($metodo, $uri);
    } catch (NoEncontrado) {
        status_header(404);
        Vista::render('publico/error', [
            'titulo'  => 'Página no encontrada | Santa Fe Propiedades',
            'mensaje' => 'La página que buscás no existe o la propiedad ya no está publicada.',
        ]);
    }
    exit;
}, 1);
