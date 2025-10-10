# SISTEMA DE DESCUENTOS POR PORCENTAJE - MultiGamer360

## 📋 RESUMEN DEL CAMBIO

Se ha reemplazado el sistema de "Precio de Oferta" fijo por un **Sistema de Descuentos por Porcentaje** más flexible y fácil de gestionar.

---

## 🆕 ¿QUÉ CAMBIÓ?

### ANTES (Sistema Antiguo)
```
❌ Campo: "Precio de Oferta (COP)"
❌ Usuario ingresaba precio final manualmente
❌ Había que calcular mentalmente el descuento
❌ Propenso a errores de cálculo
❌ Difícil ver el % de descuento real
```

### AHORA (Sistema Nuevo)
```
✅ Checkbox: "Producto en Oferta"
✅ Campo: "Porcentaje de Descuento (%)"
✅ Cálculo automático del precio final
✅ Vista previa con precio tachado
✅ Muestra ahorro y porcentaje claramente
✅ Validación automática (0-100%)
```

---

## 🗄️ CAMBIOS EN BASE DE DATOS

### Script SQL a Ejecutar
```sql
-- Archivo: config/agregar_descuento_porcentaje.sql

-- 1. Agregar columna para marcar si está en oferta
ALTER TABLE products 
ADD COLUMN is_on_sale BOOLEAN DEFAULT FALSE 
COMMENT 'Indica si el producto está en oferta';

-- 2. Agregar columna para el porcentaje
ALTER TABLE products 
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00 
COMMENT 'Porcentaje de descuento (0-100)';

-- 3. Eliminar columna antigua (ya no se usa)
ALTER TABLE products DROP COLUMN IF EXISTS offer_price;

-- 4. Crear índice para optimizar búsquedas
CREATE INDEX idx_on_sale ON products(is_on_sale);
```

### Nueva Estructura de `products`
```
id
name
slug
description
short_description
sku
price_pesos            ← Precio original
price_dollars
is_on_sale             ← NUEVO: ¿Está en oferta?
discount_percentage    ← NUEVO: % de descuento
stock_quantity
category_id
brand_id
console_id
is_featured
is_active
status
meta_title
meta_description
created_at
updated_at
```

---

## 🎨 NUEVA INTERFAZ DEL FORMULARIO

### Switch "Producto en Oferta"
```
┌─────────────────────────────────────────┐
│ [●─────] Producto en Oferta            │
│          (Switch encendido = En oferta)│
└─────────────────────────────────────────┘
```

### Campo de Porcentaje (solo visible si está en oferta)
```
┌─────────────────────────────────────────┐
│ Porcentaje de Descuento     %           │
│ ┌────────────┐                          │
│ │    20      │ %                        │
│ └────────────┘                          │
│ Ingrese el porcentaje (0-100%)          │
│                                         │
│ ┌──────────────────────────────────┐   │
│ │ ✅ Descuento: 20%                │   │
│ │    Ahorro: $50,000               │   │
│ │                                  │   │
│ │ $250,000  →  $200,000            │   │
│ └──────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

---

## 💻 CÓMO FUNCIONA EL SISTEMA

### Fórmula de Cálculo
```javascript
precio_final = precio_pesos - (precio_pesos × discount_percentage / 100)

Ejemplo:
Precio Original:    $250,000 COP
Descuento:          20%
Cálculo:            $250,000 - ($250,000 × 20 / 100)
                  = $250,000 - $50,000
Precio Final:       $200,000 COP
```

### Validaciones Automáticas
```javascript
✅ Porcentaje debe estar entre 0 y 100
✅ Si no está "En Oferta", el descuento es 0
✅ Vista previa se actualiza en tiempo real
✅ Formato de moneda con separador de miles
```

---

## 🎯 FLUJO DE USO

### Crear Producto con Descuento

1. **Llenar información básica**
   ```
   Nombre: God of War Ragnarök
   Precio: $250,000 COP
   ```

2. **Activar oferta**
   ```
   [✓] Producto en Oferta
   ```

3. **Ingresar porcentaje**
   ```
   Descuento: 20%
   ```

4. **Ver vista previa**
   ```
   ✅ Descuento: 20%
      Ahorro: $50,000
   
   $250,000  →  $200,000
   ```

5. **Guardar producto**
   ```
   Sistema guarda:
   - is_on_sale = 1
   - discount_percentage = 20.00
   ```

### Quitar Descuento de Producto

1. **Desactivar switch**
   ```
   [ ] Producto en Oferta
   ```

2. **Sistema automáticamente:**
   ```
   - Oculta campo de porcentaje
   - Limpia vista previa
   - Al guardar: is_on_sale = 0, discount_percentage = 0
   ```

---

## 📊 COMPARACIÓN ANTES/DESPUÉS

| Característica | Antes | Ahora |
|----------------|-------|-------|
| **Facilidad** | ⚠️ Calcular precio manualmente | ✅ Solo % y se calcula solo |
| **Errores** | ⚠️ Posibles errores de cálculo | ✅ Sin errores, todo automático |
| **Claridad** | ⚠️ No se ve % de descuento | ✅ % visible prominentemente |
| **Flexibilidad** | ⚠️ Cambiar precio para cambiar % | ✅ Cambiar % y listo |
| **Vista Previa** | ⚠️ Solo ahorro en pesos | ✅ %, ahorro, precio tachado |
| **Validación** | ⚠️ Manual (oferta < precio) | ✅ Automática (0-100%) |

---

## 🔍 CONSULTAS SQL ÚTILES

### Ver Productos en Oferta
```sql
SELECT 
    id,
    name,
    price_pesos as precio_original,
    discount_percentage as descuento,
    ROUND(price_pesos - (price_pesos * discount_percentage / 100), 2) as precio_final,
    ROUND((price_pesos * discount_percentage / 100), 2) as ahorro
FROM products
WHERE is_on_sale = TRUE
ORDER BY discount_percentage DESC;
```

### Productos con Mayor Descuento
```sql
SELECT 
    name,
    discount_percentage as descuento,
    price_pesos as original,
    ROUND(price_pesos - (price_pesos * discount_percentage / 100), 2) as final
FROM products
WHERE is_on_sale = TRUE
ORDER BY discount_percentage DESC
LIMIT 10;
```

### Productos en Oferta por Categoría
```sql
SELECT 
    c.name as categoria,
    COUNT(*) as productos_en_oferta,
    AVG(p.discount_percentage) as descuento_promedio
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.is_on_sale = TRUE
GROUP BY c.id, c.name;
```

---

## 🎨 EJEMPLOS VISUALES

### Producto SIN Oferta
```
Precio en Pesos (COP): $250,000 COP

[ ] Producto en Oferta

(Campo de porcentaje oculto)
```

### Producto CON Oferta 10%
```
Precio en Pesos (COP): $250,000 COP

[✓] Producto en Oferta

Porcentaje de Descuento: 10%

┌────────────────────────────┐
│ ✅ Descuento: 10%          │
│    Ahorro: $25,000         │
│                            │
│ $250,000  →  $225,000      │
└────────────────────────────┘
```

### Producto CON Oferta 50%
```
Precio en Pesos (COP): $250,000 COP

[✓] Producto en Oferta

Porcentaje de Descuento: 50%

┌────────────────────────────┐
│ ✅ Descuento: 50%          │
│    Ahorro: $125,000        │
│                            │
│ $250,000  →  $125,000      │
└────────────────────────────┘
```

---

## 🚀 PASOS PARA IMPLEMENTAR

### 1. Ejecutar SQL en Hostinger
```bash
1. Ir a phpMyAdmin en Hostinger
2. Seleccionar base de datos: u851317150_mg360_db
3. Ir a pestaña SQL
4. Copiar contenido de: config/agregar_descuento_porcentaje.sql
5. Pegar y ejecutar
6. Verificar: DESCRIBE products;
```

### 2. Subir Archivo Actualizado
```bash
# Subir vía FTP o File Manager:
admin/product_edit.php
```

### 3. Probar Funcionalidad
```bash
1. Ir a: admin/product_edit.php
2. Crear producto nuevo
3. Activar "Producto en Oferta"
4. Ingresar porcentaje: 20%
5. Ver vista previa del descuento
6. Guardar producto
7. Verificar en BD:
   SELECT * FROM products WHERE is_on_sale = 1;
```

---

## ✅ CHECKLIST DE VALIDACIÓN

### Base de Datos
- [ ] Ejecutado SQL en phpMyAdmin
- [ ] Columna `is_on_sale` existe
- [ ] Columna `discount_percentage` existe
- [ ] Columna `offer_price` eliminada (opcional)
- [ ] Índice `idx_on_sale` creado

### Interfaz
- [ ] Switch "Producto en Oferta" visible
- [ ] Campo de porcentaje aparece al activar switch
- [ ] Campo de porcentaje se oculta al desactivar
- [ ] Vista previa muestra cálculo correcto
- [ ] Formato de moneda con separador de miles

### Funcionalidad
- [ ] Guardar producto con oferta (is_on_sale=1)
- [ ] Guardar producto sin oferta (is_on_sale=0)
- [ ] Validación: % entre 0-100
- [ ] Cálculo correcto del precio final
- [ ] Editar producto existente mantiene valores

### JavaScript
- [ ] No hay errores en consola
- [ ] Switch funciona correctamente
- [ ] Vista previa se actualiza al escribir
- [ ] Formato de moneda correcto (es-CO)

---

## 🎓 VENTAJAS DEL NUEVO SISTEMA

### Para Administradores
```
✅ Más rápido: Solo ingresar %
✅ Sin errores: Cálculo automático
✅ Visual: Vista previa clara
✅ Flexible: Cambiar % sin recalcular precio
✅ Profesional: Formato de moneda correcto
```

### Para Clientes (Frontend)
```
✅ Claridad: "20% OFF" es más claro
✅ Confianza: Ver % de descuento real
✅ Atractivo: Precio tachado + precio final
✅ Comparación: Fácil comparar % entre productos
```

### Para Marketing
```
✅ Campañas: "Hasta 50% OFF"
✅ Destacar: Productos con mayor %
✅ Filtros: Buscar por rango de descuento
✅ Estadísticas: % promedio de descuentos
```

---

## 📈 USO EN FRONTEND (Para Desarrollador)

### PHP: Obtener Precio con Descuento
```php
<?php
function getPrecioConDescuento($product) {
    if ($product['is_on_sale'] && $product['discount_percentage'] > 0) {
        $descuento = $product['price_pesos'] * $product['discount_percentage'] / 100;
        return $product['price_pesos'] - $descuento;
    }
    return $product['price_pesos'];
}

// Uso:
$precio_final = getPrecioConDescuento($producto);
?>
```

### HTML: Mostrar Producto con Descuento
```html
<?php if ($product['is_on_sale']): ?>
    <div class="badge bg-danger">
        <?php echo $product['discount_percentage']; ?>% OFF
    </div>
    <div class="price">
        <del>$<?php echo number_format($product['price_pesos']); ?></del>
        <strong>$<?php echo number_format(getPrecioConDescuento($product)); ?></strong>
    </div>
<?php else: ?>
    <div class="price">
        $<?php echo number_format($product['price_pesos']); ?>
    </div>
<?php endif; ?>
```

---

## 🐛 TROUBLESHOOTING

### Error: "Unknown column 'is_on_sale'"
**Solución:** Ejecutar el SQL script primero
```sql
ALTER TABLE products ADD COLUMN is_on_sale BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00;
```

### Campo de porcentaje no aparece
**Solución:** Verificar JavaScript en consola
```javascript
// En consola del navegador:
console.log(document.getElementById('is_on_sale'));
console.log(document.getElementById('discount-section'));
```

### Descuento no se calcula
**Solución:** Verificar valores
```javascript
console.log(document.getElementById('price_pesos').value);
console.log(document.getElementById('discount_percentage').value);
console.log(document.getElementById('is_on_sale').checked);
```

---

## 📚 ARCHIVOS MODIFICADOS

```
✅ config/agregar_descuento_porcentaje.sql (NUEVO)
✅ admin/product_edit.php (ACTUALIZADO)
   - PHP: Campos is_on_sale y discount_percentage
   - HTML: Switch y campo de porcentaje
   - JavaScript: Cálculo automático y validación
✅ docs/SISTEMA_DESCUENTOS_PORCENTAJE.md (ESTE ARCHIVO)
```

---

**Fecha de Implementación:** Diciembre 2024  
**Versión:** 2.1  
**Sistema:** Descuentos por Porcentaje  
**Estado:** ✅ LISTO PARA IMPLEMENTAR

**Próximo Paso:** Ejecutar `config/agregar_descuento_porcentaje.sql` en Hostinger
