<?php
?>
<div class="wrap wavelog-admin">
    <h1>Wavelog Kayıt İstekleri</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($_GET['message']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($_GET['error']); ?></p>
        </div>
    <?php endif; ?>

    <div class="wavelog-admin-container">
        <div class="wavelog-admin-sidebar">
            <div class="card">
                <h2>Kısa Kodlar</h2>
                <table class="wp-list-table widefat fixed">
                    <thead>
                        <tr>
                            <th>Kısa Kod</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[wavelog_registration_form]</code></td>
                            <td>Kullanıcı kayıt formunu gösterir</td>
                        </tr>
                        <tr>
                            <td><code>[wavelog_admin_login]</code></td>
                            <td>Yönetici giriş formunu gösterir</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Kullanım Örnekleri</h2>
                <p><strong>Kayıt Sayfası:</strong></p>
                <pre><code>[wavelog_registration_form]</code></pre>
                
                <p><strong>Yönetici Giriş Sayfası:</strong></p>
                <pre><code>[wavelog_admin_login]</code></pre>
            </div>

            <div class="card">
                <h2>Otomatik Sayfalar</h2>
                <p>Otomatik oluşturulan sayfalar:</p>
                <ul>
                    <li><strong>Kayıt:</strong> <code><?php echo home_url('/register'); ?></code></li>
                    <li><strong>Yönetici:</strong> <code><?php echo home_url('/admin-login'); ?></code></li>
                </ul>
            </div>
        </div>

        <div class="wavelog-admin-main">
            <div class="main-card">
                <h2>Bekleyen Kayıt İstekleri</h2>
                <?php if (count($registrations) > 0): ?>
                    <div class="table-container">
                        <table class="wp-list-table widefat fixed striped wavelog-registrations-table">
                            <thead>
                                <tr>
                                    <th class="col-id">ID</th>
                                    <th class="col-callsign">Çağrı Kodu</th>
                                    <th class="col-username">Kullanıcı Adı</th>
                                    <th class="col-email">E-posta</th>
                                    <th class="col-name">Ad</th>
                                    <th class="col-name">Soyad</th>
                                    <th class="col-locator">Grid Locator</th>
                                    <th class="col-date">Kayıt Tarihi</th>
                                    <th class="col-actions">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td class="col-id"><?php echo $reg->id; ?></td>
                                    <td class="col-callsign"><?php echo esc_html($reg->user_callsign); ?></td>
                                    <td class="col-username"><?php echo esc_html($reg->user_name); ?></td>
                                    <td class="col-email"><?php echo esc_html($reg->user_email); ?></td>
                                    <td class="col-name"><?php echo esc_html($reg->user_firstname); ?></td>
                                    <td class="col-name"><?php echo esc_html($reg->user_lastname); ?></td>
                                    <td class="col-locator"><?php echo esc_html($reg->user_locator); ?></td>
                                    <td class="col-date"><?php echo date('d.m.Y H:i', strtotime($reg->created_at)); ?></td>
                                    <td class="col-actions">
                                        <div class="action-buttons">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wavelog_approve&id=' . $reg->id), 'wavelog_admin_action'); ?>" 
                                               class="button button-primary" 
                                               onclick="return confirm('Bu kaydı onaylamak istediğinizden emin misiniz?')">
                                                Onayla
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wavelog_reject&id=' . $reg->id), 'wavelog_admin_action'); ?>" 
                                               class="button button-secondary" 
                                               onclick="return confirm('Bu kaydı reddetmek istediğinizden emin misiniz?')">
                                                Reddet
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Bekleyen kayıt bulunamadı</h3>
                        <p>Şu anda onay bekleyen hiç kayıt isteği yok.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.wavelog-admin-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    align-items: flex-start;
}

.wavelog-admin-sidebar {
    flex: 0 0 220px; /* Daha da daralttık */
    min-width: 220px;
    position: sticky;
    top: 20px;
}

.wavelog-admin-main {
    flex: 1;
    min-width: 0;
}

/* Ana kart - tam genişlik ve otomatik boyut */
.main-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 25px;
    margin-bottom: 0;
    width: 100%;
    box-sizing: border-box;
}

.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    width: 100%;
    box-sizing: border-box;
}

.card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
    color: #23282d;
    font-size: 15px;
}

.main-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 12px;
    color: #23282d;
    font-size: 18px;
    margin-bottom: 20px;
}

/* Tablo konteyneri - tam genişlik */
.table-container {
    width: 100%;
    overflow-x: auto;
}

/* Tablo stilleri - esnek ve responsive */
.wavelog-registrations-table {
    width: 100%;
    table-layout: auto;
    border-collapse: collapse;
}

.wavelog-registrations-table th,
.wavelog-registrations-table td {
    padding: 12px 15px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
    border-bottom: 1px solid #e1e1e1;
}

/* Esnek sütun genişlikleri */
.wavelog-registrations-table .col-id {
    width: 60px;
    min-width: 60px;
}

.wavelog-registrations-table .col-callsign {
    width: 120px;
    min-width: 120px;
}

.wavelog-registrations-table .col-username {
    width: 140px;
    min-width: 140px;
}

.wavelog-registrations-table .col-email {
    width: auto;
    min-width: 200px;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wavelog-registrations-table .col-name {
    width: 120px;
    min-width: 120px;
}

.wavelog-registrations-table .col-locator {
    width: 100px;
    min-width: 100px;
}

.wavelog-registrations-table .col-date {
    width: 140px;
    min-width: 140px;
}

.wavelog-registrations-table .col-actions {
    width: 170px;
    min-width: 170px;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
}

.action-buttons .button {
    text-decoration: none;
    white-space: nowrap;
    font-size: 13px;
    padding: 8px 12px;
    height: auto;
    line-height: 1.4;
    flex: 1;
    text-align: center;
}

.empty-state {
    text-align: center;
    padding: 60px 40px;
    color: #646970;
    background: #f9f9f9;
    border-radius: 4px;
    border: 1px dashed #ccd0d4;
}

/* Sidebar içeriği - kompakt */
.wavelog-admin-sidebar .card table {
    font-size: 12px;
}

.wavelog-admin-sidebar .card th,
.wavelog-admin-sidebar .card td {
    padding: 6px 8px;
}

.wavelog-admin-sidebar pre {
    margin: 8px 0;
    padding: 8px;
    font-size: 11px;
}

.wavelog-admin-sidebar code {
    font-size: 11px;
    padding: 1px 4px;
}

.wavelog-admin-sidebar ul {
    margin-left: 15px;
    font-size: 12px;
}

.wavelog-admin-sidebar li {
    margin-bottom: 4px;
}

.wavelog-admin-sidebar p {
    margin: 8px 0;
    font-size: 12px;
    line-height: 1.4;
}

/* Responsive tasarım */
@media (max-width: 1600px) {
    .wavelog-admin-sidebar {
        flex: 0 0 200px;
        min-width: 200px;
    }
    
    .wavelog-registrations-table th,
    .wavelog-registrations-table td {
        padding: 10px 12px;
    }
}

@media (max-width: 1400px) {
    .wavelog-admin-sidebar {
        flex: 0 0 180px;
        min-width: 180px;
    }
    
    .main-card {
        padding: 20px;
    }
    
    .wavelog-registrations-table .col-email {
        min-width: 180px;
        max-width: 250px;
    }
}

@media (max-width: 1200px) {
    .wavelog-admin-container {
        flex-direction: column;
    }
    
    .wavelog-admin-sidebar {
        flex: 0 0 auto;
        min-width: 100%;
        position: static;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
    }
    
    .wavelog-admin-sidebar .card {
        margin-bottom: 0;
    }
    
    .wavelog-admin-main {
        min-width: 100%;
    }
    
    .table-container {
        overflow-x: auto;
    }
}

@media (max-width: 782px) {
    .wavelog-admin-sidebar {
        grid-template-columns: 1fr;
    }
    
    .main-card {
        padding: 15px;
    }
    
    .wavelog-registrations-table th,
    .wavelog-registrations-table td {
        padding: 8px 10px;
        font-size: 13px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .action-buttons .button {
        font-size: 12px;
        padding: 6px 8px;
    }
    
    .wavelog-registrations-table .col-actions {
        width: 130px;
        min-width: 130px;
    }
    
    .wavelog-registrations-table .col-email {
        min-width: 150px;
        max-width: 200px;
    }
}

/* Çok geniş ekranlar için */
@media (min-width: 2000px) {
    .wavelog-admin-container {
        max-width: 95%;
        margin-left: auto;
        margin-right: auto;
    }
    
    .main-card {
        padding: 30px;
    }
    
    .wavelog-registrations-table th,
    .wavelog-registrations-table td {
        padding: 15px 20px;
        font-size: 14px;
    }
    
    .action-buttons .button {
        font-size: 14px;
        padding: 10px 16px;
    }
}
</style>