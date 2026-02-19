@echo off
chcp 65001 > nul
echo ========================================
echo 🎨 SUBIENDO FIX HEADERS TABLA PRODUCTOS
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add admin/assets/css/admin.css
git add admin/inc/header.php

echo.
echo 💾 Creando commit...
git commit -m "HOTFIX: Color blanco visible en headers de tabla productos (v4.2)"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ FIX DESPLEGADO - HEADERS VISIBLES
echo ========================================
echo.
echo Presiona Ctrl+Shift+F5 en el navegador
echo.
pause
