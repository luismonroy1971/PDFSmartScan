<?php use Core\Session; ?>
<!-- Elimino cualquier estilo o layout heredado -->
<?php $layout = false; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PDFSmartScan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background-color: #f5f8fa;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            display: flex;
            max-width: 960px;
            width: 100%;
            margin: 2rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .login-banner {
            background-color: #1976d2;
            color: white;
            padding: 3rem;
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
        }
        .login-banner h2 {
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-size: 1.8rem;
        }
        .login-banner p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .login-logo {
            background-color: #5f5f5f;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1rem;
            max-width: 250px;
        }
        .login-logo img {
            width: 100%;
            height: auto;
        }
        .folder-icon {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            opacity: 0.3;
            font-size: 3rem;
        }
        .login-form {
            background-color: white;
            padding: 3rem;
            width: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            color: #333;
        }
        .login-form p {
            color: #666;
            margin-bottom: 2rem;
        }
        .form-label {
            color: #555;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1rem;
            background-color: #f9f9f9;
        }
        .form-control:focus {
            border-color: #1976d2;
            box-shadow: none;
        }
        .input-group-text {
            background-color: #f9f9f9;
            border-right: none;
            color: #888;
        }
        .btn-primary {
            background-color: #1976d2;
            border-color: #1976d2;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 8px;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #1565c0;
            border-color: #1565c0;
        }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #777;
            font-size: 0.85rem;
        }
        .form-check-input:checked {
            background-color: #1976d2;
            border-color: #1976d2;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 12px;
            color: #888;
        }
        .password-field {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Columna izquierda - Banner azul -->
        <div class="login-banner">
            <div class="login-logo">
                <img src="/images/logo-tema-litoclean.png" alt="Tema Litoclean" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNDAgODAiPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC1zaXplPSIyNCIgZmlsbD0id2hpdGUiPnRlbWEgbGl0b2NsZWFuPC90ZXh0Pjwvc3ZnPg==';">
            </div>
            <h2>Bienvenido de nuevo</h2>
            <p>Sistema de Gestión de PDFs y Excel con OCR</p>
            <div class="folder-icon">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>
        
        <!-- Columna derecha - Formulario -->
        <div class="login-form">
            <h2>Iniciar Sesión</h2>
            <p>Ingresa tus credenciales para acceder al sistema</p>
            
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
            
            <form action="/login" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Usuario</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">Recordar sesión</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </form>
            
            <div class="login-footer">
                <p>© <?= date('Y') ?> Sistema de Gestión. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });
    </script>
</body>
</html>