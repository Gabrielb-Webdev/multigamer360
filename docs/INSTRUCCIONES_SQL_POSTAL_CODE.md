# Instrucciones para Agregar Código Postal y Last Login

## ⚠️ IMPORTANTE - EJECUTAR ANTES DE USAR LAS NUEVAS FUNCIONALIDADES

Antes de que las nuevas funcionalidades de código postal y last_login funcionen, debes ejecutar el archivo SQL en tu base de datos.

## 📋 Pasos a seguir:

### 1. Abrir phpMyAdmin
- Ve a tu phpMyAdmin (local o Hostinger)
- Selecciona la base de datos `u851317150_mg360_db` (o la que estés usando)

### 2. Ejecutar el SQL
- Abre el archivo `add_postal_code_last_login.sql`
- Copia todo el contenido
- Ve a la pestaña "SQL" en phpMyAdmin
- Pega el código y haz clic en "Continuar"

### 3. Verificar que se ejecutó correctamente
Deberías ver un mensaje como:
```
✓ 2 columnas agregadas exitosamente
✓ 2 índices creados
```

### 4. Verificar estructura de la tabla
En phpMyAdmin, ve a la tabla `users` y verifica que ahora tenga:
- ✅ Columna `postal_code` (VARCHAR 10, NULL)
- ✅ Columna `last_login` (DATETIME, NULL)

## 🎯 Nuevas Funcionalidades Activadas:

### 1️⃣ Campo de Código Postal en Perfil
- Los usuarios ahora pueden guardar su código postal en su perfil
- Se muestra en el formulario "Editar Perfil"
- Incluye texto de ayuda: "Se usará para calcular automáticamente el envío en el carrito"

### 2️⃣ Auto-completar en Carrito
- Si el usuario tiene código postal guardado, se carga automáticamente en el carrito
- El botón "CALCULAR" se habilita automáticamente
- Ahorra tiempo al usuario en cada compra

### 3️⃣ Registro de Last Login
- Cada vez que un usuario inicia sesión, se actualiza `last_login` con la fecha/hora actual
- Útil para estadísticas y seguridad

### 4️⃣ Sin Auto-selección de Envío
- Cuando se calcula el código postal, se muestran las opciones
- El usuario DEBE seleccionar manualmente el método de envío
- No se auto-selecciona ninguna opción
- El total solo se muestra después de seleccionar método

## 🔍 Testing

Después de ejecutar el SQL, prueba:

1. **Perfil de Usuario:**
   - Login como usuario normal
   - Ve a tu perfil
   - Edita perfil y agrega código postal (ej: 1425)
   - Guarda cambios

2. **Carrito:**
   - Agrega un producto al carrito
   - Ve al carrito
   - Verifica que el código postal ya está precargado
   - Haz clic en "CALCULAR"
   - Verifica que NO se selecciona automáticamente ninguna opción
   - Selecciona manualmente una opción (Moto CABA, Correo, etc.)
   - Verifica que ahora sí aparece el TOTAL

3. **Last Login:**
   - Cierra sesión (logout)
   - Vuelve a iniciar sesión
   - Ve a phpMyAdmin > tabla users > tu usuario
   - Verifica que `last_login` tiene la fecha/hora actual

## ❌ Si algo sale mal

Si ves errores como:
```
Unknown column 'postal_code' in 'field list'
```

Significa que no ejecutaste el SQL. Vuelve al paso 2.

## 📌 Archivos Modificados

- ✅ `add_postal_code_last_login.sql` - Archivo SQL a ejecutar
- ✅ `profile.php` - Formulario con campo de código postal
- ✅ `ajax/update-profile.php` - Guarda código postal en BD
- ✅ `config/user_manager_simple.php` - Actualiza last_login en login
- ✅ `carrito.php` - Auto-completa código postal y sin auto-selección

## 🚀 Deploy

Los cambios ya están en GitHub y se desplegarán automáticamente a Hostinger.

**NO OLVIDES ejecutar el SQL también en la base de datos de Hostinger!**
