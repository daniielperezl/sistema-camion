# Proyecto Android - Truck Delivery App

Este directorio contiene el código fuente completo para la aplicación móvil Android.

## Requisitos Previos
*   **Android Studio** (versión reciente, ej: Iguana o Jellyfish).
*   **JDK 17** (usualmente incluido con Android Studio).

## Instrucciones para Compilar y Generar APK

1.  **Abrir el Proyecto:**
    *   Abre Android Studio.
    *   Selecciona **Open** (Abrir).
    *   Navega a la carpeta `android_app` dentro de este repositorio y selecciona el archivo `settings.gradle` o la carpeta raíz `android_app`.
    *   Espera a que Gradle sincronice el proyecto (puede tardar unos minutos descargando dependencias).

2.  **Configurar la API:**
    *   Abre el archivo: `app/src/main/java/com/shadowgroup/truckdelivery/ApiClient.kt`.
    *   Busca la línea: `private const val BASE_URL = "http://10.0.2.2:8000/api/"`.
    *   **IMPORTANTE:** Cambia `http://10.0.2.2:8000/api/` por la URL real de tu servidor donde subiste el sistema web.
        *   Ejemplo: `http://tudominio.com/api/` (Asegúrate de que termine en `/`).
    *   Si usas un servidor local y pruebas en un dispositivo físico, usa la IP de tu PC (ej: `http://192.168.1.50/truck_delivery/api/`).

3.  **Ejecutar en Emulador o Dispositivo:**
    *   Conecta tu celular Android por USB (activa depuración USB) o crea un emulador en Android Studio.
    *   Haz clic en el botón **Run** (Triángulo verde) en la barra superior.

4.  **Generar APK (Para instalar manualmente):**
    *   Ve al menú **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**.
    *   Una vez termine, aparecerá una notificación. Haz clic en **locate** para encontrar el archivo `.apk` (usualmente en `app/build/outputs/apk/debug/app-debug.apk`).
    *   Copia este archivo a los celulares de los conductores e instálalo.

## Características de la App
*   **Login:** Usa las mismas credenciales que la web.
*   **Ruta del Día:** Detecta automáticamente el día y carga la ruta.
*   **Voz (TTS):** Saluda y lee las ciudades de la ruta al iniciar.
*   **Mapa:** Muestra la ubicación actual y los pines de los comercios (OpenStreetMap).
*   **Lista:** Muestra los comercios con su orden, dueño y distancia.
*   **Navegación:** Botones directos para abrir Waze o Google Maps con las coordenadas.
*   **Búsqueda:** Filtra la lista por nombre de comercio o dueño.

## Notas Técnicas
*   La app usa **Retrofit** para conectar con la API PHP.
*   Usa **OSMDroid** para los mapas (gratis, no requiere API Key de Google).
*   Requiere permiso de Ubicación para calcular distancias y mostrar la posición del conductor.
