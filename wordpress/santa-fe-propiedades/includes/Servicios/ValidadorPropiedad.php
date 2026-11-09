<?php
declare(strict_types=1);

namespace App\Servicios;

/** Lectura de coordenadas escritas a mano (la usa la página Contacto para ubicar la oficina). */
final class ValidadorPropiedad
{
    /**
     * "-31.6396, -60.7132", o un enlace de Google Maps con "@-31.63,-60.71" o "q=-31.63,-60.71".
     * Solo acepta puntos dentro de la zona de Santa Fe y alrededores.
     * @return array{0: float, 1: float}|null
     */
    public static function coordenadas(string $texto): ?array
    {
        if (!preg_match('/(-3\d\.\d+)\s*,\s*(-6\d\.\d+)/', $texto, $m)) {
            return null;
        }
        [$lat, $lng] = [(float) $m[1], (float) $m[2]];
        return $lat > -32.5 && $lat < -30.5 && $lng > -61.5 && $lng < -60 ? [round($lat, 6), round($lng, 6)] : null;
    }
}
