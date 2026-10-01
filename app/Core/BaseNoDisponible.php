<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** No se pudo conectar con la base de datos (apagada o con datos de acceso mal); public/index.php responde 503. */
final class BaseNoDisponible extends RuntimeException
{
}
