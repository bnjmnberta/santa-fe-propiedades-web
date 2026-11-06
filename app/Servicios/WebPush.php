<?php
declare(strict_types=1);

namespace App\Servicios;

use RuntimeException;

/**
 * Notificaciones push (Web Push) para el panel, sin librerías: VAPID (RFC 8292) y cifrado del mensaje (RFC 8291, aes128gcm).
 * Las claves VAPID se crean la primera vez y quedan en storage/push-vapid.json (fuera de la carpeta pública).
 */
final class WebPush
{
    private string $archivoClaves;

    public function __construct(?string $carpeta = null)
    {
        $this->archivoClaves = ($carpeta ?? dirname(__DIR__, 2) . '/storage') . '/push-vapid.json';
    }

    public static function b64(string $binario): string
    {
        return rtrim(strtr(base64_encode($binario), '+/', '-_'), '=');
    }

    public static function deB64(string $texto): string
    {
        return (string) base64_decode(strtr($texto, '-_', '+/') . str_repeat('=', (4 - strlen($texto) % 4) % 4), true);
    }

    /** @return array{publica: string, privada: string} publica: punto sin comprimir en base64url (lo que usa el navegador); privada: PEM */
    public function claves(): array
    {
        if (is_file($this->archivoClaves)) {
            $datos = json_decode((string) file_get_contents($this->archivoClaves), true);
            if (is_array($datos) && isset($datos['publica'], $datos['privada'])) {
                return $datos;
            }
        }
        $clave = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($clave === false) {
            throw new RuntimeException('No se pudieron crear las claves de notificación.');
        }
        openssl_pkey_export($clave, $pem);
        $datos = ['publica' => self::b64(self::puntoPublico($clave)), 'privada' => $pem];
        $carpeta = dirname($this->archivoClaves);
        if (!is_dir($carpeta)) {
            @mkdir($carpeta, 0775, true);
        }
        file_put_contents($this->archivoClaves, json_encode($datos), LOCK_EX);
        @chmod($this->archivoClaves, 0600);
        return $datos;
    }

    /** @param \OpenSSLAsymmetricKey $clave */
    private static function puntoPublico($clave): string
    {
        $detalles = openssl_pkey_get_details($clave);
        return "\x04" . str_pad($detalles['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($detalles['ec']['y'], 32, "\0", STR_PAD_LEFT);
    }

    /** Clave pública de un navegador (65 bytes) como clave de OpenSSL. */
    private static function clavePublicaDe(string $punto)
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $punto;
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $clave = openssl_pkey_get_public($pem);
        if ($clave === false) {
            throw new RuntimeException('Clave del navegador inválida.');
        }
        return $clave;
    }

    private static function hkdf(string $sal, string $material, string $info, int $largo): string
    {
        return hash_hkdf('sha256', $material, $largo, $info, $sal);
    }

    /**
     * Cifra el mensaje para un navegador (RFC 8291). Con $efimera y $sal fijos sirve para pruebas; sin ellos son aleatorios.
     * @return string el cuerpo del pedido (cabecera aes128gcm + mensaje cifrado)
     */
    public function cifrar(string $mensaje, string $clavePublicaNavegador, string $secretoAutenticacion, $efimera = null, ?string $sal = null): string
    {
        $efimera ??= openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $sal ??= random_bytes(16);
        $miPunto = self::puntoPublico($efimera);
        $compartido = openssl_pkey_derive(self::clavePublicaDe($clavePublicaNavegador), $efimera, 32);
        if ($compartido === false) {
            throw new RuntimeException('No se pudo calcular el secreto compartido.');
        }
        $claveIkm = self::hkdf($secretoAutenticacion, $compartido, "WebPush: info\0" . $clavePublicaNavegador . $miPunto, 32);
        $clave = self::hkdf($sal, $claveIkm, "Content-Encoding: aes128gcm\0", 16);
        $nonce = self::hkdf($sal, $claveIkm, "Content-Encoding: nonce\0", 12);
        $cifrado = openssl_encrypt($mensaje . "\x02", 'aes-128-gcm', $clave, OPENSSL_RAW_DATA, $nonce, $etiqueta, '', 16);
        if ($cifrado === false) {
            throw new RuntimeException('No se pudo cifrar el mensaje.');
        }
        return $sal . pack('N', 4096) . chr(strlen($miPunto)) . $miPunto . $cifrado . $etiqueta;
    }

    /** Firma VAPID (JWT ES256) para el servicio de notificaciones de ese navegador. */
    private function firma(string $endpoint, string $sujeto): string
    {
        $origen = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $cabecera = self::b64((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $cuerpo = self::b64((string) json_encode(['aud' => $origen, 'exp' => time() + 12 * 3600, 'sub' => $sujeto]));
        openssl_sign($cabecera . '.' . $cuerpo, $der, openssl_pkey_get_private($this->claves()['privada']), OPENSSL_ALGO_SHA256);
        return $cabecera . '.' . $cuerpo . '.' . self::b64(self::derARaw($der));
    }

    /** La firma de OpenSSL viene en DER; JWT la quiere como R||S de 64 bytes. */
    private static function derARaw(string $der): string
    {
        $pos = 3; // 30 len 02 lenR ...
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7f;
        }
        $lenR = ord($der[$pos]);
        $r = substr($der, $pos + 1, $lenR);
        $pos += 1 + $lenR + 1; // 02 lenS
        $lenS = ord($der[$pos]);
        $s = substr($der, $pos + 1, $lenS);
        return str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT) . str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
    }

    /**
     * Manda una notificación a una suscripción. Devuelve el estado HTTP (201 = entregada al servicio; 404/410 = la suscripción ya no existe).
     * @param array{endpoint: string, claves: array{p256dh: string, auth: string}} $suscripcion
     * @param array<string, mixed> $datos titulo, texto, url, etiqueta
     */
    public function enviar(array $suscripcion, array $datos, string $sujeto): int
    {
        $cuerpo = $this->cifrar((string) json_encode($datos, JSON_UNESCAPED_UNICODE), self::deB64($suscripcion['claves']['p256dh']), self::deB64($suscripcion['claves']['auth']));
        $conexion = curl_init($suscripcion['endpoint']);
        curl_setopt_array($conexion, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: 86400',
                'Urgency: high',
                'Authorization: vapid t=' . $this->firma($suscripcion['endpoint'], $sujeto) . ', k=' . $this->claves()['publica'],
            ],
        ]);
        curl_exec($conexion);
        $estado = (int) curl_getinfo($conexion, CURLINFO_RESPONSE_CODE);
        curl_close($conexion);
        return $estado;
    }
}
