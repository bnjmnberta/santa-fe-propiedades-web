<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;

/** @var list<App\Modelos\Propiedad> $alquileres */
/** @var list<App\Modelos\Propiedad> $ventas */
// Orden: título con el buscador en una fila → destacadas (asoman en la primera pantalla) → ¿Por qué elegirnos? → Alquilá / Vendé con nosotros.
// Destacadas de alquiler y de venta, intercaladas, para el estado inicial ("Todas").
$destacadas = [];
foreach (range(0, max(count($alquileres), count($ventas)) - 1) as $n) {
    foreach ([$alquileres[$n] ?? null, $ventas[$n] ?? null] as $propiedad) {
        if ($propiedad !== null) {
            $destacadas[] = $propiedad;
        }
    }
}
?>
<section class="portada">
  <div class="contenedor portada__fila">
    <h1 class="portada__titulo"><?= e(Cfg::get('eslogan', 'Tu lugar en Santa Fe')) ?></h1>
    <form class="buscador" action="/propiedades" method="get" role="search">
      <fieldset class="buscador__operacion" data-segmentado>
        <legend class="solo-lectores">Operación</legend>
        <?php foreach (['' => 'Todas', 'alquiler' => 'Alquiler', 'venta' => 'Venta', 'comerciales' => 'Comerciales'] as $valor => $etiqueta): ?>
          <label class="opcion-seg"<?= $valor === 'venta' ? ' data-tono="venta"' : '' ?>>
            <input type="radio" name="operacion" value="<?= e($valor) ?>" <?= $valor === '' ? 'checked' : '' ?>>
            <span><?= e($etiqueta) ?></span>
          </label>
        <?php endforeach ?>
      </fieldset>
      <label class="buscador__campo">
        <span class="solo-lectores">Barrio, calle o código</span>
        <?= icono('lupa') ?>
        <input type="search" name="q" placeholder="Barrio, calle o código" autocomplete="off">
      </label>
      <button class="boton boton--primario" type="submit">Buscar</button>
    </form>
  </div>
</section>

<?php /* El buscador de arriba filtra esta sección sin cambiar de página (app.js); sin JavaScript envía a /propiedades. */ ?>
<section class="seccion" id="resultados" aria-labelledby="destacadas-titulo">
  <div class="contenedor">
    <div class="seccion__encabezado">
      <h2 class="seccion__titulo" id="destacadas-titulo" data-resultados-titulo data-inicial="Propiedades destacadas">Propiedades destacadas</h2>
      <p class="resultados" role="status" data-resultados-estado></p>
    </div>
    <div class="carrusel" data-carrusel data-resultados>
      <div class="carrusel__pista" data-resultados-pista>
        <?php foreach ($destacadas as $propiedad): ?>
          <?= Vista::parcial('tarjeta', ['propiedad' => $propiedad]) ?>
        <?php endforeach ?>
      </div>
      <button class="carrusel__boton carrusel__boton--anterior" type="button" aria-label="Anteriores" data-carrusel-anterior><?= icono('anterior') ?></button>
      <button class="carrusel__boton carrusel__boton--siguiente" type="button" aria-label="Siguientes" data-carrusel-siguiente><?= icono('siguiente') ?></button>
      <p class="carrusel__ver-todas"><a class="boton boton--secundario" href="/propiedades" data-resultados-enlace data-inicial="Ver todas las propiedades">Ver todas las propiedades</a></p>
    </div>
  </div>
</section>

<?php if ($motivos): ?>
  <?= Vista::parcial('motivos', ['motivos' => $motivos, 'enlace' => modulo('paginas') ? ['Conocé más sobre nosotros', '/nosotros'] : null]) ?>
<?php endif ?>

<?= Vista::parcial('captacion') ?>
