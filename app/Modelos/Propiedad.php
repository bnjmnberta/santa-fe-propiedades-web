<?php
declare(strict_types=1);

namespace App\Modelos;

/** Ficha de una propiedad, armada desde una fila de PropiedadRepositorio. */
final class Propiedad
{
    /** @var list<array{archivo: string, ancho: ?int, alto: ?int}> */
    public array $fotos = [];

    private function __construct(
        public readonly int $id,
        public readonly int $codigo,
        public readonly string $slug,
        public readonly string $titulo,
        public readonly Operacion $operacion,
        public readonly EstadoPropiedad $estado,
        public readonly Precio $precio,
        public readonly string $tipo,
        public readonly bool $esComercial,
        public readonly ?string $zona,
        public readonly ?string $portada,
        private readonly array $fila,
    ) {
    }

    public static function desdeFila(array $f): self
    {
        return new self(
            id: (int) $f['id_propiedad'],
            codigo: (int) $f['codigo'],
            slug: (string) $f['slug'],
            titulo: (string) $f['titulo'],
            operacion: Operacion::from($f['operacion']),
            estado: EstadoPropiedad::from($f['estado']),
            precio: Precio::crear($f['moneda'] ?? null, $f['precio'] ?? null),
            tipo: (string) ($f['tipo'] ?? ''),
            esComercial: (bool) ($f['es_comercial'] ?? false),
            zona: $f['zona'] ?? null,
            portada: $f['portada'] ?? null,
            fila: $f,
        );
    }

    /** Columnas opcionales de la ficha (dormitorios, sup_cubierta, requisitos...). */
    public function dato(string $columna): mixed
    {
        $valor = $this->fila[$columna] ?? null;
        return $valor === '' ? null : $valor;
    }

    public function url(): string
    {
        return '/propiedad/' . $this->codigo . '-' . $this->slug;
    }

    /** Etiqueta del mapa: el precio corto o, si no hay precio, el tipo ("Galpón", "Depto"). */
    public function etiquetaMapa(): string
    {
        if ($this->precio->tieneValor()) {
            return $this->precio->formatearCorto();
        }
        return match ($this->tipo) {
            'Departamento'   => 'Depto',
            'Salón / local'  => 'Local',
            'Lote / terreno' => 'Lote',
            'Casa quinta'    => 'Quinta',
            default          => $this->tipo,
        };
    }

    public function precioTexto(): string
    {
        return $this->precio->formatear($this->operacion === Operacion::Alquiler);
    }

    public function direccionVisible(): ?string
    {
        return (bool) $this->dato('mostrar_direccion') ? $this->dato('direccion') : null;
    }

    /** "Candioti Norte · D. Silva 1350" o lo que haya disponible. */
    public function ubicacion(): string
    {
        return implode(' · ', array_filter([$this->zona, $this->direccionVisible()]));
    }

    /**
     * Rasgos cortos para tarjetas: [ícono, texto].
     * @return list<array{0: string, 1: string}>
     */
    public function rasgos(): array
    {
        $rasgos = [];
        $dormitorios = $this->dato('dormitorios');
        if ($dormitorios !== null && (int) $dormitorios > 0) {
            $rasgos[] = ['cama', $dormitorios . ' dorm.'];
        } elseif ($dormitorios !== null && $this->tipo === 'Departamento') {
            $rasgos[] = ['cama', 'Monoambiente'];
        }
        if ($this->dato('banos') !== null && (int) $this->dato('banos') > 0) {
            $rasgos[] = ['ducha', $this->dato('banos') . ((int) $this->dato('banos') === 1 ? ' baño' : ' baños')];
        }
        $superficie = $this->dato('sup_cubierta') ?? $this->dato('sup_terreno');
        if ($superficie !== null && (float) $superficie > 0) {
            $rasgos[] = ['regla', numero((float) $superficie) . ' m²'];
        }
        if ((int) $this->dato('cocheras') > 0) {
            $rasgos[] = ['auto', (int) $this->dato('cocheras') === 1 ? 'Cochera' : $this->dato('cocheras') . ' cocheras'];
        }
        return $rasgos;
    }

    /** @return list<string> Una característica por línea (formato de Instagram). */
    public function caracteristicas(): array
    {
        $texto = (string) $this->dato('caracteristicas');
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $texto))));
    }

    /** @param 'grande'|'chica' $variante Ver ImagenServicio::VARIANTE_*. */
    public function urlFoto(string $archivo, string $variante = 'grande'): string
    {
        return '/uploads/propiedades/' . $this->codigo . '/' . $archivo . '-' . $variante . '.webp';
    }

    /** @param 'grande'|'chica' $variante */
    public function urlPortada(string $variante = 'chica'): ?string
    {
        $archivo = $this->portada ?? ($this->fotos[0]['archivo'] ?? null);
        return $archivo === null ? null : $this->urlFoto($archivo, $variante);
    }
}
