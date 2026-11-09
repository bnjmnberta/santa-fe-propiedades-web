<?php
/**
 * Logo de Santa Fe Propiedades (PNG con el recorte justo, sin margen transparente).
 * $claro = true usa la versión con letras blancas, para fondos oscuros como el pie.
 * El enlace que lo envuelve ya lleva el aria-label, por eso la imagen va con alt vacío.
 */
$claro = $claro ?? false;
?>
<img class="logo__img" src="<?= e(asset($claro ? 'assets/img/logo-blanco.png' : 'assets/img/logo.png')) ?>" alt="" width="406" height="95">
