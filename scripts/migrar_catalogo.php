<?php
declare(strict_types=1);

/*
 * Migra el catálogo de la web vieja (santafe-propiedades.com.ar) a la base nueva.
 * La web vieja aporta estructura y fotos; Instagram corrige estado, precio y textos
 * (scripts/datos/correcciones_instagram.php); después van las de edición, con títulos y zonas
 * (scripts/datos/correcciones_edicion.php). Ver docs/ANALISIS.md, sección 14.
 *
 *   php scripts/migrar_catalogo.php               solo datos, sin descargar fotos
 *   php scripts/migrar_catalogo.php --fotos       también descarga y convierte las fotos
 *   php scripts/migrar_catalogo.php --solo=257    una o varias propiedades (257,258)
 *
 * Se puede correr varias veces: actualiza cada propiedad por su código.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Conexion;
use App\Servicios\ImagenServicio;
use App\Servicios\Texto;

const SITIO = 'https://santafe-propiedades.com.ar';

$opciones = getopt('', ['fotos', 'solo:']);
$conFotos = isset($opciones['fotos']);
$solo = isset($opciones['solo']) ? array_map('intval', explode(',', (string) $opciones['solo'])) : null;

$pdo = Conexion::obtenerInstancia()->pdo();
$tipos = $pdo->query('SELECT slug, id_tipo FROM tipo_propiedad')->fetchAll(PDO::FETCH_KEY_PAIR);
$zonas = $pdo->query('SELECT slug, id_zona FROM zona')->fetchAll(PDO::FETCH_KEY_PAIR);
$correcciones = array_replace_recursive(
    require __DIR__ . '/datos/correcciones_instagram.php',
    require __DIR__ . '/datos/correcciones_edicion.php'
);

function descargar(string $url): string
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (migracion SFP Web)',
        CURLOPT_SSL_OPTIONS    => CURLSSLOPT_NATIVE_CA,
    ]);
    $cuerpo = curl_exec($curl);
    $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($cuerpo === false || $estado !== 200) {
        throw new RuntimeException("No se pudo descargar $url (HTTP $estado $error)");
    }
    return $cuerpo;
}

/** Texto plano de un fragmento HTML de la web vieja (viene en ISO o UTF-8 con entidades). */
function limpiar(string $html): string
{
    if (!mb_check_encoding($html, 'UTF-8')) {
        $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
    }
    $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/[ \t\x{00A0}]+/u', ' ', str_replace("\r", '', $texto)));
}

/** "TERRAZA" → "Terraza"; "Living comedor" queda igual. */
function normalizarItem(string $item): string
{
    $item = trim($item, " -\t\n");
    if ($item === '') {
        return '';
    }
    return mb_strtoupper($item) === $item ? Texto::capitalizar(mb_strtolower($item)) : Texto::capitalizar($item);
}

function numeroDesde(string $texto): ?float
{
    return preg_match('/(\d+(?:[.,]\d+)?)/', $texto, $m) ? (float) str_replace(',', '.', $m[1]) : null;
}

// 1. Listados: código, tipo y operación de cada tarjeta, zona y destacadas.
function leerListado(string $consulta): array
{
    $tarjetas = [];
    for ($pagina = 0; $pagina < 20; $pagina++) {
        $html = descargar(SITIO . "/resultados.php?{$consulta}pageNum_propiedades={$pagina}");
        preg_match_all(
            '#descripcion\.php\?id=(\d+)\s*">([^<]+)</a></h4>\s*<p\s*>(.*?)</p>#s',
            $html,
            $coincidencias,
            PREG_SET_ORDER
        );
        $nuevas = 0;
        foreach ($coincidencias as [, $codigo, $encabezado, $direccion]) {
            if (!isset($tarjetas[(int) $codigo])) {
                $tarjetas[(int) $codigo] = ['encabezado' => limpiar($encabezado), 'direccion' => limpiar($direccion)];
                $nuevas++;
            }
        }
        if ($nuevas === 0) {
            break;
        }
    }
    return $tarjetas;
}

echo "Leyendo listados de " . SITIO . "...\n";
$tarjetas = leerListado('');
$destacadas = array_keys(leerListado('destacados=S&'));
echo count($tarjetas) . " propiedades publicadas, " . count($destacadas) . " destacadas.\n\n";

// 2. Fichas
$propiedades = [];
foreach ($tarjetas as $codigo => $tarjeta) {
    if ($solo !== null && !in_array($codigo, $solo, true)) {
        continue;
    }
    $html = descargar(SITIO . '/descripcion.php?id=' . $codigo);

    if (!preg_match('/^(.+?) en (venta|alquiler)$/i', $tarjeta['encabezado'], $m)) {
        fwrite(STDERR, "  #$codigo: no se reconoce tipo/operación en '{$tarjeta['encabezado']}', se saltea\n");
        continue;
    }
    $tipoSlug = Texto::slug($m[1]);
    if (!isset($tipos[$tipoSlug])) {
        fwrite(STDERR, "  #$codigo: tipo desconocido '$m[1]', se saltea\n");
        continue;
    }

    $p = [
        'codigo'      => $codigo,
        'operacion'   => strtolower($m[2]),
        'id_tipo'     => $tipos[$tipoSlug],
        'zona'        => null,
        'titulo'      => preg_match('#<div class="col-lg-9 col-sm-8 ">\s*<h2>(.*?)</h2>#s', $html, $t) ? Texto::capitalizar(limpiar($t[1])) : "Propiedad $codigo",
        'descripcion' => preg_match('#Descripci[^<]{1,10}</h4>\s*<p>(.*?)</p>#s', $html, $d) ? limpiar($d[1]) : null,
        'direccion'   => preg_match('#glyphicon-map-marker"></span>\s*(.*?)</p>#s', $html, $dir) ? (limpiar($dir[1]) ?: null) : null,
        'moneda'      => null,
        'precio'      => null,
        'latitud'     => null,
        'longitud'    => null,
        'dormitorios' => null,
        'banos'       => null,
        'cocheras'    => 0,
        'sup_cubierta' => null,
        'sup_terreno' => null,
        'regimen'     => null,
        'planta'      => null,
        'destacada'   => in_array($codigo, $destacadas, true) ? 1 : 0,
        'fotos'       => [],
    ];

    // Zona: de la ficha o, si falta, del "(centro)" de la tarjeta.
    $zonaTexto = preg_match('#glyphicon-road"></span>\s*(.*?)</p>#s', $html, $z) ? limpiar($z[1]) : '';
    if ($zonaTexto === '' && preg_match('/\(([^)]+)\)/', $tarjeta['direccion'], $z)) {
        $zonaTexto = $z[1];
    }
    $p['zona'] = $zonaTexto !== '' && isset($zonas[Texto::slug($zonaTexto)]) ? Texto::slug($zonaTexto) : null;

    if (preg_match('#<p class="price">\s*(.*?)\s*</p>#s', $html, $precio)
        && preg_match('/(U\$S|\$)\s*([\d.]+)/', limpiar($precio[1]), $pm)) {
        $monto = (float) str_replace('.', '', $pm[2]);
        if ($monto > 0) {
            $p['moneda'] = $pm[1] === 'U$S' ? 'USD' : 'ARS';
            $p['precio'] = $monto;
        }
    }

    if (preg_match('/LatLng\((-?\d+\.\d+),\s*(-?\d+\.\d+)\)/', $html, $coordenadas)) {
        $p['latitud'] = round((float) $coordenadas[1], 6);
        $p['longitud'] = round((float) $coordenadas[2], 6);
    }

    // Bloque técnico: líneas separadas por <br />, con secciones CARACTERÍSTICAS y SERVICIOS.
    $caracteristicas = [];
    $servicios = [];
    if (preg_match('#Descripci[^<]{1,10}</h4>\s*<p>.*?</p>\s*<p>(.*?)</p>#s', $html, $bloque)) {
        $seccion = null;
        foreach (preg_split('#<br\s*/?>#i', $bloque[1]) as $linea) {
            $linea = limpiar($linea);
            if ($linea === '') {
                continue;
            }
            if (preg_match('/^CARACTER.STICAS:?$/iu', $linea)) { $seccion = 'caracteristicas'; continue; }
            if (preg_match('/^SERVICIOS:?$/i', $linea)) { $seccion = 'servicios'; continue; }

            if ($seccion === null) {
                if (preg_match('/^REGIMEN DE (.+)$/i', $linea, $r)) {
                    $p['regimen'] = Texto::capitalizar(mb_strtolower($r[1]));
                } elseif (preg_match('/^Superficie cubierta:/i', $linea)) {
                    $p['sup_cubierta'] = numeroDesde($linea);
                } elseif (preg_match('/^Superficie (terreno|total|lote)/i', $linea)) {
                    $p['sup_terreno'] = numeroDesde($linea);
                } elseif (preg_match('/^Planta:\s*(.+)$/i', $linea, $pl)) {
                    $p['planta'] = Texto::capitalizar(mb_strtolower($pl[1]));
                } elseif (preg_match('/^Ascensores?:\s*(\d+)/i', $linea, $as)) {
                    if ((int) $as[1] > 0) {
                        $caracteristicas[] = 'Ascensor';
                    }
                } else {
                    $caracteristicas[] = normalizarItem($linea);
                }
                continue;
            }

            foreach (explode(' - ', $linea) as $item) {
                $item = normalizarItem($item);
                if ($item === '') {
                    continue;
                }
                if ($seccion === 'servicios') {
                    $servicios[] = $item;
                } elseif (preg_match('/^(\d+)\s*Dormitorio/i', $item, $n)) {
                    $p['dormitorios'] = (int) $n[1];
                } elseif (preg_match('/^(\d+)\s*Ba.o/iu', $item, $n)) {
                    $p['banos'] = (int) $n[1];
                } elseif (preg_match('/^(\d+)\s*Cochera/i', $item, $n)) {
                    $p['cocheras'] = (int) $n[1];
                } elseif (preg_match('/^Monoambiente$/i', $item)) {
                    $p['dormitorios'] = 0;
                } else {
                    $caracteristicas[] = $item;
                }
            }
        }
    }
    $p['caracteristicas'] = $caracteristicas ? implode("\n", array_unique(array_filter($caracteristicas))) : null;
    $p['servicios'] = $servicios ? implode(' · ', array_unique($servicios)) : null;

    preg_match_all("#src='cache/([0-9a-f]+)_w800_h600_cp_ma\.jpg'#", $html, $fotos);
    $p['fotos'] = array_values(array_unique($fotos[1]));

    $propiedades[$codigo] = $p;
    usleep(300_000); // no cargar el servidor viejo
}

// Coordenadas repetidas en más de 2 fichas son un valor por defecto de la web vieja, no la ubicación real.
$repetidas = array_count_values(array_map(
    fn (array $p) => $p['latitud'] === null ? '' : $p['latitud'] . ',' . $p['longitud'],
    $propiedades
));
foreach ($propiedades as &$p) {
    if ($p['latitud'] !== null && $repetidas[$p['latitud'] . ',' . $p['longitud']] > 2) {
        $p['latitud'] = $p['longitud'] = null;
    }
}
unset($p);

// 3. Correcciones de Instagram y guardado
$columnas = ['codigo', 'slug', 'titulo', 'operacion', 'id_tipo', 'id_zona', 'direccion', 'moneda', 'precio',
    'expensas_detalle', 'requisitos', 'amoblado', 'dormitorios', 'banos', 'cocheras', 'sup_cubierta', 'sup_terreno',
    'frente_m', 'fondo_m', 'regimen', 'planta', 'ubicacion_unidad', 'caracteristicas', 'servicios', 'referencias',
    'descripcion', 'instagram_url', 'latitud', 'longitud', 'estado', 'destacada', 'fecha_cierre'];
$actualizar = implode(', ', array_map(fn ($c) => "$c = VALUES($c)", array_diff($columnas, ['codigo'])));
$guardar = $pdo->prepare('INSERT INTO propiedad (' . implode(', ', $columnas) . ') VALUES (:'
    . implode(', :', $columnas) . ") ON DUPLICATE KEY UPDATE $actualizar");
$existe = $pdo->prepare('SELECT id_propiedad FROM propiedad WHERE codigo = ?');
$historial = $pdo->prepare("INSERT INTO historial_estado (id_propiedad, estado_anterior, estado_nuevo, nota, fecha)
    VALUES (?, 'disponible', ?, ?, ?)");

foreach ($propiedades as $codigo => $p) {
    $correccion = $correcciones[$codigo] ?? [];
    $fechaInstagram = $correccion['_fecha_instagram'] ?? null;
    if (isset($correccion['zona'])) {
        $p['zona'] = $correccion['zona'];
    }
    unset($correccion['_fecha_instagram'], $correccion['zona']);
    $p = array_merge($p, $correccion);
    $p['estado'] ??= 'disponible';

    $existe->execute([$codigo]);
    $esNueva = $existe->fetchColumn() === false;

    $valores = [];
    foreach ($columnas as $columna) {
        $valores[$columna] = $p[$columna] ?? null;
    }
    $valores['slug'] = Texto::slug($p['titulo']);
    $valores['id_zona'] = $p['zona'] !== null ? $zonas[$p['zona']] : null;
    $valores['amoblado'] = (int) ($p['amoblado'] ?? 0);
    $valores['cocheras'] = (int) ($p['cocheras'] ?? 0);
    $valores['fecha_cierre'] = in_array($p['estado'], ['vendido', 'alquilado'], true) ? ($fechaInstagram ?? date('Y-m-d')) . ' 12:00:00' : null;

    $pdo->beginTransaction();
    $guardar->execute($valores);
    $existe->execute([$codigo]);
    $idPropiedad = (int) $existe->fetchColumn();
    if ($esNueva && $p['estado'] !== 'disponible') {
        $historial->execute([$idPropiedad, $p['estado'], 'Migración: estado según Instagram', $valores['fecha_cierre']]);
    }

    $cantidadFotos = count($p['fotos']);
    if ($conFotos && $cantidadFotos > 0) {
        $carpeta = PUBLICO . '/uploads/propiedades/' . $codigo;
        $pdo->prepare('DELETE FROM foto WHERE id_propiedad = ?')->execute([$idPropiedad]);
        foreach (glob("$carpeta/*.webp") ?: [] as $viejo) {
            unlink($viejo);
        }
        $insertarFoto = $pdo->prepare('INSERT INTO foto (id_propiedad, archivo, orden, ancho, alto) VALUES (?, ?, ?, ?, ?)');
        foreach ($p['fotos'] as $orden => $hash) {
            $temporal = tempnam(sys_get_temp_dir(), 'sfp');
            file_put_contents($temporal, descargar(SITIO . "/cache/{$hash}_w800_h600_cp_ma.jpg"));
            // Nombre único: las fotos se cachean un año, reusar un nombre mostraría la imagen vieja.
            $archivo = sprintf('%d-%s', $codigo, bin2hex(random_bytes(5)));
            $medidas = ImagenServicio::procesar($temporal, $carpeta, $archivo);
            unlink($temporal);
            $insertarFoto->execute([$idPropiedad, $archivo, $orden, $medidas['ancho'], $medidas['alto']]);
            usleep(200_000);
        }
    }
    $pdo->commit();

    printf(
        "  #%-4d %-9s %-40s %-18s %2d fotos%s%s\n",
        $codigo,
        $p['operacion'],
        mb_strimwidth($p['titulo'], 0, 40, '…'),
        $valores['precio'] ? ($valores['moneda'] === 'USD' ? 'U$S ' : '$ ') . number_format((float) $valores['precio'], 0, ',', '.') : 'consultar',
        $cantidadFotos,
        $conFotos ? '' : ' (sin descargar)',
        $correccion ? '  ← Instagram: ' . implode(', ', array_keys($correccion)) : ''
    );
}

echo "\nListo: " . count($propiedades) . " propiedades migradas" . ($conFotos ? ' con fotos' : ' sin fotos (usar --fotos)') . ".\n";
