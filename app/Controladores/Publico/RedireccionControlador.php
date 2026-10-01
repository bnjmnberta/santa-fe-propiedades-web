<?php
declare(strict_types=1);

namespace App\Controladores\Publico;

use App\Repositorios\PropiedadRepositorio;

/**
 * URLs de la web vieja (santafe-propiedades.com.ar) redirigidas con 301, para no perder
 * los enlaces compartidos en Instagram y WhatsApp ni el posicionamiento en Google.
 */
final class RedireccionControlador
{
    /** descripcion.php?id=258 → /propiedad/258-local-sobre-av-galicia */
    public function fichaVieja(): void
    {
        $codigo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $propiedad = $codigo ? (new PropiedadRepositorio())->porCodigo($codigo) : null;
        redirigir($propiedad?->url() ?? '/propiedades', 301);
    }

    /** resultados.php?tipo=V|A → /ventas o /alquileres */
    public function listadoViejo(): void
    {
        $destino = match ($_GET['tipo'] ?? null) {
            'V'     => '/ventas',
            'A'     => '/alquileres',
            default => '/propiedades',
        };
        redirigir($destino, 301);
    }

    public function paginaVieja(string $pagina): void
    {
        redirigir(match ($pagina) {
            'empresa'   => modulo('paginas') ? '/la-empresa' : '/',
            'servicios' => modulo('paginas') ? '/servicios' : '/',
            default     => '/contacto',
        }, 301);
    }
}
