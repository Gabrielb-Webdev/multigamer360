# 🚀 GUÍA DE IMPLEMENTACIÓN - SISTEMA DE PAGOS ARGENTINA
## MultiGamer360 E-commerce

---

## 📋 PASO 1: EJECUTAR MIGRACIÓN DE BASE DE DATOS

### ⚠️ IMPORTANTE: Hacer backup de la base de datos ANTES

```bash
# En Hostinger o tu panel de hosting:
# 1. Ir a PhpMyAdmin
# 2. Seleccionar tu base de datos
# 3. Exportar → Exportar (Quick) → SQL → Descargar
# 4. Guardar backup con nombre: backup_multigamer360_FECHA.sql
```

### Ejecutar migración:

1. **Abrir PhpMyAdmin**
2. **Seleccionar base de datos:** `u172569814_multigamer`
3. **Clic en pestaña "SQL"**
4. **Copiar TODO el contenido de:** `MIGRACION_PAGOS_ARGENTINA.sql`
5. **Pegar en el editor SQL**
6. **Clic en "Continuar"**
7. **Verificar que aparezca:** "Consulta ejecutada correctamente"

### Verificar que se creó todo:

```sql
-- Ejecutar esta consulta para verificar:
SHOW COLUMNS FROM orders LIKE '%payment%';
SHOW TABLES LIKE '%payment%';
SELECT * FROM payment_methods_config;
```

Deberías ver:
- ✅ Nuevas columnas en tabla `orders`
- ✅ Tabla `payment_transactions` creada
- ✅ Tabla `payment_methods_config` creada
- ✅ Tabla `bank_accounts` creada
- ✅ 5 métodos de pago insertados

---

## 📋 PASO 2: CONFIGURAR DATOS REALES

### A) Actualizar datos bancarios

Editar: `config/payment_config.php`

Buscar línea 73 y actualizar:

```php
'primary_account' => [
    'bank_name' => 'TU BANCO REAL', // Ej: "Banco Galicia"
    'account_type' => 'Caja de Ahorro', 
    'cbu' => 'TU_CBU_REAL_22_DIGITOS',
    'alias' => 'TU.ALIAS.BANCARIO',
    'cuit' => 'TU-CUIT-REAL',
    'holder_name' => 'NOMBRE DEL TITULAR',
],
```

También actualizar en la base de datos:

```sql
UPDATE bank_accounts 
SET 
    bank_name = 'TU BANCO REAL',
    cbu = 'TU_CBU_22_DIGITOS',
    alias = 'TU.ALIAS',
    holder_name = 'TITULAR REAL',
    holder_cuit = 'TU-CUIT',
    notes = 'Cuenta principal activa'
WHERE id = 1;
```

### B) Actualizar datos del local

Editar: `config/payment_config.php`

Buscar línea 155 y actualizar:

```php
'store_info' => [
    'name' => 'MultiGamer360',
    'address' => 'DIRECCIÓN REAL DEL LOCAL',
    'city' => 'CIUDAD',
    'postal_code' => 'CÓDIGO POSTAL',
    'phone' => '+54 9 11 XXXX-XXXX', // Tu teléfono
    'whatsapp' => '+54 9 11 XXXX-XXXX', // Tu WhatsApp
    'email' => 'TU_EMAIL@multigamer360.com',
    
    'schedule' => [
        'monday' => '10:00 - 20:00', // ACTUALIZAR tus horarios
        'tuesday' => '10:00 - 20:00',
        // ... etc
    ],
],
```

### C) Actualizar emails de notificación

Buscar línea 185:

```php
'notification_emails' => [
    'TU_EMAIL_REAL@gmail.com', // Email donde te llegan notificaciones
],
```

---

## 📋 PASO 3: CONFIGURAR MERCADO PAGO

### A) Crear cuenta Mercado Pago Vendedor

1. **Ir a:** https://www.mercadopago.com.ar/hub/registration/landing
2. **Registrarte como vendedor** (si no tenés cuenta)
3. **Verificar tu identidad** (DNI, selfie, etc)
4. **Esperar aprobación** (puede tardar 24-48hs)

### B) Obtener credenciales de PRUEBA (TEST)

1. **Ir a:** https://www.mercadopago.com.ar/developers/panel/app
2. **Crear una aplicación:**
   - Nombre: "MultiGamer360 Test"
   - Tipo: E-commerce
3. **Copiar credenciales de TEST:**
   - Public Key (comienza con `TEST-`)
   - Access Token (comienza con `TEST-`)

4. **Pegar en** `config/payment_config.php` línea 27:

```php
'sandbox' => [
    'public_key' => 'TEST-tu-public-key-aqui',
    'access_token' => 'TEST-tu-access-token-aqui',
],
```

### C) Obtener credenciales de PRODUCCIÓN (cuando estés listo)

1. En el mismo panel de desarrolladores
2. **Copiar credenciales de PRODUCCIÓN:**
   - Public Key (comienza con `APP_USR-`)
   - Access Token (comienza con `APP_USR-`)

3. **Pegar en** `config/payment_config.php` línea 32:

```php
'production' => [
    'public_key' => 'APP_USR-tu-public-key-real',
    'access_token' => 'APP_USR-tu-access-token-real',
],
```

### D) Cambiar a modo producción

Cuando estés listo para REAL (NO antes de probar):

```php
'mode' => 'production', // Cambiar de 'sandbox' a 'production'
```

---

## 📋 PASO 4: PROBAR CON TARJETAS DE PRUEBA

**USAR SOLO EN MODO SANDBOX (TEST)**

### Tarjetas de prueba de Mercado Pago:

| Tarjeta | Número | CVV | Fecha | Resultado |
|---------|--------|-----|-------|-----------|
| Mastercard | 5031 7557 3453 0604 | 123 | 11/25 | ✅ Aprobada |
| Visa | 4509 9535 6623 3704 | 123 | 11/25 | ✅ Aprobada |
| Visa | 4074 0000 0000 0004 | 123 | 11/25 | ❌ Rechazada |

**Datos de prueba:**
- Email: test_user_123456@testuser.com
- Documento: 12345678

Probar:
1. Agregar producto al carrito
2. Ir a checkout
3. Seleccionar "Retiro en tienda"
4. Seleccionar "Pagar ahora online"
5. Usar tarjeta de prueba
6. Verificar que funcione

---

## 📋 PASO 5: SUBIR ARCHIVOS AL SERVIDOR

### Archivos a subir vía FileZilla/FTP:

```
/config/payment_config.php → Subir
/MIGRACION_PAGOS_ARGENTINA.sql → NO subir (solo para ejecutar en DB)
```

Luego voy a crear más archivos que vas a tener que subir:
- `/api/payment/webhook-mercadopago.php`
- `/api/payment/process-mercadopago.php`
- Archivos modificados de checkout

---

## 📋 PASO 6: CONFIGURAR WEBHOOK EN MERCADO PAGO

Un webhook es una URL donde Mercado Pago te avisa cuando un pago fue aprobado.

### A) Configurar URL de webhook:

1. **Ir a:** https://www.mercadopago.com.ar/developers/panel/app
2. **Seleccionar tu aplicación**
3. **Ir a "Webhooks"**
4. **Agregar URL:**
   ```
   https://teal-fish-507993.hostingersite.com/api/payment/webhook-mercadopago.php
   ```
5. **Seleccionar eventos:**
   - ✅ Pagos (payment)
   - ✅ Merchant Orders

6. **Guardar**

### B) Probar webhook:

Mercado Pago tiene simulador de webhooks en el panel de desarrolladores.

---

## 📋 PASO 7: VERIFICAR PERMISOS DE CARPETAS

Asegurarte que estas carpetas tengan permisos de escritura (755):

```bash
/uploads/payment_proofs/  (crear si no existe)
/logs/payments/           (crear si no existe)
```

---

## ✅ CHECKLIST FINAL ANTES DE PONER EN PRODUCCIÓN

- [ ] Backup de base de datos realizado
- [ ] Migración SQL ejecutada exitosamente
- [ ] Datos bancarios actualizados (CBU, Alias, etc)
- [ ] Datos del local actualizados (dirección, horarios)
- [ ] Email de notificaciones configurado
- [ ] Credenciales de Mercado Pago TEST funcionando
- [ ] Probado pago con tarjeta de prueba
- [ ] Probado flujo de transferencia bancaria
- [ ] Probado código de reserva para pago presencial
- [ ] Webhook de Mercado Pago configurado
- [ ] Todos los archivos subidos al servidor
- [ ] Probado en móvil y escritorio
- [ ] Credenciales de Mercado Pago PRODUCCIÓN obtenidas
- [ ] Cambiar `mode` a `production` en config

---

## 🆘 PROBLEMAS COMUNES

### "Error al ejecutar SQL"
- Verificar que estés en la base de datos correcta
- Verificar que no haya caracteres raros al copiar/pegar
- Ejecutar consultas de a una si falla

### "Mercado Pago no funciona"
- Verificar que estés en modo 'sandbox' para pruebas
- Verificar credenciales copiadas correctamente
- Ver errores en navegador (F12 → Console)

### "No llegan emails de confirmación"
- Verificar configuración SMTP en `config/email_config.php`
- Verificar que email no esté en spam

### "Webhook no se ejecuta"
- Verificar URL en panel de Mercado Pago
- Verificar que archivo exista en servidor
- Verificar logs en `/logs/payments/webhooks.log`

---

## 📞 SIGUIENTE PASO

Una vez completados TODOS los pasos anteriores, avisame y seguimos con:

1. ✅ Modificar página de checkout (nuevas opciones de pago)
2. ✅ Crear archivos para procesar pagos
3. ✅ Crear webhooks
4. ✅ Panel admin para validar transferencias
5. ✅ Emails personalizados

---

**¿Completaste los pasos? ¿Algún error? Avisame y lo solucionamos! 🚀**
