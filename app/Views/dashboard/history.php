<?php 
use Core\Session;
include_once VIEWS_PATH . '/layouts/header.php'; 
?>
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <?php include_once VIEWS_PATH . '/dashboard/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Historial de Actividad</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/dashboard" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <button id="btn-export-history" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="periodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar-alt"></i> Período
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="periodDropdown">
                            <li><a class="dropdown-item period-filter" href="#" data-period="7">Última semana</a></li>
                            <li><a class="dropdown-item period-filter" href="#" data-period="30">Último mes</a></li>
                            <li><a class="dropdown-item period-filter" href="#" data-period="90">Últimos 3 meses</a></li>
                            <li><a class="dropdown-item period-filter" href="#" data-period="365">Último año</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item period-filter" href="#" data-period="all">Todo el historial</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
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
            
            <?php if (empty($history)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Aún no hay actividad registrada en tu cuenta.
                </div>
            <?php else: ?>
                <!-- Filtros y búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="btn-group" role="group" aria-label="Filtros de actividad">
                            <button type="button" class="btn btn-outline-secondary activity-filter active" data-type="all">
                                Todas
                            </button>
                            <button type="button" class="btn btn-outline-secondary activity-filter" data-type="upload">
                                <i class="fas fa-file-upload"></i> Subidas
                            </button>
                            <button type="button" class="btn btn-outline-secondary activity-filter" data-type="ocr">
                                <i class="fas fa-text-width"></i> OCR
                            </button>
                            <button type="button" class="btn btn-outline-secondary activity-filter" data-type="export">
                                <i class="fas fa-file-excel"></i> Exportaciones
                            </button>
                            <button type="button" class="btn btn-outline-secondary activity-filter" data-type="template">
                                <i class="fas fa-clone"></i> Plantillas
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="history-search" class="form-control" placeholder="Buscar...">
                            <button class="btn btn-outline-secondary" type="button" id="btn-search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de historial -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm" id="history-table">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Tipo de Actividad</th>
                                <th>Documento</th>
                                <th>Detalles</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $activity): ?>
                                <tr class="activity-row" data-type="<?= htmlspecialchars($activity['type']) ?>">
                                    <td><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        switch ($activity['type']) {
                                            case 'upload':
                                                echo '<span class="badge bg-primary"><i class="fas fa-file-upload"></i> Subida</span>';
                                                break;
                                            case 'ocr':
                                                echo '<span class="badge bg-info"><i class="fas fa-text-width"></i> OCR</span>';
                                                break;
                                            case 'export':
                                                echo '<span class="badge bg-success"><i class="fas fa-file-excel"></i> Exportación</span>';
                                                break;
                                            case 'template':
                                                echo '<span class="badge bg-warning text-dark"><i class="fas fa-clone"></i> Plantilla</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Otro</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($activity['document_id']): ?>
                                            <a href="/pdf/view/<?= $activity['document_id'] ?>" title="Ver documento">
                                                <?= htmlspecialchars($activity['document_name'] ?? $activity['details']) ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($activity['details']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        switch ($activity['status']) {
                                            case 'success':
                                                echo '<span class="badge bg-success">Éxito</span>';
                                                break;
                                            case 'error':
                                                echo '<span class="badge bg-danger">Error</span>';
                                                break;
                                            case 'warning':
                                                echo '<span class="badge bg-warning text-dark">Advertencia</span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="badge bg-secondary">Pendiente</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">-</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <nav aria-label="Paginación del historial">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="/dashboard/history?page=<?= $currentPage - 1 ?>">Anterior</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="/dashboard/history?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="/dashboard/history?page=<?= $currentPage + 1 ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros de tipo de actividad
    const activityFilters = document.querySelectorAll('.activity-filter');
    const activityRows = document.querySelectorAll('.activity-row');
    
    activityFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            // Actualizar estado de botones de filtro
            activityFilters.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterType = this.dataset.type;
            
            // Filtrar filas de la tabla
            activityRows.forEach(row => {
                if (filterType === 'all' || row.dataset.type === filterType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Filtro por período
    const periodFilters = document.querySelectorAll('.period-filter');
    
    periodFilters.forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.dataset.period;
            
            // Actualizar texto del dropdown
            document.getElementById('periodDropdown').innerHTML = 
                `<i class="fas fa-calendar-alt"></i> ${this.textContent}`;
            
            // Redirigir con parámetro de período
            window.location.href = `/dashboard/history?period=${period}`;
        });
    });
    
    // Búsqueda en la tabla
    const searchInput = document.getElementById('history-search');
    const btnSearch = document.getElementById('btn-search');
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        
        activityRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    btnSearch.addEventListener('click', performSearch);
    
    // Exportar historial
    document.getElementById('btn-export-history').addEventListener('click', function() {
        // Obtener parámetros actuales de filtrado
        const activeFilter = document.querySelector('.activity-filter.active').dataset.type;
        const period = new URLSearchParams(window.location.search).get('period') || 'all';
        
        // Redirigir a la URL de exportación con parámetros
        window.location.href = `/dashboard/history/export?type=${activeFilter}&period=${period}`;
    });
});
</script>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>