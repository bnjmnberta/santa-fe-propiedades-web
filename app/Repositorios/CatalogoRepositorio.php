<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\Conexion;
use PDO;

/** Tipos, zonas y servicios para menús, buscador y portada. */
final class CatalogoRepositorio
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Conexion::obtenerInstancia()->pdo();
    }

    /** Solo los tipos que tienen propiedades publicadas, para no ofrecer búsquedas vacías. */
    public function tiposConPublicadas(): array
    {
        return $this->pdo->query("SELECT t.nombre, t.slug, COUNT(*) AS cantidad
              FROM tipo_propiedad t
              JOIN propiedad p ON p.id_tipo = t.id_tipo
             WHERE p.eliminado_en IS NULL AND p.estado IN ('disponible', 'reservado')
             GROUP BY t.id_tipo, t.nombre, t.slug, t.orden
             ORDER BY t.orden")->fetchAll();
    }

    public function zonasConPublicadas(): array
    {
        return $this->pdo->query("SELECT z.nombre, z.slug, COUNT(*) AS cantidad
              FROM zona z
              JOIN propiedad p ON p.id_zona = z.id_zona
             WHERE p.eliminado_en IS NULL AND p.estado IN ('disponible', 'reservado')
             GROUP BY z.id_zona, z.nombre, z.slug
             ORDER BY z.nombre")->fetchAll();
    }

    public function servicios(): array
    {
        return $this->pdo->query('SELECT titulo, descripcion, icono FROM servicio WHERE activo = 1 ORDER BY orden')->fetchAll();
    }

    /** Bloque "¿Por qué elegirnos?". */
    public function motivos(): array
    {
        return $this->pdo->query('SELECT titulo, dato, descripcion, icono FROM motivo WHERE activo = 1 ORDER BY orden')->fetchAll();
    }

    public function preguntasFrecuentes(): array
    {
        return $this->pdo->query('SELECT pregunta, respuesta, grupo FROM faq WHERE activo = 1 ORDER BY orden')->fetchAll();
    }

    /** Puntos estratégicos del mapa (facultades, terminal, puerto, costanera). */
    public function puntosInteres(): array
    {
        return array_map(static fn (array $p): array => [
            'nombre'    => $p['nombre'],
            'categoria' => $p['categoria'],
            'lat'       => (float) $p['latitud'],
            'lng'       => (float) $p['longitud'],
        ], $this->pdo->query('SELECT nombre, categoria, latitud, longitud FROM punto_interes WHERE activo = 1 ORDER BY categoria, nombre')->fetchAll());
    }
}
