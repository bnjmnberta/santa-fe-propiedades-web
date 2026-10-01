<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\Conexion;
use App\Modelos\FiltrosCatalogo;
use App\Modelos\Operacion;
use App\Modelos\Propiedad;
use PDO;

final class PropiedadRepositorio
{
    private const SELECT = <<<'SQL'
        SELECT p.*, t.nombre AS tipo, t.slug AS tipo_slug, t.es_comercial,
               z.nombre AS zona, z.slug AS zona_slug,
               (SELECT f.archivo FROM foto f
                 WHERE f.id_propiedad = p.id_propiedad
                 ORDER BY f.orden, f.id_foto LIMIT 1) AS portada
          FROM propiedad p
          JOIN tipo_propiedad t ON t.id_tipo = p.id_tipo
          LEFT JOIN zona z ON z.id_zona = p.id_zona
        SQL;

    /** Regla d y regla k: sin baja lógica y en estado público. */
    private const PUBLICADA = "p.eliminado_en IS NULL AND p.estado IN ('disponible', 'reservado')";

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Conexion::obtenerInstancia()->pdo();
    }

    /** @return array{items: list<Propiedad>, total: int} */
    public function buscarPublicadas(FiltrosCatalogo $filtros, int $pagina, int $porPagina): array
    {
        [$where, $parametros] = $this->condiciones($filtros);

        $total = $this->pdo->prepare('SELECT COUNT(*) FROM propiedad p JOIN tipo_propiedad t ON t.id_tipo = p.id_tipo
            LEFT JOIN zona z ON z.id_zona = p.id_zona WHERE ' . $where);
        $total->execute($parametros);

        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE ' . $where
            . ' ORDER BY p.destacada DESC, p.fecha_modificacion DESC, p.codigo DESC LIMIT :limite OFFSET :desde');
        foreach ($parametros as $clave => $valor) {
            $consulta->bindValue($clave, $valor);
        }
        $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $consulta->bindValue(':desde', max(0, ($pagina - 1) * $porPagina), PDO::PARAM_INT);
        $consulta->execute();

        return [
            'items' => array_map(Propiedad::desdeFila(...), $consulta->fetchAll()),
            'total' => (int) $total->fetchColumn(),
        ];
    }

    /** Destacadas primero; si no alcanzan, se completan con las más recientes. @return list<Propiedad> */
    public function destacadas(int $limite, ?Operacion $operacion = null): array
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE ' . self::PUBLICADA
            . ($operacion ? ' AND p.operacion = :operacion' : '')
            . ' ORDER BY p.destacada DESC, p.fecha_modificacion DESC LIMIT :limite');
        if ($operacion) {
            $consulta->bindValue(':operacion', $operacion->value);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return array_map(Propiedad::desdeFila(...), $consulta->fetchAll());
    }

    /**
     * Propiedades con coordenadas para el mapa del catálogo (todas las que cumplen los filtros,
     * no solo la página actual).
     * @return list<array{codigo: int, titulo: string, url: string, precio: string, operacion: string, lat: float, lng: float, foto: ?string}>
     */
    public function puntosMapa(FiltrosCatalogo $filtros): array
    {
        [$where, $parametros] = $this->condiciones($filtros);
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE ' . $where . ' AND p.latitud IS NOT NULL AND p.longitud IS NOT NULL');
        $consulta->execute($parametros);
        return array_map(static function (array $fila): array {
            $propiedad = Propiedad::desdeFila($fila);
            return [
                'codigo'    => $propiedad->codigo,
                'titulo'    => $propiedad->titulo,
                'url'       => $propiedad->url(),
                'precio'    => $propiedad->precioTexto(),
                'etiqueta'  => $propiedad->etiquetaMapa(),
                'operacion' => $propiedad->operacion->value,
                'lat'       => (float) $fila['latitud'],
                'lng'       => (float) $fila['longitud'],
                'foto'      => $propiedad->urlPortada('chica'),
            ];
        }, $consulta->fetchAll());
    }

    /**
     * Cualquier propiedad sin baja lógica, publicada o no: una ficha vendida sigue
     * respondiendo (enlaces viejos de Instagram o WhatsApp) y avisa que no está disponible.
     */
    public function porCodigo(int $codigo): ?Propiedad
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE p.codigo = :codigo AND p.eliminado_en IS NULL');
        $consulta->execute([':codigo' => $codigo]);
        $fila = $consulta->fetch();
        if ($fila === false) {
            return null;
        }

        $propiedad = Propiedad::desdeFila($fila);
        $fotos = $this->pdo->prepare('SELECT archivo, ancho, alto FROM foto WHERE id_propiedad = :id ORDER BY orden, id_foto');
        $fotos->execute([':id' => $propiedad->id]);
        $propiedad->fotos = $fotos->fetchAll();
        return $propiedad;
    }

    /** @return list<Propiedad> Misma operación y tipo, publicadas. */
    public function similares(Propiedad $propiedad, int $limite): array
    {
        $consulta = $this->pdo->prepare(self::SELECT . ' WHERE ' . self::PUBLICADA
            . ' AND p.operacion = :operacion AND p.id_propiedad <> :id
               ORDER BY (t.nombre = :tipo) DESC, p.fecha_modificacion DESC LIMIT :limite');
        $consulta->bindValue(':operacion', $propiedad->operacion->value);
        $consulta->bindValue(':id', $propiedad->id, PDO::PARAM_INT);
        $consulta->bindValue(':tipo', $propiedad->tipo);
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();
        return array_map(Propiedad::desdeFila(...), $consulta->fetchAll());
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function condiciones(FiltrosCatalogo $filtros): array
    {
        $where = [self::PUBLICADA];
        $parametros = [];
        if ($filtros->operacion !== null) {
            $where[] = 'p.operacion = :operacion';
            $parametros[':operacion'] = $filtros->operacion->value;
        }
        if ($filtros->soloComerciales) {
            $where[] = 't.es_comercial = 1';
        }
        if ($filtros->tipo !== null) {
            $where[] = 't.slug = :tipo';
            $parametros[':tipo'] = $filtros->tipo;
        }
        if ($filtros->zona !== null) {
            $where[] = 'z.slug = :zona';
            $parametros[':zona'] = $filtros->zona;
        }
        if ($filtros->dormitorios !== null) {
            $where[] = 'p.dormitorios >= :dormitorios';
            $parametros[':dormitorios'] = $filtros->dormitorios;
        }
        if ($filtros->texto !== null) {
            // Sin prepares emulados cada marcador se usa una sola vez: por eso tres.
            $where[] = '(p.titulo LIKE :texto1 OR p.direccion LIKE :texto2 OR z.nombre LIKE :texto3)';
            $patron = '%' . addcslashes($filtros->texto, '%_\\') . '%';
            $parametros[':texto1'] = $parametros[':texto2'] = $parametros[':texto3'] = $patron;
        }
        return [implode(' AND ', $where), $parametros];
    }
}
