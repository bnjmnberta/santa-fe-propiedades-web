<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;

/** @var list<App\Modelos\Propiedad> $alquileres */
/** @var list<App\Modelos\Propiedad> $ventas */
// Orden: título con el buscador en una fila → destacadas (asoman en la primera pantalla) → ¿Por qué elegirnos? → Alquilá / Vendé con nosotros.
$carrusel = function (array $propiedades, string $id, string $enlace, string $textoEnlace, bool $oculto): string {
    ob_start(); ?>
    <div class="carrusel" id="<?= e($id) ?>" role="tabpanel" <?= $oculto ? 'hidden' : '' ?> data-carrusel>
      <div class="carrusel__pista">
        <?php foreach ($propiedades as $propiedad): ?>
          <?= Vista::parcial('tarjeta', ['propiedad' => $propiedad]) ?>
        <?php endforeach ?>
      </div>
      <button class="carrusel__boton carrusel__boton--anterior" type="button" aria-label="Anteriores" data-carrusel-anterior><?= icono('anterior') ?></button>
      <button class="carrusel__boton carrusel__boton--siguiente" type="button" aria-label="Siguientes" data-carrusel-siguiente><?= icono('siguiente') ?></button>
      <p class="carrusel__ver-todas"><a class="boton boton--secundario" href="<?= e($enlace) ?>"><?= e($textoEnlace) ?></a></p>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<section class="portada">
  <div class="contenedor portada__fila">
    <h1 class="portada__titulo"><?= e(Cfg::get('eslogan', 'Tu lugar en Santa Fe')) ?></h1>
    <form class="buscador" action="/propiedades" method="get" role="search">
      <fieldset class="buscador__operacion">
        <legend class="solo-lectores">Operación</legend>
        <?php foreach (['' => 'Todas', 'alquiler' => 'Alquiler', 'venta' => 'Venta', 'comerciales' => 'Comerciales'] as $valor => $etiqueta): ?>
          <label class="opcion-seg">
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

<section class="seccion">
  <div class="contenedor">
    <div class="seccion__encabezado">
      <h2 class="seccion__titulo">Propiedades destacadas</h2>
      <div class="interruptor" role="tablist" aria-label="Operación" data-pestanias>
        <button type="button" role="tab" aria-selected="true" aria-controls="destacadas-alquiler" class="interruptor__opcion interruptor__opcion--alquiler">Alquiler</button>
        <button type="button" role="tab" aria-selected="false" aria-controls="destacadas-venta" class="interruptor__opcion interruptor__opcion--venta">Venta</button>
      </div>
    </div>
    <?= $carrusel($alquileres, 'destacadas-alquiler', '/alquileres', 'Ver todos los alquileres', false) ?>
    <?= $carrusel($ventas, 'destacadas-venta', '/ventas', 'Ver todas las ventas', true) ?>
  </div>
</section>

<?php if ($motivos): ?>
  <?= Vista::parcial('motivos', ['motivos' => $motivos, 'enlace' => modulo('paginas') ? ['Conocé más sobre nosotros', '/nosotros'] : null]) ?>
<?php endif ?>

<?= Vista::parcial('captacion') ?>
