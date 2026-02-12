# Configuración de RAWG API para Auto-Completado

## ¿Qué es RAWG API?
RAWG es una base de datos gratuita de videojuegos que permite obtener automáticamente información como:
- Descripción del juego
- Géneros
- Plataformas/Consolas
- Imágenes
- Fecha de lanzamiento
- Calificaciones

## Pasos para obtener tu API Key GRATUITA

### 1. Crear cuenta en RAWG
Visita: **https://rawg.io/login?forward=developer**

### 2. Registrarte
- Puedes usar tu email o cuenta de Google/Steam
- Es completamente **GRATUITO**
- No requiere tarjeta de crédito

### 3. Ir al Developer Panel
- Una vez logueado, ve a: https://rawg.io/apidocs
- Haz clic en **"Get API Key"**

### 4. Obtener tu API Key
- Se generará una key similar a: `1234567890abcdefghijklmnopqrstuv`
- Copia esta key

### 5. Configurar en el sistema
Abre el archivo: `admin/ajax/autocomplete_game_info.php`

Encuentra la línea 13 que dice:
```php
$apiKey = ''; // Agregar tu API key aquí
```

Y reemplázala con tu key:
```php
$apiKey = '1234567890abcdefghijklmnopqrstuv'; // Tu API key
```

### 6. ¡Listo!
Guarda el archivo y recarga la página. El botón "Auto-Rellenar Información del Juego" ahora funcionará.

## Límites de la API Gratuita
- **20,000 requests por mes** (más que suficiente)
- Sin costo
- Sin necesidad de actualizar

## Soporte
Si tienes problemas, revisa:
- https://rawg.io/apidocs
- Documentación oficial de RAWG

---
**Nota:** Esta API es opcional. Puedes seguir subiendo productos manualmente sin ella.
