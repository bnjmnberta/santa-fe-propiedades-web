<?php
declare(strict_types=1);

namespace App\Servicios;

/**
 * Valida y normaliza el formulario de propiedad del panel.
 * Acepta números como los escribe la gente ("159.000", "52,5") y coordenadas pegadas de Google Maps.
 */
final class ValidadorPropiedad
{
    /**
     * @param list<int> $idsTipo  tipos válidos
     * @param list<int> $idsZona  zonas válidas
     * @return array{0: array<string, mixed>, 1: array<string, string>} [datos, errores por campo]
     */
    public static function validar(array $entrada, array $idsTipo, array $idsZona): array
    {
        $errores = [];
        $texto = static function (string $campo, int $largo) use ($entrada, &$errores): ?string {
            $valor = trim((string) ($entrada[$campo] ?? ''));
            if (mb_strlen($valor) > $largo) {
                $errores[$campo] = "Máximo $largo caracteres.";
            }
            return $valor === '' ? null : $valor;
        };
        $numero = static function (string $campo, float $maximo) use ($entrada, &$errores): ?float {
            $valor = trim((string) ($entrada[$campo] ?? ''));
            if ($valor === '') {
                return null;
            }
            // "159.000" y "159000" son lo mismo; "52,5" es decimal.
            $normalizado = str_replace(',', '.', preg_replace('/\.(?=\d{3}(\D|$))/', '', $valor));
            if (!is_numeric($normalizado) || (float) $normalizado < 0 || (float) $normalizado > $maximo) {
                $errores[$campo] = 'Tiene que ser un número válido.';
                return null;
            }
            return (float) $normalizado;
        };
        $entero = static fn (string $campo) => ($n = $numero($campo, 50)) === null ? null : (int) round($n);

        $datos = [
            'titulo'           => $texto('titulo', 150),
            'operacion'        => in_array($entrada['operacion'] ?? '', ['venta', 'alquiler'], true) ? $entrada['operacion'] : null,
            'id_tipo'          => in_array((int) ($entrada['id_tipo'] ?? 0), $idsTipo, true) ? (int) $entrada['id_tipo'] : null,
            'id_zona'          => in_array((int) ($entrada['id_zona'] ?? 0), $idsZona, true) ? (int) $entrada['id_zona'] : null,
            'direccion'        => $texto('direccion', 150),
            'mostrar_direccion' => isset($entrada['mostrar_direccion']) ? 1 : 0,
            'moneda'           => in_array($entrada['moneda'] ?? '', ['ARS', 'USD'], true) ? $entrada['moneda'] : null,
            'precio'           => $numero('precio', 999999999999),
            'expensas'         => $numero('expensas', 99999999),
            'expensas_detalle' => $texto('expensas_detalle', 60),
            'requisitos'       => $texto('requisitos', 150),
            'disponible_desde' => null,
            'amoblado'         => isset($entrada['amoblado']) ? 1 : 0,
            'dormitorios'      => $entero('dormitorios'),
            'banos'            => $entero('banos'),
            'cocheras'         => $entero('cocheras') ?? 0,
            'sup_cubierta'     => $numero('sup_cubierta', 99999999),
            'sup_terreno'      => $numero('sup_terreno', 99999999),
            'frente_m'         => $numero('frente_m', 9999),
            'fondo_m'          => $numero('fondo_m', 9999),
            'regimen'          => $texto('regimen', 60),
            'planta'           => $texto('planta', 30),
            'ubicacion_unidad' => in_array($entrada['ubicacion_unidad'] ?? '', ['frente', 'contrafrente', 'interno', 'lateral'], true) ? $entrada['ubicacion_unidad'] : null,
            'caracteristicas'  => $texto('caracteristicas', 3000),
            'servicios'        => $texto('servicios', 300),
            'referencias'      => $texto('referencias', 250),
            'descripcion'      => $texto('descripcion', 5000),
            'instagram_url'    => $texto('instagram_url', 255),
            'latitud'          => null,
            'longitud'         => null,
            'destacada'        => isset($entrada['destacada']) ? 1 : 0,
        ];

        if ($datos['titulo'] === null) {
            $errores['titulo'] = 'Poné un título.';
        }
        if ($datos['operacion'] === null) {
            $errores['operacion'] = 'Elegí venta o alquiler.';
        }
        if ($datos['id_tipo'] === null) {
            $errores['id_tipo'] = 'Elegí el tipo de propiedad.';
        }
        // Regla c: precio opcional, pero con moneda; un precio en 0 cuenta como "consultar".
        if ($datos['precio'] !== null && $datos['precio'] <= 0) {
            $datos['precio'] = null;
        }
        if ($datos['precio'] !== null && $datos['moneda'] === null) {
            $errores['moneda'] = 'Elegí pesos o dólares.';
        }
        if ($datos['precio'] === null) {
            $datos['moneda'] = null;
        }

        $fecha = trim((string) ($entrada['disponible_desde'] ?? ''));
        if ($fecha !== '') {
            $valida = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
            $valida ? $datos['disponible_desde'] = $valida->format('Y-m-d') : $errores['disponible_desde'] = 'Fecha inválida.';
        }

        if ($datos['instagram_url'] !== null && !preg_match('#^https://(www\.)?instagram\.com/#', $datos['instagram_url'])) {
            $errores['instagram_url'] = 'Pegá el enlace completo del post (https://www.instagram.com/...).';
        }

        $coordenadas = trim((string) ($entrada['coordenadas'] ?? ''));
        if ($coordenadas !== '') {
            $par = self::coordenadas($coordenadas);
            $par ? [$datos['latitud'], $datos['longitud']] = $par : $errores['coordenadas'] = 'No reconozco esas coordenadas. Pegá algo como -31.6396, -60.7132.';
        }

        return [$datos, $errores];
    }

    /**
     * "-31.6396, -60.7132", o un enlace de Google Maps con "@-31.63,-60.71" o "q=-31.63,-60.71".
     * Solo acepta puntos dentro de la zona de Santa Fe y alrededores.
     * @return array{0: float, 1: float}|null
     */
    public static function coordenadas(string $texto): ?array
    {
        if (!preg_match('/(-3\d\.\d+)\s*,\s*(-6\d\.\d+)/', $texto, $m)) {
            return null;
        }
        [$lat, $lng] = [(float) $m[1], (float) $m[2]];
        return $lat > -32.5 && $lat < -30.5 && $lng > -61.5 && $lng < -60 ? [round($lat, 6), round($lng, 6)] : null;
    }
}
