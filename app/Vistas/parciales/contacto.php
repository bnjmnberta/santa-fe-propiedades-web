<?php
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

$fotoLocal = Cfg::get('foto_local');
$telefono = Cfg::get('telefono_fijo');
?>
<div class="contacto" id="contacto">
  <div class="contacto__foto">
    <?php if ($fotoLocal !== ''): ?>
      <img src="<?= e(asset($fotoLocal)) ?>" alt="Oficina de Santa Fe Propiedades en <?= e(Cfg::get('direccion')) ?>" loading="lazy">
    <?php else: ?>
      <div class="sin-foto"><?= icono('casa', 'sin-foto__icono') ?><span>Foto del local (pendiente)</span></div>
    <?php endif ?>
  </div>
  <div class="contacto__datos">
    <h2 class="seccion__titulo">Contacto</h2>
    <ul class="contacto__lista">
      <li><?= icono('whatsapp') ?><a href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), 'Hola, les escribo desde la web de Santa Fe Propiedades.')) ?>"
             target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="contacto"><?= e(Cfg::get('whatsapp_visible')) ?></a></li>
      <li><?= icono('pin') ?><a href="https://www.google.com/maps/search/?api=1&query=<?= e(rawurlencode(Cfg::get('direccion') . ', Argentina')) ?>" target="_blank" rel="noopener"><?= e(Cfg::get('direccion')) ?></a></li>
      <?php if ($telefono !== ''): ?>
        <li><?= icono('telefono') ?><a href="tel:+54<?= e(preg_replace('/\D+/', '', ltrim($telefono, '0'))) ?>" data-evento="click_telefono"><?= e($telefono) ?></a></li>
      <?php endif ?>
      <li><?= icono('mail') ?><a href="mailto:<?= e(Cfg::get('email')) ?>"><?= e(Cfg::get('email')) ?></a></li>
      <li><?= icono('reloj') ?><?= e(Cfg::get('horario')) ?></li>
    </ul>
  </div>
</div>
