<?php
/**
 * Preguntas frecuentes agrupadas por tema: el nombre del grupo a la izquierda (con un cuadrado de color del logo)
 * y sus preguntas desplegables a la derecha. Los grupos salen del campo "grupo" y respetan el orden de las preguntas;
 * las que no tienen grupo van al final, en "Otras preguntas". En celular el título queda arriba de sus preguntas.
 *
 * @var list<array{pregunta: string, respuesta: string, grupo?: string}> $faqs
 * @var ?int $nivel nivel de título de cada tema: 3 cuando la sección ya tiene su h2 (Servicios), 2 en la página propia
 */
$nivel = (int) ($nivel ?? 3);
$grupos = [];
foreach ($faqs as $faq) {
    $grupos[trim($faq['grupo'] ?? '') ?: 'Otras preguntas'][] = $faq;
}
if (isset($grupos['Otras preguntas']) && count($grupos) > 1) {
    $otras = $grupos['Otras preguntas'];
    unset($grupos['Otras preguntas']);
    $grupos['Otras preguntas'] = $otras;
}
$colores = ['rojo', 'celeste', 'azul'];
$sinGrupos = array_keys($grupos) === ['Otras preguntas'];
?>
<div class="faq">
  <?php $n = 0; foreach ($grupos as $nombre => $preguntas): ?>
    <section class="faq__grupo">
      <?php if (!$sinGrupos): ?>
        <h<?= $nivel ?> class="faq__titulo"><span class="faq__cuadro faq__cuadro--<?= $colores[$n % 3] ?>" aria-hidden="true"></span><?= e($nombre) ?></h<?= $nivel ?>>
      <?php endif ?>
      <div class="faq__lista">
        <?php foreach ($preguntas as $faq): ?>
          <details class="faq__item">
            <summary><?= e($faq['pregunta']) ?></summary>
            <p><?= e($faq['respuesta']) ?></p>
          </details>
        <?php endforeach ?>
      </div>
    </section>
  <?php $n++; endforeach ?>
</div>
