# 🔧 SOLUCIÓN AL ERROR DE PHPMY ADMIN

## ❌ ERROR ACTUAL

```
#1044 - Acceso denegado para usuario: 'u851317150_mg360_user'@'127.0.0.1' 
a la base de datos 'multigamer360'
```

**Problema:** phpMyAdmin está usando credenciales de producción/hosting en lugar de las credenciales locales de XAMPP.

---

## ✅ SOLUCIÓN RÁPIDA

### Opción 1: Desconectar y Reconectar en phpMyAdmin

1. **Cierra sesión en phpMyAdmin:**
   - Ve a: http://localhost/phpmyadmin
   - Busca el ícono de "Salir" o "Cerrar sesión"
   - Haz clic en "Cerrar sesión"

2. **Inicia sesión nuevamente con credenciales correctas:**
   ```
   Usuario: root
   Contraseña: (dejar en blanco o vacío)
   ```

3. **Verifica la conexión:**
   - En phpMyAdmin, deberías ver tu usuario como: `root@localhost`
   - NO como: `u851317150_mg360_user@127.0.0.1`

---

### Opción 2: Abrir phpMyAdmin en Incógnito

1. **Abre una ventana de incógnito** en tu navegador (Ctrl+Shift+N)

2. **Ve a:**
   ```
   http://localhost/phpmyadmin
   ```

3. **Inicia sesión:**
   ```
   Usuario: root
   Contraseña: (vacío)
   ```

---

### Opción 3: Limpiar Cookies de phpMyAdmin

1. **Abre las herramientas de desarrollador** (F12)
2. **Ve a:** Aplicación → Cookies
3. **Elimina todas las cookies de:** `localhost`
4. **Recarga la página**
5. **Inicia sesión nuevamente**

---

## 📋 PASOS DESPUÉS DE RECONECTAR

Una vez que estés conectado como `root@localhost`:

### 1. Seleccionar Base de Datos
- En el panel izquierdo, haz clic en: **`multigamer360`**
- Debería aparecer resaltada

### 2. Ir a la pestaña SQL
- Haz clic en la pestaña: **SQL**

### 3. Ejecutar el Script
- Copia y pega el contenido de: `config/01_estructura_minima.sql`
- Haz clic en: **Ejecutar** (o "Go")

### 4. Verificar Resultado
Deberías ver:
```
✅ Tablas creadas exitosamente
```

---

## 🔍 VERIFICAR TU USUARIO ACTUAL

Ejecuta este script en phpMyAdmin para ver qué usuario estás usando:

```sql
SELECT USER() as usuario_actual, DATABASE() as base_datos_actual;
```

**Resultado esperado:**
- Usuario: `root@localhost`
- Base de datos: `multigamer360`

**Si ves algo como:**
- Usuario: `u851317150_mg360_user@...`
- ❌ Estás usando credenciales incorrectas

---

## 🔐 VERIFICAR config/database.php

Asegúrate de que tu archivo `config/database.php` tenga:

```php
<?php
$host = 'localhost';
$dbname = 'multigamer360';
$username = 'root';
$password = ''; // Vacío para XAMPP local

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
```

---

## 🆘 SI NADA FUNCIONA

### Crear Base de Datos y Tablas desde MySQL Command Line

1. **Abre PowerShell como Administrador**

2. **Ve a la carpeta de MySQL:**
   ```powershell
   cd C:\xampp\mysql\bin
   ```

3. **Conecta a MySQL:**
   ```powershell
   .\mysql.exe -u root
   ```

4. **Crea la base de datos:**
   ```sql
   CREATE DATABASE IF NOT EXISTS multigamer360 
   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Selecciona la base de datos:**
   ```sql
   USE multigamer360;
   ```

6. **Ejecuta el script:**
   ```powershell
   source f:/xampp/htdocs/multigamer360/config/01_estructura_minima.sql
   ```

---

## ✅ DESPUÉS DE ARREGLAR

Una vez que phpMyAdmin funcione correctamente:

1. ✅ Verás las tablas creadas en el panel izquierdo
2. ✅ Podrás ver el usuario admin creado
3. ✅ Podrás probar el registro en: http://localhost/multigamer360/register.php

---

**¿Cuál opción prefieres probar primero?**
