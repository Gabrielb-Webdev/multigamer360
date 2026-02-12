# Changelog - Sistema de Imágenes (product_edit.php)

## Versión 2.1.0 - 2025-01-10

### 🎯 Cambios Principales

#### ✅ Drag & Drop Mejorado
- Agregado `forceFallback: true` para mejor compatibilidad cross-browser
- Cambiado handle a `.card` (toda la card es arrastrable)
- Agregado `fallbackTolerance: 3` para mejor detección de arrastre
- Removido `style="cursor: move;"` inline que causaba conflictos
- Agregados logs de depuración: `🎯 Iniciando arrastre` y `🎯 Movida de posición`

#### ✅ Radio Buttons para Portada
- Reemplazados botones por radio buttons
- Solo UNA imagen puede estar seleccionada como portada
- Cambio automático al seleccionar otro radio
- Estilo visual mejorado con icono de estrella

#### ✅ Vista Previa SERP Arreglada
- Eliminada referencia a `short_description` (columna eliminada)
- Agregadas verificaciones de elementos antes de leer `.value`
- Cambiado fallback de `short_description` a `description`
- Función `updateSERPPreview()` ahora es robusta ante elementos nulos

#### ✅ Alerta Eliminada
- Cambiado `alert('Campos SEO generados')` por `console.log()`
- No más popups molestos al auto-generar SEO

#### 🎨 Estilos CSS Mejorados
```css
.sortable-chosen .card {
    transform: scale(1.05) rotate(2deg);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.sortable-ghost .card {
    opacity: 0.3;
    border: 3px dashed #0d6efd;
}
```

#### 📊 Sistema de Depuración
- `console.log('📦 Sistema de Imágenes v2.1.0')`
- `console.log('✅ Sortable.js cargado correctamente')`
- `console.log('✓ Elementos arrastrables: X')`
- `console.log('🎯 Iniciando arrastre de imagen X')`

---

## Versión 2.0.0 - 2025-01-09

### 🎯 Cambios Principales

#### ✅ Sistema de Imágenes Acumulativas
- Las imágenes ya NO se reemplazan al subir nuevas
- Se pueden agregar imagen por imagen
- Arrays `pendingImages` y `pendingImageFiles` para almacenar temporalmente
- Botón "Subir X imagen(es) pendiente(s)" para subida AJAX

#### ✅ APIs REST Creadas
- `admin/api/upload_product_images.php` - Subir múltiples imágenes
- `admin/api/set_primary_image.php` - Marcar imagen como portada
- `admin/api/update_image_order.php` - Actualizar orden de imágenes
- `admin/api/delete_product_image.php` - Eliminar imagen individual

#### ✅ Eliminado Campo `short_description`
- Removido del formulario HTML
- Actualizado PHP para no procesar ese campo
- SQL script creado: `config/actualizar_imagenes_SIMPLE.sql`

#### ✅ Sistema de Reordenamiento
- Integración de Sortable.js 1.15.0
- Drag & drop funcional para cambiar orden
- Actualización automática de badges de orden (#1, #2, #3...)
- Guardado automático en servidor

---

## Versión 1.0.0 - Inicial

### Características Base
- Formulario de creación/edición de productos
- Subida de múltiples imágenes
- Sistema de categorías, marcas y géneros
- Campos SEO (meta title, meta description)
- Precios en pesos y dólares
- Sistema de descuentos por porcentaje
- Vista previa SERP de Google

---

## 🔧 Archivos Modificados

### `admin/product_edit.php`
**Líneas modificadas:**
- **862-920**: Estilos CSS para drag & drop
- **935-945**: Verificación de carga de Sortable
- **1075-1105**: Configuración de Sortable mejorada
- **1325-1375**: Funciones SERP sin referencias a short_description
- **330-345**: HTML del input de imágenes
- **365-415**: Grid de imágenes con radio buttons

### Archivos Creados
- `admin/api/upload_product_images.php`
- `admin/api/set_primary_image.php`
- `config/actualizar_imagenes_SIMPLE.sql`
- `docs/SISTEMA_IMAGENES_ACUMULATIVAS.md`

---

## 🚀 Instrucciones de Actualización

### Para que los cambios se apliquen:

1. **Limpiar caché del navegador:**
   ```
   Ctrl + Shift + Delete
   o
   Ctrl + F5 (recarga forzada)
   ```

2. **Verificar versión en consola:**
   ```
   Abre F12 > Console
   Deberías ver: 📦 Sistema de Imágenes v2.1.0
   ```

3. **Ejecutar SQL en Hostinger:**
   ```sql
   -- Archivo: config/actualizar_imagenes_SIMPLE.sql
   -- Elimina short_description y configura product_images
   ```

4. **Probar funcionalidades:**
   - [ ] Arrastrar imágenes para reordenar
   - [ ] Seleccionar radio button de portada
   - [ ] Agregar nuevas imágenes (acumulativas)
   - [ ] Eliminar imágenes individuales
   - [ ] Auto-generar campos SEO (sin alerta)

---

## 🐛 Problemas Conocidos Resueltos

| Problema | Versión | Solución |
|----------|---------|----------|
| Imágenes se reemplazan al subir | 1.0.0 | Sistema acumulativo en v2.0.0 |
| Cannot read properties of null | 2.0.0 | Verificaciones en v2.1.0 |
| Drag & drop no funciona | 2.0.0 | forceFallback en v2.1.0 |
| Alerta molesta de SEO | 2.0.0 | console.log en v2.1.0 |
| Vista SERP desaparece | 2.0.0 | Funciones robustas en v2.1.0 |

---

## 📝 Notas para Desarrolladores

### Cache Busting
- Los estilos y scripts están INLINE en product_edit.php
- No usan `asset_versions.php` (ese es para archivos externos)
- Para forzar recarga: Cambiar número de versión en comentarios
- O agregar `?v=X` a la URL en el navegador

### Debugging
- Todos los console.log tienen emojis para fácil identificación
- 🚀 = Inicialización
- ✅ = Éxito
- ❌ = Error
- 🎯 = Acción de usuario
- 📦 = Versión

### Testing
Siempre probar en:
- Chrome (última versión)
- Firefox (última versión)
- Edge (última versión)
- Safari (si es posible)

---

## 🎯 Próximas Mejoras (Roadmap)

### v2.2.0 (Planeado)
- [ ] Crop de imágenes antes de subir
- [ ] Compresión automática de imágenes
- [ ] Generación de thumbnails
- [ ] Edición de alt_text para SEO
- [ ] Importar imágenes desde URL

### v2.3.0 (Futuro)
- [ ] Galería con zoom en frontend
- [ ] Drag & drop directo (sin botón "Elegir archivos")
- [ ] Editor de imágenes (rotate, resize)
- [ ] Copiar imágenes entre productos
- [ ] Batch upload (subir a múltiples productos)

---

**Última actualización**: 2025-01-10  
**Mantenedor**: GitHub Copilot  
**Versión actual**: 2.1.0
