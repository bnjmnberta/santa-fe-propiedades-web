<?php
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

/**
 * Cierre de contacto de la página Servicios: el WhatsApp como camino principal a la izquierda y los demás
 * datos en filas con el cuadrado de color del logo a la derecha. La página Contacto tiene la versión completa.
 */
$telefono = Cfg::get('telefono_fijo');
$direccion = Cfg::get('direccion');
?>
<div class="cierre-contacto" id="contacto">
  <div class="cierre-contacto__cabeza">
    <h2 class="cierre-contacto__titulo">Contacto</h2>
    <p>Escribinos por WhatsApp y te respondemos en el horario de atención, o pasá por la oficina.</p>
    <a class="boton boton--whatsapp" href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), 'Hola, les escribo desde la web de Santa Fe Propiedades.')) ?>"
       target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="servicios-contacto"><?= icono('whatsapp') ?>WhatsApp · <?= e(Cfg::get('whatsapp_visible')) ?></a>
    <a class="enlace-flecha" href="/contacto">Ver todas las formas de contacto <?= icono('flecha') ?></a>
  </div>
  <ul class="cierre-contacto__filas">
    <?php if ($telefono !== ''): ?>
      <li><a class="fila-contacto" href="tel:+54<?= e(preg_replace('/\D+/', '', ltrim($telefono, '0'))) ?>" data-evento="click_telefono" data-origen="servicios-contacto">
        <span class="fila-contacto__cuadro fila-contacto__cuadro--telefono"><?= icono('telefono') ?></span>
        <span class="fila-contacto__texto"><span class="fila-contacto__rotulo">Teléfono de la oficina</span><strong class="fila-contacto__valor"><?= e($telefono) ?></strong></span>
        <?= icono('flecha') ?></a></li>
    <?php endif ?>
    <li><a class="fila-contacto" href="mailto:<?= e(Cfg::get('email')) ?>">
      <span class="fila-contacto__cuadro fila-contacto__cuadro--email"><?= icono('mail') ?></span>
      <span class="fila-contacto__texto"><span class="fila-contacto__rotulo">Email</span><strong class="fila-contacto__valor"><?= e(Cfg::get('email')) ?></strong></span>
      <?= icono('flecha') ?></a></li>
    <li><a class="fila-contacto" href="https://www.google.com/maps/search/?api=1&query=<?= e(rawurlencode($direccion . ', Argentina')) ?>" target="_blank" rel="noopener">
      <span class="fila-contacto__cuadro fila-contacto__cuadro--direccion"><?= icono('pin') ?></span>
      <span class="fila-contacto__texto"><span class="fila-contacto__rotulo">Oficina</span><strong class="fila-contacto__valor"><?= e($direccion) ?></strong></span>
      <?= icono('flecha') ?></a></li>
    <li><div class="fila-contacto fila-contacto--fija">
      <span class="fila-contacto__cuadro fila-contacto__cuadro--horario"><?= icono('reloj') ?></span>
      <span class="fila-contacto__texto"><span class="fila-contacto__rotulo">Horario</span><strong class="fila-contacto__valor"><?= e(Cfg::get('horario')) ?></strong></span>
    </div></li>
  </ul>
</div>
