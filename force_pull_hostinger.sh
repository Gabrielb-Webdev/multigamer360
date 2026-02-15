#!/bin/bash

# Script para forzar actualización en Hostinger vía SSH
# Este script se debe ejecutar directamente en el servidor de Hostinger

echo "=========================================="
echo "🔄 FORZANDO ACTUALIZACIÓN DESDE GITHUB"
echo "=========================================="

cd /home/u962947096/domains/teal-fish-507993.hostingersite.com/public_html

echo ""
echo "📍 Directorio actual:"
pwd

echo ""
echo "🌿 Branch actual:"
git branch

echo ""
echo "📥 Haciendo fetch de cambios..."
git fetch origin

echo ""
echo "📊 Estado del repositorio:"
git status

echo ""
echo "⬇️ Haciendo pull de main..."
git pull origin main --force

echo ""
echo "✅ Actualización completada!"
echo ""
echo "🔍 Último commit:"
git log -1 --oneline

echo ""
echo "=========================================="
echo "🔄 Para verificar en navegador:"
echo "1. Presiona Ctrl + F5 para limpiar cache"
echo "2. Abre DevTools (F12) → Network → Disable cache"
echo "3. Recarga la página"
echo "=========================================="
