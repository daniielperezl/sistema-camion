# Guía de Actualización

## Actualización 4: Ruta del Día, Edición y Búsqueda

Esta actualización es mayor e incluye nuevas funcionalidades:
1.  **Ruta del Día**: Una nueva pantalla principal que muestra automáticamente la ruta de hoy (según el día de la semana) con opciones para navegar (Waze/Maps).
2.  **Edición y Búsqueda**: Ahora es posible buscar comercios por nombre o dueño en ambas pantallas. También se puede editar la información y el orden de los comercios existentes.
3.  **Campo Dueño**: Se agregó un campo para el nombre del dueño.

### Archivos a reemplazar
Debes actualizar (reemplazar) los siguientes archivos en tu hosting:
1.  **dashboard.php**: Estructura nueva con la sección "Ruta del Día" y formularios actualizados.
2.  **js/app.js**: Lógica completa para la detección de fecha, búsqueda y edición.
3.  **api/routes.php**: Lógica backend para manejar actualizaciones (`UPDATE`) y el nuevo campo `owner`.

### Base de Datos (phpMyAdmin)
Es necesario agregar una nueva columna llamada `owner` a la tabla `stops`. Ejecuta el siguiente comando SQL:

```sql
ALTER TABLE stops ADD COLUMN owner VARCHAR(255) AFTER name;
```

---

## Historial de Actualizaciones Anteriores

### Actualización 3: Mapa Interactivo
*   **Archivos:** `dashboard.php`, `js/app.js`

### Actualización 2: Corrección de Cuentas (Signos)
*   **Archivos:** `api/accounts.php`, `js/app.js`
*   **SQL:** `UPDATE daily_accounts SET total_descuadre = -total_descuadre;`

### Actualización 1: Ciudades
*   **Archivos:** `dashboard.php`, `js/app.js`, `api/routes.php`
*   **SQL:** `ALTER TABLE stops ADD COLUMN city VARCHAR(100) AFTER route_id;`
