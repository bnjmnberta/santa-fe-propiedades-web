<?php
declare(strict_types=1);

namespace App\Core;

/** Valores de config/config.php, leídos con notación de puntos: Config::get('db.host'). */
final class Config
{
    private static array $valores = [];

    public static function cargar(array $valores): void
    {
        self::$valores = $valores;
    }

    public static function get(string $clave, mixed $defecto = null): mixed
    {
        $valor = self::$valores;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $defecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
