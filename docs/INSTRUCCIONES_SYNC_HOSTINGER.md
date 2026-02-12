# 🚨 INSTRUCCIONES URGENTES - FORZAR ACTUALIZACIÓN EN HOSTINGER

## ⚠️ SITUACIÓN ACTUAL

- ✅ Código corregido está en GitHub (commit: 3327179)
- ❌ Hostinger NO está sincronizando automáticamente
- ❌ El sitio sigue mostrando la versión antigua con error de Bootstrap

## 🎯 OBJETIVO

Forzar que Hostinger actualice el archivo `product-details.php` desde GitHub.

---

## 📋 MÉTODO 1: Activar Sincronización Automática (RECOMENDADO)

### Paso 1: Ir a Hostinger Panel
1. Ve a https://hpanel.hostinger.com
2. Selecciona tu hosting
3. Busca la sección **"Git"** o **"GitHub"**

### Paso 2: Verificar Branch
- Asegúrate de que esté conectado a la rama `main`
- Si está en otra rama, cámbiala a `main`

### Paso 3: Forzar Pull Manual
- Busca el botón **"Pull"** o **"Sync"** o **"Update"**
- Haz clic para forzar la actualización
- Espera 30 segundos

### Paso 4: Verificar Actualización
Ve a: https://teal-fish-507993.hostingersite.com/version-check.php

Debe mostrar:
```json
{
  "current_version": "2.2",
  "commit": "3327179",
  "status": "FORCE_UPDATE_2025_10_17_21_00"
}
```

---

## 📋 MÉTODO 2: Usar Webhook de GitHub (AUTOMÁTICO)

### Configurar Webhook en GitHub

1. Ve a tu repositorio: https://github.com/Gabrielb-Webdev/multigamer360
2. Ve a **Settings** → **Webhooks** → **Add webhook**

3. Configura:
   - **Payload URL**: `https://teal-fish-507993.hostingersite.com/github-webhook.php`
   - **Content type**: `application/json`
   - **Secret**: (dejar vacío o crear uno)
   - **Events**: Selecciona "Just the push event"
   - Click **Add webhook**

4. Crea el archivo webhook en tu proyecto local (ya lo hice abajo)

### Archivo github-webhook.php (Crear este archivo)

Voy a crearlo ahora...

---

## 📋 MÉTODO 3: Forzar via SSH (SI TIENES ACCESO)

```bash
# Conectar a Hostinger
ssh u123456789@srv123456.hstgr.cloud

# Ir al directorio
cd public_html

# Forzar pull
git fetch origin
git reset --hard origin/main
git pull origin main

# Verificar versión
head -10 product-details.php
```

---

## 📋 MÉTODO 4: Subir Manualmente via FTP (ÚLTIMO RECURSO)

1. Descarga el archivo local: `F:\xampp\htdocs\multigamer360\product-details.php`
2. Ve a Hostinger File Manager
3. Navega a `public_html/`
4. Sube el archivo (Overwrite)
5. Verifica permisos (644)

---

## ✅ VERIFICACIÓN FINAL

Después de cualquier método, verifica:

1. **Version Check**: https://teal-fish-507993.hostingersite.com/version-check.php
   - Debe mostrar versión 2.2

2. **Producto**: https://teal-fish-507993.hostingersite.com/product-details.php?id=3
   - NO debe mostrar error en consola
   - Debe cargar la página correctamente

3. **Consola del navegador** (F12):
   - ✅ NO debe aparecer: "bootstrap is not defined"
   - ✅ Debe aparecer: "Cart loaded from PHP"

---

## 🔧 SI NADA FUNCIONA

Si ningún método funciona, es probable que:

1. **Git está desconectado en Hostinger**
   - Solución: Reconectar GitHub en el panel
   
2. **Hay caché agresivo**
   - Solución: Deshabilitar OPcache o limpiar caché
   
3. **El branch es incorrecto**
   - Solución: Verificar que esté en `main` no en `master`

---

## 📞 PRÓXIMO PASO INMEDIATO

**HÁGANME SABER:**
¿Cuál método vas a usar? Te guío paso a paso.

1. ¿Tienes acceso al panel de Hostinger? → Usar Método 1
2. ¿Tienes acceso SSH? → Usar Método 3
3. ¿Ninguno? → Usar Método 4 (FTP)

---

**Commit actual en GitHub:** 3327179
**Versión requerida en Hostinger:** 2.2
**Estado:** 🔴 PENDIENTE DE SINCRONIZACIÓN
