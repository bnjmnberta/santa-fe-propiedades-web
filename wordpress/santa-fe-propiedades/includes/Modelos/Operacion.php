<?php
declare(strict_types=1);

namespace App\Modelos;

/** Regla a: cada propiedad tiene una sola operación. */
enum Operacion: string
{
    case Venta = 'venta';
    case Alquiler = 'alquiler';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Venta    => 'Venta',
            self::Alquiler => 'Alquiler',
        };
    }

    /** Estado de cierre propio de cada operación (regla e). */
    public function estadoCierre(): EstadoPropiedad
    {
        return match ($this) {
            self::Venta    => EstadoPropiedad::Vendido,
            self::Alquiler => EstadoPropiedad::Alquilado,
        };
    }
}
