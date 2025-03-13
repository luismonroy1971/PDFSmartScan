<?php include_once VIEWS_PATH . '/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/pdf/view/<?= $document['id'] ?>">Documento</a></li>
                    <li class="breadcrumb-item"><a href="/pdf/area-selector/<?= $document['id'] ?>">Selección de áreas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Configurar Excel</li>
                </ol>
            </nav>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Configuración de exportación a Excel</h4>
                    <div>
                        <a href="/pdf/area-selector/<?= $document['id'] ?>" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-arrow-left"></i> Volver a selección
                        </a>
                        <a href="/dashboard" class="btn btn-light btn-sm">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Documento: <?= htmlspecialchars($document['original_filename']) ?></h5>
                    
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
                    
                    <?php if (empty($areas)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No hay áreas seleccionadas para este documento.
                            <a href="/pdf/area-selector/<?= $document['id'] ?>" class="alert-link">Volver a selección de áreas</a>.
                        </div>
                    <?php else: ?>
                        <p class="mb-4">
                            Configure los nombres de las columnas y las opciones de exportación a Excel. 
                            Estos nombres aparecerán como encabezados en el archivo Excel generado.
                        </p>
                        
                        <form action="/excel/configure" method="POST" id="configure-form">
                            <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                            
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Nombres de columnas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($areas as $area): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="mb-2">
                                                            <span class="badge bg-info">
                                                                Área #<?= $area['id'] ?>
                                                            </span>
                                                            <span class="badge bg-secondary">
                                                                Página <?= $area['page_number'] ?>
                                                            </span>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="column_<?= $area['id'] ?>">Nombre de columna</label>
                                                            <input type="text" class="form-control" 
                                                                id="column_<?= $area['id'] ?>" 
                                                                name="columns[<?= $area['id'] ?>]" 
                                                                value="<?= htmlspecialchars($area['column_name']) ?>" 
                                                                required>
                                                        </div>
                                                        <div class="small text-muted mt-2">
                                                            Coordenadas: 
                                                            X:<?= round($area['x_pos']) ?>, 
                                                            Y:<?= round($area['y_pos']) ?>, 
                                                            W:<?= round($area['width']) ?>, 
                                                            H:<?= round($area['height']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Opciones de exportación</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="export_format">Formato de exportación</label>
                                                <select class="form-select" id="export_format" name="export_format">
                                                    <option value="xlsx" selected>Excel (XLSX)</option>
                                                    <option value="csv">CSV (valores separados por comas)</option>
                                                </select>
                                            </div>
                                            
                                            <div id="csv-options" class="mb-3" style="display: none;">
                                                <div class="form-group mb-2">
                                                    <label for="csv_delimiter">Delimitador CSV</label>
                                                    <select class="form-select" id="csv_delimiter" name="csv_delimiter">
                                                        <option value="," selected>Coma (,)</option>
                                                        <option value=";">Punto y coma (;)</option>
                                                        <option value="|">Barra vertical (|)</option>
                                                        <option value="tab">Tabulador</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="csv_encoding">Codificación de caracteres</label>
                                                    <select class="form-select" id="csv_encoding" name="csv_encoding">
                                                        <option value="UTF-8" selected>UTF-8</option>
                                                        <option value="ISO-8859-1">ISO-8859-1 (Latin 1)</option>
                                                        <option value="windows-1252">Windows-1252</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="add_header" 
                                                       name="add_header" value="1" checked>
                                                <label class="form-check-label" for="add_header">
                                                    Incluir fila de encabezados
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="add_metadata" 
                                                       name="add_metadata" value="1">
                                                <label class="form-check-label" for="add_metadata">
                                                    Incluir hoja con metadatos del documento
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="auto_width" 
                                                       name="auto_width" value="1" checked>
                                                <label class="form-check-label" for="auto_width">
                                                    Ajustar automáticamente ancho de columnas
                                                </label>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="save_as_template" 
                                                       name="save_as_template" value="1">
                                                <label class="form-check-label" for="save_as_template">
                                                    Guardar configuración como plantilla
                                                </label>
                                            </div>
                                            
                                            <div id="template-options" class="mt-3" style="display: none;">
                                                <div class="form-group mb-2">
                                                    <label for="template_name">Nombre de la plantilla</label>
                                                    <input type="text" class="form-control" id="template_name" 
                                                           name="template_name" placeholder="Ej: Formulario Tipo A">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="template_description">Descripción (opcional)</label>
                                                    <textarea class="form-control" id="template_description" 
                                                              name="template_description" rows="2" 
                                                              placeholder="Descripción breve de la plantilla"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="/pdf/area-selector/<?= $document['id'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver a selección
                                </a>
                                
                                <div>
                                    <button type="submit" name="action" value="save" class="btn btn-primary me-2">
                                        <i class="fas fa-save"></i> Guardar configuración
                                    </button>
                                    <button type="submit" name="action" value="download" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Exportar a Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar opciones de CSV
    const exportFormat = document.getElementById('export_format');
    const csvOptions = document.getElementById('csv-options');
    
    if (exportFormat && csvOptions) {
        exportFormat.addEventListener('change', function() {
            if (this.value === 'csv') {
                csvOptions.style.display = 'block';
            } else {
                csvOptions.style.display = 'none';
            }
        });
    }
    
    // Mostrar/ocultar opciones de plantilla
    const saveAsTemplate = document.getElementById('save_as_template');
    const templateOptions = document.getElementById('template-options');
    
    if (saveAsTemplate && templateOptions) {
        saveAsTemplate.addEventListener('change', function() {
            if (this.checked) {
                templateOptions.style.display = 'block';
                document.getElementById('template_name').required = true;
            } else {
                templateOptions.style.display = 'none';
                document.getElementById('template_name').required = false;
            }
        });
    }
    
    // Validación del formulario
    const form = document.getElementById('configure-form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar nombres de columnas
            const columnInputs = document.querySelectorAll('input[name^="columns["]');
            columnInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            // Validar nombre de plantilla si está marcado
            if (saveAsTemplate && saveAsTemplate.checked) {
                const templateName = document.getElementById('template_name');
                if (!templateName.value.trim()) {
                    templateName.classList.add('is-invalid');
                    isValid = false;
                } else {
                    templateName.classList.remove('is-invalid');
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });
    }
});
</script>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>