<?php
declare(strict_types=1);

namespace App\Core;

use LogicException;
use PDO;

/**
 * Patrón Singleton: una sola conexión PDO por solicitud (docs/ANALISIS.md, sección 12).
 *
 * El hosting compartido limita las conexiones simultáneas por usuario de MySQL
 * (max_user_connections). Si cada repositorio abriera la suya, una página con varios
 * bloques multiplicaría las conexiones por visita. Todos piden la conexión acá.
 */
final class Conexion
{
    private static ?Conexion $instancia = null;

    private PDO $pdo;

    private function __construct()
    {
        $db = Config::get('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['puerto'] ?? 3306,
            $db['nombre'],
            $db['charset'] ?? 'utf8mb4'
        );
        $this->pdo = new PDO($dsn, $db['usuario'], $db['clave'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $this->pdo->exec("SET time_zone = '-03:00'");
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new LogicException('Conexion es un Singleton: no se puede deserializar.');
    }

    public static function obtenerInstancia(): self
    {
        return self::$instancia ??= new self();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
