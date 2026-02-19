@echo off
chcp 65001 > nul
echo ========================================
echo 🎟️ SUBIENDO SISTEMA DE CUPONES V5.0
echo ========================================
echo.

cd /d "%~dp0"

echo 📝 Agregando archivos nuevos y modificados...
git add config/notifications_table.sql
git add ajax/notifications.php
git add includes/notification_manager.php
git add admin/coupons.php
git add includes/order_manager.php
git add MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql
git add DOCUMENTACION_CUPONES_V5.0.md

echo.
echo 💾 Creando commit...
git commit -m "SISTEMA CUPONES V5.0: Límites de uso, notificaciones y timezone Argentina

NUEVAS FUNCIONALIDADES:
✅ Límite de usos por usuario (per_user_limit)
✅ Límite total de usos respetado (usage_limit)
✅ 3 tipos de cupones: Privado, Público, Notificar a todos
✅ Sistema de notificaciones para usuarios
✅ Timezone Argentina (America/Argentina/Buenos_Aires)
✅ Registro completo en coupon_usage

ARCHIVOS NUEVOS:
- ajax/notifications.php (API REST notificaciones)
- includes/notification_manager.php (envío masivo)
- config/notifications_table.sql
- MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql
- DOCUMENTACION_CUPONES_V5.0.md

ARCHIVOS MODIFICADOS:
- admin/coupons.php (UI mejorada + notificaciones)
- includes/order_manager.php (registro de uso mejorado)

INSTRUCCIONES DEPLOYMENT:
1. Ejecutar MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql en phpMyAdmin
2. Este script sube automáticamente los archivos PHP
3. Limpiar caché: Ctrl+Shift+F5
4. Leer DOCUMENTACION_CUPONES_V5.0.md para casos de uso"

echo.
echo 🌐 Subiendo a GitHub...
git push origin main

echo.
echo ========================================
echo ✅ SISTEMA DE CUPONES V5.0 DESPLEGADO
echo ========================================
echo.
echo 📋 PRÓXIMOS PASOS:
echo.
echo 1. IR A PHPMYADMIN:
echo    https://teal-fish-507993.hostingersite.com:2083/cpsess.../phpMyAdmin
echo.
echo 2. EJECUTAR EL SQL:
echo    Archivo: MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql
echo    Ir a pestaña SQL y pegar el contenido completo
echo.
echo 3. VERIFICAR EN ADMIN:
echo    https://teal-fish-507993.hostingersite.com/admin/coupons.php
echo    Ctrl+Shift+F5 para limpiar caché
echo.
echo 4. CREAR CUPÓN DE PRUEBA:
echo    - Tipo: "Notificar a todos los usuarios"
echo    - Límite de usos: 10
echo    - Usos por usuario: 1
echo.
echo 5. LEER DOCUMENTACIÓN:
echo    Abrir: DOCUMENTACION_CUPONES_V5.0.md
echo.
pause
