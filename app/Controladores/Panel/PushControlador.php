<?php
declare(strict_types=1);

namespace App\Controladores\Panel;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Sesion;
use App\Servicios\SuscripcionesPush;
use App\Servicios\WebPush;

/**
 * Avisos al teléfono (notificaciones push) para los administradores: activar, desactivar y mandar una de prueba.
 * Los avisos reales los dispara el asistente (AsistenteApiControlador::notificar) cuando hay algo importante.
 */
final class PushControlador
{
    public function suscribir(): void
    {
        $usuario = $this->entrar();
        $datos = $this->cuerpo();
        $ok = (new SuscripcionesPush())->agregar((array) ($datos['suscripcion'] ?? []), (int) $usuario['id'], (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $this->responder($ok ? 200 : 400, $ok ? ['ok' => true] : ['error' => 'Este navegador mandó una suscripción inválida.']);
    }

    public function baja(): void
    {
        $this->entrar();
        (new SuscripcionesPush())->quitar((string) ($this->cuerpo()['endpoint'] ?? ''));
        $this->responder(200, ['ok' => true]);
    }

    /** Manda una notificación de prueba a ese dispositivo, para ver que anda. */
    public function probar(): void
    {
        $this->entrar();
        $endpoint = (string) ($this->cuerpo()['endpoint'] ?? '');
        $suscripciones = new SuscripcionesPush();
        $suscripcion = $suscripciones->todas()[$endpoint] ?? null;
        if ($suscripcion === null) {
            $this->responder(404, ['error' => 'Este dispositivo todavía no está registrado.']);
        }
        $estado = (new WebPush())->enviar($suscripcion, ['titulo' => 'Avisos activados', 'texto' => 'Así te van a llegar los avisos de los clientes.', 'url' => '/panel/chats', 'etiqueta' => 'prueba'], (string) Config::get('push.contacto', 'mailto:avisos@santafepropiedades.com.ar'));
        if ($estado === 404 || $estado === 410) {
            $suscripciones->quitar($endpoint);
        }
        $this->responder($estado >= 200 && $estado < 300 ? 200 : 502, $estado >= 200 && $estado < 300 ? ['ok' => true] : ['error' => 'El servicio de notificaciones del navegador no aceptó el aviso (' . $estado . ').']);
    }

    /** @return array{id: int} */
    private function entrar(): array
    {
        $usuario = Sesion::usuario();
        if ($usuario === null || $usuario['rol'] !== 'administrador') {
            $this->responder(401, ['error' => 'Tenés que volver a ingresar.']);
        }
        if (!hash_equals(Csrf::token(), (string) ($_SERVER['HTTP_X_CSRF'] ?? ''))) {
            $this->responder(419, ['error' => 'La página estuvo abierta mucho tiempo. Recargala.']);
        }
        return $usuario;
    }

    /** @return array<string, mixed> */
    private function cuerpo(): array
    {
        $datos = json_decode((string) file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }

    /** @param array<string, mixed> $datos */
    private function responder(int $status, array $datos): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
