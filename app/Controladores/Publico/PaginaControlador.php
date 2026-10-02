<?php
declare(strict_types=1);

namespace App\Controladores\Publico;

use App\Core\NoEncontrado;
use App\Core\Vista;
use App\Repositorios\CatalogoRepositorio;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\HorarioAtencion;
use App\Servicios\ValidadorPropiedad;

/** Páginas institucionales: Contacto, Servicios, Nosotros, captación de propietarios y FAQ. */
final class PaginaControlador
{
    /** Contacto está siempre visible: no depende de ningún módulo. */
    public function contacto(): void
    {
        $oficina = ValidadorPropiedad::coordenadas(Cfg::get('oficina_coordenadas'));
        Vista::render('publico/contacto', [
            'titulo'      => 'Contacto | Santa Fe Propiedades',
            'descripcion' => 'Escribinos por WhatsApp, llamanos o visitanos en ' . Cfg::get('direccion') . '. ' . Cfg::get('horario') . '.',
            'canonica'    => url('/contacto'),
            'horario'     => HorarioAtencion::desdeConfiguracion(),
            'mapa'        => $oficina ? [
                'propiedades' => [],
                'puntos'      => [],
                'centrar'     => true,
                'oficina'     => ['lat' => $oficina[0], 'lng' => $oficina[1], 'nombre' => 'Santa Fe Propiedades', 'direccion' => Cfg::get('direccion')],
            ] : null,
        ]);
    }

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
            'titulo'      => 'Nosotros | Santa Fe Propiedades',
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
