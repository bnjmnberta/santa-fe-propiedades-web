<?php
declare(strict_types=1);

namespace App\Controladores\Panel;

use App\Core\Csrf;
use App\Core\Sesion;
use App\Core\Vista;
use App\Servicios\AsistenteCliente;

/**
 * Chats de WhatsApp del asistente, como en WhatsApp: se ven las charlas con los clientes, se puede escribir y se puede
 * quitar o devolver el chat al asistente. Solo el Administrador (son conversaciones de clientes).
 * Los datos viven en el asistente; acá solo se pide y se muestra.
 */
final class ChatsControlador
{
    public function inicio(): void
    {
        Sesion::exigirAdministrador();
        Vista::render('panel/chats', [
            'titulo'      => 'Chats',
            'conectado'   => AsistenteCliente::configurado(),
            'token'       => Csrf::token(),
            'pushClave'   => AsistenteCliente::configurado() ? $this->claveDeAvisos() : '',
        ], 'layouts/panel');
    }

    /** La clave pública para los avisos al teléfono (se crea la primera vez). Vacía si no se pueden crear: el panel anda igual, sin avisos. */
    private function claveDeAvisos(): string
    {
        try {
            return (new \App\Servicios\WebPush())->claves()['publica'];
        } catch (\Throwable) {
            return '';
        }
    }

    /** Busca dentro de lo que se dijeron en los chats (nombre y número los filtra el navegador). */
    public function buscar(): void
    {
        $this->exigirParaJson();
        $this->responder((new AsistenteCliente())->pedir('GET', 'buscar', ['q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80)]));
    }

    public function lista(): void
    {
        $this->exigirParaJson();
        $this->responder((new AsistenteCliente())->pedir('GET', 'chats'));
    }

    public function mensajes(string $contacto): void
    {
        $this->exigirParaJson();
        $this->responder((new AsistenteCliente())->pedir('GET', 'mensajes', ['contacto' => $contacto, 'desde' => (int) ($_GET['desde'] ?? 0)]));
    }

    public function enviar(string $contacto): void
    {
        $this->exigirParaJson(true);
        $texto = trim((string) ($_POST['texto'] ?? ''));
        if ($texto === '') {
            $this->responder(['status' => 400, 'datos' => ['error' => 'Escribí un mensaje.']]);
        }
        $this->responder((new AsistenteCliente())->pedir('POST', 'enviar', [], ['contacto' => $contacto, 'texto' => $texto]));
    }

    /** Nota de voz grabada en el navegador: se reenvía tal cual al asistente, que la sube a WhatsApp. */
    public function audio(string $contacto): void
    {
        $this->exigirParaJson(true);
        $archivo = $_FILES['audio'] ?? null;
        if (!is_array($archivo) || (int) $archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $archivo['tmp_name'])) {
            $this->responder(['status' => 400, 'datos' => ['error' => 'No llegó el audio. Probá grabarlo de nuevo.']]);
        }
        if ((int) $archivo['size'] > 8 * 1024 * 1024) {
            $this->responder(['status' => 413, 'datos' => ['error' => 'El audio es demasiado largo.']]);
        }
        $this->responder((new AsistenteCliente())->subirAudio(
            ['contacto' => $contacto, 'duracion' => max(0, min(3600, (int) ($_POST['duracion'] ?? 0)))],
            (string) $archivo['tmp_name']
        ));
    }

    public function modo(string $contacto): void
    {
        $this->exigirParaJson(true);
        $modo = ($_POST['modo'] ?? '') === 'bot' ? 'bot' : 'persona';
        $this->responder((new AsistenteCliente())->pedir('POST', 'modo', [], ['contacto' => $contacto, 'modo' => $modo]));
    }

    public function leer(string $contacto): void
    {
        $this->exigirParaJson(true);
        $this->responder((new AsistenteCliente())->pedir('POST', 'leer', [], ['contacto' => $contacto]));
    }

    /** Para los pedidos que hace el JavaScript de la página: sin sesión o con el token vencido, responde JSON (no redirige). */
    private function exigirParaJson(bool $escritura = false): void
    {
        $usuario = Sesion::usuario();
        if ($usuario === null || $usuario['rol'] !== 'administrador') {
            $this->responder(['status' => 401, 'datos' => ['error' => 'Tenés que volver a ingresar.']]);
        }
        if ($escritura && !hash_equals(Csrf::token(), (string) ($_POST['_csrf'] ?? ''))) {
            $this->responder(['status' => 419, 'datos' => ['error' => 'La página estuvo abierta mucho tiempo. Recargala.']]);
        }
    }

    /** @param array{status: int, datos: array<string, mixed>} $resultado */
    private function responder(array $resultado): never
    {
        // Si el asistente rechaza la clave (401), no se muestra eso como "sesión vencida" del panel.
        $status = $resultado['status'];
        if ($status === 401 && isset($resultado['datos']['error']) && $resultado['datos']['error'] === 'Clave inválida.') {
            $status = 502;
            $resultado['datos'] = ['error' => 'El panel y el asistente no tienen la misma clave. Avisá a Tars.'];
        }
        http_response_code($status === 0 ? 502 : $status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($resultado['datos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
