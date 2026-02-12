# GUÍA DE IMPLEMENTACIÓN - HOSTINGER
## MultiGamer360 - Corrección Páginas en Blanco

**Servidor:** https://teal-fish-507993.hostingersite.com/  
**Fecha:** 13 de Octubre de 2025

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### ✅ Paso 1: Subir Archivos al Servidor

Usando **File Manager** de Hostinger o **FTP**:

1. **Archivos corregidos a subir:**
   - ✅ `/admin/coupons.php`
   - ✅ `/admin/reviews.php`
   - ✅ `/admin/newsletter.php`
   - ✅ `/admin/reports.php`
   - ✅ `/admin/media.php` (NUEVO)
   - ✅ `/config/create_media_table.sql`

2. **Ruta del servidor:**
   ```
   /public_html/admin/
   /public_html/config/
   ```

3. **Reemplazar archivos existentes** (hacer backup primero si es necesario)

---

### ✅ Paso 2: Crear Tabla en Base de Datos

1. **Acceder a phpMyAdmin:**
   - Panel Hostinger → **Bases de datos** → **phpMyAdmin**
   - O ir directamente: https://phpmyadmin.hostinger.com

2. **Seleccionar tu base de datos** (probablemente algo como `u123456789_multigamer`)

3. **Ir a la pestaña SQL**

4. **Copiar y pegar este código:**

```sql
CREATE TABLE IF NOT EXISTS `media_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL COMMENT 'Nombre del archivo en el servidor',
  `original_name` varchar(255) NOT NULL COMMENT 'Nombre original del archivo',
  `file_type` varchar(100) NOT NULL COMMENT 'Tipo MIME del archivo',
  `file_size` int(11) NOT NULL COMMENT 'Tamaño del archivo en bytes',
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'Usuario que subió el archivo',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_filename` (`filename`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_file_type` (`file_type`),
  CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

5. **Hacer clic en "Ejecutar"** o "Go"

6. **Verificar** que aparezca el mensaje: `1 tabla creada correctamente`

---

### ✅ Paso 3: Verificar/Crear Carpeta de Uploads

1. **Acceder a File Manager** en el panel de Hostinger

2. **Navegar a:**
   ```
   /public_html/uploads/
   ```

3. **Si no existe la carpeta `uploads`:**
   - Crearla con el botón **"+ Nueva Carpeta"**
   - Nombre: `uploads`

4. **Establecer permisos correctos:**
   - Click derecho en la carpeta → **Permisos** (Permissions)
   - Establecer a: **755** o **775**
   - ✅ Asegurar que tenga permisos de **escritura**

5. **Estructura final:**
   ```
   /public_html/
   ├── admin/
   ├── uploads/        ← Carpeta para archivos multimedia
   ├── config/
   └── ...
   ```

---

### ✅ Paso 4: Probar las Páginas Corregidas

Abrir en el navegador y verificar que funcionen:

#### 1️⃣ **Gestión de Cupones**
```
https://teal-fish-507993.hostingersite.com/admin/coupons.php
```
- ✅ Debe mostrar la interfaz completa
- ✅ Debe mostrar header y sidebar
- ✅ Debe permitir crear cupones

#### 2️⃣ **Gestión de Reseñas**
```
https://teal-fish-507993.hostingersite.com/admin/reviews.php
```
- ✅ Debe mostrar lista de reseñas
- ✅ Debe permitir aprobar/rechazar
- ✅ Debe permitir responder

#### 3️⃣ **Reportes y Analíticas**
```
https://teal-fish-507993.hostingersite.com/admin/reports.php
```
- ✅ Debe mostrar dashboard con gráficos
- ✅ Debe mostrar KPIs
- ✅ Sin errores de JavaScript

#### 4️⃣ **Email Marketing**
```
https://teal-fish-507993.hostingersite.com/admin/newsletter.php
```
- ✅ Debe mostrar lista de suscriptores
- ✅ Debe permitir crear campañas
- ✅ Debe mostrar estadísticas

#### 5️⃣ **Gestión de Medios** (NUEVO)
```
https://teal-fish-507993.hostingersite.com/admin/media.php
```
- ✅ Debe mostrar galería vacía (si no hay archivos)
- ✅ Debe permitir subir imágenes
- ✅ Debe mostrar estadísticas (0 archivos inicialmente)

---

### ✅ Paso 5: Probar Funcionalidad de Medios

1. **Subir una imagen de prueba:**
   - Click en botón **"Subir Archivo"**
   - Seleccionar una imagen (JPG, PNG, GIF o WEBP)
   - Tamaño máximo: 5MB
   - Click en **"Subir Archivo"**

2. **Verificar que aparezca en la galería:**
   - ✅ Debe mostrarse la miniatura
   - ✅ Debe mostrar el nombre del archivo
   - ✅ Debe mostrar el tamaño en KB
   - ✅ Debe tener botón para copiar URL

3. **Probar copiar URL:**
   - Click en **"Copiar URL"**
   - ✅ Debe mostrar alert con la URL completa
   - ✅ La URL debe ser: `https://teal-fish-507993.hostingersite.com/uploads/media_XXXXX.jpg`

4. **Probar eliminar archivo:**
   - Click en botón de **basura** (🗑️)
   - ✅ Debe pedir confirmación
   - ✅ Debe eliminar el archivo

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### ❌ Error: "Página en blanco"
**Causa:** Error de PHP no visible  
**Solución:**
1. Activar errores en Hostinger:
   - File Manager → Editar `.htaccess`
   - Agregar: `php_flag display_errors on`
2. O revisar logs en: Panel → **Archivos** → **Logs de Errores**

### ❌ Error: "No se puede subir archivo"
**Causa:** Permisos incorrectos en carpeta `uploads/`  
**Solución:**
1. File Manager → Click derecho en `/uploads/`
2. Permisos → Cambiar a **755** o **775**
3. Asegurar que la carpeta existe

### ❌ Error: "Tabla no existe"
**Causa:** No se creó la tabla `media_files`  
**Solución:**
1. phpMyAdmin → Tu base de datos
2. Verificar si existe tabla `media_files`
3. Si no existe, ejecutar el SQL del Paso 2

### ❌ Error: "Cannot modify header information"
**Causa:** Espacio o salida antes de `<?php`  
**Solución:**
1. Abrir archivo en editor
2. Verificar que no haya espacios antes de `<?php`
3. Verificar que no haya BOM en el archivo

### ❌ Imágenes no se muestran
**Causa:** Ruta incorrecta de `uploads/`  
**Solución:**
1. Verificar que la carpeta sea: `/public_html/uploads/`
2. Verificar permisos: **755**
3. Verificar que el archivo exista físicamente

---

## 📊 URLs DE VERIFICACIÓN RÁPIDA

**Panel Admin Principal:**
```
https://teal-fish-507993.hostingersite.com/admin/
```

**Todas las páginas corregidas:**
- [Cupones](https://teal-fish-507993.hostingersite.com/admin/coupons.php)
- [Reseñas](https://teal-fish-507993.hostingersite.com/admin/reviews.php)
- [Reportes](https://teal-fish-507993.hostingersite.com/admin/reports.php)
- [Newsletter](https://teal-fish-507993.hostingersite.com/admin/newsletter.php)
- [Medios](https://teal-fish-507993.hostingersite.com/admin/media.php)

---

## 🎯 VERIFICACIÓN FINAL

Marcar cuando esté completado:

- [ ] Archivos subidos al servidor
- [ ] Tabla `media_files` creada en BD
- [ ] Carpeta `uploads/` existe con permisos correctos
- [ ] Todas las páginas muestran contenido (no páginas en blanco)
- [ ] Se puede subir una imagen de prueba
- [ ] Se puede copiar URL de imagen
- [ ] Se puede eliminar imagen
- [ ] No hay errores en consola del navegador (F12)
- [ ] Header y sidebar se muestran correctamente
- [ ] Sesión de usuario funciona correctamente

---

## 📝 NOTAS IMPORTANTES

1. **Rutas relativas:** Todos los archivos usan rutas relativas (`../uploads/`) que funcionan tanto en local como en Hostinger

2. **Base de datos:** La conexión usa el archivo `inc/auth.php` que ya debe estar configurado para Hostinger

3. **Seguridad:** Solo se permiten imágenes, máximo 5MB por archivo

4. **Backups:** Hostinger hace backups automáticos, pero considera hacer backup manual antes de cambios grandes

5. **Caché:** Si ves páginas antiguas, limpia caché del navegador (Ctrl+Shift+R)

---

## ✅ RESULTADO ESPERADO

Después de seguir todos los pasos:
- ✅ Todas las páginas deben funcionar sin errores
- ✅ No más páginas en blanco
- ✅ Sistema de gestión de medios completamente funcional
- ✅ URLs correctas con dominio de Hostinger

---

**Última actualización:** 13 de Octubre de 2025  
**Servidor:** Hostinger - teal-fish-507993.hostingersite.com
