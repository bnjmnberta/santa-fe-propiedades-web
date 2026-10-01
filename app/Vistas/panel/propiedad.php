<?php
use App\Core\Csrf;
use App\Core\Sesion;
use App\Modelos\EstadoPropiedad;
use App\Repositorios\GestionPropiedadRepositorio;

/** @var array|null $fila  propiedad guardada (null = nueva) */
/** @var array $valores    valores del formulario (guardados o recién enviados) */
/** @var array $errores */
$v = fn (string $campo): string => (string) ($valores[$campo] ?? '');
$marcado = fn (string $campo): string => !empty($valores[$campo]) ? 'checked' : '';
$error = fn (string $campo): string => isset($errores[$campo]) ? '<span class="panel-error">' . e($errores[$campo]) . '</span>' : '';
// Números guardados como "52.00" se muestran como "52".
$n = fn (string $campo): string => is_numeric($v($campo)) && str_contains($v($campo), '.') ? rtrim(rtrim($v($campo), '0'), '.') : $v($campo);
// Montos con separador de miles ("650.000"), como los escribe la gente; el validador los entiende.
$monto = fn (string $campo): string => is_numeric($v($campo)) ? number_format((float) $v($campo), 0, ',', '.') : $v($campo);
$accion = $fila ? '/panel/propiedades/' . (int) $fila['id_propiedad'] : '/panel/propiedades';
?>
<div class="panel-encabezado">
  <div>
    <a class="panel-volver" href="/panel">← Propiedades</a>
    <h1><?= e($titulo) ?></h1>
  </div>
  <?php if ($fila): ?>
    <div class="panel-encabezado__acciones">
      <span class="panel-estado panel-estado--<?= e($fila['estado']) ?>"><?= e(EstadoPropiedad::from($fila['estado'])->etiqueta()) ?></span>
      <a class="boton boton--chico panel-boton-suave" href="/panel/propiedades/<?= (int) $fila['id_propiedad'] ?>/estado">Cambiar estado</a>
      <?php if ($propiedad): ?><a class="boton boton--chico panel-boton-suave" href="<?= e($propiedad->url()) ?>" target="_blank" rel="noopener">Ver en la web ↗</a><?php endif ?>
    </div>
  <?php endif ?>
</div>

<?php if ($errores): ?>
  <p class="panel-aviso panel-aviso--error" role="alert">Revisá los campos marcados en rojo.</p>
<?php endif ?>

<form class="panel-formulario panel-formulario--ancho" action="<?= e($accion) ?>" method="post">
  <?= Csrf::campo() ?>

  <fieldset class="panel-bloque">
    <legend>Lo principal</legend>
    <label class="panel-campo panel-campo--completo">
      <span>Título *</span>
      <input type="text" name="titulo" value="<?= e($v('titulo')) ?>" maxlength="150" placeholder="Ej: Dpto 1 dormitorio al frente con balcón" required>
      <?= $error('titulo') ?>
    </label>
    <div class="panel-campo">
      <span>Operación *</span>
      <div class="panel-opciones">
        <label class="panel-opcion panel-opcion--alquiler"><input type="radio" name="operacion" value="alquiler" <?= $v('operacion') === 'alquiler' ? 'checked' : '' ?>> Alquiler</label>
        <label class="panel-opcion panel-opcion--venta"><input type="radio" name="operacion" value="venta" <?= $v('operacion') === 'venta' ? 'checked' : '' ?>> Venta</label>
      </div>
      <?= $error('operacion') ?>
    </div>
    <label class="panel-campo">
      <span>Tipo *</span>
      <select name="id_tipo" required>
        <option value="">Elegí…</option>
        <?php foreach ($tipos as $tipo): ?>
          <option value="<?= (int) $tipo['id_tipo'] ?>" <?= (int) $v('id_tipo') === (int) $tipo['id_tipo'] ? 'selected' : '' ?>><?= e($tipo['nombre']) ?></option>
        <?php endforeach ?>
      </select>
      <?= $error('id_tipo') ?>
    </label>
    <label class="panel-campo">
      <span>Zona / barrio</span>
      <select name="id_zona">
        <option value="">Sin zona</option>
        <?php foreach ($zonas as $zona): ?>
          <option value="<?= (int) $zona['id_zona'] ?>" <?= (int) $v('id_zona') === (int) $zona['id_zona'] ? 'selected' : '' ?>><?= e($zona['nombre']) ?></option>
        <?php endforeach ?>
      </select>
    </label>
    <label class="panel-campo">
      <span>Dirección</span>
      <input type="text" name="direccion" value="<?= e($v('direccion')) ?>" maxlength="150" placeholder="Ej: Crespo 3200">
    </label>
    <label class="panel-check"><input type="checkbox" name="mostrar_direccion" value="1" <?= $marcado('mostrar_direccion') ?>> Mostrar la dirección en la web</label>
    <label class="panel-check"><input type="checkbox" name="destacada" value="1" <?= $marcado('destacada') ?>> <?= icono('estrella', 'icono panel-check__estrella') ?>Destacar en la portada</label>
  </fieldset>

  <fieldset class="panel-bloque">
    <legend>Precio y condiciones</legend>
    <div class="panel-campo">
      <span>Precio</span>
      <div class="panel-precio">
        <select name="moneda" aria-label="Moneda">
          <option value="ARS" <?= $v('moneda') !== 'USD' ? 'selected' : '' ?>>$</option>
          <option value="USD" <?= $v('moneda') === 'USD' ? 'selected' : '' ?>>U$S</option>
        </select>
        <input type="text" name="precio" value="<?= e($monto('precio')) ?>" inputmode="decimal" placeholder="Vacío = «Consultar precio»">
      </div>
      <?= $error('precio') . $error('moneda') ?>
    </div>
    <label class="panel-campo">
      <span>Expensas ($ por mes)</span>
      <input type="text" name="expensas" value="<?= e($monto('expensas')) ?>" inputmode="decimal">
      <?= $error('expensas') ?>
    </label>
    <label class="panel-campo">
      <span>Expensas en palabras</span>
      <input type="text" name="expensas_detalle" value="<?= e($v('expensas_detalle')) ?>" maxlength="60" placeholder="Ej: Sin expensas / Bajas expensas">
    </label>
    <label class="panel-campo">
      <span>Requisitos</span>
      <input type="text" name="requisitos" value="<?= e($v('requisitos')) ?>" maxlength="150" placeholder="Ej: 5 recibos de sueldo">
    </label>
    <label class="panel-campo">
      <span>Disponible desde</span>
      <input type="date" name="disponible_desde" value="<?= e($v('disponible_desde')) ?>">
      <?= $error('disponible_desde') ?>
    </label>
  </fieldset>

  <fieldset class="panel-bloque">
    <legend>Características</legend>
    <?php foreach (['dormitorios' => 'Dormitorios (0 = monoambiente)', 'banos' => 'Baños', 'cocheras' => 'Cocheras'] as $campo => $etiqueta): ?>
      <label class="panel-campo panel-campo--chico">
        <span><?= e($etiqueta) ?></span>
        <input type="number" name="<?= $campo ?>" value="<?= e($v($campo)) ?>" min="0" max="50" inputmode="numeric">
        <?= $error($campo) ?>
      </label>
    <?php endforeach ?>
    <?php foreach (['sup_cubierta' => 'Sup. cubierta (m²)', 'sup_terreno' => 'Terreno (m²)', 'frente_m' => 'Frente del lote (m)', 'fondo_m' => 'Fondo del lote (m)'] as $campo => $etiqueta): ?>
      <label class="panel-campo panel-campo--chico">
        <span><?= e($etiqueta) ?></span>
        <input type="text" name="<?= $campo ?>" value="<?= e($n($campo)) ?>" inputmode="decimal">
        <?= $error($campo) ?>
      </label>
    <?php endforeach ?>
    <label class="panel-campo panel-campo--chico">
      <span>Piso / planta</span>
      <input type="text" name="planta" value="<?= e($v('planta')) ?>" maxlength="30" placeholder="Ej: 2.º piso">
    </label>
    <label class="panel-campo panel-campo--chico">
      <span>Ubicación en el edificio</span>
      <select name="ubicacion_unidad">
        <option value="">—</option>
        <?php foreach (['frente' => 'Al frente', 'contrafrente' => 'Contrafrente', 'interno' => 'Interno', 'lateral' => 'Lateral'] as $valor => $etiqueta): ?>
          <option value="<?= $valor ?>" <?= $v('ubicacion_unidad') === $valor ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
        <?php endforeach ?>
      </select>
    </label>
    <label class="panel-campo panel-campo--chico">
      <span>Régimen</span>
      <input type="text" name="regimen" value="<?= e($v('regimen')) ?>" maxlength="60" placeholder="Ej: Propiedad horizontal">
    </label>
    <label class="panel-check"><input type="checkbox" name="amoblado" value="1" <?= $marcado('amoblado') ?>> Amoblado</label>
    <label class="panel-campo panel-campo--completo">
      <span>Características (una por renglón)</span>
      <textarea name="caracteristicas" rows="6" placeholder="Balcón al frente&#10;Cocina con barra&#10;Lavadero cubierto"><?= e($v('caracteristicas')) ?></textarea>
    </label>
    <label class="panel-campo panel-campo--completo">
      <span>Servicios</span>
      <input type="text" name="servicios" value="<?= e($v('servicios')) ?>" maxlength="300" placeholder="Ej: Gas natural · Cloaca · Asfalto · Agua corriente">
    </label>
  </fieldset>

  <fieldset class="panel-bloque">
    <legend>Descripción y ubicación</legend>
    <label class="panel-campo panel-campo--completo">
      <span>Descripción</span>
      <textarea name="descripcion" rows="6"><?= e($v('descripcion')) ?></textarea>
    </label>
    <label class="panel-campo panel-campo--completo">
      <span>Qué hay cerca</span>
      <input type="text" name="referencias" value="<?= e($v('referencias')) ?>" maxlength="250" placeholder="Ej: A metros del Parque Sur y de la Facultad de Ciencias Económicas">
    </label>
    <?php /* div y no label: un clic en el mapa no tiene que mandar el foco al campo de texto. */ ?>
    <div class="panel-campo panel-campo--completo">
      <label for="coordenadas"><span>Ubicación en el mapa</span></label>
      <input type="text" id="coordenadas" name="coordenadas" value="<?= e($v('coordenadas')) ?>" placeholder="Ej: -31.6396, -60.7132" aria-describedby="coordenadas-ayuda">
      <div class="panel-mapa" data-mapa-coordenadas="coordenadas" hidden></div>
      <small id="coordenadas-ayuda">Tocá el mapa o arrastrá el pin hasta la propiedad y las coordenadas se completan solas. También podés pegarlas o pegar el enlace de Google Maps.</small>
      <?= $error('coordenadas') ?>
    </div>
    <label class="panel-campo panel-campo--completo">
      <span>Publicación de Instagram</span>
      <input type="url" name="instagram_url" value="<?= e($v('instagram_url')) ?>" placeholder="https://www.instagram.com/p/...">
      <?= $error('instagram_url') ?>
    </label>
  </fieldset>

  <div class="panel-guardar">
    <button class="boton boton--primario" type="submit"><?= $fila ? 'Guardar cambios' : 'Crear propiedad' ?></button>
  </div>
</form>

<?php if ($fila): ?>
  <section class="panel-bloque" id="fotos">
    <h2>Fotos <small>(<?= count($fotos) ?> de <?= GestionPropiedadRepositorio::MAXIMO_FOTOS ?>)</small></h2>
    <form class="panel-subir" action="/panel/propiedades/<?= (int) $fila['id_propiedad'] ?>/fotos" method="post" enctype="multipart/form-data" data-subir-fotos>
      <?= Csrf::campo() ?>
      <label class="panel-subir__zona">
        <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" multiple>
        <strong>Elegir fotos</strong>
        <span data-subir-estado>JPG, PNG, WebP o HEIC de iPhone, hasta 8 MB cada una. Podés elegir varias juntas.</span>
      </label>
      <button class="boton boton--primario" type="submit">Subir</button>
    </form>

    <?php if ($fotos): ?>
      <p class="panel-ayuda">La primera foto es la portada de la tarjeta: usá una foto limpia, sin textos encima.</p>
      <ul class="panel-fotos">
        <?php foreach ($fotos as $indice => $foto): ?>
          <li class="panel-foto">
            <img src="/uploads/propiedades/<?= (int) $fila['codigo'] ?>/<?= e($foto['archivo']) ?>-chica.webp" alt="Foto <?= $indice + 1 ?>" loading="lazy">
            <?php if ($indice === 0): ?><span class="panel-foto__portada">Portada</span><?php endif ?>
            <div class="panel-foto__acciones">
              <?php foreach (['arriba' => '←', 'abajo' => '→', 'portada' => 'Portada'] as $movimiento => $texto): ?>
                <?php if (($movimiento !== 'arriba' || $indice > 0) && ($movimiento !== 'abajo' || $indice < count($fotos) - 1) && ($movimiento !== 'portada' || $indice > 0)): ?>
                  <form action="/panel/fotos/<?= (int) $foto['id_foto'] ?>/mover" method="post">
                    <?= Csrf::campo() ?><input type="hidden" name="movimiento" value="<?= $movimiento ?>">
                    <button type="submit" title="<?= $movimiento === 'portada' ? 'Usar como portada' : 'Mover' ?>"><?= $texto ?></button>
                  </form>
                <?php endif ?>
              <?php endforeach ?>
              <form action="/panel/fotos/<?= (int) $foto['id_foto'] ?>/borrar" method="post" data-confirmar="¿Borrar esta foto?">
                <?= Csrf::campo() ?><button type="submit" class="panel-foto__borrar" title="Borrar">Borrar</button>
              </form>
            </div>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </section>

  <?php if ($instagram): ?>
    <section class="panel-bloque" id="instagram">
      <h2>Texto para Instagram</h2>
      <p class="panel-ayuda">Se arma solo con los datos guardados, con el mismo formato de las publicaciones de la cuenta.</p>
      <textarea class="panel-instagram" rows="14" readonly data-texto-instagram><?= e($instagram) ?></textarea>
      <button class="boton boton--secundario" type="button" data-copiar-instagram>Copiar texto</button>
    </section>
  <?php endif ?>

  <?php if (Sesion::esAdministrador()): ?>
    <form class="panel-baja" action="/panel/propiedades/<?= (int) $fila['id_propiedad'] ?>/baja" method="post"
          data-confirmar="¿Dar de baja la propiedad #<?= (int) $fila['codigo'] ?>? Deja de aparecer en todos lados (queda guardada en la base).">
      <?= Csrf::campo() ?>
      <button class="panel-enlace-peligro" type="submit">Dar de baja esta propiedad</button>
    </form>
  <?php endif ?>
<?php endif ?>
