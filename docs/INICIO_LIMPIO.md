# 🚀 GUÍA DE INICIO - MULTIGAMER360 LIMPIO

## ✅ ARCHIVOS LIMPIADOS

Se han eliminado los siguientes archivos innecesarios:
- ❌ `test_connection.php`
- ❌ `test_images.php`  
- ❌ `test_simple.php`
- ❌ `database_export.sql`
- ❌ `multigamer360.sql`
- ❌ `fix_image_paths.php`
- ❌ `fix_permissions.php`
- ❌ `setup_server.php`
- ❌ `deploy.php`
- ❌ `version.php`
- ❌ `admin/test_login.php`
- ❌ `admin/setup_roles.php`
- ❌ `admin/api/test_delete.php`
- ❌ `admin/api/test_endpoint.php`

---

## 📋 PASO 1: CREAR ESTRUCTURA DE BASE DE DATOS

### Abrir phpMyAdmin
http://localhost/phpmyadmin

### Ejecutar Script SQL
1. Selecciona la base de datos `multigamer360`
2. Ve a la pestaña **SQL**
3. Copia y pega el contenido de:
   ```
   config/01_estructura_minima.sql
   ```
4. Haz clic en **Ejecutar**

### ¿Qué crea este script?
- ✅ Tabla `users` (usuarios)
- ✅ Tabla `categories` (categorías)
- ✅ Tabla `brands` (marcas)
- ✅ Tabla `products` (productos)
- ✅ Tabla `cart_items` (carrito)
- ✅ Tabla `user_favorites` (wishlist)
- ✅ Usuario admin por defecto

---

## 🔐 CREDENCIALES ADMIN

**Email:** admin@multigamer360.com  
**Password:** password

⚠️ **CAMBIAR INMEDIATAMENTE**

---

## 📝 PASO 2: PROBAR REGISTRO DE USUARIOS

### Formulario de Registro
URL: http://localhost/multigamer360/register.php

### Campos Requeridos:
- ✅ **Nombre** (solo letras, espacios, acentos)
- ✅ **Apellido** (solo letras, espacios, acentos)
- ✅ **Email** (formato válido)
- ✅ **Contraseña** (mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo)
- ✅ **Confirmar Contraseña**
- ⭕ **Teléfono** (opcional)

### Ejemplo de Contraseña Válida:
```
MultiGamer360!
```

**Requisitos:**
- ✅ Mínimo 8 caracteres
- ✅ Al menos una mayúscula (M, G)
- ✅ Al menos una minúscula (ulti, amer)
- ✅ Al menos un número (3, 6, 0)
- ✅ Al menos un símbolo especial (!)

---

## 🎯 PASO 3: VERIFICAR REGISTRO

### Opción A: Ver en phpMyAdmin
```sql
SELECT id, email, first_name, last_name, role, created_at 
FROM users 
ORDER BY created_at DESC;
```

### Opción B: Iniciar Sesión
URL: http://localhost/multigamer360/login.php

Usa las credenciales que acabas de registrar.

---

## 🔧 ESTRUCTURA SIMPLIFICADA

### Sistema de Roles Simple
- **admin** → Acceso completo al panel de administración
- **customer** → Usuario normal (compra productos)

### No usa tabla de roles compleja
El sistema ahora usa un campo simple `role` en la tabla users.

---

## 📊 ARCHIVOS MODIFICADOS

### Nuevos Archivos:
- ✅ `config/01_estructura_minima.sql` - Estructura básica de BD
- ✅ `config/user_manager_simple.php` - Gestor de usuarios simplificado

### Archivos Actualizados:
- ✅ `register.php` - Usa el nuevo UserManagerSimple

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Error: "Table 'users' doesn't exist"
→ Ejecuta `config/01_estructura_minima.sql`

### Error: "Email ya está registrado"
→ El email ya existe en la base de datos. Usa otro email.

### Error: "Contraseña debe tener..."
→ Tu contraseña no cumple los requisitos de seguridad:
  - Mínimo 8 caracteres
  - Una mayúscula
  - Una minúscula
  - Un número
  - Un símbolo (!@#$%^&*)

### Error: "Error de conexión"
→ Verifica `config/database.php`:
```php
$host = 'localhost';
$dbname = 'multigamer360';
$username = 'root';
$password = '';
```

### MySQL no está corriendo
→ Abre XAMPP Control Panel y arranca MySQL

---

## 📈 PRÓXIMOS PASOS

1. ✅ Crear estructura de BD
2. ✅ Probar registro de usuario
3. ⏭️ Probar login
4. ⏭️ Agregar categorías desde admin
5. ⏭️ Agregar productos desde admin
6. ⏭️ Probar carrito de compras

---

## 🌐 URLs IMPORTANTES

- **Frontend:** http://localhost/multigamer360/
- **Registro:** http://localhost/multigamer360/register.php
- **Login:** http://localhost/multigamer360/login.php
- **Admin:** http://localhost/multigamer360/admin/
- **phpMyAdmin:** http://localhost/phpmyadmin

---

**Creado:** 2025-10-10  
**Versión:** 1.0 - Sistema Simplificado
