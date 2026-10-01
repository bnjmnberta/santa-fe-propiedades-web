<?php
use App\Core\Csrf;
use App\Modelos\EstadoPropiedad;
use App\Modelos\Precio;
?>
<div class="panel-encabezado">
  <h1>Propiedades</h1>
  <a class="boton boton--primario" href="/panel/propiedades/nueva">+ Nueva propiedad</a>
</div>

<ul class="panel-contadores">
  <li><strong><?= (int) $contadores['publicadas'] ?></strong> publicadas</li>
  <li><strong><?= (int) $contadores['reservadas'] ?></strong> reservadas</li>
  <li><strong><?= (int) $contadores['cerradas_mes'] ?></strong> alquiladas o vendidas este mes</li>
  <li><strong><?= (int) $contadores['pausadas'] ?></strong> pausadas</li>
</ul>

<form class="panel-busqueda" action="/panel" method="get">
  <input type="search" name="q" value="<?= e($texto) ?>" placeholder="Buscar por código, título o dirección" aria-label="Buscar">
  <select name="estado" aria-label="Estado">
    <option value="">Todos los estados</option>
    <?php foreach (EstadoPropiedad::cases() as $opcion): ?>
      <option value="<?= e($opcion->value) ?>" <?= $estado === $opcion->value ? 'selected' : '' ?>><?= e($opcion->etiqueta()) ?></option>
    <?php endforeach ?>
  </select>
  <button class="boton boton--secundario boton--chico" type="submit">Buscar</button>
</form>

<?php if (!$propiedades): ?>
  <p class="panel-vacio">No hay propiedades con esos filtros.</p>
<?php endif ?>

<ul class="panel-lista">
  <?php foreach ($propiedades as $p): ?>
    <li class="panel-item">
      <a class="panel-item__foto" href="/panel/propiedades/<?= (int) $p['id_propiedad'] ?>">
        <?php if ($p['portada']): ?>
          <img src="/uploads/propiedades/<?= (int) $p['codigo'] ?>/<?= e($p['portada']) ?>-chica.webp" alt="" loading="lazy">
        <?php else: ?>
          <span class="panel-item__sin-foto">Sin fotos</span>
        <?php endif ?>
      </a>
      <div class="panel-item__datos">
        <p class="panel-item__etiquetas">
          <span class="panel-chip panel-chip--<?= e($p['operacion']) ?>"><?= $p['operacion'] === 'venta' ? 'Venta' : 'Alquiler' ?></span>
          <span class="panel-estado panel-estado--<?= e($p['estado']) ?>"><?= e(EstadoPropiedad::from($p['estado'])->etiqueta()) ?></span>
          <span class="panel-item__codigo">#<?= (int) $p['codigo'] ?></span>
        </p>
        <a class="panel-item__titulo" href="/panel/propiedades/<?= (int) $p['id_propiedad'] ?>"><?= e($p['titulo']) ?></a>
        <p class="panel-item__detalle">
          <?= e(Precio::crear($p['moneda'], $p['precio'])->formatear($p['operacion'] === 'alquiler')) ?>
          · <?= e($p['tipo']) ?><?= $p['zona'] ? ' · ' . e($p['zona']) : '' ?>
          · <?= (int) $p['cantidad_fotos'] ?> <?= (int) $p['cantidad_fotos'] === 1 ? 'foto' : 'fotos' ?>
        </p>
      </div>
      <div class="panel-item__acciones">
        <a class="boton boton--chico boton--primario" href="/panel/propiedades/<?= (int) $p['id_propiedad'] ?>">Editar</a>
        <a class="boton boton--chico panel-boton-suave" href="/panel/propiedades/<?= (int) $p['id_propiedad'] ?>/estado">Cambiar estado</a>
        <form action="/panel/propiedades/<?= (int) $p['id_propiedad'] ?>/destacar" method="post">
          <?= Csrf::campo() ?>
          <button class="panel-estrella <?= $p['destacada'] ? 'panel-estrella--activa' : '' ?>" type="submit"
                  title="<?= $p['destacada'] ? 'Quitar de destacadas' : 'Destacar en la portada' ?>"
                  aria-label="<?= $p['destacada'] ? 'Quitar de destacadas' : 'Destacar en la portada' ?>"><?= icono('estrella') ?></button>
        </form>
      </div>
    </li>
  <?php endforeach ?>
</ul>
