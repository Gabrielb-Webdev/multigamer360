# FIX CRÍTICO: Columna 'price' Inexistente - Wishlist y Carrito

**Fecha:** 19 de febrero de 2026  
**Commit:** `585f80b`  
**Severidad:** CRÍTICA  
**Estado:** ✅ RESUELTO

---

## 🔴 PROBLEMA REPORTADO

### Error en Consola del Navegador
```javascript
productos.php:3462 ❌ Error con wishlist: Error: Error interno del servidor
    at productos.php:3458:19
(anonymous) @ productos.php:3462

productos.php:3427 📝 Respuesta del servidor: 
{
  "success": false,
  "message": "Error interno del servidor",
  "debug": "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'price' in 'SELECT'",
  "line": 42
}
```

### Síntomas
1. **Wishlist no funcional:** Error al intentar agregar productos a la wishlist
2. **Carrito vacío:** Productos agregados no se visualizan en el carrito
3. **Error SQL:** Column not found `'price'` en queries SELECT

---

## 🔍 CAUSA RAÍZ

Las consultas SQL intentaban usar una columna `price` que **no existe** en la tabla `products`.

### Estructura Real de la Tabla
```sql
-- Columnas de precio existentes:
price_pesos    DECIMAL(10,2)
price_dollars  DECIMAL(10,2)

-- Columna inexistente (causante del error):
price  -- ❌ NO EXISTE
```

### Queries Problemáticas

**1. ajax/toggle-wishlist.php** (Línea 37)
```sql
SELECT id, name, 
       COALESCE(price_pesos, price_dollars, price) as price,  -- ❌ Error
       COALESCE(main_image, '') as image_url 
FROM products 
WHERE id = ? AND is_active = 1
```

**2. ajax/toggle-wishlist.php** (Línea 112)
```sql
SELECT SUM(COALESCE(p.price_pesos, p.price_dollars, p.price)) as total  -- ❌ Error
FROM user_favorites uf
JOIN products p ON uf.product_id = p.id
WHERE uf.user_id = ? AND p.is_active = 1
```

**3. carrito.php** (Línea 106 y 138)
```sql
SELECT p.id, p.name, 
       COALESCE(p.price_pesos, p.price_dollars, p.price) as price,  -- ❌ Error
       p.main_image, p.stock_quantity
FROM products p
WHERE p.id IN (...)
```

---

## ✅ SOLUCIÓN IMPLEMENTADA

### Correcciones Aplicadas

Reemplazar todas las referencias a la columna inexistente `price`:

```sql
-- ANTES (❌ Error)
COALESCE(price_pesos, price_dollars, price)

-- DESPUÉS (✅ Correcto)
COALESCE(price_pesos, price_dollars, 0)
```

### Archivos Modificados

#### 1. [ajax/toggle-wishlist.php](ajax/toggle-wishlist.php)

**Línea 37:**
```sql
SELECT id, name, 
       COALESCE(price_pesos, price_dollars, 0) as price,  -- ✅ Corregido
       COALESCE(main_image, '') as image_url 
FROM products 
WHERE id = ? AND is_active = 1
```

**Línea 112:**
```sql
SELECT SUM(COALESCE(p.price_pesos, p.price_dollars, 0)) as total  -- ✅ Corregido
FROM user_favorites uf
JOIN products p ON uf.product_id = p.id
WHERE uf.user_id = ? AND p.is_active = 1
```

#### 2. [carrito.php](carrito.php)

**Línea 106:**
```sql
SELECT p.id, p.name, 
       COALESCE(p.price_pesos, p.price_dollars, 0) as price,  -- ✅ Corregido
       p.main_image, p.stock_quantity,
       COALESCE(
           (SELECT pi.image_url FROM product_images pi 
            WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
           p.main_image, ''
       ) as primary_image
FROM products p
WHERE p.id IN (...)
```

**Línea 138 (fallback sin product_images):**
```sql
SELECT id, name, 
       COALESCE(price_pesos, price_dollars, 0) as price,  -- ✅ Corregido
       main_image, stock_quantity,
       COALESCE(main_image, '') as primary_image
FROM products
WHERE id IN (...)
```

---

## 🎯 IMPACTO DE LA SOLUCIÓN

### Funcionalidades Restauradas

✅ **Wishlist Funcional**
- Los usuarios pueden agregar productos a su wishlist sin errores
- El contador de wishlist se actualiza correctamente
- El total de la wishlist se calcula sin errores

✅ **Carrito Funcional**
- Los productos agregados se visualizan correctamente en carrito.php
- Los precios se muestran usando `price_pesos` como principal
- Fallback a `price_dollars` si `price_pesos` no está disponible
- Fallback final a `0` si ningún precio está disponible

✅ **Sin Errores SQL**
- No más `SQLSTATE[42S22]: Column not found`
- Todas las queries usan columnas existentes
- Debug habilitado muestra respuestas correctas

---

## 📋 TESTING

### Casos de Prueba

1. **Agregar Producto a Wishlist**
   - ✅ Click en ícono corazón funciona
   - ✅ Mensaje de confirmación se muestra
   - ✅ Contador de wishlist se incrementa
   - ✅ Sin errores en consola

2. **Ver Carrito con Productos**
   - ✅ Productos agregados se visualizan
   - ✅ Precios se muestran correctamente
   - ✅ Cantidades editables
   - ✅ Subtotales calculados

3. **Remover de Wishlist**
   - ✅ Click en remover funciona
   - ✅ Total de wishlist se actualiza
   - ✅ Sin errores SQL

---

## 🔄 DEPLOYMENT

### Proceso
1. Correcciones aplicadas localmente
2. Commit: `585f80b`
3. Push a GitHub: ✅ Exitoso
4. Sincronización automática con Hostinger

### Validación Post-Deploy
```bash
# Limpiar caché del navegador
Ctrl+Shift+F5

# Verificar funciones:
1. Agregar productos a wishlist → OK
2. Ver carrito con productos → OK
3. Consola del navegador → Sin errores
```

---

## 📚 LECCIONES APRENDIDAS

### Prevención Futura

1. **Verificar Estructura de BD Antes de Consultas**
   ```sql
   SHOW COLUMNS FROM products LIKE 'price%';
   ```

2. **Usar COALESCE con Valores por Defecto**
   ```sql
   -- ✅ Siempre incluir fallback final
   COALESCE(price_pesos, price_dollars, 0)
   
   -- ❌ No asumir que columnas existen
   COALESCE(price_pesos, price_dollars, price)
   ```

3. **Testing con Diferentes Escenarios**
   - Productos sin `price_pesos`
   - Productos sin `price_dollars`
   - Productos sin ningún precio

4. **Debug en Desarrollo**
   ```php
   // Habilitar temporalmente para debugging
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

---

## 📞 SOPORTE

Si el problema persiste después del deployment:

1. Limpiar caché del navegador: `Ctrl+Shift+F5`
2. Verificar sincronización con Hostinger
3. Revisar logs del servidor en `/error_log`
4. Contactar a soporte técnico con este documento

---

**Autor:** GitHub Copilot  
**Revisado por:** Equipo MultiGamer360
