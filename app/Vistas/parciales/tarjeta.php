<?php
/** @var App\Modelos\Propiedad $propiedad */
use App\Modelos\EstadoPropiedad;
?>
<article class="tarjeta">
  <a class="tarjeta__enlace" href="<?= e($propiedad->url()) ?>">
    <div class="tarjeta__foto">
      <?php if ($propiedad->urlPortada()): ?>
        <img src="<?= e($propiedad->urlPortada(480)) ?>" alt="<?= e($propiedad->titulo) ?>" loading="lazy" decoding="async" width="480" height="360">
      <?php else: ?>
        <div class="sin-foto"><?= icono('casa', 'sin-foto__icono') ?><span>Fotos próximamente</span></div>
      <?php endif ?>
      <span class="etiqueta etiqueta--<?= e($propiedad->operacion->value) ?>"><?= e($propiedad->operacion->etiqueta()) ?></span>
      <?php if ($propiedad->estado === EstadoPropiedad::Reservado): ?>
        <span class="etiqueta etiqueta--estado">Reservado</span>
      <?php endif ?>
    </div>
    <div class="tarjeta__cuerpo">
      <p class="tarjeta__precio"><?= e($propiedad->precioTexto()) ?></p>
      <h3 class="tarjeta__titulo"><?= e($propiedad->titulo) ?></h3>
      <p class="tarjeta__ubicacion"><?= icono('pin') ?><span><?= e($propiedad->ubicacion() ?: $propiedad->tipo) ?></span></p>
      <?php if ($propiedad->rasgos()): ?>
        <ul class="rasgos">
          <?php foreach ($propiedad->rasgos() as [$icono, $texto]): ?>
            <li><?= icono($icono) ?><?= e($texto) ?></li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </div>
  </a>
</article>
