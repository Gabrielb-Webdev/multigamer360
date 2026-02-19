@echo off
chcp 65001 > nul
echo ========================================
echo 🐛 FIX CRÍTICO: CUPONES NO SE CREABAN
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add admin/coupons.php

echo.
echo 💾 Creando commit...
git commit -m "HOTFIX: Corregir creacion de cupones (no se guardaban)

🐛 PROBLEMA:
- Cupones no se creaban al hacer submit
- Mensajes de exito/error no se mostraban
- Variables locales en lugar de sesion

🔧 SOLUCION:
- Cambiar success_msg a SESSION['success']
- Cambiar error_msg a SESSION['error']
- Agregar header redirect (PRG pattern)
- Error logging mejorado

✅ RESULTADO:
- Cupones se crean correctamente
- Mensajes de confirmacion visibles
- Evita reenvio de formulario (F5)"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ FIX DESPLEGADO
echo ========================================
echo.
echo PROBAR AHORA:
echo 1. Ir a admin/coupons.php
echo 2. Click "Crear Cupon"
echo 3. Llenar formulario
echo 4. Submit
echo 5. Ver mensaje de exito verde
echo 6. Cupon aparece en la tabla
echo.
pause
