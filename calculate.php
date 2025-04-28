<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Credit.php';

$pageTitle = 'Kredit Kalkulyatoru - ' . SITE_NAME;

// Kredit tipi
$type = $_GET['type'] ?? '';

// Default dəyərlər
$defaultAmount = 5000;
$defaultPeriod = 12;
$defaultRate = 18;
$defaultInitialPayment = 0;

// Header
include 'templates/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Kredit Kalkulyatoru -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4">Kredit Kalkulyatoru</h2>
                    
                    <form id="creditCalculator">
                        <!-- Kredit məbləği -->
                        <div class="mb-4">
                            <label class="form-label">Kredit məbləği (AZN)</label>
                            <input type="range" class="form-range" id="creditAmountRange"
                                   min="<?php echo MIN_CREDIT_AMOUNT; ?>" 
                                   max="<?php echo MAX_CREDIT_AMOUNT; ?>" 
                                   step="100" 
                                   value="<?php echo $defaultAmount; ?>">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><?php echo MIN_CREDIT_AMOUNT; ?> AZN</span>
                                <input type="number" class="form-control form-control-sm w-25" 
                                       id="creditAmount" value="<?php echo $defaultAmount; ?>">
                                <span class="text-muted"><?php echo MAX_CREDIT_AMOUNT; ?> AZN</span>
                            </div>
                        </div>

                        <!-- Kredit müddəti -->
                        <div class="mb-4">
                            <label class="form-label">Kredit müddəti (ay)</label>
                            <input type="range" class="form-range" id="creditPeriodRange"
                                   min="<?php echo MIN_CREDIT_PERIOD; ?>" 
                                   max="<?php echo MAX_CREDIT_PERIOD; ?>" 
                                   value="<?php echo $defaultPeriod; ?>">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><?php echo MIN_CREDIT_PERIOD; ?> ay</span>
                                <input type="number" class="form-control form-control-sm w-25" 
                                       id="creditPeriod" value="<?php echo $defaultPeriod; ?>">
                                <span class="text-muted"><?php echo MAX_CREDIT_PERIOD; ?> ay</span>
                            </div>
                        </div>

                        <!-- Faiz dərəcəsi -->
                        <div class="mb-4">
                            <label class="form-label">İllik faiz dərəcəsi (%)</label>
                            <input type="range" class="form-range" id="interestRateRange"
                                   min="<?php echo MIN_INTEREST_RATE; ?>" 
                                   max="<?php echo MAX_INTEREST_RATE; ?>" 
                                   step="0.5" 
                                   value="<?php echo $defaultRate; ?>">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><?php echo MIN_INTEREST_RATE; ?>%</span>
                                <input type="number" class="form-control form-control-sm w-25" 
                                       id="interestRate" value="<?php echo $defaultRate; ?>">
                                <span class="text-muted"><?php echo MAX_INTEREST_RATE; ?>%</span>
                            </div>
                        </div>

                        <!-- İlkin ödəniş -->
                        <div class="mb-4">
                            <label class="form-label">İlkin ödəniş (AZN)</label>
                            <input type="range" class="form-range" id="initialPaymentRange"
                                   min="0" max="0" step="100" value="0">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">0 AZN</span>
                                <input type="number" class="form-control form-control-sm w-25" 
                                       id="initialPayment" value="<?php echo $defaultInitialPayment; ?>">
                                <span class="text-muted" id="maxInitialPayment">0 AZN</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Hesabla</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Nəticələr -->
        <div class="col-lg-6 mt-4 mt-lg-0">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4">Kredit Məlumatları</h3>
                    
                    <!-- Kredit xülasəsi -->
                    <div id="creditSummary" class="alert alert-info d-none">
                        <!-- JavaScript ilə doldurulacaq -->
                    </div>

                    <!-- Ödəniş cədvəli -->
                    <div id="paymentSchedule" class="table-responsive">
                        <!-- JavaScript ilə doldurulacaq -->
                    </div>
<?php if (isset($_SESSION['user_id'])): ?>
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-success" id="applyButton" disabled>
                                <i class="fas fa-check-circle me-2"></i>Kredit üçün müraciət et
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Kredit üçün müraciət etmək üçün əvvəlcə 
                            <a href="register.php">qeydiyyatdan keçin</a> və ya 
                            <a href="login.php">daxil olun</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Kredit şərtləri -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4">Kredit Şərtləri</h3>
                    
                    <div class="row g-4">
                        <!-- Tələb olunan sənədlər -->
                        <div class="col-md-4">
                            <h5><i class="fas fa-file-alt me-2"></i>Tələb olunan sənədlər</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Şəxsiyyət vəsiqəsi</li>
                                <li><i class="fas fa-check text-success me-2"></i>FİN kart</li>
                                <li><i class="fas fa-check text-success me-2"></i>İş yerindən arayış</li>
                                <li><i class="fas fa-check text-success me-2"></i>Əmək haqqı arayışı</li>
                            </ul>
                        </div>

                        <!-- Kredit şərtləri -->
                        <div class="col-md-4">
                            <h5><i class="fas fa-list me-2"></i>Kredit şərtləri</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Yaş: 18-65</li>
                                <li><i class="fas fa-check text-success me-2"></i>Rəsmi iş stajı: 3 ay</li>
                                <li><i class="fas fa-check text-success me-2"></i>Aylıq gəlir: min. 500 AZN</li>
                                <li><i class="fas fa-check text-success me-2"></i>Kredit tarixi: müsbət</li>
                            </ul>
                        </div>

                        <!-- Üstünlüklər -->
                        <div class="col-md-4">
                            <h5><i class="fas fa-star me-2"></i>Üstünlüklər</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Sürətli təsdiq</li>
                                <li><i class="fas fa-check text-success me-2"></i>Minimum sənəd tələbi</li>
                                <li><i class="fas fa-check text-success me-2"></i>Sərfəli şərtlər</li>
                                <li><i class="fas fa-check text-success me-2"></i>Fərdi yanaşma</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kredit müraciət modalı -->
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredit Müraciəti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="creditApplicationForm">
                    <!-- Kredit məlumatları -->
                    <div class="alert alert-info">
                        <h6>Seçilmiş kredit məlumatları:</h6>
                        <p class="mb-1">Məbləğ: <span id="modalAmount"></span> AZN</p>
                        <p class="mb-1">Müddət: <span id="modalPeriod"></span> ay</p>
                        <p class="mb-1">Faiz: <span id="modalRate"></span>%</p>
                        <p class="mb-1">İlkin ödəniş: <span id="modalInitialPayment"></span> AZN</p>
                        <p class="mb-0">Aylıq ödəniş: <span id="modalMonthlyPayment"></span> AZN</p>
                    </div>

                    <!-- İş yeri məlumatları -->
                    <div class="mb-3">
                        <label class="form-label">İş yeri *</label>
                        <input type="text" class="form-control" name="workplace" required>
                    </div>

                    <!-- Vəzifə -->
                    <div class="mb-3">
                        <label class="form-label">Vəzifə *</label>
                        <input type="text" class="form-control" name="position" required>
                    </div>

                    <!-- Aylıq gəlir -->
                    <div class="mb-3">
                        <label class="form-label">Aylıq gəlir (AZN) *</label>
                        <input type="number" class="form-control" name="monthly_income" required
                               min="500" step="100">
                    </div>

                    <!-- İş stajı -->
                    <div class="mb-3">
                        <label class="form-label">İş stajı (ay) *</label>
                        <input type="number" class="form-control" name="work_experience" required
                               min="3">
                    </div>

                    <!-- Əlavə qeyd -->
                    <div class="mb-3">
                        <label class="form-label">Əlavə qeyd</label>
                        <textarea class="form-control" name="note" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                <button type="button" class="btn btn-primary" id="submitApplication">Müraciət et</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Range input və text input əlaqələndirmə
    function connectRangeAndNumber(rangeId, numberId) {
        const range = document.getElementById(rangeId);
        const number = document.getElementById(numberId);
        
        range.addEventListener('input', function() {
            number.value = this.value;
            updateCalculations();
        });
        
        number.addEventListener('input', function() {
            range.value = this.value;
            updateCalculations();
        });
    }

    connectRangeAndNumber('creditAmountRange', 'creditAmount');
    connectRangeAndNumber('creditPeriodRange', 'creditPeriod');
    connectRangeAndNumber('interestRateRange', 'interestRate');
    connectRangeAndNumber('initialPaymentRange', 'initialPayment');

    // İlkin ödəniş maksimumunu yeniləyirik
    document.getElementById('creditAmount').addEventListener('input', function() {
        const amount = parseFloat(this.value);
        const maxInitial = amount * 0.7; // Maksimum 70% ilkin ödəniş
        
        const initialPaymentRange = document.getElementById('initialPaymentRange');
        const initialPayment = document.getElementById('initialPayment');
        const maxInitialPaymentText = document.getElementById('maxInitialPayment');
        
        initialPaymentRange.max = maxInitial;
        maxInitialPaymentText.textContent = Math.round(maxInitial) + ' AZN';
        
        if (parseFloat(initialPayment.value) > maxInitial) {
            initialPayment.value = maxInitial;
            initialPaymentRange.value = maxInitial;
        }
        
        updateCalculations();
    });

    // Kredit hesablama
    function updateCalculations() {
        const amount = parseFloat(document.getElementById('creditAmount').value);
        const period = parseInt(document.getElementById('creditPeriod').value);
        const rate = parseFloat(document.getElementById('interestRate').value);
        const initialPayment = parseFloat(document.getElementById('initialPayment').value) || 0;

        // API-yə sorğu göndəririk
        fetch(`api/credits.php?action=calculate&amount=${amount}&period=${period}&rate=${rate}&initial_payment=${initialPayment}`)
            .then(response => response.json())
            .then(data => {
                // Kredit xülasəsi
                document.getElementById('creditSummary').innerHTML = `
                    <h5>Kredit Xülasəsi</h5>
                    <p class="mb-1">Ümumi kredit məbləği: ${amount.toFixed(2)} AZN</p>
                    <p class="mb-1">İlkin ödəniş: ${initialPayment.toFixed(2)} AZN</p>
                    <p class="mb-1">Kredit müddəti: ${period} ay</p>
                    <p class="mb-1">İllik faiz dərəcəsi: ${rate}%</p>
                    <p class="mb-1">Aylıq ödəniş: ${data.monthly_payment} AZN</p>
                    <p class="mb-0">Ümumi ödəniləcək məbləğ: ${data.total_amount} AZN</p>
                `;
                document.getElementById('creditSummary').classList.remove('d-none');

                // Ödəniş cədvəli
                let schedule = '<table class="table table-striped"><thead><tr>' +
                             '<th>Ay</th><th>Ödəniş</th><th>Əsas borc</th><th>Faiz</th><th>Qalıq</th>' +
                             '</tr></thead><tbody>';

                let balance = amount - initialPayment;
                const monthlyPayment = data.monthly_payment;
                const monthlyRate = rate / 12 / 100;

                for (let month = 1; month <= period; month++) {
                    const interest = balance * monthlyRate;
                    const principal = monthlyPayment - interest;
                    balance -= principal;

                    schedule += `<tr>
                        <td>${month}</td>
                        <td>${monthlyPayment.toFixed(2)} AZN</td>
                        <td>${principal.toFixed(2)} AZN</td>
                        <td>${interest.toFixed(2)} AZN</td>
                        <td>${Math.max(0, balance.toFixed(2))} AZN</td>
                    </tr>`;
                }

                schedule += '</tbody></table>';
                document.getElementById('paymentSchedule').innerHTML = schedule;

                // Müraciət düyməsini aktiv edirik
                const applyButton = document.getElementById('applyButton');
                if (applyButton) {
                    applyButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Hesablama xətası:', error);
            });
    }

    // Kalkulyator formu
    document.getElementById('creditCalculator').addEventListener('submit', function(e) {
        e.preventDefault();
        updateCalculations();
    });

    // İlkin hesablama
    updateCalculations();

    // Kredit müraciəti
    if (document.getElementById('applyButton')) {
        document.getElementById('applyButton').addEventListener('click', function() {
            // Modal məlumatlarını doldururuq
            document.getElementById('modalAmount').textContent = document.getElementById('creditAmount').value;
            document.getElementById('modalPeriod').textContent = document.getElementById('creditPeriod').value;
            document.getElementById('modalRate').textContent = document.getElementById('interestRate').value;
            document.getElementById('modalInitialPayment').textContent = document.getElementById('initialPayment').value;
            document.getElementById('modalMonthlyPayment').textContent = 
                document.querySelector('#creditSummary p:nth-child(5)').textContent.split(': ')[1].split(' ')[0];

            // Modalı göstəririk
            new bootstrap.Modal(document.getElementById('applyModal')).show();
        });

        // Müraciət formu
        document.getElementById('submitApplication').addEventListener('click', function() {
            const form = document.getElementById('creditApplicationForm');
            if (form.checkValidity()) {
                const formData = new FormData(form);
                formData.append('amount', document.getElementById('creditAmount').value);
                formData.append('period', document.getElementById('creditPeriod').value);
                formData.append('rate', document.getElementById('interestRate').value);
                formData.append('initial_payment', document.getElementById('initialPayment').value);

                // Müraciəti göndəririk
                fetch('api/credits.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'dashboard.php?status=applied';
                    } else {
                        alert('Xəta baş verdi: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Müraciət xətası:', error);
                    alert('Müraciət göndərilərkən xəta baş verdi');
                });
            } else {
                form.reportValidity();
            }
        });
    }
});
</script>

<?php
// Footer
include 'templates/footer.php';
?>