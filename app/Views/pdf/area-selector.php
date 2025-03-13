<?php include_once VIEWS_PATH . '/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Seleccionar Áreas de Interés</h4>
                    <div>
                        <a href="/dashboard" class="btn btn-light me-2">Volver al Dashboard</a>
                        <button id="btn-save-areas" class="btn btn-success">Guardar Áreas</button>
                    </div>
                </div>
                <div class="card-body">
                    <p>Selecciona las áreas del documento de las que deseas extraer información. Cada área seleccionada se convertirá en una columna en el archivo Excel.</p>
                    
                    <?php if (Session::hasFlash('error')): ?>
                        <div class="alert alert-danger">
                            <?= Session::getFlash('error') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (Session::hasFlash('success')): ?>
                        <div class="alert alert-success">
                            <?= Session::getFlash('success') ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong>Instrucciones:</strong>
                        <ul>
                            <li>Haz clic en el botón "Añadir Área" y dibuja un rectángulo sobre el texto que deseas extraer.</li>
                            <li>Asigna un nombre a cada área seleccionada, este nombre se usará como encabezado de columna en Excel.</li>
                            <li>Puedes seleccionar múltiples áreas del documento, incluso en diferentes páginas.</li>
                            <li>Navega entre las páginas con los botones "Anterior" y "Siguiente" o con las flechas del teclado.</li>
                            <li>Puedes eliminar un área seleccionada con la tecla "Delete" o haciendo clic en el botón "Eliminar".</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Vista del Documento</h5>
                        <div>
                            <button id="btn-prev-page" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Anterior
                            </button>
                            <span class="mx-2">
                                Página <span id="page-num">1</span> de <span id="page-total">1</span>
                            </span>
                            <button id="btn-next-page" class="btn btn-sm btn-outline-primary">
                                Siguiente <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body text-center">
                    <div id="pdf-container" class="pdf-container">
                        <div id="pdf-page" class="pdf-page"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Áreas Seleccionadas</h5>
                    <button id="btn-add-area" class="btn btn-sm btn-primary">Añadir Área</button>
                </div>
                <div class="card-body">
                    <div class="areas-container">
                        <ul id="areas-list" class="list-group">
                            <!-- Las áreas seleccionadas se mostrarán aquí -->
                            <li class="list-group-item text-center text-muted">
                                No hay áreas seleccionadas
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Procesamiento OCR</h5>
                </div>
                <div class="card-body">
                    <p class="small">El sistema utilizará reconocimiento óptico de caracteres (OCR) para extraer texto de las áreas seleccionadas, incluyendo texto manuscrito.</p>
                    <div class="d-grid gap-2">
                        <button id="btn-test-ocr" class="btn btn-warning">Probar Extracción</button>
                    </div>
                    <div id="ocr-results" class="mt-3 small d-none">
                        <h6>Resultados de la prueba:</h6>
                        <div class="ocr-results-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Input oculto para el ID del documento -->
<input type="hidden" id="document-id" value="<?= $document['id'] ?>">

<!-- Scripts necesarios -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    // Configuración de PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
</script>
<script src="/js/area-selector.js"></script>

<!-- Script para probar OCR -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnTestOcr = document.getElementById('btn-test-ocr');
    const ocrResults = document.getElementById('ocr-results');
    const ocrResultsContent = document.querySelector('.ocr-results-content');
    const documentId = document.getElementById('document-id').value;
    
    btnTestOcr.addEventListener('click', function() {
        // Verificar si hay áreas seleccionadas
        const areasList = document.getElementById('areas-list');
        if (areasList.children.length === 1 && areasList.children[0].classList.contains('text-muted')) {
            alert('No hay áreas seleccionadas para probar OCR');
            return;
        }
        
        btnTestOcr.disabled = true;
        btnTestOcr.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Enviar solicitud al servidor para procesar OCR
        fetch(`/api/process-ocr/${documentId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            btnTestOcr.disabled = false;
            btnTestOcr.innerHTML = 'Probar Extracción';
            
            if (data.success) {
                ocrResults.classList.remove('d-none');
                
                // Mostrar resultados
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                html += '<thead><tr><th>Columna</th><th>Texto Extraído</th></tr></thead><tbody>';
                
                for (const [column, text] of Object.entries(data.results)) {
                    html += `<tr><td>${column}</td><td>${text || '<em>No se pudo extraer texto</em>'}</td></tr>`;
                }
                
                html += '</tbody></table></div>';
                ocrResultsContent.innerHTML = html;
            } else {
                alert('Error al procesar OCR: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btnTestOcr.disabled = false;
            btnTestOcr.innerHTML = 'Probar Extracción';
            alert('Error al procesar OCR. Por favor, inténtalo de nuevo.');
        });
    });
});
</script>

<style>
.pdf-container {
    overflow: auto;
    max-height: 800px;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.pdf-page {
    display: inline-block;
    margin: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    background-color: white;
}

.areas-container {
    max-height: 400px;
    overflow-y: auto;
}

.area-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.column-name-input {
    width: 120px;
    padding: 2px 5px;
    border: 1px solid #ccc;
    border-radius: 3px;
}

.btn-delete-area {
    padding: 2px 5px;
    font-size: 12px;
    color: white;
    background-color: #dc3545;
    border: none;
    border-radius: 3px;
}
</style>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>