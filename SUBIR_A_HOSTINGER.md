# 🚀 ARCHIVOS PARA SUBIR A HOSTINGER

## ✅ CAMBIOS REALIZADOS

Se corrigieron los archivos para que los productos con `is_featured = 1` y `is_new = 1` aparezcan correctamente en el home.

### Problemas solucionados:
1. ✅ **index.php** - Ahora usa `primary_image` correctamente con la ruta `uploads/products/`
2. ✅ **admin/product_create.php** - Agregado checkbox "Novedad" que se marca automáticamente
3. ✅ **admin/product_edit.php** - Agregado checkbox "Novedad" para productos existentes

---

## 📦 ARCHIVOS A SUBIR (VIA FILE MANAGER DE HOSTINGER)

### **1. Archivo Principal:**

**Archivo:** `index.php`
**Ubicación en Hostinger:** `/public_html/index.php`
**Qué hace:** Muestra productos destacados y novedades en el home

---

### **2. Archivos del Admin:**

**Archivo:** `admin/product_create.php`
**Ubicación en Hostinger:** `/public_html/admin/product_create.php`
**Qué hace:** Permite crear productos con checkbox "Novedad" marcado por defecto

**Archivo:** `admin/product_edit.php`
**Ubicación en Hostinger:** `/public_html/admin/product_edit.php`
**Qué hace:** Permite editar productos y marcar/desmarcar "Producto Destacado" y "Novedad"

---

## 📋 PASOS PARA SUBIR LOS ARCHIVOS

### **Opción 1: File Manager de Hostinger (RECOMENDADO)**

1. **Accede a tu panel de Hostinger:**
   - https://hpanel.hostinger.com
   - Inicia sesión con tus credenciales

2. **Abre File Manager:**
   - En el panel, busca "File Manager" o "Administrador de archivos"
   - Click en "File Manager"

3. **Navega a public_html:**
   - En la columna izquierda, haz click en `public_html/`

4. **Sube index.php:**
   - Click en el botón "Upload" (Subir)
   - Selecciona el archivo `index.php` desde tu computadora
   - **IMPORTANTE:** Si existe, selecciona "Sobrescribir" o "Replace"

5. **Navega a la carpeta admin:**
   - En File Manager, navega a `public_html/admin/`
   - Sube estos archivos (sobrescribir si existen):
     - `product_create.php`
     - `product_edit.php`

6. **Limpia la caché:**
   - En el panel de Hostinger, busca "Clear Cache" o "Limpiar Caché"
   - Click para limpiar

---

### **Opción 2: FTP (Alternativa)**

Si prefieres usar FTP:

1. **Datos de conexión:**
   - Host: `ftp.hostingersite.com`
   - Usuario: (tu usuario de Hostinger)
   - Puerto: 21

2. **Conecta con FileZilla o tu cliente FTP favorito**

3. **Sube los archivos a estas ubicaciones:**
   ```
   /public_html/index.php
   /public_html/admin/product_create.php
   /public_html/admin/product_edit.php
   ```

---

## 🧪 VERIFICACIÓN

### **1. Verifica que los productos tengan los flags correctos en BD:**

Ve a phpMyAdmin y ejecuta:

```sql
SELECT id, name, is_featured, is_new, is_active 
FROM products 
WHERE (is_featured = 1 OR is_new = 1) AND is_active = 1;
```

Deberías ver algo como:
```
| id | name              | is_featured | is_new | is_active |
|----|-------------------|-------------|--------|-----------|
| 3  | Kingdom Hearts 3  | 1           | 1      | 1         |
```

### **2. Prueba el Home:**

1. Ve a: https://teal-fish-507993.hostingersite.com
2. Fuerza recarga: `Ctrl + Shift + R` (Windows) o `Cmd + Shift + R` (Mac)
3. Deberías ver:
   - **Sección "NOVEDADES"** con los productos que tienen `is_new = 1`
   - **Sección "PRODUCTOS DESTACADOS"** con los productos que tienen `is_featured = 1`

### **3. Prueba el Admin:**

1. Ve a: https://teal-fish-507993.hostingersite.com/admin/
2. Edita un producto
3. Deberías ver estos checkboxes:
   - ☑️ Producto Destacado
   - ⭐ Novedad (aparece en "Novedades" del home)
   - ☑️ Visible en la tienda

---

## 🐛 TROUBLESHOOTING

### **Si los productos no aparecen después de subir:**

1. **Limpia caché del navegador:**
   - Presiona `Ctrl + Shift + R` (Windows) o `Cmd + Shift + R` (Mac)
   - O abre en modo incógnito

2. **Verifica que los archivos se subieron correctamente:**
   - Ve a File Manager de Hostinger
   - Verifica que las fechas de modificación de los archivos sean recientes

3. **Verifica permisos de archivos:**
   - Los archivos PHP deben tener permisos 644
   - En File Manager, click derecho → Permissions → 644

4. **Verifica errores PHP:**
   - Ve a: https://teal-fish-507993.hostingersite.com/index.php
   - Si hay errores, se mostrarán en pantalla

5. **Verifica que las imágenes existan:**
   - Las imágenes deben estar en: `public_html/uploads/products/`
   - Verifica que los nombres de archivo coincidan con los de la BD

---

## ✅ CHECKLIST FINAL

- [ ] Subir `index.php` a `/public_html/`
- [ ] Subir `admin/product_create.php` a `/public_html/admin/`
- [ ] Subir `admin/product_edit.php` a `/public_html/admin/`
- [ ] Limpiar caché de Hostinger
- [ ] Verificar productos en BD (is_featured y is_new)
- [ ] Probar home (recargar con Ctrl+Shift+R)
- [ ] Verificar que aparezcan productos en "NOVEDADES"
- [ ] Verificar que aparezcan productos en "PRODUCTOS DESTACADOS"
- [ ] Probar edición de productos en admin

---

## 🎉 ¡LISTO!

Una vez subidos los archivos, tu home mostrará correctamente:
- ⭐ **NOVEDADES**: Productos con `is_new = 1`
- 🌟 **PRODUCTOS DESTACADOS**: Productos con `is_featured = 1`

**¿Algún problema?** Revisa la sección de Troubleshooting o avísame! 😊
