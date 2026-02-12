// ==================== CÓDIGO LIMPIO PARA EL MODAL DE REVISIÓN ====================

// Función para mostrar el modal de revisión
function showReviewModal() {
    const reviewModalEl = document.getElementById('reviewProductModal');
    if (!reviewModalEl) {
        alert('Error: Modal de revisión no encontrado');
        return;
    }
    
    const reviewModal = new bootstrap.Modal(reviewModalEl, {
        backdrop: 'static',
        keyboard: false
    });
    
    // Inicializar listeners una sola vez
    initializeReviewModalListeners();
    
    // Cargar primer producto
    reviewModalEl.addEventListener('shown.bs.modal', function handler() {
        reviewModalEl.removeEventListener('shown.bs.modal', handler);
        setTimeout(() => {
            if (!loadProductReview(0)) {
                alert('Error al cargar el primer producto');
                reviewModal.hide();
            }
        }, 100);
    });
    
    reviewModal.show();
}

// Función para cargar un producto en el modal
function loadProductReview(index) {
    if (index < 0 || index >= productsToReview.length) {
        return false;
    }
    
    currentProductIndex = index;
    const product = productsToReview[index];
    
    try {
        // Limpiar imágenes previas
        window.downloadedGameImages = [];
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview) imagePreview.innerHTML = '';
        
        // Actualizar contador
        const counter = document.getElementById('productCounter');
        if (counter) counter.textContent = `(${index + 1} de ${productsToReview.length})`;
        
        // Datos del CSV
        setTextContent('csvTitle', product.title);
        setTextContent('csvConsole', product.console_name || '-');
        setTextContent('csvStock', product.stock || '0');
        setTextContent('csvPriceCop', '$' + Number(product.price_cop || 0).toLocaleString());
        setTextContent('csvPriceUsd', product.price_usd ? '$' + product.price_usd : '-');
        
        // Badge de tipo
        const csvType = document.getElementById('csvType');
        if (csvType) {
            const types = {
                'game': { label: 'Juego', class: 'bg-success' },
                'console': { label: 'Consola', class: 'bg-primary' },
                'accessory': { label: 'Accesorio', class: 'bg-warning' }
            };
            const type = types[product.product_type] || types['game'];
            csvType.textContent = type.label;
            csvType.className = 'badge ' + type.class;
        }
        
        // Formulario
        setValue('editTitle', product.title);
        setValue('editProductType', product.product_type || 'game');
        setValue('editSku', product.sku || '');
        setValue('editStatus', product.status);
        setValue('editPriceCop', product.price_cop);
        setValue('editPriceUsd', product.price_usd || '');
        setValue('editStock', product.stock || 0);
        setValue('editDescription', product.description || '');
        setChecked('editFeatured', product.is_featured == 1);
        setChecked('editNew', product.is_new == 1);
        setChecked('editOnSale', product.on_sale == 1);
        
        // SEO
        setValue('editMetaTitle', product.meta_title || product.title + ' - MultiGamer360');
        setValue('editMetaDescription', product.meta_description || generarMetaDesc(product));
        
        // Dropdowns
        populateDropdown('editCategory', dropdownData.categories, product.category_id);
        populateDropdown('editBrand', dropdownData.brands, product.brand_id);
        populateDropdown('editConsole', dropdownData.consoles, product.console_id);
        populateGenreCheckboxes('editGenres', dropdownData.genres, product.genres);
        
        // Botones de navegación
        const prevBtn = document.getElementById('previousProductBtn');
        const nextBtn = document.getElementById('saveProductBtn');
        const finishBtn = document.getElementById('finishImportBtn');
        
        if (prevBtn) prevBtn.disabled = index === 0;
        if (nextBtn) nextBtn.classList.toggle('d-none', index >= productsToReview.length - 1);
        if (finishBtn) finishBtn.classList.toggle('d-none', index < productsToReview.length - 1);
        
        return true;
    } catch (error) {
        console.error('Error al cargar producto:', error);
        return false;
    }
}

// Helpers
function setTextContent(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function setValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
}

function setChecked(id, checked) {
    const el = document.getElementById(id);
    if (el) el.checked = checked;
}

function generarMetaDesc(product) {
    let desc = product.description || `Compra ${product.title} en MultiGamer360`;
    desc = desc.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    if (desc.length > 155) desc = desc.substring(0, 152) + '...';
    return desc;
}

function populateDropdown(selectId, items, selectedValue) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar...</option>';
    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        if (item.id == selectedValue) option.selected = true;
        select.appendChild(option);
    });
}

function populateGenreCheckboxes(containerId, items, selectedValues) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    if (!items || items.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0">No hay géneros disponibles</p>';
        return;
    }
    
    const row = document.createElement('div');
    row.className = 'row';
    
    items.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-2';
        
        const formCheck = document.createElement('div');
        formCheck.className = 'form-check';
        
        const checkbox = document.createElement('input');
        checkbox.className = 'form-check-input';
        checkbox.type = 'checkbox';
        checkbox.name = 'genres[]';
        checkbox.value = item.id;
        checkbox.id = 'genre_' + item.id;
        if (selectedValues && selectedValues.includes(item.id)) {
            checkbox.checked = true;
        }
        
        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = 'genre_' + item.id;
        label.textContent = item.name;
        
        formCheck.appendChild(checkbox);
        formCheck.appendChild(label);
        col.appendChild(formCheck);
        row.appendChild(col);
    });
    
    container.appendChild(row);
}

// Inicializar listeners del modal (solo una vez)
let listenersInitialized = false;

function initializeReviewModalListeners() {
    if (listenersInitialized) return;
    listenersInitialized = true;
    
    // Botón anterior
    const prevBtn = document.getElementById('previousProductBtn');
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentProductIndex > 0) {
                loadProductReview(currentProductIndex - 1);
            }
        });
    }
    
    // Botón saltar
    const skipBtn = document.getElementById('skipProductBtn');
    if (skipBtn) {
        skipBtn.addEventListener('click', () => {
            const skipModal = new bootstrap.Modal(document.getElementById('skipProductConfirmModal'));
            skipModal.show();
        });
    }
    
    // Confirmar saltar
    const confirmSkipBtn = document.getElementById('confirmSkipProductBtn');
    if (confirmSkipBtn) {
        confirmSkipBtn.addEventListener('click', () => {
            const skipModal = bootstrap.Modal.getInstance(document.getElementById('skipProductConfirmModal'));
            if (skipModal) skipModal.hide();
            
            if (currentProductIndex < productsToReview.length - 1) {
                loadProductReview(currentProductIndex + 1);
            } else {
                alert('Revisión completada');
                window.location.reload();
            }
        });
    }
    
    // Botón cerrar
    const closeBtn = document.getElementById('closeReviewModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            const closeModal = new bootstrap.Modal(document.getElementById('closeWithoutSavingModal'));
            closeModal.show();
        });
    }
    
    // Confirmar cerrar
    const confirmCloseBtn = document.getElementById('confirmCloseWithoutSavingBtn');
    if (confirmCloseBtn) {
        confirmCloseBtn.addEventListener('click', () => {
            window.location.reload();
        });
    }
    
    // Botón guardar y siguiente
    const saveBtn = document.getElementById('saveProductBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', () => guardarProducto(false));
    }
    
    // Botón finalizar
    const finishBtn = document.getElementById('finishImportBtn');
    if (finishBtn) {
        finishBtn.addEventListener('click', () => guardarProducto(true));
    }
    
    // Auto-completar
    const autoBtn = document.getElementById('autoCompleteBtn');
    if (autoBtn) {
        autoBtn.addEventListener('click', autoCompletarInfo);
    }
    
    // Preview de imágenes
    const imageInput = document.getElementById('editImages');
    if (imageInput) {
        imageInput.addEventListener('change', handleImagePreview);
    }
    
    // Inicializar botones de agregar (categoría, marca, consola, género)
    initializeAddButtons();
}

// Función para guardar producto
function guardarProducto(isLast) {
    const btn = isLast ? document.getElementById('finishImportBtn') : document.getElementById('saveProductBtn');
    if (!btn) return;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    
    // Recopilar datos del formulario
    const productData = {
        title: getValue('editTitle'),
        product_type: getValue('editProductType'),
        sku: getValue('editSku'),
        status: getValue('editStatus'),
        price_cop: getValue('editPriceCop'),
        price_usd: getValue('editPriceUsd'),
        stock: getValue('editStock'),
        description: getValue('editDescription'),
        category_id: getValue('editCategory'),
        brand_id: getValue('editBrand'),
        console_id: getValue('editConsole'),
        is_featured: getChecked('editFeatured') ? 1 : 0,
        is_new: getChecked('editNew') ? 1 : 0,
        on_sale: getChecked('editOnSale') ? 1 : 0,
        meta_title: getValue('editMetaTitle'),
        meta_description: getValue('editMetaDescription'),
        genres: getCheckedValues('genres[]'),
        images: window.downloadedGameImages || []
    };
    
    // Validaciones
    if (!productData.title || !productData.price_cop) {
        alert('El título y precio son requeridos');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>' + (isLast ? 'Finalizar' : 'Guardar y Siguiente');
        return;
    }
    
    // Enviar al servidor
    fetch('ajax/save_product_from_csv.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(productData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            savedProducts.push(data.product_id);
            
            if (isLast || currentProductIndex >= productsToReview.length - 1) {
                alert(`¡Importación completada! ${savedProducts.length} productos guardados.`);
                window.location.reload();
            } else {
                if (!loadProductReview(currentProductIndex + 1)) {
                    alert('Error al cargar siguiente producto');
                    window.location.reload();
                }
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>' + (isLast ? 'Finalizar' : 'Guardar y Siguiente');
    });
}

// Helpers para recopilar datos
function getValue(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}

function getChecked(id) {
    const el = document.getElementById(id);
    return el ? el.checked : false;
}

function getCheckedValues(name) {
    return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`))
        .map(cb => parseInt(cb.value));
}

// Auto-completar información
function autoCompletarInfo() {
    const btn = this || document.getElementById('autoCompleteBtn');
    const gameName = getValue('editTitle');
    const productType = getValue('editProductType');
    
    if (!gameName) {
        alert('Primero ingresa el nombre del producto');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Buscando...';
    
    const endpoint = productType === 'game' ? 
        'ajax/autocomplete_game_info.php' : 
        'ajax/autocomplete_console_info.php';
    
    fetch(`${endpoint}?game_name=${encodeURIComponent(gameName)}&product_name=${encodeURIComponent(gameName)}&product_type=${productType}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                aplicarAutocompletado(data.data, productType);
            } else {
                alert(data.message || 'No se encontró información');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al auto-completar');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-2"></i>Auto-Rellenar';
        });
}

function aplicarAutocompletado(data, productType) {
    // Aplicar datos solo si los campos están vacíos
    if (!getValue('editDescription') && data.description) {
        setValue('editDescription', data.description);
    }
    if (!getValue('editSku') && data.title) {
        const sku = data.title.toUpperCase().replace(/[^A-Z0-9]/g, '-').substring(0, 30);
        setValue('editSku', sku);
    }
    if (!getValue('editMetaTitle')) {
        setValue('editMetaTitle', (data.title || getValue('editTitle')) + ' - MultiGamer360');
    }
    
    alert('Información completada exitosamente');
}

// Preview de imágenes
function handleImagePreview(e) {
    const preview = document.getElementById('imagePreview');
    if (!preview) return;
    
    preview.innerHTML = '';
    const files = Array.from(e.target.files);
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'd-inline-block position-relative me-2 mb-2';
            div.style.width = '150px';
            div.style.height = '150px';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-thumbnail';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            
            div.appendChild(img);
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// Función para abrir lightbox
function openLightbox(imageSrc, caption) {
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxModal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
    
    if (lightboxImage && lightboxCaption) {
        lightboxImage.src = imageSrc;
        lightboxCaption.textContent = caption || '';
        lightboxModal.show();
    }
}
