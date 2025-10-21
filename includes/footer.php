    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <!-- Primera sección del footer -->
        <div class="footer-main">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <div class="footer-branding">
                            <div class="footer-logo">
                                <img src="/assets/images/footer/logo-footer.png" alt="MultiGamer360 Logo" class="footer-logo-img">
                            </div>
                            <div class="footer-social-icons">
                                <a href="#" class="social-icon" target="_blank" aria-label="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-icon" target="_blank" aria-label="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-icon" target="_blank" aria-label="WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <h5 class="footer-title">AYUDA</h5>
                        <ul class="footer-links">
                            <li><a href="faq.php">Preguntas frecuentes</a></li>
                            <li><a href="#">Cómo comprar</a></li>
                        </ul>
                    </div>
                    <div class="col-md-2">
                        <h5 class="footer-title">Contáctanos</h5>
                        <ul class="footer-contact">
                            <li>
                                <i class="fas fa-envelope"></i>
                                <span>info@multigamer360.com</span>
                            </li>
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Buenos Aires, Argentina</span>
                            </li>
                            <li>
                                <i class="fas fa-phone"></i>
                                <span>+54 (11) 4567-8901</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h5 class="footer-title">Newsletter</h5>
                        <form class="newsletter-form" action="newsletter_process.php" method="POST">
                            <div class="form-group mb-3">
                                <input type="text" class="form-control newsletter-input" placeholder="Nombre completo" name="nombre_completo" required>
                            </div>
                            <div class="form-group mb-3">
                                <input type="email" class="form-control newsletter-input" placeholder="Correo electrónico" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-newsletter">SUSCRIBIRSE</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Segunda sección del footer - Copyright -->
        <div class="footer-copyright">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <p>&copy; <?php echo date('Y'); ?> MultiGamer360. Todos los derechos reservados.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript Files - Orden optimizado para evitar conflictos -->
    <script src="/assets/js/mobile-menu.js?v=1.2"></script>
    <script src="/assets/js/wishlist-system.js?v=0.5"></script>
    <script src="/assets/js/modern-cart-button.js?v=0.4"></script>
    <script src="/assets/js/main.js?v=1.2"></script>
    
    <!-- Verificación de errores y limpieza -->
    <script>
        // Inicialización segura de sistemas
        window.addEventListener('load', function() {
            console.log('Iniciando sistemas JavaScript...');
            
            // Inicializar ModernCartButton si está disponible y no ha sido inicializado
            if (typeof window.ModernCartButton !== 'undefined' && !window.modernCartButton) {
                try {
                    window.modernCartButton = new ModernCartButton();
                    console.log('ModernCartButton inicializado correctamente');
                } catch (error) {
                    console.error('Error inicializando ModernCartButton:', error);
                }
            }
            
            // Inicializar WishlistSystem si está disponible y no ha sido inicializado
            if (typeof window.WishlistSystem !== 'undefined' && !window.wishlistSystem) {
                try {
                    window.wishlistSystem = new WishlistSystem();
                    console.log('WishlistSystem inicializado correctamente');
                } catch (error) {
                    console.error('Error inicializando WishlistSystem:', error);
                }
            }
            
            // Limpiar listeners duplicados si los hay
            const existingHandlers = document.querySelectorAll('[data-handler-attached]');
            existingHandlers.forEach(element => {
                element.removeAttribute('data-handler-attached');
            });
            
            console.log('Sistemas verificados y limpiados');
        });
    </script>
    
    <!-- CSS adicional para asegurar que los dropdowns funcionen correctamente -->
    <style>
        /* Asegurar que los dropdowns tengan el z-index correcto */
        .dropdown-menu {
            z-index: 9999 !important;
        }
        
        .user-dropdown-menu {
            z-index: 9999 !important;
            position: absolute !important;
            top: calc(100% + 5px) !important;
            right: 0 !important;
            left: auto !important;
            min-width: 200px !important;
            margin-top: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px !important;
            background: rgba(40, 40, 40, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Forzar el posicionamiento correcto */
        .header-actions .dropdown {
            position: relative !important;
        }
        
        /* Mejorar la apariencia de los items del dropdown */
        .user-dropdown-menu .dropdown-item {
            color: #fff !important;
            padding: 10px 15px !important;
            border-radius: 4px !important;
            margin: 2px 5px !important;
            transition: all 0.2s ease !important;
        }

        .user-dropdown-menu .dropdown-item:hover {
            background: rgba(220, 38, 27, 0.8) !important;
            color: #fff !important;
        }

        .user-dropdown-menu .dropdown-header {
            color: #dc262b !important;
            font-weight: bold !important;
            padding: 10px 15px 5px !important;
        }

        .user-dropdown-menu .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.2) !important;
            margin: 5px 0 !important;
        }
    </style>
    
    <!-- Script para actualizar carrito al cargar página -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const baseUrl = window.SITE_URL || '';
            // Actualizar el estado del carrito inmediatamente al cargar la página
            fetch(`${baseUrl}/ajax/get-cart-count.php`, {
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Footer sync - Cart data:', data);
                const cartDisplay = document.getElementById('cart-display');
                if (cartDisplay && data.cart_total !== undefined) {
                    const formattedTotal = data.cart_total > 0 ? `$${parseFloat(data.cart_total).toFixed(2)}` : '$0';
                    cartDisplay.textContent = formattedTotal;
                    console.log('Footer sync - Updated display to:', formattedTotal);
                }
            })
            .catch(error => {
                console.error('Error al sincronizar carrito:', error);
            });
        });
    </script>
</body>
</html>
