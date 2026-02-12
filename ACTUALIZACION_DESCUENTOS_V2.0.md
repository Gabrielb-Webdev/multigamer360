# 🔥 Actualización Sistema de Descuentos - Documentación

## 📋 Cambios Implementados

### ✅ 1. Cambio de Moneda Principal
- **Antes:** Precio (COP) - Pesos Colombianos
- **Ahora:** Precio (ARS) - Pesos Argentinos

### ✅ 2. Reubicación del Checkbox "En Oferta"
- **Antes:** Estaba en la sección "Producto Destacado" (sidebar)
- **Ahora:** Está dentro de la card "Precios e Inventario" justo después de los precios

### ✅ 3. Descuentos Separados por Moneda
Ahora puedes configurar descuentos diferentes para cada moneda:
- **Descuento ARS (%)**: Aplica al precio en Pesos Argentinos
- **Descuento USD (%)**: Aplica al precio en Dólares

## 🚀 Instrucciones de Instalación

### Opción 1: Script PHP Automático (Recomendado)

1. Ve a tu navegador y accede a:
   ```
   https://hostingersite.com/admin/add_discount_columns.php
   ```

2. Verás una página con información sobre los cambios

3. Haz clic en **"Ejecutar Actualización"**

4. Espera la confirmación de éxito ✅

5. ¡Listo! Ya puedes crear productos con descuentos separados

### Opción 2: SQL Manual en phpMyAdmin

1. Accede a phpMyAdmin en Hostinger

2. Selecciona la base de datos `u851317150_mg360_db`

3. Ve a la pestaña **SQL**

4. Copia y pega el contenido del archivo `add_discount_columns.sql`

5. Haz clic en **"Continuar"** o **"Ejecutar"**

6. Verifica que aparezca el mensaje de éxito

## 💡 Cómo Usar el Nuevo Sistema

### Crear Producto con Descuento

1. **Llenar información básica del producto**
   - Nombre, descripción, etc.

2. **Configurar precios** (en la sección "Precios e Inventario"):
   - **Precio (ARS)**: Ej: `25000.00` pesos argentinos
   - **Precio (USD)**: Ej: `54.00` dólares

3. **Activar descuento**:
   - Marca el checkbox **🔥 En Oferta**

4. **Configurar porcentajes de descuento**:
   - **Descuento (ARS) %**: Ej: `15` (15% de descuento)
   - **Descuento (USD) %**: Ej: `10` (10% de descuento)

5. **Vista previa automática**:
   - El sistema muestra el precio final en tiempo real
   - Ejemplo ARS: `$25,000.00 → $21,250.00 -15%`
   - Ejemplo USD: `$54.00 → $48.60 -10%`

### Ejemplos Prácticos

#### Ejemplo 1: Mismo descuento en ambas monedas
```
Precio ARS: $30,000
Precio USD: $65.00
Descuento ARS: 20%
Descuento USD: 20%

Resultado:
- Precio final ARS: $24,000.00 (ahorro $6,000)
- Precio final USD: $52.00 (ahorro $13.00)
```

#### Ejemplo 2: Descuentos diferentes
```
Precio ARS: $45,000
Precio USD: $98.00
Descuento ARS: 25%
Descuento USD: 15%

Resultado:
- Precio final ARS: $33,750.00 (ahorro $11,250)
- Precio final USD: $83.30 (ahorro $14.70)
```

#### Ejemplo 3: Descuento solo en ARS
```
Precio ARS: $18,000
Precio USD: $39.00
Descuento ARS: 30%
Descuento USD: 0%

Resultado:
- Precio final ARS: $12,600.00 (ahorro $5,400)
- Precio final USD: $39.00 (sin descuento)
```

## 📊 Estructura de Base de Datos

### Nuevas Columnas Agregadas:

```sql
discount_percentage_ars  DECIMAL(5,2)  -- Valores: 0.00 a 999.99
discount_percentage_usd  DECIMAL(5,2)  -- Valores: 0.00 a 999.99
```

### Valores Permitidos:
- **Mínimo**: `0.00` (sin descuento)
- **Máximo**: `100.00` (100% de descuento - producto gratis)
- **Decimales**: Soporta hasta 2 decimales (ej: `15.50`)

## 🔍 Validación del Sistema

Para verificar que todo funciona correctamente:

1. **Crear un producto de prueba:**
   - Nombre: "Producto Test"
   - Precio ARS: $10,000
   - Precio USD: $25.00
   - Activar "En Oferta"
   - Descuento ARS: 10%
   - Descuento USD: 5%

2. **Verificar en la base de datos:**
   ```sql
   SELECT 
       name, 
       price_pesos, 
       price_dollars,
       is_on_sale,
       discount_percentage_ars,
       discount_percentage_usd
   FROM products 
   WHERE name = 'Producto Test';
   ```

3. **Resultado esperado:**
   ```
   name: Producto Test
   price_pesos: 10000.00
   price_dollars: 25.00
   is_on_sale: 1
   discount_percentage_ars: 10.00
   discount_percentage_usd: 5.00
   ```

## ⚠️ Notas Importantes

- **Migración automática**: Si tenías descuentos en la columna antigua `discount_percentage`, se migrarán automáticamente a `discount_percentage_ars`

- **Compatibilidad**: Los productos existentes sin descuentos tendrán `0.00` en ambas columnas

- **Frontend**: Asegúrate de actualizar también las vistas del frontend (productos.php, product-details.php) para mostrar los descuentos correctamente

## 🐛 Solución de Problemas

### Error: "Unknown column 'discount_percentage_ars'"
**Solución:** Ejecutar el script de base de datos nuevamente

### Los descuentos no se guardan
**Solución:** Verificar que los campos `name="discount_percentage_ars"` y `name="discount_percentage_usd"` existan en el formulario

### La vista previa no funciona
**Solución:** Verificar la consola del navegador (F12) para errores JavaScript

## 📞 Soporte

Si tienes problemas con la actualización:
1. Revisa los logs de PHP
2. Verifica la consola del navegador (F12)
3. Comprueba que las columnas se crearon correctamente en phpMyAdmin

---

**Fecha de actualización:** Febrero 2026  
**Versión:** 2.0 - Sistema de Descuentos Multi-Moneda
