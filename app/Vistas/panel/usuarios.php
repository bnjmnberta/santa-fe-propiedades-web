<?php
use App\Core\Csrf;
use App\Core\Sesion;
?>
<div class="panel-encabezado"><h1>Usuarios</h1></div>

<section class="panel-bloque">
  <ul class="panel-usuarios">
    <?php foreach ($usuarios as $usuario): ?>
      <li class="panel-usuario <?= $usuario['activo'] ? '' : 'panel-usuario--inactivo' ?>">
        <div>
          <strong><?= e($usuario['nombre']) ?></strong>
          <span class="panel-chip"><?= $usuario['rol'] === 'administrador' ? 'Administrador' : 'Editor' ?></span>
          <?= $usuario['activo'] ? '' : '<span class="panel-chip">Desactivado</span>' ?>
          <p><?= e($usuario['email']) ?> · último ingreso: <?= $usuario['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']))) : 'nunca' ?></p>
        </div>
        <details>
          <summary>Opciones</summary>
          <form action="/panel/usuarios/<?= (int) $usuario['id_usuario'] ?>/contrasena" method="post" class="panel-en-linea">
            <?= Csrf::campo() ?>
            <input type="password" name="contrasena" minlength="10" placeholder="Contraseña nueva (10+ caracteres)" autocomplete="new-password" required>
            <button class="boton boton--chico boton--secundario" type="submit">Cambiar contraseña</button>
          </form>
          <?php if ((int) $usuario['id_usuario'] !== Sesion::usuario()['id']): ?>
            <form action="/panel/usuarios/<?= (int) $usuario['id_usuario'] ?>/activo" method="post">
              <?= Csrf::campo() ?>
              <input type="hidden" name="activo" value="<?= $usuario['activo'] ? '0' : '1' ?>">
              <button class="panel-enlace-peligro" type="submit"><?= $usuario['activo'] ? 'Desactivar usuario' : 'Reactivar usuario' ?></button>
            </form>
          <?php endif ?>
        </details>
      </li>
    <?php endforeach ?>
  </ul>
</section>

<section class="panel-bloque">
  <h2>Nuevo usuario</h2>
  <form class="panel-formulario" action="/panel/usuarios" method="post">
    <?= Csrf::campo() ?>
    <label class="panel-campo"><span>Nombre</span><input type="text" name="nombre" required maxlength="80"></label>
    <label class="panel-campo"><span>Email</span><input type="email" name="email" required maxlength="120"></label>
    <label class="panel-campo"><span>Contraseña inicial</span><input type="password" name="contrasena" minlength="10" autocomplete="new-password" required></label>
    <label class="panel-campo">
      <span>Rol</span>
      <select name="rol">
        <option value="editor">Editor: carga y edita propiedades</option>
        <option value="administrador">Administrador: además usuarios y configuración</option>
      </select>
    </label>
    <button class="boton boton--primario" type="submit">Crear usuario</button>
  </form>
</section>
