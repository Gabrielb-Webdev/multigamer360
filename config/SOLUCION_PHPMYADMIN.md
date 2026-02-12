# Solución a los Errores de phpMyAdmin

## 📊 Lo que Descubrimos

Basado en los errores que viste:

### ✅ Categorías
- **`parent_id`** → ✅ YA EXISTE (error #1060 "Nombre duplicado")
- **`sort_order`** → ❌ Necesita agregarse

### ❌ Usuarios  
- **`password_reset_expires`** → ❌ NO EXISTE en tu base de datos
- Por eso falló el `AFTER password_reset_expires`

---

## 🔧 Solución: Usar Versión Corregida

He creado un nuevo archivo: **`agregar_columnas_CORREGIDO.sql`**

Este archivo:
- ✅ No intenta agregar `parent_id` (ya existe)
- ✅ Agrega `last_login` al FINAL de la tabla users (sin usar AFTER)
- ✅ Solo agrega las columnas que realmente faltan

---

## 🎯 Cómo Ejecutarlo

### Opción A: Ejecutar Todo de Una Vez

1. Abre phpMyAdmin
2. Selecciona tu base de datos `u851317150_mg360_db`
3. Ve a la pestaña **SQL**
4. Copia y pega TODO este código:

```sql
-- Categorías: solo sort_order (parent_id ya existe)
ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;

-- Marcas: is_featured y sort_order
ALTER TABLE brands ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER is_active;
ALTER TABLE brands ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;

-- Productos: nivel mínimo de stock
ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 10 AFTER stock_quantity;

-- Usuarios: último login (al final de la tabla)
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
```

5. Haz clic en **Continuar**

---

### Opción B: Ejecutar Línea por Línea

Si prefieres ir con cuidado, ejecuta una por una:

#### 1️⃣ Categories - sort_order
```sql
ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;
```
**Resultado esperado:** "Query OK, X rows affected"  
**Error "Duplicate column":** Ya existía, ignóralo

---

#### 2️⃣ Brands - is_featured
```sql
ALTER TABLE brands ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER is_active;
```
**Resultado esperado:** "Query OK, X rows affected"

---

#### 3️⃣ Brands - sort_order  
```sql
ALTER TABLE brands ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;
```
**Resultado esperado:** "Query OK, X rows affected"

---

#### 4️⃣ Products - min_stock_level
```sql
ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 10 AFTER stock_quantity;
```
**Resultado esperado:** "Query OK, X rows affected"

---

#### 5️⃣ Users - last_login
```sql
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
```
**Resultado esperado:** "Query OK, X rows affected"

**NOTA:** Esta columna se agregará al final de la tabla, no importa la posición.

---

## ✅ Verificar que Funcionó

Después de ejecutar los SQL, verifica:

### Categories
```sql
DESCRIBE categories;
```
Deberías ver:
- ✅ `parent_id` (ya existía)
- ✅ `sort_order` (recién agregada)

### Brands
```sql
DESCRIBE brands;
```
Deberías ver:
- ✅ `is_featured`
- ✅ `sort_order`

### Products
```sql
DESCRIBE products;
```
Deberías ver:
- ✅ `min_stock_level`

### Users
```sql
DESCRIBE users;
```
Deberías ver:
- ✅ `last_login` (al final)

---

## 🚨 Errores Comunes y Soluciones

### Error: "Duplicate column name"
**Causa:** La columna ya existe  
**Solución:** ✅ Ignóralo, está perfecto

### Error: "Unknown column in field list"  
**Causa:** Intentas usar AFTER con una columna que no existe  
**Solución:** Usa el archivo `agregar_columnas_CORREGIDO.sql` que no usa AFTER para users

### Error: "Can't DROP... check that column/key exists"
**Causa:** Intentas eliminar algo que no existe  
**Solución:** Ignóralo

---

## 📋 Resumen de lo que Necesitas

Según los errores que viste, necesitas ejecutar:

```sql
-- 1. Sort order en categories
ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;

-- 2. Marcas destacadas
ALTER TABLE brands ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER is_active;
ALTER TABLE brands ADD COLUMN sort_order INT DEFAULT 0 AFTER slug;

-- 3. Stock mínimo en productos
ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 10 AFTER stock_quantity;

-- 4. Último login en usuarios
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
```

---

## 💡 ¿Por Qué Falló Antes?

### Problema 1: IF NOT EXISTS
Tu versión de MySQL/MariaDB no soporta `IF NOT EXISTS` en ALTER TABLE ADD COLUMN.

**Solución:** Quitamos esa sintaxis.

### Problema 2: AFTER password_reset_expires  
Esa columna no existe en tu tabla users.

**Solución:** Agregamos `last_login` al final sin usar AFTER.

### Problema 3: parent_id ya existe
Ya fue agregado anteriormente.

**Solución:** Solo agregamos las columnas que faltan.

---

## ✅ Archivo a Usar

**Usa este archivo:** `config/agregar_columnas_CORREGIDO.sql`

Este archivo está adaptado específicamente a tu base de datos actual.

---

## 🆘 Si Sigues con Problemas

Copia el error completo que te muestre phpMyAdmin y avísame exactamente:
1. Qué línea SQL ejecutaste
2. Qué error te dio
3. En qué tabla

¡Te ayudaré a solucionarlo! 🚀
