<?php
use App\Core\Vista;
use App\Modelos\EstadoPropiedad;
use App\Repositorios\ConfiguracionRepositorio as Cfg;
use App\Servicios\EnlaceWhatsApp;

/** @var App\Modelos\Propiedad $propiedad */
/** @var list<App\Modelos\Propiedad> $similares */
$p = $propiedad;
$disponible = $p->estado->esPublico();
$seccion = $p->esComercial ? ['/comerciales', 'Comerciales'] : ($p->operacion->value === 'venta' ? ['/ventas', 'Ventas'] : ['/alquileres', 'Alquileres']);

// Datos técnicos: [etiqueta, valor], solo los que tienen valor.
$m2 = fn ($valor) => $valor !== null && (float) $valor > 0 ? numero((float) $valor) . ' m²' : null;
$datos = array_filter([
    ['Tipo', $p->tipo],
    ['Dormitorios', $p->dato('dormitorios') !== null ? ((int) $p->dato('dormitorios') === 0 ? 'Monoambiente' : $p->dato('dormitorios')) : null],
    ['Baños', $p->dato('banos')],
    ['Cocheras', (int) $p->dato('cocheras') > 0 ? $p->dato('cocheras') : null],
    ['Sup. cubierta', $m2($p->dato('sup_cubierta'))],
    ['Terreno', $m2($p->dato('sup_terreno'))],
    ['Medidas del lote', $p->dato('frente_m') && $p->dato('fondo_m') ? numero((float) $p->dato('frente_m'), 1) . ' × ' . numero((float) $p->dato('fondo_m'), 1) . ' m' : null],
    ['Ubicación en el edificio', $p->dato('ubicacion_unidad') ? ucfirst($p->dato('ubicacion_unidad')) : null],
    ['Planta', $p->dato('planta')],
    ['Régimen', $p->dato('regimen')],
    ['Amoblado', $p->dato('amoblado') ? 'Sí' : null],
], fn (array $dato) => $dato[1] !== null && $dato[1] !== '');
$expensas = $p->dato('expensas') ? '$ ' . numero((float) $p->dato('expensas')) . ' de expensas' : $p->dato('expensas_detalle');
$disponibleDesde = $p->dato('disponible_desde') ? date('d/m/Y', strtotime($p->dato('disponible_desde'))) : null;
$enlaceMapa = $p->dato('latitud') && $p->dato('longitud')
    ? 'https://www.google.com/maps/search/?api=1&query=' . $p->dato('latitud') . ',' . $p->dato('longitud')
    : ($p->direccionVisible() ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($p->direccionVisible() . ', Santa Fe, Argentina') : null);
// El marco de la galería toma la proporción de las fotos: sin franjas a los costados.
$primeraFoto = $p->fotos[0] ?? ['ancho' => 4, 'alto' => 3];
$anchoFoto = (int) ($primeraFoto['ancho'] ?: 4);
$altoFoto = (int) ($primeraFoto['alto'] ?: 3);
$whatsappSimilares = EnlaceWhatsApp::general(Cfg::get('whatsapp_numero'), sprintf('Hola, vi que la propiedad #%d ya no está disponible. ¿Tienen algo parecido?', $p->codigo));
?>
<div class="contenedor ficha">
  <nav class="migas" aria-label="Ubicación en el sitio">
    <a href="/">Inicio</a><span aria-hidden="true">/</span><a href="<?= e($seccion[0]) ?>"><?= e($seccion[1]) ?></a><span aria-hidden="true">/</span><span>Código <?= e($p->codigo) ?></span>
  </nav>

  <?php if (!$disponible): ?>
    <div class="aviso aviso--fuerte">
      <strong>Esta propiedad ya está <?= e(mb_strtolower($p->estado->etiqueta())) ?>.</strong>
      Mirá las similares más abajo o escribinos y te avisamos cuando entre algo parecido.
    </div>
  <?php endif ?>

  <div class="galeria<?= $altoFoto > $anchoFoto ? ' galeria--vertical' : '' ?>" style="--ancho: <?= $anchoFoto ?>; --alto: <?= $altoFoto ?>" data-galeria>
    <?php if ($p->fotos): ?>
      <div class="galeria__pista" tabindex="0" aria-label="Fotos de la propiedad">
        <?php foreach ($p->fotos as $indice => $foto): ?>
          <img src="<?= e($p->urlFoto($foto['archivo'])) ?>" alt="<?= e($p->titulo) ?>, foto <?= $indice + 1 ?>"
               width="<?= e($foto['ancho'] ?? 1600) ?>" height="<?= e($foto['alto'] ?? 1200) ?>"
               <?= $indice === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
        <?php endforeach ?>
      </div>
      <?php if (count($p->fotos) > 1): ?>
        <button class="galeria__boton galeria__boton--anterior" type="button" aria-label="Foto anterior" data-galeria-anterior><?= icono('anterior') ?></button>
        <button class="galeria__boton galeria__boton--siguiente" type="button" aria-label="Foto siguiente" data-galeria-siguiente><?= icono('siguiente') ?></button>
        <span class="galeria__contador" data-galeria-contador>1 / <?= count($p->fotos) ?></span>
      <?php endif ?>
    <?php else: ?>
      <div class="sin-foto sin-foto--grande"><?= icono('casa', 'sin-foto__icono') ?><span>Fotos próximamente</span></div>
    <?php endif ?>
  </div>

  <div class="ficha__cuerpo">
    <div class="ficha__principal">
      <div class="ficha__etiquetas">
        <span class="etiqueta etiqueta--<?= e($p->operacion->value) ?>"><?= e($p->operacion->etiqueta()) ?></span>
        <?php if ($p->estado === EstadoPropiedad::Reservado): ?><span class="etiqueta etiqueta--estado">Reservado</span><?php endif ?>
        <span class="ficha__codigo">Código <?= e($p->codigo) ?></span>
      </div>
      <h1 class="ficha__titulo"><?= e($p->titulo) ?></h1>
      <?php if ($p->ubicacion()): ?>
        <p class="ficha__ubicacion"><?= icono('pin') ?><?= e($p->ubicacion()) ?></p>
      <?php endif ?>

      <?php if ($p->rasgos()): ?>
        <ul class="rasgos rasgos--grandes">
          <?php foreach ($p->rasgos() as [$icono, $texto]): ?><li><?= icono($icono) ?><?= e($texto) ?></li><?php endforeach ?>
        </ul>
      <?php endif ?>

      <?php if ($p->dato('descripcion')): ?>
        <h2 class="ficha__subtitulo">Descripción</h2>
        <div class="ficha__texto"><?= nl2br(e($p->dato('descripcion'))) ?></div>
      <?php endif ?>

      <?php if ($p->caracteristicas()): ?>
        <h2 class="ficha__subtitulo">Características</h2>
        <ul class="caracteristicas">
          <?php foreach ($p->caracteristicas() as $caracteristica): ?><li><?= icono('check') ?><?= e($caracteristica) ?></li><?php endforeach ?>
        </ul>
      <?php endif ?>

      <?php if ($datos): ?>
        <h2 class="ficha__subtitulo">Datos técnicos</h2>
        <dl class="datos">
          <?php foreach ($datos as [$etiqueta, $valor]): ?>
            <div><dt><?= e($etiqueta) ?></dt><dd><?= e($valor) ?></dd></div>
          <?php endforeach ?>
        </dl>
      <?php endif ?>

      <?php if ($p->dato('servicios')): ?>
        <h2 class="ficha__subtitulo">Servicios</h2>
        <p class="ficha__texto"><?= e($p->dato('servicios')) ?></p>
      <?php endif ?>

      <?php if ($p->dato('referencias') || $enlaceMapa || $cercanos): ?>
        <h2 class="ficha__subtitulo">Ubicación</h2>
        <?php if ($p->dato('referencias')): ?><p class="ficha__texto"><?= e($p->dato('referencias')) ?></p><?php endif ?>
        <?php if ($cercanos): ?>
          <ul class="cercanos">
            <?php foreach ($cercanos as $cercano): ?>
              <li>
                <?= icono(['facultad' => 'birrete', 'transporte' => 'bus', 'puerto' => 'ancla', 'costanera' => 'ola'][$cercano['categoria']] ?? 'pin') ?>
                <span>A <strong><?= $cercano['metros'] < 1000 ? e(round($cercano['metros'] / 50) * 50) . ' m' : e(numero($cercano['metros'] / 1000, 1)) . ' km' ?></strong> de <?= e($cercano['nombre']) ?></span>
              </li>
            <?php endforeach ?>
          </ul>
          <p class="cercanos__nota">Distancias en línea recta, aproximadas.</p>
        <?php endif ?>
        <?php if (!empty($mapa)): ?><?= Vista::parcial('mapa', ['mapa' => $mapa]) ?><?php endif ?>
        <?php if ($enlaceMapa): ?><a class="enlace-flecha" href="<?= e($enlaceMapa) ?>" target="_blank" rel="noopener">Ver en Google Maps <?= icono('flecha') ?></a><?php endif ?>
      <?php endif ?>

      <?php if ($p->dato('instagram_url')): ?>
        <p><a class="enlace-flecha" href="<?= e($p->dato('instagram_url')) ?>" target="_blank" rel="noopener"><?= icono('instagram') ?>Ver la publicación en Instagram</a></p>
      <?php endif ?>
    </div>

    <aside class="ficha__lateral">
      <div class="precio-caja">
        <p class="precio-caja__precio"><?= e($p->precioTexto()) ?></p>
        <?php if ($expensas): ?><p class="precio-caja__dato"><?= e($expensas) ?></p><?php endif ?>
        <?php if ($p->dato('requisitos')): ?><p class="precio-caja__dato"><strong>Requisitos:</strong> <?= e($p->dato('requisitos')) ?></p><?php endif ?>
        <?php if ($disponibleDesde): ?><p class="precio-caja__dato"><strong>Disponible desde:</strong> <?= e($disponibleDesde) ?></p><?php endif ?>
        <?php if ($disponible): ?>
          <a class="boton boton--whatsapp boton--ancho" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"
             data-evento="consulta_whatsapp" data-origen="ficha" data-codigo="<?= e($p->codigo) ?>"
             data-operacion="<?= e($p->operacion->value) ?>" data-tipo="<?= e($p->tipo) ?>"><?= icono('whatsapp') ?>Consultar por WhatsApp</a>
          <p class="precio-caja__nota">Te llega un mensaje con el código <?= e($p->codigo) ?> para que te respondamos más rápido.</p>
        <?php else: ?>
          <a class="boton boton--whatsapp boton--ancho" href="<?= e($whatsappSimilares) ?>" target="_blank" rel="noopener"
             data-evento="consulta_whatsapp" data-origen="ficha_no_disponible" data-codigo="<?= e($p->codigo) ?>"><?= icono('whatsapp') ?>Pedir algo similar</a>
        <?php endif ?>
      </div>
    </aside>
  </div>

  <?php if ($similares): ?>
    <section class="seccion seccion--compacta">
      <h2 class="seccion__titulo">También te puede interesar</h2>
      <div class="grilla">
        <?php foreach ($similares as $similar): ?><?= Vista::parcial('tarjeta', ['propiedad' => $similar]) ?><?php endforeach ?>
      </div>
    </section>
  <?php endif ?>
</div>

<?php if ($disponible): ?>
  <div class="barra-movil">
    <span class="barra-movil__precio"><?= e($p->precioTexto()) ?></span>
    <a class="boton boton--whatsapp" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"
       data-evento="consulta_whatsapp" data-origen="barra_movil" data-codigo="<?= e($p->codigo) ?>"
       data-operacion="<?= e($p->operacion->value) ?>" data-tipo="<?= e($p->tipo) ?>"><?= icono('whatsapp') ?>Consultar</a>
  </div>
<?php endif ?>
