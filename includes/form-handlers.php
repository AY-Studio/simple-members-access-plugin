<?php
function members_register_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['members_register_nonce']) && wp_verify_nonce($_POST['members_register_nonce'], 'members_register')) {
        $recaptcha_secret = get_option('members_access_recaptcha_secret_key_v3');
        $recaptcha_token = $_POST['recaptcha_token'] ?? '';
        $remote_ip = $_SERVER['REMOTE_ADDR'];

        if ($recaptcha_secret && $recaptcha_token) {
            $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $recaptcha_secret,
                    'response' => $recaptcha_token,
                    'remoteip' => $remote_ip
                ]
            ]);

            $body = wp_remote_retrieve_body($verify);
            $result = json_decode($body, true);

            if (empty($result['success']) || $result['score'] < 0.5) {
                echo '<p class="members-ui-error">reCAPTCHA failed. Please try again.</p>';
                return;
            }
        }

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {
            // Save custom field data as user meta
            $custom_fields_json = get_option('members_access_custom_fields');
            $custom_fields = json_decode($custom_fields_json, true);

            if (is_array($custom_fields)) {
                foreach ($custom_fields as $field) {
                    $meta_key = $field['name'];
                    if (isset($_POST[$meta_key])) {
                        update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
                    }
                }
            }

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

        <?php
        // Render dynamic custom fields from settings
        $custom_fields_json = get_option('members_access_custom_fields');
        $custom_fields = json_decode($custom_fields_json, true);

        if (is_array($custom_fields)) {
            foreach ($custom_fields as $field) {
                $label = esc_html($field['label'] ?? '');
                $name = esc_attr($field['name'] ?? '');
                $type = esc_attr($field['type'] ?? 'text');
                $required = !empty($field['required']) ? 'required' : '';

                echo "<div class=\"members-ui-field\">
                        <label for=\"members-{$name}\" class=\"members-ui-label\">{$label}</label>
                        <input type=\"{$type}\" id=\"members-{$name}\" name=\"{$name}\" class=\"members-ui-input\" {$required}>
                      </div>";
            }
        }

        // reCAPTCHA script and hidden token field
        $recaptcha_site_key = get_option('members_access_recaptcha_site_key_v3');
        if ($recaptcha_site_key) {
            echo '<input type="hidden" name="recaptcha_token" id="recaptcha_token">';
            echo '<script src="https://www.google.com/recaptcha/api.js?render=' . esc_attr($recaptcha_site_key) . '"></script>';
            echo '<script>
                grecaptcha.ready(function () {
                    grecaptcha.execute("' . esc_attr($recaptcha_site_key) . '", {action: "register"}).then(function (token) {
                        document.getElementById("recaptcha_token").value = token;
                    });
                });
            </script>';
        }

        wp_nonce_field('members_register', 'members_register_nonce');

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

add_action('show_user_profile', 'members_show_custom_user_fields');
add_action('edit_user_profile', 'members_show_custom_user_fields');

function members_show_custom_user_fields($user) {
    $custom_fields_json = get_option('members_access_custom_fields');
    $custom_fields = json_decode($custom_fields_json, true);

    if (!is_array($custom_fields)) return;

    echo '<h2>Custom Member Fields</h2><table class="form-table">';
    foreach ($custom_fields as $field) {
        $name = esc_attr($field['name']);
        $label = esc_html($field['label']);
        $value = esc_attr(get_user_meta($user->ID, $name, true));

        echo "<tr>
                <th><label for='{$name}'>{$label}</label></th>
                <td><input type='text' name='{$name}' id='{$name}' value='{$value}' class='regular-text'></td>
              </tr>";
    }
    echo '</table>';
}

add_action('personal_options_update', 'members_save_custom_user_fields');
add_action('edit_user_profile_update', 'members_save_custom_user_fields');

function members_save_custom_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    $custom_fields_json = get_option('members_access_custom_fields');
    $custom_fields = json_decode($custom_fields_json, true);

    if (!is_array($custom_fields)) return;

    foreach ($custom_fields as $field) {
        $meta_key = esc_attr($field['name']);
        if (isset($_POST[$meta_key])) {
            update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
        }
    }
}
