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

        Vista::render('publico/inicio', [
            'titulo'      => 'Santa Fe Propiedades | Inmobiliaria en Santa Fe Capital',
            'descripcion' => 'Alquileres, ventas y locales comerciales en Santa Fe Capital. Tasaciones y administración de alquileres desde 2007.',
            'canonica'    => url('/'),
            'alquileres'  => $alquileres,
            'ventas'      => $ventas,
            'motivos'     => $catalogo->motivos(),
        ]);
    }
}
