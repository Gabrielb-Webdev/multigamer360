# 🚀 INSTRUCCIONES PARA SUBIR CAMBIOS A HOSTINGER

## ⚠️ PROBLEMA ACTUAL
El error `bootstrap is not defined` aparece porque **Hostinger tiene una versión antigua** del archivo `product-details.php`.

Los cambios que hice en el archivo local **NO están en el servidor de Hostinger**.

## ✅ SOLUCIÓN: Subir archivo actualizado

### Opción 1: Via File Manager de Hostinger (RECOMENDADO)

1. **Accede a tu panel de Hostinger:**
   - Ve a: https://hpanel.hostinger.com
   - Inicia sesión con tus credenciales

2. **Abre el File Manager:**
   - En el panel, busca "Archivos" o "File Manager"
   - Click en "File Manager"

3. **Navega a public_html:**
   - Busca la carpeta `public_html` o `htdocs`
   - Esta es la raíz de tu sitio web

4. **Sube el archivo actualizado:**
   - Busca el archivo `product-details.php` actual
   - **Elimínalo** o renómbralo a `product-details.php.old`
   - Click en "Upload" o "Subir"
   - Selecciona el archivo local: `f:\xampp\htdocs\multigamer360\product-details.php`
   - Espera a que termine la carga

5. **Limpia la caché:**
   - En el panel de Hostinger, busca "Clear Cache" o "Limpiar Caché"
   - Click para limpiar la caché del sitio

### Opción 2: Via FTP (FileZilla)

Si tienes acceso FTP configurado:

1. **Abre FileZilla** (o tu cliente FTP favorito)

2. **Conéctate a Hostinger:**
   - Host: ftp.hostingersite.com (o el que te dieron)
   - Usuario: tu usuario FTP
   - Contraseña: tu contraseña FTP
   - Puerto: 21

3. **Navega a la carpeta correcta:**
   - Panel derecho: busca `public_html`

4. **Sube el archivo:**
   - Panel izquierdo: navega a `f:\xampp\htdocs\multigamer360`
   - Arrastra `product-details.php` al panel derecho
   - Confirma sobrescribir

### Opción 3: Via Git (Si usas deployment automático)

Si tienes configurado Git con Hostinger:

```bash
cd f:\xampp\htdocs\multigamer360
git add product-details.php
git commit -m "Fix: Eliminar script problemático de Bootstrap en product-details"
git push origin main
```

## 🔄 DESPUÉS DE SUBIR EL ARCHIVO

1. **Limpia la caché del navegador:**
   - Presiona `Ctrl + Shift + Delete`
   - Selecciona "Caché" o "Archivos en caché"
   - Click en "Limpiar" o "Borrar"

2. **Fuerza la recarga de la página:**
   - Ve a: https://teal-fish-507993.hostingersite.com/product-details.php?id=10
   - Presiona `Ctrl + Shift + R` (o `Ctrl + F5`)

3. **Verifica en la consola:**
   - Presiona `F12` para abrir DevTools
   - Ve a la pestaña "Console"
   - Recarga la página
   - **NO debería aparecer** el error de `bootstrap is not defined`

## 📝 ARCHIVOS QUE TAMBIÉN DEBERÍAS SUBIR

Si hiciste cambios en estos archivos, súbelos también:

- ✅ `product-details.php` (OBLIGATORIO)
- ✅ `productos.php` (tiene el cambio del rango de precio con fondo blanco)

## ❓ ¿Necesitas las credenciales de Hostinger?

Si no tienes acceso al panel de Hostinger:
1. Revisa tu email de bienvenida de Hostinger
2. O recupera la contraseña en: https://hpanel.hostinger.com/password-reset

## 🎯 RESULTADO ESPERADO

Después de subir el archivo y limpiar la caché:
- ✅ La página carga correctamente
- ✅ NO hay errores en la consola
- ✅ Los productos se muestran normalmente
- ✅ El rango de precio tiene fondo blanco y letras negras
