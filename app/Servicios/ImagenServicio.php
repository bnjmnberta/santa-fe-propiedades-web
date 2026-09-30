<?php
declare(strict_types=1);

namespace App\Servicios;

use RuntimeException;

/**
 * Regla g: las fotos se guardan en WebP, una grande (hasta 1600 px) y una miniatura de 480 px.
 * Usa GD, que viene en el hosting compartido.
 */
final class ImagenServicio
{
    public const ANCHO_GRANDE = 1600;
    public const ANCHO_MINIATURA = 480;
    private const CALIDAD = 80;

    /** @return array{ancho: int, alto: int} Medidas de la versión grande. */
    public static function procesar(string $origen, string $carpetaDestino, string $nombreBase): array
    {
        $datos = @getimagesize($origen);
        $imagen = match ($datos[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($origen),
            IMAGETYPE_PNG  => @imagecreatefrompng($origen),
            IMAGETYPE_WEBP => @imagecreatefromwebp($origen),
            default        => false,
        };
        if ($imagen === false) {
            throw new RuntimeException('Formato de imagen no admitido: ' . basename($origen));
        }
        if (($datos[2] ?? null) === IMAGETYPE_JPEG) {
            $imagen = self::enderezar($imagen, $origen);
        }

        if (!is_dir($carpetaDestino) && !mkdir($carpetaDestino, 0755, true) && !is_dir($carpetaDestino)) {
            throw new RuntimeException('No se pudo crear la carpeta ' . $carpetaDestino);
        }

        $grande = self::redimensionar($imagen, self::ANCHO_GRANDE);
        imagewebp($grande, $carpetaDestino . '/' . $nombreBase . '-' . self::ANCHO_GRANDE . '.webp', self::CALIDAD);
        $medidas = ['ancho' => imagesx($grande), 'alto' => imagesy($grande)];

        $miniatura = self::redimensionar($imagen, self::ANCHO_MINIATURA);
        imagewebp($miniatura, $carpetaDestino . '/' . $nombreBase . '-' . self::ANCHO_MINIATURA . '.webp', self::CALIDAD);

        return $medidas;
    }

    /**
     * Las fotos sacadas con el celular guardan la rotación en EXIF en lugar de rotar los píxeles.
     * Sin esto, una foto vertical del celular aparecería acostada en la web.
     */
    private static function enderezar(\GdImage $imagen, string $origen): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($origen) : false;
        $angulo = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };
        return $angulo === 0 ? $imagen : imagerotate($imagen, $angulo, 0);
    }

    /** Achica sin agrandar: una foto de 800 px queda en 800 aunque se pidan 1600. */
    private static function redimensionar(\GdImage $imagen, int $anchoMaximo): \GdImage
    {
        $ancho = imagesx($imagen);
        if ($ancho <= $anchoMaximo) {
            return $imagen;
        }
        $alto = (int) round(imagesy($imagen) * $anchoMaximo / $ancho);
        $nueva = imagecreatetruecolor($anchoMaximo, $alto);
        imagecopyresampled($nueva, $imagen, 0, 0, 0, 0, $anchoMaximo, $alto, $ancho, imagesy($imagen));
        return $nueva;
    }
}
