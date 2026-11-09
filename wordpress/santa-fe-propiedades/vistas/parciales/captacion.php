<?php
/**
 * Franjas "Alquilá con nosotros" / "Vendé con nosotros", de ancho completo. En escritorio van lado a lado
 * (una azul y una roja, los colores de cada operación); en celular, una debajo de la otra.
 * Se usan en la portada, el catálogo, Nosotros, Contacto y las páginas de captación.
 * Van fuera de cualquier .contenedor para ocupar todo el ancho.
 *
 * @var ?string $sin 'alquila' o 'vende': omite esa franja (en su propia página no tiene sentido).
 */
if (!modulo('captacion')) {
    return;
}
$sin ??= null;
?>
<div class="franjas franjas--<?= $sin === null ? 'dos' : 'una' ?>">
<?php if ($sin !== 'alquila'): ?>
  <section class="franja franja--alquiler">
    <div class="contenedor franja__contenido">
      <div>
        <h2 class="franja__titulo">Alquilá con nosotros</h2>
        <p>La publicamos en la web y en Instagram, buscamos inquilinos y administramos el alquiler para que cobres tranquilo.</p>
      </div>
      <a class="boton boton--claro" href="/alquila-con-nosotros">Quiero alquilar mi propiedad <?= icono('flecha') ?></a>
    </div>
  </section>
<?php endif ?>
<?php if ($sin !== 'vende'): ?>
  <section class="franja franja--venta">
    <div class="contenedor franja__contenido">
      <div>
        <h2 class="franja__titulo">Vendé con nosotros</h2>
        <p>Tasación profesional, difusión y asesoramiento en cada paso de la operación.</p>
      </div>
      <a class="boton boton--claro" href="/vende-con-nosotros">Pedí tu tasación <?= icono('flecha') ?></a>
    </div>
  </section>
<?php endif ?>
</div>
