<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Mis Documentos</h2>
                <a href="/documents/upload" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Subir Nuevo Documento
                </a>
            </div>
            
            <?php if (isset($flash['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($flash['success']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($flash['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($flash['error']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($documents)): ?>
                <div class="alert alert-info">
                    No tienes documentos subidos. Comienza subiendo tu primer documento PDF.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tamaño</th>
                                <th>Fecha de Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td><?= htmlspecialchars($document['original_filename']) ?></td>
                                    <td><?= formatFileSize($document['file_size']) ?></td>
                                    <td><?= formatDate($document['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/documents/view/<?= $document['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                            <a href="/ocr/process/<?= $document['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-cogs"></i> Procesar
                                            </a>
                                            <a href="/documents/delete/<?= $document['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('¿Estás seguro de eliminar este documento?')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Función auxiliar para formatear tamaño de archivo
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Función auxiliar para formatear fecha
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>