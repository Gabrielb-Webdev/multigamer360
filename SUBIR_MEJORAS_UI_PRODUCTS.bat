@echo off
echo ========================================
echo  SUBIR MEJORAS UI/UX PRODUCTS.PHP
echo  Version 4.0 - Premium UI
echo ========================================
echo.

echo [1/3] Agregando archivos al staging...
git add admin/api/bulk_update_status.php
git add admin/inc/header.php
git add admin/assets/css/admin.css
git add SUBIR_MEJORAS_UI_PRODUCTS.bat
echo     OK - Archivos agregados
echo.

echo [2/3] Creando commit...
git commit -m "MEJORA: UI/UX Premium para products.php v4.0

- Fix: bulk_update_status ahora sincroniza campo is_active
- Fix: Productos ahora se actualizan correctamente (0 -> N productos)
- Mejora: Cards de estadisticas con gradientes y animaciones
- Mejora: Filtros modernos con efectos hover
- Mejora: Tabla de productos con efectos 3D
- Mejora: Badges con gradientes y sombras
- Mejora: Modales embellecidos
- Mejora: Paginacion moderna
- Mejora: Botones con animaciones suaves
- Mejora: Checkboxes mejorados
- Mejora: Sistema de acciones en masa embellecido
- Version CSS: v4.0 (cache busting)"
echo     OK - Commit creado
echo.

echo [3/3] Subiendo a GitHub (se sincroniza automaticamente con Hostinger)...
git push origin main
echo     OK - Cambios subidos
echo.

echo ========================================
echo  COMPLETADO
echo ========================================
echo.
echo Los cambios se han subido correctamente.
echo GitHub sincronizara automaticamente con Hostinger.
echo.
echo IMPORTANTE:
echo - Recarga la pagina con Ctrl+F5 para ver los cambios
echo - Los estilos CSS v4.0 se cargaran automaticamente
echo - El bug de bulk_update_status esta corregido
echo.
pause
