<?php
declare(strict_types=1);

namespace App\Controladores\Api;

use App\Core\Config;
use App\Modelos\EstadoPropiedad;
use App\Modelos\Operacion;
use App\Repositorios\GestionPropiedadRepositorio;
use App\Servicios\SuscripcionesPush;
use App\Servicios\WebPush;

/**
 * Cambios sencillos que el asistente de WhatsApp hace a pedido de un dueño (ya confirmados por él en el chat):
 * el estado de una propiedad y su precio. Nada más: no borra, no toca textos, fotos ni usuarios.
 *
 * Solo lo llama el asistente, de servidor a servidor, con la clave compartida en X-Asistente-Key (config api.clave).
 * El estado respeta las mismas reglas que el panel (transiciones permitidas, historial) y cada cambio queda en
 * storage/asistente-cambios.log con el valor anterior, para poder deshacerlo.
 */
final class AsistenteApiControlador
{
    /** POST /api/asistente/estado  {codigo, nuevo, quien} */
    public function estado(): void
    {
        $datos = $this->entrar();
        $repositorio = new GestionPropiedadRepositorio();
        $fila = $repositorio->porCodigo((int) ($datos['codigo'] ?? 0));
        if ($fila === null) {
            $this->responder(404, ['error' => 'no_existe', 'mensaje' => 'No existe esa propiedad o ya no está publicada.']);
        }
        $nuevo = EstadoPropiedad::tryFrom((string) ($datos['nuevo'] ?? ''));
        if ($nuevo === null) {
            $this->responder(400, ['error' => 'estado_invalido', 'mensaje' => 'Estado desconocido.']);
        }
        $actual = EstadoPropiedad::from($fila['estado']);
        if ($nuevo === $actual) {
            $this->responder(409, ['error' => 'sin_cambio', 'mensaje' => 'Ya estaba como ' . mb_strtolower($actual->etiqueta()) . '.', 'estado' => $actual->value]);
        }
        $operacion = Operacion::from($fila['operacion']);
        if (!$actual->puedePasarA($nuevo, $operacion, false)) {
            $permitidos = array_map(static fn (EstadoPropiedad $e): string => $e->value, $actual->transicionesPermitidas($operacion, false));
            $this->responder(422, ['error' => 'no_permitido', 'mensaje' => 'Ese cambio de estado no está permitido desde el WhatsApp.', 'estado' => $actual->value, 'permitidos' => $permitidos]);
        }
        $quien = $this->quien($datos);
        $repositorio->cambiarEstado((int) $fila['id_propiedad'], $actual, $nuevo, null, mb_substr('Pedido por WhatsApp de ' . $quien, 0, 200));
        $this->anotar('estado', (int) $fila['codigo'], $quien, $actual->value, $nuevo->value);
        $this->responder(200, ['ok' => true, 'codigo' => (int) $fila['codigo'], 'titulo' => $fila['titulo'], 'anterior' => $actual->value, 'nuevo' => $nuevo->value]);
    }

    /** POST /api/asistente/precio  {codigo, precio, moneda?, quien} */
    public function precio(): void
    {
        $datos = $this->entrar();
        $repositorio = new GestionPropiedadRepositorio();
        $fila = $repositorio->porCodigo((int) ($datos['codigo'] ?? 0));
        if ($fila === null) {
            $this->responder(404, ['error' => 'no_existe', 'mensaje' => 'No existe esa propiedad o ya no está publicada.']);
        }
        $precio = $datos['precio'] ?? null;
        if (!is_numeric($precio) || (float) $precio <= 0 || (float) $precio > 100000000000) {
            $this->responder(400, ['error' => 'precio_invalido', 'mensaje' => 'El precio tiene que ser un número mayor que cero.']);
        }
        $moneda = strtoupper((string) ($datos['moneda'] ?? ($fila['moneda'] ?? '')));
        if (!in_array($moneda, ['ARS', 'USD'], true)) {
            $this->responder(400, ['error' => 'moneda_invalida', 'mensaje' => 'Falta la moneda (pesos o dólares).']);
        }
        $anterior = $fila['precio'] === null ? 'sin precio' : $fila['moneda'] . ' ' . (float) $fila['precio'];
        $quien = $this->quien($datos);
        $repositorio->cambiarPrecio((int) $fila['id_propiedad'], round((float) $precio, 2), $moneda);
        $this->anotar('precio', (int) $fila['codigo'], $quien, $anterior, $moneda . ' ' . round((float) $precio, 2));
        $this->responder(200, ['ok' => true, 'codigo' => (int) $fila['codigo'], 'titulo' => $fila['titulo'], 'anterior' => ['precio' => $fila['precio'] === null ? null : (float) $fila['precio'], 'moneda' => $fila['moneda']], 'nuevo' => ['precio' => round((float) $precio, 2), 'moneda' => $moneda]]);
    }

    /** POST /api/asistente/campo  {codigo, campo: expensas|destacada|descripcion, valor, quien} */
    public function campo(): void
    {
        $datos = $this->entrar();
        $repositorio = new GestionPropiedadRepositorio();
        $fila = $repositorio->porCodigo((int) ($datos['codigo'] ?? 0));
        if ($fila === null) {
            $this->responder(404, ['error' => 'no_existe', 'mensaje' => 'No existe esa propiedad o ya no está publicada.']);
        }
        $campo = (string) ($datos['campo'] ?? '');
        $valor = $datos['valor'] ?? null;
        switch ($campo) {
            case 'expensas':
                if (!is_numeric($valor) || (float) $valor < 0 || (float) $valor > 100000000000) {
                    $this->responder(400, ['error' => 'valor_invalido', 'mensaje' => 'Las expensas tienen que ser un número (0 si no hay).']);
                }
                $valor = round((float) $valor, 2);
                break;
            case 'destacada':
                if (!in_array($valor, [0, 1, true, false, '0', '1'], true)) {
                    $this->responder(400, ['error' => 'valor_invalido', 'mensaje' => 'Destacada es sí o no.']);
                }
                $valor = $valor ? 1 : 0;
                break;
            case 'descripcion':
                $valor = trim((string) $valor);
                if ($valor === '' || mb_strlen($valor) > 3000) {
                    $this->responder(400, ['error' => 'valor_invalido', 'mensaje' => 'La descripción no puede estar vacía ni pasar de 3000 caracteres.']);
                }
                break;
            default:
                $this->responder(400, ['error' => 'campo_no_permitido', 'mensaje' => 'Ese dato no se puede cambiar desde el WhatsApp.']);
        }
        $quien = $this->quien($datos);
        $anterior = $repositorio->cambiarCampo((int) $fila['id_propiedad'], $campo, $valor);
        $this->anotar($campo, (int) $fila['codigo'], $quien, (string) $anterior, (string) $valor);
        $this->responder(200, ['ok' => true, 'codigo' => (int) $fila['codigo'], 'titulo' => $fila['titulo'], 'campo' => $campo, 'anterior' => $anterior, 'nuevo' => $valor]);
    }

    /** POST /api/asistente/notificar  {titulo, texto, url?, etiqueta?}: un aviso push a los celulares de los administradores que lo activaron */
    public function notificar(): void
    {
        $datos = $this->entrar();
        $titulo = mb_substr(trim((string) ($datos['titulo'] ?? '')), 0, 80);
        $texto = mb_substr(trim((string) ($datos['texto'] ?? '')), 0, 300);
        if ($titulo === '' && $texto === '') {
            $this->responder(400, ['error' => 'vacio', 'mensaje' => 'Falta el texto del aviso.']);
        }
        $url = (string) ($datos['url'] ?? '/panel/chats');
        if (!str_starts_with($url, '/panel/')) {
            $url = '/panel/chats';
        }
        $suscripciones = new SuscripcionesPush();
        $push = new WebPush();
        $sujeto = (string) Config::get('push.contacto', 'mailto:avisos@santafepropiedades.com.ar');
        $enviados = $fallidos = 0;
        foreach ($suscripciones->todas() as $endpoint => $suscripcion) {
            try {
                $estado = $push->enviar($suscripcion, ['titulo' => $titulo ?: 'Santa Fe Propiedades', 'texto' => $texto, 'url' => $url, 'etiqueta' => mb_substr((string) ($datos['etiqueta'] ?? 'aviso'), 0, 40)], $sujeto);
            } catch (\Throwable) {
                $estado = 0;
            }
            if ($estado === 404 || $estado === 410) {
                $suscripciones->quitar($endpoint); // el dispositivo ya no existe (se borró la app o se revocó el permiso)
            }
            $estado >= 200 && $estado < 300 ? $enviados++ : $fallidos++;
        }
        $this->responder(200, ['ok' => true, 'enviados' => $enviados, 'fallidos' => $fallidos]);
    }

    /** @return array<string, mixed> el cuerpo JSON, ya con la clave comprobada */
    private function entrar(): array
    {
        $clave = (string) Config::get('api.clave', '');
        if ($clave === '') {
            $this->responder(503, ['error' => 'apagada', 'mensaje' => 'Este acceso no está configurado.']);
        }
        if (!hash_equals($clave, (string) ($_SERVER['HTTP_X_ASISTENTE_KEY'] ?? ''))) {
            $this->responder(401, ['error' => 'clave', 'mensaje' => 'Clave inválida.']);
        }
        $datos = json_decode((string) file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }

    private function quien(array $datos): string
    {
        $quien = trim((string) ($datos['quien'] ?? ''));
        return mb_substr($quien !== '' ? $quien : 'un dueño', 0, 60);
    }

    private function anotar(string $tipo, int $codigo, string $quien, string $antes, string $despues): void
    {
        $carpeta = dirname(__DIR__, 3) . '/storage';
        if (is_dir($carpeta) || @mkdir($carpeta, 0775, true)) {
            @file_put_contents($carpeta . '/asistente-cambios.log', json_encode(['fecha' => date('c'), 'tipo' => $tipo, 'codigo' => $codigo, 'quien' => $quien, 'antes' => $antes, 'despues' => $despues], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
        }
    }

    /** @param array<string, mixed> $datos */
    private function responder(int $status, array $datos): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
