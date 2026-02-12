# ✅ CHECKLIST DE VERIFICACIÓN - Sistema de Productos Separado

## 📝 Lista de Verificación Post-Implementación

### 1. Archivos Creados/Modificados

- [x] **product_create.php** - Creado ✅
- [x] **product_edit.php** - Modificado (solo edición) ✅
- [x] **products.php** - Actualizado (botones) ✅
- [x] **SEPARATION_DOCUMENTATION.md** - Creado ✅
- [x] **QUICK_GUIDE.md** - Creado ✅
- [x] **CHECKLIST.md** - Este archivo ✅

---

## 🧪 Pruebas a Realizar

### A. Pruebas en `product_create.php`

#### Test 1: Acceso Directo
- [ ] Abre: `http://localhost/multigamer360/admin/product_create.php`
- [ ] ✅ Debe mostrar formulario vacío
- [ ] ✅ Banner: "Creando nuevo producto"
- [ ] ✅ Botón: "Crear Producto"

#### Test 2: Crear Producto Básico
- [ ] Completa:
  - Nombre: "Test Kingdom Hearts"
  - Precio Pesos: 150000
  - Stock: 10
  - Categoría: (cualquiera)
  - Descripción: "Producto de prueba"
- [ ] Click "Crear Producto"
- [ ] ✅ Redirige a `product_edit.php?id=X`
- [ ] ✅ Mensaje: "Producto creado correctamente"

#### Test 3: Crear con Imágenes
- [ ] Repite Test 2
- [ ] Sube 2-3 imágenes antes de guardar
- [ ] Click "Crear Producto"
- [ ] ✅ En product_edit.php, verifica que las imágenes aparecen
- [ ] ✅ Primera imagen debe tener badge "Portada"

#### Test 4: Validaciones
- [ ] Intenta crear sin nombre
- [ ] ✅ Debe mostrar error de validación
- [ ] Intenta crear sin precio
- [ ] ✅ Debe mostrar error
- [ ] Intenta crear sin categoría
- [ ] ✅ Debe mostrar error

---

### B. Pruebas en `product_edit.php`

#### Test 5: Acceso Directo SIN ID
- [ ] Abre: `http://localhost/multigamer360/admin/product_edit.php`
- [ ] ✅ Debe redirigir a `products.php`
- [ ] ✅ Mensaje: "ID de producto no proporcionado"

#### Test 6: Acceso con ID Válido
- [ ] Abre: `http://localhost/multigamer360/admin/product_edit.php?id=1`
- [ ] ✅ Debe mostrar formulario con datos del producto
- [ ] ✅ Banner: "Editando: [Nombre del Producto]"
- [ ] ✅ Botón: "Actualizar Producto"
- [ ] ✅ Botón: "Ver en Sitio"

#### Test 7: Acceso con ID Inválido
- [ ] Abre: `http://localhost/multigamer360/admin/product_edit.php?id=99999`
- [ ] ✅ Debe redirigir a `products.php`
- [ ] ✅ Mensaje: "Producto no encontrado"

#### Test 8: Editar Información Básica
- [ ] Abre un producto existente
- [ ] Cambia el nombre
- [ ] Click "Actualizar Producto"
- [ ] ✅ Mensaje: "Producto actualizado correctamente"
- [ ] ✅ Permanece en la misma página
- [ ] ✅ Cambios reflejados en el formulario

#### Test 9: Sistema de Imágenes - Agregar
- [ ] Abre un producto existente
- [ ] Cuenta las imágenes actuales (ej: 3)
- [ ] Click "Agregar Imágenes"
- [ ] Selecciona 2 nuevas imágenes
- [ ] ✅ Aparecen en vista previa con borde verde
- [ ] ✅ Dice "Nueva #1", "Nueva #2"
- [ ] Click "Subir X imagen(es) pendiente(s)"
- [ ] ✅ Ahora debe haber 5 imágenes totales
- [ ] ✅ Las nuevas se agregaron al final

#### Test 10: Sistema de Imágenes - Reordenar
- [ ] Abre un producto con al menos 3 imágenes
- [ ] Identifica imagen #2
- [ ] Click flecha ↑ en imagen #2
- [ ] ✅ Se mueve a posición #1
- [ ] ✅ Badge actualiza automáticamente
- [ ] ✅ Flecha ↑ se deshabilita (ahora es primera)
- [ ] Click flecha ↓ en imagen #1
- [ ] ✅ Vuelve a posición #2

#### Test 11: Sistema de Imágenes - Portada
- [ ] Abre un producto con varias imágenes
- [ ] Identifica cuál tiene badge "Portada" (verde)
- [ ] Selecciona otra imagen con el radio button "⭐ Imagen de Portada"
- [ ] ✅ Badge "Portada" cambia a la nueva imagen
- [ ] Recarga la página
- [ ] ✅ La nueva portada persiste

#### Test 12: Sistema de Imágenes - Eliminar
- [ ] Abre un producto con al menos 3 imágenes
- [ ] Cuenta imágenes totales (ej: 5)
- [ ] Click "🗑️ Eliminar" en una imagen
- [ ] ✅ Confirma en el alert
- [ ] ✅ Imagen desaparece inmediatamente
- [ ] ✅ Contador actualiza (ahora: 4)
- [ ] ✅ Badges se renumeran (#1, #2, #3, #4)

#### Test 13: Edge Case - Eliminar Última Imagen
- [ ] Crea producto con 1 sola imagen
- [ ] Click "🗑️ Eliminar"
- [ ] ✅ Muestra: "Este producto aún no tiene imágenes"
- [ ] Agrega nueva imagen
- [ ] ✅ Se marca automáticamente como portada

---

### C. Pruebas en `products.php`

#### Test 14: Botón "Nuevo Producto"
- [ ] Abre: `http://localhost/multigamer360/admin/products.php`
- [ ] Click "+ Nuevo Producto"
- [ ] ✅ Redirige a `product_create.php` (NO a product_edit.php)

#### Test 15: Botón "Editar"
- [ ] En la lista de productos, click "Editar" en cualquier producto
- [ ] ✅ Redirige a `product_edit.php?id=X`
- [ ] ✅ X es el ID correcto del producto

---

### D. Pruebas de Integración

#### Test 16: Flujo Completo
- [ ] 1. Click "+ Nuevo Producto" en products.php
- [ ] 2. Completa formulario en product_create.php
- [ ] 3. Sube 1 imagen inicial
- [ ] 4. Click "Crear Producto"
- [ ] 5. ✅ Redirige a product_edit.php?id=X
- [ ] 6. Agrega 3 imágenes más
- [ ] 7. Reordena con flechas
- [ ] 8. Selecciona portada diferente
- [ ] 9. Elimina 1 imagen
- [ ] 10. Click "Actualizar Producto"
- [ ] 11. ✅ Todo guardado correctamente
- [ ] 12. Click "Ver en Sitio"
- [ ] 13. ✅ Se abre en nueva pestaña
- [ ] 14. ✅ Imagen de portada correcta

#### Test 17: Permisos - Usuario sin permisos
- [ ] Crea usuario de prueba sin permiso 'products.create'
- [ ] Intenta acceder a product_create.php
- [ ] ✅ Redirige a products.php con error

#### Test 18: Permisos - Usuario sin permisos de edición
- [ ] Crea usuario sin permiso 'products.update'
- [ ] Intenta acceder a product_edit.php?id=1
- [ ] ✅ Redirige a products.php con error

---

### E. Pruebas de Navegador

#### Test 19: Consola JavaScript
- [ ] Abre product_create.php
- [ ] Presiona F12 (DevTools)
- [ ] ✅ No debe haber errores en consola
- [ ] Abre product_edit.php?id=1
- [ ] ✅ Debe mostrar: "📦 Sistema de Imágenes v2.2.0 - Simplificado"

#### Test 20: Responsive Design
- [ ] Abre product_create.php en móvil (F12 > Device Toolbar)
- [ ] ✅ Formulario se adapta correctamente
- [ ] Abre product_edit.php?id=1 en móvil
- [ ] ✅ Flechas ↑↓ accesibles
- [ ] ✅ Imágenes se ven bien en grid

---

### F. Pruebas de Base de Datos

#### Test 21: Verificar Orden de Imágenes
```sql
SELECT id, product_id, image_url, is_primary, display_order 
FROM product_images 
WHERE product_id = 1 
ORDER BY display_order;
```
- [ ] ✅ display_order debe ser secuencial: 1, 2, 3, 4...
- [ ] ✅ Solo UNA imagen debe tener is_primary = 1

#### Test 22: Verificar Producto Creado
```sql
SELECT * FROM products 
WHERE name = 'Test Kingdom Hearts' 
ORDER BY created_at DESC 
LIMIT 1;
```
- [ ] ✅ Existe el producto
- [ ] ✅ created_at y updated_at tienen fechas
- [ ] ✅ SKU está generado

---

### G. Pruebas de Errores Comunes

#### Test 23: Doble Submit
- [ ] Abre product_create.php
- [ ] Completa formulario
- [ ] Click rápido 2 veces en "Crear Producto"
- [ ] ✅ Solo debe crear 1 producto (no duplicados)

#### Test 24: Archivos Grandes
- [ ] Intenta subir imagen de 10MB
- [ ] ✅ Debe rechazarla (límite 5MB)

#### Test 25: Formato Inválido
- [ ] Intenta subir archivo .txt
- [ ] ✅ Debe rechazarlo (solo imágenes)

---

## 📊 Resultados

### Resumen de Pruebas
```
Total de Tests: 25
Completados: [ ] / 25
Fallidos: [ ]
```

### Tests Críticos (deben pasar 100%)
- [ ] Test 1: Acceso a product_create.php
- [ ] Test 2: Crear producto básico
- [ ] Test 5: Bloqueo sin ID en product_edit.php
- [ ] Test 6: Editar producto con ID
- [ ] Test 10: Reordenar imágenes
- [ ] Test 11: Cambiar portada
- [ ] Test 14: Botón "Nuevo Producto"
- [ ] Test 16: Flujo completo

---

## 🐛 Registro de Bugs Encontrados

### Bug #1
**Descripción:**
**Pasos para reproducir:**
**Resultado esperado:**
**Resultado actual:**
**Severidad:** [ ] Crítico [ ] Alto [ ] Medio [ ] Bajo
**Estado:** [ ] Abierto [ ] En progreso [ ] Resuelto

---

## ✅ Aprobación Final

- [ ] Todos los tests críticos pasan
- [ ] No hay errores en consola JavaScript
- [ ] No hay errores PHP en logs
- [ ] Documentación revisada
- [ ] URLs actualizadas correctamente

**Aprobado por:** _______________
**Fecha:** _______________
**Notas:**

---

## 📸 Screenshots Recomendados

Para documentación, captura:

1. [ ] product_create.php - Formulario limpio
2. [ ] product_create.php - Con imágenes en preview
3. [ ] product_edit.php - Banner "Editando: X"
4. [ ] product_edit.php - Flechas ↑↓ en imágenes
5. [ ] product_edit.php - Radio buttons de portada
6. [ ] products.php - Botón "+ Nuevo Producto"
7. [ ] Consola JavaScript - Mensaje v2.2.0

---

## 🎯 Próximas Mejoras (Opcional)

- [ ] Drag & drop más intuitivo (HTML5 nativo)
- [ ] Crop de imágenes en el navegador
- [ ] Múltiples vistas (lista/grid) en product_edit.php
- [ ] Historial de cambios de producto
- [ ] Importación masiva de productos (CSV)

---

**Versión del Checklist:** 1.0
**Fecha de creación:** 2025-01-10
**Última actualización:** 2025-01-10
