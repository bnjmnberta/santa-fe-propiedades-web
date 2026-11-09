<?php
declare(strict_types=1);

namespace App\Repositorios;

/** Datos editables desde Propiedades > Configuración del sitio (WhatsApp, teléfonos, horario, secciones...). */
final class ConfiguracionRepositorio
{
    private const OPCION = 'sfp_config';

    private static ?array $valores = null;

    public static function get(string $clave, string $defecto = ''): string
    {
        self::$valores ??= (array) get_option(self::OPCION, []);
        return (string) (self::$valores[$clave] ?? $defecto);
    }

    /** @param array<string, string> $valores */
    public static function guardar(array $valores): void
    {
        update_option(self::OPCION, $valores, false);
        self::$valores = $valores;
    }
}
