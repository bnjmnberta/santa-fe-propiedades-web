<?php
use App\Core\Csrf;
use App\Core\Vista;
?>
<div class="panel-ingreso">
  <div class="panel-ingreso__logo"><?= Vista::parcial('logo') ?></div>
  <h1>Panel de propiedades</h1>
  <form class="panel-formulario" action="/panel/ingresar" method="post">
    <?= Csrf::campo() ?>
    <input type="hidden" name="volver" value="<?= e($_GET['volver'] ?? ($_POST['volver'] ?? '')) ?>">
    <label class="panel-campo">
      <span>Email</span>
      <input type="email" name="email" value="<?= e($email ?? '') ?>" autocomplete="username" required autofocus>
    </label>
    <label class="panel-campo">
      <span>Contraseña</span>
      <input type="password" name="contrasena" autocomplete="current-password" required>
    </label>
    <button class="boton boton--primario boton--ancho" type="submit">Ingresar</button>
  </form>
</div>
