<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

/** @var ?array $mapa */
$numeroWhatsApp = preg_replace('/\D+/', '', Cfg::get('whatsapp_numero'));
$telefono = Cfg::get('telefono_fijo');
$email = Cfg::get('email');
$direccion = Cfg::get('direccion');
$instagram = Cfg::get('instagram_url');
$fotoLocal = Cfg::get('foto_local');
$comoLlegar = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($direccion . ', Argentina');
// Lo que la persona elige en "¿Qué necesitás?" y cómo se lee en el mensaje de WhatsApp.
$motivos = [
    'alquilar'   => ['Alquilar', 'alquilar una propiedad'],
    'comprar'    => ['Comprar', 'comprar una propiedad'],
    'vender'     => ['Vender o tasar', 'vender o tasar mi propiedad'],
    'administrar' => ['Que administren mi alquiler', 'que administren el alquiler de mi propiedad'],
    'otra'       => ['Otra consulta', 'hacer una consulta'],
];
?>
<section class="encabezado-pagina encabezado-pagina--contacto">
  <div class="contenedor encabezado-contacto">
    <div>
      <p class="encabezado-pagina__antetitulo">Santa Fe Propiedades · Santa Fe Capital</p>
      <h1>Contacto</h1>
      <p>Respondemos por WhatsApp en el horario de atención. También podés llamarnos o pasar por la oficina.</p>
    </div>
    <?php /* Los cuadros del logo, en grande: dos rojos arriba a la izquierda, tres celestes y tres azules. */ ?>
    <svg class="encabezado-contacto__cuadros" viewBox="0 0 52 52" aria-hidden="true">
      <rect class="rojo" x="0" y="0" width="15" height="15"/><rect class="rojo" x="18.5" y="0" width="15" height="15"/><rect class="celeste" x="0" y="18.5" width="15" height="15"/><rect class="celeste" x="18.5" y="18.5" width="15" height="15"/><rect class="celeste" x="37" y="18.5" width="15" height="15"/><rect class="azul" x="0" y="37" width="15" height="15"/><rect class="azul" x="18.5" y="37" width="15" height="15"/><rect class="azul" x="37" y="37" width="15" height="15"/>
    </svg>
  </div>
</section>

<section class="seccion">
  <div class="contenedor contacto-pagina">
    <div class="canales">
      <h2 class="seccion__titulo">Hablemos</h2>

      <a class="canal canal--whatsapp" href="<?= e(EnlaceWhatsApp::general($numeroWhatsApp, 'Hola, les escribo desde la web de Santa Fe Propiedades.')) ?>"
         target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="contacto">
        <span class="canal__icono"><?= icono('whatsapp') ?></span>
        <span class="canal__texto">
          <span class="canal__rotulo">WhatsApp · la forma más rápida</span>
          <strong><?= e(Cfg::get('whatsapp_visible')) ?></strong>
        </span>
        <span class="canal__accion">Escribir <?= icono('flecha') ?></span>
      </a>

      <?php if ($telefono !== ''): ?>
        <a class="canal canal--telefono" href="tel:+54<?= e(preg_replace('/\D+/', '', ltrim($telefono, '0'))) ?>" data-evento="click_telefono" data-origen="contacto">
          <span class="canal__icono"><?= icono('telefono') ?></span>
          <span class="canal__texto">
            <span class="canal__rotulo">Teléfono de la oficina</span>
            <strong><?= e($telefono) ?></strong>
          </span>
          <span class="canal__accion">Llamar <?= icono('flecha') ?></span>
        </a>
      <?php endif ?>

      <?php if ($email !== ''): ?>
        <a class="canal canal--email" href="mailto:<?= e($email) ?>">
          <span class="canal__icono"><?= icono('mail') ?></span>
          <span class="canal__texto">
            <span class="canal__rotulo">Email</span>
            <strong><?= e($email) ?></strong>
          </span>
          <span class="canal__accion">Escribir <?= icono('flecha') ?></span>
        </a>
      <?php endif ?>

      <?php if ($instagram !== ''): ?>
        <a class="canal canal--instagram" href="<?= e($instagram) ?>" target="_blank" rel="noopener">
          <span class="canal__icono"><?= icono('instagram') ?></span>
          <span class="canal__texto">
            <span class="canal__rotulo">Instagram · propiedades nuevas todas las semanas</span>
            <strong>@santafepropiedadesinmobiliaria</strong>
          </span>
          <span class="canal__accion">Ver <?= icono('flecha') ?></span>
        </a>
      <?php endif ?>

      <p class="canales__horario"><?= icono('reloj') ?><span><strong>Horario de atención:</strong> <?= e(Cfg::get('horario')) ?></span></p>
      <?php if (modulo('faq')): ?>
        <p class="canales__horario"><?= icono('check') ?><span>Requisitos, visitas y tasaciones: <a href="/preguntas-frecuentes">mirá las preguntas frecuentes</a>.</span></p>
      <?php endif ?>
    </div>

    <?php
    /* Sin JavaScript el formulario igual funciona: abre WhatsApp con el mensaje escrito (campo "text").
       Con JavaScript, app.js arma un mensaje con el nombre, el motivo y el código. Nada se guarda en la web. */
    ?>
    <form class="consulta" action="https://wa.me/<?= e($numeroWhatsApp) ?>" method="get" target="_blank" data-formulario-whatsapp="<?= e($numeroWhatsApp) ?>">
      <h2 class="consulta__titulo">Armá tu consulta</h2>
      <p class="consulta__bajada">Completá lo que quieras y se abre WhatsApp con el mensaje listo para enviar.</p>

      <label class="campo" for="consulta-nombre">
        <span class="campo__etiqueta">Tu nombre</span>
        <input id="consulta-nombre" type="text" autocomplete="name" maxlength="80" placeholder="Ej: Laura" data-consulta="nombre">
      </label>

      <fieldset class="consulta__motivos">
        <legend class="campo__etiqueta">¿Qué necesitás?</legend>
        <?php foreach ($motivos as $clave => [$etiqueta, $frase]): ?>
          <label class="opcion-chip">
            <input type="radio" name="motivo" value="<?= e($clave) ?>" data-frase="<?= e($frase) ?>" data-consulta="motivo">
            <span><?= e($etiqueta) ?></span>
          </label>
        <?php endforeach ?>
      </fieldset>

      <label class="campo" for="consulta-codigo">
        <span class="campo__etiqueta">Código de la propiedad <span class="campo__opcional">(si tenés uno)</span></span>
        <input id="consulta-codigo" type="text" inputmode="numeric" maxlength="6" placeholder="Ej: 258" data-consulta="codigo">
      </label>

      <label class="campo" for="consulta-mensaje">
        <span class="campo__etiqueta">Mensaje</span>
        <textarea id="consulta-mensaje" name="text" rows="4" maxlength="800" placeholder="Contanos qué buscás: zona, cantidad de dormitorios, presupuesto…" data-consulta="mensaje"></textarea>
      </label>

      <div class="consulta__vista" hidden data-consulta-vista>
        <span class="campo__etiqueta">Así va a llegar</span>
        <p data-consulta-texto></p>
      </div>

      <button class="boton boton--whatsapp boton--ancho" type="submit" data-evento="consulta_whatsapp" data-origen="contacto-formulario"><?= icono('whatsapp') ?>Enviar por WhatsApp</button>
      <p class="consulta__nota">No guardamos tus datos: el mensaje sale desde tu WhatsApp.</p>
    </form>
  </div>
</section>

<section class="seccion seccion--alterna">
  <div class="contenedor oficina">
    <div class="oficina__datos">
      <h2 class="seccion__titulo">Visitanos</h2>
      <ul class="oficina__lista">
        <li><?= icono('pin') ?><span><strong><?= e($direccion) ?></strong><br>Santa Fe Capital</span></li>
        <li><?= icono('reloj') ?><span><?= e(Cfg::get('horario')) ?></span></li>
        <?php if (Cfg::get('matricula') !== ''): ?>
          <li><?= icono('llave') ?><span>Corredores matriculados · <?= e(Cfg::get('matricula')) ?></span></li>
        <?php endif ?>
      </ul>
      <a class="boton boton--primario" href="<?= e($comoLlegar) ?>" target="_blank" rel="noopener"><?= icono('pin') ?>Cómo llegar</a>
      <?php if ($fotoLocal !== ''): ?>
        <div class="oficina__foto"><img src="<?= e(asset($fotoLocal)) ?>" alt="Oficina de Santa Fe Propiedades en <?= e($direccion) ?>" loading="lazy"></div>
      <?php endif ?>
    </div>
    <?php if ($mapa): ?>
      <div class="oficina__mapa">
        <div class="mapa__lienzo" id="mapa" role="region" aria-label="Mapa con la ubicación de la oficina"></div>
        <script type="application/json" id="datos-mapa"><?= json_encode($mapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
      </div>
    <?php endif ?>
  </div>
</section>

<?= Vista::parcial('captacion') ?>
