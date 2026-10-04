<?php use App\Core\Vista; ?>
<?= Vista::parcial('encabezado', ['titulo' => 'Preguntas frecuentes', 'bajada' => 'Si no encontrás tu respuesta, escribinos por WhatsApp.', 'migas' => ['Preguntas frecuentes']]) ?>

<section class="seccion">
  <div class="contenedor">
    <?= Vista::parcial('faq', ['faqs' => $faqs]) ?>
  </div>
</section>
