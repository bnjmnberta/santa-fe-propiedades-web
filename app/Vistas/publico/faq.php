<?php use App\Core\Vista; ?>
<section class="encabezado-pagina">
  <div class="contenedor">
    <h1>Preguntas frecuentes</h1>
    <p>Si no encontrás tu respuesta, escribinos por WhatsApp.</p>
  </div>
</section>

<section class="seccion">
  <div class="contenedor contenedor--angosto">
    <?= Vista::parcial('faq', ['faqs' => $faqs]) ?>
  </div>
</section>
