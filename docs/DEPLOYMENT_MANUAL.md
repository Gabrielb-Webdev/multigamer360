# 🚀 Guía de Deployment Manual - MultiGamer360

## 📋 Problema Actual

Los cambios en el código no se están reflejando automáticamente en Hostinger. Esto puede deberse a:
- ❌ El webhook de GitHub no está configurado correctamente
- ❌ El servidor no tiene permisos para ejecutar `git pull`
- ❌ Hay caché en el servidor o navegador
- ❌ Los archivos no se actualizaron correctamente

## ✅ Solución: Deployment Manual

He creado 2 herramientas para que puedas actualizar el sitio manualmente:

---

## 🔧 Herramienta 1: Script de Verificación

### 📍 URL:
```
https://teal-fish-507993.hostingersite.com/version.php
```

### ¿Qué hace?
- Muestra el último commit que está en el servidor
- Verifica si los archivos están actualizados
- Comprueba si `clearAllFilters()` tiene la corrección aplicada
- Muestra las versiones de CSS/JS

### ¿Cuándo usarlo?
- **Antes** de hacer deployment (para ver versión actual)
- **Después** de hacer deployment (para verificar que se actualizó)
- Cuando quieras verificar qué código está en producción

---

## 🚀 Herramienta 2: Script de Deployment

### 📍 URL:
```
https://teal-fish-507993.hostingersite.com/deploy.php?secret=multigamer360_deploy_2025
```

### ⚠️ IMPORTANTE: 
**Necesita el parámetro `?secret=multigamer360_deploy_2025` para funcionar**

### ¿Qué hace?
1. Verifica que Git esté disponible
2. Guarda cambios locales temporalmente (git stash)
3. Descarga las actualizaciones desde GitHub (git fetch)
4. Aplica las actualizaciones (git pull)
5. Limpia el caché de PHP si está disponible
6. Muestra el resultado de cada paso

### ¿Cuándo usarlo?
- **Después** de hacer `git push` desde tu computadora local
- Cuando quieras forzar la actualización del servidor
- Si el webhook automático no funciona

---

## 📝 Proceso Completo de Actualización

### Paso 1: Verificar versión actual
1. Abre en tu navegador: `https://teal-fish-507993.hostingersite.com/version.php`
2. Busca la sección **"Último Commit"**
3. Anota el hash del commit (ej: `81e0633`)

### Paso 2: Verificar último commit en GitHub
1. Ve a: `https://github.com/Gabrielb-Webdev/multigamer360/commits/main`
2. Compara el hash del commit más reciente
3. Si son diferentes → necesitas hacer deployment

### Paso 3: Ejecutar deployment
1. Abre: `https://teal-fish-507993.hostingersite.com/deploy.php?secret=multigamer360_deploy_2025`
2. Espera a que termine (verás mensajes verdes ✅ si todo va bien)
3. Busca el mensaje final: **"✅ Deployment completado exitosamente"**

### Paso 4: Verificar actualización
1. Vuelve a: `https://teal-fish-507993.hostingersite.com/version.php`
2. Verifica que el **"Último Commit"** ahora coincide con GitHub
3. Verifica que diga: **"✅ Función actualizada correctamente (preserva category)"**

### Paso 5: Limpiar caché del navegador
1. En Chrome/Edge: Presiona `Ctrl + Shift + R` (recarga forzada)
2. En Firefox: Presiona `Ctrl + F5`
3. O abre el sitio en modo incógnito: `Ctrl + Shift + N`

### Paso 6: Probar la funcionalidad
1. Ve a: `https://teal-fish-507993.hostingersite.com/productos.php?category=videojuegos`
2. Haz clic en **NES**
3. Aplica algunos filtros (marca, precio, etc.)
4. Haz clic en **"Limpiar filtros"**
5. **Resultado esperado**: Deberías seguir viendo solo juegos de NES (sin filtros)

---

## 🐛 Solución de Problemas

### Problema: "❌ Git no está disponible"
**Solución:** Contacta a soporte de Hostinger para que habiliten Git en tu cuenta

### Problema: "❌ Permission denied"
**Solución:** Los archivos pueden no tener permisos. Contacta a soporte o usa FTP para actualizar manualmente

### Problema: "Already up to date"
**Solución:** El servidor ya tiene la última versión. Limpia el caché del navegador

### Problema: Sigo sin ver cambios
**Soluciones:**
1. Limpia caché del navegador (Ctrl + Shift + R)
2. Abre en modo incógnito
3. Verifica en `version.php` que los archivos tengan las verificaciones en ✅
4. Si persiste, puede ser caché del servidor Hostinger (espera 5-10 minutos)

---

## 🔐 Seguridad

### Cambiar el secret del deploy
Si quieres cambiar el secret por seguridad:

1. Edita `deploy.php` en la línea 11:
```php
define('DEPLOY_SECRET', 'TU_NUEVO_SECRET_AQUI');
```

2. La nueva URL será:
```
https://teal-fish-507993.hostingersite.com/deploy.php?secret=TU_NUEVO_SECRET_AQUI
```

### Ocultar los scripts en producción
Después de hacer deployment, puedes:
- Renombrar los archivos (ej: `version_oculto.php`)
- Agregar autenticación adicional
- Eliminarlos si ya no los necesitas

---

## 📞 Contacto de Emergencia

Si nada de esto funciona:
1. Verifica que el webhook de GitHub esté configurado en Hostinger
2. Sube los archivos manualmente por FTP
3. Contacta a soporte de Hostinger

---

## 🎯 Resumen Rápido

```
1. Hago cambios en local → git push
2. Verifico: version.php (ver versión actual)
3. Deploy: deploy.php?secret=multigamer360_deploy_2025
4. Verifico: version.php (confirmar actualización)
5. Limpio caché: Ctrl + Shift + R
6. Pruebo: productos.php?category=nes → Limpiar filtros
```

---

## ✅ Últimos Commits Aplicados

```
81e0633 - Fix: Limpiar filtros ahora mantiene la consola seleccionada - v0.2
d346473 - Feature: Filtrado por consola específica usando product_categories
8d24fe8 - Style: Ajustar posicionamiento dropdown consolas - v0.3
```

**Estos commits incluyen:**
- ✅ Filtrado por consola específica (NES solo muestra NES)
- ✅ Botón "Limpiar filtros" mantiene la consola seleccionada
- ✅ Dropdown de consolas reposicionado
- ✅ Mejora de rendimiento en queries de base de datos

---

**Última actualización:** 2025-10-08
**Versión de scripts:** 1.0
