@echo off
chcp 65001 > nul
echo ========================================
echo 🗑️ ELIMINANDO PÁGINAS INNECESARIAS ADMIN
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add admin/brands.php
git add admin/categories.php
git add admin/reviews.php
git add admin/settings.php
git add admin/inc/header.php
git add admin/inc/security_config.php

echo.
echo 💾 Creando commit...
git commit -m "CLEANUP: Eliminar páginas innecesarias del admin

🗑️ ARCHIVOS ELIMINADOS:
- admin/brands.php
- admin/categories.php
- admin/reviews.php
- admin/settings.php

✅ ARCHIVOS ACTUALIZADOS:
- admin/inc/header.php (eliminados enlaces del menú)
- admin/inc/security_config.php (eliminadas referencias)

💡 RAZÓN:
Simplificación del panel admin - páginas no utilizadas"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ LIMPIEZA COMPLETADA
echo ========================================
echo.
echo Páginas eliminadas del admin:
echo - Marcas (brands.php)
echo - Categorías (categories.php)
echo - Reseñas (reviews.php)
echo - Configuración (settings.php)
echo.
echo El menú lateral se actualizó automáticamente.
echo.
pause
