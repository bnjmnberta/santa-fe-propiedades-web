<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;
?>
<?= Vista::parcial('encabezado', ['titulo' => 'Servicios', 'bajada' => 'Todo lo que necesitás para alquilar, vender o tasar tu propiedad en Santa Fe.', 'migas' => ['Servicios']]) ?>

<?php
/* Scroll horizontal guiado por el scroll vertical: al bajar, la página se queda fija y los
   servicios pasan de costado, uno por vez, cada uno con su botón de WhatsApp.
   Sin JavaScript (o con "reducir movimiento") se ven todos uno debajo del otro. */
$colores = ['azul', 'celeste', 'rojo'];
$total = count($servicios);
?>
<section class="servicios-h" data-servicios-h style="--n: <?= $total ?>" aria-label="Servicios">
  <div class="servicios-h__escena">
    <div class="servicios-h__pista">
      <?php foreach ($servicios as $i => $servicio): ?>
        <article class="servicio-h" data-servicio-h>
          <span class="servicio-h__numero" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="contenedor servicio-h__contenido">
            <span class="servicio-h__icono servicio__icono--<?= $colores[$i % 3] ?>"><?= icono($servicio['icono'] ?: 'casa') ?></span>
            <div class="servicio-h__texto">
              <h2 class="servicio-h__titulo"><?= e($servicio['titulo']) ?></h2>
              <p><?= e($servicio['descripcion']) ?></p>
              <a class="boton boton--whatsapp" href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), 'Hola, quiero consultar por ' . mb_strtolower($servicio['titulo']) . '.')) ?>"
                 target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="servicio"><?= icono('whatsapp') ?>Consultar por WhatsApp</a>
            </div>
          </div>
        </article>
      <?php endforeach ?>
    </div>
    <div class="contenedor servicios-h__progreso">
      <p class="servicios-h__contador" data-servicios-contador aria-live="off"></p>
      <div class="servicios-h__pasos" role="group" aria-label="Ir a un servicio">
        <?php foreach ($servicios as $i => $servicio): ?>
          <button type="button" class="servicios-h__paso servicios-h__paso--<?= $colores[$i % 3] ?>" data-paso aria-label="<?= e($servicio['titulo']) ?>">
            <span class="servicios-h__barra"><i></i></span>
            <span class="servicios-h__nombre"><?= e($servicio['titulo']) ?></span>
          </button>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <?= Vista::parcial('contacto') ?>
  </div>
</section>

<?php if ($faqs): ?>
  <section class="seccion seccion--alterna" id="faq">
    <div class="contenedor">
      <h2 class="seccion__titulo">Preguntas frecuentes</h2>
      <?= Vista::parcial('faq', ['faqs' => $faqs]) ?>
    </div>
  </section>
<?php endif ?>
