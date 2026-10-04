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
  <link rel="icon" href="<?= e(asset('assets/img/icono-sf.svg')) ?>" type="image/svg+xml" sizes="any">
  <link rel="icon" href="<?= e(asset('assets/img/favicon-32.png')) ?>" type="image/png" sizes="32x32">
  <link rel="icon" href="<?= e(asset('assets/img/favicon-48.png')) ?>" type="image/png" sizes="48x48">
  <link rel="shortcut icon" href="/favicon.ico">
  <link rel="apple-touch-icon" href="<?= e(asset('assets/img/apple-touch-icon.png')) ?>">
  <link rel="preload" href="/assets/fuentes/open-sauce-sans-400.woff" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/cargadores.css')) ?>">
  <?php /* Antes del primer cuadro: pantalla de carga en la primera visita de la sesión, o
           página tapada si se llegó con la transición entre secciones (assets/js/cargadores.js). */ ?>
  <script>(function(){var h=document.documentElement;h.classList.add('js');try{var s=sessionStorage;if(!s.getItem('sf-intro')){s.setItem('sf-intro','1');h.classList.add('sf-con-intro');}else if(s.getItem('sf-pt')){h.classList.add('sf-llegando');}s.removeItem('sf-pt');}catch(e){}})();</script>
  <?php if (!empty($mapa)): ?>
    <?php /* mapa.js trae MapLibre (CSS y JS) recién cuando el mapa está por verse: no pesa en la carga de la página. */ ?>
    <script src="<?= e(asset('assets/js/mapa.js')) ?>" defer></script>
  <?php endif ?>
  <?php if ($ga !== ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
  <?php endif ?>
</head>
<body>
<?= Vista::parcial('iconos') ?>
<?= Vista::parcial('cargadores') ?>
<a class="saltar" href="#contenido">Saltar al contenido</a>

<header class="cabecera">
  <div class="contenedor cabecera__fila">
    <button class="cabecera__menu" type="button" aria-controls="navegacion" aria-expanded="false" aria-label="Abrir menú">
      <?= icono('menu') ?>
    </button>
    <a class="logo" href="/" aria-label="Santa Fe Propiedades, inicio"><?= Vista::parcial('logo') ?></a>
    <?php
    /* El menú marca la página actual (aria-current). Las vistas pueden fijarla con $menuActivo (por ejemplo la ficha de una propiedad). */
    $rutaActual = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $menuActivo ??= ltrim($rutaActual, '/');
    $marca = static fn (string $clave): string => $menuActivo === $clave ? ' aria-current="page"' : '';
    ?>
    <nav class="navegacion" id="navegacion" aria-label="Principal">
      <a href="/alquileres"<?= $marca('alquileres') ?>>Alquileres</a>
      <a href="/ventas"<?= $marca('ventas') ?>>Ventas</a>
      <a href="/comerciales"<?= $marca('comerciales') ?>>Comerciales</a>
      <?php if (modulo('paginas')): ?>
        <a href="/servicios"<?= $marca('servicios') ?>>Servicios</a>
        <a href="/nosotros"<?= $marca('nosotros') ?>>Nosotros</a>
      <?php endif ?>
      <a href="/contacto"<?= $marca('contacto') ?>>Contacto</a>
    </nav>
    <a class="boton boton--whatsapp boton--chico cabecera__cta" href="<?= e($whatsappGeneral) ?>" target="_blank" rel="noopener"
       data-evento="consulta_whatsapp" data-origen="cabecera"><?= icono('whatsapp') ?><span>WhatsApp</span></a>
  </div>
</header>

<main id="contenido">
<?= $contenido ?>
</main>

<?php /* Pie compacto en tres filas: logo y enlaces, datos de contacto en una línea, y la línea legal. */ ?>
<footer class="pie">
  <div class="contenedor pie__fila">
    <a class="logo logo--claro" href="/" aria-label="Santa Fe Propiedades, inicio"><?= Vista::parcial('logo') ?></a>
    <nav class="pie__enlaces" aria-label="Pie de página">
      <a href="/alquileres">En alquiler</a>
      <a href="/ventas">En venta</a>
      <a href="/comerciales">Locales, galpones y cocheras</a>
      <?php if (modulo('paginas')): ?><a href="/servicios">Tasaciones y servicios</a><?php endif ?>
      <?php if (modulo('captacion')): ?><a href="/alquila-con-nosotros">Alquilá o vendé con nosotros</a><?php endif ?>
      <?php if (modulo('faq')): ?><a href="/preguntas-frecuentes">Preguntas frecuentes</a><?php endif ?>
      <a href="/contacto">Contacto</a>
    </nav>
  </div>
  <div class="contenedor">
    <ul class="pie__datos" aria-label="Contacto">
      <li><?= icono('pin') ?><?= e(Cfg::get('direccion')) ?></li>
      <li><?= icono('whatsapp') ?><a href="<?= e($whatsappGeneral) ?>" target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="pie"><?= e(Cfg::get('whatsapp_visible')) ?></a></li>
      <?php if ($telefonoFijo !== ''): ?>
        <li><?= icono('telefono') ?><a href="tel:+54<?= e(preg_replace('/\D+/', '', ltrim($telefonoFijo, '0'))) ?>" data-evento="click_telefono"><?= e($telefonoFijo) ?></a></li>
      <?php endif ?>
      <li><?= icono('mail') ?><a href="mailto:<?= e(Cfg::get('email')) ?>"><?= e(Cfg::get('email')) ?></a></li>
      <li><?= icono('instagram') ?><a href="<?= e(Cfg::get('instagram_url')) ?>" target="_blank" rel="noopener">@santafepropiedadesinmobiliaria</a></li>
      <li><?= icono('reloj') ?><?= e(Cfg::get('horario')) ?></li>
    </ul>
  </div>
  <div class="contenedor pie__legal">
    <span>© <?= date('Y') ?> Santa Fe Propiedades · Inmobiliaria en Santa Fe Capital desde 2007</span>
    <span><?= e(Cfg::get('matricula')) ?></span>
  </div>
</footer>

<?php if (empty($sinFlotante)): ?>
  <a class="whatsapp-flotante" href="<?= e($whatsappGeneral) ?>" target="_blank" rel="noopener" aria-label="Escribinos por WhatsApp"
     data-evento="consulta_whatsapp" data-origen="flotante"><?= icono('whatsapp') ?></a>
<?php endif ?>

<script src="<?= e(asset('assets/js/cargadores.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
