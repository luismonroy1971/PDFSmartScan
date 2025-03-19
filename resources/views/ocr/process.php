<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Procesamiento OCR - <?= htmlspecialchars($document['original_filename']) ?></h2>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Áreas seleccionadas para procesamiento</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Columna Excel</th>
                                    <th>Página</th>
                                    <th>Posición</th>
                                    <th>Dimensiones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($areas as $area): ?>
                                <tr>
                                    <td><?= htmlspecialchars($area['column_name']) ?></td>
                                    <td><?= htmlspecialchars($area['page_number']) ?></td>
                                    <td>X: <?= htmlspecialchars($area['x_pos']) ?>, Y: <?= htmlspecialchars($area['y_pos']) ?></td>
                                    <td><?= htmlspecialchars($area['width']) ?> x <?= htmlspecialchars($area['height']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Opciones de OCR</h5>
                </div>
                <div class="card-body">
                    <form id="ocrOptionsForm">
                        <input type="hidden" name="document_id" value="<?= htmlspecialchars($document['id']) ?>">
                        
                        <div class="form-group mb-3">
                            <label for="language">Idioma:</label>
                            <select class="form-control" id="language" name="options[lang]">
                                <option value="spa">Español</option>
                                <option value="eng">Inglés</option>
                                <option value="spa+eng">Español + Inglés</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="psm">Modo de segmentación de página:</label>
                            <select class="form-control" id="psm" name="options[psm]">
                                <option value="6">Bloque de texto uniforme (por defecto)</option>
                                <option value="7">Línea de texto única</option>
                                <option value="8">Palabra única</option>
                                <option value="13">Texto sin formato, una línea</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="optimize" name="options[optimize]" value="1" checked>
                            <label class="form-check-label" for="optimize">
                                Optimizar imagen para mejorar reconocimiento
                            </label>
                        </div>
                        
                        <button type="button" id="processOcrBtn" class="btn btn-primary">
                            <i class="fas fa-cogs"></i> Procesar OCR
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4" id="resultsCard" style="display: none;">
                <div class="card-header">
                    <h5>Resultados del OCR</h5>
                </div>
                <div class="card-body">
                    <div id="ocrResults">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Procesando...</span>
                            </div>
                            <p>Procesando OCR, por favor espere...</p>
                        </div>
                    </div>
                    
                    <div class="mt-4" id="exportOptions" style="display: none;">
                        <a href="/ocr/export/<?= htmlspecialchars($document['id']) ?>/xlsx" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </a>
                        <a href="/ocr/export/<?= htmlspecialchars($document['id']) ?>/csv" class="btn btn-secondary">
                            <i class="fas fa-file-csv"></i> Exportar a CSV
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="/documents/view/<?= htmlspecialchars($document['id']) ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al documento
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Procesar OCR al hacer clic en el botón
    $('#processOcrBtn').click(function() {
        // Mostrar tarjeta de resultados
        $('#resultsCard').show();
        $('#exportOptions').hide();
        
        // Obtener datos del formulario
        var formData = $('#ocrOptionsForm').serialize();
        
        // Realizar petición AJAX
        $.ajax({
            url: '/ocr/execute',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mostrar resultados
                    var resultsHtml = '<div class="table-responsive"><table class="table table-bordered">';
                    resultsHtml += '<thead><tr><th>Columna</th><th>Texto Extraído</th></tr></thead><tbody>';
                    
                    $.each(response.data, function(column, text) {
                        resultsHtml += '<tr>';
                        resultsHtml += '<td>' + column + '</td>';
                        resultsHtml += '<td>' + (text || '<em>No se pudo extraer texto</em>') + '</td>';
                        resultsHtml += '</tr>';
                    });
                    
                    resultsHtml += '</tbody></table></div>';
                    
                    $('#ocrResults').html(resultsHtml);
                    $('#exportOptions').show();
                } else {
                    // Mostrar error
                    $('#ocrResults').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#ocrResults').html('<div class="alert alert-danger">Error al procesar la solicitud</div>');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>