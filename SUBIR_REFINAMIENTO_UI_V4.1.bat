@echo off
echo ================================================
echo SUBIENDO REFINAMIENTO UI/UX V4.1 A HOSTINGER
echo ================================================
echo.

cd /d "E:\Users\gabri\Documentos\Brodev Lab\Clientes\multigamer360"

echo [1/4] Agregando archivos modificados...
git add admin/assets/css/admin.css
git add admin/inc/header.php

echo.
echo [2/4] Creando commit...
git commit -m "REFINAMIENTO UI/UX V4.1: Estilos discretos y profesionales para products.php - Eliminados efectos hover exagerados (zoom, scale, translateY) - Badges sin gradientes ni sombras - Transiciones suaves y sutiles - Bordes mas delgados y discretos - Colores mas profesionales - Focus states refinados"

echo.
echo [3/4] Subiendo a GitHub...
git push origin main

echo.
echo [4/4] Deployment automatico a Hostinger en progreso...
timeout /t 3

echo.
echo ================================================
echo COMPLETADO - UI/UX REFINADO V4.1 DESPLEGADO
echo ================================================
echo.
echo La pagina se vera mas profesional y discreta
echo Sin efectos hover exagerados
echo.
pause
