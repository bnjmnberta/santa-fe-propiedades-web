<?php if (!modulo('captacion')) { return; } ?>
<div class="captacion">
  <a class="captacion__tarjeta captacion__tarjeta--alquiler" href="/alquila-con-nosotros">
    <span class="captacion__antetitulo">Propietarios</span>
    <strong>Alquilá con nosotros</strong>
    <span>Buscamos inquilinos y administramos tu propiedad.</span>
    <span class="captacion__flecha"><?= icono('flecha') ?></span>
  </a>
  <a class="captacion__tarjeta captacion__tarjeta--venta" href="/vende-con-nosotros">
    <span class="captacion__antetitulo">Propietarios</span>
    <strong>Vendé con nosotros</strong>
    <span>Tasación, difusión y asesoramiento hasta la escritura.</span>
    <span class="captacion__flecha"><?= icono('flecha') ?></span>
  </a>
</div>
