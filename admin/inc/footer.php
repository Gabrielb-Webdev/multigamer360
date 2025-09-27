            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>
    
    <!-- CSRF Token para AJAX -->
    <script>
        window.csrfToken = '<?php echo $csrf_token; ?>';
        
        // Configurar token CSRF para todas las peticiones AJAX
        if (typeof jQuery !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-Token': window.csrfToken
                }
            });
        }
    </script>
    
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
</body>
</html>