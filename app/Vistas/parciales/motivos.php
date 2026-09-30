<?php /** @var list<array{titulo: string, descripcion: string, icono: ?string}> $motivos */ ?>
<div class="motivos">
  <?php foreach ($motivos as $motivo): ?>
    <article class="motivo">
      <span class="motivo__icono"><?= icono($motivo['icono'] ?: 'check') ?></span>
      <h3 class="motivo__titulo"><?= e($motivo['titulo']) ?></h3>
      <p><?= e($motivo['descripcion']) ?></p>
    </article>
  <?php endforeach ?>
</div>
