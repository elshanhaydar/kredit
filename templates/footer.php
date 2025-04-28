<footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">
                        Qapı sistemləri üçün kredit həlləri təqdim edirik. 
                        Sərfəli şərtlər və rahat ödəniş üsulları.
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Faydalı Linklər</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>/about.php">Haqqımızda</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/terms.php">İstifadə Şərtləri</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/privacy.php">Məxfilik Siyasəti</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php">Əlaqə</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Əlaqə</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +994 XX XXX XX XX</li>
                        <li><i class="fas fa-envelope me-2"></i> info@example.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Bakı şəh., AZ1000</li>
                    </ul>
                    <div class="social-links mt-3">
                        <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                        Bütün hüquqlar qorunur.
                    </p>
                </div>
            </div>
        </div>
    </footer>

<!-- jQuery -->
    <script src="assets/js/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS (admin panel üçün) -->
    <?php if(isset($isAdmin) && $isAdmin): ?>
    <script src="assets/js/datatables.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>

    <!-- Əlavə JavaScript faylları -->
    <?php if(isset($extraJS)): ?>
        <?php foreach($extraJS as $js): ?>
        <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
</html>