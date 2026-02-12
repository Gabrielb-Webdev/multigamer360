# GUÍA RÁPIDA DE DESPLIEGUE - FORMULARIO DE PRODUCTOS

## 🎯 RESUMEN
Esta guía te ayudará a desplegar el nuevo formulario de productos en Hostinger en 5 pasos simples.

---

## 📋 PASO 1: EJECUTAR SQL EN HOSTINGER

### Opción A: Desde phpMyAdmin
1. Ir a: https://hpanel.hostinger.com
2. Bases de datos → phpMyAdmin
3. Seleccionar base de datos: `u851317150_mg360_db`
4. Ir a pestaña **SQL**
5. Abrir archivo: `config/actualizar_productos_estructura.sql`
6. Copiar todo el contenido
7. Pegar en el campo SQL
8. Hacer clic en **Continuar**
9. Verificar mensaje: "X filas afectadas"

### Opción B: Desde Terminal SSH
```bash
mysql -u u851317150_mg360_user -p u851317150_mg360_db < config/actualizar_productos_estructura.sql
```

---

## 📤 PASO 2: SUBIR ARCHIVOS ACTUALIZADOS

### Archivos a subir vía FTP/File Manager:

#### 1. Formulario Principal
```
/admin/product_edit.php
```

#### 2. APIs (7 archivos)
```
/admin/api/add_category.php
/admin/api/add_brand.php
/admin/api/add_console.php
/admin/api/add_genre.php
/admin/api/delete_product_image.php
/admin/api/update_image_order.php
/admin/api/update_primary_image.php
```

### Comandos FTP (si usas terminal):
```bash
# Conectar a FTP
ftp test-leh-507993.hostingersite.com

# Subir archivo principal
put admin/product_edit.php /public_html/admin/product_edit.php

# Subir APIs
cd /public_html/admin/api/
mput admin/api/*.php
```

---

## 🔐 PASO 3: VERIFICAR PERMISOS

### Carpeta de Imágenes
```bash
chmod 755 /public_html/uploads/products/
```

O desde File Manager de Hostinger:
1. Navegar a `/public_html/uploads/`
2. Crear carpeta `products` si no existe
3. Clic derecho → Permisos
4. Establecer: **755** (rwxr-xr-x)

---

## 🧪 PASO 4: PRUEBAS FUNCIONALES

### Checklist de Pruebas:

#### ✅ Acceso al Formulario
- [ ] Ir a: `https://test-leh-507993.hostingersite.com/admin/product_edit.php`
- [ ] Verificar que carga sin errores
- [ ] Ver dropdowns de categorías, marcas, consolas
- [ ] Ver checkboxes de géneros

#### ✅ Crear Categoría Nueva
- [ ] Clic en botón "+" junto a Categoría
- [ ] Modal se abre correctamente
- [ ] Llenar nombre: "Test Categoría"
- [ ] Clic en "Guardar"
- [ ] Ver nueva opción en dropdown

#### ✅ Subir Imágenes
- [ ] Seleccionar 2-3 imágenes
- [ ] Ver vista previa instantánea
- [ ] Guardar producto
- [ ] Verificar imágenes en carpeta `uploads/products/`

#### ✅ Reordenar Imágenes (si hay producto existente)
- [ ] Editar producto con imágenes
- [ ] Arrastrar una imagen a diferente posición
- [ ] Recargar página
- [ ] Verificar nuevo orden guardado

#### ✅ Marcar Imagen Principal
- [ ] Seleccionar radio "Marcar como principal"
- [ ] Ver badge "Principal" actualizado
- [ ] Recargar página
- [ ] Verificar cambio persistió

#### ✅ Eliminar Imagen
- [ ] Clic en "Eliminar" en una imagen
- [ ] Confirmar eliminación
- [ ] Ver imagen removida
- [ ] Verificar archivo eliminado del servidor

#### ✅ Auto-generar SEO
- [ ] Llenar nombre del producto
- [ ] Clic en "Auto-generar" en sección SEO
- [ ] Verificar meta título generado
- [ ] Verificar meta descripción generada
- [ ] Ver contadores de caracteres (0/60, 0/160)
- [ ] Ver vista previa de Google actualizada

#### ✅ Validación de Precios
- [ ] Llenar precio regular: `50000`
- [ ] Llenar precio de oferta: `45000`
- [ ] Ver cálculo de descuento: "10%"
- [ ] Ver ahorro: "$5000 COP"
- [ ] Intentar precio oferta mayor → Ver alerta

#### ✅ Selección de Géneros Múltiples
- [ ] Marcar 2-3 géneros diferentes
- [ ] Guardar producto
- [ ] Editar producto
- [ ] Verificar géneros siguen marcados

#### ✅ Crear Género Nuevo
- [ ] Clic en "Agregar" en sección géneros
- [ ] Modal se abre
- [ ] Llenar nombre: "Test Género"
- [ ] Guardar
- [ ] Página recarga
- [ ] Ver nuevo checkbox disponible

---

## 🐛 PASO 5: RESOLVER PROBLEMAS COMUNES

### Error: "Table 'categories' doesn't exist"
```sql
-- Ejecutar manualmente:
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Error: "Permission denied" al subir imagen
```bash
# SSH
chmod 777 /home/u851317150/public_html/uploads/products/

# O desde File Manager
Permisos → 777 (rwxrwxrwx)
```

### Error: "Modal no se abre"
```html
<!-- Verificar que esté incluido Bootstrap JS en footer: -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

### Error: "Drag & drop no funciona"
```html
<!-- Verificar que esté incluido SortableJS: -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
```

### Error: "API devuelve 403 Forbidden"
```php
// Verificar en admin/inc/auth.php:
function hasPermission($resource, $action) {
    if (!isset($_SESSION['user_id'])) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return ($user && $user['role'] === 'administrador');
}
```

---

## 📊 VERIFICACIÓN FINAL

### Base de Datos
```sql
-- Verificar tablas existen:
SHOW TABLES LIKE '%products%';
-- Debe mostrar:
-- ✓ products
-- ✓ product_genres
-- ✓ product_images

SHOW TABLES LIKE '%categories%';
-- ✓ categories

SHOW TABLES LIKE '%brands%';
-- ✓ brands

SHOW TABLES LIKE '%consoles%';
-- ✓ consoles

SHOW TABLES LIKE '%genres%';
-- ✓ genres
```

### Archivos
```bash
# Verificar archivos existen:
ls -la /public_html/admin/product_edit.php
ls -la /public_html/admin/api/add_*.php
ls -la /public_html/admin/api/update_*.php
ls -la /public_html/admin/api/delete_*.php
```

### Permisos
```bash
# Verificar permisos:
ls -ld /public_html/uploads/products/
# Debe mostrar: drwxr-xr-x (755)
```

---

## 📞 SOPORTE

### Si algo no funciona:

1. **Revisar logs de errores**
   ```bash
   tail -f /home/u851317150/public_html/admin/logs/error.log
   ```

2. **Verificar conexión a BD**
   ```bash
   # Crear archivo test_connection.php:
   <?php
   require_once 'config/database.php';
   echo "Conexión exitosa: " . ($pdo ? "Sí" : "No");
   ?>
   ```

3. **Activar modo debug en PHP**
   ```php
   // En config/database.php:
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

---

## ✅ CHECKLIST FINAL

- [ ] SQL ejecutado sin errores
- [ ] Archivos subidos a Hostinger
- [ ] Permisos de carpetas configurados
- [ ] Formulario carga correctamente
- [ ] Se pueden crear categorías, marcas, consolas, géneros
- [ ] Se pueden subir imágenes múltiples
- [ ] Drag & drop funciona
- [ ] Radio buttons de imagen principal funcionan
- [ ] Botón de eliminar imagen funciona
- [ ] Auto-generación de SEO funciona
- [ ] Calculadora de descuentos funciona
- [ ] Géneros múltiples se guardan correctamente
- [ ] No hay errores en consola del navegador
- [ ] No hay errores en logs de PHP

---

## 🎉 ¡LISTO!

Si todos los checks están marcados, el formulario está completamente funcional.

**Tiempo estimado de despliegue**: 15-20 minutos

**Nivel de dificultad**: ⭐⭐⭐ (Medio)

---

**Última actualización**: Diciembre 2024  
**Contacto**: Desarrollo MultiGamer360
