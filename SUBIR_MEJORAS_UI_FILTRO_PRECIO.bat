@echo off
chcp 65001 > nul
echo ========================================
echo 🎨 SUBIENDO MEJORAS UX FILTRO PRECIOS
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add productos.php

echo.
echo 💾 Creando commit...
git commit -m "UX MEJORA: Filtro de precios moderno y sutil con tema oscuro glassmorphism"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ MEJORAS DESPLEGADAS
echo ========================================
echo.
echo Presiona Ctrl+Shift+F5 en el navegador
echo.
pause
