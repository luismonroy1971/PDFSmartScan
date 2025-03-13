/**
 * Script para la selección interactiva de áreas en un PDF
 */
document.addEventListener('DOMContentLoaded', function() {
    const pdfContainer = document.getElementById('pdf-container');
    const pdfPage = document.getElementById('pdf-page');
    const btnAddArea = document.getElementById('btn-add-area');
    const btnSaveAreas = document.getElementById('btn-save-areas');
    const btnNextPage = document.getElementById('btn-next-page');
    const btnPrevPage = document.getElementById('btn-prev-page');
    const pageNumSpan = document.getElementById('page-num');
    const totalPagesSpan = document.getElementById('page-total');
    const areasList = document.getElementById('areas-list');
    const documentIdInput = document.getElementById('document-id');
    
    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 1.5;
    let canvas = document.createElement('canvas');
    let ctx = canvas.getContext('2d');
    pdfPage.appendChild(canvas);
    
    // Lista de áreas seleccionadas
    let areas = [];
    
    // Estado de dibujo
    let isDrawing = false;
    let startX, startY;
    let selectedAreaIndex = -1;
    let currentArea = null;
    let editingColumnName = false;
    
    // Cargar el PDF
    const documentId = documentIdInput.value;
    const url = `/uploads/${documentId}.pdf`;
    
    // Inicializar PDF.js
    pdfjsLib.getDocument(url).promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPagesSpan.textContent = pdf.numPages;
        
        // Cargar la primera página
        renderPage(pageNum);
        
        // Cargar áreas guardadas (si existen)
        loadSavedAreas();
    });
    
    /**
     * Renderiza una página del PDF
     * @param {number} num Número de página
     */
    function renderPage(num) {
        pageRendering = true;
        
        // Actualizar indicador de página
        pageNumSpan.textContent = num;
        
        // Obtener la página
        pdfDoc.getPage(num).then(function(page) {
            const viewport = page.getViewport({ scale: scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            // Renderizar PDF en el canvas
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            const renderTask = page.render(renderContext);
            
            renderTask.promise.then(function() {
                pageRendering = false;
                
                // Dibujar áreas existentes
                drawAreas();
                
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            });
        });
    }
    
    /**
     * Cambiar de página
     * @param {number} delta Cambio de página (1 o -1)
     */
    function queueRenderPage(delta) {
        if (pageRendering) {
            pageNumPending = pageNum + delta;
        } else {
            pageNum += delta;
            if (pageNum <= 0) {
                pageNum = 1;
            } else if (pageNum > pdfDoc.numPages) {
                pageNum = pdfDoc.numPages;
            }
            renderPage(pageNum);
        }
    }
    
    // Eventos de navegación
    btnPrevPage.addEventListener('click', function() {
        queueRenderPage(-1);
    });
    
    btnNextPage.addEventListener('click', function() {
        queueRenderPage(1);
    });
    
    // Evento para añadir una nueva área
    btnAddArea.addEventListener('click', function() {
        startDrawing();
    });
    
    /**
     * Inicia el modo de dibujo
     */
    function startDrawing() {
        isDrawing = true;
        canvas.style.cursor = 'crosshair';
        
        // Cambiar el botón
        btnAddArea.textContent = 'Dibujando...';
        btnAddArea.disabled = true;
    }
    
    /**
     * Dibuja todas las áreas en el canvas
     */
    function drawAreas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Volver a renderizar la página
        pdfDoc.getPage(pageNum).then(function(page) {
            const viewport = page.getViewport({ scale: scale });
            
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            page.render(renderContext).promise.then(function() {
                // Dibujar cada área
                areas.forEach((area, index) => {
                    if (area.page === pageNum) {
                        drawArea(area, index === selectedAreaIndex);
                    }
                });
            });
        });
    }
    
    /**
     * Dibuja un área en el canvas
     * @param {Object} area Área a dibujar
     * @param {boolean} isSelected Si el área está seleccionada
     */
    function drawArea(area, isSelected) {
        if (area.page !== pageNum) return;
        
        ctx.strokeStyle = isSelected ? '#FF0000' : '#00FF00';
        ctx.lineWidth = 2;
        ctx.strokeRect(area.x, area.y, area.width, area.height);
        
        // Dibujar nombre de columna
        ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
        ctx.fillRect(area.x, area.y - 20, 150, 20);
        ctx.fillStyle = '#000000';
        ctx.font = '12px Arial';
        ctx.fillText(area.column_name || 'Columna sin nombre', area.x + 5, area.y - 5);
    }
    
    /**
     * Actualiza la lista de áreas en el DOM
     */
    function updateAreasList() {
        // Limpiar lista
        areasList.innerHTML = '';
        
        // Añadir cada área
        areas.forEach((area, index) => {
            const li = document.createElement('li');
            li.className = 'area-item';
            
            // Añadir un input para el nombre de columna
            const input = document.createElement('input');
            input.type = 'text';
            input.value = area.column_name || `Columna ${index + 1}`;
            input.className = 'column-name-input';
            
            // Guardar cambios al salir del input
            input.addEventListener('blur', function() {
                if (input.value.trim() !== '') {
                    areas[index].column_name = input.value.trim();
                    drawAreas();
                }
            });
            
            // Añadir información del área
            const info = document.createElement('span');
            info.textContent = ` (Página ${area.page}, ${Math.round(area.width)}x${Math.round(area.height)})`;
            
            // Botón para eliminar área
            const btnDelete = document.createElement('button');
            btnDelete.textContent = 'Eliminar';
            btnDelete.className = 'btn-delete-area';
            btnDelete.addEventListener('click', function() {
                areas.splice(index, 1);
                updateAreasList();
                drawAreas();
            });
            
            // Añadir elementos a la lista
            li.appendChild(input);
            li.appendChild(info);
            li.appendChild(btnDelete);
            
            // Seleccionar el área al hacer clic
            li.addEventListener('click', function(e) {
                if (e.target !== input && e.target !== btnDelete) {
                    selectedAreaIndex = index;
                    
                    // Si el área está en otra página, cambiar a esa página
                    if (areas[index].page !== pageNum) {
                        pageNum = areas[index].page;
                        renderPage(pageNum);
                    } else {
                        drawAreas();
                    }
                }
            });
            
            areasList.appendChild(li);
        });
    }
    
    /**
     * Guarda las áreas en el servidor
     */
    function saveAreas() {
        if (areas.length === 0) {
            alert('No hay áreas para guardar');
            return;
        }
        
        // Preparar datos para enviar
        const data = {
            document_id: documentId,
            areas: areas
        };
        
        // Enviar datos al servidor
        fetch('/api/save-areas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Áreas guardadas correctamente');
                window.location.href = `/excel/configure/${documentId}`;
            } else {
                alert('Error al guardar áreas: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar áreas');
        });
    }
    
    /**
     * Carga áreas guardadas del servidor
     */
    function loadSavedAreas() {
        fetch(`/api/get-areas/${documentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.areas.length > 0) {
                areas = data.areas.map(area => ({
                    x: parseFloat(area.x_pos),
                    y: parseFloat(area.y_pos),
                    width: parseFloat(area.width),
                    height: parseFloat(area.height),
                    page: parseInt(area.page_number),
                    column_name: area.column_name
                }));
                
                updateAreasList();
                drawAreas();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Eventos de dibujo en el canvas
    canvas.addEventListener('mousedown', function(e) {
        if (!isDrawing) return;
        
        const rect = canvas.getBoundingClientRect();
        startX = e.clientX - rect.left;
        startY = e.clientY - rect.top;
        
        currentArea = {
            x: startX,
            y: startY,
            width: 0,
            height: 0,
            page: pageNum,
            column_name: `Columna ${areas.length + 1}`
        };
    });
    
    canvas.addEventListener('mousemove', function(e) {
        if (!isDrawing || !currentArea) return;
        
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Actualizar dimensiones
        currentArea.width = x - startX;
        currentArea.height = y - startY;
        
        // Redibujar
        drawAreas();
        
        // Dibujar área actual
        ctx.strokeStyle = '#0000FF';
        ctx.lineWidth = 2;
        ctx.strokeRect(currentArea.x, currentArea.y, currentArea.width, currentArea.height);
    });
    
    canvas.addEventListener('mouseup', function() {
        if (!isDrawing || !currentArea) return;
        
        // Normalizar valores negativos
        if (currentArea.width < 0) {
            currentArea.x += currentArea.width;
            currentArea.width = Math.abs(currentArea.width);
        }
        
        if (currentArea.height < 0) {
            currentArea.y += currentArea.height;
            currentArea.height = Math.abs(currentArea.height);
        }
        
        // Añadir el área a la lista
        areas.push(currentArea);
        selectedAreaIndex = areas.length - 1;
        
        // Reiniciar estado
        isDrawing = false;
        currentArea = null;
        btnAddArea.textContent = 'Añadir Área';
        btnAddArea.disabled = false;
        canvas.style.cursor = 'default';
        
        // Actualizar lista y redibujado
        updateAreasList();
        drawAreas();
    });
    
    // Evento para guardar todas las áreas
    btnSaveAreas.addEventListener('click', function() {
        saveAreas();
    });
    
    // Permitir seleccionar un área haciendo clic dentro
    canvas.addEventListener('click', function(e) {
        if (isDrawing) return;
        
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Buscar si hay un área en esa posición
        let found = false;
        areas.forEach((area, index) => {
            if (area.page === pageNum && 
                x >= area.x && x <= area.x + area.width &&
                y >= area.y && y <= area.y + area.height) {
                selectedAreaIndex = index;
                found = true;
                drawAreas();
            }
        });
        
        // Si no se encontró un área, deseleccionar
        if (!found) {
            selectedAreaIndex = -1;
            drawAreas();
        }
    });
    
    // Manejar teclas para navegación y eliminar áreas
    document.addEventListener('keydown', function(e) {
        // Navegación con flechas izquierda/derecha
        if (e.key === 'ArrowLeft') {
            queueRenderPage(-1);
        } else if (e.key === 'ArrowRight') {
            queueRenderPage(1);
        }
        
        // Eliminar área seleccionada con Delete o Backspace
        if ((e.key === 'Delete' || e.key === 'Backspace') && selectedAreaIndex >= 0) {
            areas.splice(selectedAreaIndex, 1);
            selectedAreaIndex = -1;
            updateAreasList();
            drawAreas();
        }
    });
});