@echo off
chcp 65001 > nul
echo ========================================
echo 🎨 SUBIENDO MEJORAS UI FILTROS PRODUCTOS
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando cambios...
git add productos.php

echo.
echo 💾 Creando commit...
git commit -m "UX MEJORA: Sistema de filtros moderno con tarjetas visuales

✨ NUEVAS FUNCIONALIDADES:
- Filtro principal destacado 'TIPO DE PRODUCTO' con tarjetas visuales
- Tarjetas grandes con iconos personalizados por categoría:
  * Videojuegos (gamepad) - rojo
  * Consolas (TV) - azul
  * Accesorios (headphones) - amarillo
  * Coleccionables (star) - morado
- Cards interactivas con efectos hover y selección
- Animación de check al seleccionar
- Diseño glassmorphism coherente con el tema
- Toggle intuitivo con un solo click
- Contador de productos por categoría visible

🎨 MEJORAS UI:
- Mejor jerarquía visual de filtros
- Iconos más grandes y claros
- Bordes y sombras sutiles
- Animaciones suaves
- Estados activos bien diferenciados

💡 BENEFICIOS:
- Mucho más fácil filtrar por tipo de producto
- UI más moderna y profesional
- Mejor experiencia de usuario
- Filtros más visibles y accesibles"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ MEJORAS DESPLEGADAS
echo ========================================
echo.
echo Presiona Ctrl+Shift+F5 en el navegador
echo para ver los filtros con tarjetas modernas
echo.
pause
