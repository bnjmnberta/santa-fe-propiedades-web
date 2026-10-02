<?php
use App\Core\Vista;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

/**
 * Contacto: una pantalla que responde cómo (WhatsApp), cuándo (estado en vivo) y dónde (dirección y mapa).
 * Escritorio: dos columnas. Celular: una sola, en el orden del HTML (cabeza, dirección, filas, mapa).
 *
 * @var ?array $mapa
 * @var ?App\Servicios\HorarioAtencion $horario
 */
$numeroWhatsApp = preg_replace('/\D+/', '', Cfg::get('whatsapp_numero'));
$telefono = Cfg::get('telefono_fijo');
$email = Cfg::get('email');
$direccion = Cfg::get('direccion');
$calle = trim(explode(',', $direccion)[0]);
$instagram = Cfg::get('instagram_url');
$fotoLocal = Cfg::get('foto_local');
$comoLlegar = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($direccion . ', Argentina');
$estado = $horario?->estado();
$bajadaAbierto = 'Te atendemos por WhatsApp ahora.';
$bajadaCerrado = 'Escribinos y te respondemos apenas abramos.';
// Lo que la persona elige en "¿Qué necesitás?" y cómo se lee en el mensaje de WhatsApp.
$motivos = [
    'alquilar'    => ['Alquilar', 'alquilar una propiedad'],
    'comprar'     => ['Comprar', 'comprar una propiedad'],
    'vender'      => ['Vender o tasar', 'vender o tasar mi propiedad'],
    'administrar' => ['Que administren mi alquiler', 'que administren el alquiler de mi propiedad'],
    'otra'        => ['Otra consulta', 'hacer una consulta'],
];
// Canales secundarios: [clase de color, ícono, rótulo, valor, enlace, atributos]
$canales = [];
if ($telefono !== '') {
    $canales[] = ['telefono', 'telefono', 'Teléfono de la oficina', e($telefono),
        'tel:+54' . preg_replace('/\D+/', '', ltrim($telefono, '0')), 'data-evento="click_telefono" data-origen="contacto"'];
}
if ($email !== '') {
    $canales[] = ['email', 'mail', 'Email', str_replace('@', '@<wbr>', e($email)), 'mailto:' . $email, ''];
}
if ($instagram !== '') {
    $canales[] = ['instagram', 'instagram', 'Instagram · propiedades nuevas todas las semanas', '@santafepropiedades<wbr>inmobiliaria', $instagram, 'target="_blank" rel="noopener"'];
}
?>
<section class="contacto-pagina">
  <div class="contenedor contacto-pagina__grilla">

    <div class="contacto-pagina__cabeza">
      <?php if ($estado): ?>
        <p class="estado-oficina estado-oficina--<?= $estado['abierto'] ? 'abierto' : 'cerrado' ?>" data-horario="<?= e(json_encode($horario->datos(), JSON_UNESCAPED_UNICODE)) ?>">
          <i aria-hidden="true"></i><span data-estado-texto><?= e($estado['texto']) ?></span>
        </p>
      <?php endif ?>
      <h1 class="contacto-pagina__titulo">Contacto</h1>
      <p class="contacto-pagina__bajada" data-bajada-oficina data-abierto="<?= e($bajadaAbierto) ?>" data-cerrado="<?= e($bajadaCerrado) ?>"><?= e($estado ? ($estado['abierto'] ? $bajadaAbierto : $bajadaCerrado) : 'Escribinos por WhatsApp, llamanos o pasá por la oficina.') ?></p>

      <a class="contacto-pagina__wa" href="<?= e(EnlaceWhatsApp::general($numeroWhatsApp, 'Hola, les escribo desde la web de Santa Fe Propiedades.')) ?>"
         target="_blank" rel="noopener" data-evento="consulta_whatsapp" data-origen="contacto">
        <span class="contacto-pagina__wa-texto"><?= icono('whatsapp') ?>WhatsApp · <?= e(Cfg::get('whatsapp_visible')) ?></span>
        <?= icono('flecha') ?>
      </a>

      <?php
      /* Sin JavaScript el formulario igual funciona: abre WhatsApp con el mensaje escrito (campo "text").
         Con JavaScript, app.js arma un mensaje con el nombre, el motivo y el código. Nada se guarda en la web. */
      ?>
      <details class="consulta">
        <summary><?= icono('siguiente') ?>Armar mi consulta <span>(opcional)</span></summary>
        <form class="consulta__cuerpo" action="https://wa.me/<?= e($numeroWhatsApp) ?>" method="get" target="_blank" data-formulario-whatsapp="<?= e($numeroWhatsApp) ?>">
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
            <textarea id="consulta-mensaje" name="text" rows="3" maxlength="800" placeholder="Contanos qué buscás: zona, cantidad de dormitorios, presupuesto…" data-consulta="mensaje"></textarea>
          </label>

          <div class="consulta__vista" hidden data-consulta-vista>
            <span class="campo__etiqueta">Así va a llegar</span>
            <p data-consulta-texto></p>
          </div>

          <button class="boton boton--whatsapp" type="submit" data-evento="consulta_whatsapp" data-origen="contacto-formulario"><?= icono('whatsapp') ?>Enviar por WhatsApp</button>
          <p class="consulta__nota">No guardamos tus datos: el mensaje sale desde tu WhatsApp. Se abre en una pestaña nueva.</p>
        </form>
      </details>
    </div>

    <div class="contacto-pagina__direccion">
      <h2><?= e($calle) ?></h2>
      <p class="contacto-pagina__ciudad">Santa Fe Capital</p>
      <p class="contacto-pagina__dato"><?= icono('reloj') ?><span><?= e(Cfg::get('horario')) ?></span></p>
      <?php if (Cfg::get('matricula') !== ''): ?>
        <p class="contacto-pagina__dato"><?= icono('llave') ?><span>Corredores matriculados · <?= e(Cfg::get('matricula')) ?></span></p>
      <?php endif ?>
      <a class="boton boton--primario" href="<?= e($comoLlegar) ?>" target="_blank" rel="noopener"><?= icono('pin') ?>Cómo llegar</a>
      <?php if ($fotoLocal !== ''): ?>
        <div class="contacto-pagina__foto"><img src="<?= e(asset($fotoLocal)) ?>" alt="Oficina de Santa Fe Propiedades en <?= e($direccion) ?>" loading="lazy"></div>
      <?php endif ?>
    </div>

    <?php if ($canales): ?>
      <ul class="contacto-pagina__filas">
        <?php foreach ($canales as [$clase, $icono, $rotulo, $valor, $enlace, $atributos]): ?>
          <li>
            <a class="fila-contacto" href="<?= e($enlace) ?>" <?= $atributos ?>>
              <span class="fila-contacto__cuadro fila-contacto__cuadro--<?= e($clase) ?>"><?= icono($icono) ?></span>
              <span class="fila-contacto__texto"><span class="fila-contacto__rotulo"><?= e($rotulo) ?></span><strong class="fila-contacto__valor"><?= $valor ?></strong></span>
              <?= icono('flecha') ?>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>

    <?php if ($mapa): ?>
      <?php /* El mapa se carga recién cuando se ve (mapa.js). En celular queda detrás de un botón. */ ?>
      <div class="contacto-pagina__mapa" data-mapa-contacto>
        <button type="button" class="contacto-pagina__mapa-boton" data-mapa-boton aria-expanded="false" aria-controls="mapa"><?= icono('pin') ?><span>Ver mapa</span></button>
        <div class="mapa__lienzo" id="mapa" role="region" aria-label="Mapa con la ubicación de la oficina"></div>
        <script type="application/json" id="datos-mapa"><?= json_encode($mapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
      </div>
    <?php endif ?>

  </div>
</section>

<?= Vista::parcial('captacion') ?>
