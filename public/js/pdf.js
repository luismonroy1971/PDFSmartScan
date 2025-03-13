/**
 * Utilidades para visualización y manipulación de archivos PDF
 * Utiliza PDF.js para renderizar PDFs en el navegador
 */

// Configuración global para PDF.js
if (typeof pdfjsLib !== 'undefined') {
    // Establecer la ruta al worker de PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
}

/**
 * Clase para gestionar visualización de PDFs
 */
class PDFViewer {
    /**
     * Constructor
     * @param {string} containerId - ID del elemento contenedor
     * @param {Object} options - Opciones de configuración
     */
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Contenedor no encontrado:', containerId);
            return;
        }
        
        this.options = Object.assign({
            scale: 1.0,
            pageGap: 10,
            enableAnnotations: false,
            pageNavigator: true,
            maxPagesInViewport: 3,
            renderAllPages: false,
            initialPage: 1
        }, options);
        
        this.pdfDoc = null;
        this.currentPage = this.options.initialPage;
        this.numPages = 0;
        this.pageRendering = false;
        this.pageNumPending = null;
        this.scale = this.options.scale;
        this.canvasArray = [];
        this.annotationLayerArray = [];
        
        // Inicializar la UI
        this.setupUI();
    }
    
    /**
     * Configura la interfaz de usuario
     */
    setupUI() {
        // Crear el contenedor de páginas si no existe
        if (!this.container.querySelector('.pdf-pages')) {
            const pagesContainer = document.createElement('div');
            pagesContainer.className = 'pdf-pages';
            this.container.appendChild(pagesContainer);
        }
        
        this.pagesContainer = this.container.querySelector('.pdf-pages');
        
        // Añadir navegador de páginas si está habilitado
        if (this.options.pageNavigator) {
            const navigatorHTML = `
                <div class="pdf-navigator">
                    <button class="btn btn-sm btn-primary" id="${this.container.id}-prev">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </button>
                    <span>
                        Página <span id="${this.container.id}-page-num">0</span> 
                        de <span id="${this.container.id}-page-count">0</span>
                    </span>
                    <button class="btn btn-sm btn-primary" id="${this.container.id}-next">
                        Siguiente <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            `;
            
            this.container.insertAdjacentHTML('afterbegin', navigatorHTML);
            
            // Configurar los eventos de los botones
            document.getElementById(`${this.container.id}-prev`).addEventListener('click', () => {
                this.changePage(-1);
            });
            
            document.getElementById(`${this.container.id}-next`).addEventListener('click', () => {
                this.changePage(1);
            });
            
            this.pageNumElement = document.getElementById(`${this.container.id}-page-num`);
            this.pageCountElement = document.getElementById(`${this.container.id}-page-count`);
        }
    }
    
    /**
     * Carga un documento PDF
     * @param {string} url - URL del documento PDF
     * @param {Function} callback - Función a ejecutar cuando el PDF está cargado
     */
    loadDocument(url, callback) {
        pdfjsLib.getDocument(url).promise.then(pdfDoc => {
            this.pdfDoc = pdfDoc;
            this.numPages = pdfDoc.numPages;
            
            if (this.options.pageNavigator) {
                this.pageCountElement.textContent = this.numPages;
            }
            
            // Renderizar páginas
            if (this.options.renderAllPages) {
                // Limpiar páginas existentes
                this.pagesContainer.innerHTML = '';
                this.canvasArray = [];
                this.annotationLayerArray = [];
                
                for (let i = 1; i <= this.numPages; i++) {
                    this.renderPage(i);
                }
            } else {
                this.renderPage(this.currentPage);
            }
            
            if (typeof callback === 'function') {
                callback(this.pdfDoc);
            }
        }).catch(error => {
            console.error('Error al cargar el PDF:', error);
        });
    }
    
    /**
     * Renderiza una página específica
     * @param {number} pageNumber - Número de página a renderizar
     * @param {Function} callback - Función a ejecutar cuando la página está renderizada
     */
    renderPage(pageNumber, callback) {
        if (this.pageRendering) {
            // Si hay otra página renderizándose, poner esta en cola
            this.pageNumPending = pageNumber;
            return;
        }
        
        this.pageRendering = true;
        this.currentPage = pageNumber;
        
        if (this.options.pageNavigator) {
            this.pageNumElement.textContent = pageNumber;
        }
        
        // Obtener la página del documento
        this.pdfDoc.getPage(pageNumber).then(page => {
            // Crear o reutilizar el canvas para esta página
            let canvas, annotationLayer;
            const pageIndex = pageNumber - 1;
            
            if (this.canvasArray[pageIndex]) {
                canvas = this.canvasArray[pageIndex];
                annotationLayer = this.annotationLayerArray[pageIndex];
            } else {
                // Crear contenedor de página
                const pageContainer = document.createElement('div');
                pageContainer.className = 'pdf-page';
                pageContainer.dataset.pageNumber = pageNumber;
                pageContainer.style.marginBottom = `${this.options.pageGap}px`;
                
                // Crear canvas
                canvas = document.createElement('canvas');
                canvas.className = 'pdf-canvas';
                pageContainer.appendChild(canvas);
                
                // Crear capa de anotaciones si está habilitada
                if (this.options.enableAnnotations) {
                    annotationLayer = document.createElement('div');
                    annotationLayer.className = 'pdf-annotation-layer';
                    pageContainer.appendChild(annotationLayer);
                    this.annotationLayerArray[pageIndex] = annotationLayer;
                }
                
                // Añadir al contenedor
                if (this.options.renderAllPages) {
                    this.pagesContainer.appendChild(pageContainer);
                } else {
                    this.pagesContainer.innerHTML = '';
                    this.pagesContainer.appendChild(pageContainer);
                }
                
                this.canvasArray[pageIndex] = canvas;
            }
            
            // Escalar la página según el factor de escala
            const viewport = page.getViewport({ scale: this.scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            // Renderizar la página en el canvas
            const renderContext = {
                canvasContext: canvas.getContext('2d'),
                viewport: viewport
            };
            
            const renderTask = page.render(renderContext);
            
            // Esperar a que termine de renderizar
            renderTask.promise.then(() => {
                this.pageRendering = false;
                
                // Si hay otra página en cola, renderizarla
                if (this.pageNumPending !== null) {
                    this.renderPage(this.pageNumPending);
                    this.pageNumPending = null;
                }
                
                // Renderizar anotaciones si está habilitado
                if (this.options.enableAnnotations && annotationLayer) {
                    page.getAnnotations().then(annotations => {
                        const annotationContext = {
                            viewport: viewport.clone({ dontFlip: true }),
                            div: annotationLayer,
                            annotations: annotations,
                            page: page,
                            linkService: {
                                externalLinkTarget: 2, // Abrir en nueva pestaña
                                getDestinationHash: function() { return ''; },
                                getAnchorUrl: function() { return ''; },
                                navigateTo: function() {}
                            }
                        };
                        
                        // Limpiar capa anterior
                        annotationLayer.innerHTML = '';
                        
                        // Usar la API de anotaciones de PDF.js
                        pdfjsLib.AnnotationLayer.render(annotationContext);
                    });
                }
                
                if (typeof callback === 'function') {
                    callback();
                }
            });
        });
    }
    
    /**
     * Cambia a otra página
     * @param {number} offset - Desplazamiento relativo (+1 siguiente, -1 anterior)
     */
    changePage(offset) {
        const newPage = this.currentPage + offset;
        
        if (newPage < 1 || newPage > this.numPages) {
            return;
        }
        
        this.renderPage(newPage);
    }
    
    /**
     * Cambia el factor de escala y vuelve a renderizar
     * @param {number} newScale - Nuevo factor de escala
     */
    setScale(newScale) {
        if (newScale === this.scale) {
            return;
        }
        
        this.scale = newScale;
        
        // Volver a renderizar la página actual o todas las páginas
        if (this.options.renderAllPages) {
            // Limpiar y renderizar todas las páginas
            this.pagesContainer.innerHTML = '';
            this.canvasArray = [];
            this.annotationLayerArray = [];
            
            for (let i = 1; i <= this.numPages; i++) {
                this.renderPage(i);
            }
        } else {
            this.renderPage(this.currentPage);
        }
    }
    
    /**
     * Ir a una página específica
     * @param {number} pageNumber - Número de página
     */
    goToPage(pageNumber) {
        if (pageNumber < 1 || pageNumber > this.numPages) {
            return;
        }
        
        this.renderPage(pageNumber);
    }
}

/**
 * Función para vista previa rápida de un PDF
 * @param {string} url - URL del PDF
 * @param {string} containerId - ID del contenedor
 * @param {Object} options - Opciones adicionales
 */
function quickPDFPreview(url, containerId, options = {}) {
    const viewer = new PDFViewer(containerId, options);
    viewer.loadDocument(url);
    return viewer;
}

// Exportar funciones/clases para uso global
window.PDFViewer = PDFViewer;
window.quickPDFPreview = quickPDFPreview;