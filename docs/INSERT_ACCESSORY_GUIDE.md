# 🎮 GUÍA: SUBIR ACCESORIO DE EJEMPLO

## 📦 Producto a Insertar

**Nombre**: Control DualShock 2 Original Sony  
**Categoría**: Accesorios  
**Marca**: Sony  
**Consola**: PlayStation 2  
**Precio**: $29.99 → **$24.99** (en oferta 16% OFF)  
**Stock**: 8 unidades  
**SKU**: ACC-PS2-DS2-001  
**Condición**: Usado (como nuevo)  
**Imagen**: https://images.unsplash.com/photo-1592840496694-26d035b52b48?w=500

---

## 🚀 PASOS PARA INSERTAR EN HOSTINGER

### **PASO 1: Crear Categoría "Accesorios"**

1. Ve a phpMyAdmin de Hostinger
2. Selecciona la base de datos `u851317150_mg360`
3. Click en pestaña **SQL**
4. Copia y pega esto:

```sql
INSERT INTO categories (name, slug, description, icon, is_active) 
VALUES ('Accesorios', 'accesorios', 'Accesorios y periféricos para consolas', 'fa-gamepad', 1)
ON DUPLICATE KEY UPDATE name=name;
```

5. Click en **"Continuar"**
6. ✅ **Resultado esperado**: "1 fila insertada" (o "0 filas afectadas" si ya existe)

---

### **PASO 2: Insertar el Accesorio**

1. En la misma pestaña **SQL** de phpMyAdmin
2. Copia y pega esto:

```sql
INSERT INTO products (
    name, 
    slug, 
    description, 
    short_description, 
    price, 
    sale_price, 
    stock_quantity, 
    sku, 
    category_id, 
    brand_id, 
    console_id,
    image_url, 
    is_featured, 
    is_active, 
    condition_product
) VALUES (
    'Control DualShock 2 Original Sony',
    'control-dualshock-2-ps2-negro',
    'Control inalámbrico DualShock 2 original de Sony para PlayStation 2. Incluye función de vibración dual, gatillos analógicos sensibles a la presión y máximo confort ergonómico. Compatible con todos los juegos de PS2. Estado: Como nuevo, probado y funcional.',
    'Control original Sony DualShock 2 para PS2. Vibración dual, gatillos analógicos.',
    29.99,
    24.99,
    8,
    'ACC-PS2-DS2-001',
    (SELECT id FROM categories WHERE slug = 'accesorios' LIMIT 1),
    (SELECT id FROM brands WHERE name = 'Sony' LIMIT 1),
    (SELECT id FROM consoles WHERE name = 'PlayStation 2' LIMIT 1),
    'https://images.unsplash.com/photo-1592840496694-26d035b52b48?w=500',
    0,
    1,
    'used'
);
```

3. Click en **"Continuar"**
4. ✅ **Resultado esperado**: "1 fila insertada"

---

### **PASO 3: Verificar Inserción**

1. En la misma pestaña **SQL**
2. Copia y pega esto:

```sql
SELECT 
    p.id,
    p.name,
    p.sku,
    p.price,
    p.sale_price,
    p.stock_quantity,
    c.name as categoria,
    b.name as marca,
    co.name as consola,
    p.is_active,
    p.condition_product
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN brands b ON p.brand_id = b.id
LEFT JOIN consoles co ON p.console_id = co.id
WHERE p.slug = 'control-dualshock-2-ps2-negro';
```

3. ✅ **Deberías ver**:

| id | name | sku | price | sale_price | stock_quantity | categoria | marca | consola | is_active | condition_product |
|----|------|-----|-------|------------|----------------|-----------|-------|---------|-----------|-------------------|
| X | Control DualShock 2 Original Sony | ACC-PS2-DS2-001 | 29.99 | 24.99 | 8 | Accesorios | Sony | PlayStation 2 | 1 | used |

---

## 🧪 VERIFICAR EN EL SITIO WEB

### **Opción 1: Ver en página de productos**

1. Abre: `https://teal-fish-507993.hostingersite.com/productos.php`
2. **Resultado esperado**:
   - Ver 6 productos (5 juegos + 1 accesorio)
   - El control debe aparecer con:
     - ✅ Imagen del control
     - ✅ Precio tachado: ~~$29.99~~ → **$24.99**
     - ✅ Badge "OFERTA 16% OFF"
     - ✅ Badge "USADO"

### **Opción 2: Filtrar por categoría**

1. En `productos.php`, marca el filtro **"Accesorios"** en CATEGORÍAS
2. Clic en **"Aplicar Filtros"**
3. **Resultado esperado**: Solo debe aparecer el control

### **Opción 3: Filtrar por consola**

1. Marca el filtro **"PlayStation 2"** en CONSOLAS
2. **Resultado esperado**: 
   - Kingdom Hearts 2
   - Control DualShock 2
   - (Otros productos de PS2)

---

## 📊 DATOS DEL ACCESORIO

### **Características Principales:**
- ✅ **En Oferta**: 16% descuento ($29.99 → $24.99)
- ✅ **Usado**: Estado como nuevo
- ✅ **Compatible**: PlayStation 2
- ✅ **Stock**: 8 unidades disponibles
- ✅ **Categoría Nueva**: Accesorios (se crea automáticamente)

### **Filtros que Aplican:**
- **Categoría**: Accesorios
- **Marca**: Sony
- **Consola**: PlayStation 2
- **Géneros**: Ninguno (los accesorios no tienen géneros)

---

## 🎨 CÓMO SE VERÁ EN LA PÁGINA

```
┌─────────────────────────────┐
│  [Imagen del Control PS2]   │
│                             │
│  Control DualShock 2        │
│  Original Sony              │
│                             │
│  🏷️ OFERTA 16% OFF          │
│  ♻️ USADO                    │
│                             │
│  $29.99 → $24.99            │
│                             │
│  [💝 Favoritos] [🛒 Carrito]│
└─────────────────────────────┘
```

---

## 🔧 TROUBLESHOOTING

### ❌ **Error: "Duplicate entry for key 'slug'"**
**Causa**: Ya existe un producto con ese slug  
**Solución**: Cambia el slug en el INSERT:
```sql
slug = 'control-dualshock-2-ps2-negro-v2'
```

### ❌ **Error: "Cannot add or update a child row"**
**Causa**: No existe la marca "Sony" o la consola "PlayStation 2"  
**Solución**: Verifica que ejecutaste los scripts de migración correctamente

### ❌ **No aparece en productos.php**
**Causa**: `is_active = 0` o no tiene categoría  
**Solución**: Ejecuta:
```sql
UPDATE products SET is_active = 1 WHERE slug = 'control-dualshock-2-ps2-negro';
```

---

## ✅ CHECKLIST

- [ ] Ejecutar PASO 1 en phpMyAdmin (crear categoría)
- [ ] Ejecutar PASO 2 en phpMyAdmin (insertar producto)
- [ ] Ejecutar PASO 3 en phpMyAdmin (verificar)
- [ ] Abrir productos.php y ver el accesorio
- [ ] Probar filtros (Accesorios, PlayStation 2, Sony)
- [ ] Verificar que muestre badge "OFERTA" y "USADO"

---

¡Listo! Una vez insertado, tendrás 6 productos en total (5 juegos + 1 accesorio) 🎉
