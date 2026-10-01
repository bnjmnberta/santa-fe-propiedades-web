<?php
declare(strict_types=1);

namespace App\Controladores\Publico;

use App\Core\Vista;
use App\Modelos\Operacion;
use App\Repositorios\CatalogoRepositorio;
use App\Repositorios\PropiedadRepositorio;

final class InicioControlador
{
    public function mostrar(): void
    {
        $catalogo = new CatalogoRepositorio();
        $propiedades = new PropiedadRepositorio();
        $alquileres = $propiedades->destacadas(8, Operacion::Alquiler);
        $ventas = $propiedades->destacadas(8, Operacion::Venta);

        // Fondo difuminado de la portada: la foto de una propiedad destacada. Va la chica: con 10 px
        // de desenfoque no se nota la diferencia y pesa un tercio.
        $fondo = null;
        foreach ([...$alquileres, ...$ventas] as $propiedad) {
            if ($propiedad->urlPortada()) {
                $fondo = $propiedad->urlPortada('chica');
                break;
            }
        }

        Vista::render('publico/inicio', [
            'titulo'      => 'Santa Fe Propiedades | Inmobiliaria en Santa Fe Capital',
            'descripcion' => 'Alquileres, ventas y locales comerciales en Santa Fe Capital. Tasaciones y administración de alquileres desde 2007.',
            'canonica'    => url('/'),
            'alquileres'  => $alquileres,
            'ventas'      => $ventas,
            'fondoPortada' => $fondo,
            'tipos'       => $catalogo->tiposConPublicadas(),
            'zonas'       => $catalogo->zonasConPublicadas(),
            'motivos'     => $catalogo->motivos(),
        ]);
    }
}
