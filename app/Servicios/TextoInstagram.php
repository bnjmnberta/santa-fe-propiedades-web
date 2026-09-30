<?php
declare(strict_types=1);

namespace App\Servicios;

use App\Modelos\Operacion;
use App\Modelos\Propiedad;

/**
 * Arma el texto de la publicación con el formato que ya usa @santafepropiedadesinmobiliaria
 * (docs/ANALISIS.md, sección 16): el equipo carga la propiedad una vez y la publica en los dos lados.
 */
final class TextoInstagram
{
    public static function generar(Propiedad $propiedad, string $urlFicha): string
    {
        $esAlquiler = $propiedad->operacion === Operacion::Alquiler;
        $lineas = [];
        $lineas[] = mb_strtoupper($propiedad->operacion->etiqueta() . ' | ' . $propiedad->titulo);
        $ubicacion = $propiedad->zona ?? $propiedad->direccionVisible();
        if ($ubicacion) {
            $lineas[] = '📍' . mb_strtoupper($ubicacion);
        }
        $lineas[] = '';

        if ($propiedad->dato('referencias')) {
            $lineas[] = $propiedad->dato('referencias');
            $lineas[] = '';
        }

        $items = [];
        if ($propiedad->dato('sup_cubierta')) {
            $items[] = '✔️ ' . numero((float) $propiedad->dato('sup_cubierta')) . ' M2 CUBIERTOS';
        }
        if ($propiedad->dato('dormitorios') !== null) {
            $dormitorios = (int) $propiedad->dato('dormitorios');
            $items[] = '🛏️ ' . ($dormitorios === 0 ? 'MONOAMBIENTE' : $dormitorios . ($dormitorios === 1 ? ' DORMITORIO' : ' DORMITORIOS'));
        }
        if ($propiedad->dato('banos')) {
            $items[] = '🚿 ' . $propiedad->dato('banos') . ((int) $propiedad->dato('banos') === 1 ? ' BAÑO' : ' BAÑOS');
        }
        foreach ($propiedad->caracteristicas() as $caracteristica) {
            $items[] = '✅ ' . $caracteristica;
        }
        if ((int) $propiedad->dato('cocheras') > 0) {
            $items[] = '🚗 COCHERA';
        }
        if ($items) {
            $lineas = [...$lineas, ...$items, ''];
        }

        if ($propiedad->dato('requisitos')) {
            $lineas[] = '📝 Requisitos: ' . $propiedad->dato('requisitos');
        }
        $precio = $propiedad->precio->tieneValor() ? $propiedad->precioTexto() : 'VALOR A CONSULTAR';
        $expensas = $propiedad->dato('expensas_detalle') ? ' | ' . mb_strtoupper($propiedad->dato('expensas_detalle')) : '';
        $lineas[] = '💵 ' . $precio . $expensas;
        $lineas[] = '📲 Más fotos y consultas: ' . $urlFicha;
        $lineas[] = '';
        $lineas[] = '——————';
        $lineas[] = '';

        $hashtags = ['#SantaFePropiedades', $esAlquiler ? '#AlquilerSantaFe' : '#VentaSantaFe', '#SantaFeCapital'];
        if ($propiedad->zona) {
            $hashtags[] = '#' . str_replace(' ', '', ucwords(Texto::slug($propiedad->zona, 40) === '' ? '' : str_replace('-', ' ', Texto::slug($propiedad->zona, 40))));
        }
        $lineas[] = implode(' ', array_filter($hashtags, fn ($h) => $h !== '#'));

        return implode("\n", $lineas);
    }
}
