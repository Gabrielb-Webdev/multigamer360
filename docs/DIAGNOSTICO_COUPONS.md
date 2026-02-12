# 🔧 DIAGNÓSTICO RÁPIDO - Coupons.php en Blanco

## Servidor: https://teal-fish-507993.hostingersite.com/

---

## 📋 PASOS PARA DIAGNOSTICAR

### 1️⃣ Subir Archivos Actualizados

Subir a Hostinger (`/public_html/admin/`):
- ✅ `coupons.php` (corregido)
- ✅ `diagnostico_coupons.php` (nuevo - para diagnóstico)

### 2️⃣ Ejecutar Diagnóstico

**Abrir en navegador:**
```
https://teal-fish-507993.hostingersite.com/admin/diagnostico_coupons.php
```

Este archivo verificará:
- ✅ Si existe `inc/auth.php`
- ✅ Si la conexión a BD funciona
- ✅ Si existe la tabla `coupons`
- ✅ Si la consulta SQL funciona
- ✅ Si existen los templates (header, sidebar, footer)
- ✅ Si la sesión está activa

### 3️⃣ Leer los Resultados

El diagnóstico mostrará **exactamente** dónde está el problema:

- ✅ = Todo OK
- ❌ = Problema encontrado

---

## 🐛 PROBLEMAS COMUNES Y SOLUCIONES

### Problema 1: auth.php no existe
**Solución:** Subir el archivo `inc/auth.php` a `/public_html/admin/inc/`

### Problema 2: Tabla coupons no existe
**Solución:** Ejecutar en phpMyAdmin:
```sql
-- Copiar el contenido de config/coupons_table.sql
```

### Problema 3: Error de conexión a BD
**Solución:** Verificar `config/database.php` con las credenciales correctas de Hostinger

### Problema 4: Templates no existen
**Solución:** Subir los archivos:
- `inc/header.php`
- `inc/sidebar.php`
- `inc/footer.php`

### Problema 5: No hay sesión activa
**Solución:** Hacer login primero en:
```
https://teal-fish-507993.hostingersite.com/admin/login.php
```

---

## 🔍 CAMBIOS REALIZADOS EN COUPONS.PHP

1. ✅ **Eliminada instancia duplicada de UserManager**
2. ✅ **Corregidos nombres de campos SQL:**
   - `starts_at` → `start_date`
   - `expires_at` → `end_date`
   - Agregado campo `name` (faltaba)
   - Agregado campo `per_user_limit`

3. ✅ **Agregado manejo de errores:**
   - Try-catch en consulta SQL
   - Logging de errores
   - Mensaje amigable al usuario

4. ✅ **Agregado debug temporal:**
   - Log de errores en archivo
   - No muestra errores en pantalla (seguridad)

---

## 📝 ARCHIVOS IMPORTANTES

### En tu computadora (local):
```
f:\xampp\htdocs\multigamer360\admin\coupons.php (corregido)
f:\xampp\htdocs\multigamer360\admin\diagnostico_coupons.php (nuevo)
```

### En Hostinger (servidor):
```
/public_html/admin/coupons.php
/public_html/admin/diagnostico_coupons.php
/public_html/admin/inc/auth.php
/public_html/admin/inc/header.php
/public_html/admin/inc/sidebar.php
/public_html/admin/inc/footer.php
```

---

## ⚡ PROCEDIMIENTO RÁPIDO

1. **Subir archivos:**
   ```
   - Ir a File Manager de Hostinger
   - Navegar a /public_html/admin/
   - Subir coupons.php (reemplazar)
   - Subir diagnostico_coupons.php (nuevo)
   ```

2. **Ejecutar diagnóstico:**
   ```
   Abrir: https://teal-fish-507993.hostingersite.com/admin/diagnostico_coupons.php
   ```

3. **Leer resultados:**
   ```
   - Ver qué paso muestra ❌
   - Seguir la solución indicada
   - Volver a probar
   ```

4. **Probar coupons.php:**
   ```
   Abrir: https://teal-fish-507993.hostingersite.com/admin/coupons.php
   ```

5. **Eliminar diagnóstico:**
   ```
   - Una vez funcionando, eliminar diagnostico_coupons.php
   - Por seguridad
   ```

---

## 🎯 RESULTADO ESPERADO

Después de seguir estos pasos:
- ✅ El diagnóstico muestra todos los pasos con ✅
- ✅ coupons.php carga correctamente
- ✅ Se muestra la lista de cupones
- ✅ Se puede crear nuevos cupones

---

## 📞 SI AÚN NO FUNCIONA

Captura de pantalla del resultado del diagnóstico y compártela para ver exactamente dónde está el problema.

---

**Última actualización:** 13 de Octubre de 2025  
**Archivos modificados:** 2  
**Tiempo estimado:** 10 minutos
