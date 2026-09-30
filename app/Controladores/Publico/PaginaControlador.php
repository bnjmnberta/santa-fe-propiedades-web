<?php
declare(strict_types=1);

namespace App\Controladores\Publico;

use App\Core\NoEncontrado;
use App\Core\Vista;
use App\Repositorios\CatalogoRepositorio;

/** Páginas institucionales del boceto: Servicios, La Empresa, captación de propietarios y FAQ. */
final class PaginaControlador
{
    public function servicios(): void
    {
        $this->exigir('paginas');
        $catalogo = new CatalogoRepositorio();
        Vista::render('publico/servicios', [
            'titulo'      => 'Servicios inmobiliarios en Santa Fe | Santa Fe Propiedades',
            'descripcion' => 'Tasaciones, administración de alquileres, asesoramiento en compraventa y difusión de propiedades en Santa Fe Capital.',
            'servicios'   => $catalogo->servicios(),
            'motivos'     => $catalogo->motivos(),
            'faqs'        => modulo('faq') ? $catalogo->preguntasFrecuentes() : [],
        ]);
    }

    public function empresa(): void
    {
        $this->exigir('paginas');
        Vista::render('publico/empresa', [
            'titulo'      => 'La empresa | Santa Fe Propiedades',
            'descripcion' => 'Inmobiliaria en Santa Fe Capital desde 2007, con un equipo que viene de más de 30 años en la construcción.',
            'motivos'     => (new CatalogoRepositorio())->motivos(),
        ]);
    }

    public function captacion(string $operacion): void
    {
        $this->exigir('captacion');
        $esAlquiler = $operacion === 'alquila';
        Vista::render('publico/captacion', [
            'titulo'      => ($esAlquiler ? 'Alquilá tu propiedad con nosotros' : 'Vendé tu propiedad con nosotros') . ' | Santa Fe Propiedades',
            'descripcion' => $esAlquiler
                ? 'Publicamos, buscamos inquilinos y administramos tu propiedad en alquiler en Santa Fe.'
                : 'Tasamos, difundimos y te asesoramos en la venta de tu propiedad en Santa Fe.',
            'esAlquiler'  => $esAlquiler,
            'motivos'     => (new CatalogoRepositorio())->motivos(),
        ]);
    }

    public function preguntasFrecuentes(): void
    {
        $this->exigir('faq');
        Vista::render('publico/faq', [
            'titulo'      => 'Preguntas frecuentes | Santa Fe Propiedades',
            'descripcion' => 'Cómo consultar, requisitos para alquilar, visitas, tasaciones y horarios de Santa Fe Propiedades.',
            'faqs'        => (new CatalogoRepositorio())->preguntasFrecuentes(),
        ]);
    }

    /** Un módulo oculto por configuración responde 404, como si no existiera. */
    private function exigir(string $modulo): void
    {
        if (!modulo($modulo)) {
            throw new NoEncontrado($modulo);
        }
    }
}
