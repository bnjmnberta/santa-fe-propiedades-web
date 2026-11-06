<?php
declare(strict_types=1);

namespace App\Servicios;

use App\Core\Config;

/**
 * Habla con el asistente de WhatsApp (el bot) para mostrar sus chats en el panel y escribir desde ahí.
 * La clave compartida (config > asistente > clave) vive solo en el servidor: el navegador nunca la ve.
 */
final class AsistenteCliente
{
    public static function configurado(): bool
    {
        return (string) Config::get('asistente.url', '') !== '' && (string) Config::get('asistente.clave', '') !== '';
    }

    /**
     * @param array<string, scalar> $consulta
     * @param array<string, mixed>|null $cuerpo
     * @return array{status: int, datos: array<string, mixed>} status 0 si no se pudo conectar
     */
    public function pedir(string $metodo, string $accion, array $consulta = [], ?array $cuerpo = null): array
    {
        if (!self::configurado()) {
            return ['status' => 503, 'datos' => ['error' => 'El asistente todavía no está conectado al panel.']];
        }
        $direccion = (string) Config::get('asistente.url') . '?' . http_build_query(['accion' => $accion] + $consulta);
        $cabeceras = ['X-Panel-Key: ' . (string) Config::get('asistente.clave'), 'Accept: application/json'];
        if ($cuerpo !== null) {
            $cabeceras[] = 'Content-Type: application/json';
        }
        $conexion = curl_init($direccion);
        curl_setopt_array($conexion, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER     => $cabeceras,
            CURLOPT_POSTFIELDS     => $cuerpo !== null ? json_encode($cuerpo, JSON_UNESCAPED_UNICODE) : null,
        ]);
        $respuesta = curl_exec($conexion);
        $status = (int) curl_getinfo($conexion, CURLINFO_RESPONSE_CODE);
        curl_close($conexion);
        if ($respuesta === false || $status === 0) {
            return ['status' => 0, 'datos' => ['error' => 'No se pudo conectar con el asistente.']];
        }
        $datos = json_decode((string) $respuesta, true);
        return ['status' => $status, 'datos' => is_array($datos) ? $datos : ['error' => 'Respuesta inesperada del asistente.']];
    }

    /**
     * Manda un audio grabado en el panel al asistente (formulario con archivo, no JSON).
     * @param array<string, scalar> $campos
     * @return array{status: int, datos: array<string, mixed>}
     */
    public function subirAudio(array $campos, string $rutaArchivo): array
    {
        if (!self::configurado()) {
            return ['status' => 503, 'datos' => ['error' => 'El asistente todavía no está conectado al panel.']];
        }
        $conexion = curl_init((string) Config::get('asistente.url') . '?' . http_build_query(['accion' => 'enviar_audio']));
        curl_setopt_array($conexion, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER     => ['X-Panel-Key: ' . (string) Config::get('asistente.clave'), 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => $campos + ['audio' => new \CURLFile($rutaArchivo, 'application/octet-stream', 'audio')],
        ]);
        $respuesta = curl_exec($conexion);
        $status = (int) curl_getinfo($conexion, CURLINFO_RESPONSE_CODE);
        curl_close($conexion);
        if ($respuesta === false || $status === 0) {
            return ['status' => 0, 'datos' => ['error' => 'No se pudo conectar con el asistente.']];
        }
        $datos = json_decode((string) $respuesta, true);
        return ['status' => $status, 'datos' => is_array($datos) ? $datos : ['error' => 'Respuesta inesperada del asistente.']];
    }
}
