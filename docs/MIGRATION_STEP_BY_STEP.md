# 🔧 GUÍA DE MIGRACIÓN PASO A PASO

## ⚠️ IMPORTANTE: Error de Permisos

El error que viste es porque el usuario actual (`u851317150_mg360_user`) no tiene permisos para ejecutar algunos comandos SQL.

**Solución:** Necesitas usar el usuario **root** de MySQL en tu phpMyAdmin local.

---

## 📝 PASOS PARA EJECUTAR LA MIGRACIÓN

### ✅ PASO 1: Cambiar a Usuario Root

1. En phpMyAdmin, cierra sesión del usuario actual
2. Inicia sesión con:
   - **Usuario**: `root`
   - **Contraseña**: (deja vacío o la contraseña de tu XAMPP)
3. Selecciona la base de datos `multigamer360`

---

### ✅ PASO 2: Ejecutar Scripts en Orden

Ejecuta los siguientes archivos SQL **EN ORDEN** desde la carpeta `config/`:

#### Script 1: Crear Tabla Consoles
📄 Archivo: `migration_step1_consoles.sql`

```sql
-- Copia y pega el contenido en phpMyAdmin -> pestaña SQL -> Ejecutar
```

**Resultado esperado:** ✅ Tabla `consoles` creada

---

#### Script 2: Crear Tabla Genres
📄 Archivo: `migration_step2_genres.sql`

```sql
-- Ejecutar después del paso 1
```

**Resultado esperado:** ✅ Tabla `genres` creada

---

#### Script 3: Crear Tabla Product_Genres
📄 Archivo: `migration_step3_product_genres.sql`

```sql
-- Ejecutar después del paso 2
```

**Resultado esperado:** ✅ Tabla `product_genres` creada (relación N:N)

---

#### Script 4: Modificar Tabla Products
📄 Archivo: `migration_step4_alter_products.sql`

```sql
-- Ejecutar después del paso 3
-- IMPORTANTE: Esto modifica la tabla products
```

**Resultado esperado:** ✅ Columna `console_id` agregada a `products`

---

#### Script 5: Insertar Consolas
📄 Archivo: `migration_step5_insert_consoles.sql`

```sql
-- Ejecutar después del paso 4
-- Inserta ~30 consolas populares
```

**Resultado esperado:** ✅ 30 consolas insertadas

---

#### Script 6: Insertar Géneros
📄 Archivo: `migration_step6_insert_genres.sql`

```sql
-- Ejecutar después del paso 5
-- Inserta ~20 géneros populares
```

**Resultado esperado:** ✅ 20 géneros insertados

---

## 🎯 VERIFICAR QUE TODO SALIÓ BIEN

Después de ejecutar los 6 scripts, ejecuta estos queries para verificar:

### 1. Ver estructura de products
```sql
SHOW COLUMNS FROM products;
```

**Debe mostrar:** Columna `console_id` después de `brand_id`

### 2. Ver consolas insertadas
```sql
SELECT id, name, manufacturer FROM consoles ORDER BY name;
```

**Debe mostrar:** ~30 consolas

### 3. Ver géneros insertados
```sql
SELECT id, name, description FROM genres ORDER BY name;
```

**Debe mostrar:** ~20 géneros

### 4. Ver tablas nuevas
```sql
SHOW TABLES LIKE '%console%';
SHOW TABLES LIKE '%genre%';
```

**Debe mostrar:** 
- `consoles`
- `genres`
- `product_genres`

---

## 📊 NUEVA ESTRUCTURA

Después de la migración, tu base de datos tendrá:

```
✅ categories (existente)
✅ brands (existente)
✅ consoles (NUEVA)
✅ genres (NUEVA)
✅ product_genres (NUEVA - relación N:N)
✅ products (modificada - ahora tiene console_id)
```

---

## 🔄 ACTUALIZAR TUS PRODUCTOS ACTUALES

Después de ejecutar los 6 scripts, necesitas actualizar tus 5 productos con:

### Ejemplo: Kingdom Hearts II (PlayStation 2)

```sql
-- 1. Buscar ID de PlayStation 2
SELECT id FROM consoles WHERE name LIKE '%PlayStation 2%';
-- Resultado: id = 14

-- 2. Actualizar producto con console_id
UPDATE products 
SET console_id = 14 
WHERE name = 'Kingdom Hearts II';

-- 3. Buscar IDs de géneros
SELECT id, name FROM genres WHERE name IN ('Acción', 'RPG', 'Aventura');
-- Resultado: 1=Acción, 2=Aventura, 3=RPG

-- 4. Asignar géneros al producto
INSERT INTO product_genres (product_id, genre_id) VALUES 
(2, 1),  -- Acción
(2, 2),  -- Aventura
(2, 3);  -- RPG
```

---

## ❓ ¿NECESITAS AYUDA?

Si algún script falla:

1. **Copia el mensaje de error completo**
2. **Dime en qué paso estás**
3. **Envíame el resultado de:**
   ```sql
   SHOW TABLES;
   SHOW COLUMNS FROM products;
   ```

---

## 📝 CHECKLIST DE MIGRACIÓN

- [ ] Cambiar a usuario root en phpMyAdmin
- [ ] Ejecutar script 1 (consoles)
- [ ] Ejecutar script 2 (genres)
- [ ] Ejecutar script 3 (product_genres)
- [ ] Ejecutar script 4 (alter products)
- [ ] Ejecutar script 5 (insert consoles)
- [ ] Ejecutar script 6 (insert genres)
- [ ] Verificar estructura con queries
- [ ] Actualizar productos existentes con console_id
- [ ] Asignar géneros a productos

---

**Una vez completada la migración, podré actualizar el código PHP para usar la nueva estructura.** 🚀
