<?php
declare(strict_types=1);

namespace App\Controladores\Publico;

use App\Core\NoEncontrado;
use App\Core\Vista;
use App\Repositorios\CatalogoRepositorio;
use App\Repositorios\ConfiguracionRepositorio;
use App\Repositorios\PropiedadRepositorio;
use App\Servicios\EnlaceWhatsApp;

final class FichaControlador
{
    public function mostrar(string $codigo, string $slug = ''): void
    {
        $repositorio = new PropiedadRepositorio();
        $propiedad = $repositorio->porCodigo((int) $codigo);
        if ($propiedad === null) {
            throw new NoEncontrado('propiedad ' . $codigo);
        }
        // Una sola URL por ficha: si el slug no coincide (título editado), 301 a la canónica.
        if ($slug !== $propiedad->slug) {
            redirigir($propiedad->url(), 301);
        }

        $urlFicha = url($propiedad->url());
        $descripcion = trim(implode(' · ', array_filter([
            $propiedad->operacion->etiqueta() . ' · ' . $propiedad->tipo,
            $propiedad->ubicacion(),
            $propiedad->precioTexto(),
        ])));

        // "Cerca de": el punto estratégico más próximo de cada categoría, a menos de 2,5 km.
        $cercanos = [];
        $puntos = [];
        if (modulo('mapa') && $propiedad->dato('latitud') !== null) {
            $lat = (float) $propiedad->dato('latitud');
            $lng = (float) $propiedad->dato('longitud');
            $puntos = (new CatalogoRepositorio())->puntosInteres();
            foreach ($puntos as $punto) {
                $metros = distanciaMetros($lat, $lng, $punto['lat'], $punto['lng']);
                if ($metros <= 2500 && (!isset($cercanos[$punto['categoria']]) || $metros < $cercanos[$punto['categoria']]['metros'])) {
                    $cercanos[$punto['categoria']] = $punto + ['metros' => $metros];
                }
            }
            uasort($cercanos, fn (array $a, array $b) => $a['metros'] <=> $b['metros']);
        }

        Vista::render('publico/ficha', [
            'sinFlotante' => true, // la ficha ya tiene su barra de WhatsApp
            'cercanos'    => array_values($cercanos),
            'mapa'        => $puntos ? [
                'propiedades' => [[
                    'codigo'    => $propiedad->codigo,
                    'titulo'    => $propiedad->titulo,
                    'url'       => $propiedad->url(),
                    'precio'    => $propiedad->precioTexto(),
                    'etiqueta'  => $propiedad->etiquetaMapa(),
                    'operacion' => $propiedad->operacion->value,
                    'lat'       => (float) $propiedad->dato('latitud'),
                    'lng'       => (float) $propiedad->dato('longitud'),
                    'foto'      => null,
                ]],
                'puntos'      => $puntos,
                'centrar'     => true,
            ] : null,
            'titulo'      => $propiedad->titulo . ' | Santa Fe Propiedades',
            'descripcion' => $descripcion,
            'canonica'    => $urlFicha,
            'imagenOg'    => $propiedad->urlPortada('grande') ? url($propiedad->urlPortada('grande')) : null,
            'propiedad'   => $propiedad,
            'similares'   => $repositorio->similares($propiedad, 3),
            'whatsapp'    => EnlaceWhatsApp::paraPropiedad(
                $propiedad,
                ConfiguracionRepositorio::get('whatsapp_numero'),
                $urlFicha
            ),
        ]);
    }
}
