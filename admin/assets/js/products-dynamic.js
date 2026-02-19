/**
 * =====================================================
 * PRODUCTS DYNAMIC UX - Sin Recargas de Página
 * =====================================================
 * Sistema dinámico para gestión de productos sin recargas
 * Version: 1.0
 * Fecha: 2026-02-19
 */

/**
 * Sistema de Toasts Modernos
 */
class ToastManager {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'success', duration = 3000) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const colors = {
            success: 'bg-success',
            error: 'bg-danger',
            warning: 'bg-warning',
            info: 'bg-info'
        };

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white ${colors[type]} border-0 fade show`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${icons[type]} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        this.container.appendChild(toast);

        // Auto-hide después de duration
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);

        return toast;
    }
}

const toastManager = new ToastManager();

/**
 * Actualizar Fila de Producto Dinámicamente
 */
function updateProductRow(productId, newData) {
    const row = document.querySelector(`tr input[value="${productId}"]`)?.closest('tr');
    if (!row) {
        console.warn(`No se encontró la fila del producto ${productId}`);
        return;
    }

    // Animación de actualización
    row.style.transition = 'background-color 0.3s ease';
    row.style.backgroundColor = '#fff3cd';
    
    setTimeout(() => {
        row.style.backgroundColor = '';
    }, 600);

    // Actualizar estado si se proporciona
    if (newData.status !== undefined) {
        const statusBadge = row.querySelector('td:nth-last-child(2) .badge');
        if (statusBadge) {
            const statusClass = newData.status === 1 ? 'bg-success' : 'bg-secondary';
            const statusText = newData.status === 1 ? 'Activo' : 'Inactivo';
            
            statusBadge.className = `badge ${statusClass}`;
            statusBadge.textContent = statusText;
        }
    }

    // Actualizar otros datos si se proporcionan
    if (newData.stock !== undefined) {
        const stockBadge = row.querySelector('td:nth-last-child(4) .badge');
        if (stockBadge) {
            stockBadge.textContent = newData.stock;
        }
    }
}

/**
 * Remover Fila de Producto con Animación
 */
function removeProductRow(productId) {
    const row = document.querySelector(`tr input[value="${productId}"]`)?.closest('tr');
    if (!row) return;

    // Animación de eliminación
    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    row.style.opacity = '0';
    row.style.transform = 'translateX(-20px)';

    setTimeout(() => {
        row.remove();
        updateProductCount(-1);
        updateBulkActionVisibility();
    }, 300);
}

/**
 * Actualizar Contador de Productos
 */
function updateProductCount(change) {
    const countElement = document.querySelector('.card-header span');
    if (!countElement) return;

    const currentText = countElement.textContent;
    const match = currentText.match(/\(([0-9,]+)/);
    if (match) {
        let count = parseInt(match[1].replace(/,/g, ''));
        count += change;
        countElement.textContent = `Productos (${count.toLocaleString('es-ES')} totales)`;
    }
}

/**
 * Cambio en Masa de Estado - Sin Reload
 */
function bulkChangeStatus(newStatus) {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        toastManager.show('Seleccione al menos un producto', 'warning');
        return;
    }

    const ids = Array.from(selected).map(cb => cb.value);

    const statusConfigs = {
        'active': {
            name: 'Activo',
            icon: 'fa-check-circle',
            color: 'success'
        },
        'inactive': {
            name: 'Inactivo',
            icon: 'fa-times-circle',
            color: 'secondary'
        },
        'draft': {
            name: 'Borrador',
            icon: 'fa-file-alt',
            color: 'warning'
        }
    };

    const config = statusConfigs[newStatus] || statusConfigs['active'];

    // Configurar modal
    const modal = document.getElementById('bulkStatusModal');
    const countEl = document.getElementById('bulkStatusCount');
    const nameEl = document.getElementById('bulkStatusName');
    const iconEl = document.getElementById('bulkStatusIcon');
    const headerEl = document.getElementById('bulkStatusHeader');
    const confirmBtn = document.getElementById('confirmBulkStatusBtn');

    countEl.textContent = ids.length;
    nameEl.textContent = config.name;
    iconEl.className = `fas ${config.icon} fa-3x text-${config.color} mb-3`;
    headerEl.className = `modal-header bg-${config.color} text-white`;
    confirmBtn.className = `btn btn-${config.color}`;
    confirmBtn.onclick = function() {
        executeBulkStatusChangeImproved(ids, newStatus, config.name);
    };

    // Mostrar el modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Ejecutar Cambio de Estado (Sin Reload)
 */
function executeBulkStatusChangeImproved(ids, newStatus, statusName) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkStatusModal'));
    const confirmBtn = document.getElementById('confirmBulkStatusBtn');
    const originalBtnText = confirmBtn.innerHTML;

    // Deshabilitar botón
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || AdminPanel?.csrfToken || '';
    const formData = new FormData();
    formData.append('product_ids', ids.join(','));
    formData.append('status', newStatus);
    formData.append('csrf_token', csrfToken);

    fetch('api/bulk_update_status.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Cerrar modal
            modal.hide();

            // Actualizar filas dinámicamente
            const statusValue = newStatus === 'active' ? 1 : 0;
            ids.forEach(id => {
                updateProductRow(id, { status: statusValue });
            });

            // Deseleccionar checkboxes
            document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => {
                cb.checked = false;
            });
            document.getElementById('select-all').checked = false;
            updateBulkActionVisibility();

            // Mostrar toast de éxito
            toastManager.show(`${ids.length} producto(s) cambiado(s) a ${statusName}`, 'success');

        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastManager.show('Error: ' + error.message, 'error', 5000);

        // Restaurar botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnText;
    });
}

/**
 * Eliminación en Masa (Sin Reload)
 */
function bulkDelete() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (selected.length === 0) {
        toastManager.show('Seleccione al menos un producto', 'warning');
        return;
    }

    const ids = Array.from(selected).map(cb => cb.value);

    // Configurar modal
    const countEl = document.getElementById('bulkDeleteCount');
    countEl.textContent = ids.length;

    const confirmBtn = document.getElementById('confirmBulkDeleteBtn');
    confirmBtn.onclick = function() {
        executeBulkDeleteImproved(ids);
    };

    // Mostrar modal
    const modal = document.getElementById('bulkDeleteModal');
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Ejecutar Eliminación en Masa (Sin Reload)
 */
function executeBulkDeleteImproved(ids) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
    const confirmBtn = document.getElementById('confirmBulkDeleteBtn');
    const originalBtnText = confirmBtn.innerHTML;

    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Eliminando...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || AdminPanel?.csrfToken || '';
    const formData = new FormData();
    formData.append('ids', ids.join(','));
    formData.append('csrf_token', csrfToken);

    fetch('api/delete_product.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Cerrar modal
            modal.hide();

            // Remover filas con animación
            ids.forEach(id => {
                removeProductRow(id);
            });

            // Deseleccionar todo
            document.getElementById('select-all').checked = false;

            // Mostrar toast de éxito
            toastManager.show(`${ids.length} producto(s) eliminado(s) correctamente`, 'success');

        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastManager.show('Error: ' + error.message, 'error', 5000);
        
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnText;
    });
}

/**
 * Eliminación Individual (Sin Reload)
 */
function deleteProduct(id) {
    const modal = document.getElementById('deleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    confirmBtn.onclick = function() {
        executeDeleteImproved(id);
    };

    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Ejecutar Eliminación Individual (Sin Reload)
 */
function executeDeleteImproved(id) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalHTML = confirmBtn.innerHTML;
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || AdminPanel?.csrfToken || '';
    const formData = new FormData();
    formData.append('ids', id);
    formData.append('csrf_token', csrfToken);

    fetch('api/delete_product.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Cerrar modal
            modal.hide();

            // Remover fila con animación
            removeProductRow(id);

            // Toast de éxito
            toastManager.show('Producto eliminado correctamente', 'success');

        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastManager.show('Error: ' + error.message, 'error', 5000);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalHTML;
    });
}

/**
 * Actualizar Visibilidad de Acciones en Masa
 */
function updateBulkActionVisibility() {
    const selected = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    const selectedCount = document.querySelector('.selected-count');

    if (bulkActions) {
        if (selected.length > 0) {
            bulkActions.style.display = 'flex';
            if (selectedCount) {
                selectedCount.textContent = selected.length;
            }
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

/**
 * Inicializar Event Listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    // Select All
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionVisibility();
        });
    }

    // Individual checkboxes
    document.querySelectorAll('tbody input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionVisibility);
    });

    console.log('🚀 Products Dynamic UX initialized - No page reloads!');
});
