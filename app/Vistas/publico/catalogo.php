<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

/** @var App\Modelos\FiltrosCatalogo $filtros */
/** @var list<App\Modelos\Propiedad> $propiedades */
$accion = $seccion === null ? '/propiedades' : '/' . $seccion;
$pestanias = ['alquileres' => 'Alquileres', 'ventas' => 'Ventas', 'comerciales' => 'Comerciales', '' => 'Todas'];
$enlacePagina = fn (int $numero): string => $accion . '?' . http_build_query($filtros->aConsulta($seccion) + ['pagina' => $numero]);
// En /propiedades la operación viaja como parámetro (viene del buscador de la portada).
$operacionOculta = $filtros->aConsulta($seccion)['operacion'] ?? null;
?>
<section class="encabezado-pagina">
  <div class="contenedor">
    <h1><?= e($encabezado) ?></h1>
    <p><?= e($descripcion) ?></p>
  </div>
</section>

<section class="seccion seccion--compacta">
  <div class="contenedor">
    <nav class="pestanias" aria-label="Operación">
      <?php foreach ($pestanias as $clave => $nombre): ?>
        <a href="<?= $clave === '' ? '/propiedades' : '/' . e($clave) ?>" <?= ($seccion ?? '') === $clave ? 'aria-current="page"' : '' ?>><?= e($nombre) ?></a>
      <?php endforeach ?>
    </nav>

    <form class="busqueda" action="<?= e($accion) ?>" method="get" role="search">
      <?php if ($operacionOculta !== null): ?>
        <input type="hidden" name="operacion" value="<?= e($operacionOculta) ?>">
      <?php endif ?>
      <div class="busqueda__barra">
        <?= icono('lupa', 'icono busqueda__lupa') ?>
        <input class="busqueda__texto" type="search" name="q" value="<?= e($filtros->texto ?? '') ?>" placeholder="Buscá por barrio, calle o palabra clave" aria-label="Búsqueda">
        <button class="boton boton--primario boton--chico" type="submit">Buscar</button>
      </div>
      <details class="busqueda__filtros" <?= $filtros->hayFiltrosAvanzados() ? 'open' : '' ?>>
        <summary><?= icono('filtros') ?>Filtros</summary>
        <div class="filtros" data-envio-automatico>
          <label class="campo">
            <span class="campo__etiqueta">Tipo</span>
            <select name="tipo">
              <option value="">Todos</option>
              <?php foreach ($tipos as $tipo): ?>
                <option value="<?= e($tipo['slug']) ?>" <?= $filtros->tipo === $tipo['slug'] ? 'selected' : '' ?>><?= e($tipo['nombre']) ?></option>
              <?php endforeach ?>
            </select>
          </label>
          <label class="campo">
            <span class="campo__etiqueta">Zona</span>
            <select name="zona">
              <option value="">Todas</option>
              <?php foreach ($zonas as $zona): ?>
                <option value="<?= e($zona['slug']) ?>" <?= $filtros->zona === $zona['slug'] ? 'selected' : '' ?>><?= e($zona['nombre']) ?></option>
              <?php endforeach ?>
            </select>
          </label>
          <label class="campo">
            <span class="campo__etiqueta">Dormitorios</span>
            <select name="dormitorios">
              <option value="">Indistinto</option>
              <?php foreach ([1, 2, 3] as $cantidad): ?>
                <option value="<?= $cantidad ?>" <?= $filtros->dormitorios === $cantidad ? 'selected' : '' ?>><?= $cantidad ?> o más</option>
              <?php endforeach ?>
            </select>
          </label>
        </div>
      </details>
    </form>

    <?php if ($codigoBuscado): ?>
      <p class="aviso">No encontramos una propiedad con el código <?= e($codigoBuscado) ?>. Te mostramos el catálogo completo.</p>
    <?php endif ?>

    <p class="resultados"><?= $total === 1 ? '1 propiedad' : e($total) . ' propiedades' ?></p>

    <?php if ($propiedades): ?>
      <div class="grilla">
        <?php foreach ($propiedades as $propiedad): ?>
          <?= Vista::parcial('tarjeta', ['propiedad' => $propiedad]) ?>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <?php if ($paginas > 1): ?>
      <nav class="paginacion" aria-label="Páginas">
        <?php for ($numero = 1; $numero <= $paginas; $numero++): ?>
          <a href="<?= e($enlacePagina($numero)) ?>" <?= $numero === $pagina ? 'aria-current="page"' : '' ?>><?= $numero ?></a>
        <?php endfor ?>
      </nav>
    <?php endif ?>

    <?php if (!empty($mapa) && $mapa['propiedades']): ?>
      <h2 class="seccion__titulo seccion__titulo--mapa">En el mapa</h2>
      <p class="mapa__bajada">Las propiedades junto a facultades, la terminal, el puerto y la costanera.</p>
      <?= Vista::parcial('mapa', ['mapa' => $mapa]) ?>
    <?php endif ?>

    <aside class="rescate">
      <h2>¿No encontraste lo que buscabas? Hablanos</h2>
      <p>Contanos qué necesitás: muchas propiedades entran antes de publicarse.</p>
      <a class="boton boton--whatsapp" href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), 'Hola, estoy buscando una propiedad y no encontré lo que necesito en la web.')) ?>"
         target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="rescate"><?= icono('whatsapp') ?>WhatsApp</a>
    </aside>

    <?= Vista::parcial('captacion') ?>
  </div>
</section>
