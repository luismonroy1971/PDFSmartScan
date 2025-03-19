<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Visualizador de PDF</h3>
                    <div>
                        <span id="pageInfo">Página: <span id="currentPage">1</span> / <span id="totalPages">?</span></span>
                        <div class="btn-group ms-3">
                            <button id="prevPage" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </button>
                            <button id="nextPage" class="btn btn-sm btn-outline-secondary">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="btn-group ms-3">
                            <button id="zoomOut" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button id="zoomIn" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="pdfContainer" class="pdf-container">
                        <canvas id="pdfCanvas"></canvas>
                        <div id="selectionLayer" class="selection-layer"></div>
                    </div>
                    <div id="loadingIndicator" class="loading-indicator">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando documento...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Información del Documento</h4>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($document['original_filename']) ?></p>
                    <p><strong>Tamaño:</strong> <?= formatFileSize($document['file_size']) ?></p>
                    <p><strong>Subido el:</strong> <?= formatDate($document['created_at']) ?></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Áreas Seleccionadas</h4>
                    <button id="addAreaBtn" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Añadir Área
                    </button>
                </div>
                <div class="card-body">
                    <div id="areasList" class="areas-list">
                        <?php if (empty($areas)): ?>
                            <div class="text-center text-muted">
                                <p>No hay áreas seleccionadas</p>
                                <p>Haz clic en "Añadir Área" para comenzar</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($areas as $area): ?>
                                <div class="area-item" data-id="<?= $area['id'] ?>">
                                    <div class="area-header">
                                        <span class="area-name"><?= htmlspecialchars($area['column_name']) ?></span>
                                        <div class="area-actions">
                                            <button class="btn btn-sm btn-outline-primary edit-area-btn">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-area-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="area-details">
                                        <small>Página: <?= $area['page_number'] ?></small>
                                        <small>Posición: (<?= $area['x_pos'] ?>, <?= $area['y_pos'] ?>)</small>
                                        <small>Tamaño: <?= $area['width'] ?> x <?= $area['height'] ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-between">
                        <button id="saveAreasBtn" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Áreas
                        </button>
                        <a href="/ocr/process/<?= $document['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-cogs"></i> Procesar OCR
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="/documents" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Documentos
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal para añadir/editar área -->
<div class="modal fade" id="areaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="areaModalTitle">Añadir Área</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="areaForm">
                    <input type="hidden" id="areaId" name="id" value="">
                    <input type="hidden" id="areaPage" name="page_number" value="1">
                    <input type="hidden" id="areaX" name="x_pos" value="0">
                    <input type="hidden" id="areaY" name="y_pos" value="0">
                    <input type="hidden" id="areaWidth" name="width" value="0">
                    <input type="hidden" id="areaHeight" name="height" value="0">
                    
                    <div class="mb-3">
                        <label for="columnName" class="form-label">Nombre de Columna en Excel</label>
                        <input type="text" class="form-control" id="columnName" name="column_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <p><strong>Página:</strong> <span id="displayPage">1</span></p>
                        <p><strong>Posición:</strong> X: <span id="displayX">0</span>, Y: <span id="displayY">0</span></p>
                        <p><strong>Dimensiones:</strong> <span id="displayWidth">0</span> x <span id="displayHeight">0</span></p>
                    </div>
                    
                    <div class="form-text mb-3">
                        Para seleccionar un área, haz clic y arrastra sobre el documento.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveAreaBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<style>
.pdf-container {
    position: relative;
    overflow: auto;
    max-height: 80vh;
    background-color: #525659;
    text-align: center;
}

#pdfCanvas {
    display: inline-block;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

.selection-layer {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.selection-rect {
    position: absolute;
    border: 2px dashed #ff5722;
    background-color: rgba(255, 87, 34, 0.2);
    pointer-events: none;
}

.area-rect {
    position: absolute;
    border: 2px solid #4caf50;
    background-color: rgba(76, 175, 80, 0.2);
    cursor: pointer;
    pointer-events: all;
}

.loading-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    background-color: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.areas-list {
    max-height: 300px;
    overflow-y: auto;
}

.area-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    background-color: #f9f9f9;
}

.area-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.area-name {
    font-weight: bold;
}

.area-details {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.8rem;
    color: #666;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
<script>
// Configurar worker de PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';

$(document).ready(function() {
    // Variables globales
    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 1.5;
    let canvas = document.getElementById('pdfCanvas');
    let ctx = canvas.getContext('2d');
    let documentId = <?= $document['id'] ?>;
    let pdfPath = '<?= $document['file_path'] ?>';
    let isDrawing = false;
    let startX, startY, endX, endY;
    let selectionLayer = document.getElementById('selectionLayer');
    let currentSelection = null;
    let areas = <?= json_encode($areas ?? []) ?>;
    let editingAreaId = null;
    let areaModal = new bootstrap.Modal(document.getElementById('areaModal'));
    
    // Cargar PDF
    pdfjsLib.getDocument('/public' + pdfPath).promise.then(function(pdf) {
        pdfDoc = pdf;
        document.getElementById('totalPages').textContent = pdf.numPages;
        document.getElementById('loadingIndicator').style.display = 'none';
        
        // Renderizar primera página
        renderPage(pageNum);
        
        // Mostrar áreas existentes
        renderAreas();
    }).catch(function(error) {
        console.error('Error al cargar el PDF:', error);
        document.getElementById('loadingIndicator').innerHTML = 
            '<div class="alert alert-danger">Error al cargar el documento PDF. Por favor, inténtelo de nuevo.</div>';
    });
    
    // Renderizar una página específica
    function renderPage(num) {
        pageRendering = true;
        
        // Actualizar indicador de página actual
        document.getElementById('currentPage').textContent = num;
        
        // Obtener página
        pdfDoc.getPage(num).then(function(page) {
            // Determinar escala para ajustar a la ventana
            const viewport = page.getViewport({ scale: scale });
            
            // Ajustar tamaño del canvas
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            // Renderizar PDF en el canvas
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            const renderTask = page.render(renderContext);
            
            // Esperar a que termine el renderizado
            renderTask.promise.then(function() {
                pageRendering = false;
                
                // Si hay una página pendiente, renderizarla
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
                
                // Renderizar áreas para esta página
                renderAreas();
            });
        });
    }
    
    // Cambiar página
    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }
    
    // Ir a página anterior
    document.getElementById('prevPage').addEventListener('click', function() {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
    });
    
    // Ir a página siguiente
    document.getElementById('nextPage').addEventListener('click', function() {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
    });
    
    // Zoom in
    document.getElementById('zoomIn').addEventListener('click', function() {
        scale *= 1.2;
        queueRenderPage(pageNum);
    });
    
    // Zoom out
    document.getElementById('zoomOut').addEventListener('click', function() {
        scale /= 1.2;
        queueRenderPage(pageNum);
    });
    
    // Iniciar selección de área
    canvas.addEventListener('mousedown', function(e) {
        // Solo permitir selección si estamos en modo de añadir área
        if (!document.getElementById('addAreaBtn').classList.contains('active')) return;
        
        const rect = canvas.getBoundingClientRect();
        startX = e.clientX - rect.left;
        startY = e.clientY - rect.top;
        isDrawing = true;
        
        // Crear elemento de selección
        currentSelection = document.createElement('div');
        currentSelection.className = 'selection-rect';
        currentSelection.style.left = startX + 'px';
        currentSelection.style.top = startY + 'px';
        selectionLayer.appendChild(currentSelection);
    });
    
    // Actualizar selección mientras se arrastra
    canvas.addEventListener('mousemove', function(e) {
        if (!isDrawing) return;
        
        const rect = canvas.getBoundingClientRect();
        endX = e.clientX - rect.left;
        endY = e.clientY - rect.top;
        
        // Calcular dimensiones y posición
        const width = Math.abs(endX - startX);
        const height = Math.abs(endY - startY);
        const left = Math.min(startX, endX);
        const top = Math.min(startY, endY);
        
        // Actualizar elemento de selección
        currentSelection.style.width = width + 'px';
        currentSelection.style.height = height + 'px';
        currentSelection.style.left = left + 'px';
        currentSelection.style.top = top + 'px';
        
        // Actualizar campos del formulario
        document.getElementById('areaX').value = Math.round(left);
        document.getElementById('areaY').value = Math.round(top);
        document.getElementById('areaWidth').value = Math.round(width);
        document.getElementById('areaHeight').value = Math.round(height);
        document.getElementById('areaPage').value = pageNum;
        
        // Actualizar campos de visualización
        document.getElementById('displayX').textContent = Math.round(left);
        document.getElementById('displayY').textContent = Math.round(top);
        document.getElementById('displayWidth').textContent = Math.round(width);
        document.getElementById('displayHeight').textContent = Math.round(height);
        document.getElementById('displayPage').textContent = pageNum;
    });
    
    // Finalizar selección
    canvas.addEventListener('mouseup', function() {
        if (!isDrawing) return;
        isDrawing = false;
        
        // Mostrar modal para configurar el área
        document.getElementById('areaModalTitle').textContent = 'Añadir Área';
        document.getElementById('columnName').value = '';
        document.getElementById('areaId').value = '';
        areaModal.show();
    });
    
    // Botón para activar modo de selección
    document.getElementById('addAreaBtn').addEventListener('click', function() {
        this.classList.toggle('active');
        if (this.classList.contains('active')) {
            this.textContent = 'Cancelar Selección';
            this.classList.replace('btn-primary', 'btn-danger');
            canvas.style.cursor = 'crosshair';
        } else {
            this.textContent = 'Añadir Área';
            this.classList.replace('btn-danger', 'btn-primary');
            canvas.style.cursor = 'default';
            
            // Limpiar selección actual
            if (currentSelection) {
                selectionLayer.removeChild(currentSelection);
                currentSelection = null;
            }
        }
    });
    
    // Guardar área seleccionada
    document.getElementById('saveAreaBtn').addEventListener('click', function() {
        const areaData = {
            id: document.getElementById('areaId').value || null,
            document_id: documentId,
            column_name: document.getElementById('columnName').value,
            x_pos: parseInt(document.getElementById('areaX').value),
            y_pos: parseInt(document.getElementById('areaY').value),
            width: parseInt(document.getElementById('areaWidth').value),
            height: parseInt(document.getElementById('areaHeight').value),
            page_number: parseInt(document.getElementById('areaPage').value)
        };
        
        // Validar datos
        if (!areaData.column_name) {
            alert('Por favor, ingrese un nombre de columna');
            return;
        }
        
        // Enviar datos al servidor
        $.ajax({
            url: '/documents/save-area',
            type: 'POST',
            data: areaData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Actualizar lista de áreas
                    if (areaData.id) {
                        // Actualizar área existente
                        for (let i = 0; i < areas.length; i++) {
                            if (areas[i].id == areaData.id) {
                                areas[i] = {...areaData, id: response.area_id};
                                break;
                            }
                        }
                    } else {
                        // Añadir nueva área
                        areas.push({...areaData, id: response.area_id});
                    }
                    
                    // Actualizar visualización
                    renderAreasList();
                    renderAreas();
                    
                    // Limpiar selección
                    if (currentSelection) {
                        selectionLayer.removeChild(currentSelection);
                        currentSelection = null;
                    }
                    
                    // Desactivar modo de selección
                    document.getElementById('addAreaBtn').classList.remove('active');
                    document.getElementById('addAreaBtn').textContent = 'Añadir Área';
                    document.getElementById('addAreaBtn').classList.replace('btn-danger', 'btn-primary');
                    canvas.style.cursor = 'default';
                    
                    // Cerrar modal
                    areaModal.hide();
                } else {
                    alert('Error al guardar el área: ' + response.message);
                }
            },
            error: function() {
                alert('Error de comunicación con el servidor');
            }
        });
    });
    
    // Renderizar áreas existentes
    function renderAreas() {
        // Limpiar áreas existentes
        const existingAreas = selectionLayer.querySelectorAll('.area-rect');
        existingAreas.forEach(area => area.remove());
        
        // Mostrar áreas de la página actual
        areas.filter(area => area.page_number == pageNum).forEach(area => {
            const areaElement = document.createElement('div');
            areaElement.className = 'area-rect';
            areaElement.style.left = area.x_pos + 'px';
            areaElement.style.top = area.y_pos + 'px';
            areaElement.style.width = area.width + 'px';
            areaElement.style.height = area.height + 'px';
            areaElement.dataset.id = area.id;
            areaElement.title = area.column_name;
            
            // Añadir etiqueta con nombre
            const label = document.createElement('div');
            label.className = 'area-label';
            label.textContent = area.column_name;
            areaElement.appendChild(label);
            
            // Añadir al layer de selección
            selectionLayer.appendChild(areaElement);
            
            // Evento para editar área
            areaElement.addEventListener('click', function() {
                editArea(area.id);
            });
        });
    }
    
    // Renderizar lista de áreas
    function renderAreasList() {
        const areasList = document.getElementById('areasList');
        
        if (areas.length === 0) {
            areasList.innerHTML = `
                <div class="text-center text-muted">
                    <p>No hay áreas seleccionadas</p>
                    <p>Haz clic en "Añadir Área" para comenzar</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        areas.forEach(area => {
            html += `
                <div class="area-item" data-id="${area.id}">
                    <div class="area-header">
                        <span class="area-name">${area.column_name}</span>
                        <div class="area-actions">
                            <button class="btn btn-sm btn-outline-primary edit-area-btn" data-id="${area.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-area-btn" data-id="${area.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="area-details">
                        <small>Página: ${area.page_number}</small>
                        <small>Posición: (${area.x_pos}, ${area.y_pos})</small>
                        <small>Tamaño: ${area.width} x ${area.height}</small>
                    </div>
                </div>
            `;
        });
        
        areasList.innerHTML = html;
        
        // Añadir eventos a botones
        document.querySelectorAll('.edit-area-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                editArea(this.dataset.id);
            });
        });
        
        document.querySelectorAll('.delete-area-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteArea(this.dataset.id);
            });
        });
    }
    
    // Editar área existente
    function editArea(areaId) {
        const area = areas.find(a => a.id == areaId);
        if (!area) return;
        
        // Ir a la página donde está el área
        if (pageNum != area.page_number) {
            pageNum = area.page_number;
            queueRenderPage(pageNum);
        }
        
        // Llenar formulario con datos del área
        document.getElementById('areaId').value = area.id;
        document.getElementById('columnName').value = area.column_name;
        document.getElementById('areaX').value = area.x_pos;
        document.getElementById('areaY').value = area.y_pos;
        document.getElementById('areaWidth').value = area.width;
        document.getElementById('areaHeight').value = area.height;
        document.getElementById('areaPage').value = area.page_number;
        
        // Actualizar campos de visualización
        document.getElementById('displayX').textContent = area.x_pos;
        document.getElementById('displayY').textContent = area.y_pos;
        document.getElementById('displayWidth').textContent = area.width;
        document.getElementById('displayHeight').textContent = area.height;
        document.getElementById('displayPage').textContent = area.page_number;
        
        // Actualizar título del modal
        document.getElementById('areaModalTitle').textContent = 'Editar Área';
        
        // Mostrar modal
        areaModal.show();
        
        // Crear selección visual
        if (currentSelection) {
            selectionLayer.removeChild(currentSelection);
        }