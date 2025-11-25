# Wavelog Kayıt Otomasyon — WordPress Eklentisi

Wavelog Kayıt Otomasyon, amatör telsiz kullanıcılarının kayıt işlemlerini yönetmek için hazırlanmış bir WordPress eklentisidir. Bu eklentiyle kullanıcı kayıt formları, yönetici giriş formu, bekleyen kayıt isteklerinin yönetimi ve kısa kod desteği sağlanır.

**Özellikler:**
- Kullanıcı kayıt formu kısa kodu
- Yönetici giriş kısa kodu
- Bekleyen kayıt isteklerinin yönetimi (onayla / reddet)
- Yönetici panelinde kısa kodları hızlıca kopyalama butonları
- Responsive ve modern admin arayüzüne basit CSS/JS düzeltmeleri

**Dosya yapısı (önemli):**
- `wavelog-registration.php` — Eklenti ana dosyası
- `admin/admin-panel.php` — Yönetici paneli görünümü
- `admin/admin-login.php`, `templates/registration-form.php` — Giriş ve kayıt şablonları
- `assets/admin-style.css`, `assets/admin.js` — Yönetici stilleri ve JS
- `languages/` — Dil dosyaları

**Kurulum:**
1. Eklenti klasörünü WordPress `wp-content/plugins/` dizinine yükleyin.
2. WordPress yönetici panelinden eklentiyi etkinleştirin.
3. Kısa kodları kullanarak sayfalar oluşturun veya otomatik oluşturulan sayfaları ziyaret edin.

**Kısa Kodlar (Shortcodes):**
- ` [wavelog_registration_form]` — Kayıt formunu sayfaya ekler.
- ` [wavelog_admin_login]` — Yönetici giriş formunu sayfaya ekler.

Not: Yönetici panelinde `Kısa Kodlar` kutusunda birer `Kopyala` butonu bulunur; bu butonlar tek tıkla ilgili kodu panoya kopyalar.

**Yönetici Paneli Notları:**
- "Bekleyen Kayıt İstekleri" alanı yönetici panelinde listelenir; tablodaki "Onayla" ve "Reddet" butonları işlemi nonce ile güvenli biçimde gönderir.
- Eğer admin panelinde görsel örtüşme (sidebar'in main kutunun üstüne çıkması gibi) yaşarsanız, tarayıcı cache'ini temizleyip sayfayı yenileyin. Eklenti içinde gerekli z-index ve layout düzeltmeleri uygulanmıştır (`assets/admin-style.css`).

**Sürüm Notları (kısa):**
- v1.5.0: Admin CSS/JS güncellemeleri; kısa kod kopyalama butonları eklendi; sidebar-main overlap sorunları için z-index ve boyut düzeltmeleri yapıldı.

**Sorun Giderme:**
- Kopyalama çalışmıyorsa: tarayıcınızın güvenlik ayarları veya eski tarayıcı versiyonları `navigator.clipboard` API'sini engelliyor olabilir; eklenti, fallback olarak `document.execCommand('copy')` kullanır.
- Stil çakışmaları devam ederse: tema veya başka admin eklentileri kendi CSS'leri ile çakışıyor olabilir; bana kullandığınız tema/eklentileri söyleyin, spesifik selector ile daha yüksek öncelikli (specificity) düzeltme ekleyebilirim.

**Destek / Geri Bildirim:**
E-posta: destek@ornekdomain.com (örnek) — hata raporları veya istekler için lütfen eklenti ve WordPress sürüm bilgilerini ekleyin.

**Lisans:**
Kendi kullanımınız için serbesttir. Dağıtım veya türev çalışmalar için lütfen geliştirici ile iletişime geçin.

---
Bu README, yönetici arayüzünde yaptığımız son görsel düzeltileri ve kısa kod kullanımını hızlıca açıklamak amacıyla oluşturulmuştur. Daha detaylı dokümantasyon isterseniz ekleyebilirim.