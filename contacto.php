<?php
require_once 'includes/header.php';
?>

<!-- Página de Contacto Rediseñada -->
<main class="contact-page-redesigned">
    <!-- Header Section -->
    <section class="contact-header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="contact-header-content">
                        <h1 class="contact-main-title">Contáctanos</h1>
                        <p class="contact-subtitle">¿Tienes alguna pregunta sobre nuestros productos gaming? Estamos aquí para ayudarte. Nuestro equipo de especialistas está listo para resolver todas tus dudas.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-stats-grid">
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number">24h</span>
                                <span class="stat-label">Respuesta</span>
                            </div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number">24/7</span>
                                <span class="stat-label">Soporte</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Contact Section -->
    <section class="contact-main-section">
        <div class="container">
            <div class="row">
                <!-- Formulario Principal -->
                <div class="col-lg-8 mb-4">
                    <div class="contact-form-card">
                        <div class="card-header-custom">
                            <h2 class="form-title">
                                <i class="fas fa-paper-plane me-2"></i>
                                Envía tu mensaje
                            </h2>
                            <p class="form-description">Completa el formulario y nos pondremos en contacto contigo lo antes posible.</p>
                        </div>

                        <!-- Mensaje de estado -->
                        <div id="contact-message" style="display: none;" class="contact-alert mb-4"></div>

                        <form class="contact-form-redesigned" id="contactForm" action="contact_process.php" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label class="input-label">Nombre</label>
                                        <input type="text" class="input-field" name="nombre" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label class="input-label">Apellido</label>
                                        <input type="text" class="input-field" name="apellido" required>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group-custom">
                                <label class="input-label">Email</label>
                                <input type="email" class="input-field" name="email" required>
                            </div>

                            <div class="input-group-custom">
                                <label class="input-label">Asunto</label>
                                <select class="input-field input-select" name="asunto" required>
                                    <option value="">Selecciona un asunto</option>
                                    <option value="consulta_producto">🎮 Consulta sobre productos</option>
                                    <option value="soporte_tecnico">🛠️ Soporte técnico</option>
                                    <option value="sugerencia">💡 Sugerencia</option>
                                    <option value="reclamo">⚠️ Reclamo</option>
                                    <option value="otro">📝 Otro</option>
                                </select>
                            </div>

                            <div class="input-group-custom">
                                <label class="input-label">Mensaje</label>
                                <textarea class="input-field input-textarea" name="mensaje" rows="5" placeholder="Describe tu consulta o comentario..." required></textarea>
                            </div>

                            <button type="submit" class="btn-submit-redesigned">
                                <span class="btn-content">
                                    <i class="fas fa-paper-plane"></i>
                                    <span class="btn-text">Enviar mensaje</span>
                                </span>
                                <span class="btn-loading" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Enviando...</span>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="col-lg-4">
                    <div class="contact-info-sidebar">
                        <!-- Información de contacto -->
                        <div class="info-card">
                            <h3 class="info-title">
                                <i class="fas fa-info-circle me-2"></i>
                                Información de contacto
                            </h3>
                            <div class="info-items">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">Email principal</span>
                                        <a href="mailto:contacto@multigamer360.com" class="info-link">contacto@multigamer360.com</a>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">Soporte técnico</span>
                                        <a href="mailto:soporte@multigamer360.com" class="info-link">soporte@multigamer360.com</a>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">Horarios de atención</span>
                                        <span class="info-text">Lun - Vie: 9:00 - 21:00</span>
                                        <span class="info-text">Sáb: 10:00 - 18:00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Rápido -->
                        <div class="info-card">
                            <h3 class="info-title">
                                <i class="fas fa-question-circle me-2"></i>
                                Preguntas frecuentes
                            </h3>
                            <div class="faq-quick">
                                <details class="faq-item-quick">
                                    <summary>¿Cuánto tardan en responder?</summary>
                                    <p>Respondemos todas las consultas en menos de 24 horas durante días hábiles.</p>
                                </details>
                                <details class="faq-item-quick">
                                    <summary>¿Hacen envíos a todo el país?</summary>
                                    <p>Sí, realizamos envíos a todo el territorio nacional con diferentes opciones de entrega.</p>
                                </details>
                                <details class="faq-item-quick">
                                    <summary>¿Tienen garantía los productos?</summary>
                                    <p>Todos nuestros productos incluyen garantía del fabricante y soporte técnico especializado.</p>
                                </details>
                            </div>
                        </div>

                        <!-- Gaming Tips -->
                        <div class="info-card gaming-tips">
                            <h3 class="info-title">
                                <i class="fas fa-gamepad me-2"></i>
                                ¿Sabías qué?
                            </h3>
                            <div class="tip-content">
                                <p>Nuestro equipo de especialistas en gaming puede ayudarte a armar la configuración perfecta para tus necesidades y presupuesto.</p>
                                <div class="tip-highlight">
                                    <i class="fas fa-star"></i>
                                    <span>Asesoramiento gratuito personalizado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const contactMessage = document.getElementById('contact-message');
    
    // Manejo del formulario
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('.btn-submit-redesigned');
        const btnContent = submitBtn.querySelector('.btn-content');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        // Mostrar estado de carga
        btnContent.style.display = 'none';
        btnLoading.style.display = 'flex';
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        
        // Enviar formulario via AJAX
        const formData = new FormData(this);
        
        fetch('contact_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contactMessage.className = 'contact-alert alert-success mb-4';
                contactMessage.innerHTML = `
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>¡Mensaje enviado correctamente!</strong>
                        <p>${data.message}</p>
                    </div>
                `;
                contactForm.reset();
            } else {
                contactMessage.className = 'contact-alert alert-error mb-4';
                contactMessage.innerHTML = `
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Error al enviar</strong>
                        <p>${data.message}</p>
                    </div>
                `;
            }
            contactMessage.style.display = 'flex';
            
            // Scroll suave al mensaje
            contactMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
        .catch(error => {
            contactMessage.className = 'contact-alert alert-error mb-4';
            contactMessage.innerHTML = `
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-content">
                    <strong>Error de conexión</strong>
                    <p>Hubo un problema al enviar el mensaje. Por favor, inténtalo de nuevo.</p>
                </div>
            `;
            contactMessage.style.display = 'flex';
        })
        .finally(() => {
            // Restaurar estado del botón
            btnContent.style.display = 'flex';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
