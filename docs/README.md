# 🎮 Multigamer360 - E-commerce de Videojuegos Retro

## 📋 Instalación Completa

### ✅ **MÉTODO AUTOMÁTICO (Recomendado)**

1. **Asegurar que XAMPP esté ejecutándose:**
   - Abrir XAMPP Control Panel
   - Iniciar **Apache** y **MySQL**

2. **Ejecutar instalación automática:**
   - Navegar a: `http://localhost/multigamer360/install.php`
   - Hacer clic en "Iniciar Instalación"
   - Esperar a que termine (30 segundos aprox.)

3. **¡Listo!** Tu e-commerce estará funcionando completamente.

---

### 🛠️ **MÉTODO MANUAL (phpMyAdmin)**

Si prefieres hacerlo manualmente:

1. **Abrir phpMyAdmin:**
   - Ir a: `http://localhost/phpmyadmin`

2. **Crear base de datos:**
   - Clic en "Nueva" en el panel izquierdo
   - Nombre: `multigamer360`
   - Cotejamiento: `utf8mb4_general_ci`
   - Clic en "Crear"

3. **Importar estructura y datos:**
   - Seleccionar la base de datos `multigamer360`
   - Ir a la pestaña "SQL"
   - Copiar todo el contenido del archivo `install_database.sql`
   - Pegar en el área de texto
   - Clic en "Continuar"

---

## 🗃️ **Estructura de Base de Datos Creada**

### **Tablas Principales:**
- **`products`** - Catálogo de productos
- **`categories`** - Categorías (PlayStation, Nintendo, Xbox, etc.)
- **`brands`** - Marcas (Sony, Nintendo, Microsoft, etc.)
- **`users`** - Usuarios y administradores
- **`orders`** - Pedidos realizados
- **`order_items`** - Items de cada pedido

### **Tablas de Soporte:**
- **`user_addresses`** - Direcciones de envío/facturación
- **`user_favorites`** - Lista de favoritos
- **`product_reviews`** - Reseñas y calificaciones
- **`coupons`** - Cupones de descuento
- **`shipping_methods`** - Métodos de envío
- **`cart_sessions`** - Carrito temporal
- **`contacts`** - Mensajes de contacto
- **`site_settings`** - Configuraciones del sitio

---

## 👥 **Usuarios de Prueba Creados**

| Email | Contraseña | Rol | Descripción |
|-------|------------|-----|-------------|
| `admin@multigamer360.com` | `password123` | Admin | Administrador principal |
| `cliente@ejemplo.com` | `password123` | Cliente | Usuario comprador |
| `maria@ejemplo.com` | `password123` | Cliente | Usuario comprador |
| `carlos@ejemplo.com` | `password123` | Cliente | Usuario sin verificar |

---

## 🎯 **Productos de Ejemplo Incluidos**

### **18 Productos Cargados:**
- **PlayStation:** Rayman 2, Final Fantasy VII, Metal Gear Solid, Crash Bandicoot
- **Nintendo:** Super Mario 64, Zelda Ocarina of Time, Super Metroid, Donkey Kong Country  
- **Xbox:** Halo Combat Evolved, Fable
- **Sega:** Sonic Genesis, Streets of Rage 2
- **Consolas Retro:** NES, Genesis, SNES
- **Accesorios:** Controles, Memory Cards

### **Características:**
- ✅ Precios realistas en pesos argentinos
- ✅ Stock configurado
- ✅ Imágenes asignadas
- ✅ Descripciones completas
- ✅ Productos destacados marcados
- ✅ Categorías y marcas asociadas

---

## 💰 **Cupones de Descuento Activos**

| Código | Descuento | Mínimo | Descripción |
|--------|-----------|--------|-------------|
| `BIENVENIDO10` | 10% | $30.000 | Descuento de bienvenida |
| `RETRO5000` | $5.000 | $40.000 | Descuento fijo en retro |
| `ENVIOGRATIS` | Envío gratis | $80.000 | Sin costo de envío |

---

## 🚚 **Métodos de Envío Configurados**

| ID | Método | Costo | Tiempo |
|----|--------|-------|--------|
| `0` | Retiro en Local | Gratis | Inmediato |
| `2207` | Punto de Retiro | $3.500 | 1-3 días |
| `1425` | Envío a Domicilio | $1.425 | 3-7 días |

---

## 🔧 **Funcionalidades Implementadas**

### **Frontend:**
- ✅ Catálogo de productos dinámico
- ✅ Carrito de compras funcional
- ✅ Sistema de favoritos
- ✅ Búsqueda de productos
- ✅ Filtros por categoría/marca
- ✅ Reseñas y calificaciones
- ✅ Checkout completo
- ✅ Registro e inicio de sesión

### **Backend:**
- ✅ Gestión de usuarios
- ✅ Gestión de productos
- ✅ Gestión de pedidos
- ✅ Sistema de cupones
- ✅ Múltiples métodos de pago
- ✅ Cálculo automático de envíos
- ✅ Administración completa

---

## 📁 **Archivos Importantes**

### **Configuración:**
- `config/database.php` - Conexión BD (auto-detecta local/producción)
- `install_database.sql` - Script completo de instalación
- `install.php` - Instalador automático

### **Managers (Clases PHP):**
- `includes/product_manager.php` - Gestión de productos
- `includes/user_manager.php` - Gestión de usuarios  
- `includes/order_manager.php` - Gestión de pedidos

### **Páginas Principales:**
- `index.php` - Página principal (usa BD)
- `productos.php` - Catálogo completo
- `product-details.php` - Detalles de producto
- `carrito.php` - Carrito de compras
- `checkout.php` - Proceso de compra
- `login.php` / `register.php` - Autenticación

---

## 🌐 **Migración a Hostinger**

### **Para subir a producción:**

1. **Actualizar configuración:**
   - Editar `config/database.php`
   - Cambiar credenciales de producción en la sección `else`

2. **Exportar base de datos:**
   - En phpMyAdmin: Exportar → SQL
   - Descargar archivo `.sql`

3. **Subir archivos:**
   - Subir todos los archivos PHP a Hostinger
   - **No subir:** `install.php`, `install_database.sql`

4. **Importar en Hostinger:**
   - Acceder a phpMyAdmin de Hostinger
   - Crear base de datos nueva
   - Importar el archivo `.sql` exportado

5. **¡Listo!** El sitio funcionará idénticamente en producción.

---

## 🛡️ **Seguridad Implementada**

- ✅ Contraseñas encriptadas con `password_hash()`
- ✅ Consultas preparadas (PDO)
- ✅ Validación de datos de entrada
- ✅ Protección contra SQL injection
- ✅ Sesiones seguras
- ✅ Sanitización de salidas HTML

---

## 📞 **Soporte**

Si tienes problemas:

1. **Verificar XAMPP:** Apache y MySQL deben estar activos
2. **Comprobar archivos:** Todos los archivos deben estar en `/xampp/htdocs/multigamer360/`
3. **Revisar permisos:** Carpeta debe tener permisos de lectura/escritura
4. **Error de conexión:** Verificar credenciales en `config/database.php`

---

## 🎊 **¡Tu E-commerce está Listo!**

Después de la instalación tendrás:
- ✅ **E-commerce completamente funcional**
- ✅ **18 productos de ejemplo cargados**
- ✅ **Sistema de usuarios implementado** 
- ✅ **Carrito y checkout operativos**
- ✅ **Panel de administración**
- ✅ **Base de datos robusta y escalable**
- ✅ **Listo para migrar a producción**

**¡Disfruta tu nueva tienda de videojuegos retro! 🎮**