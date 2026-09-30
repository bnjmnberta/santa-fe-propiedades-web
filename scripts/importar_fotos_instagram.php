<?php
declare(strict_types=1);

/*
 * Reemplaza las fotos de la web vieja por las placas de Instagram, que están mejor
 * producidas (las diseña el equipo de Tars). Fuente: scripts/datos/fotos_instagram.json.
 *
 *   php scripts/importar_fotos_instagram.php --descargar
 *       baja todas las placas a storage/instagram/ y arma storage/instagram/hoja.jpg
 *       (hoja de contactos numerada, para elegir cuáles van a la galería)
 *
 *   php scripts/importar_fotos_instagram.php --aplicar="258:1-5 78:3,1,2,4"
 *       reemplaza las fotos de cada código con las placas indicadas, en ese orden.
 *       La primera es la portada de la tarjeta: nunca la tapa con título y precio
 *       (repite los datos de la tarjeta), ni la placa de cierre.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Conexion;
use App\Servicios\ImagenServicio;

$carpeta = RAIZ . '/storage/instagram';
$fuentes = json_decode((string) file_get_contents(__DIR__ . '/datos/fotos_instagram.json'), true);
unset($fuentes['_nota']);
$opciones = getopt('', ['descargar', 'aplicar:']);

if (isset($opciones['descargar'])) {
    foreach ($fuentes as $codigo => $urls) {
        @mkdir("$carpeta/$codigo", 0755, true);
        foreach ($urls as $indice => $url) {
            $destino = "$carpeta/$codigo/$indice.jpg";
            if (is_file($destino)) {
                continue;
            }
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_OPTIONS    => CURLSSLOPT_NATIVE_CA,
                CURLOPT_USERAGENT      => 'Mozilla/5.0',
            ]);
            $cuerpo = curl_exec($curl);
            $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            if ($cuerpo === false || $estado !== 200) {
                fwrite(STDERR, "  #$codigo placa $indice: HTTP $estado (¿venció la URL?)\n");
                continue;
            }
            file_put_contents($destino, $cuerpo);
            usleep(250_000);
        }
        echo "  #$codigo: " . count(glob("$carpeta/$codigo/*.jpg")) . " placas\n";
    }

    // Hoja de contactos: una fila por código, miniaturas numeradas.
    $alto = 150;
    $ancho = 120;
    $filas = count($fuentes);
    $columnas = max(array_map('count', $fuentes));
    $hoja = imagecreatetruecolor(50 + $columnas * ($ancho + 6), $filas * ($alto + 8) + 8);
    imagefill($hoja, 0, 0, imagecolorallocate($hoja, 25, 25, 25));
    $blanco = imagecolorallocate($hoja, 255, 255, 255);
    $negro = imagecolorallocate($hoja, 0, 0, 0);
    $fila = 0;
    foreach ($fuentes as $codigo => $urls) {
        $y = 8 + $fila * ($alto + 8);
        imagestring($hoja, 5, 6, $y + 60, (string) $codigo, $blanco);
        foreach (array_keys($urls) as $indice) {
            $archivo = "$carpeta/$codigo/$indice.jpg";
            if (!is_file($archivo) || !($imagen = @imagecreatefromjpeg($archivo))) {
                continue;
            }
            $x = 50 + $indice * ($ancho + 6);
            imagecopyresampled($hoja, $imagen, $x, $y, 0, 0, $ancho, $alto, imagesx($imagen), imagesy($imagen));
            imagefilledrectangle($hoja, $x, $y, $x + 16, $y + 15, $negro);
            imagestring($hoja, 4, $x + 3, $y, (string) $indice, $blanco);
        }
        $fila++;
    }
    imagejpeg($hoja, "$carpeta/hoja.jpg", 88);
    echo "Hoja de contactos: $carpeta/hoja.jpg\n";
}

if (isset($opciones['aplicar'])) {
    $pdo = Conexion::obtenerInstancia()->pdo();
    foreach (preg_split('/\s+/', trim((string) $opciones['aplicar'])) as $regla) {
        if (!preg_match('/^(\d+):([\d,\-]+)$/', $regla, $m)) {
            fwrite(STDERR, "Regla inválida: $regla (formato código:1-5 o código:3,1,2)\n");
            continue;
        }
        [, $codigo, $lista] = $m;
        // "1-5" es un rango; "3,1,2,4" fija el orden (la primera es la portada).
        $indices = [];
        foreach (explode(',', $lista) as $parte) {
            [$desde, $hasta] = array_pad(explode('-', $parte), 2, $parte);
            $indices = array_merge($indices, range((int) $desde, (int) $hasta));
        }
        $id = $pdo->prepare('SELECT id_propiedad FROM propiedad WHERE codigo = ?');
        $id->execute([$codigo]);
        $idPropiedad = $id->fetchColumn();
        if ($idPropiedad === false) {
            fwrite(STDERR, "  #$codigo no existe en la base\n");
            continue;
        }

        $destino = PUBLICO . '/uploads/propiedades/' . $codigo;
        foreach (glob("$destino/*.webp") ?: [] as $viejo) {
            unlink($viejo);
        }
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM foto WHERE id_propiedad = ?')->execute([$idPropiedad]);
        $insertar = $pdo->prepare('INSERT INTO foto (id_propiedad, archivo, orden, ancho, alto) VALUES (?, ?, ?, ?, ?)');
        $orden = 0;
        foreach ($indices as $indice) {
            $origen = "$carpeta/$codigo/$indice.jpg";
            if (!is_file($origen)) {
                continue;
            }
            // Nombre único: las fotos se cachean un año, reusar un nombre mostraría la imagen vieja.
            $archivo = sprintf('%d-%s', $codigo, bin2hex(random_bytes(5)));
            $medidas = ImagenServicio::procesar($origen, $destino, $archivo);
            $insertar->execute([$idPropiedad, $archivo, $orden++, $medidas['ancho'], $medidas['alto']]);
        }
        $pdo->commit();
        echo "  #$codigo: $orden fotos de Instagram\n";
    }
}
