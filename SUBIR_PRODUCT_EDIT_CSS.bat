@echo off
echo ========================================
echo Subiendo CSS dedicado para product_edit.php
echo Version: 1.0 - Archivo CSS independiente
echo ========================================
echo.

REM Cambiar al directorio del proyecto
cd /d "%~dp0"

REM Hacer add de los archivos actualizados
git add admin/assets/css/product-edit.css
git add admin/product_edit.php

REM Hacer commit
git commit -m "v1.0: CSS dedicado para product_edit.php - Estilos independientes"

REM Hacer push
git push origin main

echo.
echo ========================================
echo Archivos subidos exitosamente!
echo.
echo ARCHIVOS NUEVOS/ACTUALIZADOS:
echo - admin/assets/css/product-edit.css (NUEVO - 400+ lineas)
echo - admin/product_edit.php (link al CSS agregado)
echo.
echo CAMBIOS EN ESTA VERSION:
echo - Creado archivo CSS dedicado solo para product_edit.php
echo - 400+ lineas de estilos con !important
echo - Estilos para: cards, botones, formularios, drag-drop, modales
echo - Animaciones suaves y transiciones
echo - Responsive design incluido
echo - Scrollbar personalizado
echo - Cache busting con v=1.0
echo.
echo IMPORTANTE:
echo 1. Abre: https://teal-fish-507993.hostingersite.com/admin/product_edit.php?id=59
echo 2. Hard refresh: Ctrl+Shift+F5 o Ctrl+Shift+R
echo 3. Verifica que product-edit.css se cargue (F12 ^> Network)
echo 4. Los estilos ahora son independientes del CSS principal
echo.
echo VENTAJAS:
echo - Puedes editar product-edit.css sin afectar otras paginas
echo - Carga selectiva (solo en product_edit.php)
echo - Facil de mantener y actualizar
echo - Cache independiente del admin.css
echo ========================================
pause
