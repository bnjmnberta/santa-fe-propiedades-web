<?php
declare(strict_types=1);

namespace App\Servicios;

use App\Modelos\Propiedad;

/** Regla i: el botón abre WhatsApp con el código, el título y el enlace de la propiedad. */
final class EnlaceWhatsApp
{
    public static function paraPropiedad(Propiedad $propiedad, string $numero, string $urlFicha): string
    {
        $texto = sprintf(
            "Hola, me interesa la propiedad #%d — %s\n%s",
            $propiedad->codigo,
            $propiedad->titulo,
            $urlFicha
        );
        return self::general($numero, $texto);
    }

    public static function general(string $numero, string $texto = ''): string
    {
        $numero = preg_replace('/\D+/', '', $numero);
        return 'https://wa.me/' . $numero . ($texto === '' ? '' : '?text=' . rawurlencode($texto));
    }
}
