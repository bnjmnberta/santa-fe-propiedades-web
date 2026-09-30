<?php use App\Core\Csrf; ?>
<div class="panel-encabezado"><h1>Configuración del sitio</h1></div>

<form class="panel-formulario panel-formulario--ancho" action="/panel/configuracion" method="post">
  <?= Csrf::campo() ?>
  <?php foreach ($grupos as $grupo => $campos): ?>
    <fieldset class="panel-bloque">
      <legend><?= e($grupo) ?></legend>
      <?php foreach ($campos as $clave => [$etiqueta, $tipo]): ?>
        <?php if ($tipo === 'modulo'): ?>
          <label class="panel-check"><input type="checkbox" name="<?= e($clave) ?>" value="1" <?= ($valores[$clave] ?? '1') === '1' ? 'checked' : '' ?>> <?= e($etiqueta) ?></label>
        <?php else: ?>
          <label class="panel-campo panel-campo--completo">
            <span><?= e($etiqueta) ?></span>
            <input type="text" name="<?= e($clave) ?>" value="<?= e($valores[$clave] ?? '') ?>" maxlength="300">
          </label>
        <?php endif ?>
      <?php endforeach ?>
    </fieldset>
  <?php endforeach ?>
  <div class="panel-guardar"><button class="boton boton--primario" type="submit">Guardar</button></div>
</form>
