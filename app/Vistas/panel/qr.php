<?php
/** @var array $fila  propiedad (fila de la base) */
/** @var string $url  dirección que codifica el QR */
/** @var string $svg  el QR en SVG */
/** @var bool $enLocal  la dirección apunta a esta PC (localhost) */
/** @var bool $publica  la propiedad se ve hoy en la web */
$id = (int) $fila['id_propiedad'];
?>
<div class="panel-encabezado no-imprimir">
  <div>
    <a class="panel-volver" href="/panel/propiedades/<?= $id ?>">← Volver a la propiedad</a>
    <h1>QR para el cartel</h1>
  </div>
  <div class="panel-encabezado__acciones">
    <button class="boton boton--chico boton--primario" type="button" data-imprimir>Imprimir</button>
    <a class="boton boton--chico panel-boton-suave" href="/panel/propiedades/<?= $id ?>/qr.svg" download>Descargar SVG</a>
  </div>
</div>

<?php if ($enLocal): ?>
  <p class="panel-aviso panel-aviso--error no-imprimir" role="alert">Este QR apunta a <strong><?= e((string) parse_url($url, PHP_URL_HOST)) ?></strong>, que es solo esta computadora. Para el cartel, generalo desde el panel de la web publicada.</p>
<?php endif ?>
<?php if (!$publica): ?>
  <p class="panel-aviso panel-aviso--error no-imprimir" role="status">Esta propiedad hoy no se ve en la web, así que quien escanee el QR no va a encontrar la ficha.</p>
<?php endif ?>

<article class="panel-qr">
  <div class="panel-qr__codigo"><?= $svg ?></div>
  <p class="panel-qr__leyenda">Escaneá para ver la ficha completa</p>
  <p class="panel-qr__numero">Código <?= (int) $fila['codigo'] ?></p>
  <p class="panel-qr__titulo"><?= e($fila['titulo']) ?></p>
  <p class="panel-qr__url"><?= e($url) ?></p>
</article>

<p class="panel-qr__ayuda no-imprimir">El QR abre directo esta propiedad y suma las marcas <code>utm_source=cartel</code> y <code>utm_medium=qr</code>, para ver en Analytics cuántas personas entran desde el cartel. Si más adelante cambia el título, el QR sigue funcionando.</p>
