<?php
// CSRF token için WordPress nonce kullanımı
$csrf_token = wp_create_nonce('wavelog_registration');

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wavelog_register'])) {
    if (!wp_verify_nonce($_POST['csrf_token'], 'wavelog_registration')) {
        $errors[] = "Güvenlik hatası.";
    }

    // Inputları al ve temizle
    $user_callsign = sanitize_text_field($_POST['user_callsign'] ?? '');
    $user_name = $user_callsign;
    $user_firstname = sanitize_text_field($_POST['user_firstname'] ?? '');
    $user_lastname = sanitize_text_field($_POST['user_lastname'] ?? '');
    $user_email = sanitize_email($_POST['user_email'] ?? '');
    $user_password = $_POST['user_password'] ?? '';
    $user_password_confirm = $_POST['user_password_confirm'] ?? '';
    $user_locator = sanitize_text_field($_POST['user_locator'] ?? '');

    // ZORUNLU ALAN KONTROLLERİ
    if (empty($user_callsign)) $errors[] = "Çağrı işareti gereklidir.";
    if (empty($user_firstname)) $errors[] = "Ad alanı gereklidir.";
    if (empty($user_lastname)) $errors[] = "Soyad alanı gereklidir.";
    if (empty($user_email) || !is_email($user_email)) $errors[] = "Geçerli e-posta adresi gereklidir.";
    if (empty($user_password)) $errors[] = "Şifre gereklidir.";
    if (empty($user_password_confirm)) $errors[] = "Şifre doğrulama gereklidir.";
    if (empty($user_locator)) $errors[] = "Grid Locator gereklidir.";

    // Şifre uzunluk kontrolü
    if (!empty($user_password) && strlen($user_password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }

    // Şifre eşleşme kontrolü
    if (!empty($user_password) && !empty($user_password_confirm) && $user_password !== $user_password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    if (empty($errors)) {
        global $wpdb;
        
        // Benzersizlik kontrolü - SADECE ASIL VERİTABANINDA
        $duplicate_in_main_db = WavelogRegistration::check_duplicate_in_main_db($user_name, $user_email);
        
        if ($duplicate_in_main_db) {
            $errors[] = "Bu kullanıcı adı veya e-posta adresi zaten kayıtlı.";
        }

        if (empty($errors)) {
            // Slug oluştur
            $slug = WavelogRegistration::generate_slug($user_name);
            
            // Şifreyi hashle
            $hash = password_hash($user_password, PASSWORD_DEFAULT);
            if (!$hash) {
                    $errors[] = "Şifre hashleme hatası.";
                }

            // Kayıt isteğini WordPress veritabanına kaydet
            $table_name = $wpdb->prefix . 'wavelog_registration_requests';
            $result = $wpdb->insert($table_name, array(
                'user_callsign' => strtoupper($user_callsign),
                'user_name' => $user_name,
                'user_password' => $hash,
                'user_email' => $user_email,
                'user_firstname' => ucwords(strtolower($user_firstname)),
                'user_lastname' => ucwords(strtolower($user_lastname)),
                'user_locator' => strtoupper($user_locator),
                'slug' => $slug,
                'status' => 'pending'
            ));

            // registration-form.php dosyasında, kayıt başarılı olduğunda:
if ($result === false) {
    $errors[] = "Veritabanı hatası: " . $wpdb->last_error;
} else {
    $notice = "Kayıt isteğiniz alındı. Yönetici onayından sonra hesabınız aktif olacaktır.";
    
    // YÖNETİCİ BİLDİRİMİ E-POSTASI GÖNDER
    $registration_id = $wpdb->insert_id;
    $wavelog_registration = new WavelogRegistration();
    $wavelog_registration->send_admin_notification($registration_id);
    
    // Formu temizle
    $user_callsign = $user_firstname = $user_lastname = $user_email = $user_locator = '';
}
            }
        }
    }

?>

<div class="wavelog-registration-form">
    <div class="card">
        <h1>Kayıt Ol</h1>
        <p class="muted">Hesap oluşturmak için aşağıdaki formu doldurun. Hesabınız yönetici onayından sonra aktif olacaktır.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <strong>Hatalar:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="alert success">
                <?php echo esc_html($notice); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="registrationForm">
            <input type="hidden" name="csrf_token" value="<?php echo esc_attr($csrf_token); ?>">
            <input type="hidden" name="wavelog_register" value="1">

            <!-- Çağrı İşareti -->
            <div class="form-group">
                <label for="user_callsign">
                    Çağrı İşareti<span class="required">*</span>
                </label>
                <input id="user_callsign" name="user_callsign" required 
                       placeholder="Örnek: TA1ABC" 
                       value="<?php echo esc_attr($user_callsign ?? ''); ?>"
                       oninput="this.value = this.value.toUpperCase()"
                       pattern="[A-Za-z0-9]{3,15}"
                       title="Çağrı işaretinizi giriniz (sadece harf ve rakam)">
            </div>

            <!-- Ad -->
            <div class="form-group">
                <label for="user_firstname">
                    Ad <span class="required">*</span>
                </label>
                <input id="user_firstname" name="user_firstname" required
                       placeholder="Adınız" 
                       value="<?php echo esc_attr($user_firstname ?? ''); ?>"
                       oninput="formatName(this)"
                       pattern="[A-Za-zÇçĞğİıÖöŞşÜü\s]{2,50}"
                       title="Lütfen geçerli bir ad giriniz">
            </div>

            <!-- Soyad -->
            <div class="form-group">
                <label for="user_lastname">
                    Soyad <span class="required">*</span>
                </label>
                <input id="user_lastname" name="user_lastname" required
                       placeholder="Soyadınız" 
                       value="<?php echo esc_attr($user_lastname ?? ''); ?>"
                       oninput="formatName(this)"
                       pattern="[A-Za-zÇçĞğİıÖöŞşÜü\s]{2,50}"
                       title="Lütfen geçerli bir soyad giriniz">
            </div>

            <!-- E-posta -->
            <div class="form-group">
                <label for="user_email">
                    E-posta <span class="required">*</span>
                </label>
                <input id="user_email" name="user_email" type="email" required 
                       placeholder="ornek@mail.com" 
                       value="<?php echo esc_attr($user_email ?? ''); ?>">
            </div>

            <!-- Şifre -->
            <div class="form-group">
                <label for="user_password">
                    Şifre <span class="required">*</span>
                </label>
                <input id="user_password" name="user_password" type="password" required
                       minlength="6"
                       placeholder="En az 6 karakter"
                       autocomplete="new-password">
            </div>

            <!-- Şifre Doğrulama -->
            <div class="form-group">
                <label for="user_password_confirm">
                    Şifre Doğrulama <span class="required">*</span>
                </label>
                <input id="user_password_confirm" name="user_password_confirm" type="password" required
                       minlength="6"
                       placeholder="Şifrenizi tekrar girin"
                       autocomplete="new-password">
                <div id="password-match-message" style="margin-top: 5px; font-size: 13px;"></div>
            </div>

            <!-- Grid Locator -->
            <div class="form-group">
                <label for="user_locator">
                    Grid Locator <span class="required">*</span>
                </label>
                <input id="user_locator" name="user_locator" required 
                       placeholder="Örnek: KN01AB" 
                       value="<?php echo esc_attr($user_locator ?? ''); ?>"
                       oninput="this.value = this.value.toUpperCase()"
                       pattern="[A-Za-z]{2}[0-9]{2}[A-Za-z]{2}"
                       title="Grid Locator formatı: AA00AA">
            </div>

            <button class="btn" type="submit">Hesap Oluştur</button>
            <div class="foot">
                <span class="required">*</span> işaretli alanlar zorunludur.
                <br>Şifreler güvenli bir şekilde hashlenerek saklanmaktadır.
            </div>
        </form>
        
        <div style="text-align: center;">
            <a href="<?php echo home_url(); ?>" class="back-link">← Ana Sayfaya Dön</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const callsignInput = document.getElementById('user_callsign');
    const locatorInput = document.getElementById('user_locator');
    const firstNameInput = document.getElementById('user_firstname');
    const lastNameInput = document.getElementById('user_lastname');
    const passwordInput = document.getElementById('user_password');
    const passwordConfirmInput = document.getElementById('user_password_confirm');
    const passwordMatchMessage = document.getElementById('password-match-message');
    
    // Sayfa yüklendiğinde mevcut değerleri formatla
    if (callsignInput.value) {
        callsignInput.value = callsignInput.value.toUpperCase();
    }
    if (locatorInput.value) {
        locatorInput.value = locatorInput.value.toUpperCase();
    }
    if (firstNameInput.value) {
        formatName(firstNameInput);
    }
    if (lastNameInput.value) {
        formatName(lastNameInput);
    }
    
    // Şifre eşleşme kontrolü
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = passwordConfirmInput.value;
        
        if (password === '' && confirmPassword === '') {
            passwordMatchMessage.textContent = '';
            passwordConfirmInput.style.borderColor = '';
            passwordConfirmInput.classList.remove('error-field');
        } else if (confirmPassword === '') {
            passwordMatchMessage.textContent = '';
            passwordConfirmInput.style.borderColor = '';
            passwordConfirmInput.classList.remove('error-field');
        } else if (password === confirmPassword) {
            passwordMatchMessage.textContent = '✓ Şifreler eşleşiyor';
            passwordMatchMessage.style.color = '#51cf66';
            passwordConfirmInput.style.borderColor = '#51cf66';
            passwordConfirmInput.classList.remove('error-field');
        } else {
            passwordMatchMessage.textContent = '✗ Şifreler eşleşmiyor';
            passwordMatchMessage.style.color = '#ff6b6b';
            passwordConfirmInput.style.borderColor = '#ff6b6b';
            passwordConfirmInput.classList.add('error-field');
        }
    }
    
    // Şifre alanları değiştiğinde kontrol et
    passwordInput.addEventListener('input', checkPasswordMatch);
    passwordConfirmInput.addEventListener('input', checkPasswordMatch);
    
    // Ad ve soyad formatlama fonksiyonu
    window.formatName = function(input) {
        const value = input.value;
        if (value.length > 0) {
            // Her kelimenin ilk harfini büyük, diğer harfleri küçük yap
            input.value = value.toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }
    };
    
    // Form gönderilmeden önce son kontroller
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Zorunlu alan kontrolü
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = '#ff6b6b';
                field.classList.add('error-field');
            } else {
                field.style.borderColor = '';
                field.classList.remove('error-field');
            }
        });
        
        // Grid Locator format kontrolü
        const locatorValue = locatorInput.value.trim();
        if (locatorValue && !/^[A-Z]{2}[0-9]{2}[A-Z]{2}$/.test(locatorValue)) {
            isValid = false;
            locatorInput.style.borderColor = '#ff6b6b';
            locatorInput.classList.add('error-field');
            alert('Grid Locator formatı hatalı. Örnek: KN01AB');
        }
        
        // Çağrı kodu format kontrolü
        const callsignValue = callsignInput.value.trim();
        if (callsignValue && !/^[A-Z0-9]{3,15}$/.test(callsignValue)) {
            isValid = false;
            callsignInput.style.borderColor = '#ff6b6b';
            callsignInput.classList.add('error-field');
            alert('Çağrı işareti formatı hatalı. Sadece harf ve rakam kullanabilirsiniz (3-15 karakter).');
        }
        
        // Ad format kontrolü
        const firstNameValue = firstNameInput.value.trim();
        if (firstNameValue && !/^[A-Za-zÇçĞğİıÖöŞşÜü\s]{2,50}$/.test(firstNameValue)) {
            isValid = false;
            firstNameInput.style.borderColor = '#ff6b6b';
            firstNameInput.classList.add('error-field');
            alert('Ad alanında sadece harf kullanabilirsiniz (en az 2 karakter).');
        }
        
        // Soyad format kontrolü
        const lastNameValue = lastNameInput.value.trim();
        if (lastNameValue && !/^[A-Za-zÇçĞğİıÖöŞşÜü\s]{2,50}$/.test(lastNameValue)) {
            isValid = false;
            lastNameInput.style.borderColor = '#ff6b6b';
            lastNameInput.classList.add('error-field');
            alert('Soyad alanında sadece harf kullanabilirsiniz (en az 2 karakter).');
        }
        
        // Şifre uzunluk kontrolü
        const passwordValue = passwordInput.value;
        if (passwordValue && passwordValue.length < 6) {
            isValid = false;
            passwordInput.style.borderColor = '#ff6b6b';
            passwordInput.classList.add('error-field');
            alert('Şifre en az 6 karakter olmalıdır.');
        }
        
        // Şifre eşleşme kontrolü
        const confirmPasswordValue = passwordConfirmInput.value;
        if (passwordValue !== confirmPasswordValue) {
            isValid = false;
            passwordConfirmInput.style.borderColor = '#ff6b6b';
            passwordConfirmInput.classList.add('error-field');
            alert('Şifreler eşleşmiyor. Lütfen aynı şifreyi girdiğinizden emin olun.');
        }
        
        if (!isValid) {
            e.preventDefault();
            // İlk hatalı alana odaklan
            const firstErrorField = form.querySelector('.error-field');
            if (firstErrorField) {
                firstErrorField.focus();
            }
        }
    });
    
    // Input focus olduğunda border rengini sıfırla
    const inputs = form.querySelectorAll('input');
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.style.borderColor = '';
            this.classList.remove('error-field');
        });
        
        // Input değiştiğinde real-time validasyon
        input.addEventListener('input', function() {
            if (this.value.trim() && this.checkValidity()) {
                this.style.borderColor = '#51cf66';
            } else {
                this.style.borderColor = '';
            }
        });
    });
});
</script>