<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

$ga = Cfg::get('ga_measurement_id');
$whatsappGeneral = EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), 'Hola, les escribo desde la web de Santa Fe Propiedades.');
$telefonoFijo = Cfg::get('telefono_fijo');
?><!doctype html>
<html lang="es-AR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Santa Fe Propiedades') ?></title>
  <meta name="description" content="<?= e($descripcion ?? '') ?>">
  <?php if (!empty($canonica)): ?><link rel="canonical" href="<?= e($canonica) ?>"><?php endif ?>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Santa Fe Propiedades">
  <meta property="og:locale" content="es_AR">
  <meta property="og:title" content="<?= e($titulo ?? 'Santa Fe Propiedades') ?>">
  <meta property="og:description" content="<?= e($descripcion ?? '') ?>">
  <?php if (!empty($imagenOg)): ?><meta property="og:image" content="<?= e($imagenOg) ?>"><?php endif ?>
  <meta name="theme-color" content="#1c2331">
  <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preload" href="/assets/fuentes/open-sauce-sans-400.woff" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <?php if (!empty($mapa)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1/maplibre-gl.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1/maplibre-gl.min.js" defer></script>
    <script src="<?= e(asset('assets/js/mapa.js')) ?>" defer></script>
  <?php endif ?>
  <?php if ($ga !== ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
  <?php endif ?>
</head>
<body>
<?= Vista::parcial('iconos') ?>
<a class="saltar" href="#contenido">Saltar al contenido</a>

<header class="cabecera">
  <div class="contenedor cabecera__fila">
    <button class="cabecera__menu" type="button" aria-controls="navegacion" aria-expanded="false" aria-label="Abrir menú">
      <?= icono('menu') ?>
    </button>
    <a class="logo" href="/" aria-label="Santa Fe Propiedades, inicio"><?= Vista::parcial('logo') ?></a>
    <nav class="navegacion" id="navegacion" aria-label="Principal">
      <a href="/alquileres">Alquileres</a>
      <a href="/ventas">Ventas</a>
      <a href="/comerciales">Comerciales</a>
      <?php if (modulo('paginas')): ?>
        <a href="/servicios">Servicios</a>
        <a href="/la-empresa">La empresa</a>
      <?php endif ?>
      <a href="/contacto">Contacto</a>
    </nav>
    <a class="boton boton--whatsapp boton--chico cabecera__cta" href="<?= e($whatsappGeneral) ?>" target="_blank" rel="noopener"
       data-evento="consulta_whatsapp" data-origen="cabecera"><?= icono('whatsapp') ?><span>WhatsApp</span></a>
  </div>
</header>

<main id="contenido">
<?= $contenido ?>
</main>

<footer class="pie">
  <div class="contenedor pie__grilla">
    <div>
      <a class="logo logo--claro" href="/" aria-label="Santa Fe Propiedades, inicio"><?= Vista::parcial('logo') ?></a>
      <p class="pie__texto">Inmobiliaria en Santa Fe Capital desde 2007. Alquileres, ventas, tasaciones y administración de propiedades.</p>
      <p class="pie__matricula"><?= e(Cfg::get('matricula')) ?></p>
    </div>
    <div>
      <h2 class="pie__titulo">Contacto</h2>
      <ul class="pie__lista">
        <li><?= icono('pin') ?><?= e(Cfg::get('direccion')) ?></li>
        <li><?= icono('whatsapp') ?><a href="<?= e($whatsappGeneral) ?>" target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="pie"><?= e(Cfg::get('whatsapp_visible')) ?></a></li>
        <?php if ($telefonoFijo !== ''): ?>
          <li><?= icono('telefono') ?><a href="tel:+54<?= e(preg_replace('/\D+/', '', ltrim($telefonoFijo, '0'))) ?>" data-evento="click_telefono"><?= e($telefonoFijo) ?></a></li>
        <?php endif ?>
        <li><?= icono('mail') ?><a href="mailto:<?= e(Cfg::get('email')) ?>"><?= e(Cfg::get('email')) ?></a></li>
        <li><?= icono('reloj') ?><?= e(Cfg::get('horario')) ?></li>
      </ul>
    </div>
    <div>
      <h2 class="pie__titulo">Propiedades</h2>
      <ul class="pie__lista pie__lista--simple">
        <li><a href="/alquileres">En alquiler</a></li>
        <li><a href="/ventas">En venta</a></li>
        <li><a href="/comerciales">Locales, galpones y cocheras</a></li>
        <?php if (modulo('paginas')): ?><li><a href="/servicios">Tasaciones y servicios</a></li><?php endif ?>
        <?php if (modulo('captacion')): ?><li><a href="/alquila-con-nosotros">Alquilá o vendé con nosotros</a></li><?php endif ?>
        <?php if (modulo('faq')): ?><li><a href="/preguntas-frecuentes">Preguntas frecuentes</a></li><?php endif ?>
      </ul>
      <a class="pie__social" href="<?= e(Cfg::get('instagram_url')) ?>" target="_blank" rel="noopener"><?= icono('instagram') ?>@santafepropiedadesinmobiliaria</a>
    </div>
  </div>
  <p class="pie__legal contenedor">© <?= date('Y') ?> Santa Fe Propiedades</p>
</footer>

<?php if (empty($sinFlotante)): ?>
  <a class="whatsapp-flotante" href="<?= e($whatsappGeneral) ?>" target="_blank" rel="noopener" aria-label="Escribinos por WhatsApp"
     data-evento="consulta_whatsapp" data-origen="flotante"><?= icono('whatsapp') ?></a>
<?php endif ?>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
