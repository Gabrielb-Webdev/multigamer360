<?php
// Solo incluir functions.php si existe y no se ha incluido antes
if (file_exists('includes/functions.php') && !function_exists('isAdmin')) {
    require_once 'includes/functions.php';
}

// Incluir sistema de autenticación si no se ha incluido antes
if (!function_exists('isLoggedIn')) {
    require_once 'includes/auth.php';
}

// Obtener usuario actual
$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MultiGamer360 - Tu tienda de videojuegos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=0.3">
    <!-- CSS específico para la página de contacto moderna -->
    <link rel="stylesheet" href="assets/css/contact-modern.css?v=0.1">
    <!-- CSS para botón moderno de carrito -->
    <link rel="stylesheet" href="assets/css/cart-button-modern.css?v=0.1">
    
    <!-- Precarga inmediata del estado del carrito -->
    <script>
        // Precargar estado del carrito lo más pronto posible
        (function() {
            const preloadCart = () => {
                fetch('ajax/get-cart-count.php', {
                    credentials: 'same-origin',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Guardar en variable global para acceso inmediato
                    window.cartData = data;
                    // Intentar actualizar el display si ya existe
                    const cartDisplay = document.getElementById('cart-display');
                    if (cartDisplay && data.cart_total !== undefined) {
                        const formattedTotal = data.cart_total > 0 ? `$${parseFloat(data.cart_total).toFixed(2)}` : '$0';
                        cartDisplay.textContent = formattedTotal;
                        console.log('Header preload - Cart updated:', formattedTotal);
                    }
                })
                .catch(error => console.log('Preload cart error:', error));
            };
            
            // Ejecutar inmediatamente
            preloadCart();
        })();
    </script>
    
    <!-- Script para detectar horario local del usuario -->
    <script>
        // Detectar horario local del usuario y enviarlo al servidor
        document.addEventListener('DOMContentLoaded', function() {
            const userHour = new Date().getHours();
            
            // Enviar la hora local al servidor vía AJAX para guardarla en la sesión
            fetch('ajax/set-user-timezone.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_hour: userHour,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                })
            }).catch(error => {
                console.log('No se pudo sincronizar el horario:', error);
            });
        });
        
        // Sincronización instantánea del carrito - ejecutar lo antes posible
        function syncCartInstantly() {
            fetch('ajax/get-cart-count.php', {
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                window.cartData = data; // Guardar globalmente
                const cartDisplay = document.getElementById('cart-display');
                if (cartDisplay && data.cart_total !== undefined) {
                    const formattedTotal = data.cart_total > 0 ? `$${parseFloat(data.cart_total).toFixed(2)}` : '$0';
                    cartDisplay.textContent = formattedTotal;
                    console.log('Header instant sync - Updated to:', formattedTotal);
                }
            })
            .catch(error => console.log('Cart sync error:', error));
        }
        
        // Sincronización de wishlist count
        function syncWishlistCount() {
            fetch('ajax/get-wishlist-count.php', {
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                const wishlistBadge = document.querySelector('.wishlist-count');
                if (wishlistBadge && data.count !== undefined) {
                    if (data.count > 0) {
                        wishlistBadge.textContent = data.count;
                        wishlistBadge.style.display = 'inline';
                    } else {
                        wishlistBadge.style.display = 'none';
                    }
                    console.log('Wishlist count updated to:', data.count);
                }
            })
            .catch(error => console.log('Wishlist sync error:', error));
        }
        
        // Observer para detectar cuando aparece el cart-display y actualizarlo inmediatamente
        function initCartObserver() {
            if (document.body) {
                const cartDisplayObserver = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1) { // Element node
                                const cartDisplay = node.id === 'cart-display' ? node : node.querySelector('#cart-display');
                                if (cartDisplay && window.cartData) {
                                    const formattedTotal = window.cartData.cart_total > 0 ? 
                                        `$${parseFloat(window.cartData.cart_total).toFixed(2)}` : '$0';
                                    cartDisplay.textContent = formattedTotal;
                                    console.log('Observer - Cart display updated:', formattedTotal);
                                }
                            }
                        });
                    });
                });
                
                // Iniciar observer solo si document.body existe
                cartDisplayObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                console.log('Cart observer initialized');
            } else {
                // Reintentar cuando document.body esté disponible
                setTimeout(initCartObserver, 100);
            }
        }
        
        // Ejecutar sincronización tan pronto como sea posible
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                syncCartInstantly();
                syncWishlistCount();
                initCartObserver();
            });
        } else {
            syncCartInstantly();
            syncWishlistCount();
            initCartObserver();
        }
    </script>
    
</head>
<body>
    <!-- Header Banner -->
    <header class="main-header">
        <div class="container-fluid p-0">
            <div class="header-banner position-relative">
                <img src="assets/images/Pre-header.png" alt="MultiGamer360 Header" class="w-100">
                <div class="header-overlay">
                    <div class="container-fluid px-4 h-100">
                        <div class="d-flex justify-content-between align-items-center h-100">
                            <div class="header-logo">
                                <a href="index.php">
                                    <img src="assets/images/logo.png" alt="MultiGamer360 Logo" class="header-logo-img">
                                </a>
                            </div>
                            <div class="header-actions d-flex align-items-center">
                                <div class="auth-buttons me-4 d-none d-lg-block">
                                    <?php if ($isLoggedIn): ?>
                                        <div class="dropdown">
                                            <button class="btn header-btn dropdown-toggle" type="button" id="userMenuDropdown" aria-expanded="false">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($currentUser['first_name']) ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu">
                                                <li><h6 class="dropdown-header"><?= getGreeting() ?></h6></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Mi Perfil</a></li>
                                                <li><a class="dropdown-item" href="order_history.php"><i class="fas fa-history me-2"></i>Mis Pedidos</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <a href="register.php" class="btn header-btn me-2"><i class="fas fa-user-plus"></i> CREAR CUENTA</a>
                                        <a href="login.php" class="btn header-btn me-4"><i class="fas fa-sign-in-alt"></i> INICIAR SESIÓN</a>
                                    <?php endif; ?>
                                </div>
                                <div class="wishlist-button me-2">
                                    <a href="wishlist.php" class="btn header-btn position-relative">
                                        <i class="fas fa-heart"></i> WISHLIST
                                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle wishlist-count" style="display: none;">0</span>
                                    </a>
                                </div>
                                <div class="cart-button">
                                    <a href="carrito.php" class="btn header-btn position-relative">
                                        <i class="fas fa-shopping-cart"></i> <span id="cart-display">$0</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark main-nav">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <!-- Botón de cierre real -->
                <button type="button" class="mobile-close-btn d-lg-none" aria-label="Cerrar menú">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="row w-100 align-items-center">
                    <!-- Navigation Links (65%) -->
                    <div class="col-lg-8 col-md-6">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">INICIO</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navVideojuegos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    VIDEOJUEGOS
                                </a>
                                <div class="dropdown-menu dropdown-menu-large">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <h6 class="dropdown-header">Nintendo</h6>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=nes">NES</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=snes">SNES</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=n64">N64</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=gamecube">Gamecube</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=wii">Wii</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=wiiu">Wii U</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=switch">Switch</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=gameboy">Gameboy/Gameboy Color</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=gba">Gameboy advance</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=nds">NDS</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=3ds">3DS</a>
                                                <a class="dropdown-item" href="productos.php?brand=nintendo&console=famicom">Famicom</a>
                                            </div>
                                            <div class="col-md-3">
                                                <h6 class="dropdown-header">SEGA</h6>
                                                <a class="dropdown-item" href="productos.php?brand=sega&console=gamegear">Game Gear</a>
                                                <a class="dropdown-item" href="productos.php?brand=sega&console=genesis">Genesis</a>
                                                <a class="dropdown-item" href="productos.php?brand=sega&console=saturn">Saturn</a>
                                                <a class="dropdown-item" href="productos.php?brand=sega&console=dreamcast">Dreamcast</a>
                                                <a class="dropdown-item" href="productos.php?brand=sega&console=mastersystem">Master System</a>
                                                <a class="dropdown-item" href="productos.php?brand=sega&console=segacd">Sega CD</a>
                                                <br>
                                                <h6 class="dropdown-header">Microsoft</h6>
                                                <a class="dropdown-item" href="productos.php?brand=microsoft&console=xbox">Xbox</a>
                                                <a class="dropdown-item" href="productos.php?brand=microsoft&console=xbox360">Xbox 360</a>
                                                <a class="dropdown-item" href="productos.php?brand=microsoft&console=xboxone">Xbox One</a>
                                            </div>
                                            <div class="col-md-3">
                                                <h6 class="dropdown-header">SONY</h6>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=ps">PS</a>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=ps2">PS2</a>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=ps3">PS3</a>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=ps4">PS4</a>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=ps5">PS5</a>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=psp">PSP</a>
                                                <a class="dropdown-item" href="productos.php?brand=sony&console=psvita">PSVita</a>
                                                <br>
                                                <h6 class="dropdown-header">Otros</h6>
                                                <a class="dropdown-item" href="productos.php?category=colecovision">Colecovision</a>
                                                <a class="dropdown-item" href="productos.php?category=manuales">Manuales</a>
                                                <a class="dropdown-item" href="productos.php?category=varios">Varios</a>
                                                <a class="dropdown-item" href="productos.php?category=mastersystem">Master System</a>
                                                <a class="dropdown-item" href="productos.php?category=pc">PC</a>
                                            </div>
                                            <div class="col-md-3">
                                                <h6 class="dropdown-header">Especiales</h6>
                                                <a class="dropdown-item" href="productos.php?category=descuentos">Descuentos exclusivos</a>
                                                <a class="dropdown-item" href="productos.php?category=cartas">Cartas Pokemon</a>
                                                <a class="dropdown-item" href="productos.php?category=consolas">Consolas</a>
                                                <br>
                                                <h6 class="dropdown-header">Retro</h6>
                                                <a class="dropdown-item" href="productos.php?category=homecomputer">Home Computer</a>
                                                <a class="dropdown-item" href="productos.php?category=repuestos">Repuestos</a>
                                                <a class="dropdown-item" href="productos.php?category=atari">Atari</a>
                                                <a class="dropdown-item" href="productos.php?category=3do">3DO</a>
                                                <a class="dropdown-item" href="productos.php?category=neogeo">NeoGeo</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navConsolas" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    CONSOLAS
                                </a>
                                <div class="dropdown-menu dropdown-menu-compact">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="dropdown-header">Marcas Principales</h6>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=nintendo">Nintendo</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=sony">Sony PlayStation</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=microsoft">Microsoft Xbox</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=sega">SEGA</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=atari">Atari</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=snk">Neo Geo</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=panasonic">3DO</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=coleco">ColecoVision</a>
                                                <a class="dropdown-item" href="productos.php?type=console&brand=varios">Otras Marcas</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="contacto.php">CONTACTO</a>
                            </li>
                        </ul>
                    </div>
                    <!-- Search Section (35%) -->
                    <div class="col-lg-4 col-md-6 pe-0">
                        <div class="nav-search-section">
                            <form class="search-form" action="search.php" method="GET">
                                <div class="input-group">
                                    <input type="text" class="form-control search-input" placeholder="Buscar productos...">
                                    <button class="btn search-btn" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de búsqueda en el menú móvil -->
                <div class="mobile-search-section d-lg-none mt-auto">
                    <form class="search-form" action="search.php" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control search-input" placeholder="Buscar productos...">
                            <button class="btn search-btn" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Botones de autenticación al final del menú móvil -->
                <div class="mobile-auth-section d-lg-none">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle w-100" type="button" id="mobileUserMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-2"></i><?= htmlspecialchars($currentUser['first_name']) ?>
                            </button>
                            <ul class="dropdown-menu w-100">
                                <li><h6 class="dropdown-header"><?= getGreeting() ?></h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="order_history.php"><i class="fas fa-history me-2"></i>Mis Pedidos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <a href="register.php" class="btn btn-outline-light flex-fill">
                                <i class="fas fa-user-plus me-1"></i>CREAR CUENTA
                            </a>
                            <a href="login.php" class="btn btn-light flex-fill">
                                <i class="fas fa-sign-in-alt me-1"></i>INICIAR SESIÓN
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    </header>

    <!-- REMOVED: Sistema de carrito avanzado que interfería con el sistema moderno -->
    
    <!-- Script para menú hamburguesa lateral -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        let overlay = null;
        let bsCollapse = null;
        
        // Inicializar Bootstrap Collapse
        if (navbarCollapse) {
            bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                toggle: false
            });
        }
        
        // Crear overlay dinámicamente
        function createOverlay() {
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'menu-overlay';
                document.body.appendChild(overlay);
                
                // Cerrar menú al hacer clic en el overlay
                overlay.addEventListener('click', closeMenu);
            }
        }
        
        // Función para cerrar el menú
        function closeMenu() {
            if (bsCollapse && navbarCollapse.classList.contains('show')) {
                bsCollapse.hide();
            }
        }
        
        // Función para abrir el menú
        function openMenu() {
            if (bsCollapse && !navbarCollapse.classList.contains('show')) {
                bsCollapse.show();
            }
        }
        
        // Manejar clic en el botón hamburguesa
        if (navbarToggler) {
            navbarToggler.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (navbarCollapse.classList.contains('show')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }
        
        // Cerrar menú al hacer clic en el botón X real
        const closeButton = document.querySelector('.mobile-close-btn');
        if (closeButton) {
            closeButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeMenu();
            });
        }
        
        // Cerrar menú con la tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navbarCollapse && navbarCollapse.classList.contains('show')) {
                closeMenu();
            }
        });
        
        // Manejar apertura del menú
        if (navbarCollapse) {
            navbarCollapse.addEventListener('show.bs.collapse', function() {
                createOverlay();
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    if (overlay) overlay.classList.add('show');
                }, 100);
            });
            
            // Manejar cierre del menú
            navbarCollapse.addEventListener('hide.bs.collapse', function() {
                document.body.style.overflow = '';
                if (overlay) {
                    overlay.classList.remove('show');
                    setTimeout(() => {
                        if (overlay && overlay.parentNode) {
                            overlay.parentNode.removeChild(overlay);
                            overlay = null;
                        }
                    }, 300);
                }
            });
        }
    });
    </script>

    <main class="container-fluid px-4 py-4">
