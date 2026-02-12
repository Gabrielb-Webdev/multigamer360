# 🔄 SISTEMA DE VERSIONADO DE ASSETS (CSS/JS)

## Fecha: 13 de Octubre de 2025
## Servidor: https://teal-fish-507993.hostingersite.com/

---

## ❓ ¿POR QUÉ ES NECESARIO?

### Problema del Caché:
Cuando actualizas archivos CSS o JavaScript, los navegadores (y servidores) pueden seguir mostrando la **versión antigua** guardada en caché, lo que causa:

- ❌ Los estilos nuevos no se aplican
- ❌ Los cambios de JavaScript no funcionan
- ❌ Los usuarios ven la interfaz antigua
- ❌ Frustración porque "los cambios no se reflejan"

### Solución con Versionado:
Al agregar `?v=1.1` al final de la URL, el navegador lo considera un archivo **diferente** y lo descarga nuevamente.

```php
<!-- SIN versión (usa caché) -->
<link href="assets/css/admin.css" rel="stylesheet">

<!-- CON versión (fuerza descarga) -->
<link href="assets/css/admin.css?v=1.1" rel="stylesheet">
```

---

## 📝 ARCHIVOS ACTUALIZADOS

### 1. `/admin/inc/header.php`
```php
<!-- Admin CSS -->
<link href="assets/css/admin.css?v=1.1" rel="stylesheet">
```

### 2. `/admin/inc/footer.php`
```php
<!-- Admin JS -->
<script src="assets/js/admin.js?v=1.1"></script>
```

---

## 🔢 SISTEMA DE VERSIONES

### Versión Actual: **1.1**

### Cuándo Incrementar:

| Cambio | Acción | Ejemplo |
|--------|--------|---------|
| **CSS modificado** | Incrementar versión | `1.1` → `1.2` |
| **JS modificado** | Incrementar versión | `1.1` → `1.2` |
| **Cambios menores** | Decimal | `1.1` → `1.2` |
| **Cambios mayores** | Entero | `1.9` → `2.0` |
| **Hotfix urgente** | Agregar letra | `1.1` → `1.1a` |

---

## 📋 PROCEDIMIENTO PARA FUTUROS CAMBIOS

### Paso 1: Hacer Cambios en CSS/JS
Editar los archivos:
- `admin/assets/css/admin.css`
- `admin/assets/js/admin.js`

### Paso 2: Incrementar Versión
Editar `admin/inc/header.php` y `admin/inc/footer.php`:

```php
// DE:
admin.css?v=1.1
admin.js?v=1.1

// A:
admin.css?v=1.2
admin.js?v=1.2
```

### Paso 3: Subir a Hostinger
Subir los archivos modificados:
- ✅ `admin/inc/header.php`
- ✅ `admin/inc/footer.php`
- ✅ `admin/assets/css/admin.css` (si cambió)
- ✅ `admin/assets/js/admin.js` (si cambió)

### Paso 4: Verificar
Abrir la página en el navegador:
1. Presionar **Ctrl + Shift + R** (forzar recarga)
2. Verificar que los cambios se reflejan
3. Abrir DevTools (F12) → Network → Verificar que carga la nueva versión

---

## 🎯 ARCHIVOS SUBIR A HOSTINGER

Para que las mejoras visuales de **coupons.php** se reflejen:

### Archivos Obligatorios:
```
✅ /admin/inc/header.php (v=1.1 agregado)
✅ /admin/inc/footer.php (v=1.1 agregado)
✅ /admin/coupons.php (mejoras visuales)
```

### Archivos Opcionales (si fueron modificados):
```
□ /admin/assets/css/admin.css
□ /admin/assets/js/admin.js
```

---

## 🚀 INSTRUCCIONES DE SUBIDA

### Opción 1: File Manager de Hostinger
1. Panel Hostinger → **File Manager**
2. Navegar a `/public_html/admin/inc/`
3. Subir y reemplazar:
   - `header.php`
   - `footer.php`
4. Navegar a `/public_html/admin/`
5. Subir y reemplazar:
   - `coupons.php`

### Opción 2: FTP (FileZilla)
1. Conectar a Hostinger vía FTP
2. Navegar a `/public_html/admin/inc/`
3. Arrastrar los archivos:
   - `header.php`
   - `footer.php`
4. Navegar a `/public_html/admin/`
5. Arrastrar:
   - `coupons.php`

---

## 🧪 VERIFICACIÓN POST-SUBIDA

### 1. Limpiar Caché del Navegador
```
Chrome/Edge: Ctrl + Shift + Delete
Firefox: Ctrl + Shift + Delete
Safari: Cmd + Option + E
```

### 2. Forzar Recarga
```
Windows: Ctrl + Shift + R
Mac: Cmd + Shift + R
```

### 3. Verificar en DevTools
1. Presionar **F12**
2. Ir a pestaña **Network**
3. Recargar página
4. Buscar `admin.css` y `admin.js`
5. Verificar que la columna **Name** muestre `?v=1.1`

### 4. Verificar Cambios Visuales
Abrir: `https://teal-fish-507993.hostingersite.com/admin/coupons.php`

Verificar que se muestre:
- ✅ Header con gradiente morado
- ✅ Códigos de cupón en rosa
- ✅ Tabla con diseño moderno
- ✅ Botones coloridos
- ✅ Badges con iconos

---

## 📊 HISTORIAL DE VERSIONES

| Versión | Fecha | Cambios |
|---------|-------|---------|
| **1.0** | Inicial | CSS/JS base del admin |
| **1.1** | 13/10/2025 | Mejoras visuales en coupons.php |

---

## 💡 TIPS Y BUENAS PRÁCTICAS

### ✅ Hacer:
- Incrementar versión con cada cambio CSS/JS
- Documentar qué cambió en cada versión
- Probar en navegador incógnito después de subir
- Usar números secuenciales (1.1, 1.2, 1.3...)

### ❌ Evitar:
- No incrementar versión (cambios no se verán)
- Usar versiones aleatorias (1.5, 1.2, 1.9)
- Olvidar subir header.php/footer.php
- No verificar después de subir

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### Problema: Los cambios no se ven
**Solución:**
1. ✅ Verificar que subiste `header.php` y `footer.php`
2. ✅ Verificar que incrementaste la versión
3. ✅ Forzar recarga: Ctrl + Shift + R
4. ✅ Limpiar caché del navegador
5. ✅ Probar en modo incógnito

### Problema: Error 404 en admin.css
**Solución:**
1. Verificar que el archivo existe en `/public_html/admin/assets/css/`
2. Verificar permisos del archivo (644)
3. Verificar ruta relativa en header.php

### Problema: Versión antigua en algunos navegadores
**Solución:**
- Incrementar versión a un número mayor
- Por ejemplo: `1.1` → `1.5` o `2.0`

---

## 🎓 EXPLICACIÓN TÉCNICA

### Cómo Funciona el Cache-Busting:

```
URL sin versión:
https://site.com/admin/assets/css/admin.css
└─ Browser: "Ya tengo este archivo, uso el de caché"

URL con versión:
https://site.com/admin/assets/css/admin.css?v=1.1
└─ Browser: "Este es un archivo diferente, lo descargo"
```

El parámetro `?v=1.1` es un **query string** que el servidor ignora, pero el navegador lo considera una URL diferente, forzando la descarga.

---

## 📱 COMPATIBLE CON

- ✅ Todos los navegadores modernos
- ✅ Chrome, Firefox, Safari, Edge
- ✅ Navegadores móviles
- ✅ No afecta SEO
- ✅ No requiere configuración del servidor

---

## 🎯 RESULTADO ESPERADO

Después de subir los archivos con versionado:

1. **Primera visita:**
   - Descarga nueva versión de CSS/JS
   - Muestra interfaz actualizada
   - Guarda en caché versión 1.1

2. **Visitas posteriores:**
   - Usa versión 1.1 de caché
   - Carga más rápido

3. **Próxima actualización:**
   - Cambias versión a 1.2
   - Descarga nueva versión
   - Ciclo se repite

---

## 📚 DOCUMENTACIÓN RELACIONADA

- `MEJORAS_VISUALES_COUPONS.md` - Detalles de las mejoras visuales
- `CORRECCION_URGENTE_PAGINAS_BLANCAS.md` - Corrección del error de UserManager
- `GUIA_IMPLEMENTACION_HOSTINGER.md` - Guía general de implementación

---

**Última actualización:** 13 de Octubre de 2025  
**Versión actual:** 1.1  
**Archivos modificados:** 2 (header.php, footer.php)  
**Próxima versión:** 1.2 (cuando haya nuevos cambios)
