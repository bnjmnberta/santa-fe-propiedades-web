<?php
declare(strict_types=1);

namespace App\Controladores\Panel;

use App\Core\Csrf;
use App\Core\NoEncontrado;
use App\Core\Sesion;
use App\Repositorios\GestionPropiedadRepositorio;
use App\Servicios\ImagenServicio;
use Throwable;

final class FotosControlador
{
    private const TAMANIO_MAXIMO = 8 * 1024 * 1024; // regla g: hasta 8 MB por foto

    /** Sube una o varias fotos juntas; cada una se convierte a WebP grande + miniatura. */
    public function subir(string $id): void
    {
        Sesion::exigirUsuario();
        Csrf::verificar();
        $repositorio = new GestionPropiedadRepositorio();
        $propiedad = $repositorio->porId((int) $id) ?? throw new NoEncontrado("propiedad $id");

        $archivos = $this->normalizar($_FILES['fotos'] ?? []);
        $existentes = count($repositorio->fotos((int) $id));
        $subidas = 0;
        $problemas = [];
        foreach ($archivos as $archivo) {
            if ($existentes + $subidas >= GestionPropiedadRepositorio::MAXIMO_FOTOS) {
                $problemas[] = 'Se llegó al máximo de ' . GestionPropiedadRepositorio::MAXIMO_FOTOS . ' fotos.';
                break;
            }
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $problemas[] = $archivo['name'] . ': no se pudo subir.';
                continue;
            }
            if ($archivo['size'] > self::TAMANIO_MAXIMO) {
                $problemas[] = $archivo['name'] . ': pesa más de 8 MB.';
                continue;
            }
            // El tipo se valida por el contenido real del archivo, no por la extensión.
            $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
            $esHeic = in_array($tipo, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true);
            if ($esHeic && !ImagenServicio::admiteHeic()) {
                $problemas[] = $archivo['name'] . ': es una foto HEIC de iPhone y este servidor no puede convertirla. '
                    . 'Subila desde el mismo iPhone (se convierte sola) o exportala como JPG.';
                continue;
            }
            if (!$esHeic && !in_array($tipo, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $problemas[] = $archivo['name'] . ': tiene que ser JPG, PNG, WebP o HEIC.';
                continue;
            }
            try {
                $nombre = sprintf('%d-%s', $propiedad['codigo'], bin2hex(random_bytes(5)));
                // Las HEIC se pasan primero a JPG con Imagick; GD no las lee.
                $origen = $esHeic ? ImagenServicio::convertirHeic($archivo['tmp_name']) : $archivo['tmp_name'];
                try {
                    $medidas = ImagenServicio::procesar($origen, PUBLICO . '/uploads/propiedades/' . $propiedad['codigo'], $nombre);
                } finally {
                    if ($esHeic) {
                        @unlink($origen);
                    }
                }
                $repositorio->agregarFoto((int) $id, $nombre, $medidas['ancho'], $medidas['alto']);
                $subidas++;
            } catch (Throwable $error) {
                error_log((string) $error);
                $problemas[] = $archivo['name'] . ': no se pudo procesar la imagen.';
            }
        }

        if ($subidas > 0) {
            Sesion::avisar('ok', $subidas === 1 ? 'Se subió 1 foto.' : "Se subieron $subidas fotos.");
        }
        foreach ($problemas as $problema) {
            Sesion::avisar('error', $problema);
        }
        if ($subidas === 0 && !$problemas) {
            Sesion::avisar('error', 'Elegí al menos una foto.');
        }
        redirigir("/panel/propiedades/$id#fotos");
    }

    public function mover(string $id): void
    {
        Sesion::exigirUsuario();
        Csrf::verificar();
        $movimiento = in_array($_POST['movimiento'] ?? '', ['arriba', 'abajo', 'portada'], true) ? $_POST['movimiento'] : 'arriba';
        $idPropiedad = (new GestionPropiedadRepositorio())->moverFoto((int) $id, $movimiento);
        redirigir("/panel/propiedades/$idPropiedad#fotos");
    }

    public function borrar(string $id): void
    {
        Sesion::exigirUsuario();
        Csrf::verificar();
        $foto = (new GestionPropiedadRepositorio())->borrarFoto((int) $id);
        foreach ([ImagenServicio::VARIANTE_GRANDE, ImagenServicio::VARIANTE_MINIATURA] as $variante) {
            @unlink(PUBLICO . '/uploads/propiedades/' . $foto['codigo'] . '/' . $foto['archivo'] . '-' . $variante . '.webp');
        }
        Sesion::avisar('ok', 'Foto eliminada.');
        redirigir('/panel/propiedades/' . $foto['id_propiedad'] . '#fotos');
    }

    /** $_FILES con varios archivos viene "al revés"; lo paso a una lista de archivos. */
    private function normalizar(array $campo): array
    {
        if (!isset($campo['name']) || !is_array($campo['name'])) {
            return [];
        }
        $lista = [];
        foreach ($campo['name'] as $i => $nombre) {
            if ($campo['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $lista[] = [
                'name'     => (string) $nombre,
                'tmp_name' => $campo['tmp_name'][$i],
                'error'    => $campo['error'][$i],
                'size'     => $campo['size'][$i],
            ];
        }
        return $lista;
    }
}
