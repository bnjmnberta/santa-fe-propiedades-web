<?php
declare(strict_types=1);

namespace App\Controladores\Panel;

use App\Core\Conexion;
use App\Core\Csrf;
use App\Core\Sesion;
use App\Core\Vista;
use App\Repositorios\UsuarioRepositorio;

/** Solo Administrador (regla l): usuarios del panel y datos de contacto / módulos del sitio. */
final class AdministracionControlador
{
    /** Claves editables desde el panel, con su etiqueta y tipo de campo. */
    private const CONFIGURACION = [
        'Contacto' => [
            'whatsapp_numero'  => ['WhatsApp (formato internacional, ej: 5493424219298)', 'texto'],
            'whatsapp_visible' => ['WhatsApp tal como se muestra', 'texto'],
            'telefono_fijo'    => ['Teléfono de la oficina', 'texto'],
            'email'            => ['Email', 'texto'],
            'direccion'        => ['Dirección', 'texto'],
            'oficina_coordenadas' => ['Ubicación de la oficina en el mapa de Contacto (ej: -31.6474, -60.7121)', 'texto'],
            'horario'          => ['Horario de atención', 'texto'],
            'matricula'        => ['Matrículas', 'texto'],
            'instagram_url'    => ['Instagram (enlace)', 'texto'],
        ],
        'Portada' => [
            'eslogan'          => ['Título principal', 'texto'],
            'eslogan_bajada'   => ['Texto debajo del título', 'texto'],
        ],
        'Analítica' => [
            'ga_measurement_id' => ['ID de Google Analytics 4 (G-XXXXXXX). Vacío = sin medición', 'texto'],
        ],
        'Secciones visibles' => [
            'modulo_mapa'      => ['Mapa con puntos estratégicos', 'modulo'],
            'modulo_paginas'   => ['Páginas Servicios y Nosotros', 'modulo'],
            'modulo_captacion' => ['Alquilá / Vendé con nosotros', 'modulo'],
            'modulo_faq'       => ['Preguntas frecuentes', 'modulo'],
        ],
    ];

    public function usuarios(): void
    {
        Sesion::exigirAdministrador();
        Vista::render('panel/usuarios', [
            'titulo'   => 'Usuarios',
            'usuarios' => (new UsuarioRepositorio())->todos(),
        ], 'layouts/panel');
    }

    public function crearUsuario(): void
    {
        Sesion::exigirAdministrador();
        Csrf::verificar();
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        $rol = ($_POST['rol'] ?? '') === 'administrador' ? 'administrador' : 'editor';
        $repositorio = new UsuarioRepositorio();

        $error = match (true) {
            $nombre === '' => 'Poné el nombre.',
            !filter_var($email, FILTER_VALIDATE_EMAIL) => 'El email no es válido.',
            $repositorio->porEmail($email) !== null => 'Ya hay un usuario con ese email.',
            mb_strlen($contrasena) < 10 => 'La contraseña tiene que tener al menos 10 caracteres.',
            default => null,
        };
        if ($error) {
            Sesion::avisar('error', $error);
        } else {
            $repositorio->crear($nombre, $email, $contrasena, $rol);
            Sesion::avisar('ok', "Usuario creado. Pasale a $nombre su email y contraseña por un medio privado.");
        }
        redirigir('/panel/usuarios');
    }

    public function contrasena(string $id): void
    {
        Sesion::exigirAdministrador();
        Csrf::verificar();
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        if (mb_strlen($contrasena) < 10) {
            Sesion::avisar('error', 'La contraseña tiene que tener al menos 10 caracteres.');
        } else {
            (new UsuarioRepositorio())->cambiarContrasena((int) $id, $contrasena);
            Sesion::avisar('ok', 'Contraseña cambiada.');
        }
        redirigir('/panel/usuarios');
    }

    public function activo(string $id): void
    {
        $yo = Sesion::exigirAdministrador();
        Csrf::verificar();
        if ((int) $id === $yo['id']) {
            Sesion::avisar('error', 'No podés desactivar tu propio usuario.');
        } else {
            (new UsuarioRepositorio())->cambiarActivo((int) $id, ($_POST['activo'] ?? '') === '1');
            Sesion::avisar('ok', 'Usuario actualizado.');
        }
        redirigir('/panel/usuarios');
    }

    public function configuracion(): void
    {
        Sesion::exigirAdministrador();
        $valores = Conexion::obtenerInstancia()->pdo()->query('SELECT clave, valor FROM configuracion')->fetchAll(\PDO::FETCH_KEY_PAIR);
        Vista::render('panel/configuracion', [
            'titulo'  => 'Configuración del sitio',
            'grupos'  => self::CONFIGURACION,
            'valores' => $valores,
        ], 'layouts/panel');
    }

    public function guardarConfiguracion(): void
    {
        Sesion::exigirAdministrador();
        Csrf::verificar();
        $guardar = Conexion::obtenerInstancia()->pdo()->prepare(
            'INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach (self::CONFIGURACION as $campos) {
            foreach ($campos as $clave => [, $tipo]) {
                $valor = $tipo === 'modulo'
                    ? (isset($_POST[$clave]) ? '1' : '0')
                    : mb_substr(trim((string) ($_POST[$clave] ?? '')), 0, 300);
                if ($clave === 'whatsapp_numero') {
                    $valor = preg_replace('/\D+/', '', $valor);
                }
                $guardar->execute([$clave, $valor]);
            }
        }
        Sesion::avisar('ok', 'Configuración guardada.');
        redirigir('/panel/configuracion');
    }
}
