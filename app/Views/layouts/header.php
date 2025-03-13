<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?= Session::get('csrf_token') ?>">
    <title><?= $title ?? 'Sistema de Extracción PDF a Excel con OCR' ?> - Grupo Tema Litoclean</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Additional CSS -->
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= $style ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                <img src="/images/logo-tema-litoclean.png" alt="Grupo Tema Litoclean" height="40">
                Sistema PDF-Excel OCR
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (Session::get('user_id')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= currentUrl() === '/dashboard' ? 'active' : '' ?>" 
                               href="/dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= strpos(currentUrl(), '/pdf/upload') === 0 ? 'active' : '' ?>" 
                               href="/pdf/upload">
                                <i class="fas fa-file-upload"></i> Subir PDF
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= strpos(currentUrl(), '/dashboard/history') === 0 ? 'active' : '' ?>" 
                               href="/dashboard/history">
                                <i class="fas fa-history"></i> Historial
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-tools"></i> Herramientas
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="/dashboard/templates">
                                        <i class="fas fa-clone"></i> Plantillas
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/dashboard/settings">
                                        <i class="fas fa-cog"></i> Configuración
                                    </a>
                                </li>
                                <?php if (Session::get('user_role') === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/users">
                                            <i class="fas fa-users"></i> Gestión de usuarios
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/stats">
                                            <i class="fas fa-chart-bar"></i> Estadísticas
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= currentUrl() === '/' ? 'active' : '' ?>" href="/">
                                <i class="fas fa-home"></i> Inicio
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= currentUrl() === '/about' ? 'active' : '' ?>" href="/about">
                                <i class="fas fa-info-circle"></i> Acerca de
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= currentUrl() === '/contact' ? 'active' : '' ?>" href="/contact">
                                <i class="fas fa-envelope"></i> Contacto
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <?php if (Session::get('user_id')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> 
                                <?= htmlspecialchars(Session::get('user_name')) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="/dashboard/profile">
                                        <i class="fas fa-id-card"></i> Perfil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/dashboard/settings">
                                        <i class="fas fa-cog"></i> Configuración
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/logout">
                                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= currentUrl() === '/login' ? 'active' : '' ?>" href="/login">
                                <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= currentUrl() === '/register' ? 'active' : '' ?>" href="/register">
                                <i class="fas fa-user-plus"></i> Registro
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>
    
<?php 
// Función auxiliar para obtener la URL actual
function currentUrl() {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}
?>