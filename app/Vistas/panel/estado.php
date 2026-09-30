<?php
use App\Core\Csrf;
use App\Modelos\EstadoPropiedad;

/** @var array $propiedad */
/** @var EstadoPropiedad $actual */
/** @var list<EstadoPropiedad> $permitidos */
/** @var EstadoPropiedad|null $nuevo */
$id = (int) $propiedad['id_propiedad'];
$efecto = fn (EstadoPropiedad $estado): string => match ($estado) {
    EstadoPropiedad::Disponible => 'Se muestra en la web y se puede consultar.',
    EstadoPropiedad::Reservado  => 'Sigue en la web con la etiqueta «Reservado».',
    EstadoPropiedad::Alquilado  => 'Sale del catálogo. Si alguien entra por un enlace viejo, ve que ya se alquiló.',
    EstadoPropiedad::Vendido    => 'Sale del catálogo. Si alguien entra por un enlace viejo, ve que ya se vendió. Solo un administrador puede deshacerlo.',
    EstadoPropiedad::Pausado    => 'Se oculta de la web hasta que la vuelvas a poner disponible.',
};
?>
<div class="panel-encabezado">
  <div>
    <a class="panel-volver" href="/panel">← Propiedades</a>
    <h1>Cambiar estado</h1>
    <p class="panel-subtitulo">#<?= (int) $propiedad['codigo'] ?> · <?= e($propiedad['titulo']) ?></p>
  </div>
</div>

<?php if ($nuevo === null): ?>
  <section class="panel-bloque">
    <p class="panel-paso">Paso 1 de 2 · Elegí el estado nuevo</p>
    <p>Estado actual: <span class="panel-estado panel-estado--<?= e($actual->value) ?>"><?= e($actual->etiqueta()) ?></span></p>
    <?php if ($permitidos): ?>
      <div class="panel-estados">
        <?php foreach ($permitidos as $estado): ?>
          <a class="panel-estado-opcion panel-estado-opcion--<?= e($estado->value) ?>" href="/panel/propiedades/<?= $id ?>/estado?nuevo=<?= e($estado->value) ?>">
            <strong><?= e($estado->etiqueta()) ?></strong>
            <span><?= e($efecto($estado)) ?></span>
          </a>
        <?php endforeach ?>
      </div>
    <?php else: ?>
      <p class="panel-ayuda">Una propiedad vendida es estado final. Si fue un error, pedile a un administrador que la vuelva a poner disponible.</p>
    <?php endif ?>
  </section>
<?php else: ?>
  <section class="panel-bloque panel-confirmar">
    <p class="panel-paso">Paso 2 de 2 · Confirmá el cambio</p>
    <p class="panel-confirmar__pregunta">
      ¿Pasar de <span class="panel-estado panel-estado--<?= e($actual->value) ?>"><?= e($actual->etiqueta()) ?></span>
      a <span class="panel-estado panel-estado--<?= e($nuevo->value) ?>"><?= e($nuevo->etiqueta()) ?></span>?
    </p>
    <p><?= e($efecto($nuevo)) ?></p>
    <form action="/panel/propiedades/<?= $id ?>/estado" method="post">
      <?= Csrf::campo() ?>
      <input type="hidden" name="nuevo" value="<?= e($nuevo->value) ?>">
      <label class="panel-campo panel-campo--completo">
        <span>Nota (opcional)</span>
        <input type="text" name="nota" maxlength="200" placeholder="Ej: Se alquiló a través de Instagram">
      </label>
      <div class="panel-guardar">
        <button class="boton boton--primario" type="submit">Sí, confirmar</button>
        <a class="boton panel-boton-suave" href="/panel/propiedades/<?= $id ?>/estado">Cancelar</a>
      </div>
    </form>
  </section>
<?php endif ?>

<?php if ($historial): ?>
  <section class="panel-bloque">
    <h2>Historial</h2>
    <ul class="panel-historial">
      <?php foreach ($historial as $cambio): ?>
        <li>
          <time><?= e(date('d/m/Y H:i', strtotime($cambio['fecha']))) ?></time>
          <?= e(EstadoPropiedad::from($cambio['estado_anterior'])->etiqueta()) ?> → <strong><?= e(EstadoPropiedad::from($cambio['estado_nuevo'])->etiqueta()) ?></strong>
          <?= $cambio['usuario'] ? '· ' . e($cambio['usuario']) : '' ?>
          <?= $cambio['nota'] ? '· «' . e($cambio['nota']) . '»' : '' ?>
        </li>
      <?php endforeach ?>
    </ul>
  </section>
<?php endif ?>
