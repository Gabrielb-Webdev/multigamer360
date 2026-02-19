@echo off
chcp 65001 > nul
echo ========================================
echo 🚀 SUBIENDO MENÚ SIMPLIFICADO
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add admin/inc/sidebar.php

echo.
echo 💾 Creando commit...
git commit -m "MENU SIMPLIFICADO: Eliminadas opciones Categorías, Marcas, Reseñas y Configuración"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ COMPLETADO - MENÚ SIMPLIFICADO DESPLEGADO
echo ========================================
echo.
echo Los cambios se aplicarán automáticamente en Hostinger
echo.
pause
