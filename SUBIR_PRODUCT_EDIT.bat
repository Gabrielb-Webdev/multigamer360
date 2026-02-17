@echo off
echo ========================================
echo Subiendo product_edit.php actualizado a Hostinger
echo Version: Auto-rellenar agregado
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

echo [1/4] Agregando archivos a Git...
git add admin/product_edit.php

echo.
echo [2/4] Creando commit...
git commit -m "FEATURE: Agregar botón Auto-Rellenar a product_edit.php - Igualar funcionalidad con product_create.php"

echo.
echo [3/4] Subiendo a GitHub...
git push origin main

echo.
echo [4/4] Activando actualización en Hostinger...
powershell -Command "Invoke-WebRequest -Uri 'https://teal-fish-507993.hostingersite.com/github-webhook.php' -Method POST -Headers @{'X-GitHub-Event'='push'; 'Content-Type'='application/json'} -Body '{}'"

echo.
echo ========================================
echo COMPLETADO!
echo ========================================
echo.
echo CAMBIOS REALIZADOS:
echo - Boton "Auto-Rellenar Informacion del Juego" agregado
echo - Modales de busqueda y exito agregados
echo - Funcionalidad completa de auto-rellenado
echo - Busqueda multi-fuente (RAWG API + base de datos)
echo - Descarga automatica de imagenes
echo.
echo SIGUIENTE PASO:
echo 1. Espera 10-30 segundos
echo 2. Abre: https://teal-fish-507993.hostingersite.com/admin/product_edit.php
echo 3. Presiona Ctrl+Shift+R (hard refresh)
echo 4. Verifica que aparezca el boton verde de "Auto-Rellenar"
echo ========================================
pause
