# 🚀 GUÍA RÁPIDA: Sistema de Productos Separado

## ✅ ¿Qué cambió?

Ahora tienes **DOS archivos separados** para gestionar productos:

### 1️⃣ `product_create.php` - CREAR nuevos productos
**URL:** `/admin/product_create.php`

**Cuándo usar:**
- ✅ Cuando haces clic en **"+ Nuevo Producto"**
- ✅ Para agregar un producto completamente nuevo al inventario

**Qué puedes hacer:**
- ✅ Información básica (nombre, descripción, precios)
- ✅ Categorías, marcas, consolas, géneros
- ✅ Subir imágenes iniciales (opcional)
- ✅ Configurar SEO

**Qué NO puedes hacer (aún):**
- ❌ Reordenar imágenes con flechas
- ❌ Seleccionar imagen de portada
- ❌ Eliminar imágenes individuales

**Flujo:**
```
1. Completas formulario
2. Click en "Crear Producto"
3. ✅ Se guarda en base de datos
4. 🔄 Redirige automáticamente a product_edit.php?id=X
5. 🎉 Ahora puedes gestionar imágenes avanzadas
```

---

### 2️⃣ `product_edit.php?id=X` - EDITAR productos existentes
**URL:** `/admin/product_edit.php?id=123`

**Cuándo usar:**
- ✅ Cuando haces clic en **"Editar"** en la lista de productos
- ✅ Después de crear un producto (redirige automáticamente aquí)
- ✅ Para modificar cualquier dato de un producto

**Qué puedes hacer:**
- ✅ TODO lo de product_create.php
- ✅ **Gestión avanzada de imágenes:**
  - ↑↓ Reordenar con flechas
  - 🌟 Seleccionar imagen de portada (radio buttons)
  - 🗑️ Eliminar imágenes individuales
  - ➕ Agregar más imágenes (se suman, no reemplazan)
- ✅ Ver producto en el sitio web

**Banner informativo:**
```
🔵 Editando: [Nombre del Producto]
```

---

## 📋 Comparación Visual

| Funcionalidad | product_create.php | product_edit.php |
|---------------|:------------------:|:----------------:|
| Crear producto nuevo | ✅ | ❌ |
| Editar producto existente | ❌ | ✅ |
| Información básica | ✅ | ✅ |
| Subir imágenes | ✅ (básico) | ✅ (avanzado) |
| **Reordenar imágenes** | ❌ | ✅ ↑↓ |
| **Imagen de portada** | ❌ | ✅ 🌟 |
| **Eliminar imágenes** | ❌ | ✅ 🗑️ |
| Ver en sitio | ❌ | ✅ 👁️ |

---

## 🎯 Casos de Uso

### Caso 1: Agregar producto nuevo desde cero
```
1. Click en "+ Nuevo Producto"
2. Completa información básica
3. (Opcional) Sube 1-2 imágenes iniciales
4. Click "Crear Producto"
5. 🔄 Automáticamente va a edición
6. Sube más imágenes y organízalas
```

### Caso 2: Editar producto existente
```
1. En lista de productos, click "Editar"
2. Modifica lo que necesites
3. Reordena imágenes con flechas ↑↓
4. Marca imagen de portada 🌟
5. Click "Actualizar Producto"
6. ✅ Cambios guardados
```

### Caso 3: Agregar más imágenes a producto existente
```
1. Abre product_edit.php?id=X
2. Scroll a "Imágenes del Producto"
3. Click "Agregar Imágenes"
4. Selecciona archivos
5. Aparecen en vista previa con borde verde
6. Click "Subir X imagen(es) pendiente(s)"
7. ✅ Se agregan al final (no reemplazan)
```

---

## ⚠️ Errores Comunes

### ❌ "ID de producto no proporcionado"
**Causa:** Intentaste abrir `product_edit.php` sin `?id=X` en la URL

**Solución:** Usa `product_create.php` para productos nuevos

---

### ❌ "No veo las flechas ↑↓"
**Causa:** Estás en `product_create.php` (producto nuevo sin imágenes aún)

**Solución:** 
1. Guarda el producto primero
2. Automáticamente irás a `product_edit.php`
3. Allí verás las flechas

---

### ❌ "Las imágenes se reemplazan en vez de sumarse"
**Causa:** Error de código (no debería pasar ahora)

**Solución:** Verifica que el backend usa:
```php
$current_max_order = $pdo->prepare("SELECT MAX(display_order)...")->fetchColumn();
$current_max_order++; // Incrementa en lugar de reemplazar
```

---

## 🔐 Permisos Requeridos

### Para product_create.php:
```php
'products' => 'create'
```

### Para product_edit.php:
```php
'products' => 'update'
```

---

## 🌐 URLs de Navegación

```
📋 Lista de productos:
   /admin/products.php

➕ Crear nuevo:
   /admin/product_create.php

✏️ Editar existente:
   /admin/product_edit.php?id=123

👁️ Ver en sitio:
   /product.php?id=123
```

---

## 💡 Tips Pro

### 1. Workflow óptimo para producto nuevo:
```
product_create.php (info básica)
        ↓
[GUARDAR]
        ↓
product_edit.php?id=X (gestión de imágenes)
        ↓
[REORDENAR] [PORTADA] [AGREGAR MÁS]
        ↓
¡Producto listo! 🎉
```

### 2. Para agregar imágenes masivas:
```
1. Sube todas a la vez en product_edit.php
2. Aparecen con badge #1, #2, #3...
3. Usa flechas ↑↓ para ajustar orden
4. Marca la mejor como portada 🌟
```

### 3. La primera imagen siempre es portada (en creación):
```
Si subes 5 imágenes al crear:
- La 1ra → is_primary = 1 🌟
- Las demás → is_primary = 0
```

---

## 🎨 Identificación Visual

### product_create.php:
```
┌─────────────────────────────────────┐
│ ℹ️ Creando nuevo producto.         │
│ Complete información básica...      │
└─────────────────────────────────────┘

Botón: [💾 Crear Producto]
```

### product_edit.php:
```
┌─────────────────────────────────────┐
│ 🔵 Editando: Kingdom Hearts III    │
└─────────────────────────────────────┘

Imágenes con flechas:
┌──────────┐
│ [↑] [↓] │ ← Botones de reordenamiento
│ ⚪ 🌟 Portada
│ [🗑️ Eliminar]
└──────────┘

Botón: [💾 Actualizar Producto]
```

---

## 🚦 Estado del Sistema

✅ **product_create.php** - Funcionando
✅ **product_edit.php** - Funcionando
✅ **products.php** - Actualizado
✅ **Sistema de flechas ↑↓** - Activo
✅ **Imagen de portada** - Radio buttons
✅ **Eliminar imágenes** - Funcionando
✅ **Subida acumulativa** - Confirmado

---

## 📞 Soporte

Si algo no funciona:

1. Verifica que el producto existe (si usas product_edit.php)
2. Confirma que tienes los permisos correctos
3. Revisa la consola del navegador (F12)
4. Busca mensajes de error PHP en el servidor

---

**Fecha:** 2025-01-10
**Versión:** 3.0.0 (Separación completa)
**Autor:** Sistema MultiGamer360

---

## 📸 Capturas Recomendadas

Para confirmar que todo funciona, toma screenshots de:

1. ✅ product_create.php con formulario vacío
2. ✅ product_edit.php con producto existente
3. ✅ Flechas ↑↓ en imágenes
4. ✅ Radio button de portada
5. ✅ Botón "Actualizar Producto"

¡Listo! 🎉
