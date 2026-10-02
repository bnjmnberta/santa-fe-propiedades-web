# Santa Fe Propiedades · Web

Web nueva de la inmobiliaria **Santa Fe Propiedades** (Santa Fe Capital), hecha por Tars. Reemplaza a santafe-propiedades.com.ar.

## Qué incluye

- **Sitio público:**
  - portada;
  - catálogo con búsqueda y filtros;
  - ficha de cada propiedad con galería, botón de WhatsApp y una sección "Cerca de";
  - mapa con las propiedades y los puntos estratégicos de la ciudad (facultades, terminal, puerto y costanera);
  - páginas Servicios, Nosotros, Alquilá/Vendé con nosotros y Preguntas frecuentes.
- **Panel de autogestión** (`/panel`):
  - alta y edición de propiedades;
  - fotos: subida, orden y portada;
  - cambio de estado en dos pasos, con historial;
  - destacadas, usuarios y configuración;
  - texto listo para Instagram, con el formato de la cuenta.
- **Secciones por etapas:** el mapa, las preguntas frecuentes, las páginas propias y "Alquilá/Vendé con nosotros" se prenden y apagan desde *Panel > Configuración*.
- **Redirecciones 301** desde las direcciones de la web vieja (`descripcion.php?id=N`, `resultados.php`, etc.), para no perder el posicionamiento en Google.

El análisis completo está en [`docs/ANALISIS.md`](docs/ANALISIS.md): requisitos, casos de uso, reglas de negocio, diagramas y modelo de datos.

## Tecnología

- **Servidor:** PHP 8.1 o superior, sin framework: MVC propio con PDO y consultas preparadas.
- **Base de datos:** MySQL 8.0.16 o superior, o MariaDB 10.6 o superior.
- **Hosting:** pensada para hosting compartido (Hostinger, DonWeb).
- **Navegador:** JavaScript y CSS propios, sin dependencias de compilación.
- **Mapa:** MapLibre GL con el mapa de OpenFreeMap, que no pide clave de API.
- **Tipografía:** Open Sauce Sans.

## Estructura

```
app/         controladores, modelos, repositorios, servicios y vistas
config/      config.example.php (se copia a config.php, que no se sube al repositorio)
database/    schema.sql y migraciones/
docs/        análisis y propuesta
public/      raíz web: index.php, .htaccess y assets/; uploads/ guarda las fotos (no se suben)
scripts/     migración del catálogo, fotos de Instagram y alta de usuarios
```

## Cómo levantarla en una PC

1. Instalar PHP con las extensiones `pdo_mysql`, `gd` (con WebP), `intl`, `mbstring`, `fileinfo` y `exif`.
2. Crear la base de datos e importar el esquema y las migraciones, en ese orden:
   ```bash
   mysql -u root -p -e "CREATE DATABASE sfp_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p sfp_web < database/schema.sql
   mysql -u root -p sfp_web < database/migraciones/002_boceto_y_mapa.sql
   mysql -u root -p sfp_web < database/migraciones/003_ajustes_auditoria.sql
   mysql -u root -p sfp_web < database/migraciones/004_contacto.sql
   mysql -u root -p sfp_web < database/migraciones/005_motivos_datos.sql
   mysql -u root -p sfp_web < database/migraciones/006_horario_estructurado.sql
   ```
3. Copiar `config/config.example.php` como `config/config.php` y completar los datos de la base.
4. Cargar el catálogo desde la web vieja, con las fotos:
   ```bash
   php scripts/migrar_catalogo.php --fotos
   ```
   Esta carga se hace **una sola vez**: si se vuelve a correr, pisa lo que se haya editado en el panel.
   Después, reemplazar las fotos de la web vieja (800 px) por las placas de Instagram (1080 px) donde las hay:
   ```bash
   php scripts/importar_fotos_instagram.php --descargar
   php scripts/importar_fotos_instagram.php --aplicar="78:3,1,2,4 252:1-9 254:5,1,2,3,4,6 257:2,1,3,4,5,6 258:2,1,3,4,5"
   ```
   Si ya había fotos subidas con el nombre viejo (`-1600.webp` / `-480.webp`), pasarlas al nuevo una vez:
   ```bash
   php scripts/renombrar_variantes_fotos.php
   ```
5. Crear el primer usuario del panel. La contraseña se genera sola:
   ```bash
   php scripts/crear_usuario.php --nombre="Nombre" --email=correo@ejemplo.com --rol=administrador
   ```
6. Levantar el servidor y abrir http://localhost:8080. El panel está en `/panel`.
   ```bash
   php -S localhost:8080 -t public public/index.php
   ```

## Publicar en el hosting

- El contenido de `public/` va en la raíz del sitio (`public_html`).
- `app/`, `config/`, `database/` y `scripts/` van un nivel más arriba, fuera del alcance de la web.
- En `config/config.php`, poner `'entorno' => 'produccion'` para que no se muestren los errores.
- `public/uploads/` tiene que tener permiso de escritura: ahí el panel guarda las fotos.
- `public/.htaccess` ya se encarga de tres cosas:
  - redirige a HTTPS;
  - lleva todo al dominio sin guion y sin `www`;
  - impide ejecutar PHP dentro de `uploads/`.
