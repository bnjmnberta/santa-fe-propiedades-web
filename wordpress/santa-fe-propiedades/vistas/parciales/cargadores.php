<?php
use App\Core\Vista;

/*
 * Pantalla de carga inicial y telón de transición entre secciones (de "Santa Fe Loader").
 * Están en el HTML desde el principio para que se vean en el primer cuadro, sin parpadeo.
 * El script del <head> decide si se muestran (clases sf-con-intro / sf-llegando en <html>)
 * y assets/js/cargadores.js maneja las fases. Sin JavaScript no aparecen nunca.
 */
?>
<div class="sf-intro is-build" role="status" aria-live="polite">
  <div class="sf-intro__inner">
    <?= Vista::parcial('marca-cuadros', ['tamanio' => 140]) ?>
  </div>
  <span class="sf-visually-hidden">Cargando…</span>
</div>
<div class="sf-pt is-idle" aria-hidden="true">
  <div class="sf-pt__panel sf-pt__panel--blue" style="--d: 0"></div>
  <div class="sf-pt__panel sf-pt__panel--cyan" style="--d: 1"></div>
  <div class="sf-pt__panel sf-pt__panel--white" style="--d: 2">
    <?= Vista::parcial('marca-cuadros', ['tamanio' => 96]) ?>
  </div>
</div>
