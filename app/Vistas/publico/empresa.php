<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
?>
<?= Vista::parcial('encabezado', ['titulo' => 'Nosotros', 'bajada' => 'Santa Fe Propiedades · ' . str_replace(' | ', ' · ', Cfg::get('matricula')), 'migas' => ['Nosotros']]) ?>

<section class="seccion">
  <div class="contenedor empresa">
    <div>
      <h2 class="seccion__titulo">Nuestra historia</h2>
      <p>Santa Fe Propiedades nace en 2007 del trabajo de un grupo de personas con más de 30 años en la construcción. Colegas, familiares y amigos nos pedían confiarnos sus propiedades porque buscaban transparencia, calidez humana y solidez.</p>
      <p>Desde entonces trabajamos en la administración de alquileres, las tasaciones y el asesoramiento en compraventa de inmuebles, con atención personalizada y dando soluciones a las necesidades de cada cliente.</p>
    </div>
    <ul class="cifras">
      <li><strong>2007</strong><span>en el mercado inmobiliario</span></li>
      <li><strong>+30</strong><span>años en la construcción</span></li>
      <li><strong><?= e(str_replace(' | ', ' · ', Cfg::get('matricula'))) ?></strong><span>matrículas del Colegio de Corredores</span></li>
    </ul>
  </div>
</section>

<?php if ($motivos): ?>
  <?= Vista::parcial('motivos', ['motivos' => $motivos]) ?>
<?php endif ?>

<?= Vista::parcial('captacion') ?>
