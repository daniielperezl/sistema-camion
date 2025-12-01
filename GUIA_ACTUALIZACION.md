# Guía de Actualización

## Actualización 5: Diseño Responsive (Móvil)

Se ha mejorado el diseño para adaptarse a dispositivos móviles (celulares y tablets).

### Funcionalidades
*   **Menú Lateral Ocultable:** En móviles, el menú se oculta por defecto y se puede abrir con un botón (☰).
*   **Overlay:** Se agrega un fondo oscuro cuando el menú está abierto; al hacer clic en él, se cierra el menú.
*   **Tablas y Formularios:** Se ajustan para que no se desborden en pantallas pequeñas.

### Archivos a reemplazar
Debes actualizar (reemplazar) los siguientes archivos en tu hosting:
1.  **dashboard.php**: Incluye el nuevo código HTML/CSS para el menú móvil.
2.  **js/app.js**: Incluye la lógica para abrir/cerrar el menú.

### Base de Datos
No se requieren cambios en la base de datos para esta actualización.

---

## Historial de Actualizaciones Anteriores

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
