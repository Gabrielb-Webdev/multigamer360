# CHANGELOG - Sistema de Búsqueda de Juegos

## Versión 1.1 - Búsqueda Combinada (ACTUAL)
**Estado**: ✅ ACTIVO
**Fecha**: Enero 2025
**Cambios principales**:
- ✅ Búsqueda en **KNOWN_DATABASE** (~45 juegos con plataformas verificadas)
- ✅ Búsqueda en **RAWG API** (500,000+ juegos)
- ✅ Resultados **concatenados sin límite** (todos los resultados)
- ✅ Atribución de fuente en cada resultado
- ✅ Detalles de juego enriquecidos (descripción, géneros, developers, imágenes)
- ✅ Plataformas verificadas desde KNOWN_DATABASE cuando disponible

### Archivos Actualizados (v1.1):
- `admin/ajax/search_game_multi.php` - Búsqueda combinada
- `admin/ajax/autocomplete_game_info.php` - Auto-completar detalles
- `admin/ajax/get_game_platforms.php` - Obtener plataformas

### Funcionalidad:
**Búsqueda (search_game_multi.php)**:
```
GET: admin/ajax/search_game_multi.php?query=Crash&action=search
Response: {
  "results": [
    {"name": "Crash Bandicoot 4...", "source": "KNOWN_DATABASE", ...},
    {"name": "Crash Team Racing", "source": "RAWG", ...},
    ...
  ],
  "sources_detail": {"known_database": 2, "rawg": 5}
}
```

**Detalles (autocomplete_game_info.php)**:
- Busca plataformas en KNOWN_DATABASE (verificadas)
- Enriquece con descripción/géneros de RAWG
- Retorna source="KNOWN_DATABASE ✓ Verificado" si está en base local

**Plataformas (get_game_platforms.php)**:
1. Primero busca en KNOWN_DATABASE
2. Si no encuentra, busca en RAWG
3. Retorna source="KNOWN_DATABASE" o source="RAWG"

### KNOWN_DATABASE (Base Local):
~45 juegos con plataformas verificadas:
- Kingdom Hearts series (15 entradas)
- Crash Bandicoot series (4 entradas)
- Final Fantasy (5 entradas)
- God of War (2 entradas)
- The Legend of Zelda (2 entradas)
- Super Mario (2 entradas)
- Pokemon (2 entradas)
- Y otros...

## Versión 1.0 - RAWG Only (Deprecado)
**Estado**: ❌ REEMPLAZADO
**Línea de tiempo**: 
- Creado como v1.0 inicial
- Archivos backup: `*.v1.0.bak`

---

## API Keys
- **RAWG**: `575f338491134d84bd86df30627a95fe`
- **Documentación**: https://rawg.io/@gabrielbg21/apikey
- **Límite**: 20,000 req/día (free tier)

## Próximas Mejoras
- [ ] Caché de búsquedas populares
- [ ] Sincronización automática de KNOWN_DATABASE con RAWG
- [ ] Importar más juegos a KNOWN_DATABASE
