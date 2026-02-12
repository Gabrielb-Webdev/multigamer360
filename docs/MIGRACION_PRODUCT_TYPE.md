# Migración: Agregar Tipo de Producto

## 📋 Descripción
Esta migración agrega la columna `product_type` a la tabla `products` para diferenciar entre:
- **juego** - Videojuegos
- **consola** - Consolas de videojuegos
- **accesorio** - Accesorios (controles, cables, memory cards, etc.)

## 🚀 Ejecutar Migración

### Opción 1: Desde el navegador
1. Ir a: `https://tu-dominio.com/config/add_product_type_column.php`
2. Verificar que aparezca: ✅ Migración completada exitosamente

### Opción 2: Desde línea de comandos
```bash
php config/add_product_type_column.php
```

## 🔍 Verificar Migración

Ejecuta en tu base de datos:
```sql
SHOW COLUMNS FROM products LIKE 'product_type';
```

Deberías ver:
```
Field: product_type
Type: enum('game','console','accessory')
Default: game
```

## 📊 Impacto

### Cambios en la Base de Datos:
- ✅ Nueva columna `product_type` en tabla `products`
- ✅ Índice `idx_product_type` para búsquedas rápidas
- ✅ Productos existentes marcados como 'game' automáticamente

### Cambios en el Sistema de Importación:
- ✅ Excel ahora incluye columna "tipo_producto"
- ✅ Opciones: juego, consola, accesorio
- ✅ Modal de revisión muestra tipo con badge de color
- ✅ Filtros mejorados por tipo de producto

## 🎨 Colores en el Modal

El tipo de producto se muestra con badges de colores:
- 🟢 **Juego** - Verde (bg-success)
- 🔵 **Consola** - Azul (bg-primary)
- 🟡 **Accesorio** - Amarillo (bg-warning)

## 📝 Ejemplo de Excel Actualizado

| nombre_producto | tipo_producto | consola | estado | precio_pesos | precio_dolares |
|----------------|--------------|---------|--------|--------------|----------------|
| Super Mario 64 | juego | Nintendo 64 | activo | 10000 | 10 |
| Nintendo 64 Consola | consola | Nintendo 64 | activo | 250000 | 65 |
| Control N64 Original | accesorio | Nintendo 64 | activo | 45000 | 12 |

## ⚠️ Importante

- La columna es **ENUM** y solo acepta: 'game', 'console', 'accessory'
- En el Excel usar español: 'juego', 'consola', 'accesorio'
- El sistema mapea automáticamente español → inglés
- Productos sin tipo se marcan como 'game' por defecto

## 🔄 Rollback (si es necesario)

Si necesitas revertir la migración:
```sql
ALTER TABLE products DROP INDEX idx_product_type;
ALTER TABLE products DROP COLUMN product_type;
```
