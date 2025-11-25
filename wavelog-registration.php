<?php
/**
 * Plugin Name: Wavelog Kayıt Yönetim Sistemi
 * Description: Kullanıcı kayıt onay sistemi ve yönetici paneli
 * Version: 1.5.0
 * Author: TA4AQG - Erkin Mercan
 * Text Domain: wavelog-registration
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Eklenti sabitleri
define('WAVELOG_REGISTRATION_VERSION', '1.5.0');
define('WAVELOG_REGISTRATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAVELOG_REGISTRATION_PLUGIN_PATH', plugin_dir_path(__FILE__));

class WavelogRegistration {
    
    private $settings;
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Kısa kodları kaydet
        add_shortcode('wavelog_registration_form', array($this, 'registration_form_shortcode'));
        add_shortcode('wavelog_admin_login', array($this, 'admin_login_shortcode'));
        
        // Ayarları yükle
        $this->load_settings();
        
        // AJAX işlemleri
        add_action('wp_ajax_test_db_connection', array($this, 'test_db_connection'));
        add_action('wp_ajax_test_smtp_connection', array($this, 'test_smtp_connection'));
        
        // Admin POST işlemleri için admin_post hook'u
        add_action('admin_post_wavelog_approve', array($this, 'handle_approve'));
        add_action('admin_post_wavelog_reject', array($this, 'handle_reject'));
    }
    
    public function activate() {
        $this->create_tables();
        $this->create_pages();
        
        // Varsayılan ayarları kaydet
        $default_settings = array(
            'db_host' => 'localhost',
            'db_name' => '',
            'db_user' => '',
            'db_pass' => '',
            'db_charset' => 'utf8mb4',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'from_email' => '',
            'from_name' => 'Wavelog System',
            'admin_notification' => '1',
            'user_notification' => '1'
        );
        
        if (!get_option('wavelog_registration_settings')) {
            add_option('wavelog_registration_settings', $default_settings);
        }
    }
    
    public function deactivate() {
        // Temizlik işlemleri
    }
    
    private function load_settings() {
        $this->settings = get_option('wavelog_registration_settings', array());
    }
    
    public function init() {
        load_plugin_textdomain('wavelog-registration', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    // Onaylama işlemi için admin_post hook'u
    public function handle_approve() {
        $this->handle_admin_action('approve');
    }
    
    // Reddetme işlemi için admin_post hook'u
    public function handle_reject() {
        $this->handle_admin_action('reject');
    }
    
    // Ortak admin işlem handler'ı
    private function handle_admin_action($action) {
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok.');
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wavelog_admin_action')) {
            wp_die('Güvenlik hatası.');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            wp_redirect(admin_url('admin.php?page=wavelog-registration&error=Geçersiz ID.'));
            exit;
        }
        
        if ($action === 'approve') {
            $this->approve_registration($id);
        } elseif ($action === 'reject') {
            $this->reject_registration($id);
        }
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wavelog_registration_requests';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_callsign VARCHAR(50) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            user_password VARCHAR(255) NOT NULL,
            user_email VARCHAR(100) NOT NULL,
            user_firstname VARCHAR(100),
            user_lastname VARCHAR(100),
            user_locator VARCHAR(16) NOT NULL DEFAULT '',
            slug VARCHAR(50) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX status_index (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function create_pages() {
        // Kayıt sayfası - sadece yoksa oluştur
        if (!get_page_by_path('register')) {
            $registration_page = array(
                'post_title'    => 'Kayıt Ol',
                'post_content'  => '[wavelog_registration_form]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => 'register'
            );
            wp_insert_post($registration_page);
        }
        
        // Yönetici giriş sayfası - sadece yoksa oluştur
        if (!get_page_by_path('admin-login')) {
            $admin_login_page = array(
                'post_title'    => 'Yönetici Girişi',
                'post_content'  => '[wavelog_admin_login]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => 'admin-login'
            );
            wp_insert_post($admin_login_page);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Wavelog Kayıtlar',
            'Wavelog Kayıtlar',
            'manage_options',
            'wavelog-registration',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
        
        // Ayarlar alt menüsü
        add_submenu_page(
            'wavelog-registration',
            'Wavelog Ayarlar',
            'Ayarlar',
            'manage_options',
            'wavelog-registration-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok.');
        }

        $registrations = $this->get_pending_registrations();
        
        // Admin panel şablonunu include et
        include WAVELOG_REGISTRATION_PLUGIN_PATH . 'admin/admin-panel.php';
    }
    
    // settings_page fonksiyonunu güncelleyin:
public function settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkiniz yok.');
    }
    
    // Ayarları kaydet - iki ayrı form için
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_db_settings'])) {
            $this->save_db_settings();
        } elseif (isset($_POST['save_smtp_settings'])) {
            $this->save_smtp_settings();
        }
    }

    include WAVELOG_REGISTRATION_PLUGIN_PATH . 'admin/settings-page.php';
}

// Yeni fonksiyon: sadece veritabanı ayarlarını kaydeder
private function save_db_settings() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'wavelog_save_db_settings')) {
        wp_die('Güvenlik hatası.');
    }

    // Mevcut ayarları al
    $current_settings = get_option('wavelog_registration_settings', array());
    
    // Sadece veritabanı ayarlarını güncelle
    $current_settings['db_host'] = sanitize_text_field($_POST['db_host']);
    $current_settings['db_name'] = sanitize_text_field($_POST['db_name']);
    $current_settings['db_user'] = sanitize_text_field($_POST['db_user']);
    $current_settings['db_pass'] = $_POST['db_pass'];
    $current_settings['db_charset'] = sanitize_text_field($_POST['db_charset']);

    update_option('wavelog_registration_settings', $current_settings);
    $this->load_settings();
    
    wp_redirect(admin_url('admin.php?page=wavelog-registration-settings&message=1'));
    exit;
}

// Yeni fonksiyon: sadece SMTP ayarlarını kaydeder
private function save_smtp_settings() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'wavelog_save_smtp_settings')) {
        wp_die('Güvenlik hatası.');
    }

    // Mevcut ayarları al
    $current_settings = get_option('wavelog_registration_settings', array());
    
    // Sadece SMTP ayarlarını güncelle
    $current_settings['smtp_host'] = sanitize_text_field($_POST['smtp_host']);
    $current_settings['smtp_port'] = sanitize_text_field($_POST['smtp_port']);
    $current_settings['smtp_username'] = sanitize_text_field($_POST['smtp_username']);
    $current_settings['smtp_password'] = $_POST['smtp_password'];
    $current_settings['smtp_encryption'] = sanitize_text_field($_POST['smtp_encryption']);
    $current_settings['from_email'] = sanitize_email($_POST['from_email']);
    $current_settings['from_name'] = sanitize_text_field($_POST['from_name']);
    $current_settings['admin_notification'] = isset($_POST['admin_notification']) ? '1' : '0';
    $current_settings['user_notification'] = isset($_POST['user_notification']) ? '1' : '0';

    update_option('wavelog_registration_settings', $current_settings);
    $this->load_settings();
    
    wp_redirect(admin_url('admin.php?page=wavelog-registration-settings&message=1'));
    exit;
}
    
    public function test_db_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'test_db_connection')) {
            wp_die('Güvenlik hatası.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok.');
        }

        $settings = $this->settings;
        
        if (empty($settings['db_host']) || empty($settings['db_name']) || empty($settings['db_user'])) {
            wp_send_json_error('Veritabanı ayarları eksik. Lütfen tüm alanları doldurun.');
        }

        try {
            $dsn = "mysql:host=" . $settings['db_host'] . ";dbname=" . $settings['db_name'] . ";charset=" . $settings['db_charset'];
            $pdo = new PDO($dsn, $settings['db_user'], $settings['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $stmt = $pdo->query("SELECT 1");
            $result = $stmt->fetch();
            
            wp_send_json_success('Veritabanı bağlantısı başarılı!');
        } catch (PDOException $ex) {
            wp_send_json_error('Veritabanı bağlantı hatası: ' . $ex->getMessage());
        }
    }
    
    // SMTP bağlantı testi
    public function test_smtp_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'test_smtp_connection')) {
            wp_die('Güvenlik hatası.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok.');
        }

        $settings = $this->settings;
        
        if (empty($settings['smtp_host']) || empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
            wp_send_json_error('SMTP ayarları eksik. Lütfen tüm SMTP alanlarını doldurun.');
        }

        try {
            // Test e-postası gönder
            $test_email = $settings['smtp_username'];
            $test_subject = 'Wavelog SMTP Test';
            $test_body = 'Bu bir test e-postasıdır. SMTP ayarlarınız doğru çalışıyor.';
            
            $result = $this->send_email($test_email, $test_subject, $test_body);
            
            if ($result) {
                wp_send_json_success('SMTP bağlantısı başarılı! Test e-postası gönderildi.');
            } else {
                wp_send_json_error('SMTP bağlantısı başarılı ancak e-posta gönderilemedi.');
            }
        } catch (Exception $ex) {
            wp_send_json_error('SMTP bağlantı hatası: ' . $ex->getMessage());
        }
    }
    
    // E-posta gönderme fonksiyonu
    private function send_email($to, $subject, $body, $headers = array()) {
        $settings = $this->settings;
        
        // SMTP ayarları yoksa standart wp_mail kullan
        if (empty($settings['smtp_host']) || empty($settings['smtp_username'])) {
            $from_email = !empty($settings['from_email']) ? $settings['from_email'] : get_bloginfo('admin_email');
            $from_name = !empty($settings['from_name']) ? $settings['from_name'] : get_bloginfo('name');
            
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );
            
            return wp_mail($to, $subject, $body, $headers);
        }
        
        // PHPMailer ile SMTP gönderimi
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        
        $from_email = !empty($settings['from_email']) ? $settings['from_email'] : $settings['smtp_username'];
        $from_name = !empty($settings['from_name']) ? $settings['from_name'] : 'Wavelog System';
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        $result = wp_mail($to, $subject, $body, $headers);
        
        // SMTP ayarını sıfırla
        remove_action('phpmailer_init', array($this, 'configure_smtp'));
        
        return $result;
    }
    
    // SMTP konfigürasyonu
    public function configure_smtp($phpmailer) {
        $settings = $this->settings;
        
        $phpmailer->isSMTP();
        $phpmailer->Host = $settings['smtp_host'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = $settings['smtp_port'];
        $phpmailer->Username = $settings['smtp_username'];
        $phpmailer->Password = $settings['smtp_password'];
        
        if ($settings['smtp_encryption'] === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } else {
            $phpmailer->SMTPSecure = 'tls';
        }
        
        $phpmailer->From = !empty($settings['from_email']) ? $settings['from_email'] : $settings['smtp_username'];
        $phpmailer->FromName = !empty($settings['from_name']) ? $settings['from_name'] : 'Wavelog System';
    }
    
    // Yönetici bildirimi
    public function send_admin_notification($registration_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wavelog_registration_requests';
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $registration_id), ARRAY_A);
        
        if (!$request) return false;
        
        $settings = $this->settings;
        
        if ($settings['admin_notification'] !== '1') {
            return false;
        }
        
        $admin_email = get_bloginfo('admin_email');
        $subject = 'Yeni Kayıt İsteği - ' . $request['user_callsign'];
        $body = "Yeni bir kayıt isteği alındı:\n\n" .
                "Çağrı İşareti: " . $request['user_callsign'] . "\n" .
                "Ad: " . $request['user_firstname'] . "\n" .
                "Soyad: " . $request['user_lastname'] . "\n" .
                "E-posta: " . $request['user_email'] . "\n" .
                "Grid Locator: " . $request['user_locator'] . "\n\n" .
                "Kayıt Tarihi: " . date('d.m.Y H:i', strtotime($request['created_at'])) . "\n\n" .
                "Lütfen yönetici panelinden inceleyin.";
        
        return $this->send_email($admin_email, $subject, $body);
    }
    
    // Onay e-postası
    private function send_approval_email($user_email, $user_data) {
        $settings = $this->settings;
        
        if ($settings['user_notification'] !== '1') {
            return false;
        }
        
        $subject = 'Kaydınız Onaylandı - ' . $user_data['callsign'];
        $body = "Sayın " . $user_data['firstname'] . " " . $user_data['lastname'] . ",\n\n" .
                $user_data['callsign'] . " çağrı işaretli kaydınız yönetici tarafından onaylanmıştır.\n\n" .
                "Artık Wavelog sistemine giriş yapabilirsiniz.\n\n" .
                "Saygılarımızla,\nWavelog Ekibi";
        
        return $this->send_email($user_email, $subject, $body);
    }
    
    // Red e-postası
    private function send_rejection_email($user_email, $user_data) {
        $settings = $this->settings;
        
        if ($settings['user_notification'] !== '1') {
            return false;
        }
        
        $subject = 'Kaydınız Reddedildi - ' . $user_data['callsign'];
        $body = "Sayın " . $user_data['firstname'] . " " . $user_data['lastname'] . ",\n\n" .
                $user_data['callsign'] . " çağrı işaretli kaydınız yönetici tarafından incelenmiş ve reddedilmiştir.\n\n" .
                "Daha fazla bilgi için sistem yöneticisi ile iletişime geçebilirsiniz.\n\n" .
                "Saygılarımızla,\nWavelog Ekibi";
        
        return $this->send_email($user_email, $subject, $body);
    }
    
    private function get_pending_registrations() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wavelog_registration_requests';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'pending' ORDER BY created_at DESC");
    }
    
    private function approve_registration($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wavelog_registration_requests';
        
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        if (!$request) {
            wp_redirect(admin_url('admin.php?page=wavelog-registration&error=Kayıt bulunamadı.'));
            exit;
        }
        
        try {
            $settings = $this->settings;
            
            if (empty($settings['db_host']) || empty($settings['db_name']) || empty($settings['db_user'])) {
                wp_redirect(admin_url('admin.php?page=wavelog-registration&error=Veritabanı ayarları yapılandırılmamış. Lütfen ayarlar sayfasından kontrol edin.'));
                exit;
            }

            $main_dsn = "mysql:host=" . $settings['db_host'] . ";dbname=" . $settings['db_name'] . ";charset=" . $settings['db_charset'];
            $main_pdo = new PDO($main_dsn, $settings['db_user'], $settings['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $slug = $request->slug;
            
            $default_values = [
                'user_type' => '3',
                'clubstation' => 0,
                'user_locator' => $request->user_locator ?? '',
                'user_timezone' => 1,
                'user_lotw_name' => NULL,
                'user_lotw_password' => NULL,
                'user_eqsl_name' => NULL,
                'user_eqsl_password' => NULL,
                'user_eqsl_qth_nickname' => NULL,
                'active_station_logbook' => NULL,
                'user_language' => 'turkish',
                'user_clublog_name' => NULL,
                'user_clublog_password' => NULL,
                'user_clublog_callsign' => NULL,
                'user_measurement_base' => 'K',
                'user_date_format' => 'd/m/y',
                'user_stylesheet' => 'darkly',
                'user_sota_lookup' => 0,
                'user_wwff_lookup' => 0,
                'user_pota_lookup' => 0,
                'user_qth_lookup' => 0,
                'user_show_notes' => 0,
                'user_column1' => 'Mode',
                'user_column2' => 'RSTS',
                'user_column3' => 'RSTR',
                'user_column4' => 'Band',
                'user_column5' => 'Country',
                'reset_password_code' => NULL,
                'reset_password_date' => NULL,
                'last_seen' => NULL,
                'user_show_profile_image' => 0,
                'user_previous_qsl_type' => 0,
                'user_amsat_status_upload' => 0,
                'user_mastodon_url' => NULL,
                'user_default_band' => 'ALL',
                'user_default_confirmation' => 'QL',
                'user_quicklog_enter' => 0,
                'user_quicklog' => 0,
                'user_qso_end_times' => 0,
                'winkey' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'modified_at' => date('Y-m-d H:i:s'),
                'login_attempts' => 0,
            ];
            
            $sql = "INSERT INTO users
                (user_name, user_password, user_email, user_type, clubstation, user_callsign, user_locator, user_firstname, user_lastname, user_timezone, user_lotw_name, user_lotw_password, user_eqsl_name, user_eqsl_password, user_eqsl_qth_nickname, active_station_logbook, user_language, user_clublog_name, user_clublog_password, user_clublog_callsign, user_measurement_base, user_date_format, user_stylesheet, user_sota_lookup, user_wwff_lookup, user_pota_lookup, user_qth_lookup, user_show_notes, user_column1, user_column2, user_column3, user_column4, user_column5, reset_password_code, reset_password_date, last_seen, user_show_profile_image, user_previous_qsl_type, user_amsat_status_upload, user_mastodon_url, user_default_band, user_default_confirmation, user_quicklog_enter, user_quicklog, user_qso_end_times, winkey, created_at, modified_at, login_attempts, slug)
                VALUES
                (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
            $params = [
                $request->user_name ?? '',
                $request->user_password ?? '',
                $request->user_email ?? '',
                $default_values['user_type'],
                $default_values['clubstation'],
                $request->user_callsign ?? '',
                $default_values['user_locator'],
                $request->user_firstname ?? '',
                $request->user_lastname ?? '',
                $default_values['user_timezone'],
                $default_values['user_lotw_name'],
                $default_values['user_lotw_password'],
                $default_values['user_eqsl_name'],
                $default_values['user_eqsl_password'],
                $default_values['user_eqsl_qth_nickname'],
                $default_values['active_station_logbook'],
                $default_values['user_language'],
                $default_values['user_clublog_name'],
                $default_values['user_clublog_password'],
                $default_values['user_clublog_callsign'],
                $default_values['user_measurement_base'],
                $default_values['user_date_format'],
                $default_values['user_stylesheet'],
                $default_values['user_sota_lookup'],
                $default_values['user_wwff_lookup'],
                $default_values['user_pota_lookup'],
                $default_values['user_qth_lookup'],
                $default_values['user_show_notes'],
                $default_values['user_column1'],
                $default_values['user_column2'],
                $default_values['user_column3'],
                $default_values['user_column4'],
                $default_values['user_column5'],
                $default_values['reset_password_code'],
                $default_values['reset_password_date'],
                $default_values['last_seen'],
                $default_values['user_show_profile_image'],
                $default_values['user_previous_qsl_type'],
                $default_values['user_amsat_status_upload'],
                $default_values['user_mastodon_url'],
                $default_values['user_default_band'],
                $default_values['user_default_confirmation'],
                $default_values['user_quicklog_enter'],
                $default_values['user_quicklog'],
                $default_values['user_qso_end_times'],
                $default_values['winkey'],
                $default_values['created_at'],
                $default_values['modified_at'],
                $default_values['login_attempts'],
                $slug
            ];
            
            $main_pdo->beginTransaction();
            $stmt = $main_pdo->prepare($sql);
            $stmt->execute($params);
            $uid = (int)$main_pdo->lastInsertId();
            
            try {
                $main_pdo->exec("INSERT INTO bandxuser (bandid, userid)
                            SELECT bands.id, {$uid} FROM bands");
            } catch (Exception $e) {}
            
            try {
                $main_pdo->exec("INSERT INTO paper_types (user_id, paper_name, metric, width, orientation, height)
                            SELECT {$uid}, paper_name, metric, width, orientation, height
                            FROM paper_types WHERE user_id = 0");
            } catch (Exception $e) {}
            
            $qso_icon = json_encode(['icon' => 'fas fa-dot-circle', 'color' => '#ff0000']);
            $qsoconfirm_icon = json_encode(['icon' => 'fas fa-dot-circle', 'color' => '#00ff00']);
            
            $stmt = $main_pdo->prepare("INSERT INTO user_options (user_id, option_type, option_name, option_key, option_value)
                        VALUES (?, 'map_custom', 'icon', 'qso', ?)");
            $stmt->execute([$uid, $qso_icon]);
            
            $stmt = $main_pdo->prepare("INSERT INTO user_options (user_id, option_type, option_name, option_key, option_value)
                        VALUES (?, 'map_custom', 'icon', 'qsoconfirm', ?)");
            $stmt->execute([$uid, $qsoconfirm_icon]);
            
            $main_pdo->commit();
            
            // Kullanıcıya onay e-postası gönder
            $user_data = array(
                'callsign' => $request->user_callsign,
                'firstname' => $request->user_firstname,
                'lastname' => $request->user_lastname
            );
            $this->send_approval_email($request->user_email, $user_data);
            
            $wpdb->delete($table_name, ['id' => $id]);
            
            wp_redirect(admin_url('admin.php?page=wavelog-registration&message=Kullanıcı başarıyla onaylandı ve harici veritabanına eklendi.'));
            exit;
            
        } catch (PDOException $ex) {
            if (isset($main_pdo)) {
                $main_pdo->rollBack();
            }
            wp_redirect(admin_url('admin.php?page=wavelog-registration&error=' . urlencode('Harici veritabanı hatası: ' . $ex->getMessage())));
            exit;
        } catch (Exception $ex) {
            if (isset($main_pdo)) {
                $main_pdo->rollBack();
            }
            wp_redirect(admin_url('admin.php?page=wavelog-registration&error=' . urlencode('Hata: ' . $ex->getMessage())));
            exit;
        }
    }
    
    private function reject_registration($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wavelog_registration_requests';
        
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        if ($request) {
            // Kullanıcıya red e-postası gönder
            $user_data = array(
                'callsign' => $request->user_callsign,
                'firstname' => $request->user_firstname,
                'lastname' => $request->user_lastname
            );
            $this->send_rejection_email($request->user_email, $user_data);
        }
        
        $wpdb->delete($table_name, ['id' => $id]);
        
        wp_redirect(admin_url('admin.php?page=wavelog-registration&message=Kayıt isteği reddedildi ve silindi.'));
        exit;
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('wavelog-registration-style', WAVELOG_REGISTRATION_PLUGIN_URL . 'assets/style.css', array(), WAVELOG_REGISTRATION_VERSION);
    }
    
    public function enqueue_admin_scripts() {
        wp_enqueue_style('wavelog-admin-style', WAVELOG_REGISTRATION_PLUGIN_URL . 'assets/admin-style.css', array(), WAVELOG_REGISTRATION_VERSION);
        wp_enqueue_script('wavelog-admin-js', WAVELOG_REGISTRATION_PLUGIN_URL . 'assets/admin.js', array('jquery'), WAVELOG_REGISTRATION_VERSION, true);
        
        wp_localize_script('wavelog-admin-js', 'wavelog_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('test_db_connection'),
            'smtp_nonce' => wp_create_nonce('test_smtp_connection')
        ));
    }
    
    public function registration_form_shortcode($atts) {
        ob_start();
        include WAVELOG_REGISTRATION_PLUGIN_PATH . 'templates/registration-form.php';
        return ob_get_clean();
    }
    
    public function admin_login_shortcode($atts) {
        ob_start();
        include WAVELOG_REGISTRATION_PLUGIN_PATH . 'templates/admin-login.php';
        return ob_get_clean();
    }
    
    public static function generate_slug($username) {
        return substr(md5($username . microtime(true)), 0, 10);
    }
    
    public static function check_duplicate_in_main_db($username, $email) {
        try {
            $settings = get_option('wavelog_registration_settings', array());
            
            if (empty($settings['db_host']) || empty($settings['db_name']) || empty($settings['db_user'])) {
                return true;
            }

            $main_dsn = "mysql:host=" . $settings['db_host'] . ";dbname=" . $settings['db_name'] . ";charset=" . $settings['db_charset'];
            $main_pdo = new PDO($main_dsn, $settings['db_user'], $settings['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $stmt = $main_pdo->prepare("SELECT COUNT(*) FROM users WHERE user_name = ? OR user_email = ?");
            $stmt->execute([$username, $email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $ex) {
            return true;
        }
    }
}

new WavelogRegistration();