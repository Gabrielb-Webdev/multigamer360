<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la página
$page_title = "Preguntas Frecuentes | MultiGamer360";
$page_description = "Encuentra respuestas a las preguntas más comunes sobre nuestros productos gaming, consolas retro, videojuegos y servicios en MultiGamer360.";
?>

<!-- Header -->
<?php include 'includes/header.php'; ?>

<!-- Custom CSS for FAQ -->
<link rel="stylesheet" href="assets/css/faq-modern.css?v=1.1">

<!-- FAQ Main Container -->
<div class="faq-main-container">

    <!-- FAQ Header Section -->
    <section class="faq-header-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <div class="faq-header-content">
                        <h1 class="faq-main-title">
                            <i class="fas fa-question-circle me-3"></i>
                            Preguntas Frecuentes
                        </h1>
                        <p class="faq-subtitle">
                            Encuentra respuestas rápidas a las dudas más comunes sobre nuestros productos gaming y servicios.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Search Section -->
    <section class="faq-search-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="search-box">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="faqSearch" class="search-input" 
                                   placeholder="Busca tu pregunta aquí...">
                            <button class="search-clear" id="clearSearch" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Categories Section -->
    <section class="faq-categories-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="category-tabs">
                        <button class="category-tab active" data-category="all">
                            <i class="fas fa-list"></i>
                            Todas las preguntas
                        </button>
                        <button class="category-tab" data-category="productos">
                            <i class="fas fa-gamepad"></i>
                            Productos
                        </button>
                        <button class="category-tab" data-category="envios">
                            <i class="fas fa-shipping-fast"></i>
                            Envíos
                        </button>
                        <button class="category-tab" data-category="pagos">
                            <i class="fas fa-credit-card"></i>
                            Pagos
                        </button>
                        <button class="category-tab" data-category="soporte">
                            <i class="fas fa-headset"></i>
                            Soporte
                        </button>
                        <button class="category-tab" data-category="cuenta">
                            <i class="fas fa-user"></i>
                            Cuenta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Content Section -->
    <section class="faq-content-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="faq-container" id="faqContainer">
                        
                        <!-- PRODUCTOS -->
                        <div class="faq-category" data-category="productos">
                            <h3 class="category-title">
                                <i class="fas fa-gamepad"></i>
                                Productos y Catálogo
                            </h3>
                            
                            <div class="faq-item" data-keywords="consola retro nintendo sega playstation">
                                <div class="faq-question">
                                    <h4>¿Qué tipos de consolas retro tienen disponibles?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Contamos con una amplia selección de consolas retro incluyendo:</p>
                                    <ul>
                                        <li><strong>Nintendo:</strong> NES, SNES, Nintendo 64, GameCube</li>
                                        <li><strong>Sega:</strong> Genesis/Mega Drive, Saturn, Dreamcast</li>
                                        <li><strong>Sony:</strong> PlayStation 1, PlayStation 2</li>
                                        <li><strong>Atari:</strong> 2600, 7800</li>
                                        <li><strong>Consolas portátiles:</strong> Game Boy, Game Gear, PSP</li>
                                    </ul>
                                    <p>Todas nuestras consolas están completamente funcionales y vienen con garantía.</p>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="videojuegos juegos catálogo disponibles">
                                <div class="faq-question">
                                    <h4>¿Cómo puedo ver todos los videojuegos disponibles?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Puedes explorar nuestro catálogo completo de videojuegos de varias formas:</p>
                                    <ul>
                                        <li>Visitando la sección <strong>"Videojuegos"</strong> en el menú principal</li>
                                        <li>Usando los filtros por consola, género o año</li>
                                        <li>Utilizando la barra de búsqueda en la parte superior</li>
                                        <li>Navegando por categorías como "Más vendidos" o "Ofertas especiales"</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="estado condición usado nuevo">
                                <div class="faq-question">
                                    <h4>¿En qué estado están los productos usados?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Clasificamos nuestros productos usados en diferentes categorías:</p>
                                    <ul>
                                        <li><strong>Como Nuevo:</strong> Sin signos de uso, con caja original</li>
                                        <li><strong>Excelente:</strong> Mínimos signos de uso, completamente funcional</li>
                                        <li><strong>Muy Bueno:</strong> Algunos signos de uso pero en perfecto funcionamiento</li>
                                        <li><strong>Bueno:</strong> Signos evidentes de uso pero completamente funcional</li>
                                    </ul>
                                    <p>Todos los productos incluyen fotos reales del estado actual.</p>
                                </div>
                            </div>
                        </div>

                        <!-- ENVÍOS -->
                        <div class="faq-category" data-category="envios">
                            <h3 class="category-title">
                                <i class="fas fa-shipping-fast"></i>
                                Envíos y Entregas
                            </h3>
                            
                            <div class="faq-item" data-keywords="envío gratis costo precio">
                                <div class="faq-question">
                                    <h4>¿Ofrecen envío gratuito?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Sí, ofrecemos envío gratuito en las siguientes condiciones:</p>
                                    <ul>
                                        <li>Compras superiores a <strong>$75.000 COP</strong></li>
                                        <li>Usuarios con membresía Premium</li>
                                        <li>Promociones especiales durante eventos</li>
                                    </ul>
                                    <p>Para compras menores, el costo de envío varía según la ciudad y el peso del paquete.</p>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="tiempo entrega demora días">
                                <div class="faq-question">
                                    <h4>¿Cuánto tiempo tarda en llegar mi pedido?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Los tiempos de entrega varían según la ubicación:</p>
                                    <ul>
                                        <li><strong>Bogotá:</strong> 1-2 días hábiles</li>
                                        <li><strong>Ciudades principales:</strong> 2-4 días hábiles</li>
                                        <li><strong>Otras ciudades:</strong> 3-6 días hábiles</li>
                                        <li><strong>Zonas rurales:</strong> 5-8 días hábiles</li>
                                    </ul>
                                    <p>Recibirás un código de seguimiento por email una vez despachado tu pedido.</p>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="seguimiento tracking rastreo">
                                <div class="faq-question">
                                    <h4>¿Cómo puedo rastrear mi pedido?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Puedes rastrear tu pedido de varias formas:</p>
                                    <ul>
                                        <li>Con el código de seguimiento enviado por email</li>
                                        <li>Ingresando a tu cuenta en la sección "Mis Pedidos"</li>
                                        <li>Contactando nuestro servicio al cliente</li>
                                    </ul>
                                    <p>Recibirás notificaciones por email en cada etapa del envío.</p>
                                </div>
                            </div>
                        </div>

                        <!-- PAGOS -->
                        <div class="faq-category" data-category="pagos">
                            <h3 class="category-title">
                                <i class="fas fa-credit-card"></i>
                                Métodos de Pago
                            </h3>
                            
                            <div class="faq-item" data-keywords="pago métodos tarjeta efectivo">
                                <div class="faq-question">
                                    <h4>¿Qué métodos de pago aceptan?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Aceptamos diversos métodos de pago para tu comodidad:</p>
                                    <ul>
                                        <li><strong>Tarjetas de crédito:</strong> Visa, Mastercard, American Express</li>
                                        <li><strong>Tarjetas débito:</strong> Todas las tarjetas débito nacionales</li>
                                        <li><strong>PSE:</strong> Pagos seguros en línea</li>
                                        <li><strong>Efectivo:</strong> Pago contra entrega (solo en Bogotá)</li>
                                        <li><strong>Transferencias:</strong> Bancolombia, Davivienda, BBVA</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="cuotas financiación crédito">
                                <div class="faq-question">
                                    <h4>¿Puedo pagar en cuotas?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Sí, ofrecemos varias opciones de financiación:</p>
                                    <ul>
                                        <li><strong>Tarjetas de crédito:</strong> Hasta 36 cuotas sin interés*</li>
                                        <li><strong>Financiación propia:</strong> 3, 6, 9 y 12 cuotas</li>
                                        <li><strong>Promociones especiales:</strong> 0% interés en productos seleccionados</li>
                                    </ul>
                                    <p><small>*Sujeto a aprobación crediticia y términos del banco emisor.</small></p>
                                </div>
                            </div>
                        </div>

                        <!-- SOPORTE -->
                        <div class="faq-category" data-category="soporte">
                            <h3 class="category-title">
                                <i class="fas fa-headset"></i>
                                Soporte y Garantías
                            </h3>
                            
                            <div class="faq-item" data-keywords="garantía tiempo cobertura">
                                <div class="faq-question">
                                    <h4>¿Qué garantía ofrecen en sus productos?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Ofrecemos diferentes tipos de garantía según el producto:</p>
                                    <ul>
                                        <li><strong>Consolas nuevas:</strong> 12 meses de garantía completa</li>
                                        <li><strong>Consolas usadas:</strong> 3-6 meses según el estado</li>
                                        <li><strong>Videojuegos:</strong> 30 días por defectos de fabricación</li>
                                        <li><strong>Accesorios:</strong> 3-6 meses según la marca</li>
                                    </ul>
                                    <p>La garantía cubre defectos de fabricación, no daños por mal uso.</p>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="devolución cambio retorno">
                                <div class="faq-question">
                                    <h4>¿Puedo devolver un producto si no me gusta?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Sí, ofrecemos política de devolución flexible:</p>
                                    <ul>
                                        <li><strong>Productos nuevos:</strong> 15 días para devolución</li>
                                        <li><strong>Productos usados:</strong> 7 días para devolución</li>
                                        <li>El producto debe estar en condiciones originales</li>
                                        <li>Incluir todos los accesorios y empaques</li>
                                    </ul>
                                    <p>Los gastos de envío de devolución corren por cuenta del cliente.</p>
                                </div>
                            </div>
                        </div>

                        <!-- CUENTA -->
                        <div class="faq-category" data-category="cuenta">
                            <h3 class="category-title">
                                <i class="fas fa-user"></i>
                                Cuenta y Registro
                            </h3>
                            
                            <div class="faq-item" data-keywords="registro cuenta crear perfil">
                                <div class="faq-question">
                                    <h4>¿Necesito crear una cuenta para comprar?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>No es obligatorio crear una cuenta, pero te recomendamos hacerlo por los siguientes beneficios:</p>
                                    <ul>
                                        <li>Historial de compras y seguimiento de pedidos</li>
                                        <li>Lista de deseos y productos favoritos</li>
                                        <li>Descuentos exclusivos para miembros</li>
                                        <li>Proceso de compra más rápido</li>
                                        <li>Notificaciones de ofertas especiales</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="faq-item" data-keywords="contraseña olvidé resetear cambiar">
                                <div class="faq-question">
                                    <h4>Olvidé mi contraseña, ¿cómo la recupero?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Para recuperar tu contraseña:</p>
                                    <ol>
                                        <li>Ve a la página de <strong>Iniciar Sesión</strong></li>
                                        <li>Haz clic en <strong>"¿Olvidaste tu contraseña?"</strong></li>
                                        <li>Ingresa tu email registrado</li>
                                        <li>Revisa tu correo y sigue las instrucciones</li>
                                        <li>Crea una nueva contraseña segura</li>
                                    </ol>
                                    <p>Si no recibes el correo, revisa tu carpeta de spam o contáctanos.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- No Results Message -->
                    <div class="no-results" id="noResults" style="display: none;">
                        <div class="no-results-content">
                            <i class="fas fa-search"></i>
                            <h4>No encontramos respuestas para tu búsqueda</h4>
                            <p>Intenta con palabras diferentes o <a href="contacto.php">contáctanos directamente</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
<!-- End FAQ Main Container -->

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom FAQ JS -->
    <script src="assets/js/faq.js?v=1.1"></script>

