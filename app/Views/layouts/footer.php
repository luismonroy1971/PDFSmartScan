</main>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Grupo Tema Litoclean</h5>
                    <p class="text-muted small">
                        Con más de 30 años de actividad como consultores en el ámbito ambiental 
                        y de seguridad, hemos consolidado una dilatada experiencia en el sector.
                    </p>
                    <p class="small">
                        <i class="fas fa-map-marker-alt"></i> Gálvez Barrenechea 566, Lima, Perú<br>
                        <i class="fas fa-phone"></i> Teléfono: +51 XXXXXXXXX<br>
                        <i class="fas fa-envelope"></i> Email: info@temalitoclean.pe
                    </p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Enlaces rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-decoration-none">Inicio</a></li>
                        <li><a href="/about" class="text-decoration-none">Sobre nosotros</a></li>
                        <li><a href="/services" class="text-decoration-none">Servicios</a></li>
                        <li><a href="/contact" class="text-decoration-none">Contacto</a></li>
                        <li><a href="/privacy" class="text-decoration-none">Política de privacidad</a></li>
                        <li><a href="/terms" class="text-decoration-none">Términos y condiciones</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Herramienta desarrollada por</h5>
                    <p class="text-muted small">
                        Gerencia de Gestión de Proyectos e Innovación<br>
                        Grupo Tema Litoclean
                    </p>
                    <div class="social-icons">
                        <a href="#" class="me-2 text-decoration-none">
                            <i class="fab fa-facebook-square fa-2x"></i>
                        </a>
                        <a href="#" class="me-2 text-decoration-none">
                            <i class="fab fa-twitter-square fa-2x"></i>
                        </a>
                        <a href="#" class="me-2 text-decoration-none">
                            <i class="fab fa-linkedin fa-2x"></i>
                        </a>
                        <a href="#" class="text-decoration-none">
                            <i class="fab fa-instagram-square fa-2x"></i>
                        </a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="small mb-0">
                    &copy; <?= date('Y') ?> Grupo Tema Litoclean. Todos los derechos reservados. 
                    <span class="d-none d-md-inline">|</span>
                    <br class="d-md-none">
                    <small class="text-muted">
                        Sistema de Extracción PDF a Excel con OCR v1.0.0
                    </small>
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (algunos componentes podrían requerirlo) -->
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/js/main.js"></script>
    
    <!-- Additional Scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Scripts -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?= $inlineScripts ?>
        </script>
    <?php endif; ?>
</body>
</html>