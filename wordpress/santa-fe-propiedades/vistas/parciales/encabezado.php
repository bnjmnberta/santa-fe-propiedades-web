<?php
/**
 * Encabezado de título de las páginas internas: banda fina con el color de la operación
 * (azul alquiler, rojo venta, azul marino para el resto), migas de pan arriba, título y bajada.
 *
 * @var string $titulo
 * @var ?string $bajada
 * @var string $tono 'alquiler' | 'venta' | 'neutro'
 * @var list<string> $migas Texto de cada miga después de "Inicio"; la última es la página actual y no lleva enlace.
 *                          Una miga intermedia puede ser [texto, enlace].
 */
$bajada ??= null;
$tono ??= 'neutro';
$migas ??= [];
?>
<section class="encabezado-pagina encabezado-pagina--<?= e($tono) ?>">
  <div class="contenedor">
    <nav class="encabezado-pagina__migas" aria-label="Migas de pan">
      <ol>
        <li><a href="/">Inicio</a></li>
        <?php foreach ($migas as $i => $miga): ?>
          <?php if (is_array($miga)): ?>
            <li><a href="<?= e($miga[1]) ?>"><?= e($miga[0]) ?></a></li>
          <?php elseif ($i === array_key_last($migas)): ?>
            <li><span aria-current="page"><?= e($miga) ?></span></li>
          <?php else: ?>
            <li><?= e($miga) ?></li>
          <?php endif ?>
        <?php endforeach ?>
      </ol>
    </nav>
    <div class="encabezado-pagina__fila">
      <h1><?= e($titulo) ?></h1>
      <?php if ($bajada): ?><p><?= e($bajada) ?></p><?php endif ?>
    </div>
  </div>
</section>
