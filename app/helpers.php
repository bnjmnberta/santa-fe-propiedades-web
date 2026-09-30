<?php
declare(strict_types=1);

use App\Core\Config;

/** Escapa texto para HTML. Toda salida de datos en las vistas pasa por acá. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL absoluta (para Open Graph, WhatsApp, canonical y sitemap). */
function url(string $ruta = '/'): string
{
    return rtrim((string) Config::get('app.url_base', ''), '/') . '/' . ltrim($ruta, '/');
}

/** Ruta a un archivo de public/ con versión por fecha de modificación, para cachear sin quedar desactualizado. */
function asset(string $ruta): string
{
    $ruta = '/' . ltrim($ruta, '/');
    $archivo = PUBLICO . $ruta;
    return $ruta . (is_file($archivo) ? '?v=' . filemtime($archivo) : '');
}

function redirigir(string $destino, int $codigo = 302): never
{
    header('Location: ' . $destino, true, $codigo);
    exit;
}

/** Página anterior (Referer) solo si es de este mismo sitio; si no, el destino por defecto. */
function paginaAnterior(string $defecto): string
{
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $partes = parse_url($referer);
    if ($referer === '' || ($partes['host'] ?? null) !== ($_SERVER['HTTP_HOST'] ?? null) && ($partes['host'] ?? null) !== parse_url((string) ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
        return $defecto;
    }
    $ruta = $partes['path'] ?? $defecto;
    return str_starts_with($ruta, '/') ? $ruta . (isset($partes['query']) ? '?' . $partes['query'] : '') : $defecto;
}

function numero(float|int $valor, int $decimales = 0): string
{
    return number_format($valor, $decimales, ',', '.');
}

/**
 * Módulos que se muestran por etapas (configuración modulo_*: mapa, faq, paginas, captacion).
 * Permiten entregar todo construido y habilitar cada parte cuando corresponda.
 */
function modulo(string $nombre): bool
{
    return App\Repositorios\ConfiguracionRepositorio::get('modulo_' . $nombre, '1') === '1';
}

/** Distancia en metros entre dos coordenadas (fórmula del haversine). */
function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $radio = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 2 * $radio * asin(min(1, sqrt($a)));
}

/** Ícono del sprite SVG definido en app/Vistas/parciales/iconos.php. */
function icono(string $nombre, string $clase = 'icono'): string
{
    return '<svg class="' . e($clase) . '" aria-hidden="true"><use href="#i-' . e($nombre) . '"></use></svg>';
}
