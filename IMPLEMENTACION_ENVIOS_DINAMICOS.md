# 🚚 SISTEMA DE ENVÍOS DINÁMICO - ARGENTINA

## ✅ Archivos Creados

### 1. Base de Datos
- **add_shipping_providers.sql** - Tabla de proveedores y cache de cotizaciones

### 2. Backend PHP
- **includes/shipping_calculator.php** - Calculador con integración de APIs
- **ajax/calculate-shipping.php** - Endpoint para calcular envíos en tiempo real
- **ajax/set-shipping.php** - Endpoint actualizado para guardar envío seleccionado

### 3. Frontend JavaScript
- **assets/js/shipping-calculator.js** - Lógica del cliente para calcular envíos

---

## 📋 PASOS DE IMPLEMENTACIÓN

### PASO 1: Ejecutar SQL en phpMyAdmin ✅

```sql
-- Ejecutar: add_shipping_providers.sql
```

Esto creará:
- Tabla `shipping_providers` (4 proveedores)
- Tabla `shipping_quotes_cache` (cache de 24hs)

---

### PASO 2: Configurar Datos del Local 🔴

Editar [includes/shipping_calculator.php](includes/shipping_calculator.php) líneas 14-15:

```php
$this->originZip = '1426'; // ← CAMBIAR por tu CP real
$this->originAddress = 'Av. Corrientes 1234, CABA'; // ← CAMBIAR por tu dirección
```

---

### PASO 3: Actualizar carrito.php 🔴

#### A) Agregar JavaScript al final del archivo:

```html
<!-- ANTES de cerrar </body> -->
<script src="assets/js/shipping-calculator.js"></script>
```

#### B) Modificar sección de código postal (línea ~382):

**BUSCAR:**
```html
<!-- Código Postal -->
<div class="mb-4">
    <h6 class="text-info mb-3">INGRESA TU CÓDIGO POSTAL</h6>
```

**REEMPLAZAR CON:**
```html
<!-- Código Postal -->
<div class="mb-4">
    <h6 class="text-info mb-3">INGRESA TU CÓDIGO POSTAL</h6>
    
    <!-- Formulario de ingreso de CP -->
    <div id="postalCodeForm">
        <div class="input-group mb-3">
            <input type="text" 
                   class="form-control bg-dark text-white border-secondary" 
                   id="postalCodeInput" 
                   placeholder="Ej: 1425" 
                   maxlength="4"
                   pattern="\d{4}">
            <button class="btn btn-danger" 
                    type="button" 
                    id="calculateShipping">
                <i class="fas fa-calculator me-2"></i>CALCULAR ENVÍO
            </button>
        </div>
        <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Ingresá tu código postal para ver las opciones de envío disponibles
        </div>
    </div>
    
    <!-- Confirmación de CP (después de calcular) -->
    <div id="postalConfirmation" style="display: none;" class="alert alert-success">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-map-marker-alt me-2"></i>
                Entregas para el CP: <strong><span id="confirmedCP"></span></strong>
            </div>
            <button class="btn btn-sm btn-outline-success" id="changePostal">
                <i class="fas fa-edit"></i> CAMBIAR CP
            </button>
        </div>
    </div>
</div>
```

#### C) Modificar sección de opciones de envío (línea ~427):

**BUSCAR:**
```html
<!-- Opciones de envío (inicialmente ocultas) -->
<div id="shippingOptions" style="display: none;" class="mb-4">
```

**REEMPLAZAR TODO ese bloque CON:**
```html
<!-- Opciones de envío DINÁMICAS (inicialmente ocultas) -->
<div id="shippingOptions" style="display: none;" class="mb-4">
    <h6 class="text-white mb-3">ENVÍO A DOMICILIO</h6>
    
    <div id="shippingOptionsHTML">
        <!-- Las opciones se cargarán dinámicamente aquí -->
    </div>
</div>
```

#### D) Asegurar que el total esté oculto inicialmente (línea ~510):

```html
<!-- Total (oculto hasta que se calcule el envío) -->
<div id="totalSection" style="display: none;" class="mt-4 pt-3 border-top border-secondary">
```

---

### PASO 4: Probar el Sistema ✅

1. Ir al carrito con productos
2. Ingresar código postal (ej: 1425 para CABA, 5000 para Córdoba)
3. Click en "CALCULAR ENVÍO"
4. Verás opciones dinámicas:
   - **Moova Mensajería** (solo CABA): ~$3.500
   - **Correo Argentino**: ~$3.500-$5.000
   - **OCA**: ~$5.000-$7.000
   - **Andreani**: ~$6.000-$9.000

---

## 🔌 INTEGRACIÓN CON APIs REALES (OPCIONAL)

Actualmente el sistema usa **estimaciones inteligentes** basadas en zonas. Para conectar APIs reales:

### Andreani (Recomendado)

1. Registrarse en: https://developers.andreani.com
2. Obtener API Key (x-authorization-token)
3. Actualizar en BD:

```sql
UPDATE shipping_providers 
SET api_key = 'TU_API_KEY_ANDREANI',
    config_json = '{"peso_default_kg": 1.0, "contrato": "TU_NUMERO_CONTRATO"}'
WHERE provider_key = 'andreani';
```

### OCA

1. Contactar: comercio@oca.com.ar
2. Solicitar credenciales API REST
3. Actualizar config_json con CUIT y operativa

### Correo Argentino

1. Registrar en: https://www.correoargentino.com.ar/empresas
2. Solicitar acceso API
3. Actualizar credenciales

### Moova (Solo zona metropolitana)

1. Registrar en: https://moova.io
2. Obtener API key
3. Actualizar:

```sql
UPDATE shipping_providers 
SET api_key = 'TU_MOOVA_API_KEY'
WHERE provider_key = 'moova';
```

---

## 📊 Cómo Funciona

### Flujo del Sistema:

```
1. Usuario ingresa CP en carrito
   ↓
2. JavaScript llama a ajax/calculate-shipping.php
   ↓
3. PHP carga ShippingCalculator
   ↓
4. Consulta cache (24hs)
   ↓
5. Si no hay cache → calcula según zona
   ↓
6. Retorna 4 opciones (Moova/Correo/OCA/Andreani)
   ↓
7. JavaScript muestra opciones ordenadas por precio
   ↓
8. Usuario selecciona
   ↓
9. Se guarda en sesión
   ↓
10. Se actualiza total del carrito
```

### Estimación de Precios:

El sistema calcula precios basándose en:
- **distancia** (estimada por rangos de CP)
- **peso** del carrito
- **valor declarado**
- **zona** (CABA / GBA / Interior / Patagonia)

#### Rangos de CP Argentina:
- 1000-1499: CABA
- 1600-1900: GBA
- 2000-2999: Buenos Aires Interior
- 3000-3999: Santa Fe, Entre Ríos
- 4000-4999: Norte (Salta, Jujuy, Tucumán)
- 5000-5999: Centro (Córdoba, La Rioja)
- 8000-9999: Patagonia

---

## 🎯 Ventajas del Sistema

✅ **Sin costos fijos** - Solo usas APIs cuando necesitas  
✅ **Cache inteligente** - No consulta todo el tiempo  
✅ **Fallback a estimaciones** - Funciona aunque las APIs fallen  
✅ **Múltiples opciones** - Cliente elige la que prefiere  
✅ **Precios reales** - Basados en distancia y peso  
✅ **Actualización automática** - Cambios de tarifa sin tocar código  

---

## 🔧 Mantenimiento

### Actualizar precios base:

Editar [includes/shipping_calculator.php](includes/shipping_calculator.php):

```php
// Línea ~150 - OCA
$basePrice = 5000; // ← Ajustar
$pricePerKg = 1000; // ← Ajustar

// Línea ~175 - Correo Argentino
$basePrice = 3500; // ← Ajustar
$pricePerKg = 800; // ← Ajustar
```

### Limpiar cache:

```sql
DELETE FROM shipping_quotes_cache WHERE valid_until < NOW();
```

### Desactivar proveedor:

```sql
UPDATE shipping_providers SET is_active = 0 WHERE provider_key = 'moova';
```

---

## 📝 TODO List

- [ ] Ejecutar add_shipping_providers.sql
- [ ] Actualizar CP de origen en shipping_calculator.php
- [ ] Modificar carrito.php (3 secciones)
- [ ] Agregar shipping-calculator.js al HTML
- [ ] Probar con diferentes CPs
- [ ] (Opcional) Integrar APIs reales

---

¡Sistema listo para calcular envíos dinámicos en tiempo real! 🚀📦
