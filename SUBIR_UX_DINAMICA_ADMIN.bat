@echo off
chcp 65001 > nul
echo ========================================
echo 🚀 SUBIENDO UX DINÁMICA SIN RECARGAS
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando archivos...
git add admin/products.php
git add admin/assets/js/products-dynamic.js
git add admin/assets/css/products-dynamic.css
git add admin/inc/header.php

echo.
echo 💾 Creando commit...
git commit -m "UX MEJORA: Sistema dinámico sin recargas para admin productos

✨ NUEVAS FUNCIONALIDADES:
- Acciones en masa sin recargar página (activar/desactivar)
- Eliminación de productos sin reload
- Sistema de toasts modernos en lugar de alerts
- Actualización de filas en tiempo real
- Animaciones suaves para todas las acciones

🎨 MEJORAS UX:
- Toast Manager con notificaciones slide-in
- Actualización dinámica del estado de productos
- Eliminación con animación fade-out
- Contador de productos actualizado en tiempo real
- Badges con transiciones suaves
- Hover effects mejorados en botones y checkboxes

💡 ARCHIVOS:
- admin/assets/js/products-dynamic.js (NUEVO)
- admin/assets/css/products-dynamic.css (NUEVO)
- admin/products.php (actualizado con script dinámico)
- admin/inc/header.php (incluyeconsultas CSS dinámico)

🔧 BENEFICIOS:
- Panel admin mucho más fluido y rápido
- Sin recargas molestas después de cada acción
- Feedback visual inmediato
- Experiencia moderna al estilo SPA
- Menos consumo de servidor (no recargar HTML completo)

BREAKING: Las funciones antiguas se sobrescriben con versiones dinámicas"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ UX DINÁMICA DESPLEGADA
echo ========================================
echo.
echo PROBAR EN ADMIN:
echo 1. Ir a admin/products.php
echo 2. Seleccionar productos
echo 3. Acciones en masa → Cambiar estado
echo 4. Ver actualización SIN RELOAD
echo 5. Notificaciones toast modernas
echo.
echo Presiona Ctrl+Shift+F5 para limpiar caché
echo.
pause
