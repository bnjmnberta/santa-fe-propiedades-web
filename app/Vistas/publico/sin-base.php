<?php
// Se muestra cuando la base de datos no responde (public/index.php, respuesta 503).
// No usa el diseño general porque ese diseño lee la configuración de la base.
use App\Core\Vista;
?><!doctype html>
<html lang="es-AR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Volvemos en unos minutos | Santa Fe Propiedades</title>
  <link rel="icon" href="<?= e(asset('assets/img/icono-sf.svg')) ?>" type="image/svg+xml">
  <link rel="preload" href="/assets/fuentes/open-sauce-sans-400.woff" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
  <section class="seccion">
    <div class="contenedor error-pagina">
      <p><a class="logo" href="/"><?= Vista::parcial('logo') ?></a></p>
      <?php if ($enLocal): ?>
        <h1>La base de datos está apagada</h1>
        <p>La web la necesita para mostrar las propiedades. Abrí <strong>INICIAR WEB LOCAL.bat</strong>, en la carpeta del proyecto: prende la base y la web juntas.</p>
      <?php else: ?>
        <h1>Volvemos en unos minutos</h1>
        <p>Tenemos un problema técnico. Probá de nuevo en un rato.</p>
      <?php endif ?>
      <p><a class="boton boton--primario" href="">Probar de nuevo</a></p>
    </div>
  </section>
</body>
</html>
