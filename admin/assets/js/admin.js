// Admin Panel JavaScript

// Variables globales
window.AdminPanel = {
    csrfToken: window.csrfToken || '',
    baseUrl: window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/'
};

// Configuración AJAX global
const ajaxConfig = {
    headers: {
        'X-CSRF-Token': AdminPanel.csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
    }
};

// Utilidades
const Utils = {
    // Mostrar notificación toast
    showToast: function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        // Crear contenedor si no existe
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remover el elemento después de que se oculte
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    },
    
    // Confirmar acción
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    // Formatear número como moneda
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('es-CL', {
            style: 'currency',
            currency: 'CLP'
        }).format(amount);
    },
    
    // Formatear fecha
    formatDate: function(date, includeTime = false) {
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        };
        
        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }
        
        return new Date(date).toLocaleDateString('es-CL', options);
    },
    
    // Debounce para búsquedas
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Gestión de formularios
const FormManager = {
    // Enviar formulario via AJAX
    submitForm: function(form, options = {}) {
        const formData = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        
        // Agregar token CSRF si no existe
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', AdminPanel.csrfToken);
        }
        
        // Estado de carga
        if (button) {
            button.classList.add('loading');
            button.disabled = true;
        }
        
        const config = {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': AdminPanel.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        return fetch(form.action || window.location.href, config)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Utils.showToast(data.message || 'Operación completada', 'success');
                    if (options.onSuccess) {
                        options.onSuccess(data);
                    }
                } else {
                    Utils.showToast(data.message || 'Error en la operación', 'danger');
                    if (options.onError) {
                        options.onError(data);
                    }
                }
                return data;
            })
            .catch(error => {
                console.error('Error:', error);
                Utils.showToast('Error de conexión', 'danger');
                if (options.onError) {
                    options.onError(error);
                }
            })
            .finally(() => {
                if (button) {
                    button.classList.remove('loading');
                    button.disabled = false;
                }
            });
    },
    
    // Validar formulario
    validate: function(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
};

// Gestión de tablas
const TableManager = {
    // Búsqueda en tabla
    setupSearch: function(searchInput, tableSelector) {
        const debouncedSearch = Utils.debounce((query) => {
            this.filterTable(tableSelector, query);
        }, 300);
        
        searchInput.addEventListener('input', (e) => {
            debouncedSearch(e.target.value);
        });
    },
    
    // Filtrar tabla
    filterTable: function(tableSelector, query) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const lowerQuery = query.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(lowerQuery) ? '' : 'none';
        });
    },
    
    // Selección múltiple
    setupBulkActions: function(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        const selectAll = table.querySelector('thead input[type="checkbox"]');
        const rowCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');
        
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
                this.updateBulkActions();
            });
        }
        
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateBulkActions();
            });
        });
    },
    
    // Actualizar acciones bulk
    updateBulkActions: function() {
        const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
        const bulkActions = document.querySelector('.bulk-actions');
        
        if (bulkActions) {
            bulkActions.style.display = selected.length > 0 ? 'block' : 'none';
            
            const count = bulkActions.querySelector('.selected-count');
            if (count) {
                count.textContent = selected.length;
            }
        }
    }
};

// Gestión de archivos
const FileManager = {
    // Configurar drag & drop
    setupDropZone: function(element, callback) {
        element.addEventListener('dragover', (e) => {
            e.preventDefault();
            element.classList.add('dragover');
        });
        
        element.addEventListener('dragleave', () => {
            element.classList.remove('dragover');
        });
        
        element.addEventListener('drop', (e) => {
            e.preventDefault();
            element.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (callback) {
                callback(files);
            }
        });
    },
    
    // Previsualizar imagen
    previewImage: function(file, container) {
        if (!file.type.startsWith('image/')) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'image-preview';
            
            container.innerHTML = '';
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    }
};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar formularios AJAX
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (FormManager.validate(form)) {
                FormManager.submitForm(form);
            }
        });
    });
    
    // Configurar confirmaciones
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const message = button.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Configurar tooltips de Bootstrap
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(element => {
        new bootstrap.Tooltip(element);
    });
    
    // Auto-hide alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Hacer las utilidades disponibles globalmente
window.Utils = Utils;
window.FormManager = FormManager;
window.TableManager = TableManager;
window.FileManager = FileManager;