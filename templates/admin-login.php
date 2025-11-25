<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wavelog_admin_login'])) {
    $username = sanitize_user($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // WordPress kullanıcı doğrulaması
    $user = wp_authenticate($username, $password);
    
    if (!is_wp_error($user) && user_can($user, 'manage_options')) {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Yönetici paneline yönlendir
        wp_redirect(admin_url('admin.php?page=wavelog-registration'));
        exit;
    } else {
        $error = "Geçersiz kullanıcı adı veya şifre";
    }
}
?>

<div class="wavelog-admin-login">
    <div class="login-form">
        <h2>Yönetici Girişi</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="wavelog_admin_login" value="1">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
    </div>
</div>