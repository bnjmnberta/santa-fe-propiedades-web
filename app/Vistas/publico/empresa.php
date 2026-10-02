<?php
use App\Core\Vista;
?>
<?= Vista::parcial('encabezado', ['titulo' => 'Nosotros', 'migas' => ['Nosotros']]) ?>

<?php /* Los datos (año, matrículas, atención) viven en "¿Por qué elegirnos?", acá solo va la historia contada. */ ?>
<section class="seccion">
  <div class="contenedor historia">
    <h2 class="historia__titulo">Nuestra historia</h2>
    <p class="historia__destacado">Santa Fe Propiedades nace de un equipo que venía de la construcción y conocía las propiedades por dentro.</p>
    <div class="historia__texto">
      <p>Colegas, familiares y amigos nos pedían confiarnos sus propiedades porque buscaban transparencia, calidez humana y solidez.</p>
      <p>Desde entonces trabajamos en la administración de alquileres, las tasaciones y el asesoramiento en compraventa de inmuebles, con atención personalizada y dando soluciones a las necesidades de cada cliente.</p>
    </div>
  </div>
</section>

<?php if ($motivos): ?>
  <?= Vista::parcial('motivos', ['motivos' => $motivos]) ?>
<?php endif ?>

<?= Vista::parcial('captacion') ?>
