<?php
declare(strict_types=1);

namespace App\Controladores\Panel;

use App\Core\Conexion;
use App\Core\Csrf;
use App\Core\NoEncontrado;
use App\Core\Sesion;
use App\Core\Vista;
use App\Modelos\EstadoPropiedad;
use App\Modelos\Operacion;
use App\Modelos\Propiedad;
use App\Repositorios\GestionPropiedadRepositorio;
use App\Repositorios\PropiedadRepositorio;
use App\Servicios\TextoInstagram;
use App\Servicios\ValidadorPropiedad;

final class PropiedadesControlador
{
    private GestionPropiedadRepositorio $repositorio;

    public function __construct()
    {
        $this->repositorio = new GestionPropiedadRepositorio();
    }

    public function inicio(): void
    {
        Sesion::exigirUsuario();
        $texto = trim((string) ($_GET['q'] ?? ''));
        $estado = (string) ($_GET['estado'] ?? '');
        Vista::render('panel/inicio', [
            'titulo'      => 'Propiedades',
            'propiedades' => $this->repositorio->listar($texto, $estado ?: null),
            'contadores'  => $this->repositorio->contadores(),
            'texto'       => $texto,
            'estado'      => $estado,
        ], 'layouts/panel');
    }

    public function nueva(): void
    {
        Sesion::exigirUsuario();
        $this->formulario(null, ['operacion' => 'alquiler', 'mostrar_direccion' => 1, 'cocheras' => 0], []);
    }

    public function crear(): void
    {
        $usuario = Sesion::exigirUsuario();
        Csrf::verificar();
        [$datos, $errores] = $this->validar();
        if ($errores) {
            $this->formulario(null, $_POST, $errores);
            return;
        }
        $id = $this->repositorio->crear($datos, $usuario['id']);
        Sesion::avisar('ok', 'Propiedad creada. Ahora sumale las fotos.');
        redirigir("/panel/propiedades/$id#fotos");
    }

    public function editar(string $id): void
    {
        Sesion::exigirUsuario();
        $fila = $this->buscar((int) $id);
        $this->formulario($fila, $fila + ['coordenadas' => $fila['latitud'] ? $fila['latitud'] . ', ' . $fila['longitud'] : ''], []);
    }

    public function actualizar(string $id): void
    {
        Sesion::exigirUsuario();
        Csrf::verificar();
        $fila = $this->buscar((int) $id);
        [$datos, $errores] = $this->validar();
        if ($errores) {
            $this->formulario($fila, $_POST, $errores);
            return;
        }
        $this->repositorio->actualizar((int) $id, $datos);
        Sesion::avisar('ok', 'Cambios guardados.');
        redirigir("/panel/propiedades/$id");
    }

    /**
     * Regla f, paso 1: elegir el estado nuevo. Paso 2 (?nuevo=vendido): confirmar.
     * Así un cambio de estado siempre lleva dos toques y nunca se hace por error.
     */
    public function estado(string $id): void
    {
        Sesion::exigirUsuario();
        $fila = $this->buscar((int) $id);
        $actual = EstadoPropiedad::from($fila['estado']);
        $permitidos = $actual->transicionesPermitidas(Operacion::from($fila['operacion']), Sesion::esAdministrador());
        $nuevo = EstadoPropiedad::tryFrom((string) ($_GET['nuevo'] ?? ''));
        if ($nuevo !== null && !in_array($nuevo, $permitidos, true)) {
            $nuevo = null;
        }
        Vista::render('panel/estado', [
            'titulo'     => 'Cambiar estado',
            'propiedad'  => $fila,
            'actual'     => $actual,
            'permitidos' => $permitidos,
            'nuevo'      => $nuevo,
            'historial'  => $this->repositorio->historial((int) $id),
        ], 'layouts/panel');
    }

    public function cambiarEstado(string $id): void
    {
        $usuario = Sesion::exigirUsuario();
        Csrf::verificar();
        $fila = $this->buscar((int) $id);
        $actual = EstadoPropiedad::from($fila['estado']);
        $nuevo = EstadoPropiedad::tryFrom((string) ($_POST['nuevo'] ?? ''));
        // Reglas e y f: se revalida en el servidor aunque el botón ya venga filtrado.
        if ($nuevo === null || !$actual->puedePasarA($nuevo, Operacion::from($fila['operacion']), Sesion::esAdministrador())) {
            Sesion::avisar('error', 'Ese cambio de estado no está permitido.');
            redirigir("/panel/propiedades/$id/estado");
        }
        $nota = mb_substr(trim((string) ($_POST['nota'] ?? '')), 0, 200);
        $this->repositorio->cambiarEstado((int) $id, $actual, $nuevo, $usuario['id'], $nota);
        Sesion::avisar('ok', sprintf('#%d quedó como %s.', $fila['codigo'], mb_strtolower($nuevo->etiqueta())));
        redirigir('/panel');
    }

    public function destacar(string $id): void
    {
        Sesion::exigirUsuario();
        Csrf::verificar();
        $this->buscar((int) $id);
        $this->repositorio->alternarDestacada((int) $id);
        redirigir(paginaAnterior('/panel'));
    }

    public function baja(string $id): void
    {
        Sesion::exigirAdministrador();
        Csrf::verificar();
        $fila = $this->buscar((int) $id);
        $this->repositorio->darDeBaja((int) $id);
        Sesion::avisar('ok', sprintf('#%d se dio de baja. Sigue guardada en la base, pero ya no aparece en ningún lado.', $fila['codigo']));
        redirigir('/panel');
    }

    private function formulario(?array $fila, array $valores, array $errores): void
    {
        $pdo = Conexion::obtenerInstancia()->pdo();
        $propiedad = $fila ? (new PropiedadRepositorio())->porCodigo((int) $fila['codigo']) : null;
        Vista::render('panel/propiedad', [
            'titulo'    => $fila ? 'Editar #' . $fila['codigo'] : 'Nueva propiedad',
            'fila'      => $fila,
            'valores'   => $valores,
            'errores'   => $errores,
            'tipos'     => $pdo->query('SELECT id_tipo, nombre FROM tipo_propiedad ORDER BY orden')->fetchAll(),
            'zonas'     => $pdo->query('SELECT id_zona, nombre FROM zona ORDER BY nombre')->fetchAll(),
            'fotos'     => $fila ? $this->repositorio->fotos((int) $fila['id_propiedad']) : [],
            'instagram' => $propiedad ? TextoInstagram::generar($propiedad, url($propiedad->url())) : null,
            'propiedad' => $propiedad,
        ], 'layouts/panel');
    }

    private function validar(): array
    {
        $pdo = Conexion::obtenerInstancia()->pdo();
        return ValidadorPropiedad::validar(
            $_POST,
            array_map('intval', $pdo->query('SELECT id_tipo FROM tipo_propiedad')->fetchAll(\PDO::FETCH_COLUMN)),
            array_map('intval', $pdo->query('SELECT id_zona FROM zona')->fetchAll(\PDO::FETCH_COLUMN))
        );
    }

    private function buscar(int $id): array
    {
        return $this->repositorio->porId($id) ?? throw new NoEncontrado("propiedad $id");
    }
}
