<?php
use App\Core\Csrf;
use App\Core\Sesion;
use App\Core\Vista;

$usuario = Sesion::usuario();
$avisos = Sesion::tomarAvisos();
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/panel', PHP_URL_PATH);
?><!doctype html>
<html lang="es-AR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titulo ?? 'Panel') ?> · Panel Santa Fe Propiedades</title>
  <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/panel.css')) ?>">
</head>
<body class="panel">
<?= Vista::parcial('iconos') ?>

<?php if ($usuario): ?>
  <header class="panel-cabecera">
    <div class="panel-cabecera__fila">
      <a class="panel-cabecera__marca" href="/panel"><?= Vista::parcial('logo') ?><span class="panel-cabecera__etiqueta">Panel</span></a>
      <nav class="panel-nav" aria-label="Panel">
        <a href="/panel" <?= $ruta === '/panel' || str_starts_with($ruta, '/panel/propiedades') ? 'aria-current="page"' : '' ?>>Propiedades</a>
        <?php if (Sesion::esAdministrador()): ?>
          <a href="/panel/usuarios" <?= $ruta === '/panel/usuarios' ? 'aria-current="page"' : '' ?>>Usuarios</a>
          <a href="/panel/configuracion" <?= $ruta === '/panel/configuracion' ? 'aria-current="page"' : '' ?>>Configuración</a>
        <?php endif ?>
        <a href="/" target="_blank" rel="noopener">Ver la web ↗</a>
      </nav>
      <form class="panel-cabecera__salir" action="/panel/salir" method="post">
        <?= Csrf::campo() ?>
        <span><?= e($usuario['nombre']) ?></span>
        <button class="boton boton--chico panel-boton-suave" type="submit">Salir</button>
      </form>
    </div>
  </header>
<?php endif ?>

<main class="panel-contenido">
  <?php foreach ($avisos as $aviso): ?>
    <p class="panel-aviso panel-aviso--<?= e($aviso['tipo']) ?>" role="<?= $aviso['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= e($aviso['mensaje']) ?></p>
  <?php endforeach ?>
  <?= $contenido ?>
</main>

<script src="<?= e(asset('assets/js/panel.js')) ?>" defer></script>
</body>
</html>
