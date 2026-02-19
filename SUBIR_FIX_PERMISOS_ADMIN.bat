@echo off
echo ========================================
echo FIX CRITICO: Agregar permisos a administradores
echo Version: 1.0 - Permisos automaticos
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

REM Hacer add de los archivos actualizados
git add admin/inc/auth.php

REM Hacer commit
git commit -m "FIX CRITICO: Agregar permisos automaticos a administradores - Soluciona error 403"

REM Hacer push
git push origin main

echo.
echo ========================================
echo Archivos subidos exitosamente!
echo.
echo ARCHIVOS ACTUALIZADOS:
echo - admin/inc/auth.php (permisos automaticos)
echo.
echo CAMBIOS EN ESTA VERSION:
echo - FIX: Administradores ahora tienen permisos automaticos al iniciar sesion
echo - AGREGADO: Permisos de productos (view, create, edit, update, delete)
echo - AGREGADO: Permisos de categorias, marcas, ordenes, usuarios
echo - MEJORA: Funcion hasPermission verifica permisos especificos
echo - SOLUCION: Error 403 "No tienes permisos para editar productos"
echo.
echo MUY IMPORTANTE - DEBES HACER ESTO:
echo ========================================
echo 1. Cerrar sesion en: https://teal-fish-507993.hostingersite.com/admin/logout.php
echo 2. Volver a iniciar sesion
echo 3. Los permisos se cargaran automaticamente
echo 4. Verificar en: https://teal-fish-507993.hostingersite.com/admin/verificar_sesion.php
echo 5. Ahora deberia ver "Tiene permisos de productos: SI"
echo.
echo NOTA: Los permisos se cargan al iniciar sesion, por eso debes
echo       cerrar sesion y volver a entrar para que se apliquen.
echo ========================================
pause
