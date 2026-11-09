<?php
declare(strict_types=1);

namespace App\Modelos;

enum EstadoPropiedad: string
{
    case Disponible = 'disponible';
    case Reservado = 'reservado';
    case Alquilado = 'alquilado';
    case Vendido = 'vendido';
    case Pausado = 'pausado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Reservado  => 'Reservado',
            self::Alquilado  => 'Alquilado',
            self::Vendido    => 'Vendido',
            self::Pausado    => 'Pausado',
        };
    }

    /** Regla d: solo Disponible y Reservado aparecen en el catálogo público. */
    public function esPublico(): bool
    {
        return $this === self::Disponible || $this === self::Reservado;
    }

    /**
     * Estados a los que se puede pasar desde el actual (reglas e y f).
     * Vendido es final: solo el Administrador puede volverlo a Disponible.
     *
     * @return list<self>
     */
    public function transicionesPermitidas(Operacion $operacion, bool $esAdministrador): array
    {
        $cierre = $operacion->estadoCierre();

        return match ($this) {
            self::Disponible => [self::Reservado, $cierre, self::Pausado],
            self::Reservado  => [self::Disponible, $cierre, self::Pausado],
            self::Pausado    => [self::Disponible],
            self::Alquilado  => [self::Disponible, self::Pausado],
            self::Vendido    => $esAdministrador ? [self::Disponible] : [],
        };
    }

    public function puedePasarA(self $nuevo, Operacion $operacion, bool $esAdministrador): bool
    {
        return in_array($nuevo, $this->transicionesPermitidas($operacion, $esAdministrador), true);
    }
}
