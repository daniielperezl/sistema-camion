# Guía de Actualización

## Actualización 1: Función de Ciudades

### Archivos a reemplazar
1.  **dashboard.php**
2.  **js/app.js**
3.  **api/routes.php**

### Base de Datos (phpMyAdmin)
Ejecutar el siguiente comando SQL:
```sql
ALTER TABLE stops ADD COLUMN city VARCHAR(100) AFTER route_id;
```

---

## Actualización 2: Corrección de Cuentas y Descuadres (Importante)

Se ha corregido la lógica de cálculo para que el "Descuadre" refleje correctamente el balance:
*   **Positivo (+):** Se entregó más dinero del esperado (Verde).
*   **Negativo (-):** Se entregó menos dinero del esperado (Rojo).

### Archivos a reemplazar
1.  **api/accounts.php**: Corrección en el cálculo del lado del servidor.
2.  **js/app.js**: Corrección en el cálculo en tiempo real y en los colores/símbolos.

### Base de Datos (Corrección de datos existentes)
Dado que la lógica se invirtió, los datos guardados anteriormente mostrarán el signo opuesto. Para corregir los registros históricos en la base de datos, ejecuta este comando en **phpMyAdmin**:

```sql
UPDATE daily_accounts SET total_descuadre = -total_descuadre;
```
*Nota: Ejecuta esto SOLO UNA VEZ.*
