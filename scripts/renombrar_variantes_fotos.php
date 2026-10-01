<?php
declare(strict_types=1);

/*
 * Pasa las fotos ya subidas del nombre viejo ("-1600.webp" / "-480.webp") al nuevo
 * ("-grande.webp" / "-chica.webp"). Se corre una sola vez después de actualizar el código.
 * Si se corre de nuevo no hace nada: solo toca los archivos con el nombre viejo.
 *
 *   php scripts/renombrar_variantes_fotos.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Servicios\ImagenServicio;

$cambios = [
    '-1600.webp' => '-' . ImagenServicio::VARIANTE_GRANDE . '.webp',
    '-480.webp'  => '-' . ImagenServicio::VARIANTE_MINIATURA . '.webp',
];
$renombradas = 0;
foreach (glob(PUBLICO . '/uploads/propiedades/*/*.webp') ?: [] as $archivo) {
    foreach ($cambios as $viejo => $nuevo) {
        if (str_ends_with($archivo, $viejo)) {
            rename($archivo, substr($archivo, 0, -strlen($viejo)) . $nuevo);
            $renombradas++;
        }
    }
}
echo "Listo: $renombradas archivos renombrados.\n";
