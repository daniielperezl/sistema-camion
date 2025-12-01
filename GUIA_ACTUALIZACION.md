# Guía de Actualización

## Actualización 6: Soporte para App Android

Para que la aplicación móvil funcione correctamente (especialmente el inicio de sesión), es necesario actualizar el backend.

### Archivos a reemplazar
Debes actualizar (reemplazar) el siguiente archivo en tu hosting:
1.  **api/login.php**: Se modificó para devolver la información del usuario (ID y nombre) al iniciar sesión, lo cual es requerido por la App.

*Nota: Si ya realizaste la Actualización 5 (Diseño Responsive), asegúrate de tener también esos cambios en `dashboard.php` y `js/app.js`.*

### Base de Datos
No se requieren cambios en la base de datos para esta actualización.

---

## Historial de Actualizaciones Anteriores

### Actualización 5: Diseño Responsive (Móvil)
*   **Archivos:** `dashboard.php`, `js/app.js`

### Actualización 4: Ruta del Día y Edición
*   **Archivos:** `dashboard.php`, `js/app.js`, `api/routes.php`
*   **SQL:** `ALTER TABLE stops ADD COLUMN owner VARCHAR(255) AFTER name;`

### Actualización 3: Mapa Interactivo
*   **Archivos:** `dashboard.php`, `js/app.js`

### Actualización 2: Corrección de Cuentas (Signos)
*   **Archivos:** `api/accounts.php`, `js/app.js`
*   **SQL:** `UPDATE daily_accounts SET total_descuadre = -total_descuadre;`

### Actualización 1: Ciudades
*   **Archivos:** `dashboard.php`, `js/app.js`, `api/routes.php`
*   **SQL:** `ALTER TABLE stops ADD COLUMN city VARCHAR(100) AFTER route_id;`
