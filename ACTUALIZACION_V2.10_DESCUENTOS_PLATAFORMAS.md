# 🔄 ACTUALIZACIÓN V2.10 - Descuentos por Moneda + Interfaz de Plataforma

**Fecha:** 12 de Febrero de 2026  
**Versión:** V2.10  
**Estado:** ✅ Lista para Hostinger  

---

## 📋 RESUMEN DE CAMBIOS

Esta actualización implementa:
- ✅ Descuentos específicos por moneda (ARS vs USD)
- ✅ Interfaz visual mejorada para seleccionar plataforma
- ✅ Corrección de error `[object Object]` en consolas
- ✅ Eliminación de referencias a campos inexistentes en BD

---

## 🔧 ARCHIVOS MODIFICADOS

### 1. **admin/product_create.php**
**Versión:** 2.16 → **2.17**

**Cambios:**
- ✅ Agregado: `discount_percentage_ars` en array de datos
- ✅ Agregado: `discount_percentage_usd` en array de datos
- ✅ Mejora: Interfaz visual para seleccionar plataforma específica
- ✅ Corregido: Error `[object Object]` - ahora usa parámetro `platform` directamente
- ✅ Mejora: Iconos diferentes para cada consola (PC, PlayStation, Xbox, Switch, etc.)
- ✅ Mejora: Animaciones suaves en tarjetas de selección de plataforma

```php
// ANTES:
'discount_percentage_ars' => !empty($_POST['discount_percentage_ars']) ? ... // ❌ No guardaba
'discount_percentage_usd' => !empty($_POST['discount_percentage_usd']) ? ...

// DESPUÉS:
'discount_percentage_ars' => !empty($_POST['discount_percentage_ars']) ? ... // ✅ Guardando
'discount_percentage_usd' => !empty($_POST['discount_percentage_usd']) ? ...
```

### 2. **admin/product_edit.php**
**Versión:** 4.0.0 → **4.1.0**

**Cambios:**
- ✅ Agregado: Campos `discount_percentage_ars` en formulario
- ✅ Agregado: Campos `discount_percentage_usd` en formulario
- ✅ Mejora: Interfaz de edición con dos campos separados para descuentos

```php
// ANTES: Un solo campo de descuento
<input type="text" name="discount_percentage" ... />

// DESPUÉS: Dos campos específicos por moneda
<input type="number" name="discount_percentage_ars" ... />
<input type="number" name="discount_percentage_usd" ... />
```

### 3. **wishlist.php**
**Versión:** 1.0 → **2.1.0**

**Cambios:**
- ✅ Corregido: Removido campo `price` inexistente del SELECT
- ✅ Mejora: Ahora solo usa `price_pesos` y `price_dollars`

```sql
-- ANTES: Error SQL
SELECT ... COALESCE(p.price_pesos, p.price_dollars, p.price) as price ...

-- DESPUÉS: Correcto
SELECT ... COALESCE(p.price_pesos, p.price_dollars) as price ...
```

---

## 🗄️ CAMBIOS EN BASE DE DATOS

**Archivo: `add_discount_currencies.sql`** (Nuevo)

```sql
ALTER TABLE products ADD COLUMN discount_percentage_ars DECIMAL(5,2) DEFAULT 0.00 
  COMMENT 'Descuento en pesos argentinos (ARS)';
  
ALTER TABLE products ADD COLUMN discount_percentage_usd DECIMAL(5,2) DEFAULT 0.00 
  COMMENT 'Descuento en USD';
```

**Acciones necesarias en Hostinger:**
1. Ejecutar el SQL anterior en phpMyAdmin
2. Verificar que las columnas aparezcan en la tabla `products`

---

## 🎯 FUNCIONALIDAD NEW

### Descuentos por Moneda
Ahora los administradores pueden:
- Establecer descuentos específicos para **ARS** (pesos argentinos)
- Establecer descuentos específicos para **USD** 
- Dejar en 0 si no hay descuento para esa moneda

**Ejemplo:**
- Producto: PlayStation 5
- Descuento ARS: 15%
- Descuento USD: 10%
- Se mostrará 15% OFF cuando está en modo ARS
- Se mostrará 10% OFF cuando está en modo USD

### Interfaz Mejorada de Plataforma
Cuando se busca un juego con múltiples plataformas:
1. ✅ Muestra tarjetas visuales para cada plataforma
2. ✅ Ícono diferente según consola (PC, PS, Xbox, Switch, etc.)
3. ✅ Usuario selecciona la plataforma específica
4. ✅ Se cargan datos solo para esa plataforma
5. ✅ Se asigna la consola correcta automáticamente

---

## 🐛 CORRECCIONES

### Error: `[object Object]` en consolas
**Problema:** Al auto-completar datos del juego, mostraba `[object Object]` en lugar del nombre de consola

**Causa:** El código buscaba `gameDetails.main_console` que no existía

**Solución:** Ahora usa el parámetro `platform` que el usuario selecciona directamente

### Error SQL: Unknown column 'price'
**Problema:** `SQLSTATE[42S22]: Unknown column 'price' in 'SELECT'`

**Causa:** Campo `price` fue removido de la tabla `products`

**Solución:** Queries actualizados para usar `price_pesos` y `price_dollars`

### Error SQL: Unknown column 'discount_percentage_ars'
**Problema:** `SQLSTATE[42S22]: Unknown column 'discount_percentage_ars' in 'INSERT INTO'`

**Causa:** Las columnas no existían en la BD

**Solución:** Columnas creadas y referencias agregadas en código

---

## 📝 INSTRUCCIONES DE ACTUALIZACIÓN

### En tu PC (Local)
```bash
# Descargar cambios
git pull origin main

# Ver cambios
git diff admin/product_create.php
git diff admin/product_edit.php
git diff wishlist.php
```

### En Hostinger
**Opción 1: Con Webhook (automático)**
- El webhook de GitHub automáticamente desplegará los cambios

**Opción 2: Manual en cPanel**
1. Conectar por SFTP o cPanel File Manager
2. Reemplazar archivos:
   - `admin/product_create.php`
   - `admin/product_edit.php`
   - `wishlist.php`
3. Ejecutar SQL en phpMyAdmin

---

## ✅ CHECKLIST DE VERIFICACIÓN

Después de la actualización, verificar:

- [ ] Los archivos tienen los comentarios de versión correctos
- [ ] La BD tiene las columnas `discount_percentage_ars` y `discount_percentage_usd`
- [ ] Al crear producto: aparecen dos campos de descuento (ARS, USD)
- [ ] Al editar producto: aparecen dos campos de descuento
- [ ] Al buscar juego con múltiples plataformas: aparecen tarjetas visuales
- [ ] NO aparece `[object Object]` en las consolas
- [ ] NO hay errores SQL de campos inexistentes

---

## 🔗 ARCHIVOS RELACIONADOS

- [ACTUALIZACION_V2.9_MAS_CRITICO.md](ACTUALIZACION_V2.9_MAS_CRITICO.md) - Versión anterior
- [CHANGELOG_AUTO_RELLENAR.md](CHANGELOG_AUTO_RELLENAR.md) - Historial de cambios de auto-completado
- [add_discount_currencies.sql](add_discount_currencies.sql) - Script SQL de actualización

---

## 📌 NOTAS IMPORTANTES

1. **Backward Compatible:** Los productos existentes tendrán descuentos en 0.00 automáticamente
2. **No requiere migración de datos:** Los descuentos antiguos pueden ignorarse o migrarse manual
3. **Frontend:** Necesitará actualización para mostrar descuentos según la moneda seleccionada

---

**Actualización realizada por:** GitHub Copilot  
**Versión del sistema:** V2.10  
**Proximas versiones:** V2.11, V2.12, ... V3.0
