# MOCKUP VISUAL DEL FORMULARIO - PRODUCT EDIT

## 🎨 DISEÑO DE LA INTERFAZ

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  MultiGamer360 Admin Panel                                    🔔  👤 Admin  ⚙️    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  📦 Productos > Nuevo Producto                                                      │
├──────────────────────────────────────┬──────────────────────────────────────────────┤
│                                      │                                              │
│  ┌─── 📋 INFORMACIÓN BÁSICA ──────┐  │  ┌─── 💵 PRECIOS E INVENTARIO ──────────┐  │
│  │                                 │  │  │                                       │  │
│  │  Nombre del Producto *          │  │  │  Precio en Pesos (COP) *         ℹ️  │  │
│  │  ┌─────────────────────────┐   │  │  │  ┌────────────┐                      │  │
│  │  │ God of War Ragnarök     │   │  │  │  │ $  250000  │ COP                  │  │
│  │  └─────────────────────────┘   │  │  │  └────────────┘                      │  │
│  │                                 │  │  │                                       │  │
│  │  SKU              Estado *      │  │  │  Precio en Dólares (USD)         ℹ️  │  │
│  │  ┌──────────┐  ┌──────────┐    │  │  │  ┌────────────┐                      │  │
│  │  │GODOFWAR-│  │ ✓ Activo │    │  │  │  │ $   65.00  │ USD                  │  │
│  │  │  1234   │  └──────────┘    │  │  │  └────────────┘                      │  │
│  │  └──────────┘                   │  │  │                                       │  │
│  │  Auto-generado si vacío         │  │  │  Precio de Oferta (COP)    🏷️OFERTA │  │
│  │                                 │  │  │  ┌────────────┐                      │  │
│  │  Descripción Corta              │  │  │  │ $  200000  │ COP                  │  │
│  │  ┌─────────────────────────┐   │  │  │  └────────────┘                      │  │
│  │  │Épico juego de acción... │   │  │  │  ⚠️ Menor al precio regular          │  │
│  │  └─────────────────────────┘   │  │  │                                       │  │
│  │  Máximo 500 caracteres          │  │  │  ┌────────────────────────────┐     │  │
│  │                                 │  │  │  │ ✅ Descuento: 20%          │     │  │
│  │  Descripción *                  │  │  │  │    Ahorro: $50000 COP      │     │  │
│  │  ┌─────────────────────────┐   │  │  │  └────────────────────────────┘     │  │
│  │  │Sumérgete en la épica    │   │  │  │                                       │  │
│  │  │aventura de Kratos y     │   │  │  │  ─────────────────────────────       │  │
│  │  │Atreus mientras exploran │   │  │  │                                       │  │
│  │  │los Nueve Reinos...      │   │  │  │  Cantidad en Stock * 📦              │  │
│  │  └─────────────────────────┘   │  │  │  ┌──────┐                            │  │
│  │                                 │  │  │  │  15  │                            │  │
│  │  ☑️ Producto Destacado          │  │  │  └──────┘                            │  │
│  │  ☑️ Visible en la tienda        │  │  │  ✅ Stock disponible                 │  │
│  └─────────────────────────────────┘  │  └───────────────────────────────────────┘  │
│                                      │                                              │
│  ┌─── 🖼️  IMÁGENES DEL PRODUCTO ──┐  │  ┌─── 🏷️  CATEGORIZACIÓN ────────────┐  │
│  │                                 │  │  │                                       │  │
│  │  Subir Nuevas Imágenes          │  │  │  Categoría *                          │  │
│  │  ┌─────────────────────────┐   │  │  │  ┌────────────────────┐  ┌─────┐     │  │
│  │  │ 📁 Elegir archivos...   │   │  │  │  │ Juegos PS5         │  │  +  │     │  │
│  │  └─────────────────────────┘   │  │  │  └────────────────────┘  └─────┘     │  │
│  │  ℹ️ Múltiples imágenes JPG/PNG  │  │  │                                       │  │
│  │                                 │  │  │  Marca                                │  │
│  │  🗂️ Imágenes Actuales [3]       │  │  │  ┌────────────────────┐  ┌─────┐     │  │
│  │  ⬍⬍ Arrastra para reordenar     │  │  │  │ Sony               │  │  +  │     │  │
│  │                                 │  │  │  └────────────────────┘  └─────┘     │  │
│  │  ┌────┬────┬────┐              │  │  │                                       │  │
│  │  │⬍⬍ │⬍⬍ │⬍⬍ │              │  │  │  Consola / Plataforma                 │  │
│  │  │ 🌟 │    │    │              │  │  │  ┌────────────────────┐  ┌─────┐     │  │
│  │  │[1] │[2] │[3] │              │  │  │  │ PlayStation 5      │  │  +  │     │  │
│  │  │ ⭕ │ ⭕ │ ⭕ │ Principal    │  │  │  └────────────────────┘  └─────┘     │  │
│  │  │🗑️ │🗑️ │🗑️ │              │  │  │                                       │  │
│  │  └────┴────┴────┘              │  │  │  Géneros        [Agregar +]           │  │
│  │                                 │  │  │  ┌──────────────────────────────┐    │  │
│  └─────────────────────────────────┘  │  │  │ ☑️ Acción      ☑️ Aventura   │    │  │
│                                      │  │  │ ☑️ RPG         ☐ Estrategia  │    │  │
│  ┌─── 🔍 SEO - OPTIMIZACIÓN ──────┐  │  │  │ ☐ Deportes     ☐ Carreras    │    │  │
│  │                      [Auto⚡]   │  │  │  │ ☐ Disparos     ☐ Plataformas │    │  │
│  │                                 │  │  │  └──────────────────────────────┘    │  │
│  │  Meta Título         (57/60)    │  │  │  Múltiples géneros permitidos         │  │
│  │  ┌─────────────────────────┐   │  │  └───────────────────────────────────────┘  │
│  │  │God of War Ragnarök |    │   │  │                                              │
│  │  │MultiGamer360            │   │  │  ┌─── ⚡ ACCIONES ────────────────────┐  │
│  │  └─────────────────────────┘   │  │  │                                       │  │
│  │  💡 50-60 caracteres Google     │  │  │  ┌─────────────────────────────┐    │  │
│  │                                 │  │  │  │  💾 CREAR PRODUCTO          │    │  │
│  │  Meta Descripción   (158/160)   │  │  │  └─────────────────────────────┘    │  │
│  │  ┌─────────────────────────┐   │  │  │                                       │  │
│  │  │Épica aventura nórdica   │   │  │  │  ┌─────────────────────────────┐    │  │
│  │  │con Kratos y Atreus en   │   │  │  │  │  ❌ Cancelar                 │    │  │
│  │  │los Nueve Reinos. Compra │   │  │  │  └─────────────────────────────┘    │  │
│  │  │ahora con descuento      │   │  │  │                                       │  │
│  │  └─────────────────────────┘   │  │  │  ┌─────────────────────────────┐    │  │
│  │  💡 150-160 caracteres Google   │  │  │  │  👁️  Ver en Sitio (edición)│    │  │
│  │                                 │  │  │  └─────────────────────────────┘    │  │
│  │  ┌────────────────────────────┐│  │  └───────────────────────────────────────┘  │
│  │  │ 👁️ Vista Previa en Google: ││  │                                              │
│  │  │                            ││  │                                              │
│  │  │ God of War Ragnarök |...   ││  │                                              │
│  │  │ www.multigamer360.com › ...││  │                                              │
│  │  │ Épica aventura nórdica...  ││  │                                              │
│  │  └────────────────────────────┘│  │                                              │
│  └─────────────────────────────────┘  │                                              │
└──────────────────────────────────────┴──────────────────────────────────────────────┘
```

---

## 🎭 MODALES (VENTANAS EMERGENTES)

### Modal: Nueva Categoría
```
┌──────────────────────────────────────┐
│  📁 Nueva Categoría              ✖️  │
├──────────────────────────────────────┤
│                                      │
│  Nombre *                            │
│  ┌────────────────────────────────┐ │
│  │ Accesorios Gaming              │ │
│  └────────────────────────────────┘ │
│                                      │
│  Descripción                         │
│  ┌────────────────────────────────┐ │
│  │ Teclados, ratones, auriculares │ │
│  │ y más accesorios para gamers   │ │
│  └────────────────────────────────┘ │
│                                      │
├──────────────────────────────────────┤
│  [Cancelar]         [💾 Guardar]    │
└──────────────────────────────────────┘
```

### Modal: Nueva Marca
```
┌──────────────────────────────────────┐
│  🏷️  Nueva Marca                 ✖️  │
├──────────────────────────────────────┤
│                                      │
│  Nombre *                            │
│  ┌────────────────────────────────┐ │
│  │ Razer                          │ │
│  └────────────────────────────────┘ │
│                                      │
├──────────────────────────────────────┤
│  [Cancelar]         [💾 Guardar]    │
└──────────────────────────────────────┘
```

### Modal: Nueva Consola
```
┌──────────────────────────────────────┐
│  🎮 Nueva Consola                ✖️  │
├──────────────────────────────────────┤
│                                      │
│  Nombre *                            │
│  ┌────────────────────────────────┐ │
│  │ PlayStation 5 Pro              │ │
│  └────────────────────────────────┘ │
│                                      │
│  Fabricante                          │
│  ┌────────────────────────────────┐ │
│  │ Sony Interactive Entertainment │ │
│  └────────────────────────────────┘ │
│                                      │
├──────────────────────────────────────┤
│  [Cancelar]         [💾 Guardar]    │
└──────────────────────────────────────┘
```

### Modal: Nuevo Género
```
┌──────────────────────────────────────┐
│  📋 Nuevo Género                 ✖️  │
├──────────────────────────────────────┤
│                                      │
│  Nombre *                            │
│  ┌────────────────────────────────┐ │
│  │ Battle Royale                  │ │
│  └────────────────────────────────┘ │
│                                      │
│  Descripción                         │
│  ┌────────────────────────────────┐ │
│  │ Juegos multijugador masivo con │ │
│  │ último superviviente           │ │
│  └────────────────────────────────┘ │
│                                      │
├──────────────────────────────────────┤
│  [Cancelar]         [💾 Guardar]    │
└──────────────────────────────────────┘
```

---

## 🎬 INTERACCIONES ANIMADAS

### 1. Drag & Drop de Imágenes
```
Estado Inicial:
┌─────┐  ┌─────┐  ┌─────┐
│  1  │  │  2  │  │  3  │
│ 🌟  │  │     │  │     │
└─────┘  └─────┘  └─────┘

Arrastrando la imagen 3:
┌─────┐  ┌─────┐  ┌─────┐
│  1  │  │ ... │  │ 👆  │ ← cursor agarrando
│ 🌟  │  │     │  │ [3] │
└─────┘  └─────┘  └─────┘

Soltando entre 1 y 2:
┌─────┐  ┌─────┐  ┌─────┐
│  3  │  │  1  │  │  2  │
│     │  │ 🌟  │  │     │
└─────┘  └─────┘  └─────┘
          ✅ Orden actualizado automáticamente
```

### 2. Auto-generación de SEO
```
ANTES de hacer clic en [Auto⚡]:

Nombre: "God of War Ragnarök"
Meta Título: [          ]
Meta Desc:   [          ]

DESPUÉS de hacer clic en [Auto⚡]:

Nombre: "God of War Ragnarök"
Meta Título: [God of War Ragnarök | MultiGamer360    ] (57/60) ✅
Meta Desc:   [Épico juego de acción y aventura...    ] (158/160) ✅

Vista Previa Google:
┌────────────────────────────────────────┐
│ God of War Ragnarök | MultiGamer360    │ ← Azul
│ www.multigamer360.com › producto › ... │ ← Verde
│ Épico juego de acción y aventura...    │ ← Gris
└────────────────────────────────────────┘
```

### 3. Calculadora de Descuento
```
Usuario escribe:

Precio Regular:  $ 250000 COP
Precio Oferta:   $ 200000 COP

Sistema calcula automáticamente:
┌────────────────────────────┐
│ ✅ Descuento: 20%          │
│    Ahorro: $50000 COP      │
└────────────────────────────┘

Si precio oferta > regular:
┌────────────────────────────┐
│ ⚠️ El precio de oferta debe│
│   ser menor al regular     │
└────────────────────────────┘
```

### 4. Alerta de Stock
```
Stock = 0:
┌─────┐
│  0  │ 🔴 Sin stock
└─────┘

Stock = 3:
┌─────┐
│  3  │ 🟡 Stock bajo
└─────┘

Stock = 15:
┌─────┐
│ 15  │ ✅ Stock disponible
└─────┘
```

---

## 📱 VISTA MÓVIL (Responsive)

### En pantallas pequeñas (<768px):
```
┌──────────────────────────┐
│  📦 Nuevo Producto       │
├──────────────────────────┤
│  📋 INFORMACIÓN BÁSICA   │
│  [Nombre...]             │
│  [SKU...] [Estado▼]      │
│  [Descripción corta...]  │
│  [Descripción...]        │
│  ☑️ Destacado             │
├──────────────────────────┤
│  🖼️  IMÁGENES             │
│  [📁 Elegir archivos]    │
│  [Vista previa...]       │
├──────────────────────────┤
│  🔍 SEO          [Auto⚡]│
│  [Meta título...]        │
│  [Meta desc...]          │
├──────────────────────────┤
│  💵 PRECIOS              │
│  [$ Pesos COP]           │
│  [$ Dólares USD]         │
│  [$ Oferta]              │
│  [📦 Stock]              │
├──────────────────────────┤
│  🏷️  CATEGORIZACIÓN      │
│  [Categoría ▼] [+]       │
│  [Marca ▼] [+]           │
│  [Consola ▼] [+]         │
│  [☑️ Géneros...]          │
├──────────────────────────┤
│  [💾 CREAR PRODUCTO]     │
│  [❌ Cancelar]            │
└──────────────────────────┘
```

---

## 🎨 PALETA DE COLORES

```
Colores principales:

🟢 Verde (#28a745)   - Éxito, disponible
🔵 Azul (#0d6efd)    - Acción primaria
🟡 Amarillo (#ffc107) - Advertencia
🔴 Rojo (#dc3545)    - Peligro, eliminar
⚫ Gris (#6c757d)    - Secundario

Badges:
┌─────────┬─────────┬─────────┬─────────┐
│ 🟢 Nuevo│ 🌟 Princ│ 🏷️ Oferta│ ⚫ Inact │
└─────────┴─────────┴─────────┴─────────┘
```

---

## 🖱️ ESTADOS DE BOTONES

### Botón Principal (Crear/Actualizar)
```
Normal:     [💾 CREAR PRODUCTO]    (azul)
Hover:      [💾 CREAR PRODUCTO]    (azul oscuro)
Disabled:   [💾 CREAR PRODUCTO]    (gris, cursor no permitido)
Loading:    [⏳ Guardando...]      (azul, con spinner)
```

### Botones Secundarios
```
Cancelar:   [❌ Cancelar]          (gris)
Ver en web: [👁️  Ver en Sitio]    (azul claro)
Eliminar:   [🗑️ Eliminar]          (rojo)
Agregar:    [+ ]                  (verde, pequeño)
```

---

## 🔔 NOTIFICACIONES

### Éxito
```
┌────────────────────────────────────┐
│ ✅ Producto creado correctamente   │
└────────────────────────────────────┘
```

### Error
```
┌────────────────────────────────────┐
│ ❌ Error: El SKU ya existe         │
└────────────────────────────────────┘
```

### Advertencia
```
┌────────────────────────────────────┐
│ ⚠️ Precio de oferta debe ser menor │
└────────────────────────────────────┘
```

### Información
```
┌────────────────────────────────────┐
│ ℹ️ Campos SEO generados            │
└────────────────────────────────────┘
```

---

## 🎯 TOOLTIPS

```
Hover sobre ℹ️:
┌─────────────────────────┐
│ Precio en pesos         │
│ colombianos para el     │
│ mercado local           │
└─────────────────────────┘
```

---

**Nota**: Este es un mockup en texto ASCII para visualización rápida.  
El diseño real utiliza Bootstrap 5 y es completamente responsive.
