<?php include_once VIEWS_PATH . '/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Iniciar Sesión</h4>
                </div>
                <div class="card-body">
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
                        <div class="form-group mb-3">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="/reset-password">¿Olvidaste tu contraseña?</a>
                        <p class="mt-2">¿No tienes cuenta? <a href="/register">Regístrate</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>