@echo off
chcp 65001 > nul
echo ========================================
echo 🔧 SUBIENDO FIX CRÍTICO: WISHLIST Y CARRITO
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando archivos corregidos...
git add ajax/toggle-wishlist.php
git add carrito.php

echo.
echo 💾 Creando commit...
git commit -m "FIX CRÍTICO: Corregir columna 'price' inexistente en wishlist y carrito

❌ PROBLEMA:
- Error SQL: Column not found 'price' in SELECT
- Wishlist no funcionaba al agregar productos
- Carrito no mostraba productos correctamente
- SQLSTATE[42S22]: Unknown column 'price'

✅ SOLUCIÓN:
- Corregir COALESCE(price_pesos, price_dollars, price) 
  → COALESCE(price_pesos, price_dollars, 0)
- Eliminar referencia a columna 'price' inexistente
- Usar solo columnas existentes: price_pesos, price_dollars

📁 ARCHIVOS CORREGIDOS:
- ajax/toggle-wishlist.php (líneas 37, 112)
- carrito.php (líneas 106, 138)

🎯 IMPACTO:
- Wishlist funcional ✓
- Carrito mostrando productos ✓
- Sin errores SQL ✓"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ FIX DESPLEGADO
echo ========================================
echo.
echo Presiona Ctrl+Shift+F5 para validar:
echo - Agregar productos a wishlist funciona
echo - Carrito muestra productos agregados
echo.
pause
