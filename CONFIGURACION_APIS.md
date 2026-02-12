# Configuración de API - KNOWN_DATABASE + RAWG (v1.1)

**Versión:** 1.1 (Búsqueda Combinada)
**Fecha:** Enero 2025
**Status:** ✅ ACTIVO - Búsqueda en AMBAS fuentes

## 📋 Resumen del Sistema

Tu sistema usa **BÚSQUEDA COMBINADA** de dos fuentes:

1. **KNOWN_DATABASE** (Base Local)
   - ~45 juegos con plataformas verificadas
   - Kingdom Hearts, Crash, Final Fantasy, God of War, Zelda, etc.
   - Prioridad: Se busca primero aquí

2. **RAWG API** (500,000+ juegos)
   - Base de datos completa
   - Información enriquecida (géneros, descripción, imágenes)
   - Fallback si no está en KNOWN_DATABASE

**✅ Características:**
- Búsqueda sin límite de resultados
- Ambas fuentes retornan todos los matches
- Atribución de fuente en cada resultado
- Plataformas verificadas desde KNOWN_DATABASE
```

## 🚀 Información de la API RAWG

**RAWG** es la base de datos más completa de videojuegos con información actualizada sobre juegos de todas las plataformas.

**Detalles de tu cuenta:**
- **Desarrollador:** Gabriel Bustos
- **Email:** Gbustosgarcia01@gmail.com
- **Sitio:** https://teal-fish-507993.hostingersite.com
- **API Key:** `575f338491134d84bd86df30627a95fe`
- **Perfil:** https://rawg.io/@gabrielbg21/apikey

**Estado de la API:**
- Plan: Free
- Requests restantes: 19,688
- Renovación: 1/3/2026
- Base de datos: 500,000+ juegos

## 🔧 Cómo Funciona (v1.1 - Búsqueda Combinada)

### 1. Usuario busca un juego
```
GET: admin/product_create.php
Usuario escribe: "Crash Bandicoot"
```

### 2. Sistema busca en AMBAS fuentes
```
KNOWN_DATABASE (local)
└─ ✅ "Crash Bandicoot 4 It's About Time" → Plataformas verificadas

RAWG API (500,000+ juegos)
└─ ✅ "Crash Team Racing"
└─ ✅ "Crash Bandicoot N Sane Trilogy"
└─ ✅ Y todas las versiones de Crash
```

### 3. Resultados combinados
```json
{
  "results": [
    {
      "name": "Crash Bandicoot 4 It's About Time",
      "source": "KNOWN_DATABASE" ✓ Verificado,
      "platforms": ["PS5", "PS4", "Xbox Series X", "Xbox One", "Switch", "PC"]
    },
    {
      "name": "Crash Team Racing",
      "source": "RAWG",
      "platforms": ["PlayStation", "Xbox", "Switch", "PC"]
    }
  ],
  "sources_detail": {
    "known_database": 1,
    "rawg": 5
  }
}
```

## 🛠️ Solución de Problemas

### Problema: "No aparecen resultados de KNOWN_DATABASE"
**Causa:** El juego no está en la base de datos local (~45 juegos)
**Solución:** El sistema automáticamente busca en RAWG como fallback

### Problema: "RAWG no responde"
**Causas:**
- Límite de requests excedido (20,000/día)
- Problema de conexión
- API temporalmente no disponible

**Solución:**
1. Verifica en https://rawg.io/@gabrielbg21/apikey cuántos requests quedan
2. Verifica la conexión a internet
3. Consulta https://rawg.io/status

### Problema: "Las plataformas no son correctas"
**Solución:**
- Si el juego está en KNOWN_DATABASE: plataformas son 100% correctas ✓
- Si viene de RAWG: pueden existir variaciones menores

## 📁 Archivos que Usan la API

### Archivos reescritos (Versión 5.0 - Sistema Limpio)

1. **search_game_multi.php**
   - Archivo: `admin/ajax/search_game_multi.php`
   - Función: Búsqueda principal de juegos
   - Cambios: ❌ Eliminado mock data y fallbacks
   - API Key: Línea 33

2. **search_game_rawg.php**
   - Archivo: `admin/ajax/search_game_rawg.php`
   - Función: Endpoint directo de RAWG
   - Estado: ✅ Solo RAWG, sin cambios (ya estaba limpio)
   - API Key: Línea 26

3. **autocomplete_game_info.php**
   - Archivo: `admin/ajax/autocomplete_game_info.php`
   - Función: Auto-rellenar información del juego
   - Cambios: ❌ Eliminada base de datos local de 200+ juegos
   - API Key: Línea 21

4. **get_game_platforms.php**
   - Archivo: `admin/ajax/get_game_platforms.php`
   - Función: Obtener plataformas disponibles
   - Cambios: ❌ Eliminada base de datos local de plataformas
   - API Key: Línea 15

## 💡 Mejores Prácticas

### Seguridad
- ✅ La API key ya está configurada en todos los archivos
- ✅ Nunca compartas la API key públicamente
- ✅ Guarda un backup de la key en lugar seguro

### Monitoreo
- Revisa el uso periódicamente en: https://rawg.io/@gabrielbg21/apikey
- Si excedes el límite, el sistema mostrará errores (sin fallbacks)
- Considera espaciar las búsquedas si estás cerca del límite

### Backups Creados
Se crearon backups de los archivos originales:
- `admin/ajax/autocomplete_game_info.php.bak`
- `admin/ajax/get_game_platforms.php.bak`

---

## ✨ Estado Actual

**✅ SISTEMA LIMPIO** - Tu aplicación usa únicamente RAWG API sin ningún tipo de datos falsos, bases de datos locales o fuentes alternativas. Toda la información proviene directamente de RAWG.

**URLs importantes:**
- Panel de RAWG: https://rawg.io/@gabrielbg21/apikey
- Documentación: https://rawg.io/apidocs
- Tu sitio: https://teal-fish-507993.hostingersite.com

🎮 ¡Disfruta de información 100% real de 500,000+ juegos!
