@echo off
chcp 65001 > nul
echo ========================================
echo 📅 MEJORANDO UI/UX SELECTOR DE FECHAS
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add admin/assets/css/admin.css

echo.
echo 💾 Creando commit...
git commit -m "UX PREMIUM: Mejora del selector de fechas/calendario

✨ MEJORAS VISUALES:
- Gradiente sutil en inputs de fecha
- Bordes redondeados y sombras elegantes
- Hover effect con elevación
- Focus state con glow effect
- Icono de calendario con color morado
- Animación de entrada suave

🎨 INTERACTIVIDAD:
- Hover en icono calendario (escala + fondo)
- Transiciones fluidas (cubic-bezier)
- Estados de validación (verde/rojo)
- Disabled state mejorado

🔧 DETALLES:
- Labels con mejor tipografía
- Helper text para orientación
- Responsive optimizado para móvil
- Accesibilidad mejorada

💡 RESULTADO:
Selector de fechas premium con mejor UX"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ DATE PICKER MEJORADO
echo ========================================
echo.
echo PROBAR EN ADMIN:
echo 1. Ir a admin/coupons.php
echo 2. Click en "Crear Cupón"
echo 3. Ver campos Fecha de Inicio/Fin
echo 4. Hover sobre el input
echo 5. Click en icono calendario
echo.
echo Presiona Ctrl+Shift+F5 para limpiar caché
echo.
pause
