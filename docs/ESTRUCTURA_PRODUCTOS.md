# 🎮 ESTRUCTURA DE PRODUCTOS - MULTIGAMER360

---

## 📋 ESTRUCTURA COMPLETA DE LA BASE DE DATOS

### **Tabla Principal: `products`**

| Columna | Tipo | Descripción | Control UI |
|---------|------|-------------|------------|
| **id** | INT AUTO_INCREMENT | ID único del producto | - |
| **name** | VARCHAR(255) | Nombre del producto | Input text |
| **slug** | VARCHAR(255) UNIQUE | URL amigable (SEO) | Auto-generado |
| **description** | TEXT | Descripción completa | Textarea / Editor |
| **price_pesos** | DECIMAL(10,2) | Precio en COP ($) | Input number |
| **price_dollars** | DECIMAL(10,2) | Precio en USD ($) | Input number |
| **stock_quantity** | INT | Cantidad en stock | Input number |
| **category_id** | INT (FK) | Categoría | **🔽 Dropdown Select** |
| **genre_id** | - | Géneros (relación) | **☑️ Multiple Checkboxes** |
| **brand_id** | INT (FK) | Marca | **🔽 Dropdown Select** |
| **console_id** | INT (FK) | Consola compatible | **🔽 Dropdown Select** |

---

## 🔗 TABLAS RELACIONADAS

### **1. Categorías (`categories`)** - DROPDOWN
```sql
id, name, slug, description, image_url, is_active
```
**Ejemplos:**
- Videojuegos
- Consolas
- Accesorios
- Figuras y Coleccionables
- Gift Cards

---

### **2. Géneros (`genres`)** - MÚLTIPLES CHECKBOXES
```sql
id, name, slug, description, is_active
```
**Relación:** Tabla intermedia `product_genres` (muchos a muchos)

**Ejemplos:**
- ☑️ Acción
- ☑️ Aventura
- ☑️ RPG
- ☑️ Deportes
- ☑️ Carreras
- ☑️ Shooter
- ☑️ Estrategia
- ☑️ Terror
- ☑️ Lucha
- ☑️ Plataformas

**Un producto puede tener varios géneros:**
- The Last of Us: ☑️ Acción + ☑️ Aventura + ☑️ Terror

---

### **3. Marcas (`brands`)** - DROPDOWN
```sql
id, name, slug, logo_url, is_active
```
**Ejemplos:**
- Sony
- Microsoft
- Nintendo
- Electronic Arts
- Ubisoft
- Activision
- Rockstar Games

---

### **4. Consolas (`consoles`)** - DROPDOWN
```sql
id, name, slug, manufacturer, release_year, is_active
```
**Ejemplos:**
- PlayStation 5
- PlayStation 4
- Xbox Series X
- Xbox Series S
- Xbox One
- Nintendo Switch
- Nintendo Switch OLED
- PC
- Multiplataforma

---

## 🎨 INTERFAZ DE USUARIO - FORMULARIO DE PRODUCTO

```
┌─────────────────────────────────────────────────────┐
│  AGREGAR / EDITAR PRODUCTO                          │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Nombre del Producto *                              │
│  [________________________________]                 │
│                                                      │
│  Descripción *                                       │
│  [________________________________]                 │
│  [                                ]                 │
│  [                                ]                 │
│                                                      │
│  Precio en Pesos (COP) *    Precio en USD           │
│  [$____________]            [$____________]         │
│                                                      │
│  Cantidad en Stock *                                 │
│  [_____]                                            │
│                                                      │
│  Categoría * 🔽                                      │
│  [Seleccionar categoría    ▼]                      │
│                                                      │
│  Géneros * (Múltiple selección)                     │
│  ☑️ Acción          ☐ Deportes      ☐ Terror       │
│  ☐ Aventura        ☐ Carreras      ☐ Lucha        │
│  ☐ RPG             ☑️ Shooter       ☐ Plataformas  │
│  ☐ Estrategia      ☐ Simulación    ☐ Puzzle       │
│                                                      │
│  Marca * 🔽                                          │
│  [Seleccionar marca        ▼]                      │
│                                                      │
│  Consola Compatible * 🔽                             │
│  [Seleccionar consola      ▼]                      │
│                                                      │
│  [ Cancelar ]  [ Guardar Producto ]                 │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 💾 EJEMPLO DE PRODUCTO COMPLETO

### **God of War Ragnarök**

```
ID: 1
Nombre: God of War Ragnarök
Slug: god-of-war-ragnarok
Descripción: Embárcate en un viaje épico por los Nueve Reinos...
Precio Pesos: $279,900
Precio USD: $69.99
Stock: 50 unidades

Categoría: 🔽 Videojuegos
Géneros: ☑️ Acción, ☑️ Aventura, ☑️ RPG
Marca: 🔽 Sony
Consola: 🔽 PlayStation 5
```

---

## 🗄️ MODELO RELACIONAL

```
┌──────────────┐
│  categories  │
│──────────────│
│  id (PK)     │
│  name        │
│  slug        │
└──────┬───────┘
       │
       │ 1:N
       │
┌──────▼───────────────┐       ┌──────────────────┐
│     products         │  N:M  │  product_genres  │
│──────────────────────│◄──────┤──────────────────│
│  id (PK)             │       │  product_id (FK) │
│  name                │       │  genre_id (FK)   │
│  slug                │       └──────┬───────────┘
│  description         │              │
│  price_pesos         │              │ N:1
│  price_dollars       │              │
│  stock_quantity      │       ┌──────▼───────┐
│  category_id (FK)    │       │    genres    │
│  brand_id (FK)       │       │──────────────│
│  console_id (FK)     │       │  id (PK)     │
└──┬────┬──────────────┘       │  name        │
   │    │                      │  slug        │
   │    │                      └──────────────┘
   │    │
   │    │ N:1
   │    │
   │    └──────────┐
   │               │
   │ N:1           │
   │        ┌──────▼───────┐
   │        │   consoles   │
   │        │──────────────│
   │        │  id (PK)     │
   │        │  name        │
   │        │  slug        │
   │        └──────────────┘
   │
   │ N:1
   │
┌──▼───────┐
│  brands  │
│──────────│
│  id (PK) │
│  name    │
│  slug    │
└──────────┘
```

---

## 📝 CÓDIGO PHP - CREAR PRODUCTO

```php
<?php
// Guardar nuevo producto
$stmt = $pdo->prepare("
    INSERT INTO products (
        name, slug, description, 
        price_pesos, price_dollars, stock_quantity,
        category_id, brand_id, console_id,
        is_active, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
");

$stmt->execute([
    $_POST['name'],
    $_POST['slug'],
    $_POST['description'],
    $_POST['price_pesos'],
    $_POST['price_dollars'],
    $_POST['stock_quantity'],
    $_POST['category_id'],
    $_POST['brand_id'],
    $_POST['console_id']
]);

$product_id = $pdo->lastInsertId();

// Guardar géneros (múltiples checkboxes)
if (!empty($_POST['genres'])) {
    $stmt_genre = $pdo->prepare("
        INSERT INTO product_genres (product_id, genre_id) 
        VALUES (?, ?)
    ");
    
    foreach ($_POST['genres'] as $genre_id) {
        $stmt_genre->execute([$product_id, $genre_id]);
    }
}
?>
```

---

## 📝 CÓDIGO HTML - FORMULARIO

```html
<!-- Categoría - DROPDOWN -->
<div class="mb-3">
    <label class="form-label">Categoría *</label>
    <select name="category_id" class="form-select" required>
        <option value="">Seleccionar categoría</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Géneros - MÚLTIPLES CHECKBOXES -->
<div class="mb-3">
    <label class="form-label">Géneros *</label>
    <div class="row">
        <?php foreach ($genres as $genre): ?>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" 
                           type="checkbox" 
                           name="genres[]" 
                           value="<?= $genre['id'] ?>"
                           id="genre_<?= $genre['id'] ?>">
                    <label class="form-check-label" 
                           for="genre_<?= $genre['id'] ?>">
                        <?= htmlspecialchars($genre['name']) ?>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Marca - DROPDOWN -->
<div class="mb-3">
    <label class="form-label">Marca *</label>
    <select name="brand_id" class="form-select" required>
        <option value="">Seleccionar marca</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?= $brand['id'] ?>">
                <?= htmlspecialchars($brand['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Consola - DROPDOWN -->
<div class="mb-3">
    <label class="form-label">Consola Compatible *</label>
    <select name="console_id" class="form-select" required>
        <option value="">Seleccionar consola</option>
        <?php foreach ($consoles as $console): ?>
            <option value="<?= $console['id'] ?>">
                <?= htmlspecialchars($console['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
```

---

## 🔍 CONSULTAS SQL ÚTILES

### **Obtener Producto con Todas las Relaciones**
```sql
SELECT 
    p.id,
    p.name,
    p.slug,
    p.description,
    p.price_pesos,
    p.price_dollars,
    p.stock_quantity,
    c.name as category_name,
    b.name as brand_name,
    co.name as console_name,
    GROUP_CONCAT(g.name SEPARATOR ', ') as genres
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN brands b ON p.brand_id = b.id
LEFT JOIN consoles co ON p.console_id = co.id
LEFT JOIN product_genres pg ON p.id = pg.product_id
LEFT JOIN genres g ON pg.genre_id = g.id
WHERE p.id = 1
GROUP BY p.id;
```

### **Productos por Categoría**
```sql
SELECT p.*, c.name as category_name
FROM products p
INNER JOIN categories c ON p.category_id = c.id
WHERE c.slug = 'videojuegos'
AND p.is_active = 1;
```

### **Productos por Género**
```sql
SELECT DISTINCT p.*
FROM products p
INNER JOIN product_genres pg ON p.id = pg.product_id
INNER JOIN genres g ON pg.genre_id = g.id
WHERE g.slug = 'accion'
AND p.is_active = 1;
```

### **Productos por Consola**
```sql
SELECT p.*, co.name as console_name
FROM products p
INNER JOIN consoles co ON p.console_id = co.id
WHERE co.slug = 'playstation-5'
AND p.is_active = 1;
```

---

## 🚀 DEPLOYMENT EN HOSTINGER

### **Paso 1: Ejecutar SQL**
```bash
1. Accede a phpMyAdmin en Hostinger
2. Base de datos: u851317150_mg360_db
3. Pestaña "SQL"
4. Copia/pega: config/actualizar_productos_estructura.sql
5. Click "Ejecutar"
```

### **Paso 2: Verificar Datos**
```sql
-- Ver categorías
SELECT * FROM categories;

-- Ver géneros
SELECT * FROM genres;

-- Ver marcas
SELECT * FROM brands;

-- Ver consolas
SELECT * FROM consoles;
```

---

## ✅ CARACTERÍSTICAS

### **✅ Dropdowns (Select):**
- Categoría (Videojuegos, Consolas, Accesorios...)
- Marca (Sony, Nintendo, Microsoft...)
- Consola (PS5, Xbox, Switch...)

### **☑️ Múltiples Checkboxes:**
- Géneros (Acción, Aventura, RPG, Shooter...)
- Un producto puede tener varios géneros

### **💰 Precios:**
- Pesos colombianos (COP)
- Dólares estadounidenses (USD)

### **📦 Inventario:**
- Stock quantity (cantidad disponible)
- Control de existencias

---

## 📊 RESUMEN DE COLUMNAS

| # | Columna | Tipo | Control | Obligatorio |
|---|---------|------|---------|-------------|
| 1 | **id** | INT | Auto | ✅ |
| 2 | **name** | VARCHAR | Input | ✅ |
| 3 | **slug** | VARCHAR | Auto-gen | ✅ |
| 4 | **description** | TEXT | Textarea | ✅ |
| 5 | **price_pesos** | DECIMAL | Input | ✅ |
| 6 | **price_dollars** | DECIMAL | Input | ❌ |
| 7 | **stock_quantity** | INT | Input | ✅ |
| 8 | **category_id** | INT | 🔽 Dropdown | ✅ |
| 9 | **genres** | Relación | ☑️ Checkboxes | ✅ |
| 10 | **brand_id** | INT | 🔽 Dropdown | ✅ |
| 11 | **console_id** | INT | 🔽 Dropdown | ✅ |

---

**🎮 MultiGamer360 - Estructura de Productos Completa**
*Actualizado: Octubre 10, 2025*
