<?php 
use App\Core\Session;
include_once VIEWS_PATH . '/layouts/header.php'; 
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Crear nueva cuenta</h4>
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
                    
                    <form action="/register" method="POST" id="register-form">
                        <div class="form-group mb-3">
                            <label for="name">Nombre completo</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Por favor ingrese su nombre completo.</div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="email">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Por favor ingrese un correo electrónico válido.</div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="8">
                            <small class="form-text text-muted">
                                La contraseña debe tener al menos 8 caracteres e incluir letras y números.
                            </small>
                            <div class="invalid-feedback">
                                La contraseña debe tener al menos 8 caracteres.
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password_confirm">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="password_confirm" 
                                   name="password_confirm" required>
                            <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Acepto los <a href="/terms" target="_blank">términos y condiciones</a> 
                                y la <a href="/privacy" target="_blank">política de privacidad</a>.
                            </label>
                            <div class="invalid-feedback">
                                Debe aceptar los términos y condiciones para continuar.
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Registrarse</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>¿Ya tienes una cuenta? <a href="/login">Iniciar sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validar campos
        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');
        const terms = document.getElementById('terms');
        
        // Validar nombre
        if (!name.value.trim()) {
            name.classList.add('is-invalid');
            isValid = false;
        } else {
            name.classList.remove('is-invalid');
        }
        
        // Validar email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailRegex.test(email.value)) {
            email.classList.add('is-invalid');
            isValid = false;
        } else {
            email.classList.remove('is-invalid');
        }
        
        // Validar contraseña
        if (password.value.length < 8) {
            password.classList.add('is-invalid');
            isValid = false;
        } else {
            password.classList.remove('is-invalid');
        }
        
        // Validar confirmación de contraseña
        if (password.value !== passwordConfirm.value) {
            passwordConfirm.classList.add('is-invalid');
            isValid = false;
        } else {
            passwordConfirm.classList.remove('is-invalid');
        }
        
        // Validar términos
        if (!terms.checked) {
            terms.classList.add('is-invalid');
            isValid = false;
        } else {
            terms.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
});
</script>

<?php include_once VIEWS_PATH . '/layouts/footer.php'; ?>