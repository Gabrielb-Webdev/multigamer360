# ===============================================
# SCRIPT MAESTRO DE LIMPIEZA - MULTIGAMER360
# ===============================================
# Este script ejecuta todo el proceso de limpieza
# de forma guiada y segura
# ===============================================

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     🧹 LIMPIEZA COMPLETA DE MULTIGAMER360 🧹                   ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Establecer ubicación del proyecto
$projectPath = "f:\xampp\htdocs\multigamer360"
$mysqlPath = "f:\xampp\mysql\bin"

Set-Location $projectPath

# ===============================================
# PASO 1: VERIFICACIÓN INICIAL
# ===============================================
Write-Host "📋 PASO 1: VERIFICACIÓN INICIAL" -ForegroundColor Yellow
Write-Host "=================================================" -ForegroundColor Gray

# Verificar que MySQL esté corriendo
$mysqlRunning = Get-Process mysql -ErrorAction SilentlyContinue

if ($null -eq $mysqlRunning) {
    Write-Host "❌ ERROR: MySQL no está corriendo" -ForegroundColor Red
    Write-Host "Por favor, inicia XAMPP primero" -ForegroundColor Yellow
    Pause
    Exit
} else {
    Write-Host "✅ MySQL está corriendo" -ForegroundColor Green
}

# Verificar archivos críticos
$criticalFiles = @(
    "config\database.php",
    "config\cleanup\02_clean_all_data.sql",
    "config\cleanup\03_drop_and_recreate_database.sql"
)

foreach ($file in $criticalFiles) {
    if (Test-Path $file) {
        Write-Host "✅ Encontrado: $file" -ForegroundColor Green
    } else {
        Write-Host "❌ Falta: $file" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Presiona cualquier tecla para continuar..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
Write-Host ""

# ===============================================
# PASO 2: CREAR BACKUP
# ===============================================
Write-Host "💾 PASO 2: CREAR BACKUP DE LA BASE DE DATOS" -ForegroundColor Yellow
Write-Host "=================================================" -ForegroundColor Gray

$backupDate = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "backup_multigamer360_$backupDate.sql"

Write-Host "Archivo de backup: $backupFile" -ForegroundColor Cyan

$createBackup = Read-Host "¿Crear backup de la base de datos? (S/N) [RECOMENDADO]"

if ($createBackup -eq "S" -or $createBackup -eq "s") {
    Write-Host "Creando backup..." -ForegroundColor Yellow
    
    Set-Location $mysqlPath
    $backupPath = Join-Path $projectPath $backupFile
    
    & .\mysqldump.exe -u root multigamer360 > $backupPath
    
    if ($LASTEXITCODE -eq 0) {
        $backupSize = (Get-Item $backupPath).Length / 1KB
        Write-Host "✅ Backup creado exitosamente" -ForegroundColor Green
        Write-Host "   Ubicación: $backupPath" -ForegroundColor Gray
        Write-Host "   Tamaño: $([math]::Round($backupSize, 2)) KB" -ForegroundColor Gray
    } else {
        Write-Host "❌ Error al crear backup" -ForegroundColor Red
        Write-Host "¿Deseas continuar sin backup? (S/N)" -ForegroundColor Yellow
        $continuar = Read-Host
        if ($continuar -ne "S" -and $continuar -ne "s") {
            Exit
        }
    }
    
    Set-Location $projectPath
} else {
    Write-Host "⚠️  Omitiendo backup (NO RECOMENDADO)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Presiona cualquier tecla para continuar..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
Write-Host ""

# ===============================================
# PASO 3: ELEGIR TIPO DE LIMPIEZA
# ===============================================
Write-Host "🎯 PASO 3: ELEGIR TIPO DE LIMPIEZA" -ForegroundColor Yellow
Write-Host "=================================================" -ForegroundColor Gray
Write-Host ""
Write-Host "Opciones disponibles:" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1️⃣  LIMPIEZA SUAVE" -ForegroundColor Green
Write-Host "      - Elimina todos los datos" -ForegroundColor Gray
Write-Host "      - Mantiene la estructura de tablas" -ForegroundColor Gray
Write-Host "      - Conserva usuario admin" -ForegroundColor Gray
Write-Host "      - ✅ RECOMENDADO para uso normal" -ForegroundColor Green
Write-Host ""
Write-Host "  2️⃣  LIMPIEZA COMPLETA" -ForegroundColor Red
Write-Host "      - Elimina TODA la base de datos" -ForegroundColor Gray
Write-Host "      - Recrea desde cero" -ForegroundColor Gray
Write-Host "      - ⚠️  Solo para problemas graves" -ForegroundColor Yellow
Write-Host ""
Write-Host "  0️⃣  CANCELAR" -ForegroundColor Gray
Write-Host ""

$option = Read-Host "Selecciona una opción (1/2/0)"

switch ($option) {
    "1" {
        Write-Host ""
        Write-Host "⚠️  ADVERTENCIA: LIMPIEZA SUAVE" -ForegroundColor Yellow
        Write-Host "Esto eliminará todos los datos pero mantendrá la estructura" -ForegroundColor Yellow
        $confirm = Read-Host "¿Estás seguro? Escribe 'CONFIRMAR' para continuar"
        
        if ($confirm -eq "CONFIRMAR") {
            Write-Host "Ejecutando limpieza suave..." -ForegroundColor Yellow
            
            Set-Location $mysqlPath
            $sqlScript = Join-Path $projectPath "config\cleanup\02_clean_all_data.sql"
            & .\mysql.exe -u root multigamer360 < $sqlScript
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "✅ Base de datos limpiada exitosamente" -ForegroundColor Green
            } else {
                Write-Host "❌ Error al limpiar la base de datos" -ForegroundColor Red
            }
            
            Set-Location $projectPath
        } else {
            Write-Host "❌ Operación cancelada" -ForegroundColor Red
            Exit
        }
    }
    "2" {
        Write-Host ""
        Write-Host "⚠️⚠️⚠️  ADVERTENCIA: LIMPIEZA COMPLETA ⚠️⚠️⚠️" -ForegroundColor Red
        Write-Host "Esto eliminará TODA la base de datos y la recreará" -ForegroundColor Red
        $confirm = Read-Host "¿Estás ABSOLUTAMENTE seguro? Escribe 'ELIMINAR TODO' para continuar"
        
        if ($confirm -eq "ELIMINAR TODO") {
            Write-Host "Eliminando y recreando base de datos..." -ForegroundColor Yellow
            
            Set-Location $mysqlPath
            
            # Ejecutar drop y recreate
            $sqlScript1 = Join-Path $projectPath "config\cleanup\03_drop_and_recreate_database.sql"
            & .\mysql.exe -u root < $sqlScript1
            
            # Ejecutar estructura
            $sqlScript2 = Join-Path $projectPath "config\database_structure.sql"
            & .\mysql.exe -u root multigamer360 < $sqlScript2
            
            # Crear admin
            $sqlScript3 = Join-Path $projectPath "config\cleanup\05_create_admin_user.sql"
            & .\mysql.exe -u root multigamer360 < $sqlScript3
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "✅ Base de datos recreada exitosamente" -ForegroundColor Green
            } else {
                Write-Host "❌ Error al recrear la base de datos" -ForegroundColor Red
            }
            
            Set-Location $projectPath
        } else {
            Write-Host "❌ Operación cancelada" -ForegroundColor Red
            Exit
        }
    }
    default {
        Write-Host "❌ Operación cancelada" -ForegroundColor Red
        Exit
    }
}

Write-Host ""
Write-Host "Presiona cualquier tecla para continuar..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
Write-Host ""

# ===============================================
# PASO 4: LIMPIAR ARCHIVOS
# ===============================================
Write-Host "📁 PASO 4: LIMPIEZA DE ARCHIVOS" -ForegroundColor Yellow
Write-Host "=================================================" -ForegroundColor Gray

$cleanFiles = Read-Host "¿Ejecutar limpieza de archivos temporales? (S/N)"

if ($cleanFiles -eq "S" -or $cleanFiles -eq "s") {
    & .\config\cleanup\cleanup_files.ps1
} else {
    Write-Host "⏭️  Omitiendo limpieza de archivos" -ForegroundColor Yellow
}

Write-Host ""

# ===============================================
# PASO 5: VERIFICACIÓN FINAL
# ===============================================
Write-Host "✔️  PASO 5: VERIFICACIÓN FINAL" -ForegroundColor Yellow
Write-Host "=================================================" -ForegroundColor Gray

Write-Host "Ejecutando verificación de la base de datos..." -ForegroundColor Yellow

Set-Location $mysqlPath
$sqlScript = Join-Path $projectPath "config\cleanup\04_verify_database.sql"
& .\mysql.exe -u root multigamer360 < $sqlScript

Set-Location $projectPath

Write-Host ""

# ===============================================
# RESUMEN FINAL
# ===============================================
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║              ✅ LIMPIEZA COMPLETADA ✅                         ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "📋 RESUMEN:" -ForegroundColor Cyan
Write-Host "  ✅ Base de datos limpiada" -ForegroundColor Green
Write-Host "  ✅ Archivos temporales eliminados" -ForegroundColor Green
if ($createBackup -eq "S" -or $createBackup -eq "s") {
    Write-Host "  ✅ Backup guardado: $backupFile" -ForegroundColor Green
}
Write-Host ""

Write-Host "🔐 CREDENCIALES ADMIN:" -ForegroundColor Cyan
Write-Host "  Email: admin@multigamer360.com" -ForegroundColor White
Write-Host "  Password: password" -ForegroundColor White
Write-Host "  ⚠️  CAMBIAR INMEDIATAMENTE DESPUÉS DE LOGIN" -ForegroundColor Yellow
Write-Host ""

Write-Host "📝 PRÓXIMOS PASOS:" -ForegroundColor Cyan
Write-Host "  1. Iniciar sesión en el panel de admin" -ForegroundColor White
Write-Host "  2. Cambiar la contraseña del admin" -ForegroundColor White
Write-Host "  3. Crear categorías y productos" -ForegroundColor White
Write-Host "  4. Configurar métodos de pago y envío" -ForegroundColor White
Write-Host ""

Write-Host "🌐 ACCEDER A LA APLICACIÓN:" -ForegroundColor Cyan
Write-Host "  Frontend: http://localhost/multigamer360/" -ForegroundColor White
Write-Host "  Admin: http://localhost/multigamer360/admin/" -ForegroundColor White
Write-Host ""

Pause
