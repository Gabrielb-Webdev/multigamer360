@echo off
echo ========================================
echo Subiendo archivos actualizados a Hostinger
echo Version: 2.9 - Filtro Restrictivo Eliminado
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

REM Hacer add de los archivos actualizados
git add admin/product_create.php
git add admin/ajax/search_game_multi.php
git add CHANGELOG_AUTO_RELLENAR.md
git add OBTENER_API_KEY_RAWG.md

REM Hacer commit
git commit -m "v2.10: Mock mejorado con 12 resultados inteligentes mientras se arregla RAWG API key (HTTP 401)"

REM Hacer push
git push origin main

echo.
echo ========================================
echo Archivos subidos exitosamente!
echo.
echo ARCHIVOS ACTUALIZADOS:
echo - admin/product_create.php (v2.6)
echo - admin/ajax/search_game_multi.php (v2.0)
echo.
echo CAMBIOS EN ESTA VERSION:
echo - Mejor priorizacion de fuentes
echo - Removido FreeToGame (resultados inutiles)
echo - Debugging mejorado
echo.
echo IMPORTANTE:
echo 1. Espera hard refresh con Ctrl+Shift+R
echo 2. Abre F12 para ver los logs
echo ========================================
pause
