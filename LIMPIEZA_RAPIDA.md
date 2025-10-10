# 🚀 INICIO RÁPIDO - LIMPIEZA DE MULTIGAMER360

## ⚡ OPCIÓN FÁCIL (RECOMENDADA)

### Ejecutar el Script Maestro

1. Abre **PowerShell como Administrador**
2. Navega a la carpeta del proyecto:
   ```powershell
   cd f:\xampp\htdocs\multigamer360
   ```
3. Ejecuta el script maestro:
   ```powershell
   .\config\cleanup\cleanup_master.ps1
   ```
4. Sigue las instrucciones en pantalla

---

## 📋 OPCIÓN MANUAL

### 1. Crear Backup (OBLIGATORIO)

#### Desde phpMyAdmin:
1. Abre http://localhost/phpmyadmin
2. Selecciona `multigamer360`
3. Clic en "Exportar" → "Ejecutar"
4. Guarda el archivo

#### Desde PowerShell:
```powershell
cd f:\xampp\mysql\bin
.\mysqldump.exe -u root multigamer360 > f:\xampp\htdocs\multigamer360\backup.sql
```

---

### 2. Limpiar Base de Datos

#### Opción A: Limpieza Suave (Solo datos)
**Ejecutar en phpMyAdmin:**
```
config/cleanup/02_clean_all_data.sql
```

#### Opción B: Limpieza Completa (Todo)
**Ejecutar en orden:**
1. `config/cleanup/03_drop_and_recreate_database.sql`
2. `config/database_structure.sql`
3. `config/cleanup/05_create_admin_user.sql`

---

### 3. Limpiar Archivos

```powershell
cd f:\xampp\htdocs\multigamer360
.\config\cleanup\cleanup_files.ps1
```

---

## 🔐 CREDENCIALES POST-LIMPIEZA

**Admin:**
- Email: `admin@multigamer360.com`
- Password: `password`

⚠️ **CAMBIAR INMEDIATAMENTE**

---

## ✅ VERIFICAR

```sql
-- Ejecutar en phpMyAdmin:
SELECT 'products' as tabla, COUNT(*) as registros FROM products
UNION ALL
SELECT 'users', COUNT(*) FROM users;
```

---

## 🆘 RESTAURAR BACKUP

```powershell
cd f:\xampp\mysql\bin
.\mysql.exe -u root multigamer360 < f:\xampp\htdocs\multigamer360\backup.sql
```

---

## 📞 PROBLEMAS COMUNES

### "MySQL no está corriendo"
→ Inicia XAMPP Control Panel y arranca MySQL

### "Access denied"
→ Verifica el usuario/contraseña en `config/database.php`

### "Table doesn't exist"
→ Ejecuta `config/database_structure.sql`

---

**Creado:** 2025-10-10  
**Para dudas:** Consulta `config/cleanup/README.md`
