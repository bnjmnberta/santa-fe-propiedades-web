<?php
declare(strict_types=1);

namespace App\Controladores\Panel;

use App\Core\Csrf;
use App\Core\Sesion;
use App\Core\Vista;
use App\Repositorios\UsuarioRepositorio;

final class AccesoControlador
{
    public function formulario(): void
    {
        if (Sesion::usuario() !== null) {
            redirigir('/panel');
        }
        Vista::render('panel/ingresar', ['titulo' => 'Ingresar al panel'], 'layouts/panel');
    }

    public function ingresar(): void
    {
        Csrf::verificar();
        $email = trim((string) ($_POST['email'] ?? ''));
        $resultado = (new UsuarioRepositorio())->autenticar($email, (string) ($_POST['contrasena'] ?? ''));

        if ($resultado['resultado'] === 'ok') {
            $usuario = $resultado['usuario'];
            Sesion::ingresar(['id' => (int) $usuario['id_usuario'], 'nombre' => $usuario['nombre'], 'rol' => $usuario['rol']]);
            // Solo se vuelve a rutas internas del panel (evita redirecciones abiertas).
            $volver = (string) ($_POST['volver'] ?? '');
            redirigir(str_starts_with($volver, '/panel') && !str_starts_with($volver, '//') ? $volver : '/panel');
        }

        Sesion::avisar('error', $resultado['resultado'] === 'bloqueado'
            ? 'Demasiados intentos. Por seguridad, la cuenta queda bloqueada ' . UsuarioRepositorio::MINUTOS_BLOQUEO . ' minutos.'
            : 'El email o la contraseña no son correctos.');
        Vista::render('panel/ingresar', ['titulo' => 'Ingresar al panel', 'email' => $email], 'layouts/panel');
    }

    public function salir(): void
    {
        Csrf::verificar();
        Sesion::cerrar();
        redirigir('/panel/ingresar');
    }
}
