# Sistema de Importación de Productos Simplificado

## 📋 Resumen del Sistema

Este sistema permite importar productos de forma rápida con solo 4 campos en el Excel, y luego revisar y completar cada producto uno por uno con auto-rellenado de información.

## 🔄 Flujo Completo

```
1. Usuario sube Excel con 4 campos
   ↓
2. Sistema procesa y muestra productos para revisión
   ↓
3. Por cada producto:
   - Muestra datos del Excel
   - Permite auto-completar con API de juegos
   - Usuario completa/edita información
   - Usuario sube imágenes manualmente
   - Guardar y siguiente
   ↓
4. Finalizar importación
```

## 📄 Archivo Excel Simplificado

### Columnas Requeridas (Solo 4):
| nombre_producto | estado | precio_pesos | precio_dolares |
|----------------|--------|--------------|----------------|
| Super Mario 64 | activo | 10000 | 10 |

### Estados Permitidos:
- `activo` - Producto activo y visible
- `inactivo` - Producto inactivo pero en sistema
- `agotado` - Sin stock disponible

## 🎮 Auto-Completado con RAWG API

### ¿Qué es RAWG?
API gratuita de videojuegos con información de más de 500,000 juegos.

### Información que auto-completa:
- ✅ Descripción completa del juego
- ✅ Descripción corta
- ✅ Géneros (intenta mapear automáticamente)
- ✅ Plataformas/Consolas (intenta mapear)
- ✅ Desarrolladores
- ✅ Publishers
- ✅ Tags/Etiquetas
- ✅ Rating ESRB
- ✅ URLs de imágenes de referencia

### Cómo funciona:
1. Usuario hace clic en "Auto-Rellenar Información"
2. Sistema busca en RAWG con el nombre del juego
3. Mapea automáticamente la información a tu base de datos
4. Usuario revisa y ajusta si es necesario

## 🖼️ Subida Manual de Imágenes

- Subida múltiple de imágenes por producto
- Vista previa antes de guardar
- Validación de formato y tamaño
- Primera imagen se marca como principal automáticamente

## 📦 Archivos Creados/Modificados

### Nuevos Archivos:

1. **admin/ajax/process_csv_preview.php**
   - Procesa el Excel y extrae productos
   - Devuelve JSON con productos para revisión
   - Incluye dropdowns de categorías, marcas, consolas, géneros

2. **admin/ajax/save_reviewed_product.php**
   - Guarda producto completado en base de datos
   - Maneja géneros múltiples
   - Procesa imágenes

3. **admin/ajax/autocomplete_game_info.php**
   - Conecta con RAWG API
   - Busca información del juego
   - Mapea datos a formato de la BD

4. **admin/ajax/upload_product_images.php**
   - Sube imágenes del producto
   - Valida formato y tamaño
   - Retorna URLs de imágenes

### Archivos Modificados:

1. **admin/products.php**
   - Modal simplificado de subida Excel
   - Modal completo de revisión producto por producto
   - JavaScript para navegación entre productos
   - Integración con auto-completado

2. **admin/download_excel_example.php**
   - Simplificado a solo 4 columnas
   - Instrucciones actualizadas
   - 10 productos de ejemplo

## 🎯 Ventajas del Nuevo Sistema

### Para el Usuario:
- ✅ **Excel súper simple**: Solo 4 campos obligatorios
- ✅ **Control total**: Revisa cada producto antes de guardar
- ✅ **Auto-completado**: Información del juego en 1 clic
- ✅ **Flexible**: Puede saltar productos o volver atrás
- ✅ **Visual**: Ve exactamente qué está guardando

### Comparado con el Sistema Anterior:
| Característica | Anterior | Nuevo |
|---------------|----------|-------|
| Campos en Excel | 20 | 4 |
| Revisión antes de guardar | ❌ | ✅ |
| Auto-completado | ❌ | ✅ |
| Navegación entre productos | ❌ | ✅ |
| Subida de imágenes integrada | ❌ | ✅ |
| Complejidad | Alta | Baja |

## 🚀 Uso del Sistema

### Paso 1: Preparar Excel
```excel
nombre_producto         | estado  | precio_pesos | precio_dolares
Super Mario 64          | activo  | 10000        | 10
The Legend of Zelda     | activo  | 12000        | 12
Kingdom Hearts          | activo  | 8000         | 8
```

### Paso 2: Subir Excel
1. Ir a Admin > Productos
2. Clic en "Importar Excel"
3. Seleccionar archivo
4. Clic en "Subir y Revisar"

### Paso 3: Revisar Producto por Producto
**Para cada producto:**

1. **Ver datos del Excel** (parte superior en azul)
2. **Auto-rellenar** (opcional):
   - Clic en "Auto-Rellenar Información del Juego"
   - Esperar búsqueda en RAWG
   - Revisar información cargada
3. **Completar campos obligatorios**:
   - Categoría
   - Marca  
   - Consola
   - Stock
4. **Subir imágenes** (opcional pero recomendado)
5. **Ajustar información** según necesites
6. **Guardar y Siguiente** o **Finalizar**

### Navegación:
- **Saltar**: Ignora el producto actual (no se guarda)
- **Anterior**: Vuelve al producto anterior
- **Guardar y Siguiente**: Guarda y pasa al siguiente
- **Finalizar**: Guarda el último producto y completa

## 🔧 Configuración RAWG API (Opcional)

Para mejorar el límite de requests, obtener API key gratis:

1. Ir a https://rawg.io/apidocs
2. Crear cuenta gratis
3. Copiar API Key
4. Editar `admin/ajax/autocomplete_game_info.php`:
```php
$apiKey = 'TU_API_KEY_AQUI';
```

**Límites:**
- Sin API Key: ~30 requests/minuto
- Con API Key: 20,000 requests/mes

## 🐛 Solución de Problemas

### "No se encontró información del juego"
- El nombre del juego no está en RAWG
- Completa manualmente la información

### "Error al subir imágenes"
- Verifica que las imágenes sean JPG, PNG, GIF o WEBP
- Tamaño máximo: 5MB por imagen
- Verifica permisos de carpeta `uploads/products/`

### "Campos obligatorios"
- Asegúrate de completar:
  - Nombre
  - Precio COP
  - Categoría
  - Marca
  - Consola

## 📊 Campos del Formulario de Revisión

### Obligatorios (*):
- Nombre del Producto *
- Precio Pesos (COP) *
- Categoría *
- Marca *
- Consola/Plataforma *
- Stock * (puede ser 0)
- Estado *

### Opcionales:
- SKU (se genera automáticamente)
- Precio Dólares
- Descripción
- Descripción Corta
- Géneros (múltiple)
- Condición (nuevo/usado/reacondicionado)
- Destacado (checkbox)
- Novedad (checkbox)
- En Oferta (checkbox)
- Etiquetas
- Imágenes
- Meta Título (SEO)
- Meta Descripción (SEO)

## 📈 Próximas Mejoras (Opcionales)

- [ ] Guardar borradores de productos
- [ ] Importar imágenes desde URLs de RAWG automáticamente
- [ ] Búsqueda de productos ya existentes antes de crear
- [ ] Edición por lotes de productos ya importados
- [ ] Historial de importaciones
- [ ] Plantillas de productos por consola
