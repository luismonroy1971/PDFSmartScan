<?php 
use Core\Session;
include_once VIEWS_PATH . '/layouts/header.php'; 
?>
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Panel de Control</h4>
                    <a href="/pdf/upload" class="btn btn-light">Subir Nuevo PDF</a>
                </div>
                <div class="card-body">
                    <p>Bienvenido, <strong><?= htmlspecialchars(Session::get('user_name')) ?></strong></p>
                    
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
                    
                    <p>Desde aquí puedes gestionar tus documentos PDF y extraer datos para convertirlos a Excel.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mis Documentos</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <div class="alert alert-info">
                            No tienes documentos subidos. <a href="/pdf/upload">Sube tu primer PDF</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Fecha de Subida</th>
                                        <th>Tamaño</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $document): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($document['original_filename']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($document['created_at'])) ?></td>
                                            <td><?= formatBytes($document['file_size']) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/pdf/view/<?= $document['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>
                                                    <a href="/pdf/area-selector/<?= $document['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-crop"></i> Seleccionar Áreas
                                                    </a>
                                                    <a href="/excel/configure/<?= $document['id'] ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-file-excel"></i> Exportar a Excel
                                                    </a>
                                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $document['id'] ?>">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Paginación">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                                            <a class="page-link" href="/dashboard?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este documento? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejo del modal de eliminación
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const confirmDelete = document.getElementById('confirmDelete');
    
    document.querySelectorAll('.btn-delete').forEach(function(button) {
        button.addEventListener('click', function() {
            const documentId = this.getAttribute('data-id');
            confirmDelete.setAttribute('href', '/pdf/delete/' + documentId);
            deleteModal.show();
        });
    });
});
</script>

<?php 
// Función auxiliar para formatear bytes
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