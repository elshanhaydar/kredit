// DOM yüklənməsi
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    setupCreditCalculator();
    setupNotifications();
    setupDataTables();
});

// Tooltip funksiyası
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Kredit Kalkulyatoru
function setupCreditCalculator() {
    const calculator = document.getElementById('creditCalculator');
    if (!calculator) return;

    calculator.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = parseFloat(document.getElementById('creditAmount').value);
        const period = parseInt(document.getElementById('creditPeriod').value);
        const rate = parseFloat(document.getElementById('interestRate').value);
        const initialPayment = parseFloat(document.getElementById('initialPayment').value);

        if (validateCreditInputs(amount, period, rate, initialPayment)) {
            calculateMonthlyPayment(amount, period, rate, initialPayment);
        }
    });
}

// Kredit məlumatlarının validasiyası
function validateCreditInputs(amount, period, rate, initialPayment) {
    if (isNaN(amount) || amount < MIN_CREDIT_AMOUNT || amount > MAX_CREDIT_AMOUNT) {
        showAlert('Kredit məbləği düzgün deyil', 'error');
        return false;
    }
    if (isNaN(period) || period < MIN_CREDIT_PERIOD || period > MAX_CREDIT_PERIOD) {
        showAlert('Kredit müddəti düzgün deyil', 'error');
        return false;
    }
    if (isNaN(rate) || rate < MIN_INTEREST_RATE || rate > MAX_INTEREST_RATE) {
        showAlert('Faiz dərəcəsi düzgün deyil', 'error');
        return false;
    }
    if (isNaN(initialPayment) || initialPayment < 0 || initialPayment >= amount) {
        showAlert('İlkin ödəniş məbləği düzgün deyil', 'error');
        return false;
    }
    return true;
}

// Aylıq ödəniş hesablama
function calculateMonthlyPayment(amount, period, rate, initialPayment) {
    const loanAmount = amount - initialPayment;
    const monthlyRate = rate / 12 / 100;
    const monthlyPayment = (loanAmount * monthlyRate * Math.pow(1 + monthlyRate, period)) / 
                          (Math.pow(1 + monthlyRate, period) - 1);

    displayPaymentSchedule(amount, period, monthlyPayment, initialPayment, rate);
}

// Ödəniş cədvəlinin göstərilməsi
function displayPaymentSchedule(amount, period, monthlyPayment, initialPayment, rate) {
    const scheduleContainer = document.getElementById('paymentSchedule');
    if (!scheduleContainer) return;

    let html = '<table class="table table-striped">';
    html += '<thead><tr><th>Ay</th><th>Ödəniş</th><th>Əsas borc</th><th>Faiz</th><th>Qalıq</th></tr></thead>';
    html += '<tbody>';

    let balance = amount - initialPayment;
    let totalInterest = 0;
    let totalPrincipal = 0;

    for (let month = 1; month <= period; month++) {
        const interest = balance * (rate / 12 / 100);
        const principal = monthlyPayment - interest;
        balance -= principal;

        totalInterest += interest;
        totalPrincipal += principal;

        html += `<tr>
            <td>${month}</td>
            <td>${monthlyPayment.toFixed(2)} AZN</td>
            <td>${principal.toFixed(2)} AZN</td>
            <td>${interest.toFixed(2)} AZN</td>
            <td>${Math.max(0, balance.toFixed(2))} AZN</td>
        </tr>`;
    }

    html += '</tbody></table>';
    scheduleContainer.innerHTML = html;

    // Ümumi məlumatların göstərilməsi
    displayCreditSummary(amount, totalPrincipal, totalInterest, initialPayment);
}

// Kredit xülasəsinin göstərilməsi
function displayCreditSummary(amount, totalPrincipal, totalInterest, initialPayment) {
    const summaryContainer = document.getElementById('creditSummary');
    if (!summaryContainer) return;

    summaryContainer.innerHTML = `
        <div class="alert alert-info">
            <h5>Kredit Xülasəsi</h5>
            <p>Ümumi kredit məbləği: ${amount.toFixed(2)} AZN</p>
            <p>İlkin ödəniş: ${initialPayment.toFixed(2)} AZN</p>
            <p>Ödəniləcək əsas məbləğ: ${totalPrincipal.toFixed(2)} AZN</p>
            <p>Ümumi faiz məbləği: ${totalInterest.toFixed(2)} AZN</p>
            <p>Ümumi ödəniləcək məbləğ: ${(totalPrincipal + totalInterest).toFixed(2)} AZN</p>
        </div>`;
}

// Bildiriş sistemi
function setupNotifications() {
    // Bildirişlərin yoxlanması
    checkNotifications();
    
    // Bildirişlərin oxunması
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            markNotificationAsRead(this.dataset.id);
        });
    });
}

// Bildirişlərin yoxlanması
function checkNotifications() {
    fetch('/api/notifications.php')
        .then(response => response.json())
        .then(data => {
            updateNotificationBadge(data.unread);
            updateNotificationList(data.notifications);
        })
        .catch(error => console.error('Bildiriş yoxlama xətası:', error));
}

// DataTables inisializasiyası
function setupDataTables() {
    const tables = document.querySelectorAll('.datatable');
    tables.forEach(table => {
        $(table).DataTable({
            language: {
                url: '/assets/js/datatables-az.json'
            },
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });
}

// Alert göstərilməsi
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    alertContainer.appendChild(alert);

    // 5 saniyə sonra alert-i sil
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Form validasiyası
function validateForm(formId, rules = {}) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        const rule = rules[input.name];
        if (!rule) return;

        const value = input.value.trim();
        const errorElement = document.getElementById(`${input.name}Error`);

        if (rule.required && !value) {
            isValid = false;
            showInputError(input, errorElement, 'Bu sahə məcburidir');
        } else if (rule.minLength && value.length < rule.minLength) {
            isValid = false;
            showInputError(input, errorElement, `Minimum ${rule.minLength} simvol olmalıdır`);
        } else if (rule.pattern && !rule.pattern.test(value)) {
            isValid = false;
            showInputError(input, errorElement, 'Düzgün format deyil');
        } else {
            hideInputError(input, errorElement);
        }
    });

    return isValid;
}

// Input xətasının göstərilməsi
function showInputError(input, errorElement, message) {
    input.classList.add('is-invalid');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

// Input xətasının gizlədilməsi
function hideInputError(input, errorElement) {
    input.classList.remove('is-invalid');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}