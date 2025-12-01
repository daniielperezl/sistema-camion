# Instrucciones de Despliegue en cPanel

Este sistema está diseñado para funcionar en un hosting compartido con cPanel, PHP y MySQL.

## Requisitos
*   PHP 7.4 o superior
*   Base de datos MySQL
*   Acceso a cPanel

## Pasos para el Despliegue

### 1. Preparar los Archivos
1.  Comprime todos los archivos del proyecto en un archivo `.zip` (excluyendo la carpeta `.git` si existe).
    *   Asegúrate de incluir las carpetas `api`, `css`, `js`, `database` y los archivos PHP (`index.php`, `dashboard.php`, etc.).

### 2. Subir Archivos al Hosting
1.  Ingresa a tu cPanel.
2.  Ve al **Administrador de Archivos** (File Manager).
3.  Navega a la carpeta `public_html` (o la subcarpeta donde quieras instalar el sistema).
4.  Haz clic en **Cargar** (Upload) y sube el archivo `.zip`.
5.  Una vez subido, haz clic derecho sobre el archivo y selecciona **Extraer** (Extract).

### 3. Crear la Base de Datos
1.  En cPanel, ve a **Bases de Datos MySQL** (MySQL Databases).
2.  Crea una nueva base de datos (ej: `usuario_truckdb`).
3.  Crea un nuevo usuario de base de datos y asígnale una contraseña segura.
4.  **Importante:** Añade el usuario a la base de datos y dale **TODOS LOS PRIVILEGIOS**.

### 4. Importar la Estructura de la Base de Datos
1.  En cPanel, ve a **phpMyAdmin**.
2.  Selecciona la base de datos que acabas de crear en el panel izquierdo.
3.  Ve a la pestaña **Importar**.
4.  Haz clic en "Seleccionar archivo" y busca el archivo `database/schema.sql` que se encuentra en los archivos que subiste.
5.  Haz clic en **Continuar** para ejecutar el script. Esto creará las tablas y los usuarios por defecto.

### 5. Configurar la Conexión
1.  Regresa al **Administrador de Archivos**.
2.  Busca el archivo `db_connect.php` y edítalo.
3.  Actualiza las siguientes líneas con los datos de tu base de datos:

```php
$dbname = 'usuario_truckdb'; // El nombre de la base de datos que creaste
$user = 'usuario_dbuser';    // El usuario de la base de datos
$pass = 'tu_contraseña';     // La contraseña del usuario
```
4.  Guarda los cambios.

### 6. Verificar
1.  Abre tu navegador e ingresa a tu dominio (ej: `www.tudominio.com`).
2.  Deberías ver la pantalla de inicio de sesión.
3.  Ingresa con las credenciales por defecto:
    *   Usuario: `3213907836` / Contraseña: `74181532`
    *   Usuario: `3114128612` / Contraseña: `1057606502`

## Notas Adicionales
*   **Seguridad:** Se recomienda cambiar las contraseñas o implementar un sistema de cambio de contraseña en el futuro.
*   **Mapas:** El sistema usa OpenStreetMap (gratuito). Si deseas usar Google Maps, necesitarás editar `js/app.js` e incluir tu API Key.
