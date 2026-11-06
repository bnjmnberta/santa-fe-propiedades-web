<?php
declare(strict_types=1);

namespace App\Servicios;

/**
 * Los dispositivos (celulares, computadoras) de los administradores que activaron los avisos del panel.
 * Se guardan en storage/push-suscripciones.json (no hace falta tocar la base de datos).
 */
final class SuscripcionesPush
{
    private const MAXIMO = 50;

    private string $archivo;

    public function __construct(?string $archivo = null)
    {
        $this->archivo = $archivo ?? dirname(__DIR__, 2) . '/storage/push-suscripciones.json';
    }

    /** @return array<string, array{endpoint: string, claves: array{p256dh: string, auth: string}, usuario: ?int, dispositivo: string, desde: string}> por dirección */
    public function todas(): array
    {
        $datos = is_file($this->archivo) ? json_decode((string) file_get_contents($this->archivo), true) : [];
        return is_array($datos) ? $datos : [];
    }

    /** @param array<string, mixed> $suscripcion lo que entrega el navegador: endpoint y keys (p256dh, auth) */
    public function agregar(array $suscripcion, ?int $usuario, string $dispositivo): bool
    {
        $endpoint = (string) ($suscripcion['endpoint'] ?? '');
        $p256dh = (string) ($suscripcion['keys']['p256dh'] ?? '');
        $auth = (string) ($suscripcion['keys']['auth'] ?? '');
        if (!str_starts_with($endpoint, 'https://') || strlen($endpoint) > 600 || $p256dh === '' || $auth === '') {
            return false;
        }
        $this->modificar(function (array $todas) use ($endpoint, $p256dh, $auth, $usuario, $dispositivo): array {
            $todas[$endpoint] = ['endpoint' => $endpoint, 'claves' => ['p256dh' => $p256dh, 'auth' => $auth], 'usuario' => $usuario, 'dispositivo' => mb_substr($dispositivo, 0, 120), 'desde' => date('c')];
            return array_slice($todas, -self::MAXIMO, null, true);
        });
        return true;
    }

    public function quitar(string $endpoint): void
    {
        $this->modificar(static function (array $todas) use ($endpoint): array {
            unset($todas[$endpoint]);
            return $todas;
        });
    }

    /** @param callable(array): array $cambio */
    private function modificar(callable $cambio): void
    {
        $carpeta = dirname($this->archivo);
        if (!is_dir($carpeta)) {
            @mkdir($carpeta, 0775, true);
        }
        $manija = fopen($this->archivo, 'c+');
        if ($manija === false) {
            return;
        }
        flock($manija, LOCK_EX);
        $actual = json_decode((string) stream_get_contents($manija), true);
        $nuevo = $cambio(is_array($actual) ? $actual : []);
        ftruncate($manija, 0);
        rewind($manija);
        fwrite($manija, (string) json_encode($nuevo));
        fflush($manija);
        flock($manija, LOCK_UN);
        fclose($manija);
        @chmod($this->archivo, 0600);
    }
}
