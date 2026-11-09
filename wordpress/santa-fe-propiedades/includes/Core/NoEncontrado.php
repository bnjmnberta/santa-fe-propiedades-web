<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Se lanza cuando no existe la ruta o el recurso pedido; public/index.php responde 404. */
final class NoEncontrado extends RuntimeException
{
}
