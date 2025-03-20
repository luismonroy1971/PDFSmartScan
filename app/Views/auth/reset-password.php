<?php 
use App\Core\Session;
include_once VIEWS_PATH . '/layouts/header.php'; 
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Restablecer contraseña</h4>
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
                    
                    <p class="mb-4">
                        Ingresa tu dirección de correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                    </p>
                    
                    <form action="/reset-password" method="POST" id="reset-form">
                        <div class="form-group mb-3">
                            <label for="email">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Por favor ingrese un correo electrónico válido.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Enviar enlace</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>¿Recordaste tu contraseña? <a href="/login">Volver al inicio de sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($token)): ?>
<!-- Pantalla para establecer nueva contraseña cuando se accede con token -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Establecer nueva contraseña</h4>
                </div>
                <div class="card-body">
                    <form action="/reset-password/update" method="POST" id="new-password-form">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                        
                        <div class="form-group mb-3">
                            <label for="new_password">Nueva contraseña</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required minlength="8">
                            <small class="form-text text-muted">
                                La contraseña debe tener al menos 8 caracteres e incluir letras y números.
                            </small>
                            <div class="invalid-feedback">
                                La contraseña debe tener al menos 8 caracteres.
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="new_password_confirm">Confirmar nueva contraseña</label>
                            <input type="password" class="form-control" id="new_password_confirm" 
                                   name="new_password_confirm" required>
                            <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario de solicitud de restablecimiento
    const resetForm = document.getElementById('reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', function(event) {
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email.value.trim() || !emailRegex.test(email.value)) {
                email.classList.add('is-invalid');
                event.preventDefault();
            } else {
                email.classList.remove('is-invalid');
            }
        });
    }
    
    // Validación del formulario de nueva contraseña
    const newPasswordForm = document.getElementById('new-password-form');
    if (newPasswordForm) {
        newPasswordForm.addEventListener('submit', function(event) {
            let isValid = true;
            
            const newPassword = document.getElementById('new_password');
            const newPasswordConfirm = document.getElementById('new_password_confirm');
            
            // Validar nueva contraseña
            if (newPassword.value.length < 8) {
                newPassword.classList.add('is-invalid');
                isValid = false;
            } else {
                newPassword.classList.remove('is-invalid');
            }
            
            // Validar confirmación de nueva contraseña
            if (newPassword.value !== newPasswordConfirm.value) {
                newPasswordConfirm.classList.add('is-invalid');
                isValid = false;
            } else {
                newPasswordConfirm.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
});
</script>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>