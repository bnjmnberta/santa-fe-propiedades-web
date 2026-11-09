# Santa Fe Propiedades en WordPress

Versión del sitio como plugin de WordPress. Tiene el mismo diseño, las mismas páginas y las mismas reglas que la web PHP de este repositorio, pero las propiedades, servicios, preguntas frecuentes y puntos del mapa se administran desde el escritorio de WordPress.

## Qué hay

- `santa-fe-propiedades/`: el plugin. Se copia a `wp-content/plugins/` y se activa.
- `exportar-datos.php`: vuelca la base de la web PHP a un JSON.
- `santa-fe-propiedades/importar.php`: importa ese JSON y las fotos a WordPress.

## Cómo funciona

- Las direcciones del sitio (`/`, `/alquileres`, `/ventas`, `/comerciales`, `/propiedad/258-...`, `/servicios`, `/nosotros`, `/contacto`...) las atiende el plugin con las mismas vistas que la web PHP. El resto (escritorio, accesos, API de WordPress) sigue su curso normal.
- Propiedades, servicios, motivos, preguntas y puntos del mapa son tipos de contenido propios. Tipo de propiedad y zona son taxonomías. Cada campo de la ficha se guarda como dato `sfp_<columna>`.
- **Propiedades > Configuración del sitio** reemplaza a Configuración del panel original: WhatsApp, teléfonos, horario, condiciones del alquiler, Google Analytics y las secciones que se prenden y apagan.
- Las fotos van a la biblioteca de medios; la primera de la galería es la portada. Hay un tamaño propio de 480 px (`sfp-chica`) para tarjetas y mapa.

## Instalación en un WordPress local

Requisitos: WordPress 6.5 o superior, PHP 8.1 o superior con `mysqli`, `gd` con WebP y `mbstring`.

1. Copiar `santa-fe-propiedades/` a `wp-content/plugins/` y activarlo. Con enlaces permanentes en «Nombre de la entrada».
2. Desde la raíz de este repositorio, con la web PHP andando y su base cargada:

   ```
   php wordpress/exportar-datos.php datos.json
   ```

3. Desde la carpeta de WordPress, importar los datos y las fotos de `public/uploads/propiedades`:

   ```
   php wp-content/plugins/santa-fe-propiedades/importar.php datos.json ruta/a/public/uploads/propiedades
   ```

   Se puede volver a correr sin duplicar contenido.

## Qué no incluye todavía

- Historial de cambios de estado de cada propiedad (el panel original lo tenía).
- Usuarios con rol Editor limitado a propiedades: hoy se usan los roles de WordPress.
- Chats con el asistente de WhatsApp y avisos push, que están en la web PHP.
- Las vistas y los estilos son una copia de `app/Vistas` y `public/assets`: si cambian en la web PHP hay que volver a copiarlos.
