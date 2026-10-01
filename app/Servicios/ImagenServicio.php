<?php
declare(strict_types=1);

namespace App\Servicios;

use RuntimeException;

/**
 * Regla g: las fotos se guardan en WebP, una grande (hasta 1600 px) y una miniatura (hasta 480 px).
 * Los archivos se llaman por la variante ("-grande", "-chica") y no por el ancho, porque una foto
 * de origen chica no se agranda: la medida real queda en la tabla foto.
 * Usa GD, que viene en el hosting compartido; las HEIC de iPhone necesitan Imagick.
 */
final class ImagenServicio
{
    public const ANCHO_GRANDE = 1600;
    public const ANCHO_MINIATURA = 480;
    public const VARIANTE_GRANDE = 'grande';
    public const VARIANTE_MINIATURA = 'chica';
    private const CALIDAD = 80;

    /** Imagick compilado con libheif. Depende del hosting: comprobarlo antes de publicar. En Windows local no viene. */
    public static function admiteHeic(): bool
    {
        return class_exists(\Imagick::class) && \Imagick::queryFormats('HEIC') !== [];
    }

    /** Pasa una HEIC a un JPG temporal, ya enderezado. Quien llama borra el archivo devuelto. */
    public static function convertirHeic(string $origen): string
    {
        $imagen = new \Imagick($origen);
        $imagen->setIteratorIndex(0);
        if (method_exists($imagen, 'autoOrient')) {
            $imagen->autoOrient();
        }
        $imagen->setImageFormat('jpeg');
        $imagen->setImageCompressionQuality(92);
        $temporal = tempnam(sys_get_temp_dir(), 'heic');
        $destino = $temporal . '.jpg';
        $imagen->writeImage($destino);
        $imagen->clear();
        @unlink($temporal);
        return $destino;
    }

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
        imagewebp($grande, $carpetaDestino . '/' . $nombreBase . '-' . self::VARIANTE_GRANDE . '.webp', self::CALIDAD);
        $medidas = ['ancho' => imagesx($grande), 'alto' => imagesy($grande)];

        $miniatura = self::redimensionar($imagen, self::ANCHO_MINIATURA);
        imagewebp($miniatura, $carpetaDestino . '/' . $nombreBase . '-' . self::VARIANTE_MINIATURA . '.webp', self::CALIDAD);

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
