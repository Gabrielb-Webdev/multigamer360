# 🔐 GUÍA DE ACCESO AL PANEL DE ADMINISTRACIÓN - MULTIGAMER360

---

## 📋 INFORMACIÓN GENERAL

El panel de administración de MultiGamer360 es un área **RESTRINGIDA** que solo permite el acceso a usuarios con el rol de **👑 Administrador**.

---

## 🚪 ¿CÓMO ACCEDER AL PANEL DE ADMINISTRACIÓN?

### **URL de Acceso:**
```
http://localhost/multigamer360/admin/login.php  (Local XAMPP)
https://multigamer360.com/admin/login.php        (Producción Hostinger)
```

### **Credenciales de Administrador:**

#### 🔑 **Usuario Administrador Principal**
```
Email:    admin@multigamer360.com
Password: password
Rol:      👑 Administrador
```

> ⚠️ **IMPORTANTE:** Cambia esta contraseña después del primer acceso por seguridad.

---

## 🛡️ SISTEMA DE SEGURIDAD

### **Verificaciones de Acceso:**

1. **Autenticación Requerida**
   - Debes estar logueado con una cuenta activa
   - El email debe estar registrado en la base de datos

2. **Rol Obligatorio**
   - Solo usuarios con rol `administrador` pueden acceder
   - Los clientes, colaboradores y moderadores son **RECHAZADOS**

3. **Cuenta Activa**
   - El campo `is_active` debe ser `1` (TRUE)
   - Cuentas inactivas son redirigidas al login

---

## ❌ USUARIOS QUE **NO** PUEDEN ACCEDER

### **Roles Bloqueados:**
- 🛒 **Cliente** (cliente) - Solo compra productos
- 🤝 **Colaborador** (colaborador) - Gestiona contenido pero NO admin
- 🛡️ **Moderador** (moderador) - Modera reviews pero NO admin

### **¿Qué pasa si intentan acceder?**
```
1. El sistema verifica el rol
2. Log de seguridad registra el intento
3. Sesión destruida por seguridad
4. Redirigido a login con mensaje de error
```

**Mensaje mostrado:**
```
❌ Acceso denegado. Solo los administradores pueden 
   acceder a este panel. Tu rol actual es: [rol]
```

---

## 📊 FLUJO DE AUTENTICACIÓN

```
┌─────────────────────────────────┐
│ Usuario ingresa a /admin        │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ ¿Sesión activa?                 │
└──────────┬──────────────────────┘
           │
    ┌──────┴───────┐
    │ NO           │ SÍ
    ▼              ▼
┌────────┐   ┌─────────────────────┐
│ LOGIN  │   │ Verificar rol       │
└────────┘   └─────────┬───────────┘
                       │
             ┌─────────┴──────────┐
             │                    │
    ┌────────▼────────┐  ┌────────▼────────┐
    │ administrador   │  │ Otro rol        │
    └────────┬────────┘  └────────┬────────┘
             │                    │
    ┌────────▼────────┐  ┌────────▼────────┐
    │ ✅ ACCESO OK   │  │ ❌ DENEGADO    │
    │ Dashboard       │  │ Logout + Error  │
    └─────────────────┘  └─────────────────┘
```

---

## 🗂️ ARCHIVOS DE SEGURIDAD

### **1. admin/login.php**
- Formulario de login exclusivo para el admin
- Verifica credenciales con `UserManagerSimple`
- **Solo acepta rol 'administrador'**
- Registra intentos de acceso en logs

### **2. admin/inc/auth.php**
- Middleware de autenticación
- Se ejecuta en TODAS las páginas del admin
- Verifica sesión activa y rol administrador
- Destruye sesión si no cumple requisitos

### **3. admin/logout.php**
- Cierra sesión del administrador
- Limpia todas las variables de sesión
- Redirige al login

---

## 🔧 ¿CÓMO CREAR MÁS ADMINISTRADORES?

### **Opción 1: Desde phpMyAdmin**
```sql
-- Insertar nuevo administrador
INSERT INTO users (email, password, first_name, last_name, role, is_active, created_at)
VALUES (
    'nuevo_admin@multigamer360.com',
    '$2y$10$...',  -- Genera con password_hash('contraseña', PASSWORD_BCRYPT)
    'Nombre',
    'Apellido',
    'administrador',  -- ← IMPORTANTE: Rol administrador
    1,
    NOW()
);
```

### **Opción 2: Desde el Panel Admin**
```
1. Accede a: /admin/users.php
2. Selecciona un usuario existente
3. Cambia su rol a "👑 Administrador" con el dropdown
4. El usuario ya puede acceder al panel
```

### **Opción 3: Mediante Registro y Actualización**
```sql
-- Primero el usuario se registra normalmente
-- Luego ejecutas este SQL en phpMyAdmin:
UPDATE users 
SET role = 'administrador' 
WHERE email = 'email_del_usuario@ejemplo.com';
```

---

## 🧪 PRUEBAS DE SEGURIDAD

### **Escenario 1: Usuario Cliente Intenta Acceder**
```
1. Cliente con email: cliente@test.com
2. Intenta acceder a: /admin/index.php
3. Resultado: Redirigido a /admin/login.php?error=access_denied
4. Log registra: "ACCESO NO AUTORIZADO - Rol: cliente"
```

### **Escenario 2: Login con Credenciales de Cliente**
```
1. Va a /admin/login.php
2. Ingresa credenciales de cliente
3. Sistema verifica: Rol = 'cliente'
4. Resultado: Error "Acceso denegado. Tu rol actual es: 🛒 Cliente"
5. Sesión NO se crea
```

### **Escenario 3: Administrador Accede Correctamente**
```
1. Va a /admin/login.php
2. Ingresa: admin@multigamer360.com / password
3. Sistema verifica: Rol = 'administrador'
4. Sesión creada con:
   - user_id
   - is_admin = true
   - role = 'administrador'
5. Redirigido a: /admin/index.php (Dashboard)
```

---

## 📝 LOGS DE SEGURIDAD

El sistema registra automáticamente:

### **Accesos Exitosos:**
```
✅ ACCESO ADMIN EXITOSO
   Usuario: admin@multigamer360.com
   Rol: administrador
   IP: 192.168.1.100
   Fecha: 2025-10-10 14:30:00
```

### **Intentos Denegados:**
```
❌ INTENTO DE LOGIN ADMIN DENEGADO
   Usuario: cliente@ejemplo.com
   Rol: cliente
   IP: 192.168.1.105
   Fecha: 2025-10-10 14:35:00
```

### **Acceso No Autorizado:**
```
❌ ACCESO NO AUTORIZADO AL ADMIN
   Usuario: colaborador@ejemplo.com
   Rol: colaborador
   IP: 192.168.1.110
   Fecha: 2025-10-10 14:40:00
```

---

## 🔒 MEJORES PRÁCTICAS DE SEGURIDAD

### ✅ **Recomendaciones:**

1. **Cambiar Contraseña Por Defecto**
   ```sql
   UPDATE users 
   SET password = '$2y$10$...'  -- Genera nueva
   WHERE email = 'admin@multigamer360.com';
   ```

2. **Usar Contraseñas Fuertes**
   - Mínimo 12 caracteres
   - Mayúsculas, minúsculas, números y símbolos
   - Ejemplo: `Mg360@Secure#2025`

3. **Limitar Administradores**
   - Solo crea admins que realmente los necesites
   - Revisa periódicamente la lista de admins

4. **Monitorear Logs**
   - Revisa los logs del servidor regularmente
   - Detecta intentos sospechosos de acceso

5. **Activar HTTPS**
   - En producción, usa siempre HTTPS
   - Hostinger lo proporciona gratis

---

## 🚨 ¿QUÉ HACER SI OLVIDAS LA CONTRASEÑA?

### **Método 1: Resetear desde phpMyAdmin**
```sql
-- Cambiar contraseña del admin principal
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@multigamer360.com';

-- Contraseña nueva será: password
```

### **Método 2: Crear Script de Reset**
```php
// reset_admin_password.php (en la raíz, eliminar después)
<?php
require_once 'config/database.php';
$new_password = password_hash('nueva_password', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$new_password, 'admin@multigamer360.com']);
echo "Contraseña actualizada!";
?>
```

---

## 📞 SOPORTE

### **Problemas Comunes:**

**❓ "No puedo acceder al admin"**
- Verifica que tu usuario tenga rol 'administrador' en la BD
- Verifica que `is_active` = 1
- Limpia cookies y caché del navegador

**❓ "Me dice que no tengo permisos"**
- Tu rol no es 'administrador'
- Contacta al super admin para cambiar tu rol

**❓ "Olvidé mi contraseña"**
- Usa el método de reset desde phpMyAdmin

---

## ✅ CHECKLIST POST-INSTALACIÓN

- [ ] Ejecutar SQL para crear usuario admin
- [ ] Probar login en /admin/login.php
- [ ] Cambiar contraseña por defecto
- [ ] Verificar que clientes no puedan acceder
- [ ] Revisar que aparezca el nombre del admin en el navbar
- [ ] Probar cerrar sesión
- [ ] Verificar logs de acceso

---

**🎮 MultiGamer360 - Panel de Administración**
*Sistema de Seguridad Implementado - Octubre 2025*

---

## 🎯 RESUMEN RÁPIDO

| Aspecto | Detalle |
|---------|---------|
| **URL Login** | `/admin/login.php` |
| **Rol Requerido** | `administrador` |
| **Email Default** | `admin@multigamer360.com` |
| **Password Default** | `password` ⚠️ Cambiar |
| **Archivo Auth** | `admin/inc/auth.php` |
| **Sistema** | `UserManagerSimple` |
| **Roles Bloqueados** | cliente, colaborador, moderador |

