# Guía de uso: qué hay en el repositorio y cómo usarlo

Este documento explica cada parte del proyecto **Santa Fe Propiedades** y cómo usarla. Para el análisis de requisitos y reglas de negocio, ver [`ANALISIS.md`](ANALISIS.md). Para instalar, el [`README.md`](../README.md) de la raíz.

El proyecto tiene dos versiones del mismo sitio, con el mismo diseño y las mismas páginas:

| Versión | Dónde está | Para qué sirve |
|---|---|---|
| **Web PHP** | `app/`, `public/`, `config/`, `database/`, `scripts/` | La web con su propio panel (`/panel`). Es la versión completa. |
| **Plugin de WordPress** | `wordpress/` | El mismo sitio dentro de un WordPress: las propiedades se editan desde su escritorio. |

---

## 1. Mapa de carpetas y archivos

| Qué | Contiene | Cómo se usa |
|---|---|---|
| `app/Controladores/Publico/` | Qué hace cada página del sitio: portada, catálogo, ficha, servicios, nosotros, contacto, preguntas frecuentes y redirecciones de la web vieja. | No se toca para usar el sitio; se edita para cambiar su comportamiento. |
| `app/Controladores/Panel/` | Las pantallas del panel: acceso, propiedades, fotos, usuarios, configuración, chats y avisos. | Idem. |
| `app/Controladores/Api/` | API que usa el asistente de WhatsApp para cambiar el estado o el precio de una propiedad. | Solo la llama el asistente, de servidor a servidor. |
| `app/Modelos/` | Reglas de las propiedades: operación (alquiler o venta), estados, precio, filtros del buscador. | Ver sección 4. |
| `app/Repositorios/` | Consultas a la base de datos. | — |
| `app/Servicios/` | Piezas con una tarea: procesar fotos, texto para Instagram, enlace de WhatsApp, horario de atención (abierto o cerrado), validar propiedades, notificaciones push. | — |
| `app/Vistas/` | El HTML de cada página, del panel y de los bloques que se repiten (tarjeta de propiedad, mapa, pie, íconos, logo). | Para cambiar un texto fijo o el diseño de un bloque. |
| `app/rutas.php` | Lista de direcciones del sitio y a qué controlador va cada una. | Para agregar o cambiar una dirección. |
| `public/` | La raíz web: `index.php`, `.htaccess`, `assets/` (estilos, scripts, fuentes e imágenes) y `uploads/` (fotos de las propiedades, no se sube a GitHub). | En el hosting, su contenido va en `public_html`. |
| `public/assets/img/logo.png` y `logo-blanco.png` | Logo a color (cabecera, panel) y logo con letras blancas (pie). | Ver sección 9. |
| `config/config.example.php` | Modelo de configuración: base de datos, dirección del sitio, entorno y conexión con el asistente. | Se copia como `config/config.php` (ese archivo no se sube). |
| `database/schema.sql` | Estructura de la base y los datos de partida (tipos, zonas, servicios, contacto). | Se importa primero. |
| `database/migraciones/` | Cambios posteriores a la base, numerados (`002` a `009`). | Se aplican en orden. Ver sección 4. |
| `scripts/` | Programas de consola: cargar el catálogo, fotos de Instagram, crear usuarios. | Ver sección 5. |
| `LOCAL.bat` | Prende la base de datos y el servidor web en una PC con Windows y abre el sitio. | Doble clic. |
| `docs/` | `ANALISIS.md` (análisis), `propuesta-etapa1.pdf` (propuesta al cliente) y esta guía. | Lectura. |
| `wordpress/` | La versión como plugin de WordPress. | Ver sección 8. |
| `storage/` | Registros y datos de trabajo (cambios del asistente, claves de avisos). No se sube. | Se crea sola. |

---

## 2. Usar el sitio en una PC (Windows)

1. Hacer una sola vez la instalación del `README.md` (base de datos, `config.php`, catálogo y primer usuario).
2. Doble clic en **`LOCAL.bat`**: prende la base de datos y el servidor, y abre el sitio.
   - Sitio: http://localhost:8080
   - Panel: http://localhost:8080/panel
   - Para apagarlo, cerrar las dos ventanas que se abren.
3. **Rutas de las herramientas:** `LOCAL.bat` busca PHP y MariaDB en `G:\Herramientas\...`. Si en tu PC están en otra unidad (por ejemplo `C:\Herramientas`), cambiar esas rutas dentro del archivo.

Sin `LOCAL.bat`, a mano: prender la base de datos y, desde la carpeta del proyecto, `php -S localhost:8080 -t public public/index.php`.

Si el sitio muestra «La base de datos está apagada», prender primero la base de datos (es lo que hace `LOCAL.bat`).

---

## 3. El panel (`/panel`)

Se entra con email y contraseña. Después de 5 intentos fallidos la cuenta se bloquea un rato. Hay dos roles: **Administrador** (todo, incluidos usuarios y configuración) y **Editor** (propiedades y fotos).

| Quiero... | Dónde | Cómo |
|---|---|---|
| Cargar una propiedad | Propiedades > Nueva | Completar operación, tipo, zona, dirección, precio, características y descripción. El código público se asigna solo. |
| Editar una propiedad | Entrar a la propiedad desde la lista | Cambiar y guardar. |
| Subir fotos | Dentro de la propiedad | Se convierten solas a dos tamaños. La primera es la portada: el orden se cambia desde la galería de la propiedad. |
| Marcar como apta crédito | Dentro de la propiedad, solo ventas | Tildar «Apto crédito» (escritura, plano de mensura y plano de arquitectura). En alquiler no se guarda. |
| Cambiar el estado | Botón «Cambiar estado» | Se hace en dos pasos y queda en el historial. |
| Destacar en la portada | Botón de la lista | Las destacadas salen primero. |
| Sacar una propiedad | Baja | Es una baja lógica: no se borra. |
| Copiar el texto para Instagram | Dentro de la propiedad | Texto listo con el formato de la cuenta, para pegar. |
| Cambiar teléfonos, horario, secciones | Configuración (Administrador) | Ver sección 6. |
| Crear o desactivar usuarios | Usuarios (Administrador) | Cada uno con su rol. |
| Ver y responder chats del asistente | Chats | Muestra los chats de WhatsApp del asistente. Hay modo claro, oscuro o del sistema, y avisos al teléfono. Necesita la conexión con el asistente (sección 6). |

### Estados de una propiedad

| Estado | ¿Se ve en la web? | Notas |
|---|---|---|
| Disponible | Sí | |
| Reservado | Sí, con etiqueta | |
| Pausado | No | |
| Alquilado | No | Solo para alquileres. |
| Vendido | No | Solo para ventas. Solo un Administrador puede volverlo a Disponible. |

Una ficha que ya no está publicada sigue respondiendo (por los enlaces viejos de Instagram o WhatsApp) y avisa que no está disponible.

---

## 4. Base de datos y migraciones

Instalación desde cero: importar `schema.sql` y después las migraciones, **en orden**.

| Archivo | Qué hace |
|---|---|
| `schema.sql` | Usuarios, propiedades, fotos, historial de estados, tipos, zonas, servicios y configuración de partida. |
| `002_boceto_y_mapa.sql` | Páginas propias, preguntas frecuentes, «¿Por qué elegirnos?», puntos del mapa e interruptores de secciones. |
| `003_ajustes_auditoria.sql` | Ajustes de la auditoría del 30/09/2026. |
| `004_contacto.sql` | Página de Contacto propia. |
| `005_motivos_datos.sql` | Un dato grande por cada motivo de «¿Por qué elegirnos?». |
| `006_horario_estructurado.sql` | Días y turnos de atención, para el indicador «Abierto ahora / Cerrado». |
| `007_faq_grupos.sql` | Preguntas frecuentes agrupadas por tema. |
| `008_condiciones_alquiler.sql` | Condiciones del alquiler que se ven en cada ficha de alquiler. |
| `009_apto_credito.sql` | Campo «apto crédito» de las propiedades en venta. |

**Actualizar una base que ya existe:** aplicar solo las migraciones que falten, en orden. En el hosting se aplican a mano (por ejemplo desde phpMyAdmin).

---

## 5. Scripts de consola (`scripts/`)

Se corren desde la raíz del proyecto, por ejemplo `php scripts/crear_usuario.php ...`.

| Script | Para qué | Cuándo |
|---|---|---|
| `crear_usuario.php` | Crea un usuario del panel. La contraseña se genera sola. Parámetros: `--nombre`, `--email`, `--rol=administrador\|editor`, `--guardar-en=archivo`. | Para el primer usuario. |
| `migrar_catalogo.php` | Carga el catálogo de la web vieja. `--fotos` descarga y convierte las fotos; `--solo=257,258` migra solo esos códigos. | **Una sola vez.** Si se repite, pisa lo editado en el panel. |
| `importar_fotos_instagram.php` | Reemplaza las fotos de la web vieja por las placas de Instagram. `--descargar` las baja y arma una hoja numerada; `--aplicar="258:1-5"` indica cuáles van a cada propiedad. | Después de migrar el catálogo. |
| `renombrar_variantes_fotos.php` | Pasa fotos subidas con el nombre viejo (`-1600` y `-480`) al nuevo (`-grande` y `-chica`). | Una vez, si había fotos con el nombre viejo. |
| `datos/` | Correcciones de edición y de Instagram, y la lista de placas, que usan los scripts anteriores. | No se usa directo. |

---

## 6. Configuración

### `config/config.php` (archivo local, no se sube)

| Clave | Qué es |
|---|---|
| `db` | Datos de la base: servidor, puerto, nombre, usuario y clave. |
| `app.url_base` | Dirección del sitio (`http://localhost:8080` en la PC; la real en el hosting). Se usa en enlaces para compartir. |
| `app.entorno` | `desarrollo` muestra los errores; `produccion` los oculta. |
| `asistente.url` y `asistente.clave` | Conexión con el asistente de WhatsApp. La clave es la misma que tiene el asistente y solo la usa el servidor. Sin esto, la pantalla Chats avisa que falta conectar. |

### Panel > Configuración (se guarda en la base)

- **Contacto:** WhatsApp (número y cómo se muestra), teléfono, email, dirección, ubicación de la oficina, matrículas e Instagram.
- **Horario:** el texto que se ve, y los días y turnos para el indicador Abierto/Cerrado.
- **Portada:** el título principal.
- **Alquileres:** las condiciones para inquilinos, una por línea. Se muestran en la ficha de cada alquiler.
- **Analítica:** el ID de Google Analytics 4. Vacío = sin medición.
- **Secciones visibles:** mapa, páginas Servicios y Nosotros, «Alquilá / Vendé con nosotros» y preguntas frecuentes. Se prenden y apagan según la etapa.

---

## 7. Publicar en el hosting

1. El contenido de `public/` va en la raíz del sitio (`public_html`).
2. `app/`, `config/`, `database/` y `scripts/` van un nivel más arriba, fuera del alcance de la web.
3. Crear la base en el panel del hosting, importar `schema.sql` y todas las migraciones en orden.
4. Crear `config/config.php` con los datos de la base del hosting, la dirección real en `app.url_base` y `'entorno' => 'produccion'`.
5. Dar permiso de escritura a `public/uploads/` (ahí se guardan las fotos).
6. Subir las fotos de las propiedades y, si hace falta, correr `scripts/renombrar_variantes_fotos.php`.
7. Crear el primer usuario con `scripts/crear_usuario.php` (o pedirlo al soporte del hosting si no hay consola).
8. Probar `/`, `/alquileres`, una ficha, `/contacto` y `/panel`.

`public/.htaccess` ya redirige a HTTPS, unifica el dominio (sin guion y sin `www`), no deja ejecutar PHP en `uploads/` y activa la caché de fuentes, estilos y scripts.

---

## 8. Versión WordPress (`wordpress/`)

Es el mismo sitio como **plugin**. Las direcciones del sitio las atiende el plugin con las mismas vistas; el resto es WordPress normal.

| Qué | Contiene | Cómo se usa |
|---|---|---|
| `wordpress/santa-fe-propiedades/` | El plugin completo. | Copiar a `wp-content/plugins/` y activar. Enlaces permanentes en «Nombre de la entrada». |
| `wordpress/exportar-datos.php` | Vuelca la base de la web PHP a un archivo JSON. | `php wordpress/exportar-datos.php datos.json` desde la raíz del proyecto. |
| `wordpress/santa-fe-propiedades/importar.php` | Importa ese JSON y las fotos a WordPress. | Desde la carpeta de WordPress: `php wp-content/plugins/santa-fe-propiedades/importar.php datos.json ruta/a/public/uploads/propiedades`. Se puede repetir sin duplicar. |
| `wordpress/README.md` | Detalle técnico y lo que todavía falta. | Lectura. |

**Usarlo desde el escritorio de WordPress** (menú **Propiedades**):

- **Propiedades:** editar con todos los campos, elegir tipo y zona, y armar la galería (la primera foto es la portada; se reordenan arrastrando).
- **Servicios, Motivos para elegirnos, Preguntas frecuentes y Puntos del mapa:** cada uno con su lista.
- **Tipos de propiedad y Zonas:** el tipo tiene la opción «Es comercial» para que aparezca en Comerciales.
- **Configuración del sitio:** lo mismo que Panel > Configuración de la web PHP.

**Diferencias con la web PHP:** WordPress todavía no tiene el historial de estados, el rol Editor limitado, los chats del asistente ni los avisos push. Además, las vistas y estilos del plugin son una copia: si se cambian en la web PHP, hay que copiarlos de nuevo.

En la PC de Benja hay un WordPress de prueba en `Desktop\Claude Cowork\wordpress-cliente` (fuera del repositorio): se prende con `INICIAR WORDPRESS.bat` y queda en http://localhost:8081 (escritorio en `/wp-admin`). Sus usuarios y claves están en `CREDENCIALES-LOCAL.txt`, que no se sube.

---

## 9. Cambiar el logo

1. Exportar el logo con el lienzo recortado al dibujo (en Photoshop: *Imagen > Recortar > Píxeles transparentes*). Si el archivo tiene margen transparente, la imagen se ve chica o ensancha la barra.
2. Reemplazar `public/assets/img/logo.png` (a color) y `logo-blanco.png` (letras blancas, para el pie). En el plugin de WordPress, los mismos archivos en `wordpress/santa-fe-propiedades/assets/img/`.
3. El alto lo define `public/assets/css/app.css` (`.logo__img`): 36 px en celular, 40 px desde 60 em y 34 px en el pie. El ancho sale solo.

Los logos actuales salen de capturas de pantalla del sitio publicado (95 px de alto). Conviene reemplazarlos por el original en SVG o en PNG de mayor resolución.

---

## 10. Subir cambios a GitHub

- Repositorio: `bnjmnberta/santa-fe-propiedades-web`, rama `main`.
- Los mensajes de commit van en español y detallan qué se cambió, archivo por archivo.
- **Antes de subir:** `git pull --rebase`, para traer lo que subió otra persona.
- **Mail de los commits:** el repositorio rechaza commits hechos con un mail privado («push declined due to email privacy restrictions»). Hay que usar el mail `noreply` de GitHub de la cuenta.
- Nunca se suben `config/config.php`, `public/uploads/`, `storage/` ni claves.

---

## 11. Problemas frecuentes

| Síntoma | Causa y solución |
|---|---|
| «La base de datos está apagada» | Prender MariaDB o MySQL (`LOCAL.bat` lo hace). |
| `LOCAL.bat` no encuentra PHP | Cambiar las rutas `G:\Herramientas\...` por las de tu PC. |
| Puerto 8080 ocupado | Cerrar el otro servidor o usar otro puerto (`php -S localhost:8081 ...`) y ajustar `app.url_base`. |
| Las fotos no se ven | Revisar `public/uploads/` y el permiso de escritura; correr `renombrar_variantes_fotos.php` si tienen el nombre viejo. |
| Chats dice «falta conectar» | Completar `asistente.url` y `asistente.clave` en `config.php`. |
| Los enlaces para compartir apuntan a `localhost` | Poner la dirección real en `app.url_base`. |
| GitHub rechaza el push por «email privacy» | Hacer los commits con el mail `noreply` de la cuenta. |
| En WordPress, las páginas dan 404 | Activar el plugin y fijar los enlaces permanentes en «Nombre de la entrada». |
