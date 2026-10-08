# Películas PHP + MariaDB

Aplicación educativa para practicar el despliegue tradicional de una aplicación web. Permite listar, buscar, consultar, crear, editar y eliminar películas. Está desarrollada con PHP, PDO, MariaDB y plantillas HTML, sin framework ni Composer.

Ejecuta todos los comandos de este documento desde la raíz de la aplicación.

## Requisitos

- PHP 8.4 o posterior con la extensión `pdo_mysql` y soporte de sesiones.
- MariaDB con InnoDB y `utf8mb4`.
- Cliente `mariadb` o un panel que permita importar archivos SQL.

## Preparación de MariaDB

Crea una base y un usuario local. Sustituye la contraseña de ejemplo:

```sql
CREATE DATABASE peliculas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'peliculas'@'127.0.0.1' IDENTIFIED BY 'SUSTITUIR_POR_UNA_CLAVE_LOCAL';
GRANT SELECT, INSERT, UPDATE, DELETE ON peliculas.* TO 'peliculas'@'127.0.0.1';
```

Importa el esquema con una cuenta que pueda crear tablas y carga una sola vez los doce clásicos de aventuras, fantasía y ciencia ficción:

```bash
mariadb -u root -p peliculas < base-datos/esquema.sql
mariadb -h 127.0.0.1 -u peliculas -p peliculas < base-datos/datos-iniciales.sql
```

La carga inicial no elimina datos existentes. No la repitas sobre una base poblada, porque duplicaría las películas. Algunas instalaciones administran MariaDB mediante `sudo mariadb` o el panel del alojamiento; en ese caso, importa desde allí los dos archivos SQL en el mismo orden.

## Configuración

`.env.example` enumera las variables necesarias, pero PHP no carga archivos `.env` automáticamente. El código consume `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`, todas obligatorias. No existen credenciales implícitas ni se admite `DATABASE_URL`.

`APP_PORT` se utiliza en el comando de desarrollo. `APP_ENV` queda disponible para el entorno, pero no altera el comportamiento actual. En Bash:

```bash
export APP_ENV=desarrollo
export APP_PORT=8000
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=peliculas
export DB_USER=peliculas
read -rsp 'Contraseña de MariaDB: ' DB_PASSWORD
export DB_PASSWORD
```

No guardes contraseñas reales ni archivos `.env` en el repositorio.

## Ejecución local

Inicia el servidor de desarrollo incorporado en PHP:

```bash
php -S "127.0.0.1:${APP_PORT}" -t publico
```

Abre `http://127.0.0.1:8000/index.php?ruta=/peliculas`. La aplicación recibe la ruta lógica mediante el parámetro `ruta`; si se omite, `index.php` muestra el listado. El servidor incorporado es solo para desarrollo local y la aplicación se sirve en la raíz del dominio.

## Apache y hosting compartido

En Apache, configura `publico/` como raíz web y asegúrate de que el servidor ejecuta `index.php` con PHP. No se necesita reescritura de URLs: se puede abrir `index.php` explícitamente y la ruta viaja en la cadena de consulta. Proporciona las variables `DB_*` al proceso PHP y usa HTTPS en producción.

Si un hosting obliga a usar `public_html/`, coloca allí el contenido de `publico/` y sitúa `src/`, `configuracion/` y `vistas/` en el directorio padre para conservar las rutas relativas de `index.php`. No publiques los archivos SQL, las pruebas ni la configuración privada. El alojamiento debe admitir variables de entorno y sesiones PHP.

## Organización

- `publico/index.php`: punto de entrada web y comprobación de salud.
- `publico/css/`: estilos de la interfaz.
- `src/rutas.php`: rutas y operaciones de la aplicación.
- `src/peliculas.php` y `src/conexion.php`: consultas preparadas y conexión PDO.
- `src/validacion.php`: validación y normalización de campos.
- `vistas/`: páginas HTML y formularios sin JavaScript.
- `base-datos/`: esquema y datos iniciales.
- `pruebas/`: pruebas específicas sin dependencias externas.

## Sesiones, seguridad y salud

Los formularios usan sesiones PHP para los tokens CSRF y los mensajes de confirmación. El servidor debe conservar la sesión entre peticiones. Con varias instancias se necesitaría almacenamiento de sesiones compartido o afinidad de sesión; esta entrega no incorpora esas soluciones.

Las consultas usan parámetros preparados y las salidas HTML se escapan. Las operaciones correctas redirigen con `303`; los datos inválidos devuelven `422`, un CSRF inválido devuelve `403` y los recursos inexistentes, `404`. Los errores internos devuelven `500` con un mensaje genérico y registran el detalle en el servidor.

La validación utiliza PCRE con soporte Unicode, incluido en PHP, para comprobar UTF-8 y contar puntos de código. No requiere `mbstring`, no normaliza Unicode ni cuenta grafemas.

`GET /index.php?ruta=/salud` no inicia sesión. Ejecuta una consulta mínima y devuelve texto UTF-8 con `200` cuando conecta con MariaDB o `503` cuando falla, sin mostrar detalles técnicos.

No se incluye una licencia en esta entrega.
