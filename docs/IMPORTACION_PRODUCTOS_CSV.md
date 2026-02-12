# Guía de Importación Masiva de Productos via CSV

## 📋 Descripción

Esta funcionalidad permite importar múltiples productos a la vez mediante un archivo CSV (valores separados por comas), lo que agiliza enormemente el proceso de carga de inventario.

## 🚀 Cómo usar

### 1. Acceder a la función
1. Inicia sesión en el panel de administración
2. Ve a **Productos e Inventario**
3. Haz clic en el botón **"Importar CSV"**

### 2. Descargar el archivo de ejemplo
1. Haz clic en **"Descargar Ejemplo CSV"**
2. Este archivo contiene:
   - Los encabezados correctos
   - Ejemplos de productos con datos completos
   - Formato correcto para cada campo

### 3. Preparar tu archivo CSV

#### Campos Obligatorios
- **title**: Nombre del producto
- **sku**: Código único del producto (no puede repetirse)
- **price_pesos**: Precio en pesos argentinos
- **stock_quantity**: Cantidad en stock

#### Campos Opcionales
- **description**: Descripción completa del producto
- **short_description**: Descripción breve
- **price_usd**: Precio en dólares
- **category**: Nombre exacto de la categoría (debe existir)
- **brand**: Nombre exacto de la marca (debe existir)
- **console**: Nombre exacto de la consola (debe existir)
- **is_featured**: "si" para destacado, "no" o vacío para normal
- **is_active**: "no" para inactivo, "si" o vacío para activo
- **condition**: "new", "used" o "refurbished"
- **tags**: Etiquetas separadas por comas

### 4. Formato del archivo

```csv
title,sku,description,short_description,price_pesos,price_usd,stock_quantity,category,brand,console,is_featured,is_active,condition,tags
"Super Mario 64","SM64-N64-001","Descripción completa...","Descripción corta",10000,10,5,Videojuegos,Nintendo,Nintendo 64,si,si,new,"mario,plataformas,3d"
```

**Importante:**
- Usa comillas dobles para textos que contengan comas
- No uses saltos de línea dentro de las celdas
- Usa codificación UTF-8 para caracteres especiales
- El separador debe ser coma (`,`)

### 5. Importar el archivo

1. En el modal de importación, haz clic en **"Seleccionar archivo CSV"**
2. Elige tu archivo preparado
3. (Opcional) Marca **"Actualizar productos existentes"** si quieres actualizar productos con SKU duplicado
4. Haz clic en **"Importar Productos"**
5. Espera a que se complete el proceso

### 6. Revisar resultados

El sistema mostrará:
- ✅ Número de productos importados
- 🔄 Número de productos actualizados
- ❌ Número de errores
- 📝 Detalles de cada error (si los hay)

## ⚠️ Notas Importantes

### Sobre SKU
- El SKU debe ser único
- Si un SKU ya existe y no marcaste "Actualizar existentes", se saltará ese producto
- Si marcaste "Actualizar existentes", se actualizará el producto con ese SKU

### Sobre Categorías, Marcas y Consolas
- Deben escribirse exactamente como aparecen en el sistema
- Si no existen, ese campo quedará vacío (NULL)
- Recomendación: Crea primero las categorías, marcas y consolas necesarias

### Sobre precios
- **price_pesos**: Se usa como precio principal
- **price_usd**: Opcional, para mostrar precio en dólares
- Usa números sin símbolos de moneda
- Ejemplo: `10000` no `$10,000`

### Sobre el stock
- Debe ser un número entero positivo
- Ejemplo: `5` no `5 unidades`

## 🔍 Ejemplos

### Producto básico (solo campos obligatorios)
```csv
title,sku,price_pesos,stock_quantity
"GTA V","GTA5-PS4-001",15000,10
```

### Producto completo (todos los campos)
```csv
title,sku,description,short_description,price_pesos,price_usd,stock_quantity,category,brand,console,is_featured,is_active,condition,tags
"The Last of Us","TLOU-PS3-001","Descripción completa del juego...","Aventura post-apocalíptica",8000,8,3,Videojuegos,Naughty Dog,PlayStation 3,si,si,new,"aventura,accion,zombies"
```

## 🛠️ Solución de Problemas

### Error: "El SKU ya existe"
**Solución:** Marca la opción "Actualizar productos existentes" o cambia el SKU

### Error: "Falta el campo requerido"
**Solución:** Verifica que tu CSV tenga todos los encabezados obligatorios

### Error: "No se pudo leer el archivo"
**Solución:** 
- Asegúrate de que el archivo esté en formato CSV
- Verifica que use codificación UTF-8
- Revisa que el separador sea coma

### Los productos se importan pero sin categoría/marca/consola
**Solución:** Verifica que los nombres coincidan exactamente con los registrados en el sistema

## 📊 Mejores Prácticas

1. **Prueba con pocos productos primero**: Importa 2-3 productos de prueba antes de hacer una importación masiva
2. **Haz backup**: Respalda tu base de datos antes de importaciones grandes
3. **Prepara los datos**: Asegúrate de que categorías, marcas y consolas existan antes
4. **Usa Excel o Google Sheets**: Prepara el CSV en estas herramientas y guarda como CSV
5. **Revisa los errores**: Si hay errores, revísalos y corrige el CSV antes de reintentar

## 💡 Tips

- Puedes importar hasta cientos de productos a la vez
- El proceso puede tomar unos segundos dependiendo de la cantidad
- Si tienes muchos productos (1000+), considera dividirlos en varios archivos
- Mantén una copia del CSV original como respaldo

## 🆘 Soporte

Si tienes problemas con la importación:
1. Revisa que el formato del CSV coincida con el ejemplo
2. Verifica los mensajes de error específicos
3. Contacta al administrador del sistema si persisten los problemas
