<?php
declare(strict_types=1);

/*
 * Crea un usuario del panel desde la consola (el primero tiene que salir de acá).
 *
 *   php scripts/crear_usuario.php --nombre="Germán" --email=german@ejemplo.com --rol=administrador
 *
 * Genera una contraseña aleatoria y la guarda en el archivo indicado con --guardar-en
 * (por defecto se muestra en pantalla). Después se puede cambiar desde el panel.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Repositorios\UsuarioRepositorio;

$opciones = getopt('', ['nombre:', 'email:', 'rol:', 'guardar-en:']);
$nombre = trim((string) ($opciones['nombre'] ?? ''));
$email = trim((string) ($opciones['email'] ?? ''));
$rol = ($opciones['rol'] ?? 'editor') === 'administrador' ? 'administrador' : 'editor';

if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/crear_usuario.php --nombre=\"Nombre\" --email=correo@dominio --rol=administrador|editor [--guardar-en=archivo]\n");
    exit(1);
}
$repositorio = new UsuarioRepositorio();
if ($repositorio->porEmail($email) !== null) {
    fwrite(STDERR, "Ya existe un usuario con el email $email.\n");
    exit(1);
}

$alfabeto = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$contrasena = '';
for ($i = 0; $i < 16; $i++) {
    $contrasena .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
}
$repositorio->crear($nombre, $email, $contrasena, $rol);

if (isset($opciones['guardar-en'])) {
    file_put_contents((string) $opciones['guardar-en'], "Panel SFP · $email ($rol)\nContraseña: $contrasena\n");
    echo "Usuario $email ($rol) creado. Contraseña guardada en {$opciones['guardar-en']}.\n";
} else {
    echo "Usuario $email ($rol) creado. Contraseña: $contrasena\n";
}
