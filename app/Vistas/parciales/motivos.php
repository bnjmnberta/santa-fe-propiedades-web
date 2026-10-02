<?php
/**
 * "¿Por qué elegirnos?": franja oscura de ancho completo con un dato grande por motivo
 * ("Desde 2007", "CCI 099 · 731", "WhatsApp"), su título y la explicación.
 * Cada dato lleva un cuadrado con un color del logo. Va fuera de cualquier .contenedor.
 *
 * @var list<array{titulo: string, dato: ?string, descripcion: string, icono: ?string}> $motivos
 * @var ?array{0: string, 1: string} $enlace [texto, url] opcional, debajo de los motivos
 */
$enlace ??= null;
$colores = ['rojo', 'celeste', 'azul'];
?>
<section class="motivos" aria-labelledby="motivos-titulo">
  <div class="contenedor">
    <h2 class="motivos__titulo" id="motivos-titulo">¿Por qué elegirnos?</h2>
    <ul class="motivos__lista">
      <?php foreach ($motivos as $i => $motivo): ?>
        <li class="motivo">
          <span class="motivo__cuadro motivo__cuadro--<?= $colores[$i % 3] ?>" aria-hidden="true"></span>
          <?php if (!empty($motivo['dato'])): ?>
            <strong class="motivo__dato"><?= e($motivo['dato']) ?></strong>
          <?php else: ?>
            <span class="motivo__icono"><?= icono($motivo['icono'] ?: 'check') ?></span>
          <?php endif ?>
          <h3 class="motivo__titulo"><?= e($motivo['titulo']) ?></h3>
          <p><?= e($motivo['descripcion']) ?></p>
        </li>
      <?php endforeach ?>
    </ul>
    <?php if ($enlace): ?>
      <p class="motivos__enlace"><a class="enlace-flecha" href="<?= e($enlace[1]) ?>"><?= e($enlace[0]) ?> <?= icono('flecha') ?></a></p>
    <?php endif ?>
  </div>
</section>
