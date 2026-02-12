# FORMULARIO DE PRODUCTOS ACTUALIZADO - MULTIGAMER360

## 📋 RESUMEN DE CAMBIOS

El formulario de productos (`admin/product_edit.php`) ha sido completamente renovado con las siguientes características avanzadas:

## ✨ NUEVAS FUNCIONALIDADES

### 1. **GESTIÓN AVANZADA DE IMÁGENES**

#### Subida Múltiple de Imágenes
- ✅ Permite subir múltiples imágenes simultáneamente
- ✅ Vista previa instantánea antes de guardar
- ✅ Validación de tamaño (máximo 5MB por imagen)
- ✅ Formatos soportados: JPG, PNG, WebP

#### Reordenamiento con Drag & Drop
- ✅ Reordena imágenes arrastrando y soltando
- ✅ Orden guardado automáticamente en la base de datos
- ✅ Indicador visual de arrastre (icono de grip)
- ✅ Utiliza **SortableJS** para funcionalidad fluida

#### Selección de Imagen Principal
- ✅ Radio buttons para marcar imagen principal
- ✅ Badge "Principal" visible en la imagen seleccionada
- ✅ Solo una imagen puede ser principal
- ✅ Actualización inmediata sin recargar página

#### Eliminación de Imágenes
- ✅ Botón de eliminar en cada imagen
- ✅ Confirmación antes de eliminar
- ✅ Eliminación del archivo físico y registro en BD
- ✅ Actualización visual instantánea

---

### 2. **SEO AUTO-GENERADO**

#### Botón "Auto-generar" ⚡
- ✅ Genera meta título desde el nombre del producto
- ✅ Agrega " | MultiGamer360" al final del título
- ✅ Genera meta descripción desde la descripción del producto
- ✅ Limita automáticamente a 60/160 caracteres

#### Contadores de Caracteres
- ✅ Meta Título: (0/60) con color de advertencia si excede
- ✅ Meta Descripción: (0/160) con color de advertencia si excede
- ✅ Actualización en tiempo real mientras escribes

#### Vista Previa SERP
- ✅ Muestra cómo se verá en Google
- ✅ Título azul, URL verde, descripción gris
- ✅ Se actualiza automáticamente al escribir
- ✅ Ayuda a optimizar para motores de búsqueda

---

### 3. **CAMPOS DE PRECIOS MÚLTIPLES**

#### Tres Tipos de Precios
1. **Precio en Pesos (COP)** - *Requerido*
   - Campo principal para el mercado colombiano
   - Símbolo $ y sufijo COP

2. **Precio en Dólares (USD)** - *Opcional*
   - Para ventas internacionales
   - Símbolo $ y sufijo USD

3. **Precio de Oferta (COP)** - *Opcional*
   - Para promociones y descuentos
   - Badge "OFERTA" rojo visible

#### Calculadora de Descuentos
- ✅ Calcula porcentaje de descuento automáticamente
- ✅ Muestra ahorro en pesos
- ✅ Alert visual con % de descuento
- ✅ Validación: precio de oferta debe ser menor al regular

#### Alerta de Stock
- ✅ **Sin stock**: Alerta roja
- ✅ **Stock bajo** (<5): Alerta amarilla
- ✅ **Stock disponible**: Checkmark verde

---

### 4. **CATEGORIZACIÓN CON "AGREGAR NUEVO"**

#### Categoría (Dropdown + Botón)
- ✅ Select con todas las categorías activas
- ✅ Botón "+" abre modal para crear nueva
- ✅ Modal con campos: Nombre, Descripción
- ✅ Agrega automáticamente al select tras crear

#### Marca (Dropdown + Botón)
- ✅ Select con todas las marcas activas
- ✅ Botón "+" abre modal para crear nueva
- ✅ Modal con campo: Nombre
- ✅ Selecciona automáticamente tras crear

#### Consola / Plataforma (Dropdown + Botón)
- ✅ Select con todas las consolas activas
- ✅ Botón "+" abre modal para crear nueva
- ✅ Modal con campos: Nombre, Fabricante
- ✅ Ejemplos: PS5, Xbox Series X, Nintendo Switch

#### Géneros (Multiple Checkboxes + Botón)
- ✅ Checkboxes para selección múltiple
- ✅ Scroll interno si hay muchos géneros
- ✅ Botón "Agregar" abre modal para nuevo género
- ✅ Recarga página tras crear para mostrar nuevo checkbox
- ✅ Guarda relación en tabla `product_genres` (many-to-many)

---

### 5. **CAMPOS ADICIONALES**

#### Información Básica
- ✅ **Nombre del Producto**: Campo principal (requerido)
- ✅ **SKU**: Auto-generado si se deja vacío
- ✅ **Estado**: Active, Inactive, Out of Stock
- ✅ **Descripción Corta**: Máximo 500 caracteres
- ✅ **Descripción**: Campo largo para detalles completos
- ✅ **Producto Destacado**: Checkbox
- ✅ **Visible en la tienda**: Checkbox (is_active)

#### Generación Automática de SKU
- ✅ Se genera al salir del campo "Nombre"
- ✅ Formato: `NOMBREPR-1234`
- ✅ Usa los primeros 8 caracteres del nombre
- ✅ Agrega número aleatorio de 4 dígitos

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS

### Tabla: `products`
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    sku VARCHAR(100) UNIQUE,
    price_pesos DECIMAL(10, 2) NOT NULL,
    price_dollars DECIMAL(10, 2),
    offer_price DECIMAL(10, 2),
    stock_quantity INT DEFAULT 0,
    category_id INT,
    brand_id INT,
    console_id INT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (console_id) REFERENCES consoles(id)
);
```

### Tabla: `product_genres` (Many-to-Many)
```sql
CREATE TABLE product_genres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    genre_id INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_genre (product_id, genre_id)
);
```

### Tabla: `product_images`
```sql
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

---

## 🔌 APIS CREADAS

### 1. **add_category.php**
```
POST /admin/api/add_category.php
Body: { "name": "Nueva Categoría", "description": "Descripción..." }
Response: { "success": true, "category": { "id": 1, "name": "...", "slug": "..." } }
```

### 2. **add_brand.php**
```
POST /admin/api/add_brand.php
Body: { "name": "Nueva Marca" }
Response: { "success": true, "brand": { "id": 1, "name": "...", "slug": "..." } }
```

### 3. **add_console.php**
```
POST /admin/api/add_console.php
Body: { "name": "PS5", "manufacturer": "Sony" }
Response: { "success": true, "console": { "id": 1, "name": "...", "slug": "..." } }
```

### 4. **add_genre.php**
```
POST /admin/api/add_genre.php
Body: { "name": "Acción", "description": "Juegos de acción..." }
Response: { "success": true, "genre": { "id": 1, "name": "...", "slug": "..." } }
```

### 5. **delete_product_image.php**
```
POST /admin/api/delete_product_image.php
Body: { "image_id": 5 }
Response: { "success": true, "message": "Imagen eliminada correctamente" }
```

### 6. **update_image_order.php**
```
POST /admin/api/update_image_order.php
Body: { "images": [{ "id": 1, "order": 1 }, { "id": 2, "order": 2 }] }
Response: { "success": true, "message": "Orden actualizado" }
```

### 7. **update_primary_image.php**
```
POST /admin/api/update_primary_image.php
Body: { "product_id": 10, "image_id": 5 }
Response: { "success": true, "message": "Imagen principal actualizada" }
```

---

## 📦 DEPENDENCIAS JAVASCRIPT

### SortableJS
```html
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
```
- **Propósito**: Drag & drop para reordenar imágenes
- **Documentación**: https://sortablejs.github.io/Sortable/

### Bootstrap 5 (Ya incluido)
- **Modales**: Para ventanas de "Agregar Nuevo"
- **Tooltips**: Para información adicional
- **Alerts**: Para mensajes de confirmación

---

## 🎨 DISEÑO UI/UX

### Layout Responsive
- ✅ **Columna principal (col-lg-8)**: Información básica, imágenes, SEO
- ✅ **Columna lateral (col-lg-4)**: Precios, categorización, acciones
- ✅ **Mobile-friendly**: Se apilan las columnas en pantallas pequeñas

### Iconos Font Awesome
- ✅ `fa-images`: Sección de imágenes
- ✅ `fa-search`: SEO
- ✅ `fa-dollar-sign`: Precios
- ✅ `fa-tags`: Categorización
- ✅ `fa-grip-vertical`: Indicador de arrastre
- ✅ `fa-star`: Imagen principal
- ✅ `fa-plus`: Agregar nuevo

### Colores y Estados
- 🟢 **Verde**: Stock disponible, imagen nueva
- 🟡 **Amarillo**: Stock bajo, advertencias
- 🔴 **Rojo**: Sin stock, eliminar
- 🔵 **Azul**: Acciones principales
- ⚫ **Gris**: Información secundaria

---

## 🚀 FLUJO DE TRABAJO

### Crear Nuevo Producto
1. Usuario hace clic en "Nuevo Producto"
2. Completa nombre (SKU se auto-genera)
3. Sube imágenes múltiples (vista previa instantánea)
4. Hace clic en "Auto-generar" para SEO
5. Completa precios (ve cálculo de descuento)
6. Selecciona categoría, marca, consola
7. Marca checkboxes de géneros
8. Si falta algo, usa botones "+" para crear
9. Hace clic en "Crear Producto"
10. Sistema valida y guarda todo

### Editar Producto Existente
1. Usuario selecciona producto en lista
2. Ve imágenes actuales con badges
3. Puede arrastrar para reordenar
4. Marca radio para cambiar principal
5. Elimina imágenes no deseadas
6. Agrega nuevas imágenes si necesita
7. Actualiza campos de texto
8. Sistema guarda cambios

---

## ⚠️ VALIDACIONES

### Frontend (JavaScript)
- ✅ Precio de oferta menor a precio regular
- ✅ Categoría requerida
- ✅ SKU único
- ✅ Tamaño de imagen máximo 5MB
- ✅ Formatos de imagen permitidos

### Backend (PHP)
- ✅ Verificación de permisos (hasPermission)
- ✅ Validación de campos requeridos
- ✅ Sanitización de inputs
- ✅ Transacciones de base de datos
- ✅ Manejo de errores con try-catch

---

## 📝 PRÓXIMOS PASOS

### Para Implementar en Hostinger:

1. **Ejecutar SQL**
```bash
# Subir y ejecutar archivo:
config/actualizar_productos_estructura.sql
```

2. **Subir Archivos PHP Actualizados**
```
admin/product_edit.php
admin/api/add_category.php
admin/api/add_brand.php
admin/api/add_console.php
admin/api/add_genre.php
admin/api/delete_product_image.php
admin/api/update_image_order.php
admin/api/update_primary_image.php
```

3. **Verificar Permisos de Carpetas**
```bash
chmod 755 uploads/products/
```

4. **Probar Funcionalidades**
- [ ] Crear categoría nueva desde modal
- [ ] Subir múltiples imágenes
- [ ] Reordenar imágenes con drag & drop
- [ ] Marcar imagen como principal
- [ ] Eliminar imagen
- [ ] Auto-generar SEO
- [ ] Seleccionar múltiples géneros
- [ ] Calcular descuento automático

---

## 🐛 TROUBLESHOOTING

### Problema: "Table doesn't exist"
**Solución**: Ejecutar `actualizar_productos_estructura.sql` primero

### Problema: Imágenes no se suben
**Solución**: Verificar permisos de `uploads/products/` (chmod 755 o 777)

### Problema: Drag & drop no funciona
**Solución**: Verificar que SortableJS esté cargado correctamente

### Problema: Modales no se abren
**Solución**: Verificar que Bootstrap 5 JS esté incluido

### Problema: API devuelve error 403
**Solución**: Verificar que el usuario tenga rol 'administrador'

---

## 📚 DOCUMENTACIÓN RELACIONADA

- `docs/ESTRUCTURA_PRODUCTOS.md` - Estructura completa de la base de datos
- `config/actualizar_productos_estructura.sql` - Script SQL con todas las tablas

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Actualizar `product_edit.php` con nuevo formulario
- [x] Crear 7 archivos API para CRUD
- [x] Agregar SortableJS para drag & drop
- [x] Implementar auto-generación de SEO
- [x] Crear calculadora de descuentos
- [x] Implementar subida múltiple de imágenes
- [x] Agregar modales para "crear nuevo"
- [x] Implementar validaciones frontend y backend
- [ ] Ejecutar SQL en servidor de producción
- [ ] Subir archivos a Hostinger
- [ ] Probar todas las funcionalidades
- [ ] Capacitar al equipo en uso del nuevo formulario

---

**Fecha de actualización**: Diciembre 2024  
**Versión**: 2.0  
**Autor**: Desarrollo MultiGamer360
