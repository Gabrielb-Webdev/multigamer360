# 🔑 Tu API Key de RAWG

## ✅ Estado Actual

**Tu API key está activa y configurada correctamente:**

```
API Key: 575f338491134d84bd86df30627a95fe
```

**Detalles de la cuenta:**
- Plan: Free
- Requests disponibles: 19,688
- Renovación: 1/3/2026
- Panel: https://rawg.io/@gabrielbg21/apikey

---

## 🔄 ¿Necesitas Obtener una Nueva API Key?

### Paso 1: Ir a RAWG
1. Visita: https://rawg.io/
2. Inicia sesión con tu cuenta (Gbustosgarcia01@gmail.com)
3. O crea una cuenta nueva si es necesario

### Paso 2: Obtener la API Key
1. Ve a: https://rawg.io/apidocs
2. Busca la sección **"Get API Key"**
3. Haz clic en el botón verde **"Get API Key"**
4. Copia tu API key (formato: 32 caracteres alfanuméricos)

### Paso 3: Actualizar en el Código

Solo si necesitas cambiar la API key, edita estos 4 archivos:

**1. admin/ajax/search_game_multi.php (línea 33)**
```php
define('RAWG_API_KEY', 'TU_NUEVA_API_KEY_AQUI');
```

**2. admin/ajax/search_game_rawg.php (línea 26)**
```php
'key' => 'TU_NUEVA_API_KEY_AQUI',
```

**3. admin/ajax/autocomplete_game_info.php (línea 21)**
```php
$apiKey = 'TU_NUEVA_API_KEY_AQUI';
```

**4. admin/ajax/get_game_platforms.php (línea 15)**
```php
$apiKey = 'TU_NUEVA_API_KEY_AQUI';
```

### Paso 4: Subir los Cambios
```bash
git add admin/ajax/*.php
git commit -m "Actualizar API key de RAWG"
git push origin main
```

O súbelos manualmente por FTP/File Manager de Hostinger.

### Paso 5: Verificar que Funciona
1. Hard refresh en navegador (Ctrl+Shift+R)
2. Ir a Admin → Nuevo Producto
2. Escribir "Kingdom Hearts" en el nombre
3. Hacer clic en "Auto-Rellenar Información del Juego"
4. Deberías ver resultados reales con imágenes

---

## 📊 Límites de RAWG API (Plan Free)

| Característica | Valor |
|----------------|-------|
| Base de datos | 500,000+ juegos |
| Requests/día | 20,000 |
| Velocidad | 5 requests/segundo |

**Nota:** Con el plan Free tienes suficientes requests para un uso normal.

---

## 💡 Consejos

### Monitorear Uso
- Revisa tu panel: https://rawg.io/@gabrielbg21/apikey
- Verás cuántos requests te quedan
- Se renuevan automáticamente cada mes

### Si Excedes el Límite
- El sistema usará automáticamente datos de respaldo (mock data)
- Los usuarios seguirán pudiendo usar la función
- Considera actualizar a plan pago si necesitas más requests

---

**🎮 Tu sistema está listo para usar RAWG API con 500,000+ juegos disponibles!**
---

## 🚀 Deployment del Mock Mejorado

Ya hice los cambios localmente. Ahora sube:

```batch
cd "e:\Users\gabri\Documentos\Brodev Lab\Clientes\multigamer360"
git add admin/ajax/search_game_multi.php
git commit -m "v2.10: Mock mejorado con 12 resultados mientras se arregla API key"
git push origin main
```

Luego:
1. Esperar 1-2 min o pull manual en Hostinger
2. Ctrl+Shift+R en navegador
3. Probar búsqueda

---

## ⚠️ Importante

El mock mejorado es **temporal**. Para obtener datos reales necesitas:

1. ✅ Nueva API key de RAWG (gratis, tarda 2 minutos)
2. ✅ Actualizarla en el código
3. ✅ Subir cambios

Con RAWG funcionando correctamente obtendrás:
- Datos reales de juegos
- Descripciones completas
- Imágenes de portada oficiales
- Géneros exactos
- Publishers reales
- Ratings de usuarios
- Fechas de lanzamiento reales
- Soporte para 500,000+ juegos

---

**¿Necesitas ayuda para actualizar la API key?** Dime cuando la tengas y actualizo el código.
