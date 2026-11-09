<?php
declare(strict_types=1);

namespace App\Servicios;

use App\Repositorios\ConfiguracionRepositorio as Cfg;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Horario de atención de la oficina y si está abierta ahora, con la hora de Santa Fe.
 * Se carga en Panel > Configuración con dos datos: los días ("1-5", de 1 = lunes a 7 = domingo)
 * y los turnos ("8-12, 16-19", con minutos opcionales: "8:30-12").
 * assets/js/app.js repite este cálculo cada minuto con los mismos datos (ver datos()).
 */
final class HorarioAtencion
{
    public const ZONA = 'America/Argentina/Buenos_Aires';
    private const DIAS = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];

    /**
     * @param list<int> $dias ISO: 1 = lunes … 7 = domingo
     * @param list<array{0: int, 1: int}> $turnos [desde, hasta] en minutos desde la medianoche, ordenados
     */
    private function __construct(private readonly array $dias, private readonly array $turnos)
    {
    }

    /** Null si los datos de Configuración están vacíos o mal escritos: la página muestra solo el texto del horario. */
    public static function desdeConfiguracion(): ?self
    {
        return self::crear(Cfg::get('horario_dias', '1-5'), Cfg::get('horario_turnos', '8-12, 16-19'));
    }

    public static function crear(string $dias, string $turnos): ?self
    {
        $listaDias = [];
        foreach (explode(',', $dias) as $parte) {
            if (!preg_match('/^\s*([1-7])\s*(?:-\s*([1-7]))?\s*$/', $parte, $m)) {
                return null;
            }
            $hasta = (int) ($m[2] ?? $m[1]);
            for ($d = (int) $m[1]; $d <= $hasta; $d++) {
                $listaDias[$d] = $d;
            }
        }
        $listaTurnos = [];
        foreach (explode(',', $turnos) as $parte) {
            if (!preg_match('/^\s*(\d{1,2})(?::(\d{2}))?\s*-\s*(\d{1,2})(?::(\d{2}))?\s*$/', $parte, $m)) {
                return null;
            }
            $desde = (int) $m[1] * 60 + (int) ($m[2] ?? 0);
            $hasta = (int) $m[3] * 60 + (int) ($m[4] ?? 0);
            if ($desde >= $hasta || $hasta > 24 * 60) {
                return null;
            }
            $listaTurnos[] = [$desde, $hasta];
        }
        if ($listaDias === [] || $listaTurnos === []) {
            return null;
        }
        sort($listaDias);
        usort($listaTurnos, static fn (array $a, array $b) => $a[0] <=> $b[0]);
        return new self(array_values($listaDias), $listaTurnos);
    }

    /** @return array{abierto: bool, texto: string} */
    public function estado(?DateTimeImmutable $ahora = null): array
    {
        $ahora = ($ahora ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone(self::ZONA));
        $dia = (int) $ahora->format('N');
        $minuto = (int) $ahora->format('G') * 60 + (int) $ahora->format('i');

        if (in_array($dia, $this->dias, true)) {
            foreach ($this->turnos as [$desde, $hasta]) {
                if ($minuto >= $desde && $minuto < $hasta) {
                    return ['abierto' => true, 'texto' => 'Abierto ahora · hasta las ' . self::hora($hasta) . ' hs'];
                }
            }
            foreach ($this->turnos as [$desde]) {
                if ($minuto < $desde) {
                    return ['abierto' => false, 'texto' => 'Cerrado ahora · abrimos hoy a las ' . self::hora($desde) . ' hs'];
                }
            }
        }
        for ($salto = 1; $salto <= 7; $salto++) {
            $candidato = ($dia - 1 + $salto) % 7 + 1;
            if (in_array($candidato, $this->dias, true)) {
                $cuando = $salto === 1 ? 'mañana' : 'el ' . self::DIAS[$candidato];
                return ['abierto' => false, 'texto' => 'Cerrado ahora · abrimos ' . $cuando . ' a las ' . self::hora($this->turnos[0][0]) . ' hs'];
            }
        }
        return ['abierto' => false, 'texto' => 'Cerrado ahora'];
    }

    /** Lo que necesita el JavaScript para repetir el cálculo en el navegador. */
    public function datos(): array
    {
        return ['dias' => $this->dias, 'turnos' => $this->turnos, 'nombres' => array_values(self::DIAS)];
    }

    private static function hora(int $minutos): string
    {
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;
        return $m === 0 ? (string) $h : sprintf('%d:%02d', $h, $m);
    }
}
