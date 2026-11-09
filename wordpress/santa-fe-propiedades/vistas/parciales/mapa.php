<?php
/** @var array{propiedades: list<array>, puntos: list<array>, centrar?: bool} $mapa */
?>
<div class="mapa">
  <div class="mapa__lienzo" id="mapa" role="region" aria-label="Mapa de propiedades y puntos de referencia"></div>
  <ul class="mapa__leyenda">
    <li><span class="mapa__punto mapa__punto--alquiler">$</span>Alquiler</li>
    <li><span class="mapa__punto mapa__punto--venta">$</span>Venta</li>
    <li><?= icono('birrete') ?>Facultades</li>
    <li><?= icono('bus') ?>Terminal</li>
    <li><?= icono('ancla') ?>Puerto</li>
    <li><?= icono('ola') ?>Costanera</li>
  </ul>
</div>
<script type="application/json" id="datos-mapa"><?= json_encode($mapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
