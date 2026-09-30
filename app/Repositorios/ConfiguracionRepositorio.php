<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\Conexion;

/** Datos editables desde el panel (WhatsApp, teléfonos, horario...). Se leen una vez por solicitud. */
final class ConfiguracionRepositorio
{
    private static ?array $valores = null;

    public static function get(string $clave, string $defecto = ''): string
    {
        if (self::$valores === null) {
            self::$valores = Conexion::obtenerInstancia()->pdo()
                ->query('SELECT clave, valor FROM configuracion')
                ->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        return (string) (self::$valores[$clave] ?? $defecto);
    }
}
