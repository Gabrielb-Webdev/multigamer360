# 🌐 GUÍA COMPLETA - CONFIGURACIÓN HOSTINGER

## 📋 INFORMACIÓN DE TU BASE DE DATOS HOSTINGER

```
Host: localhost (o la IP que te dio Hostinger)
Base de datos: u851317150_mg360_db
Usuario: u851317150_mg360_user
Contraseña: MultiGamer2025
```

---

## 🚀 PASO 1: CREAR ESTRUCTURA EN HOSTINGER

### A. Acceder a phpMyAdmin de Hostinger

1. **Inicia sesión en tu panel de Hostinger:**
   - https://hpanel.hostinger.com

2. **Ve a la sección de Bases de Datos:**
   - Busca "Bases de Datos" o "phpMyAdmin"
   - Haz clic en "Administrar" junto a tu base de datos

3. **Se abrirá phpMyAdmin de Hostinger**

---

### B. Ejecutar Script SQL

1. **En phpMyAdmin de Hostinger:**
   - Panel izquierdo → Selecciona: **`u851317150_mg360_db`**
   - Clic en la pestaña: **SQL** (arriba)

2. **Copia el contenido completo de:**
   ```
   config/hostinger_estructura.sql
   ```

3. **Pega en el cuadro de texto SQL**

4. **Haz clic en:** "Ejecutar" o "Go"

5. **Resultado esperado:**
   ```
   ✅ ESTRUCTURA CREADA EXITOSAMENTE EN HOSTINGER
   ```

---

## 🔐 CREDENCIALES ADMIN CREADAS

Después de ejecutar el script, tendrás un usuario admin:

```
Email: admin@multigamer360.com
Password: password
```

⚠️ **IMPORTANTE:** Cambiar esta contraseña después del primer login

---

## ✅ PASO 2: VERIFICAR QUE FUNCIONA

### Probar Registro de Usuario

1. **Sube tus archivos a Hostinger** (si aún no lo has hecho)

2. **Accede a tu sitio:**
   ```
   https://tu-dominio.com/register.php
   ```

3. **Registra un usuario de prueba:**
   - Nombre: Test
   - Apellido: Usuario
   - Email: test@example.com
   - Password: Test1234!
   - Confirmar Password: Test1234!

4. **Verifica en phpMyAdmin:**
   ```sql
   SELECT id, email, first_name, last_name, role, created_at 
   FROM users 
   ORDER BY created_at DESC;
   ```

---

### Probar Login

1. **Accede a:**
   ```
   https://tu-dominio.com/login.php
   ```

2. **Inicia sesión con el usuario que registraste**

3. **Deberías ver:**
   - Sesión iniciada
   - Nombre del usuario en el header
   - Acceso al perfil

---

## 📁 PASO 3: SUBIR ARCHIVOS A HOSTINGER

### Archivos que DEBES subir:

```
multigamer360/
├── config/
│   ├── database.php ✅ (YA CONFIGURADO para Hostinger)
│   └── user_manager_simple.php ✅
├── includes/
│   └── (todos los archivos)
├── assets/
│   └── (css, js, images)
├── ajax/
│   └── (todos los archivos)
├── index.php
├── register.php ✅
├── login.php ✅
├── logout.php
└── ... (resto de archivos PHP)
```

### Método de subida:

**Opción A: Usar el File Manager de Hostinger**
1. Panel Hostinger → File Manager
2. Arrastra tus archivos
3. Estructura correcta

**Opción B: Usar FTP/SFTP**
1. Usa FileZilla o WinSCP
2. Credenciales FTP de Hostinger
3. Sube todo el proyecto

---

## 🔧 VERIFICAR CONFIGURACIÓN

### 1. Verificar database.php

Tu archivo `config/database.php` YA está configurado correctamente:

```php
<?php
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost');

if ($is_local) {
    // LOCAL
    $host = 'localhost';
    $dbname = 'multigamer360';
    $username = 'root';
    $password = '';
} else {
    // HOSTINGER (PRODUCCIÓN)
    $host = 'localhost';
    $dbname = 'u851317150_mg360_db';
    $username = 'u851317150_mg360_user';
    $password = 'MultiGamer2025';
}
?>
```

✅ **Esto detecta automáticamente si estás en local o en Hostinger**

---

### 2. Verificar register.php

Ya usa: `config/user_manager_simple.php` ✅

---

### 3. Verificar login.php

Déjame revisar si necesita ajustes...

---

## 🌍 URLs DE TU SITIO

Una vez subido a Hostinger:

```
Frontend: https://tu-dominio.com/
Registro: https://tu-dominio.com/register.php
Login: https://tu-dominio.com/login.php
Admin: https://tu-dominio.com/admin/
```

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Error: "No se puede conectar a la base de datos"

**Verifica:**
1. Las credenciales en `config/database.php`
2. Que la base de datos esté activa en Hostinger
3. Que el usuario tenga permisos

**Prueba la conexión:**
```php
// test_connection.php
<?php
require_once 'config/database.php';
echo "✅ Conexión exitosa a: " . $dbname;
?>
```

---

### Error: "Table users doesn't exist"

→ Ejecuta `config/hostinger_estructura.sql` en phpMyAdmin de Hostinger

---

### Error: "Access denied"

→ Verifica usuario y contraseña en el panel de Hostinger

---

## ✅ CHECKLIST COMPLETO

- [ ] Ejecutar `hostinger_estructura.sql` en phpMyAdmin de Hostinger
- [ ] Verificar que se crearon todas las tablas
- [ ] Verificar que existe el usuario admin
- [ ] Subir archivos a Hostinger
- [ ] Probar registro de usuario nuevo
- [ ] Probar login con usuario registrado
- [ ] Probar login con admin
- [ ] Cambiar contraseña del admin

---

## 📞 PRÓXIMOS PASOS

1. ✅ Ejecuta el SQL en Hostinger
2. ✅ Sube los archivos
3. ✅ Prueba el registro
4. ✅ Prueba el login
5. ⏭️ Agrega productos desde el admin
6. ⏭️ Prueba el carrito de compras
7. ⏭️ Configura pagos

---

**¿En qué paso estás ahora?**
- ¿Ya ejecutaste el SQL en Hostinger?
- ¿Ya subiste los archivos?
- ¿Necesitas ayuda con algún paso específico?
