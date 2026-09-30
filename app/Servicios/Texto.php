<?php
declare(strict_types=1);

namespace App\Servicios;

final class Texto
{
    /** "Salón / local en Av. Galicia" → "salon-local-en-av-galicia". */
    public static function slug(string $texto, int $largoMaximo = 150): string
    {
        $ascii = class_exists(\Transliterator::class)
            ? \Transliterator::create('Any-Latin; Latin-ASCII; Lower()')->transliterate($texto)
            : strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $ascii), '-');
        return rtrim(substr($slug, 0, $largoMaximo), '-');
    }

    /** "dpto 1 dorm al frente" → "Dpto 1 dorm al frente" (sin tocar el resto). */
    public static function capitalizar(string $texto): string
    {
        $texto = trim($texto);
        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }
}
