<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\Conexion;
use App\Modelos\EstadoPropiedad;
use App\Servicios\Texto;
use PDO;
use RuntimeException;

/** Operaciones del panel sobre propiedades y fotos. */
final class GestionPropiedadRepositorio
{
    public const MAXIMO_FOTOS = 30; // regla g

    private const COLUMNAS = ['titulo', 'operacion', 'id_tipo', 'id_zona', 'direccion', 'mostrar_direccion', 'moneda',
        'precio', 'expensas', 'expensas_detalle', 'requisitos', 'disponible_desde', 'amoblado', 'dormitorios', 'banos',
        'cocheras', 'sup_cubierta', 'sup_terreno', 'frente_m', 'fondo_m', 'regimen', 'planta', 'ubicacion_unidad',
        'caracteristicas', 'servicios', 'referencias', 'descripcion', 'instagram_url', 'latitud', 'longitud', 'destacada'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Conexion::obtenerInstancia()->pdo();
    }

    public function listar(?string $texto = null, ?string $estado = null): array
    {
        $where = ['p.eliminado_en IS NULL'];
        $parametros = [];
        if ($texto !== null && $texto !== '') {
            $where[] = '(p.titulo LIKE :t1 OR p.direccion LIKE :t2 OR p.codigo = :codigo)';
            $parametros[':t1'] = $parametros[':t2'] = '%' . addcslashes($texto, '%_\\') . '%';
            $parametros[':codigo'] = ctype_digit($texto) ? (int) $texto : -1;
        }
        if ($estado !== null && EstadoPropiedad::tryFrom($estado)) {
            $where[] = 'p.estado = :estado';
            $parametros[':estado'] = $estado;
        }
        $consulta = $this->pdo->prepare("SELECT p.id_propiedad, p.codigo, p.slug, p.titulo, p.operacion, p.estado, p.moneda,
                p.precio, p.destacada, p.direccion, p.fecha_modificacion, t.nombre AS tipo, z.nombre AS zona,
                (SELECT f.archivo FROM foto f WHERE f.id_propiedad = p.id_propiedad ORDER BY f.orden, f.id_foto LIMIT 1) AS portada,
                (SELECT COUNT(*) FROM foto f WHERE f.id_propiedad = p.id_propiedad) AS cantidad_fotos
            FROM propiedad p
            JOIN tipo_propiedad t ON t.id_tipo = p.id_tipo
            LEFT JOIN zona z ON z.id_zona = p.id_zona
            WHERE " . implode(' AND ', $where) . '
            ORDER BY FIELD(p.estado, "disponible", "reservado", "pausado", "alquilado", "vendido"), p.fecha_modificacion DESC');
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    /** @return array<string, int> */
    public function contadores(): array
    {
        return $this->pdo->query("SELECT
                SUM(estado IN ('disponible', 'reservado')) AS publicadas,
                SUM(estado = 'reservado') AS reservadas,
                SUM(estado IN ('alquilado', 'vendido') AND fecha_cierre >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS cerradas_mes,
                SUM(estado = 'pausado') AS pausadas
            FROM propiedad WHERE eliminado_en IS NULL")->fetch();
    }

    public function porId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT p.*, t.nombre AS tipo, z.nombre AS zona FROM propiedad p
            JOIN tipo_propiedad t ON t.id_tipo = p.id_tipo LEFT JOIN zona z ON z.id_zona = p.id_zona
            WHERE p.id_propiedad = ? AND p.eliminado_en IS NULL');
        $consulta->execute([$id]);
        return $consulta->fetch() ?: null;
    }

    /** Regla h: los códigos nuevos continúan la numeración de la web vieja. */
    public function crear(array $datos, int $idUsuario): int
    {
        $codigo = (int) $this->pdo->query('SELECT COALESCE(MAX(codigo), 0) + 1 FROM propiedad')->fetchColumn();
        $columnas = [...self::COLUMNAS, 'codigo', 'slug', 'creado_por'];
        $valores = $this->valores($datos) + [':codigo' => $codigo, ':slug' => Texto::slug($datos['titulo']), ':creado_por' => $idUsuario];
        $this->pdo->prepare('INSERT INTO propiedad (' . implode(', ', $columnas) . ') VALUES (:' . implode(', :', $columnas) . ')')
            ->execute($valores);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $asignaciones = implode(', ', array_map(fn ($c) => "$c = :$c", self::COLUMNAS));
        $this->pdo->prepare("UPDATE propiedad SET $asignaciones, slug = :slug WHERE id_propiedad = :id")
            ->execute($this->valores($datos) + [':slug' => Texto::slug($datos['titulo']), ':id' => $id]);
    }

    /** Reglas e y f: la transición se valida antes de llamar; acá se guarda estado e historial juntos. */
    public function cambiarEstado(int $id, EstadoPropiedad $anterior, EstadoPropiedad $nuevo, int $idUsuario, ?string $nota): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare('UPDATE propiedad SET estado = ?, fecha_cierre = ? WHERE id_propiedad = ?')->execute([
            $nuevo->value,
            in_array($nuevo, [EstadoPropiedad::Alquilado, EstadoPropiedad::Vendido], true) ? date('Y-m-d H:i:s') : null,
            $id,
        ]);
        $this->pdo->prepare('INSERT INTO historial_estado (id_propiedad, estado_anterior, estado_nuevo, id_usuario, nota) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $anterior->value, $nuevo->value, $idUsuario, $nota ?: null]);
        $this->pdo->commit();
    }

    public function historial(int $id): array
    {
        $consulta = $this->pdo->prepare('SELECT h.*, u.nombre AS usuario FROM historial_estado h
            LEFT JOIN usuario u ON u.id_usuario = h.id_usuario WHERE h.id_propiedad = ? ORDER BY h.fecha DESC, h.id_historial DESC');
        $consulta->execute([$id]);
        return $consulta->fetchAll();
    }

    public function alternarDestacada(int $id): void
    {
        $this->pdo->prepare('UPDATE propiedad SET destacada = 1 - destacada WHERE id_propiedad = ?')->execute([$id]);
    }

    /** Regla k: baja lógica, nunca borrado físico. */
    public function darDeBaja(int $id): void
    {
        $this->pdo->prepare('UPDATE propiedad SET eliminado_en = NOW() WHERE id_propiedad = ?')->execute([$id]);
    }

    // ---- Fotos ----

    public function fotos(int $idPropiedad): array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM foto WHERE id_propiedad = ? ORDER BY orden, id_foto');
        $consulta->execute([$idPropiedad]);
        return $consulta->fetchAll();
    }

    public function agregarFoto(int $idPropiedad, string $archivo, int $ancho, int $alto): void
    {
        $orden = $this->pdo->prepare('SELECT COALESCE(MAX(orden), -1) + 1 FROM foto WHERE id_propiedad = ?');
        $orden->execute([$idPropiedad]);
        $this->pdo->prepare('INSERT INTO foto (id_propiedad, archivo, orden, ancho, alto) VALUES (?, ?, ?, ?, ?)')
            ->execute([$idPropiedad, $archivo, (int) $orden->fetchColumn(), $ancho, $alto]);
        $this->tocar($idPropiedad);
    }

    /** @return array{id_foto: int, id_propiedad: int, archivo: string, codigo: int} */
    public function foto(int $idFoto): array
    {
        $consulta = $this->pdo->prepare('SELECT f.*, p.codigo FROM foto f JOIN propiedad p ON p.id_propiedad = f.id_propiedad WHERE f.id_foto = ?');
        $consulta->execute([$idFoto]);
        return $consulta->fetch() ?: throw new RuntimeException('La foto no existe.');
    }

    /** Reordena: mueve una foto un lugar ('arriba' o 'abajo') o al principio ('portada'). */
    public function moverFoto(int $idFoto, string $movimiento): int
    {
        $foto = $this->foto($idFoto);
        $ids = array_map('intval', array_column($this->fotos((int) $foto['id_propiedad']), 'id_foto'));
        $posicion = array_search($idFoto, $ids, true);
        if ($movimiento === 'portada') {
            array_splice($ids, $posicion, 1);
            array_unshift($ids, $idFoto);
        } else {
            $destino = $movimiento === 'arriba' ? $posicion - 1 : $posicion + 1;
            if ($destino >= 0 && $destino < count($ids)) {
                [$ids[$posicion], $ids[$destino]] = [$ids[$destino], $ids[$posicion]];
            }
        }
        $actualizar = $this->pdo->prepare('UPDATE foto SET orden = ? WHERE id_foto = ?');
        foreach ($ids as $orden => $id) {
            $actualizar->execute([$orden, $id]);
        }
        $this->tocar((int) $foto['id_propiedad']);
        return (int) $foto['id_propiedad'];
    }

    /** @return array Datos de la foto borrada, para eliminar los archivos. */
    public function borrarFoto(int $idFoto): array
    {
        $foto = $this->foto($idFoto);
        $this->pdo->prepare('DELETE FROM foto WHERE id_foto = ?')->execute([$idFoto]);
        $this->tocar((int) $foto['id_propiedad']);
        return $foto;
    }

    private function tocar(int $idPropiedad): void
    {
        $this->pdo->prepare('UPDATE propiedad SET fecha_modificacion = NOW() WHERE id_propiedad = ?')->execute([$idPropiedad]);
    }

    private function valores(array $datos): array
    {
        $valores = [];
        foreach (self::COLUMNAS as $columna) {
            $valores[':' . $columna] = $datos[$columna] ?? null;
        }
        return $valores;
    }
}
