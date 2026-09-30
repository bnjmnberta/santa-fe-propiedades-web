<?php
declare(strict_types=1);

namespace App\Core;

/** Renderiza plantillas PHP de app/Vistas dentro de un layout. */
final class Vista
{
    public static function render(string $plantilla, array $datos = [], ?string $layout = 'layouts/publico'): void
    {
        $contenido = self::capturar($plantilla, $datos);
        echo $layout === null ? $contenido : self::capturar($layout, $datos + ['contenido' => $contenido]);
    }

    public static function parcial(string $plantilla, array $datos = []): string
    {
        return self::capturar('parciales/' . $plantilla, $datos);
    }

    private static function capturar(string $plantilla, array $datos): string
    {
        extract($datos, EXTR_SKIP);
        ob_start();
        require RAIZ . '/app/Vistas/' . $plantilla . '.php';
        return (string) ob_get_clean();
    }
}
