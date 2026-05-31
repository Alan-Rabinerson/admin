# Administración (esqueleto / notas)

Este archivo describe el trabajo que se desarrollará posteriormente en PHP para el panel de administración (CMS).

- Estructura prevista:
  - `admin/index.php` -> entrada del panel con login.
  - `admin/login.php` -> formulario y autenticación (sessions).
  - `admin/dashboard.php` -> panel con listado de contenidos, usuarios y ajustes.
  - `admin/posts/` -> CRUD de entradas (create, edit, delete).
  - `admin/includes/` -> helpers, conexión a base de datos (mysqli o PDO), funciones de seguridad.

- Base de datos:
  - MySQL con tablas: `users`, `posts`, `settings`.
  - Preparar scripts SQL en `admin/sql/` para migración.

- Seguridad y funcionalidades:
  - Uso de `password_hash` / `password_verify`.
  - Protección CSRF en formularios administrativos.
  - Validación y sanitización del input.
  - Paginación y búsqueda en dashboard.

- API de conexión con React:
  - Rutas REST (opcional) para consumir contenido desde la SPA (`/api/posts.php` etc.).
  - Autenticación basada en sesiones para el panel; tokens si se escoge API separada.

Comentarios en el front-end React indican dónde conectar las llamadas (ver `src` y `src/pages/Home.jsx`).

Instrucciones rápidas para MariaDB y PHP:

- Crear la base de datos y las tablas (desde la carpeta `admin/sql`):

  ```sql
  mysql -u root -p < sql/create_tables.sql
  ```

- Configura `config.php` con las credenciales de MariaDB. Por defecto usa `proyecto_db`.
- El panel usa `PDO` y sesiones PHP. Ajusta `BASE_URL` en `config.php` si hace falta.
- Para generar una contraseña segura para el usuario `admin`, usa PHP:

  ```php
  <?php
  echo password_hash('TuContraseñaSegura', PASSWORD_DEFAULT);
  ```

  Copia el hash resultante en una inserción SQL para `users`.

Siguientes pasos posibles:
- Implementar CRUD de `posts` en `admin/posts/`.
- Añadir CSRF tokens y validación robusta en formularios.
- Crear endpoints REST en `admin/api/` si la SPA lo requiere.
