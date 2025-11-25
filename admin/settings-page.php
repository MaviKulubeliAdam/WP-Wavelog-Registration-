<?php
$settings = get_option('wavelog_registration_settings', array());
?>
<div class="wrap">
    <h1>Wavelog Ayarlar</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Ayarlar başarıyla kaydedildi.</p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Veritabanı Ayarları</h2>
        <p>Kullanıcıların aktarılacağı harici veritabanı bilgilerini girin:</p>

        <form method="post">
            <?php wp_nonce_field('wavelog_save_db_settings'); ?>
            <input type="hidden" name="save_db_settings" value="1">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="db_host">Veritabanı Sunucusu</label></th>
                    <td>
                        <input type="text" name="db_host" id="db_host" value="<?php echo esc_attr($settings['db_host'] ?? 'localhost'); ?>" class="regular-text" required>
                        <p class="description">Örnek: localhost veya 127.0.0.1</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="db_name">Veritabanı Adı</label></th>
                    <td>
                        <input type="text" name="db_name" id="db_name" value="<?php echo esc_attr($settings['db_name'] ?? ''); ?>" class="regular-text" required>
                        <p class="description">Örnek: wavelog_db</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="db_user">Veritabanı Kullanıcı Adı</label></th>
                    <td>
                        <input type="text" name="db_user" id="db_user" value="<?php echo esc_attr($settings['db_user'] ?? ''); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="db_pass">Veritabanı Şifresi</label></th>
                    <td>
                        <input type="password" name="db_pass" id="db_pass" value="<?php echo esc_attr($settings['db_pass'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="db_charset">Veritabanı Karakter Seti</label></th>
                    <td>
                        <input type="text" name="db_charset" id="db_charset" value="<?php echo esc_attr($settings['db_charset'] ?? 'utf8mb4'); ?>" class="regular-text" required>
                        <p class="description">Örnek: utf8mb4 (tavsiye edilen) veya utf8</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Veritabanı Ayarlarını Kaydet</button>
                <button type="button" id="test-db-connection" class="button button-secondary">Veritabanı Bağlantısını Test Et</button>
            </p>
            
            <div id="db-test-result" style="margin-top: 15px;"></div>
        </form>
    </div>

    <div class="card">
        <h2>E-posta Ayarları</h2>
        <p>Otomatik e-posta bildirimleri için ayarları yapılandırın:</p>

        <form method="post">
            <?php wp_nonce_field('wavelog_save_smtp_settings'); ?>
            <input type="hidden" name="save_smtp_settings" value="1">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="smtp_host">SMTP Sunucusu</label></th>
                    <td>
                        <input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr($settings['smtp_host'] ?? ''); ?>" class="regular-text">
                        <p class="description">Örnek: smtp.gmail.com</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_port">SMTP Port</label></th>
                    <td>
                        <input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr($settings['smtp_port'] ?? '587'); ?>" class="small-text">
                        <p class="description">Genellikle 587 (TLS) veya 465 (SSL)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_username">SMTP Kullanıcı Adı</label></th>
                    <td>
                        <input type="text" name="smtp_username" id="smtp_username" value="<?php echo esc_attr($settings['smtp_username'] ?? ''); ?>" class="regular-text">
                        <p class="description">E-posta adresiniz</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_password">SMTP Şifresi</label></th>
                    <td>
                        <input type="password" name="smtp_password" id="smtp_password" value="<?php echo esc_attr($settings['smtp_password'] ?? ''); ?>" class="regular-text">
                        <p class="description">E-posta şifreniz veya uygulama şifresi</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_encryption">Şifreleme</label></th>
                    <td>
                        <select name="smtp_encryption" id="smtp_encryption" class="regular-text">
                            <option value="tls" <?php selected($settings['smtp_encryption'] ?? 'tls', 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($settings['smtp_encryption'] ?? 'tls', 'ssl'); ?>>SSL</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="from_email">Gönderen E-posta</label></th>
                    <td>
                        <input type="email" name="from_email" id="from_email" value="<?php echo esc_attr($settings['from_email'] ?? ''); ?>" class="regular-text">
                        <p class="description">E-postaların hangi adresten gönderileceği</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="from_name">Gönderen Adı</label></th>
                    <td>
                        <input type="text" name="from_name" id="from_name" value="<?php echo esc_attr($settings['from_name'] ?? 'Wavelog System'); ?>" class="regular-text">
                        <p class="description">E-postalarda görünecek gönderen adı</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">E-posta Bildirimleri</th>
                    <td>
                        <label for="admin_notification">
                            <input type="checkbox" name="admin_notification" id="admin_notification" value="1" <?php checked($settings['admin_notification'] ?? '1', '1'); ?>>
                            Yeni kayıt olduğunda yöneticiye e-posta gönder
                        </label>
                        <br>
                        <label for="user_notification">
                            <input type="checkbox" name="user_notification" id="user_notification" value="1" <?php checked($settings['user_notification'] ?? '1', '1'); ?>>
                            Onay/Red işlemlerinde kullanıcıya e-posta gönder
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">E-posta Ayarlarını Kaydet</button>
                <button type="button" id="test-smtp-connection" class="button button-secondary">SMTP Bağlantısını Test Et</button>
            </p>
            
            <div id="smtp-test-result" style="margin-top: 15px;"></div>
        </form>
    </div>

    <div class="card">
        <h2>Mevcut Ayarlar</h2>
        <table class="wp-list-table widefat fixed striped">
            <tr>
                <th>Ayar</th>
                <th>Değer</th>
            </tr>
            <tr>
                <td>Veritabanı Sunucusu</td>
                <td><?php echo esc_html($settings['db_host'] ?? 'Belirtilmemiş'); ?></td>
            </tr>
            <tr>
                <td>Veritabanı Adı</td>
                <td><?php echo esc_html($settings['db_name'] ?? 'Belirtilmemiş'); ?></td>
            </tr>
            <tr>
                <td>Veritabanı Kullanıcı Adı</td>
                <td><?php echo esc_html($settings['db_user'] ?? 'Belirtilmemiş'); ?></td>
            </tr>
            <tr>
                <td>SMTP Sunucusu</td>
                <td><?php echo esc_html($settings['smtp_host'] ?? 'Belirtilmemiş'); ?></td>
            </tr>
            <tr>
                <td>SMTP Kullanıcı Adı</td>
                <td><?php echo esc_html($settings['smtp_username'] ?? 'Belirtilmemiş'); ?></td>
            </tr>
            <tr>
                <td>Gönderen E-posta</td>
                <td><?php echo esc_html($settings['from_email'] ?? 'Belirtilmemiş'); ?></td>
            </tr>
            <tr>
                <td>Yönetici Bildirimi</td>
                <td><?php echo ($settings['admin_notification'] ?? '1') === '1' ? 'Açık' : 'Kapalı'; ?></td>
            </tr>
            <tr>
                <td>Kullanıcı Bildirimi</td>
                <td><?php echo ($settings['user_notification'] ?? '1') === '1' ? 'Açık' : 'Kapalı'; ?></td>
            </tr>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#test-db-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#db-test-result');
        
        $button.prop('disabled', true).text('Test ediliyor...');
        $result.html('<div class="notice notice-info"><p>Veritabanı bağlantısı test ediliyor...</p></div>');
        
        $.post(ajaxurl, {
            action: 'test_db_connection',
            nonce: wavelog_ajax.nonce
        }, function(response) {
            $button.prop('disabled', false).text('Veritabanı Bağlantısını Test Et');
            
            if (response.success) {
                $result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
        }).fail(function() {
            $button.prop('disabled', false).text('Veritabanı Bağlantısını Test Et');
            $result.html('<div class="notice notice-error"><p>Test sırasında bir hata oluştu.</p></div>');
        });
    });
    
    $('#test-smtp-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#smtp-test-result');
        
        $button.prop('disabled', true).text('Test ediliyor...');
        $result.html('<div class="notice notice-info"><p>SMTP bağlantısı test ediliyor...</p></div>');
        
        $.post(ajaxurl, {
            action: 'test_smtp_connection',
            nonce: wavelog_ajax.smtp_nonce
        }, function(response) {
            $button.prop('disabled', false).text('SMTP Bağlantısını Test Et');
            
            if (response.success) {
                $result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
        }).fail(function() {
            $button.prop('disabled', false).text('SMTP Bağlantısını Test Et');
            $result.html('<div class="notice notice-error"><p>Test sırasında bir hata oluştu.</p></div>');
        });
    });
});
</script>

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
.description {
    color: #666;
    font-style: italic;
    margin-top: 4px;
}
</style>