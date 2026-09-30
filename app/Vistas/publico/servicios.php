<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;
?>
<section class="encabezado-pagina">
  <div class="contenedor">
    <h1>Servicios</h1>
    <p>Todo lo que necesitás para alquilar, vender o tasar tu propiedad en Santa Fe.</p>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="servicios">
      <?php foreach ($servicios as $servicio): ?>
        <article class="servicio">
          <span class="servicio__icono"><?= icono($servicio['icono'] ?: 'casa') ?></span>
          <h2 class="servicio__titulo"><?= e($servicio['titulo']) ?></h2>
          <p><?= e($servicio['descripcion']) ?></p>
        </article>
      <?php endforeach ?>
    </div>
    <p class="servicios__cta">
      <a class="boton boton--whatsapp" href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), 'Hola, quiero consultar por una tasación.')) ?>"
         target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="tasacion"><?= icono('whatsapp') ?>Pedí tu tasación</a>
    </p>
  </div>
</section>

<?php if ($motivos): ?>
  <section class="seccion seccion--alterna">
    <div class="contenedor">
      <h2 class="seccion__titulo seccion__titulo--centrado">¿Por qué elegirnos?</h2>
      <?= Vista::parcial('motivos', ['motivos' => $motivos]) ?>
    </div>
  </section>
<?php endif ?>

<section class="seccion">
  <div class="contenedor">
    <?= Vista::parcial('contacto') ?>
  </div>
</section>

<?php if ($faqs): ?>
  <section class="seccion seccion--alterna" id="faq">
    <div class="contenedor contenedor--angosto">
      <h2 class="seccion__titulo">Preguntas frecuentes</h2>
      <?= Vista::parcial('faq', ['faqs' => $faqs]) ?>
    </div>
  </section>
<?php endif ?>
