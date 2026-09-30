<?php
declare(strict_types=1);

use App\Controladores\Panel\AccesoControlador;
use App\Controladores\Panel\AdministracionControlador;
use App\Controladores\Panel\FotosControlador;
use App\Controladores\Panel\PropiedadesControlador;
use App\Controladores\Publico\CatalogoControlador;
use App\Controladores\Publico\FichaControlador;
use App\Controladores\Publico\InicioControlador;
use App\Controladores\Publico\PaginaControlador;
use App\Controladores\Publico\RedireccionControlador;

/** @var App\Core\Router $router */

// Sitio público
$router->get('/', [InicioControlador::class, 'mostrar']);
$router->get('/propiedades', [CatalogoControlador::class, 'listar']);
$router->get('/{seccion:ventas|alquileres|comerciales}', [CatalogoControlador::class, 'listar']);
$router->get('/propiedad/{codigo:\d+}-{slug:[a-z0-9-]+}', [FichaControlador::class, 'mostrar']);
$router->get('/propiedad/{codigo:\d+}', [FichaControlador::class, 'mostrar']);
$router->get('/servicios', [PaginaControlador::class, 'servicios']);
$router->get('/la-empresa', [PaginaControlador::class, 'empresa']);
$router->get('/{operacion:alquila|vende}-con-nosotros', [PaginaControlador::class, 'captacion']);
$router->get('/preguntas-frecuentes', [PaginaControlador::class, 'preguntasFrecuentes']);
$router->get('/contacto', [RedireccionControlador::class, 'contacto']);

// Panel de autogestión
$router->get('/panel/ingresar', [AccesoControlador::class, 'formulario']);
$router->post('/panel/ingresar', [AccesoControlador::class, 'ingresar']);
$router->post('/panel/salir', [AccesoControlador::class, 'salir']);
$router->get('/panel', [PropiedadesControlador::class, 'inicio']);
$router->get('/panel/propiedades/nueva', [PropiedadesControlador::class, 'nueva']);
$router->post('/panel/propiedades', [PropiedadesControlador::class, 'crear']);
$router->get('/panel/propiedades/{id:\d+}', [PropiedadesControlador::class, 'editar']);
$router->post('/panel/propiedades/{id:\d+}', [PropiedadesControlador::class, 'actualizar']);
$router->get('/panel/propiedades/{id:\d+}/estado', [PropiedadesControlador::class, 'estado']);
$router->post('/panel/propiedades/{id:\d+}/estado', [PropiedadesControlador::class, 'cambiarEstado']);
$router->post('/panel/propiedades/{id:\d+}/destacar', [PropiedadesControlador::class, 'destacar']);
$router->post('/panel/propiedades/{id:\d+}/baja', [PropiedadesControlador::class, 'baja']);
$router->post('/panel/propiedades/{id:\d+}/fotos', [FotosControlador::class, 'subir']);
$router->post('/panel/fotos/{id:\d+}/mover', [FotosControlador::class, 'mover']);
$router->post('/panel/fotos/{id:\d+}/borrar', [FotosControlador::class, 'borrar']);
$router->get('/panel/usuarios', [AdministracionControlador::class, 'usuarios']);
$router->post('/panel/usuarios', [AdministracionControlador::class, 'crearUsuario']);
$router->post('/panel/usuarios/{id:\d+}/contrasena', [AdministracionControlador::class, 'contrasena']);
$router->post('/panel/usuarios/{id:\d+}/activo', [AdministracionControlador::class, 'activo']);
$router->get('/panel/configuracion', [AdministracionControlador::class, 'configuracion']);
$router->post('/panel/configuracion', [AdministracionControlador::class, 'guardarConfiguracion']);

// URLs de la web vieja
$router->get('/descripcion.php', [RedireccionControlador::class, 'fichaVieja']);
$router->get('/resultados.php', [RedireccionControlador::class, 'listadoViejo']);
$router->get('/{pagina:empresa|servicios|contacto}.php', [RedireccionControlador::class, 'paginaVieja']);
