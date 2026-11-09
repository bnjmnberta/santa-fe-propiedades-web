<?php
declare(strict_types=1);

// Funciones que usan las vistas de la web (mismos nombres que en la versión PHP original).

if (!function_exists('e')) {
    /** Escapa texto para HTML. Toda salida de datos en las vistas pasa por acá. */
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** URL absoluta (para Open Graph, WhatsApp y canonical). Si ya es absoluta, se deja como está. */
    function url(string $ruta = '/'): string
    {
        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }
        return rtrim(home_url(), '/') . '/' . ltrim($ruta, '/');
    }
}

if (!function_exists('asset')) {
    /** Archivo de assets/ del plugin, con versión por fecha de modificación. */
    function asset(string $ruta): string
    {
        $ruta = ltrim($ruta, '/');
        $archivo = SFP_DIR . '/' . $ruta;
        return SFP_URL . $ruta . (is_file($archivo) ? '?v=' . filemtime($archivo) : '');
    }
}

if (!function_exists('redirigir')) {
    function redirigir(string $destino, int $codigo = 302): never
    {
        header('Location: ' . $destino, true, $codigo);
        exit;
    }
}

if (!function_exists('numero')) {
    function numero(float|int $valor, int $decimales = 0): string
    {
        return number_format($valor, $decimales, ',', '.');
    }
}

if (!function_exists('modulo')) {
    /** Secciones que se prenden y apagan desde Configuración (modulo_mapa, modulo_faq, modulo_paginas, modulo_captacion). */
    function modulo(string $nombre): bool
    {
        return App\Repositorios\ConfiguracionRepositorio::get('modulo_' . $nombre, '1') === '1';
    }
}

if (!function_exists('distanciaMetros')) {
    /** Distancia en metros entre dos coordenadas (fórmula del haversine). */
    function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radio = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $radio * asin(min(1, sqrt($a)));
    }
}

if (!function_exists('icono')) {
    /** Ícono del sprite SVG definido en vistas/parciales/iconos.php. */
    function icono(string $nombre, string $clase = 'icono'): string
    {
        return '<svg class="' . e($clase) . '" aria-hidden="true"><use href="#i-' . e($nombre) . '"></use></svg>';
    }
}
