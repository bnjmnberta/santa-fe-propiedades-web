<?php
declare(strict_types=1);

namespace App\Modelos;

/** Filtros del buscador público. Los valores llegan de la URL y se validan acá. */
final class FiltrosCatalogo
{
    public function __construct(
        public readonly ?Operacion $operacion = null,
        public readonly bool $soloComerciales = false,
        public readonly ?string $tipo = null,
        public readonly ?string $zona = null,
        public readonly ?string $texto = null,
        public readonly ?int $dormitorios = null,
    ) {
    }

    public static function desdeConsulta(array $consulta, ?string $seccion = null): self
    {
        // La sección sale de la ruta (/ventas) o del buscador de la portada (?operacion=venta).
        $clave = $seccion ?? ($consulta['operacion'] ?? null);
        $operacion = match ($clave) {
            'ventas', 'venta'         => Operacion::Venta,
            'alquileres', 'alquiler'  => Operacion::Alquiler,
            default                   => null,
        };
        $slug = static fn (mixed $valor): ?string =>
            is_string($valor) && preg_match('/^[a-z0-9-]{1,80}$/', $valor) ? $valor : null;

        return new self(
            operacion: $operacion,
            soloComerciales: $clave === 'comerciales',
            tipo: $slug($consulta['tipo'] ?? null),
            zona: $slug($consulta['zona'] ?? null),
            // "¿Dónde?": calle, barrio o palabra del título.
            texto: is_string($consulta['q'] ?? null) && trim($consulta['q']) !== '' ? mb_substr(trim($consulta['q']), 0, 60) : null,
            dormitorios: filter_var($consulta['dormitorios'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]) ?: null,
        );
    }

    public function hayFiltrosAvanzados(): bool
    {
        return $this->tipo !== null || $this->zona !== null || $this->dormitorios !== null;
    }

    /** Parámetros para armar enlaces de paginación conservando los filtros. */
    public function aConsulta(?string $seccion): array
    {
        $operacion = $seccion === null ? ($this->soloComerciales ? 'comerciales' : $this->operacion?->value) : null;
        return array_filter([
            'operacion'   => $operacion,
            'q'           => $this->texto,
            'tipo'        => $this->tipo,
            'zona'        => $this->zona,
            'dormitorios' => $this->dormitorios,
        ]);
    }
}
