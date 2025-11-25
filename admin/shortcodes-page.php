<?php
?>
<div class="wrap">
    <h1>Wavelog Kısa Kodlar</h1>
    
    <div class="card">
        <h2>Kullanılabilir Kısa Kodlar</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Kısa Kod</th>
                    <th>Açıklama</th>
                    <th>Kullanım</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[wavelog_registration_form]</code></td>
                    <td>Kullanıcı kayıt formunu gösterir</td>
                    <td>Bu kısa kodu kayıt sayfasında kullanın. Kullanıcılar bu form aracılığıyla kayıt isteği gönderebilir.</td>
                </tr>
                <tr>
                    <td><code>[wavelog_admin_login]</code></td>
                    <td>Yönetici giriş formunu gösterir</td>
                    <td>Bu kısa kodu yönetici giriş sayfasında kullanın. WordPress yönetici hesaplarıyla giriş yapılabilir.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Kullanım Örnekleri</h2>
        
        <h3>Kayıt Sayfası Oluşturma</h3>
        <p>Yeni bir sayfa oluşturun ve içeriğine şunu ekleyin:</p>
        <pre><code>[wavelog_registration_form]</code></pre>
        
        <h3>Yönetici Giriş Sayfası Oluşturma</h3>
        <p>Yeni bir sayfa oluşturun ve içeriğine şunu ekleyin:</p>
        <pre><code>[wavelog_admin_login]</code></pre>
        
        <h3>Otomatik Oluşturulan Sayfalar</h3>
        <p>Eklenti etkinleştirildiğinde otomatik olarak şu sayfalar oluşturulur:</p>
        <ul>
            <li><strong>Kayıt Sayfası:</strong> <code><?php echo home_url('/register'); ?></code></li>
            <li><strong>Yönetici Giriş Sayfası:</strong> <code><?php echo home_url('/admin-login'); ?></code></li>
        </ul>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>İpuçları</h2>
        <ul>
            <li>Kısa kodları istediğiniz sayfada veya yazıda kullanabilirsiniz</li>
            <li>Kayıt formu responsive tasarıma sahiptir, tüm cihazlarda düzgün görüntülenir</li>
            <li>Yönetici girişi için WordPress yönetici hesabı gereklidir</li>
            <li>Kayıt isteklerini "Wavelog Kayıtlar" menüsünden yönetebilirsiniz</li>
        </ul>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}
.card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
pre {
    background: #f6f7f7;
    padding: 10px;
    border-radius: 3px;
    border-left: 4px solid #0073aa;
}
</style>