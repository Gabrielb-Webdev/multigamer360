# 🧹 GUÍA DE LIMPIEZA COMPLETA - MULTIGAMER360

## ⚠️ ADVERTENCIA IMPORTANTE
**Estos scripts eliminarán datos de tu base de datos. Asegúrate de hacer un backup antes de proceder.**

---

## 📋 PASOS PARA LIMPIAR LA BASE DE DATOS

### 1️⃣ HACER BACKUP (OBLIGATORIO)

#### Opción A: Desde línea de comandos (PowerShell)
```powershell
cd f:\xampp\mysql\bin
.\mysqldump.exe -u root -p multigamer360 > f:\xampp\htdocs\multigamer360\backup_$(Get-Date -Format 'yyyyMMdd').sql
```

#### Opción B: Desde phpMyAdmin
1. Abre http://localhost/phpmyadmin
2. Selecciona la base de datos `multigamer360`
3. Ve a la pestaña "Exportar"
4. Haz clic en "Ejecutar"
5. Guarda el archivo SQL

---

### 2️⃣ ELEGIR NIVEL DE LIMPIEZA

#### 🟢 Opción 1: LIMPIEZA SUAVE (Solo datos, mantiene estructura)
**Ejecutar:** `02_clean_all_data.sql`

**Qué hace:**
- ✅ Elimina todos los productos
- ✅ Elimina todas las órdenes
- ✅ Elimina todos los reviews
- ✅ Limpia carritos y wishlists
- ✅ Mantiene la estructura de tablas
- ✅ Conserva usuario admin (ID 1)
- ✅ Resetea AUTO_INCREMENT

**Cuándo usar:**
- Quieres empezar con datos limpios
- Mantener la estructura actual
- Conservar configuraciones

---

#### 🟡 Opción 2: LIMPIEZA COMPLETA (Elimina y recrea BD)
**Ejecutar en orden:**
1. `03_drop_and_recreate_database.sql` (Elimina BD completa)
2. `config/database_structure.sql` (Recrea estructura)
3. `05_create_admin_user.sql` (Crea admin)

**Qué hace:**
- ✅ Elimina toda la base de datos
- ✅ Recrea desde cero
- ✅ Estructura limpia
- ✅ Sin datos residuales

**Cuándo usar:**
- Problemas graves de estructura
- Quieres empezar desde cero
- Tablas corruptas o inconsistentes

---

### 3️⃣ EJECUTAR SCRIPTS

#### Desde phpMyAdmin:
1. Abre http://localhost/phpmyadmin
2. Selecciona la base de datos `multigamer360`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido del script elegido
5. Haz clic en "Ejecutar"

#### Desde MySQL Command Line:
```powershell
cd f:\xampp\mysql\bin
.\mysql.exe -u root -p multigamer360 < f:\xampp\htdocs\multigamer360\config\cleanup\02_clean_all_data.sql
```

---

### 4️⃣ VERIFICAR LIMPIEZA

**Ejecutar:** `04_verify_database.sql`

Este script te mostrará:
- 📊 Todas las tablas existentes
- 📈 Tamaño de cada tabla
- 🔢 Cantidad de registros
- 🔗 Relaciones entre tablas

---

## 🗂️ ARCHIVOS DE LIMPIEZA

```
config/cleanup/
├── 01_backup_database.sql          # Documentación de backup
├── 02_clean_all_data.sql          # LIMPIEZA SUAVE ✅ RECOMENDADO
├── 03_drop_and_recreate_database.sql  # LIMPIEZA COMPLETA ⚠️
├── 04_verify_database.sql         # Verificación
├── 05_create_admin_user.sql       # Crear admin nuevo
└── README.md                      # Esta guía
```

---

## 🔐 CREDENCIALES ADMIN POR DEFECTO

Después de ejecutar `05_create_admin_user.sql`:

- **Email:** admin@multigamer360.com
- **Password:** password

**⚠️ CAMBIAR INMEDIATAMENTE DESPUÉS DE INICIAR SESIÓN**

---

## 🧼 LIMPIEZA DE ARCHIVOS DEL PROYECTO

### Archivos que se pueden eliminar:

```powershell
# Desde PowerShell en la raíz del proyecto:

# Eliminar archivos temporales
Remove-Item -Path ".\logs\*" -Force -Recurse -ErrorAction SilentlyContinue

# Eliminar uploads de prueba (CUIDADO: verificar antes)
# Remove-Item -Path ".\uploads\products\*" -Force -Recurse -ErrorAction SilentlyContinue

# Eliminar archivos SQL redundantes
Remove-Item -Path ".\*.sql" -Force -ErrorAction SilentlyContinue

# Eliminar archivos de debug/test
Get-ChildItem -Path "." -Filter "test_*.php" -Recurse | Remove-Item -Force
Get-ChildItem -Path "." -Filter "debug_*.php" -Recurse | Remove-Item -Force
```

---

## ✅ CHECKLIST POST-LIMPIEZA

- [ ] Backup creado y verificado
- [ ] Base de datos limpiada
- [ ] Verificación ejecutada (04_verify_database.sql)
- [ ] Usuario admin creado y funcional
- [ ] Archivos temporales eliminados
- [ ] Logs limpiados
- [ ] Sesión de prueba exitosa

---

## 🆘 SI ALGO SALE MAL

### Restaurar desde backup:
```powershell
cd f:\xampp\mysql\bin
.\mysql.exe -u root -p multigamer360 < f:\xampp\htdocs\multigamer360\backup_YYYYMMDD.sql
```

### Verificar tablas:
```sql
USE multigamer360;
SHOW TABLES;
```

### Reparar tablas corruptas:
```sql
REPAIR TABLE nombre_tabla;
```

---

## 📞 SOPORTE

Si encuentras problemas:
1. Verifica el archivo de logs: `logs/error.log`
2. Revisa la consola de phpMyAdmin
3. Verifica que XAMPP esté corriendo correctamente

---

**Última actualización:** 2025-10-10
**Versión:** 1.0.0
