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
<?= Vista::parcial('encabezado', ['titulo' => $pagina['titulo'], 'bajada' => $pagina['bajada'], 'tono' => $esAlquiler ? 'alquiler' : 'venta', 'migas' => [$esAlquiler ? 'Alquilá con nosotros' : 'Vendé con nosotros']]) ?>

<section class="seccion">
  <div class="contenedor">
    <ul class="beneficios">
      <?php foreach ($pagina['beneficios'] as $i => [$icono, $titulo, $texto]): ?>
        <li class="beneficio">
          <span class="servicio__icono servicio__icono--<?= ['azul', 'celeste', 'rojo'][$i % 3] ?>"><?= icono($icono) ?></span>
          <h2 class="beneficio__titulo"><?= e($titulo) ?></h2>
          <p><?= e($texto) ?></p>
        </li>
      <?php endforeach ?>
    </ul>
    <p class="centrado">
      <a class="boton boton--whatsapp" href="<?= e(EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), $pagina['mensaje'])) ?>"
         target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="captacion_<?= $esAlquiler ? 'alquiler' : 'venta' ?>"><?= icono('whatsapp') ?><?= e($pagina['boton']) ?></a>
    </p>
  </div>
</section>

<?php if ($motivos): ?>
  <?= Vista::parcial('motivos', ['motivos' => $motivos]) ?>
<?php endif ?>

<?= Vista::parcial('captacion', ['sin' => $esAlquiler ? 'alquila' : 'vende']) ?>
