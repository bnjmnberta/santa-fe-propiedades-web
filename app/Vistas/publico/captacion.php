<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

// Textos en BORRADOR para aprobar con la inmobiliaria.
$pagina = $esAlquiler ? [
    'titulo'    => 'Alquilá tu propiedad con nosotros',
    'bajada'    => 'Nos ocupamos de todo para que cobres tu alquiler sin preocuparte.',
    'beneficios' => [
        ['megafono', 'Difusión en la web y en Instagram', 'Tu propiedad publicada con fotos y ficha completa, y compartida en nuestras redes.'],
        ['llave', 'Búsqueda de inquilinos', 'Mostramos la propiedad y evaluamos a los interesados según los requisitos que acordemos.'],
        ['tasacion', 'Administración del alquiler', 'Seguimos el contrato, los cobros y el estado de la propiedad durante todo el alquiler.'],
    ],
    'mensaje'   => 'Hola, tengo una propiedad para poner en alquiler y quiero saber cómo trabajan.',
    'boton'     => 'Quiero alquilar mi propiedad',
] : [
    'titulo'    => 'Vendé tu propiedad con nosotros',
    'bajada'    => 'Te acompañamos desde la tasación hasta la firma.',
    'beneficios' => [
        ['tasacion', 'Tasación profesional', 'Un valor realista según el mercado de Santa Fe, para vender en tiempo y a buen precio.'],
        ['megafono', 'Difusión', 'Ficha completa en la web, publicaciones en Instagram y cartelería.'],
        ['casa', 'Asesoramiento', 'Te acompañamos en visitas, negociación y documentación hasta concretar la operación.'],
    ],
    'mensaje'   => 'Hola, quiero vender mi propiedad y me gustaría pedir una tasación.',
    'boton'     => 'Pedí tu tasación',
];
?>
<section class="encabezado-pagina encabezado-pagina--<?= $esAlquiler ? 'alquiler' : 'venta' ?>">
  <div class="contenedor">
    <p class="encabezado-pagina__antetitulo">Propietarios</p>
    <h1><?= e($pagina['titulo']) ?></h1>
    <p><?= e($pagina['bajada']) ?></p>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="motivos">
      <?php foreach ($pagina['beneficios'] as [$icono, $titulo, $texto]): ?>
        <article class="motivo">
          <span class="motivo__icono"><?= icono($icono) ?></span>
          <h2 class="motivo__titulo"><?= e($titulo) ?></h2>
          <p><?= e($texto) ?></p>
        </article>
      <?php endforeach ?>
    </div>
    <p class="centrado">
      <a class="boton boton--whatsapp" href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), $pagina['mensaje'])) ?>"
         target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="captacion_<?= $esAlquiler ? 'alquiler' : 'venta' ?>"><?= icono('whatsapp') ?><?= e($pagina['boton']) ?></a>
    </p>
  </div>
</section>

<?php if ($motivos): ?>
  <section class="seccion seccion--alterna">
    <div class="contenedor">
      <h2 class="seccion__titulo seccion__titulo--centrado">¿Por qué elegirnos?</h2>
      <?= Vista::parcial('motivos', ['motivos' => $motivos]) ?>
    </div>
  </section>
<?php endif ?>

<?= Vista::parcial('captacion', ['sin' => $esAlquiler ? 'alquila' : 'vende']) ?>
