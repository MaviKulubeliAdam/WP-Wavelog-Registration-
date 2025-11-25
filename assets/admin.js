jQuery(document).ready(function($) {
    // Bağlantı testi işlevleri
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

    // Onay/Red işlemleri için onay dialogu
    $('.action-buttons a').on('click', function(e) {
        var $link = $(this);
        var action = $link.hasClass('button-primary') ? 'Onayla' : 'Reddet';
        var callsign = $link.closest('tr').find('.col-callsign').text().trim();
        
        if (!confirm(callsign + ' çağrı kodlu kaydı ' + action.toLowerCase() + 'mak istediğinizden emin misiniz?')) {
            e.preventDefault();
            return false;
        }
    });
});