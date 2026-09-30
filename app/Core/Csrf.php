<?php
declare(strict_types=1);

namespace App\Core;

/** Token CSRF por sesión: todo formulario POST del panel lo lleva y se valida antes de actuar. */
final class Csrf
{
    public static function token(): string
    {
        Sesion::iniciar();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verificar(): void
    {
        $recibido = (string) ($_POST['_csrf'] ?? '');
        if ($recibido === '' || !hash_equals(self::token(), $recibido)) {
            http_response_code(419);
            Sesion::avisar('error', 'La página estuvo abierta mucho tiempo. Probá de nuevo.');
            redirigir(paginaAnterior('/panel'));
        }
    }
}
