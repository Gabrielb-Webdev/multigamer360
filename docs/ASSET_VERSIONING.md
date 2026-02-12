# Sistema de Versionado de Assets (Cache Busting)

## 📋 ¿Qué es esto?

El sistema de versionado de assets (cache busting) permite que los cambios en archivos CSS y JS se reflejen inmediatamente en el navegador del usuario, evitando problemas de caché.

## 🔧 Cómo Funciona

Cada archivo CSS y JS tiene un parámetro de versión en su URL:
```html
<link rel="stylesheet" href="assets/css/style.css?v=0.2">
<script src="assets/js/main.js?v=0.1"></script>
```

Cuando cambias la versión, el navegador trata el archivo como nuevo y lo descarga nuevamente.

## 📝 Cómo Actualizar Versiones

### Método 1: Manual (Actual)

1. **Cuando hagas cambios en un archivo CSS:**
   - Abre `includes/header.php`
   - Encuentra la línea del archivo modificado
   - Incrementa la versión (ej: `?v=0.1` → `?v=0.2`)

2. **Cuando hagas cambios en un archivo JS:**
   - Abre `includes/footer.php`
   - Encuentra la línea del archivo modificado
   - Incrementa la versión (ej: `?v=0.1` → `?v=0.2`)

3. **Para archivos específicos de página:**
   - Abre el archivo de la página (ej: `productos.php`)
   - Busca la línea con el CSS específico
   - Incrementa la versión

### Método 2: Centralizado (Futuro)

Existe un archivo `config/asset_versions.php` que centraliza todas las versiones.
Para usarlo:

1. Incluye el archivo en `header.php`:
   ```php
   require_once 'config/asset_versions.php';
   ```

2. Usa las funciones helper:
   ```php
   <?php echo css('assets/css/style.css'); ?>
   <?php echo js('assets/js/main.js'); ?>
   ```

3. Para actualizar versiones, solo edita `config/asset_versions.php`

## 📊 Registro de Versiones Actuales

### CSS
| Archivo | Versión | Última Modificación |
|---------|---------|---------------------|
| style.css | 0.2 | Dropdown de consolas alineado a la derecha |
| console-selector.css | 0.1 | Versión inicial |
| contact-modern.css | 0.1 | Versión inicial |
| cart-button-modern.css | 0.1 | Versión inicial |

### JavaScript
| Archivo | Versión | Última Modificación |
|---------|---------|---------------------|
| wishlist-system.js | 0.1 | Versión inicial |
| modern-cart-button.js | 0.1 | Versión inicial |
| main.js | 0.1 | Versión inicial |

## 🎯 Cuándo Incrementar Versiones

Incrementa la versión cuando:
- ✅ Cambies estilos CSS
- ✅ Modifiques funcionalidad JavaScript
- ✅ Agregues nuevas funciones
- ✅ Corrijas bugs visuales o de comportamiento

**NO** es necesario incrementar versión cuando:
- ❌ Solo cambies contenido HTML
- ❌ Modifiques PHP del backend
- ❌ Actualices la base de datos

## 🚀 Convención de Versiones

Usamos versionado semántico simplificado:

- **0.1** - Versión inicial
- **0.2** - Cambio menor (fix pequeño, ajuste de estilo)
- **0.3, 0.4...** - Más cambios menores
- **1.0** - Primera versión estable/completa
- **1.1, 1.2...** - Mejoras después de v1.0
- **2.0** - Cambio mayor (refactorización completa)

## 💡 Ejemplo de Flujo de Trabajo

1. Editas `style.css` para cambiar el color de un botón
2. Guardas el archivo
3. Abres `includes/header.php`
4. Cambias `style.css?v=0.2` a `style.css?v=0.3`
5. Haces commit y push
6. Hostinger hace el deploy automático
7. Los usuarios ven los cambios inmediatamente (sin Ctrl+F5)

## 🔄 Sincronización con Git

Cuando hagas cambios:

```bash
# Método recomendado
git add .
git commit -m "Style: Cambio en dropdown - Versión CSS 0.2 → 0.3"
git push origin main
```

Incluir el cambio de versión en el mensaje de commit ayuda a rastrear cambios.

## ⚠️ Importante

- Cada vez que cambies un archivo CSS o JS, **debes** incrementar su versión
- Si olvidas cambiar la versión, los usuarios pueden ver la versión antigua cacheada
- En producción, los cambios no se reflejarán hasta que cambies la versión

## 📞 Soporte

Si tienes dudas sobre el sistema de versionado, consulta este archivo o revisa los comentarios en:
- `config/asset_versions.php`
- `includes/header.php`
- `includes/footer.php`
