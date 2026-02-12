# 🚀 Comandos para subir cambios a Hostinger via Git

## Si tienes Git configurado con Hostinger

### Verificar estado actual
```bash
cd f:\xampp\htdocs\multigamer360
git status
```

### Subir solo product-details.php
```bash
git add product-details.php
git commit -m "Fix: Eliminar script Bootstrap que causaba error en línea 331"
git push origin main
```

### Subir product-details.php y productos.php (si también lo modificaste)
```bash
git add product-details.php productos.php
git commit -m "Fix: Bootstrap error y mejora visual de filtro de precios"
git push origin main
```

### Subir todos los cambios
```bash
git add .
git commit -m "Fix: Correcciones múltiples - Bootstrap y estilos"
git push origin main
```

## Si NO tienes Git configurado

Usa el File Manager de Hostinger (ver INSTRUCCIONES_SUBIR_HOSTINGER.md)

## Verificar que los cambios llegaron al servidor

Después de hacer push, espera 1-2 minutos y verifica:

1. Ve a: https://teal-fish-507993.hostingersite.com/product-details.php?id=10
2. Presiona Ctrl + U (ver código fuente)
3. Busca (Ctrl + F): "bootstrap"
4. NO debería aparecer ninguna referencia en scripts

## Limpiar caché después del deploy

```bash
# Limpiar caché local de Git (opcional)
git gc

# En Hostinger, limpia la caché desde el panel
# No hay comando para esto, debe hacerse desde el panel web
```

## Troubleshooting

### Si el push falla:
```bash
# Actualizar desde el servidor primero
git pull origin main

# Resolver conflictos si los hay
# Luego intentar push de nuevo
git push origin main
```

### Si necesitas forzar el push (⚠️ usar con cuidado):
```bash
git push origin main --force
```

### Ver historial de commits:
```bash
git log --oneline -5
```

### Ver diferencias antes de commitear:
```bash
git diff product-details.php
```

## Rollback (si algo sale mal)

### Volver al commit anterior:
```bash
# Ver commits
git log --oneline

# Volver al commit anterior (sustituye XXXXX por el hash del commit)
git revert XXXXX

# O deshacer el último commit (mantiene cambios locales)
git reset --soft HEAD~1
```

## Notas importantes

- ✅ Siempre verifica el estado antes de commitear: `git status`
- ✅ Escribe mensajes de commit descriptivos
- ✅ Haz pull antes de push si trabajas en equipo
- ⚠️ No uses --force a menos que sea absolutamente necesario
- ⚠️ Espera 1-2 minutos después del push para que Hostinger actualice

## Configuración inicial de Git (si aún no lo hiciste)

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tu@email.com"

# Inicializar repo (solo si no existe)
cd f:\xampp\htdocs\multigamer360
git init
git remote add origin URL_DE_TU_REPO_EN_GITHUB_O_GITLAB
```

## Conectar con Hostinger via SSH (avanzado)

Si Hostinger te dio acceso SSH:

```bash
# Conectar
ssh usuario@teal-fish-507993.hostingersite.com

# Navegar al directorio
cd public_html

# Ver archivos
ls -la

# Salir
exit
```

---

**Si necesitas ayuda con Git, avísame y te guío paso a paso.**
