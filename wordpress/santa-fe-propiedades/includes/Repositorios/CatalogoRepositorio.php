<?php
declare(strict_types=1);

namespace App\Repositorios;

/** Tipos, zonas, servicios, motivos, preguntas y puntos del mapa para menús, buscador y portada. */
final class CatalogoRepositorio
{
    /** Solo los tipos que tienen propiedades publicadas, para no ofrecer búsquedas vacías. */
    public function tiposConPublicadas(): array
    {
        return PropiedadRepositorio::conteo('tipo', 'tipo', 'tipo_slug', 'tipo_orden');
    }

    public function zonasConPublicadas(): array
    {
        return PropiedadRepositorio::conteo('zona', 'zona', 'zona_slug');
    }

    /** @return list<WP_Post> */
    private function lista(string $tipo): array
    {
        return get_posts(['post_type' => $tipo, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => ['menu_order' => 'ASC', 'ID' => 'ASC'], 'suppress_filters' => true]);
    }

    private function meta(int $id, string $clave): string
    {
        return (string) get_post_meta($id, 'sfp_' . $clave, true);
    }

    public function servicios(): array
    {
        return array_map(fn ($p): array => [
            'titulo'      => $p->post_title,
            'descripcion' => $p->post_excerpt,
            'icono'       => $this->meta($p->ID, 'icono') ?: null,
        ], $this->lista('sfp_servicio'));
    }

    /** Bloque "¿Por qué elegirnos?". */
    public function motivos(): array
    {
        return array_map(fn ($p): array => [
            'titulo'      => $p->post_title,
            'dato'        => $this->meta($p->ID, 'dato'),
            'descripcion' => $this->meta($p->ID, 'descripcion'),
            'icono'       => $this->meta($p->ID, 'icono') ?: null,
        ], $this->lista('sfp_motivo'));
    }

    public function preguntasFrecuentes(): array
    {
        return array_map(fn ($p): array => [
            'pregunta'  => $p->post_title,
            'respuesta' => $p->post_content,
            'grupo'     => $this->meta($p->ID, 'grupo') ?: null,
        ], $this->lista('sfp_faq'));
    }

    /** Puntos estratégicos del mapa (facultades, terminal, puerto, costanera). */
    public function puntosInteres(): array
    {
        $puntos = array_map(fn ($p): array => [
            'nombre'    => $p->post_title,
            'categoria' => $this->meta($p->ID, 'categoria'),
            'lat'       => (float) $this->meta($p->ID, 'latitud'),
            'lng'       => (float) $this->meta($p->ID, 'longitud'),
        ], $this->lista('sfp_punto'));
        usort($puntos, static fn (array $a, array $b): int => [$a['categoria'], $a['nombre']] <=> [$b['categoria'], $b['nombre']]);
        return $puntos;
    }
}
