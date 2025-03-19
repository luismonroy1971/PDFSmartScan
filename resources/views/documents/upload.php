<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3>Subir Nuevo Documento PDF</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($flash['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($flash['error']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/documents/upload" method="post" enctype="multipart/form-data" id="uploadForm">
                        <div class="mb-4">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-file-pdf fa-3x"></i>
                                </div>
                                <div class="upload-text">
                                    <p>Arrastra y suelta tu archivo PDF aquí</p>
                                    <p>o</p>
                                    <label for="pdf_file" class="btn btn-primary">Seleccionar archivo</label>
                                    <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" style="display: none;" required>
                                </div>
                                <div id="fileInfo" class="mt-3" style="display: none;">
                                    <p><strong>Archivo seleccionado:</strong> <span id="fileName"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="progress mb-3" style="display: none;" id="uploadProgress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/documents" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="fas fa-upload"></i> Subir Documento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-area {
    border: 2px dashed #ccc;
    border-radius: 5px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.upload-area:hover, .upload-area.dragover {
    border-color: #007bff;
    background-color: rgba(0, 123, 255, 0.05);
}
.upload-icon {
    margin-bottom: 15px;
    color: #6c757d;
}
.upload-text p {
    margin-bottom: 10px;
    color: #6c757d;
}
</style>

<script>
$(document).ready(function() {
    const uploadArea = $('#uploadArea');
    const fileInput = $('#pdf_file');
    const fileInfo = $('#fileInfo');
    const fileName = $('#fileName');
    const uploadProgress = $('#uploadProgress');
    const progressBar = uploadProgress.find('.progress-bar');
    const uploadForm = $('#uploadForm');
    
    // Manejar clic en área de subida
    uploadArea.on('click', function() {
        fileInput.click();
    });
    
    // Manejar cambio en input de archivo
    fileInput.on('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Verificar tipo de archivo
            if (file.type !== 'application/pdf') {
                alert('Solo se permiten archivos PDF');
                fileInput.val('');
                return;
            }
            
            // Mostrar información del archivo
            fileName.text(file.name);
            fileInfo.show();
        }
    });
    
    // Manejar arrastrar y soltar
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            
            // Verificar tipo de archivo
            if (file.type !== 'application/pdf') {
                alert('Solo se permiten archivos PDF');
                return;
            }
            
            // Asignar archivo al input
            fileInput[0].files = files;
            
            // Mostrar información del archivo
            fileName.text(file.name);
            fileInfo.show();
        }
    });
    
    // Manejar envío del formulario
    uploadForm.on('submit', function(e) {
        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            e.preventDefault();
            alert('Por favor selecciona un archivo PDF');
            return;
        }
        
        // Mostrar barra de progreso
        uploadProgress.show();
        
        // Simular progreso (en una implementación real, se usaría AJAX con progress event)
        let progress = 0;
        const interval = setInterval(function() {
            progress += 5;
            progressBar.css('width', progress + '%');
            
            if (progress >= 100) {
                clearInterval(interval);
            }
        }, 100);
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>