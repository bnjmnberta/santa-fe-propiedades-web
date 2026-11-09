<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Modelos\EstadoPropiedad;
use App\Modelos\FiltrosCatalogo;
use App\Modelos\Operacion;
use App\Modelos\Propiedad;

/**
 * Propiedades guardadas como contenido de WordPress (tipo sfp_propiedad). Cada columna de la base
 * original es un dato "sfp_<columna>". Son pocas decenas, así que se leen una vez por solicitud
 * y se filtran en memoria con las mismas reglas que tenía la consulta SQL.
 */
final class PropiedadRepositorio
{
    /** @var list<array<string, mixed>>|null */
    private static ?array $filas = null;

    public static function olvidar(): void
    {
        self::$filas = null;
    }

    /** @return list<array<string, mixed>> */
    private static function filas(): array
    {
        if (self::$filas !== null) {
            return self::$filas;
        }
        $posts = get_posts(['post_type' => 'sfp_propiedad', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true]);
        $ids = wp_list_pluck($posts, 'ID');
        update_meta_cache('post', $ids);
        update_object_term_cache($ids, 'sfp_propiedad');

        $filas = [];
        foreach ($posts as $post) {
            $fila = ['id_propiedad' => $post->ID, 'titulo' => $post->post_title];
            foreach (get_post_meta($post->ID) as $clave => $valores) {
                if (str_starts_with($clave, 'sfp_')) {
                    $fila[substr($clave, 4)] = maybe_unserialize($valores[0] ?? '');
                }
            }
            $tipo = get_the_terms($post->ID, 'sfp_tipo');
            $tipo = is_array($tipo) ? reset($tipo) : null;
            $zona = get_the_terms($post->ID, 'sfp_zona');
            $zona = is_array($zona) ? reset($zona) : null;
            $fila['tipo'] = $tipo ? $tipo->name : '';
            $fila['tipo_slug'] = $tipo ? $tipo->slug : '';
            $fila['tipo_orden'] = $tipo ? (int) get_term_meta($tipo->term_id, 'sfp_orden', true) : 0;
            $fila['es_comercial'] = $tipo ? get_term_meta($tipo->term_id, 'sfp_es_comercial', true) === '1' : false;
            $fila['zona'] = $zona ? $zona->name : null;
            $fila['zona_slug'] = $zona ? $zona->slug : null;
            $fotos = array_values(array_filter(array_map('intval', (array) ($fila['fotos'] ?? []))));
            $fila['fotos'] = $fotos;
            $fila['portada'] = $fotos ? (string) $fotos[0] : null;
            foreach (['precio', 'moneda', 'latitud', 'longitud', 'dormitorios', 'banos', 'sup_cubierta', 'sup_terreno', 'direccion', 'caracteristicas'] as $opcional) {
                if (($fila[$opcional] ?? '') === '') {
                    $fila[$opcional] = null;
                }
            }
            $fila['codigo'] = (int) ($fila['codigo'] ?? 0);
            $fila['fecha_modificacion'] = (string) ($fila['fecha_modificacion'] ?? $post->post_modified);
            $filas[] = $fila;
        }
        return self::$filas = $filas;
    }

    private static function esPublica(array $fila): bool
    {
        return in_array($fila['estado'] ?? '', ['disponible', 'reservado'], true);
    }

    /** @return list<array<string, mixed>> */
    private static function publicadas(): array
    {
        return array_values(array_filter(self::filas(), self::esPublica(...)));
    }

    /** Destacadas primero, luego las modificadas más recientemente. */
    private static function ordenar(array $filas): array
    {
        usort($filas, static fn (array $a, array $b): int =>
            [(int) ($b['destacada'] ?? 0), $b['fecha_modificacion'], $b['codigo']] <=> [(int) ($a['destacada'] ?? 0), $a['fecha_modificacion'], $a['codigo']]);
        return $filas;
    }

    /** Minúsculas y sin acentos, para que "galvez" encuentre "Gálvez". */
    private static function normalizar(string $texto): string
    {
        return mb_strtolower(remove_accents($texto));
    }

    /** @return list<array<string, mixed>> */
    private static function filtrar(FiltrosCatalogo $f): array
    {
        $texto = $f->texto !== null ? self::normalizar($f->texto) : null;
        return array_values(array_filter(self::publicadas(), static function (array $fila) use ($f, $texto): bool {
            if ($f->operacion !== null && ($fila['operacion'] ?? '') !== $f->operacion->value) {
                return false;
            }
            if ($f->soloComerciales && !$fila['es_comercial']) {
                return false;
            }
            if ($f->tipo !== null && $fila['tipo_slug'] !== $f->tipo) {
                return false;
            }
            if ($f->zona !== null && $fila['zona_slug'] !== $f->zona) {
                return false;
            }
            if ($f->dormitorios !== null && ($fila['dormitorios'] === null || (int) $fila['dormitorios'] < $f->dormitorios)) {
                return false;
            }
            if ($texto !== null) {
                $pajar = self::normalizar(implode(' ', [$fila['titulo'], (string) $fila['direccion'], (string) $fila['zona']]));
                if (!str_contains($pajar, $texto)) {
                    return false;
                }
            }
            return true;
        }));
    }

    /** @return array{items: list<Propiedad>, total: int} */
    public function buscarPublicadas(FiltrosCatalogo $filtros, int $pagina, int $porPagina): array
    {
        $filas = self::ordenar(self::filtrar($filtros));
        $pagina = array_slice($filas, max(0, ($pagina - 1) * $porPagina), $porPagina);
        return ['items' => array_map(Propiedad::desdeFila(...), $pagina), 'total' => count($filas)];
    }

    /** Destacadas primero; si no alcanzan, se completan con las más recientes. @return list<Propiedad> */
    public function destacadas(int $limite, ?Operacion $operacion = null): array
    {
        $filas = array_filter(self::publicadas(), static fn (array $f): bool => $operacion === null || ($f['operacion'] ?? '') === $operacion->value);
        return array_map(Propiedad::desdeFila(...), array_slice(self::ordenar(array_values($filas)), 0, $limite));
    }

    /**
     * Propiedades con coordenadas para el mapa del catálogo (todas las que cumplen los filtros,
     * no solo la página actual). Llevan lo que muestra la tarjeta de vista previa al pasar el mouse.
     */
    public function puntosMapa(FiltrosCatalogo $filtros): array
    {
        $con = array_filter(self::filtrar($filtros), static fn (array $f): bool => $f['latitud'] !== null && $f['longitud'] !== null);
        return array_values(array_map(static function (array $fila): array {
            $propiedad = Propiedad::desdeFila($fila);
            return [
                'codigo'         => $propiedad->codigo,
                'titulo'         => $propiedad->titulo,
                'url'            => $propiedad->url(),
                'precio'         => $propiedad->precioTexto(),
                'conPrecio'      => $propiedad->precio->tieneValor(),
                'etiqueta'       => $propiedad->etiquetaMapa(),
                'operacion'      => $propiedad->operacion->value,
                'operacionTexto' => $propiedad->operacion->etiqueta(),
                'reservado'      => $propiedad->estado === EstadoPropiedad::Reservado,
                'ubicacion'      => $propiedad->ubicacion() ?: $propiedad->tipo,
                'rasgos'         => $propiedad->rasgos(),
                'lat'            => (float) $fila['latitud'],
                'lng'            => (float) $fila['longitud'],
                'foto'           => $propiedad->urlPortada('chica'),
                'fotos'          => array_map(
                    fn (int $id) => $propiedad->urlFoto((string) $id, 'chica'),
                    array_slice($fila['fotos'], 0, 2)
                ),
            ];
        }, $con));
    }

    /** Cualquier propiedad con ese código, publicada o no: una ficha vendida sigue respondiendo y avisa que no está disponible. */
    public function porCodigo(int $codigo): ?Propiedad
    {
        foreach (self::filas() as $fila) {
            if ($fila['codigo'] === $codigo) {
                $propiedad = Propiedad::desdeFila($fila);
                $propiedad->fotos = array_map(static function (int $id): array {
                    $meta = wp_get_attachment_metadata($id) ?: [];
                    return ['archivo' => (string) $id, 'ancho' => $meta['width'] ?? null, 'alto' => $meta['height'] ?? null];
                }, $fila['fotos']);
                return $propiedad;
            }
        }
        return null;
    }

    /** @return list<Propiedad> Misma operación y tipo, publicadas. */
    public function similares(Propiedad $propiedad, int $limite): array
    {
        $filas = array_filter(self::publicadas(), static fn (array $f): bool => ($f['operacion'] ?? '') === $propiedad->operacion->value && $f['id_propiedad'] !== $propiedad->id);
        usort($filas, static fn (array $a, array $b): int =>
            [(int) ($b['tipo'] === $propiedad->tipo), $b['fecha_modificacion']] <=> [(int) ($a['tipo'] === $propiedad->tipo), $a['fecha_modificacion']]);
        return array_map(Propiedad::desdeFila(...), array_slice($filas, 0, $limite));
    }

    /** Tipos y zonas con propiedades publicadas, para los filtros del catálogo. */
    public static function conteo(string $clave, string $nombre, string $slug, ?string $orden = null): array
    {
        $cuenta = [];
        foreach (self::publicadas() as $fila) {
            if (($fila[$slug] ?? null) === null || $fila[$slug] === '') {
                continue;
            }
            $cuenta[$fila[$slug]] ??= ['nombre' => (string) $fila[$nombre], 'slug' => (string) $fila[$slug], 'cantidad' => 0, 'orden' => $orden ? (int) $fila[$orden] : 0];
            $cuenta[$fila[$slug]]['cantidad']++;
        }
        $lista = array_values($cuenta);
        usort($lista, static fn (array $a, array $b): int => [$a['orden'], $a['nombre']] <=> [$b['orden'], $b['nombre']]);
        return array_map(static fn (array $c): array => ['nombre' => $c['nombre'], 'slug' => $c['slug'], 'cantidad' => $c['cantidad']], $lista);
    }
}
