@echo off
echo ========================================
echo FIX: Sincronizar estado de productos
echo Version: 4.3.1 - Estado consistente
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

REM Hacer add de los archivos actualizados
git add admin/product_edit.php
git add admin/debug_product_status.php
git add fix_product_status.sql

REM Hacer commit
git commit -m "FIX: Sincronizar campos status e is_active en product_edit.php - Ahora lee correctamente el estado actual"

REM Hacer push
git push origin main

echo.
echo ========================================
echo Archivos subidos exitosamente!
echo.
echo ARCHIVOS ACTUALIZADOS:
echo - admin/product_edit.php (v4.3.1)
echo - admin/debug_product_status.php (herramienta de diagnostico)
echo - fix_product_status.sql (script de sincronizacion)
echo.
echo CAMBIOS EN ESTA VERSION:
echo - Sincronizacion automatica de status e is_active al cargar
echo - Usa is_active como fallback si status esta vacio
echo - Mantiene ambos campos consistentes
echo.
echo PROXIMOS PASOS:
echo 1. Abrir: https://teal-fish-507993.hostingersite.com/admin/debug_product_status.php
echo 2. Ver el estado actual de los productos
echo 3. Si hay inconsistencias, ejecutar fix_product_status.sql en phpMyAdmin
echo 4. Abrir: https://teal-fish-507993.hostingersite.com/admin/product_edit.php?id=59
echo 5. Verificar que el estado se muestre correctamente
echo 6. Editar y guardar para verificar que se mantenga el estado
echo ========================================
pause
