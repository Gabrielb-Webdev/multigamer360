# 🎟️ SISTEMA COMPLETO DE CUPONES Y NOTIFICACIONES V5.0

## 📋 RESUMEN DE FUNCIONALIDADES

Este sistema implementa un manejo completo de cupones con las siguientes características:

### ✅ LÍMITES DE USO

1. **Límite por Usuario**
   - Un usuario NO puede canjear el mismo cupón más de N veces
   - Controlado por campo `per_user_limit` en tabla `coupons`
   - Ejemplo: Si `per_user_limit = 1`, cada usuario solo puede usar el cupón una vez
   - Se registra en tabla `coupon_usage` cada uso individual

2. **Límite Total de Usos**
   - El cupón tiene un límite global de usos
   - Controlado por campo `usage_limit` en tabla `coupons`
   - Ejemplo: Si `usage_limit = 100`, solo 100 usuarios pueden usar el cupón
   - Sistema "primero en llegar, primero en usar"
   - Contador en tiempo real en `used_count`

### 🔔 TIPOS DE CUPONES

1. **PRIVADO** (`notification_type = 'private'`)
   - Solo se genera el código
   - El administrador lo copia y lo envía manualmente (WhatsApp, email, etc.)
   - NO se notifica a ningún usuario automáticamente
   - Ideal para: Cupones personalizados, influencers, clientes VIP

2. **PÚBLICO** (`notification_type = 'public'`)
   - El cupón está disponible en el sistema
   - Cualquiera que tenga el código puede usarlo
   - NO se envían notificaciones automáticas
   - Ideal para: Campañas en redes sociales, códigos compartidos públicamente

3. **NOTIFICAR A TODOS** (`notification_type = 'all_users'`)
   - Se envía una notificación a TODOS los usuarios registrados
   - Aparece en su sección de notificaciones
   - Respeta los límites de uso (primero en llegar)
   - Ideal para: Black Friday, lanzamientos, promociones flash

### ⏰ ZONA HORARIA ARGENTINA

- Todas las fechas/horas se manejan en `America/Argentina/Buenos_Aires`
- Configurado en `admin/coupons.php`
- Las fechas de inicio/fin se procesan en horario argentino
- Expiración automática según fecha y hora local

### 📊 REGISTRO DE USO

Cada vez que un usuario usa un cupón en un pedido:
1. Se incrementa `used_count` en tabla `coupons`
2. Se crea un registro en tabla `coupon_usage` con:
   - ID del cupón
   - ID del usuario
   - ID del pedido (orden)
   - Monto del descuento aplicado
   - Timestamp del uso

Esto permite:
- Auditoría completa de quién usó qué cupón
- Prevenir uso duplicado por el mismo usuario
- Estadísticas detalladas de cupones
- Historial de descuentos por usuario

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS

### Tabla: `coupons`

```sql
- id: INT (PK)
- code: VARCHAR(50) UNIQUE - Código del cupón (ej: "GAMER25")
- name: VARCHAR(255) - Nombre descriptivo
- description: TEXT - Descripción
- type: ENUM('percentage', 'fixed') - Tipo de descuento
- value: DECIMAL(10,2) - Valor del descuento
- minimum_amount: DECIMAL(10,2) - Compra mínima requerida
- maximum_discount: DECIMAL(10,2) - Descuento máximo (solo percentage)
- usage_limit: INT NULL - Límite total de usos (NULL = ilimitado)
- used_count: INT - Contador de usos
- per_user_limit: INT - Límite de usos por usuario
- start_date: DATETIME - Fecha de inicio
- end_date: DATETIME NULL - Fecha de fin (NULL = sin expiración)
- is_active: BOOLEAN - Activo/Inactivo
- notification_type: ENUM('private', 'public', 'all_users') - Tipo de cupón
- notified_at: TIMESTAMP NULL - Cuándo se notificó a usuarios
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

### Tabla: `coupon_usage`

```sql
- id: INT (PK)
- coupon_id: INT (FK → coupons.id)
- user_id: INT (FK → users.id)
- order_id: INT NULL (FK → orders.id)
- used_at: TIMESTAMP - Cuándo se usó
- discount_amount: DECIMAL(10,2) - Monto del descuento aplicado
```

### Tabla: `user_notifications`

```sql
- id: INT (PK)
- user_id: INT (FK → users.id)
- type: ENUM('coupon', 'order', 'system', 'promotion')
- title: VARCHAR(255) - Título de la notificación
- message: TEXT - Mensaje completo
- related_id: INT NULL - ID relacionado (ej: coupon_id)
- is_read: BOOLEAN - Leída/No leída
- created_at: TIMESTAMP
- read_at: TIMESTAMP NULL - Cuándo se marcó como leída
```

---

## 📁 ARCHIVOS DEL SISTEMA

### Archivos Nuevos Creados

1. **config/notifications_table.sql**
   - Script SQL para crear tabla de notificaciones
   - Actualización de tabla coupons con nuevos campos

2. **ajax/notifications.php** (API REST)
   - GET: Obtener notificaciones del usuario
   - POST: Marcar como leída (una o todas)
   - DELETE: Eliminar notificación
   - Autenticación requerida vía sesión

3. **includes/notification_manager.php**
   - Clase NotificationManager
   - `sendToUser()`: Enviar a un usuario específico
   - `sendToAllUsers()`: Enviar a todos los usuarios activos
   - `notifyNewCoupon()`: Notificación específica para cupones nuevos
   - `getUnreadCount()`: Contador de no leídas

4. **MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql**
   - Script completo de migración
   - Crea todas las tablas necesarias
   - Agrega columnas a tabla coupons existente
   - Documentación incluida

5. **DOCUMENTACION_CUPONES_V5.0.md** (este archivo)
   - Documentación completa del sistema

### Archivos Modificados

1. **admin/coupons.php**
   - Configuración de timezone Argentina
   - Integración con NotificationManager
   - Campo `notification_type` en formulario de creación
   - Envío automático de notificaciones al crear cupón tipo "all_users"
   - Columna "Alcance" en tabla de cupones
   - Ayuda contextual según tipo seleccionado

2. **includes/order_manager.php**
   - Método `incrementCouponUsage()` actualizado
   - Ahora registra uso en tabla `coupon_usage`
   - Incluye user_id, order_id y discount_amount

3. **ajax/validate-coupon.php** (sin cambios)
   - Ya tenía la validación de límites por usuario
   - Verifica `per_user_limit` consultando `coupon_usage`
   - Verifica `usage_limit` consultando `used_count`

---

## 🚀 INSTRUCCIONES DE INSTALACIÓN

### 1. Ejecutar Migración SQL

```bash
# En phpMyAdmin o línea de comandos MySQL
mysql -u usuario -p nombre_base_datos < MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql
```

O copiar y pegar el contenido en phpMyAdmin → SQL.

### 2. Subir Archivos al Servidor

```powershell
# Ejecutar el script de deployment
.\SUBIR_CUPONES_V5.0.bat
```

O manualmente:
- `ajax/notifications.php` → subir a carpeta ajax/
- `includes/notification_manager.php` → subir a carpeta includes/
- `admin/coupons.php` → sobrescribir en admin/
- `includes/order_manager.php` → sobrescribir en includes/

### 3. Verificar Permisos

Asegurar que los archivos tengan permisos de lectura/ejecución:
```bash
chmod 644 ajax/notifications.php
chmod 644 includes/notification_manager.php
chmod 644 admin/coupons.php
chmod 644 includes/order_manager.php
```

### 4. Limpiar Caché

En el navegador: **Ctrl + Shift + F5** o **Cmd + Shift + R**

---

## 🎯 CASOS DE USO

### Caso 1: Cupón Privado para Influencer

**Escenario**: Quieres dar un cupón del 30% a un influencer específico para que lo comparta con sus seguidores (máximo 50 usos, 1 por persona).

**Configuración**:
```
Código: INFLUENCER30
Tipo de descuento: Porcentaje
Valor: 30
Límite de usos: 50
Usos por usuario: 1
Tipo de cupón: PRIVADO
```

**Resultado**: Se genera el código INFLUENCER30. Lo copias y se lo envías al influencer por WhatsApp/email. Las primeras 50 personas que lo usen obtendrán el descuento.

---

### Caso 2: Black Friday para Todos los Usuarios

**Escenario**: Black Friday, quieres dar 40% de descuento a todos tus usuarios registrados, pero solo tienes stock para 200 órdenes.

**Configuración**:
```
Código: BLACKFRIDAY40
Tipo de descuento: Porcentaje
Valor: 40
Límite de usos: 200
Usos por usuario: 1
Tipo de cupón: NOTIFICAR A TODOS
```

**Resultado**: Se envía una notificación a TODOS los usuarios registrados. Los primeros 200 en usar el cupón obtendrán el descuento. Los demás verán un mensaje de "límite alcanzado".

---

### Caso 3: Cupón Público para Redes Sociales

**Escenario**: Quieres publicar en Instagram un cupón de $500 de descuento sin límite de usos.

**Configuración**:
```
Código: INSTAGRAM500
Tipo de descuento: Monto Fijo
Valor: 500
Límite de usos: (dejar vacío)
Usos por usuario: 1
Tipo de cupón: PÚBLICO
```

**Resultado**: Publicas "Usa el código INSTAGRAM500 para $500 OFF" en Instagram. Cualquiera puede usarlo, cada persona una vez, sin límite total.

---

## 🔍 VALIDACIONES IMPLEMENTADAS

### En `ajax/validate-coupon.php`:

1. ✅ Usuario autenticado
2. ✅ Código de cupón válido
3. ✅ Cupón activo (`is_active = 1`)
4. ✅ Fechas válidas (entre `start_date` y `end_date`)
5. ✅ Monto mínimo cumplido
6. ✅ Límite total no excedido (`used_count < usage_limit`)
7. ✅ Límite por usuario no excedido (consulta en `coupon_usage`)

### En `includes/order_manager.php`:

1. ✅ Registro de uso en `coupon_usage` con todos los datos
2. ✅ Incremento de contador `used_count`
3. ✅ Asociación con orden (order_id)
4. ✅ Monto de descuento registrado

---

## 📱 API DE NOTIFICACIONES

### Endpoint: `ajax/notifications.php`

#### GET - Obtener Notificaciones

```javascript
// Obtener últimas 20 notificaciones
fetch('/ajax/notifications.php?limit=20')
  .then(res => res.json())
  .then(data => {
    console.log(data.notifications); // Array de notificaciones
    console.log(data.unread_count); // Contador de no leídas
  });

// Solo no leídas
fetch('/ajax/notifications.php?unread_only=true')
  .then(res => res.json())
  .then(data => console.log(data));
```

#### POST - Marcar como Leída

```javascript
// Marcar una notificación
fetch('/ajax/notifications.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({notification_id: 123})
});

// Marcar todas como leídas
fetch('/ajax/notifications.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({mark_all_read: true})
});
```

#### DELETE - Eliminar Notificación

```javascript
fetch('/ajax/notifications.php', {
  method: 'DELETE',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({notification_id: 123})
});
```

---

## 🎨 UI/UX

### Formulario de Creación de Cupones

- Campo "Tipo de Cupón" con 3 opciones
- Ayuda contextual que cambia según el tipo seleccionado
- Alertas con colores diferentes:
  - **Azul** (info): Privado
  - **Azul primario**: Público
  - **Amarillo** (warning): Notificar a todos (advertencia de envío masivo)

### Tabla de Cupones

- Columna "Alcance" muestra el tipo:
  - 🔒 **Privado** (gris)
  - 🌐 **Público** (azul)
  - 🔔 **Notificado** (amarillo) + fecha/hora de notificación

- Barra de progreso de usos (verde → amarillo → rojo)
- Estados visuales: Activo, Inactivo, Expirado, Programado

---

## ⚙️ CONFIGURACIÓN

### Timezone

El sistema usa timezone de Argentina configurado en `coupons.php`:

```php
date_default_timezone_set('America/Argentina/Buenos_Aires');
```

Todas las fechas se procesan y almacenan en este timezone.

---

## 🐛 TROUBLESHOOTING

### Las notificaciones no se envían

1. Verificar que la tabla `user_notifications` existe
2. Verificar que hay usuarios activos (`is_active = 1`)
3. Revisar error_log de PHP
4. Verificar que `notification_manager.php` está en `includes/`

### El contador de usos no actualiza

1. Verificar que la tabla `coupon_usage` existe
2. Verificar que `order_manager.php` fue actualizado
3. Revisar error_log de PHP

### Un usuario puede usar el cupón más veces de las permitidas

1. Verificar que `per_user_limit` está configurado correctamente
2. Verificar que `validate-coupon.php` consulta `coupon_usage`
3. Limpiar caché del navegador

---

## 📊 CONSULTAS SQL ÚTILES

### Ver usos de un cupón

```sql
SELECT 
    c.code,
    u.email,
    cu.discount_amount,
    cu.used_at,
    o.order_number
FROM coupon_usage cu
INNER JOIN coupons c ON cu.coupon_id = c.id
INNER JOIN users u ON cu.user_id = u.id
LEFT JOIN orders o ON cu.order_id = o.id
WHERE c.code = 'GAMER25'
ORDER BY cu.used_at DESC;
```

### Cupones más usados

```sql
SELECT 
    code,
    name,
    used_count,
    usage_limit,
    (used_count / NULLIF(usage_limit, 0) * 100) as porcentaje_uso
FROM coupons
WHERE usage_limit IS NOT NULL
ORDER BY used_count DESC
LIMIT 10;
```

### Usuarios con más cupones canjeados

```sql
SELECT 
    u.email,
    COUNT(DISTINCT cu.coupon_id) as cupones_diferentes,
    COUNT(*) as total_usos,
    SUM(cu.discount_amount) as descuento_total
FROM coupon_usage cu
INNER JOIN users u ON cu.user_id = u.id
GROUP BY u.id
ORDER BY descuento_total DESC
LIMIT 10;
```

---

## 📞 SOPORTE

Para dudas o problemas:
1. Revisar este documento
2. Verificar error_log de PHP
3. Consultar MIGRACION_V5.0_CUPONES_NOTIFICACIONES.sql para detalles técnicos

---

**Versión**: 5.0  
**Fecha**: 19 de febrero de 2026  
**Autor**: GitHub Copilot para MultiGamer360
