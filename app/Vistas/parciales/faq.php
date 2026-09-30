<?php /** @var list<array{pregunta: string, respuesta: string}> $faqs */ ?>
<div class="faq">
  <?php foreach ($faqs as $faq): ?>
    <details class="faq__item">
      <summary><?= e($faq['pregunta']) ?></summary>
      <p><?= e($faq['respuesta']) ?></p>
    </details>
  <?php endforeach ?>
</div>
