<?php
function members_register_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['members_register_nonce']) && wp_verify_nonce($_POST['members_register_nonce'], 'members_register')) {
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $user_id = wp_create_user($username, $password, $email);
        if (!is_wp_error($user_id)) {
            echo '<p>Registration successful. Awaiting approval.</p>';
        } else {
            echo '<p>Error: ' . $user_id->get_error_message() . '</p>';
        }
    }
    ob_start(); ?>
    <form method="post" class="members-ui-form">
        <div class="members-ui-field">
            <label for="members-username" class="members-ui-label">Username</label>
            <input type="text" id="members-username" name="username" class="members-ui-input" required>
        </div>

        <div class="members-ui-field">
            <label for="members-email" class="members-ui-label">Email</label>
            <input type="email" id="members-email" name="email" class="members-ui-input" required>
        </div>

        <div class="members-ui-field">
            <label for="members-password" class="members-ui-label">Password</label>
            <input type="password" id="members-password" name="password" class="members-ui-input" required>
        </div>

        <?php wp_nonce_field('members_register', 'members_register_nonce'); ?>

        <?php
        $login_page_id = get_option('members_access_login_page');
        $login_url = $login_page_id ? get_permalink($login_page_id) : '#';
        ?>

        <div class="members-ui-actions-row">
            <a href="<?php echo esc_url($login_url); ?>" class="members-ui-link">Already a member? Login</a>
            <button type="submit" class="members-ui-button">Register</button>
        </div>
    </form>


    <?php return ob_get_clean();
}

function members_login_form() {
    // Redirect if already logged in
    if (is_user_logged_in() && get_option('members_access_redirect_logged_in')) {
        $redirect_id = get_option('members_access_logged_in_redirect_page');
        $redirect_url = $redirect_id ? get_permalink($redirect_id) : home_url('/members-dashboard');
        wp_redirect($redirect_url);
        exit;
    }

    $redirect_after_login = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['members_login_nonce']) && wp_verify_nonce($_POST['members_login_nonce'], 'members_login')) {
        $creds = [
            'user_login' => sanitize_user($_POST['username']),
            'user_password' => $_POST['password'],
            'remember' => true
        ];
        $user = wp_signon($creds, false);

        if (!is_wp_error($user)) {
            $redirect_after_login = true;
        } else {
            $error = '<p>Login failed: ' . $user->get_error_message() . '</p>';
        }
    }

    ob_start();

    if (isset($error)) {
        echo $error;
    }
    ?>
    <form method="post" class="members-ui-form">
        <div class="members-ui-field">
            <label for="members-username" class="members-ui-label">Username</label>
            <input type="text" id="members-username" name="username" class="members-ui-input" required>
        </div>

        <div class="members-ui-field">
            <label for="members-password" class="members-ui-label">Password</label>
            <input type="password" id="members-password" name="password" class="members-ui-input" required>
        </div>

        <?php wp_nonce_field('members_login', 'members_login_nonce'); ?>

        <?php
        $register_page_id = get_option('members_access_register_page');
        $register_url = $register_page_id ? get_permalink($register_page_id) : '#';
        ?>
        <div class="members-ui-actions-row">
            <a href="<?php echo esc_url($register_url); ?>" class="members-ui-link">Not a member? Register now.</a>
            <button type="submit" class="members-ui-button">Login</button>
        </div>

    </form>




    <?php
    $html = ob_get_clean();

    // Now that nothing has been output yet, it's safe to redirect
    if ($redirect_after_login) {
        $redirect_id = get_option('members_access_logged_in_redirect_page');
        $redirect_url = $redirect_id ? get_permalink($redirect_id) : home_url('/members-dashboard');
        wp_redirect($redirect_url);
        exit;
    }

    return $html;
}
function members_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view this page.</p>';
    }

    $site_name = get_bloginfo('name');
    return "<h2>Welcome to {$site_name} Members Area</h2>";
}
add_shortcode('members_dashboard', 'members_dashboard_shortcode');
