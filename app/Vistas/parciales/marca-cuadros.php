<?php
/**
 * Los 8 cuadraditos del logo (versión PHP de LogoMark.jsx, de "Santa Fe Loader").
 * Orden de los índices (--i): se construye de abajo hacia arriba, como un edificio:
 * fila azul (0-2), fila celeste (3-5), fila roja (6-7).
 * Proporciones del logo original (images/logo.png de la web vieja): cuadrados de lado igual
 * y un espacio entre ellos de 0,23 veces el lado. $tamanio es el ancho aproximado del conjunto.
 * Medidas en píxeles enteros y pares: con decimales el navegador redondea distinto cada
 * fila y los espacios quedan desparejos; con un total impar los bordes se ven borrosos.
 *
 * @var int $tamanio
 */
$par = static fn (float $n): int => max(2, 2 * (int) round($n / 2));
$ancho = $par($tamanio / (3 + 2 * 0.233));
$alto = $ancho;
$espacio = max(2, (int) round($ancho * 0.233));
$cuadros = [
    [1, 3, 'blue'], [2, 3, 'blue'], [3, 3, 'blue'],
    [1, 2, 'cyan'], [2, 2, 'cyan'], [3, 2, 'cyan'],
    [1, 1, 'red'], [2, 1, 'red'],
];
?>
<div class="sf-mark" style="--sq-w: <?= $ancho ?>px; --sq-h: <?= $alto ?>px; --gap: <?= $espacio ?>px" aria-hidden="true">
  <?php foreach ($cuadros as $i => [$columna, $fila, $tono]): ?>
    <span class="sf-sq sf-sq--<?= $tono ?>" style="grid-column: <?= $columna ?>; grid-row: <?= $fila ?>; --i: <?= $i ?>"></span>
  <?php endforeach ?>
</div>
