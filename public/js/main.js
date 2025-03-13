/**
 * Script principal para la aplicación PDF a Excel con OCR
 * Contiene funcionalidades generales utilizadas en todo el sistema
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    initTooltips();
    
    // Inicializar popovers de Bootstrap
    initPopovers();
    
    // Manejar mensajes flash/alertas
    initAlertDismiss();
    
    // Configurar protección CSRF para AJAX
    setupCSRFProtection();
    
    // Inicializar dropdowns de Bootstrap manualmente donde sea necesario
    initCustomDropdowns();
});

/**
 * Inicializa todos los tooltips de Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Inicializa todos los popovers de Bootstrap
 */
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Configura el cierre automático de alertas
 */
function initAlertDismiss() {
    // Auto-cierre de alertas de éxito y info después de 5 segundos
    const autoCloseAlerts = document.querySelectorAll('.alert-success, .alert-info');
    
    autoCloseAlerts.forEach(function(alert) {
        // Solo si no tiene el atributo data-no-auto-close
        if (!alert.hasAttribute('data-no-auto-close')) {
            setTimeout(function() {
                // Usar el objeto de Bootstrap para fadeout si disponible
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        }
    });
    
    // Añadir botón de cierre a todas las alertas que no lo tengan
    const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
    
    alerts.forEach(function(alert) {
        alert.classList.add('alert-dismissible', 'fade', 'show');
        
        const closeButton = document.createElement('button');
        closeButton.setAttribute('type', 'button');
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'alert');
        closeButton.setAttribute('aria-label', 'Close');
        
        alert.appendChild(closeButton);
    });
}

/**
 * Configura protección CSRF para solicitudes AJAX
 */
function setupCSRFProtection() {
    // Obtener token CSRF del meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (csrfToken) {
        // Para fetch API
        window.fetchWithCSRF = function(url, options = {}) {
            if (!options.headers) {
                options.headers = {};
            }
            
            options.headers['X-CSRF-TOKEN'] = csrfToken;
            
            return fetch(url, options);
        };
        
        // Para jQuery AJAX
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        }
    }
}

/**
 * Inicializa dropdowns personalizados donde sea necesario
 */
function initCustomDropdowns() {
    const customDropdowns = document.querySelectorAll('.custom-dropdown-toggle');
    
    customDropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            
            if (target) {
                if (target.classList.contains('show')) {
                    target.classList.remove('show');
                } else {
                    // Cerrar otros dropdowns abiertos primero
                    document.querySelectorAll('.custom-dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                    
                    target.classList.add('show');
                }
            }
        });
    });
    
    // Cerrar dropdowns cuando se hace clic fuera de ellos
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.custom-dropdown-toggle') && 
            !e.target.closest('.custom-dropdown-menu')) {
            document.querySelectorAll('.custom-dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
            });
        }
    });
}

/**
 * Formatea bytes a una representación legible
 * @param {number} bytes - El tamaño en bytes
 * @param {number} decimals - Número de decimales a mostrar
 * @return {string} Tamaño formateado
 */
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/**
 * Muestra un modal de confirmación
 * @param {string} message - Mensaje a mostrar
 * @param {Function} callback - Función a ejecutar si se confirma
 * @param {string} title - Título del modal (opcional)
 */
function confirmAction(message, callback, title = 'Confirmar acción') {
    // Verificar si existe un modal previo y eliminarlo
    const existingModal = document.getElementById('confirmActionModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Crear el modal
    const modalHTML = `
        <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmActionModalLabel">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">${message}</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmActionBtn">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Añadir el modal al body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Obtener referencia al modal y mostrarlo
    const modalElement = document.getElementById('confirmActionModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Configurar el botón de confirmación
    document.getElementById('confirmActionBtn').addEventListener('click', function() {
        modal.hide();
        if (typeof callback === 'function') {
            callback();
        }
    });
    
    // Limpiar recursos cuando el modal se cierre
    modalElement.addEventListener('hidden.bs.modal', function() {
        modalElement.remove();
    });
}

/**
 * Muestra una notificación toast
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, warning, info)
 * @param {number} duration - Duración en ms (opcional)
 */
function showToast(message, type = 'info', duration = 3000) {
    // Crear contenedor de toasts si no existe
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Determinar la clase según el tipo
    let bgClass = 'bg-info';
    let icon = 'info-circle';
    
    switch (type) {
        case 'success':
            bgClass = 'bg-success';
            icon = 'check-circle';
            break;
        case 'error':
            bgClass = 'bg-danger';
            icon = 'exclamation-circle';
            break;
        case 'warning':
            bgClass = 'bg-warning';
            icon = 'exclamation-triangle';
            break;
    }
    
    // Crear el toast
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} text-white">
                <i class="fas fa-${icon} me-2"></i>
                <strong class="me-auto">Notificación</strong>
                <small>Ahora</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Añadir el toast al contenedor
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    // Obtener referencia al toast y mostrarlo
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });
    
    toast.show();
    
    // Eliminar el toast del DOM cuando se oculte
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}