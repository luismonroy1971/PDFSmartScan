<?php include_once VIEWS_PATH . '/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/pdf/view/<?= $document['id'] ?>">Documento</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Descargar Excel</li>
                </ol>
            </nav>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-excel"></i> Exportación a Excel Completada
                    </h4>
                    <a href="/dashboard" class="btn btn-light btn-sm">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Documento: <?= htmlspecialchars($document['original_filename']) ?></h5>
                    
                    <?php if (Session::hasFlash('error')): ?>
                        <div class="alert alert-danger">
                            <?= Session::getFlash('error') ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> El proceso de extracción de datos se ha completado exitosamente.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Resumen de la exportación</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Documento original:</th>
                                            <td><?= htmlspecialchars($document['original_filename']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tamaño del documento:</th>
                                            <td><?= formatBytes($document['file_size']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Áreas procesadas:</th>
                                            <td><?= count($areas) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Formato de exportación:</th>
                                            <td>
                                                <?= $exportFormat === 'xlsx' ? 'Excel (XLSX)' : 'CSV' ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Fecha de procesamiento:</th>
                                            <td><?= date('d/m/Y H:i:s') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Previsualización de datos extraídos</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($previewData) && !empty($previewData)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <?php foreach ($previewData['headers'] as $header): ?>
                                                            <th><?= htmlspecialchars($header) ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <?php foreach ($previewData['values'] as $value): ?>
                                                            <td><?= htmlspecialchars($value) ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> La previsualización no está disponible.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Opciones de descarga</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-grid gap-2">
                                        <a href="/excel/download/<?= $document['id'] ?>/<?= $exportFormat ?>" 
                                           class="btn btn-primary btn-lg">
                                            <i class="fas fa-download"></i> Descargar archivo <?= strtoupper($exportFormat) ?>
                                        </a>
                                        <small class="text-muted text-center mt-1">
                                            Haz clic en el botón para descargar el archivo generado
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">¿Qué más puedes hacer?</h6>
                                            <ul class="list-unstyled">
                                                <li class="mb-2">
                                                    <a href="/pdf/area-selector/<?= $document['id'] ?>" class="text-decoration-none">
                                                        <i class="fas fa-edit"></i> Editar selección de áreas
                                                    </a>
                                                </li>
                                                <li class="mb-2">
                                                    <a href="/excel/configure/<?= $document['id'] ?>" class="text-decoration-none">
                                                        <i class="fas fa-cog"></i> Cambiar configuración de exportación
                                                    </a>
                                                </li>
                                                <li class="mb-2">
                                                    <a href="/pdf/view/<?= $document['id'] ?>" class="text-decoration-none">
                                                        <i class="fas fa-file-pdf"></i> Ver documento original
                                                    </a>
                                                </li>
                                                <li class="mb-2">
                                                    <a href="/dashboard" class="text-decoration-none">
                                                        <i class="fas fa-home"></i> Volver al dashboard
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opciones adicionales -->
                    <div class="d-flex justify-content-between">
                        <a href="/pdf/upload" class="btn btn-outline-primary">
                            <i class="fas fa-upload"></i> Subir nuevo documento
                        </a>
                        
                        <?php if (isset($templateId)): ?>
                            <div>
                                <span class="text-success me-2">
                                    <i class="fas fa-check-circle"></i> Plantilla guardada
                                </span>
                                <a href="/dashboard/templates" class="btn btn-outline-info">
                                    <i class="fas fa-clone"></i> Ver mis plantillas
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Función auxiliar para formatear tamaño de archivo
function formatBytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    
    $bytes /= (1 << (10 * $pow)); 
    
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
?>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>