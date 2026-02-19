@echo off
echo ========================================
echo FIX: Permisos y Filtros de Stock
echo Version: 1.0 - Filtros simplificados
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

REM Hacer add de los archivos actualizados
git add admin/products.php
git add admin/api/bulk_update_status.php
git add admin/verificar_productos_incompletos.php
git add verificar_productos_incompletos.sql

REM Hacer commit
git commit -m "FIX: Mejorar permisos bulk update y simplificar filtros de stock - Solo Stock Disponible y Agotado"

REM Hacer push
git push origin main

echo.
echo ========================================
echo Archivos subidos exitosamente!
echo.
echo ARCHIVOS ACTUALIZADOS:
echo - admin/products.php (filtros simplificados)
echo - admin/api/bulk_update_status.php (mejor manejo de permisos)
echo - admin/verificar_productos_incompletos.php (nueva herramienta)
echo - verificar_productos_incompletos.sql (queries de verificacion)
echo.
echo CAMBIOS EN ESTA VERSION:
echo - FIX: Error 403 al cambiar estado masivo (mejorada validacion de permisos)
echo - CAMBIO: Filtro de stock ahora solo muestra:
echo   * Stock Disponible (productos con stock ^> 0)
echo   * Agotado (productos con stock = 0)
echo - ELIMINADO: Filtro "Stock Bajo" y "Stock Normal"
echo - MEJORA: Estadisticas actualizadas a nuevo esquema
echo - MEJORA: Tarjeta verde para Stock Disponible
echo - NUEVO: Herramienta para verificar productos con info faltante
echo.
echo PROXIMOS PASOS:
echo 1. Abrir: https://teal-fish-507993.hostingersite.com/admin/products.php
echo 2. Hard refresh (Ctrl+Shift+F5)
echo 3. Verificar que los filtros muestren solo:
echo    - Todos / Stock Disponible / Agotado
echo 4. Probar cambio masivo de estado (debe funcionar sin error 403)
echo 5. Ver productos incompletos:
echo    https://teal-fish-507993.hostingersite.com/admin/verificar_productos_incompletos.php
echo ========================================
pause
