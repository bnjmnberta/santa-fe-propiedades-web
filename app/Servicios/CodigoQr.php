<?php
declare(strict_types=1);

namespace App\Servicios;

use InvalidArgumentException;

/**
 * Código QR en SVG, generado en el servidor y sin librerías externas (para los carteles de las propiedades).
 * Escribe texto en modo "bytes" con corrección de errores Media (M) por defecto, en versiones 1 a 10 (hasta 213 caracteres):
 * sobra para una dirección web. Sigue el estándar ISO/IEC 18004 y el esquema del generador de código abierto de Nayuki.
 */
final class CodigoQr
{
    private const VERSION_MAXIMA = 10;
    /** Bits de formato de cada nivel de corrección. */
    private const BITS_NIVEL = ['L' => 1, 'M' => 0, 'Q' => 3, 'H' => 2];
    /** Palabras de corrección por bloque, por versión (1 a 10). */
    private const CORRECCION_POR_BLOQUE = [
        'L' => [7, 10, 15, 20, 26, 18, 20, 24, 30, 18],
        'M' => [10, 16, 26, 18, 24, 16, 18, 22, 22, 26],
        'Q' => [13, 22, 18, 26, 18, 24, 18, 22, 20, 24],
        'H' => [17, 28, 22, 16, 22, 28, 26, 26, 24, 28],
    ];
    /** Cantidad de bloques, por versión (1 a 10). */
    private const BLOQUES = [
        'L' => [1, 1, 1, 1, 1, 2, 2, 2, 2, 4],
        'M' => [1, 1, 1, 2, 2, 4, 4, 4, 5, 5],
        'Q' => [1, 1, 2, 2, 4, 4, 6, 6, 8, 8],
        'H' => [1, 1, 2, 4, 4, 4, 5, 6, 8, 8],
    ];

    private int $lado;
    /** @var list<list<bool>> */
    private array $modulos = [];
    /** @var list<list<bool>> módulos fijos (patrones, formato, versión) que la máscara no toca */
    private array $fijos = [];

    private function __construct(private readonly int $version, private readonly string $nivel)
    {
        $this->lado = $version * 4 + 17;
        $fila = array_fill(0, $this->lado, false);
        $this->modulos = array_fill(0, $this->lado, $fila);
        $this->fijos = array_fill(0, $this->lado, $fila);
    }

    /** @return list<list<bool>> matriz de módulos (true = oscuro), sin el margen blanco */
    public static function matriz(string $texto, string $nivel = 'M'): array
    {
        if (!isset(self::BITS_NIVEL[$nivel])) {
            throw new InvalidArgumentException('Nivel de corrección desconocido: ' . $nivel);
        }
        $bytes = array_values(unpack('C*', $texto) ?: []);
        for ($version = 1; $version <= self::VERSION_MAXIMA; $version++) {
            $bitsCuenta = $version <= 9 ? 8 : 16;
            if (4 + $bitsCuenta + 8 * count($bytes) <= self::palabrasDeDatos($version, $nivel) * 8) {
                break;
            }
        }
        if ($version > self::VERSION_MAXIMA) {
            throw new InvalidArgumentException('El texto es demasiado largo para el QR (máximo 213 caracteres).');
        }

        $palabras = self::armarPalabrasDeDatos($bytes, $version, $nivel);
        $qr = new self($version, $nivel);
        $qr->dibujarPatrones();
        $qr->dibujarPalabras(self::agregarCorreccion($palabras, $version, $nivel));
        $qr->elegirMascara();
        return $qr->modulos;
    }

    /**
     * SVG listo para imprimir: un solo trazo con todos los módulos oscuros y un margen blanco de 4 módulos (el mínimo del estándar).
     * Con "crispEdges" los bordes quedan nítidos a cualquier tamaño.
     */
    public static function svg(string $texto, string $descripcion = 'Código QR', string $nivel = 'M', int $margen = 4): string
    {
        $matriz = self::matriz($texto, $nivel);
        $lado = count($matriz);
        $total = $lado + 2 * $margen;
        $trazo = '';
        foreach ($matriz as $y => $fila) {
            $x = 0;
            while ($x < $lado) {
                if (!$fila[$x]) {
                    $x++;
                    continue;
                }
                $inicio = $x;
                while ($x < $lado && $fila[$x]) {
                    $x++;
                }
                $trazo .= sprintf('M%d %dh%dv1h-%dz', $inicio + $margen, $y + $margen, $x - $inicio, $x - $inicio);
            }
        }
        $titulo = htmlspecialchars($descripcion, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $total . ' ' . $total . '" shape-rendering="crispEdges" role="img" aria-label="' . $titulo . '">'
            . '<title>' . $titulo . '</title>'
            . '<rect width="' . $total . '" height="' . $total . '" fill="#fff"/>'
            . '<path d="' . $trazo . '" fill="#000"/></svg>';
    }

    // ------------------------------------------------------------------ datos y corrección de errores

    /** Módulos disponibles para datos y corrección en una versión (sin patrones). */
    private static function modulosDeDatos(int $version): int
    {
        $total = (16 * $version + 128) * $version + 64;
        if ($version >= 2) {
            $alineacion = intdiv($version, 7) + 2;
            $total -= (25 * $alineacion - 10) * $alineacion - 55;
            if ($version >= 7) {
                $total -= 36;
            }
        }
        return $total;
    }

    private static function palabrasDeDatos(int $version, string $nivel): int
    {
        return intdiv(self::modulosDeDatos($version), 8)
            - self::CORRECCION_POR_BLOQUE[$nivel][$version - 1] * self::BLOQUES[$nivel][$version - 1];
    }

    /**
     * @param list<int> $bytes
     * @return list<int>
     */
    private static function armarPalabrasDeDatos(array $bytes, int $version, string $nivel): array
    {
        $bits = [];
        $agregar = static function (int $valor, int $cantidad) use (&$bits): void {
            for ($i = $cantidad - 1; $i >= 0; $i--) {
                $bits[] = ($valor >> $i) & 1;
            }
        };
        $agregar(0b0100, 4); // modo bytes
        $agregar(count($bytes), $version <= 9 ? 8 : 16);
        foreach ($bytes as $byte) {
            $agregar($byte, 8);
        }
        $capacidad = self::palabrasDeDatos($version, $nivel) * 8;
        $agregar(0, min(4, $capacidad - count($bits))); // terminador
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        for ($relleno = 0xEC; count($bits) < $capacidad; $relleno ^= 0xEC ^ 0x11) {
            $agregar($relleno, 8);
        }
        $palabras = [];
        foreach (array_chunk($bits, 8) as $octeto) {
            $palabras[] = bindec(implode('', $octeto));
        }
        return $palabras;
    }

    /**
     * Divide en bloques, calcula Reed-Solomon de cada uno e intercala.
     * @param list<int> $datos
     * @return list<int>
     */
    private static function agregarCorreccion(array $datos, int $version, string $nivel): array
    {
        $cantidadBloques = self::BLOQUES[$nivel][$version - 1];
        $largoCorreccion = self::CORRECCION_POR_BLOQUE[$nivel][$version - 1];
        $palabrasTotales = intdiv(self::modulosDeDatos($version), 8);
        $bloquesCortos = $cantidadBloques - $palabrasTotales % $cantidadBloques;
        $largoCorto = intdiv($palabrasTotales, $cantidadBloques);
        $divisor = self::divisorReedSolomon($largoCorreccion);

        $bloques = [];
        $posicion = 0;
        for ($i = 0; $i < $cantidadBloques; $i++) {
            $largo = $largoCorto - $largoCorreccion + ($i < $bloquesCortos ? 0 : 1);
            $bloque = array_slice($datos, $posicion, $largo);
            $posicion += $largo;
            $correccion = self::restoReedSolomon($bloque, $divisor);
            if ($i < $bloquesCortos) {
                $bloque[] = 0; // lugar que se saltea al intercalar
            }
            $bloques[] = array_merge($bloque, $correccion);
        }

        $resultado = [];
        $largoBloque = count($bloques[0]);
        for ($i = 0; $i < $largoBloque; $i++) {
            foreach ($bloques as $j => $bloque) {
                if ($i !== $largoCorto - $largoCorreccion || $j >= $bloquesCortos) {
                    $resultado[] = $bloque[$i];
                }
            }
        }
        return $resultado;
    }

    private static function multiplicar(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z;
    }

    /** @return list<int> */
    private static function divisorReedSolomon(int $grado): array
    {
        $resultado = array_fill(0, $grado, 0);
        $resultado[$grado - 1] = 1;
        $raiz = 1;
        for ($i = 0; $i < $grado; $i++) {
            for ($j = 0; $j < $grado; $j++) {
                $resultado[$j] = self::multiplicar($resultado[$j], $raiz);
                if ($j + 1 < $grado) {
                    $resultado[$j] ^= $resultado[$j + 1];
                }
            }
            $raiz = self::multiplicar($raiz, 0x02);
        }
        return $resultado;
    }

    /**
     * @param list<int> $datos
     * @param list<int> $divisor
     * @return list<int>
     */
    private static function restoReedSolomon(array $datos, array $divisor): array
    {
        $resultado = array_fill(0, count($divisor), 0);
        foreach ($datos as $byte) {
            $factor = $byte ^ array_shift($resultado);
            $resultado[] = 0;
            foreach ($divisor as $i => $coeficiente) {
                $resultado[$i] ^= self::multiplicar($coeficiente, $factor);
            }
        }
        return $resultado;
    }

    // ------------------------------------------------------------------ dibujo de la matriz

    private function fijar(int $x, int $y, bool $oscuro): void
    {
        $this->modulos[$y][$x] = $oscuro;
        $this->fijos[$y][$x] = true;
    }

    private function dibujarPatrones(): void
    {
        for ($i = 0; $i < $this->lado; $i++) {
            $this->fijar(6, $i, $i % 2 === 0);
            $this->fijar($i, 6, $i % 2 === 0);
        }
        $this->dibujarBuscador(3, 3);
        $this->dibujarBuscador($this->lado - 4, 3);
        $this->dibujarBuscador(3, $this->lado - 4);

        $posiciones = $this->posicionesDeAlineacion();
        $cantidad = count($posiciones);
        for ($i = 0; $i < $cantidad; $i++) {
            for ($j = 0; $j < $cantidad; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $cantidad - 1) || ($i === $cantidad - 1 && $j === 0)) {
                    continue; // esas esquinas son los buscadores
                }
                $this->dibujarAlineacion($posiciones[$i], $posiciones[$j]);
            }
        }
        $this->dibujarFormato(0);
        $this->dibujarVersion();
    }

    private function dibujarBuscador(int $cx, int $cy): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $distancia = max(abs($dx), abs($dy));
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x >= 0 && $x < $this->lado && $y >= 0 && $y < $this->lado) {
                    $this->fijar($x, $y, $distancia !== 2 && $distancia !== 4);
                }
            }
        }
    }

    private function dibujarAlineacion(int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->fijar($cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
    }

    /** @return list<int> */
    private function posicionesDeAlineacion(): array
    {
        if ($this->version === 1) {
            return [];
        }
        $cantidad = intdiv($this->version, 7) + 2;
        $paso = intdiv($this->version * 4 + $cantidad * 2 + 1, $cantidad * 2 - 2) * 2;
        $resultado = [6];
        for ($posicion = $this->lado - 7; count($resultado) < $cantidad; $posicion -= $paso) {
            array_splice($resultado, 1, 0, [$posicion]);
        }
        return $resultado;
    }

    private function dibujarFormato(int $mascara): void
    {
        $datos = (self::BITS_NIVEL[$this->nivel] << 3) | $mascara;
        $resto = $datos;
        for ($i = 0; $i < 10; $i++) {
            $resto = ($resto << 1) ^ (($resto >> 9) * 0x537);
        }
        $bits = (($datos << 10) | $resto) ^ 0x5412;
        $bit = static fn (int $i): bool => (($bits >> $i) & 1) === 1;

        for ($i = 0; $i <= 5; $i++) {
            $this->fijar(8, $i, $bit($i));
        }
        $this->fijar(8, 7, $bit(6));
        $this->fijar(8, 8, $bit(7));
        $this->fijar(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->fijar(14 - $i, 8, $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $this->fijar($this->lado - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->fijar(8, $this->lado - 15 + $i, $bit($i));
        }
        $this->fijar(8, $this->lado - 8, true); // módulo oscuro fijo
    }

    private function dibujarVersion(): void
    {
        if ($this->version < 7) {
            return;
        }
        $resto = $this->version;
        for ($i = 0; $i < 12; $i++) {
            $resto = ($resto << 1) ^ (($resto >> 11) * 0x1F25);
        }
        $bits = ($this->version << 12) | $resto;
        for ($i = 0; $i < 18; $i++) {
            $oscuro = (($bits >> $i) & 1) === 1;
            $a = $this->lado - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->fijar($a, $b, $oscuro);
            $this->fijar($b, $a, $oscuro);
        }
    }

    /** @param list<int> $palabras */
    private function dibujarPalabras(array $palabras): void
    {
        $i = 0;
        $total = count($palabras) * 8;
        for ($derecha = $this->lado - 1; $derecha >= 1; $derecha -= 2) {
            if ($derecha === 6) {
                $derecha = 5; // la columna 6 es el patrón de temporización
            }
            for ($vertical = 0; $vertical < $this->lado; $vertical++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $derecha - $j;
                    $haciaArriba = (($derecha + 1) & 2) === 0;
                    $y = $haciaArriba ? $this->lado - 1 - $vertical : $vertical;
                    if (!$this->fijos[$y][$x] && $i < $total) {
                        $this->modulos[$y][$x] = (($palabras[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                        $i++;
                    }
                }
            }
        }
    }

    // ------------------------------------------------------------------ máscara

    private function elegirMascara(): void
    {
        $mejor = 0;
        $menor = PHP_INT_MAX;
        for ($mascara = 0; $mascara < 8; $mascara++) {
            $this->aplicarMascara($mascara);
            $this->dibujarFormato($mascara);
            $penalidad = $this->penalidad();
            if ($penalidad < $menor) {
                $menor = $penalidad;
                $mejor = $mascara;
            }
            $this->aplicarMascara($mascara); // vuelve a invertir: la deja como estaba
        }
        $this->aplicarMascara($mejor);
        $this->dibujarFormato($mejor);
    }

    private function aplicarMascara(int $mascara): void
    {
        for ($y = 0; $y < $this->lado; $y++) {
            for ($x = 0; $x < $this->lado; $x++) {
                $invierte = match ($mascara) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => $x * $y % 2 + $x * $y % 3 === 0,
                    6 => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
                    default => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
                };
                if ($invierte && !$this->fijos[$y][$x]) {
                    $this->modulos[$y][$x] = !$this->modulos[$y][$x];
                }
            }
        }
    }

    /** Puntaje de penalidad del estándar (menos es mejor): rachas, bloques de 2×2, patrones tipo buscador y equilibrio de colores. */
    private function penalidad(): int
    {
        $resultado = 0;
        $lado = $this->lado;

        for ($orientacion = 0; $orientacion < 2; $orientacion++) {
            for ($a = 0; $a < $lado; $a++) {
                $colorRacha = false;
                $largoRacha = 0;
                $historial = array_fill(0, 7, 0);
                for ($b = 0; $b < $lado; $b++) {
                    $oscuro = $orientacion === 0 ? $this->modulos[$a][$b] : $this->modulos[$b][$a];
                    if ($oscuro === $colorRacha) {
                        $largoRacha++;
                        if ($largoRacha === 5) {
                            $resultado += 3;
                        } elseif ($largoRacha > 5) {
                            $resultado++;
                        }
                    } else {
                        $this->agregarHistorial($largoRacha, $historial);
                        if (!$colorRacha) {
                            $resultado += $this->contarPatrones($historial) * 40;
                        }
                        $colorRacha = $oscuro;
                        $largoRacha = 1;
                    }
                }
                $resultado += $this->terminarYContar($colorRacha, $largoRacha, $historial) * 40;
            }
        }

        for ($y = 0; $y < $lado - 1; $y++) {
            for ($x = 0; $x < $lado - 1; $x++) {
                $color = $this->modulos[$y][$x];
                if ($color === $this->modulos[$y][$x + 1] && $color === $this->modulos[$y + 1][$x] && $color === $this->modulos[$y + 1][$x + 1]) {
                    $resultado += 3;
                }
            }
        }

        $oscuros = 0;
        foreach ($this->modulos as $fila) {
            $oscuros += count(array_filter($fila));
        }
        $total = $lado * $lado;
        $k = (int) ceil(abs($oscuros * 20 - $total * 10) / $total) - 1;
        return $resultado + $k * 10;
    }

    /** @param list<int> $historial */
    private function agregarHistorial(int $largoRacha, array &$historial): void
    {
        if ($historial[0] === 0) {
            $largoRacha += $this->lado; // borde blanco del comienzo de la línea
        }
        array_pop($historial);
        array_unshift($historial, $largoRacha);
    }

    /** @param list<int> $historial */
    private function contarPatrones(array $historial): int
    {
        $n = $historial[1];
        $nucleo = $n > 0 && $historial[2] === $n && $historial[3] === $n * 3 && $historial[4] === $n && $historial[5] === $n;
        return ($nucleo && $historial[0] >= $n * 4 && $historial[6] >= $n ? 1 : 0)
            + ($nucleo && $historial[6] >= $n * 4 && $historial[0] >= $n ? 1 : 0);
    }

    /** @param list<int> $historial */
    private function terminarYContar(bool $colorRacha, int $largoRacha, array $historial): int
    {
        if ($colorRacha) {
            $this->agregarHistorial($largoRacha, $historial);
            $largoRacha = 0;
        }
        $largoRacha += $this->lado; // borde blanco del final de la línea
        $this->agregarHistorial($largoRacha, $historial);
        return $this->contarPatrones($historial);
    }
}
