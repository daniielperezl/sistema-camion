# Guía de Actualización

## Actualización 3: Mapa Interactivo y Marcadores Numerados

Se ha mejorado el mapa para permitir hacer clic y seleccionar la ubicación exacta de un nuevo comercio, y ahora los marcadores muestran el número de orden de la ruta.

### Archivos a reemplazar
Debes actualizar (reemplazar) los siguientes archivos en tu hosting:
1.  **dashboard.php**: Incluye los estilos para los marcadores numerados y actualiza el formulario.
2.  **js/app.js**: Incluye la lógica para detectar el clic en el mapa y renderizar los marcadores con números.

### Base de Datos
No se requieren cambios en la base de datos para esta actualización.

---

## Historial de Actualizaciones Anteriores

### Actualización 2: Corrección de Cuentas (Signos)
*   **Archivos:** `api/accounts.php`, `js/app.js`
*   **SQL (Solo una vez):** `UPDATE daily_accounts SET total_descuadre = -total_descuadre;`

### Actualización 1: Ciudades
*   **Archivos:** `dashboard.php`, `js/app.js`, `api/routes.php`
*   **SQL:** `ALTER TABLE stops ADD COLUMN city VARCHAR(100) AFTER route_id;`
