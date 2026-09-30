<?php
declare(strict_types=1);

namespace App\Core;

/** Sesión del panel: cookie segura, usuario logueado y mensajes de una sola vez ("flash"). */
final class Sesion
{
    private const INACTIVIDAD_MAXIMA = 60 * 60 * 8; // 8 horas sin actividad cierran la sesión

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('sfp_panel');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $ultimaActividad = $_SESSION['ultima_actividad'] ?? time();
        if (time() - $ultimaActividad > self::INACTIVIDAD_MAXIMA) {
            self::cerrar();
            session_start();
        }
        $_SESSION['ultima_actividad'] = time();
    }

    /** @param array{id: int, nombre: string, rol: string} $usuario */
    public static function ingresar(array $usuario): void
    {
        self::iniciar();
        session_regenerate_id(true); // evita fijación de sesión
        $_SESSION['usuario'] = $usuario;
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $cookie['path'], '', $cookie['secure'], $cookie['httponly']);
            session_destroy();
        }
    }

    /** @return array{id: int, nombre: string, rol: string}|null */
    public static function usuario(): ?array
    {
        self::iniciar();
        return $_SESSION['usuario'] ?? null;
    }

    /** @return array{id: int, nombre: string, rol: string} */
    public static function exigirUsuario(): array
    {
        $usuario = self::usuario();
        if ($usuario === null) {
            redirigir('/panel/ingresar?volver=' . rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '/panel')));
        }
        return $usuario;
    }

    /** Regla l: usuarios, configuración y revertir un Vendido son solo del Administrador. */
    public static function exigirAdministrador(): array
    {
        $usuario = self::exigirUsuario();
        if ($usuario['rol'] !== 'administrador') {
            http_response_code(403);
            self::avisar('error', 'Esa sección es solo para administradores.');
            redirigir('/panel');
        }
        return $usuario;
    }

    public static function esAdministrador(): bool
    {
        return (self::usuario()['rol'] ?? null) === 'administrador';
    }

    public static function avisar(string $tipo, string $mensaje): void
    {
        self::iniciar();
        $_SESSION['avisos'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /** @return list<array{tipo: string, mensaje: string}> */
    public static function tomarAvisos(): array
    {
        self::iniciar();
        $avisos = $_SESSION['avisos'] ?? [];
        unset($_SESSION['avisos']);
        return $avisos;
    }
}
