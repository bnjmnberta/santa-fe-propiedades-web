<?php
// Copiar como config.php y completar. config.php no se sube al repositorio.
// En el hosting: los datos de la base salen del panel (Hostinger/DonWeb > Bases de datos MySQL).
return [
    'db' => [
        'host'    => '127.0.0.1',
        'puerto'  => 3306,
        'nombre'  => 'sfp_web',
        'usuario' => 'sfp',
        'clave'   => 'CAMBIAR',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'url_base' => 'http://localhost:8080',
        'entorno'  => 'desarrollo', // 'produccion' en el hosting: oculta errores
    ],
];
