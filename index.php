<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Credit.php';

$pageTitle = 'Ana Səhifə - ' . SITE_NAME;
// Header-dən əvvəl əlavə CSS-ləri daxil edirik
$extraCSS = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
    'assets/css/style.css'
];

// Header-dən əvvəl əlavə JavaScript-ləri daxil edirik
$extraJS = [
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js',
    'assets/js/main.js'
];

// Header
include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 mb-4">Avtomatik Qapı Sistemləri üçün Kredit Həlləri</h1>
                <p class="lead mb-4">Sərfəli şərtlərlə və rahat ödəniş imkanları ilə kredit əldə edin. Bütün növ qapı sistemləri üçün kredit təklif edirik.</p>
                <div class="d-flex gap-3">
                    <a href="calculate.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calculator me-2"></i>Kredit Kalkulyatoru
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Qeydiyyat
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <img src="/api/placeholder/600/400" alt="Qapı Sistemləri" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Üstünlüklər -->
<section class="features py-5">
    <div class="container">
        <h2 class="text-center mb-5">Niyə Biz?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-percentage fa-3x text-primary mb-3"></i>
                        <h4>Sərfəli Faizlər</h4>
                        <p class="text-muted">İllik <?php echo MIN_INTEREST_RATE; ?>%-dən başlayan faiz dərəcələri ilə kredit təklif edirik.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                        <h4>Sürətli Prosedur</h4>
                        <p class="text-muted">Sadə qeydiyyat və təsdiq prosesi ilə krediti qısa zamanda əldə edin.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h4>Təhlükəsizlik</h4>
                        <p class="text-muted">Məlumatlarınız tam təhlükəsiz şəkildə qorunur və məxfi saxlanılır.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kredit Növləri -->
<section class="credit-types py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Kredit Növləri</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Avtomatik Qapılar</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Seksiyonal qapılar</li>
                            <li><i class="fas fa-check text-success me-2"></i>Sürüşən qapılar</li>
                            <li><i class="fas fa-check text-success me-2"></i>Qaraj qapıları</li>
                        </ul>
                        <p class="card-text">
                            <small class="text-muted">
                                <?php echo MIN_CREDIT_AMOUNT; ?> - <?php echo MAX_CREDIT_AMOUNT; ?> AZN arası
                            </small>
                        </p>
                        <a href="calculate.php?type=auto" class="btn btn-outline-primary">Ətraflı</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Giriş Qapıları</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Mənzil qapıları</li>
                            <li><i class="fas fa-check text-success me-2"></i>Villa qapıları</li>
                            <li><i class="fas fa-check text-success me-2"></i>Dəmir qapılar</li>
                        </ul>
                        <p class="card-text">
                            <small class="text-muted">
                                <?php echo MIN_CREDIT_AMOUNT; ?> - <?php echo MAX_CREDIT_AMOUNT; ?> AZN arası
                            </small>
                        </p>
                        <a href="calculate.php?type=entrance" class="btn btn-outline-primary">Ətraflı</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Təhlükəsizlik Sistemləri</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Access control</li>
                            <li><i class="fas fa-check text-success me-2"></i>Şlaqbaumlar</li>
                            <li><i class="fas fa-check text-success me-2"></i>Baryer sistemləri</li>
                        </ul>
                        <p class="card-text">
                            <small class="text-muted">
                                <?php echo MIN_CREDIT_AMOUNT; ?> - <?php echo MAX_CREDIT_AMOUNT; ?> AZN arası
                            </small>
                        </p>
                        <a href="calculate.php?type=security" class="btn btn-outline-primary">Ətraflı</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Necə İşləyir -->
<section class="how-it-works py-5">
    <div class="container">
        <h2 class="text-center mb-5">Necə İşləyir?</h2>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-number">1</div>
                        <div class="timeline-content">
                            <h4>Qeydiyyat</h4>
                            <p>Sistemdə qeydiyyatdan keçin və şəxsi kabinetinizi yaradın.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">2</div>
                        <div class="timeline-content">
                            <h4>Kredit Hesablama</h4>
                            <p>Kalkulyator vasitəsilə sizə uyğun kredit məbləği və müddətini seçin.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">3</div>
                        <div class="timeline-content">
                            <h4>Müraciət</h4>
                            <p>Onlayn müraciət formasını doldurun və təsdiq üçün göndərin.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">4</div>
                        <div class="timeline-content">
                            <h4>Təsdiq</h4>
                            <p>Müraciətiniz təsdiqləndikdən sonra müqavilə imzalayın.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="faq py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Tez-tez Verilən Suallar</h2>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Kredit almaq üçün hansı sənədlər lazımdır?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Şəxsiyyət vəsiqəsi və ya FİN kartı kifayətdir. Bəzi hallarda əlavə sənədlər tələb oluna bilər.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Maksimum kredit məbləği nə qədərdir?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Maksimum kredit məbləği <?php echo MAX_CREDIT_AMOUNT; ?> AZN-dir.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Kredit müddəti nə qədərdir?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Kredit müddəti <?php echo MIN_CREDIT_PERIOD; ?> aydan <?php echo MAX_CREDIT_PERIOD; ?> aya qədər ola bilər.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Əlaqə Bölməsi -->
<section class="contact py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h2>Bizimlə Əlaqə</h2>
                <p class="mb-4">Suallarınız varsa, bizimlə əlaqə saxlayın.</p>
                <form id="contactForm">
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Adınız">
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" placeholder="Email">
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" rows="4" placeholder="Mesajınız"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Göndər</button>
                </form>
            </div>
            <div class="col-md-6">
                <div class="contact-info mt-5 mt-md-0">
                    <h4>Əlaqə Məlumatları</h4>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Bakı şəh., AZ1000
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            +994 XX XXX XX XX
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2"></i>
                            info@example.com
                        </li>
                    </ul>
                    <h4 class="mt-4">İş Saatları</h4>
                    <p class="mb-1">Bazar ertəsi - Cümə: 09:00 - 18:00</p>
                    <p>Şənbə: 10:00 - 14:00</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Footer
include 'templates/footer.php';
?>