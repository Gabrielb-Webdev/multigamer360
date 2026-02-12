# ===============================================
# SCRIPT DE LIMPIEZA DE ARCHIVOS - MULTIGAMER360
# ===============================================
# Este script limpia archivos temporales y de prueba
# del proyecto MultiGamer360
# ===============================================

Write-Host "🧹 INICIANDO LIMPIEZA DE ARCHIVOS - MULTIGAMER360" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan
Write-Host ""

# Establecer ubicación del proyecto
$projectPath = "f:\xampp\htdocs\multigamer360"
Set-Location $projectPath

Write-Host "📂 Directorio del proyecto: $projectPath" -ForegroundColor Yellow
Write-Host ""

# ===============================================
# FUNCIÓN PARA ELIMINAR ARCHIVOS DE FORMA SEGURA
# ===============================================
function Remove-SafeFiles {
    param(
        [string]$Path,
        [string]$Filter,
        [string]$Description
    )
    
    Write-Host "🔍 Buscando: $Description..." -ForegroundColor Yellow
    $files = Get-ChildItem -Path $Path -Filter $Filter -Recurse -ErrorAction SilentlyContinue
    
    if ($files.Count -gt 0) {
        Write-Host "   Encontrados: $($files.Count) archivo(s)" -ForegroundColor Gray
        
        # Mostrar archivos encontrados
        foreach ($file in $files) {
            Write-Host "   - $($file.FullName)" -ForegroundColor DarkGray
        }
        
        # Preguntar confirmación
        $confirm = Read-Host "   ¿Eliminar estos archivos? (S/N)"
        
        if ($confirm -eq "S" -or $confirm -eq "s") {
            foreach ($file in $files) {
                Remove-Item $file.FullName -Force
                Write-Host "   ✅ Eliminado: $($file.Name)" -ForegroundColor Green
            }
        } else {
            Write-Host "   ⏭️  Omitidos" -ForegroundColor Yellow
        }
    } else {
        Write-Host "   ✅ No se encontraron archivos" -ForegroundColor Green
    }
    Write-Host ""
}

# ===============================================
# LIMPIAR LOGS
# ===============================================
Write-Host "📋 LIMPIANDO LOGS" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

if (Test-Path ".\logs") {
    $logFiles = Get-ChildItem -Path ".\logs\*.log" -ErrorAction SilentlyContinue
    
    if ($logFiles.Count -gt 0) {
        Write-Host "Encontrados $($logFiles.Count) archivo(s) de log" -ForegroundColor Yellow
        $confirm = Read-Host "¿Eliminar archivos de log? (S/N)"
        
        if ($confirm -eq "S" -or $confirm -eq "s") {
            Remove-Item -Path ".\logs\*.log" -Force
            Write-Host "✅ Logs eliminados" -ForegroundColor Green
        }
    } else {
        Write-Host "✅ No hay logs para eliminar" -ForegroundColor Green
    }
} else {
    Write-Host "⚠️  Carpeta logs no encontrada" -ForegroundColor Yellow
}
Write-Host ""

# ===============================================
# LIMPIAR ARCHIVOS SQL DE LA RAÍZ
# ===============================================
Write-Host "🗄️  LIMPIANDO ARCHIVOS SQL DE LA RAÍZ" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

$sqlFiles = Get-ChildItem -Path ".\*.sql" -ErrorAction SilentlyContinue

if ($sqlFiles.Count -gt 0) {
    Write-Host "Encontrados $($sqlFiles.Count) archivo(s) SQL en la raíz:" -ForegroundColor Yellow
    foreach ($file in $sqlFiles) {
        Write-Host "  - $($file.Name)" -ForegroundColor Gray
    }
    
    $confirm = Read-Host "¿Eliminar estos archivos SQL? (S/N)"
    
    if ($confirm -eq "S" -or $confirm -eq "s") {
        Remove-Item -Path ".\*.sql" -Force
        Write-Host "✅ Archivos SQL eliminados" -ForegroundColor Green
    }
} else {
    Write-Host "✅ No hay archivos SQL en la raíz" -ForegroundColor Green
}
Write-Host ""

# ===============================================
# LIMPIAR ARCHIVOS DE PRUEBA
# ===============================================
Write-Host "🧪 LIMPIANDO ARCHIVOS DE PRUEBA" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

Remove-SafeFiles -Path "." -Filter "test_*.php" -Description "Archivos test_*.php"
Remove-SafeFiles -Path "." -Filter "debug_*.php" -Description "Archivos debug_*.php"
Remove-SafeFiles -Path "." -Filter "temp_*.php" -Description "Archivos temp_*.php"
Remove-SafeFiles -Path "." -Filter "ejemplo_*.php" -Description "Archivos ejemplo_*.php"

# ===============================================
# LIMPIAR ARCHIVOS BACKUP
# ===============================================
Write-Host "💾 LIMPIANDO ARCHIVOS BACKUP ANTIGUOS" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

Remove-SafeFiles -Path "." -Filter "backup_*.sql" -Description "Backups SQL antiguos"
Remove-SafeFiles -Path "." -Filter "*.bak" -Description "Archivos .bak"
Remove-SafeFiles -Path "." -Filter "*.old" -Description "Archivos .old"

# ===============================================
# LIMPIAR ARCHIVOS DE CONFIGURACIÓN DUPLICADOS
# ===============================================
Write-Host "⚙️  VERIFICANDO ARCHIVOS DE CONFIGURACIÓN" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

$configFiles = @(
    ".\config\database.example.php",
    ".\config\config.example.php",
    ".\config\*.backup.php"
)

foreach ($pattern in $configFiles) {
    $files = Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue
    if ($files.Count -gt 0) {
        Write-Host "Encontrado: $($files.Name)" -ForegroundColor Yellow
    }
}

Write-Host "✅ Verificación completa" -ForegroundColor Green
Write-Host ""

# ===============================================
# LIMPIAR ARCHIVOS TEMPORALES DE EDITOR
# ===============================================
Write-Host "📝 LIMPIANDO ARCHIVOS TEMPORALES DE EDITOR" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

Remove-SafeFiles -Path "." -Filter "*.tmp" -Description "Archivos temporales"
Remove-SafeFiles -Path "." -Filter "*~" -Description "Archivos de backup del editor"
Remove-SafeFiles -Path "." -Filter ".DS_Store" -Description "Archivos .DS_Store (Mac)"

# ===============================================
# VERIFICAR UPLOADS
# ===============================================
Write-Host "🖼️  VERIFICANDO CARPETA UPLOADS" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

if (Test-Path ".\uploads") {
    $uploadSize = (Get-ChildItem -Path ".\uploads" -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
    $uploadFiles = (Get-ChildItem -Path ".\uploads" -Recurse -File).Count
    
    Write-Host "Tamaño total: $([math]::Round($uploadSize, 2)) MB" -ForegroundColor Yellow
    Write-Host "Archivos totales: $uploadFiles" -ForegroundColor Yellow
    
    $confirm = Read-Host "¿Deseas limpiar la carpeta uploads? (S/N) [CUIDADO: Eliminará imágenes]"
    
    if ($confirm -eq "S" -or $confirm -eq "s") {
        Remove-Item -Path ".\uploads\*" -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "✅ Uploads limpiados" -ForegroundColor Green
        
        # Recrear estructura
        New-Item -Path ".\uploads\products" -ItemType Directory -Force | Out-Null
        New-Item -Path ".\uploads\categories" -ItemType Directory -Force | Out-Null
        New-Item -Path ".\uploads\brands" -ItemType Directory -Force | Out-Null
        Write-Host "✅ Estructura de uploads recreada" -ForegroundColor Green
    }
} else {
    Write-Host "⚠️  Carpeta uploads no encontrada" -ForegroundColor Yellow
}
Write-Host ""

# ===============================================
# RESUMEN
# ===============================================
Write-Host "📊 RESUMEN DE LIMPIEZA" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

# Calcular tamaño del proyecto
$projectSize = (Get-ChildItem -Path "." -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB

Write-Host "Tamaño total del proyecto: $([math]::Round($projectSize, 2)) MB" -ForegroundColor Yellow
Write-Host ""

Write-Host "✅ LIMPIEZA COMPLETADA" -ForegroundColor Green
Write-Host ""
Write-Host "📋 PRÓXIMOS PASOS:" -ForegroundColor Cyan
Write-Host "1. Ejecutar scripts SQL de limpieza (ver README.md)" -ForegroundColor White
Write-Host "2. Verificar que la aplicación funcione correctamente" -ForegroundColor White
Write-Host "3. Crear nuevos datos de prueba si es necesario" -ForegroundColor White
Write-Host ""

Pause
