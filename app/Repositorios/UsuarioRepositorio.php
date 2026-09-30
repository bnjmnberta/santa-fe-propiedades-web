<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\Conexion;
use PDO;

final class UsuarioRepositorio
{
    /** Regla m: después de 5 intentos fallidos, la cuenta se bloquea 15 minutos. */
    public const INTENTOS_MAXIMOS = 5;
    public const MINUTOS_BLOQUEO = 15;
    /** Hash bcrypt válido de una contraseña que no existe; solo sirve para igualar tiempos. */
    private const HASH_DE_RELLENO = '$2y$12$/Gt4NgGp0.is6FVO1EESFezg2x701xSVI4hIIFHBiOmbjrMjRYeqS';

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Conexion::obtenerInstancia()->pdo();
    }

    public function porEmail(string $email): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM usuario WHERE email = ?');
        $consulta->execute([mb_strtolower(trim($email))]);
        return $consulta->fetch() ?: null;
    }

    public function todos(): array
    {
        return $this->pdo->query('SELECT id_usuario, nombre, email, rol, activo, ultimo_acceso FROM usuario ORDER BY nombre')->fetchAll();
    }

    /**
     * Valida credenciales aplicando el bloqueo por intentos.
     * @return array{resultado: 'ok'|'invalido'|'bloqueado', usuario?: array}
     */
    public function autenticar(string $email, string $contrasena): array
    {
        $usuario = $this->porEmail($email);
        if ($usuario === null || !(bool) $usuario['activo']) {
            // Mismo costo de tiempo que un usuario real: no revela qué emails existen.
            password_verify($contrasena, self::HASH_DE_RELLENO);
            return ['resultado' => 'invalido'];
        }
        if ($usuario['bloqueado_hasta'] !== null && strtotime($usuario['bloqueado_hasta']) > time()) {
            return ['resultado' => 'bloqueado'];
        }
        if (!password_verify($contrasena, $usuario['contrasena_hash'])) {
            $intentos = (int) $usuario['intentos_fallidos'] + 1;
            $bloqueo = $intentos >= self::INTENTOS_MAXIMOS ? date('Y-m-d H:i:s', time() + self::MINUTOS_BLOQUEO * 60) : null;
            $this->pdo->prepare('UPDATE usuario SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id_usuario = ?')
                ->execute([$bloqueo ? 0 : $intentos, $bloqueo, $usuario['id_usuario']]);
            return ['resultado' => $bloqueo ? 'bloqueado' : 'invalido'];
        }

        $nuevoHash = password_needs_rehash($usuario['contrasena_hash'], PASSWORD_DEFAULT)
            ? password_hash($contrasena, PASSWORD_DEFAULT)
            : $usuario['contrasena_hash'];
        $this->pdo->prepare('UPDATE usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW(), contrasena_hash = ? WHERE id_usuario = ?')
            ->execute([$nuevoHash, $usuario['id_usuario']]);
        return ['resultado' => 'ok', 'usuario' => $usuario];
    }

    public function crear(string $nombre, string $email, string $contrasena, string $rol): int
    {
        $this->pdo->prepare('INSERT INTO usuario (nombre, email, contrasena_hash, rol) VALUES (?, ?, ?, ?)')
            ->execute([trim($nombre), mb_strtolower(trim($email)), password_hash($contrasena, PASSWORD_DEFAULT), $rol]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cambiarContrasena(int $id, string $contrasena): void
    {
        $this->pdo->prepare('UPDATE usuario SET contrasena_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?')
            ->execute([password_hash($contrasena, PASSWORD_DEFAULT), $id]);
    }

    public function cambiarActivo(int $id, bool $activo): void
    {
        $this->pdo->prepare('UPDATE usuario SET activo = ? WHERE id_usuario = ?')->execute([(int) $activo, $id]);
    }
}
