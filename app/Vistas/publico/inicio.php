<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;

/** @var list<App\Modelos\Propiedad> $alquileres */
/** @var list<App\Modelos\Propiedad> $ventas */
// Orden: eslogan con el buscador → destacadas → ¿Por qué elegirnos? → Alquilá / Vendé con nosotros.
// El buscador va arriba de todo porque buscar es lo primero que viene a hacer casi cualquier visitante.
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
<section class="portada"<?= $fondoPortada ? ' style="--fondo: url(\'' . e($fondoPortada) . '\')"' : '' ?>>
  <div class="contenedor portada__contenido">
    <p class="portada__antetitulo">Inmobiliaria en Santa Fe Capital · desde 2007</p>
    <h1 class="portada__titulo"><?= e(Cfg::get('eslogan', 'Tu lugar en Santa Fe')) ?></h1>
    <p class="portada__bajada"><?= e(Cfg::get('eslogan_bajada')) ?></p>
    <form class="buscador" action="/propiedades" method="get" role="search" aria-label="Buscar propiedades">
      <label class="campo campo--texto">
        <span class="campo__etiqueta">Barrio, calle o palabra clave</span>
        <input type="search" name="q" placeholder="Ej: Candioti, Bv. Gálvez, cochera">
      </label>
      <label class="campo">
        <span class="campo__etiqueta">Operación</span>
        <select name="operacion">
          <option value="">Todas</option>
          <option value="alquiler">Alquiler</option>
          <option value="venta">Venta</option>
          <option value="comerciales">Comerciales</option>
        </select>
      </label>
      <label class="campo">
        <span class="campo__etiqueta">Tipo</span>
        <select name="tipo">
          <option value="">Todos</option>
          <?php foreach ($tipos as $tipo): ?>
            <option value="<?= e($tipo['slug']) ?>"><?= e($tipo['nombre']) ?></option>
          <?php endforeach ?>
        </select>
      </label>
      <button class="boton boton--primario" type="submit"><?= icono('lupa') ?>Buscar</button>
    </form>
    <form class="buscador-codigo" action="/propiedades" method="get">
      <label for="codigo">¿Tenés el código de una propiedad?</label>
      <div class="buscador-codigo__fila">
        <input id="codigo" name="codigo" type="number" inputmode="numeric" min="1" placeholder="Ej: 258">
        <button class="boton boton--claro" type="submit">Ir</button>
      </div>
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
  <section class="seccion">
    <div class="contenedor">
      <h2 class="seccion__titulo seccion__titulo--centrado">¿Por qué elegirnos?</h2>
      <?= Vista::parcial('motivos', ['motivos' => $motivos]) ?>
      <?php if (modulo('paginas')): ?>
        <p class="centrado"><a class="enlace-flecha" href="/nosotros">Conocé más sobre nosotros <?= icono('flecha') ?></a></p>
      <?php endif ?>
    </div>
  </section>
<?php endif ?>

<?= Vista::parcial('captacion') ?>
