# Separación de Formularios: Crear vs Editar Productos

## 📋 Resumen de Cambios

Se han separado los formularios de creación y edición de productos en dos archivos distintos para mejorar la claridad y experiencia del usuario.

## 📁 Archivos Creados/Modificados

### 1. `product_create.php` (NUEVO)
**Propósito:** Crear nuevos productos desde cero

**Características:**
- ✅ Formulario simplificado para creación
- ✅ No requiere ID en la URL
- ✅ Vista previa simple de imágenes antes de subir
- ✅ Al guardar redirige a `product_edit.php?id=X` para gestión avanzada
- ✅ JavaScript ligero sin funciones de reordenamiento
- ✅ Mensaje informativo: "Complete la información básica y guarde. Luego podrá agregar más imágenes."

**URL:**
```
/admin/product_create.php
```

### 2. `product_edit.php` (MODIFICADO)
**Propósito:** Editar productos existentes (SOLO con ID)

**Cambios principales:**
- ✅ **REQUIERE ID obligatorio** - Redirige a `products.php` si no hay ID
- ✅ Sistema completo de gestión de imágenes:
  - Flechas ↑↓ para reordenar
  - Radio buttons para seleccionar portada
  - Botón eliminar individual
  - Subida acumulativa (no reemplaza imágenes)
- ✅ Muestra banner: "Editando: [Nombre del Producto]"
- ✅ Botón "Actualizar Producto" (antes era dinámico)
- ✅ Botón "Ver en Sitio" siempre visible
- ✅ Eliminadas todas las referencias a `$is_edit` (ya no es necesario)
- ✅ Código limpio sin lógica condicional crear/editar

**URL:**
```
/admin/product_edit.php?id=123
```

### 3. `products.php` (MODIFICADO)
**Cambios:**
- ✅ Botón "+ Nuevo Producto" ahora apunta a `product_create.php`
- ✅ Botón "Editar" sigue apuntando a `product_edit.php?id=X`

## 🎯 Flujo de Trabajo Actualizado

### Crear Nuevo Producto:

1. Usuario hace clic en **"+ Nuevo Producto"** en `products.php`
2. Se abre `product_create.php` (formulario limpio)
3. Usuario completa información básica y opcionalmente sube imágenes iniciales
4. Al hacer clic en **"Crear Producto"**:
   - Se inserta en DB
   - Se obtiene el nuevo `product_id`
   - **Redirige automáticamente** a `product_edit.php?id=[nuevo_id]`
5. Usuario puede gestionar imágenes avanzadas (reordenar, portada, etc.)

### Editar Producto Existente:

1. Usuario hace clic en **"Editar"** en la lista de productos
2. Se abre `product_edit.php?id=123`
3. Sistema valida que el ID existe
4. Usuario puede:
   - Modificar toda la información
   - Agregar más imágenes (se suman a las existentes)
   - Reordenar imágenes con flechas ↑↓
   - Cambiar imagen de portada
   - Eliminar imágenes individuales
5. Al guardar, permanece en la misma página con mensaje de éxito

## 🔒 Validaciones de Seguridad

### `product_create.php`:
```php
// Verifica permiso de 'create'
if (!hasPermission('products', 'create')) {
    $_SESSION['error'] = 'No tiene permisos para crear productos';
    header('Location: products.php');
    exit;
}
```

### `product_edit.php`:
```php
// 1. Verifica que hay ID
if (empty($product_id)) {
    $_SESSION['error'] = 'ID de producto no proporcionado';
    header('Location: products.php');
    exit;
}

// 2. Verifica permiso de 'update'
if (!hasPermission('products', 'update')) {
    $_SESSION['error'] = 'No tiene permisos para editar productos';
    header('Location: products.php');
    exit;
}

// 3. Verifica que el producto existe
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = 'Producto no encontrado';
    header('Location: products.php');
    exit;
}
```

## ✨ Ventajas de la Separación

1. **Claridad:** URLs más descriptivas (`product_create.php` vs `product_edit.php?id=X`)
2. **Simplicidad:** Cada archivo tiene un propósito único
3. **Mantenibilidad:** Más fácil de mantener sin lógica condicional `if ($is_edit)`
4. **UX Mejorada:** Flujo lógico: crear básico → editar avanzado
5. **Código Limpio:** Sin variables `$is_edit` ni bloques `<?php if ($is_edit): ?>`
6. **Performance:** JavaScript más liviano en página de creación

## 📊 Comparación de Funcionalidades

| Funcionalidad | product_create.php | product_edit.php |
|---------------|-------------------|------------------|
| Información básica | ✅ | ✅ |
| Precios | ✅ | ✅ |
| Descuentos | ✅ | ✅ |
| Categorización | ✅ | ✅ |
| SEO | ✅ | ✅ |
| Subir imágenes | ✅ (básico) | ✅ (avanzado) |
| Reordenar imágenes | ❌ | ✅ (flechas ↑↓) |
| Imagen de portada | ❌ | ✅ (radio buttons) |
| Eliminar imágenes | ❌ | ✅ |
| Vista previa imágenes | ✅ (simple) | ✅ (completo) |
| Ver en sitio | ❌ | ✅ |

## 🚀 Próximos Pasos Recomendados

1. ✅ Probar flujo completo: crear → editar → guardar
2. ✅ Verificar permisos de usuario
3. ✅ Subir imágenes en creación y edición
4. ✅ Probar reordenamiento con flechas
5. ✅ Verificar que portada se marca correctamente
6. ✅ Confirmar redirecciones funcionan

## 🐛 Debugging

Si algo no funciona:

### Error: "ID de producto no proporcionado"
- **Causa:** Intentaste acceder a `product_edit.php` sin `?id=X`
- **Solución:** Usa `product_create.php` para nuevos productos

### Error: "Producto no encontrado"
- **Causa:** El ID en la URL no existe en la base de datos
- **Solución:** Verifica que el producto no fue eliminado

### Las flechas no aparecen
- **Causa:** Estás en `product_create.php` (no hay imágenes aún)
- **Solución:** Crea el producto primero, luego edítalo para gestionar imágenes

## 📝 Código Clave

### Redirección después de crear:
```php
// En product_create.php, después de INSERT
$product_id = $pdo->lastInsertId();
$_SESSION['success'] = 'Producto creado correctamente';
header('Location: product_edit.php?id=' . $product_id);
exit;
```

### Validación en product_edit.php:
```php
// Al inicio del archivo
$product_id = $_GET['id'] ?? null;

if (empty($product_id)) {
    $_SESSION['error'] = 'ID de producto no proporcionado';
    header('Location: products.php');
    exit;
}
```

## 🔗 Enlaces Relacionados

- **Lista de productos:** `products.php`
- **Crear nuevo:** `product_create.php`
- **Editar existente:** `product_edit.php?id=X`
- **API de imágenes:** `api/upload_product_images.php`
- **Changelog:** `CHANGELOG_PRODUCT_EDIT.md` (v2.2.0)

---

**Fecha de implementación:** 2025-01-10
**Versión:** 3.0.0 (Separación de formularios)
**Estado:** ✅ Completado
