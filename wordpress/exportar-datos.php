<?php
declare(strict_types=1);

// Vuelca los datos de la web PHP (propiedades, fotos, servicios, preguntas, configuración...) a un JSON
// que importa wordpress/santa-fe-propiedades/importar.php.
// Uso: php wordpress/exportar-datos.php datos.json
require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = App\Core\Conexion::obtenerInstancia()->pdo();
$todo = static fn (string $sql): array => $pdo->query($sql)->fetchAll();

$datos = [
    'tipos'       => $todo('SELECT * FROM tipo_propiedad ORDER BY orden'),
    'zonas'       => $todo('SELECT * FROM zona ORDER BY nombre'),
    'propiedades' => $todo('SELECT p.*, t.slug AS tipo_slug, z.slug AS zona_slug FROM propiedad p JOIN tipo_propiedad t ON t.id_tipo = p.id_tipo LEFT JOIN zona z ON z.id_zona = p.id_zona WHERE p.eliminado_en IS NULL ORDER BY p.codigo'),
    'fotos'       => $todo('SELECT * FROM foto ORDER BY id_propiedad, orden, id_foto'),
    'servicios'   => $todo('SELECT * FROM servicio ORDER BY orden'),
    'motivos'     => $todo('SELECT * FROM motivo ORDER BY orden'),
    'faqs'        => $todo('SELECT * FROM faq ORDER BY orden'),
    'puntos'      => $todo('SELECT * FROM punto_interes ORDER BY categoria, nombre'),
    'config'      => $pdo->query('SELECT clave, valor FROM configuracion')->fetchAll(PDO::FETCH_KEY_PAIR),
];
$salida = $argv[1];
file_put_contents($salida, json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
foreach ($datos as $clave => $filas) {
    echo $clave, ': ', count($filas), "\n";
}
