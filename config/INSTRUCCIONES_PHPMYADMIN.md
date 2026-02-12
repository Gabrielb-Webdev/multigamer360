# Cómo Agregar las Columnas Faltantes en phpMyAdmin

## 🚨 Problema que viste

```
#1044 - Acceso denegado para usuario: 'u851317150_mg360_user'@'127.0.0.1' 
a la base de datos 'information_schema'
```

**Causa:** El script intentaba verificar si las columnas existen consultando `information_schema`, pero tu usuario de base de datos no tiene permisos para acceder a esa base de datos del sistema.

---

## ✅ Solución: Usar Script Simplificado

He creado **3 nuevos archivos SQL** más simples:

### 📄 Opción 1: `agregar_columnas_RAPIDO.sql` (RECOMENDADO)

**Usar si:** Tienes MySQL 5.7 o superior / MariaDB 10.2 o superior

**Cómo ejecutar:**
1. Abre phpMyAdmin
2. Selecciona tu base de datos `u851317150_mg360_db`
3. Ve a la pestaña **SQL**
4. Abre el archivo `config/agregar_columnas_RAPIDO.sql`
5. Copia TODO el contenido
6. Pégalo en el cuadro SQL
7. Haz clic en **Continuar**

✅ Este archivo usa `IF NOT EXISTS` para evitar errores si la columna ya existe.

---

### 📄 Opción 2: `agregar_columnas_SIMPLE.sql` (ALTERNATIVA)

**Usar si:** La Opción 1 no funciona o tienes MySQL antiguo

**Cómo ejecutar:**
1. Abre el archivo `config/agregar_columnas_SIMPLE.sql`
2. **NO copies todo** - solo copia las líneas que necesites
3. Por ejemplo, para agregar `parent_id` a categories, copia solo:
   ```sql
   ALTER TABLE categories ADD COLUMN parent_id INT NULL AFTER id;
   ```
4. Pégala en phpMyAdmin → SQL
5. Haz clic en **Continuar**
6. Si ves error `Duplicate column name` - **está bien**, significa que ya existía

---

## 📋 Qué Columnas Necesitas Agregar

Basado en los errores que tuviste, necesitas:

### ✅ 1. Para Categories (jerarquía padre-hijo)
```sql
ALTER TABLE categories ADD COLUMN parent_id INT NULL AFTER id;
ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;
```

### ✅ 2. Para Brands (marcas destacadas)
```sql
ALTER TABLE brands ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER is_active;
ALTER TABLE brands ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;
```

### ✅ 3. Para Products (nivel de stock mínimo)
```sql
ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 10 AFTER stock_quantity;
```

### ✅ 4. Para Users (último login)
```sql
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER password_reset_expires;
```

---

## 🎯 Método Rápido (Recomendado)

### Paso a Paso:

1. **Abre phpMyAdmin** en tu navegador
2. **Selecciona tu base de datos** `u851317150_mg360_db` en el panel izquierdo
3. **Haz clic en la pestaña SQL** (arriba)
4. **Copia y pega** este código:

```sql
-- Copiar desde aquí ↓

-- Categorías con jerarquía
ALTER TABLE categories ADD COLUMN parent_id INT NULL AFTER id;
ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;

-- Marcas destacadas  
ALTER TABLE brands ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER is_active;
ALTER TABLE brands ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;

-- Nivel mínimo de stock
ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 10 AFTER stock_quantity;

-- Último login
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER password_reset_expires;

-- Copiar hasta aquí ↑
```

5. **Haz clic en el botón "Continuar"** (abajo a la derecha)

---

## ⚠️ Errores Esperados (son NORMALES)

Si ves alguno de estos errores, **NO te preocupes**:

### ❌ Error: `Duplicate column name 'parent_id'`
**Significado:** La columna ya existe  
**Acción:** Ignóralo, está bien

### ❌ Error: `#1072 - Key column 'parent_id' doesn't exist in table`
**Significado:** Intentaste agregar un índice antes de crear la columna  
**Acción:** Primero ejecuta el ALTER TABLE ADD COLUMN, luego el ADD INDEX

### ✅ Mensaje: `Query OK, 0 rows affected`
**Significado:** ¡Perfecto! La columna se agregó correctamente

---

## 🔍 Verificar que Funcionó

Después de ejecutar los SQL, verifica:

1. **Tabla categories:**
   - Ve a phpMyAdmin → categories → Estructura
   - Deberías ver `parent_id` y `sort_order`

2. **Tabla brands:**
   - Ve a phpMyAdmin → brands → Estructura  
   - Deberías ver `is_featured` y `sort_order`

3. **Tabla products:**
   - Ve a phpMyAdmin → products → Estructura
   - Deberías ver `min_stock_level`

4. **Tabla users:**
   - Ve a phpMyAdmin → users → Estructura
   - Deberías ver `last_login`

---

## 🚀 Después de Agregar las Columnas

Una vez agregadas las columnas, el sistema funcionará completamente:

- ✅ **Categories:** Podrás crear subcategorías
- ✅ **Brands:** Podrás marcar marcas como destacadas
- ✅ **Inventory:** Cada producto tendrá su nivel mínimo de stock
- ✅ **Users:** Se registrará el último login de cada usuario

**IMPORTANTE:** Los archivos PHP ya están preparados para trabajar CON o SIN estas columnas, así que el sistema funciona ahora y seguirá funcionando después de agregarlas.

---

## 📞 ¿Sigues con Problemas?

Si ves un error diferente a los mencionados, anota:
1. El mensaje de error completo
2. Qué línea SQL estabas ejecutando
3. Mándame esa información y te ayudo

---

## 📚 Archivos Creados

- ✅ `config/agregar_columnas_RAPIDO.sql` - Versión rápida con IF NOT EXISTS
- ✅ `config/agregar_columnas_SIMPLE.sql` - Versión manual con instrucciones
- ✅ `config/INSTRUCCIONES_PHPMYADMIN.md` - Este archivo con guía paso a paso
