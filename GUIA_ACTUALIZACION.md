# Guía de Actualización - Función de Ciudades

Para ver los cambios de la nueva función de selección de ciudades en tu sistema desplegado en cPanel, debes realizar dos pasos principales: actualizar los archivos y actualizar la base de datos.

## Paso 1: Actualizar Archivos
Debes reemplazar los siguientes archivos en tu `File Manager` (Administrador de Archivos) de cPanel, dentro de la carpeta `public_html` (o donde tengas instalado el sistema):

1.  **dashboard.php**: Este archivo contiene el nuevo formulario con el selector de ciudades.
2.  **js/app.js**: Este archivo contiene la lógica para mostrar la ciudad en la tabla y en el mapa.
3.  **api/routes.php**: Este archivo contiene la lógica para guardar la ciudad en la base de datos.

*Nota: Si subes todo el proyecto nuevamente y sobrescribes los archivos, también funcionará.*

## Paso 2: Actualizar la Base de Datos
Es necesario agregar una nueva columna llamada `city` a la tabla `stops` en tu base de datos.

1.  Ingresa a tu **cPanel**.
2.  Ve a **phpMyAdmin**.
3.  Selecciona tu base de datos en el menú izquierdo (ej: `usuario_truckdb`).
4.  Haz clic en la pestaña **SQL** en la parte superior.
5.  Copia y pega el siguiente comando en el cuadro de texto:

```sql
ALTER TABLE stops ADD COLUMN city VARCHAR(100) AFTER route_id;
```

6.  Haz clic en el botón **Continuar** (Go).

## Verificación
Una vez realizados estos pasos:
1.  Recarga tu página web (puedes necesitar borrar la caché del navegador con Ctrl+F5).
2.  Ve a "Organización de Rutas".
3.  Al hacer clic en "Agregar Nuevo Comercio", deberías ver el selector de ciudades (Sogamoso, Duitama, etc.).
