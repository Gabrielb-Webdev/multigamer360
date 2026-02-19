@echo off
echo ========================================
echo Subiendo estilos actualizados a Hostinger
echo Version: 3.0 - Estilos con !important para product_edit.php
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

REM Hacer add de los archivos actualizados
git add admin/assets/css/admin.css
git add admin/inc/header.php
git add admin/product_edit.php

REM Hacer commit
git commit -m "v3.0: Estilos mejorados para product_edit.php con máxima prioridad (!important)"

REM Hacer push
git push origin main

echo.
echo ========================================
echo Archivos subidos exitosamente!
echo.
echo ARCHIVOS ACTUALIZADOS:
echo - admin/assets/css/admin.css (v3.0)
echo - admin/inc/header.php (cache busting actualizado)
echo - admin/product_edit.php (estilos inline removidos)
echo.
echo CAMBIOS EN ESTA VERSION:
echo - Estilos movidos a admin.css con !important
echo - 250+ lineas de CSS optimizado
echo - Cards, botones, inputs mejorados
echo - Animaciones y transiciones suaves
echo - Indicadores de estado dinamicos
echo - Version del CSS actualizada a v3.0
echo.
echo IMPORTANTE:
echo 1. Abre: https://teal-fish-507993.hostingersite.com/admin/product_edit.php
echo 2. Hard refresh con Ctrl+Shift+F5 o Ctrl+Shift+R
echo 3. Limpia cache del navegador si es necesario
echo 4. Verifica que los estilos se apliquen correctamente
echo ========================================
pause
SUBIR_CSS_ESTILOS_PRODUCT_EDIT.bat