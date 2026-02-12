# ===============================================
# CREAR ESTRUCTURA DE BD DESDE POWERSHELL
# ===============================================
# Este script crea las tablas directamente sin usar phpMyAdmin
# ===============================================

Write-Host "🔧 CREANDO ESTRUCTURA DE BASE DE DATOS" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Gray
Write-Host ""

# Rutas
$mysqlPath = "C:\xampp\mysql\bin"
$sqlFile = "f:\xampp\htdocs\multigamer360\config\01_estructura_minima.sql"

# Verificar que MySQL esté corriendo
Write-Host "1️⃣ Verificando MySQL..." -ForegroundColor Yellow
$mysqlProcess = Get-Process mysqld -ErrorAction SilentlyContinue

if ($null -eq $mysqlProcess) {
    Write-Host "❌ ERROR: MySQL no está corriendo" -ForegroundColor Red
    Write-Host "Por favor, inicia XAMPP y arranca MySQL" -ForegroundColor Yellow
    Pause
    Exit
}

Write-Host "✅ MySQL está corriendo" -ForegroundColor Green
Write-Host ""

# Verificar que el archivo SQL exista
Write-Host "2️⃣ Verificando archivo SQL..." -ForegroundColor Yellow
if (-not (Test-Path $sqlFile)) {
    Write-Host "❌ ERROR: No se encuentra el archivo:" -ForegroundColor Red
    Write-Host "   $sqlFile" -ForegroundColor Gray
    Pause
    Exit
}

Write-Host "✅ Archivo encontrado" -ForegroundColor Green
Write-Host ""

# Crear base de datos si no existe
Write-Host "3️⃣ Creando base de datos 'multigamer360'..." -ForegroundColor Yellow

Set-Location $mysqlPath

$createDB = @"
CREATE DATABASE IF NOT EXISTS multigamer360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"@

$createDB | & .\mysql.exe -u root --default-character-set=utf8mb4

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Base de datos lista" -ForegroundColor Green
} else {
    Write-Host "❌ Error al crear la base de datos" -ForegroundColor Red
    Pause
    Exit
}

Write-Host ""

# Ejecutar el script SQL
Write-Host "4️⃣ Creando tablas..." -ForegroundColor Yellow

Get-Content $sqlFile | & .\mysql.exe -u root --default-character-set=utf8mb4 multigamer360

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Tablas creadas exitosamente" -ForegroundColor Green
} else {
    Write-Host "❌ Error al crear las tablas" -ForegroundColor Red
    Write-Host "Verifica que no haya errores de sintaxis en el archivo SQL" -ForegroundColor Yellow
    Pause
    Exit
}

Write-Host ""

# Verificar tablas creadas
Write-Host "5️⃣ Verificando tablas creadas..." -ForegroundColor Yellow

$verifySQL = @"
USE multigamer360;
SHOW TABLES;
"@

Write-Host ""
Write-Host "Tablas en la base de datos:" -ForegroundColor Cyan
$verifySQL | & .\mysql.exe -u root --default-character-set=utf8mb4

Write-Host ""

# Verificar usuario admin
Write-Host "6️⃣ Verificando usuario admin..." -ForegroundColor Yellow

$checkAdmin = @"
USE multigamer360;
SELECT id, email, first_name, last_name, role FROM users WHERE role = 'admin';
"@

$checkAdmin | & .\mysql.exe -u root --default-character-set=utf8mb4

Write-Host ""

# Resumen
Write-Host "=========================================" -ForegroundColor Green
Write-Host "✅ ESTRUCTURA CREADA EXITOSAMENTE" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""

Write-Host "🔐 CREDENCIALES ADMIN:" -ForegroundColor Cyan
Write-Host "   Email: admin@multigamer360.com" -ForegroundColor White
Write-Host "   Password: password" -ForegroundColor White
Write-Host ""

Write-Host "🌐 PRÓXIMO PASO:" -ForegroundColor Cyan
Write-Host "   Prueba el registro en:" -ForegroundColor White
Write-Host "   http://localhost/multigamer360/register.php" -ForegroundColor Yellow
Write-Host ""

Pause
