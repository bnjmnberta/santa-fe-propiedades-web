<?php
declare(strict_types=1);

namespace App\Controladores\Publico;

use App\Core\Vista;
use App\Modelos\FiltrosCatalogo;
use App\Modelos\Operacion;
use App\Repositorios\CatalogoRepositorio;
use App\Repositorios\PropiedadRepositorio;

final class CatalogoControlador
{
    private const POR_PAGINA = 12;

    private const SECCIONES = [
        'ventas'      => ['Propiedades en venta', 'Casas, departamentos, lotes y galpones en venta en Santa Fe Capital y alrededores.'],
        'alquileres'  => ['Propiedades en alquiler', 'Departamentos, casas y locales en alquiler en Santa Fe Capital.'],
        'comerciales' => ['Locales, galpones y cocheras', 'Propiedades comerciales en venta y alquiler en Santa Fe.'],
    ];

    public function listar(?string $seccion = null): void
    {
        // "Buscar por código" (regla h): si existe, va directo a la ficha.
        $codigo = filter_input(INPUT_GET, 'codigo', FILTER_VALIDATE_INT);
        if ($codigo) {
            $propiedad = (new PropiedadRepositorio())->porCodigo($codigo);
            if ($propiedad !== null) {
                redirigir($propiedad->url());
            }
        }

        $filtros = FiltrosCatalogo::desdeConsulta($_GET, $seccion);
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $resultado = (new PropiedadRepositorio())->buscarPublicadas($filtros, $pagina, self::POR_PAGINA);
        $catalogo = new CatalogoRepositorio();
        // Desde el buscador de la portada la operación llega como parámetro: el título la refleja igual.
        $claveTitulo = $seccion ?? match (true) {
            $filtros->soloComerciales                         => 'comerciales',
            $filtros->operacion === Operacion::Venta          => 'ventas',
            $filtros->operacion === Operacion::Alquiler       => 'alquileres',
            default                                           => null,
        };
        [$titulo, $descripcion] = self::SECCIONES[$claveTitulo] ?? ['Todas las propiedades', 'Catálogo completo de Santa Fe Propiedades.'];

        $repositorio = new PropiedadRepositorio();
        Vista::render('publico/catalogo', [
            'mapa'           => modulo('mapa') ? [
                'propiedades' => $repositorio->puntosMapa($filtros),
                'puntos'      => $catalogo->puntosInteres(),
            ] : null,
            'titulo'         => $titulo . ' | Santa Fe Propiedades',
            'encabezado'     => $titulo,
            'descripcion'    => $descripcion,
            'seccion'        => $seccion,
            'filtros'        => $filtros,
            'propiedades'    => $resultado['items'],
            'total'          => $resultado['total'],
            'pagina'         => $pagina,
            'paginas'        => (int) ceil($resultado['total'] / self::POR_PAGINA),
            'tipos'          => $catalogo->tiposConPublicadas(),
            'zonas'          => $catalogo->zonasConPublicadas(),
            'codigoBuscado'  => $codigo ?: null,
        ]);
    }
}
