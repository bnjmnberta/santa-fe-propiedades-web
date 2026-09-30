<?php
declare(strict_types=1);

namespace App\Modelos;

/** Regla c: el precio es opcional; sin monto se muestra "Consultar precio". */
final class Precio
{
    private function __construct(
        public readonly ?string $moneda,
        public readonly ?float $monto,
    ) {
    }

    /** Un monto nulo o en cero (caso del galpón 208 de la web vieja) cuenta como sin precio. */
    public static function crear(?string $moneda, float|int|string|null $monto): self
    {
        $monto = $monto === null || $monto === '' ? null : (float) $monto;
        if ($monto === null || $monto <= 0 || $moneda === null) {
            return new self(null, null);
        }
        return new self($moneda, $monto);
    }

    public function tieneValor(): bool
    {
        return $this->monto !== null;
    }

    /** Versión corta para etiquetas del mapa: "$700 mil", "U$S 159 mil", "U$S 1,2 M" o "Consultar". */
    public function formatearCorto(): string
    {
        if (!$this->tieneValor()) {
            return 'Consultar';
        }
        $simbolo = $this->moneda === 'USD' ? 'U$S ' : '$';
        if ($this->monto >= 1_000_000) {
            return $simbolo . rtrim(rtrim(number_format($this->monto / 1_000_000, 1, ',', '.'), '0'), ',') . ' M';
        }
        if ($this->monto >= 1_000) {
            return $simbolo . number_format(round($this->monto / 1_000), 0, ',', '.') . ' mil';
        }
        return $simbolo . number_format($this->monto, 0, ',', '.');
    }

    /** "U$S 159.000", "$ 650.000/mes" o "Consultar precio". */
    public function formatear(bool $mensual = false): string
    {
        if (!$this->tieneValor()) {
            return 'Consultar precio';
        }
        $simbolo = $this->moneda === 'USD' ? 'U$S' : '$';
        return $simbolo . ' ' . number_format($this->monto, 0, ',', '.') . ($mensual ? '/mes' : '');
    }
}
