<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador mínimo. Patrones con parámetros: '/propiedad/{codigo:\d+}'.
 * {nombre} toma un segmento sin '/'; {nombre:regex} usa la expresión indicada.
 * Los parámetros llegan a la acción como argumentos con nombre.
 */
final class Router
{
    /** @var array<string, list<array{0: string, 1: array{0: class-string, 1: string}}>> */
    private array $rutas = [];

    /** @param array{0: class-string, 1: string} $accion */
    public function get(string $patron, array $accion): void
    {
        $this->rutas['GET'][] = [$this->compilar($patron), $accion];
    }

    /** @param array{0: class-string, 1: string} $accion */
    public function post(string $patron, array $accion): void
    {
        $this->rutas['POST'][] = [$this->compilar($patron), $accion];
    }

    public function despachar(string $metodo, string $uri): void
    {
        $ruta = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        if ($ruta !== '/') {
            $ruta = rtrim($ruta, '/');
        }
        if ($metodo === 'HEAD') {
            $metodo = 'GET';
        }

        foreach ($this->rutas[$metodo] ?? [] as [$regex, [$clase, $accion]]) {
            if (preg_match($regex, $ruta, $coincidencias)) {
                $parametros = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
                (new $clase())->$accion(...$parametros);
                return;
            }
        }

        throw new NoEncontrado($ruta);
    }

    private function compilar(string $patron): string
    {
        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            fn (array $m): string => '(?<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $patron
        );
        return '#^' . $regex . '$#u';
    }
}
