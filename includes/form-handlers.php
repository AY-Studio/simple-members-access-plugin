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

        $email = sanitize_email($_POST['email']);
        $username = $email; // Use email as username
        $password = sanitize_text_field($_POST['password']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $about_you = sanitize_text_field($_POST['about_you']);
        $about_you_other = sanitize_text_field($_POST['about_you_other'] ?? '');
        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {
            // Update user's first and last name
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => trim($first_name . ' ' . $last_name)
            ]);

            // Save About You field - use "other" specification if provided
            $about_you_value = ($about_you === 'Other' && !empty($about_you_other)) ? $about_you_other : $about_you;
            update_user_meta($user_id, 'about_you', $about_you_value);
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

            echo '<div class="members-ui-success">Registration successful. Awaiting approval.</div>';
        } else {
            echo '<div class="members-ui-error">Error: ' . $user_id->get_error_message() . '</div>';
        }
    }

    ob_start(); ?>
    <form method="post" class="members-ui-form">
        <div class="members-ui-field-row members-ui-field-row-first">
            <div class="members-ui-field-half">
                <label for="members-first-name" class="members-ui-label">First Name *</label>
                <input type="text" id="members-first-name" name="first_name" class="members-ui-input" required>
            </div>
            <div class="members-ui-field-half">
                <label for="members-last-name" class="members-ui-label">Last Name *</label>
                <input type="text" id="members-last-name" name="last_name" class="members-ui-input" required>
            </div>
        </div>

        <div class="members-ui-field">
            <label for="members-email" class="members-ui-label">Email *</label>
            <input type="email" id="members-email" name="email" class="members-ui-input" required>
        </div>

        <div class="members-ui-field">
            <label for="members-password" class="members-ui-label">Password *</label>
            <input type="password" id="members-password" name="password" class="members-ui-input" required>
        </div>

        <?php
        // Render dynamic custom fields from settings - Club field first
        $custom_fields_json = get_option('members_access_custom_fields');
        $custom_fields = json_decode($custom_fields_json, true);

        if (is_array($custom_fields)) {
            // First render Club field if it exists
            foreach ($custom_fields as $field) {
                if (strtolower($field['name'] ?? '') === 'club') {
                    $label = esc_html($field['label'] ?? '');
                    $name = esc_attr($field['name'] ?? '');
                    $type = esc_attr($field['type'] ?? 'text');

                    echo "<div class=\"members-ui-field\">
                            <label for=\"members-{$name}\" class=\"members-ui-label\">{$label} *</label>
                            <input type=\"{$type}\" id=\"members-{$name}\" name=\"{$name}\" class=\"members-ui-input\" required>
                          </div>";
                    break;
                }
            }
        }
        ?>

        <!-- New About You dropdown field -->
        <div class="members-ui-field">
            <label for="members-about-you" class="members-ui-label">About You *</label>
            <select id="members-about-you" name="about_you" class="members-ui-input" required onchange="toggleOtherField(this)">
                <option value="">Select one...</option>
                <option value="Coach">Coach</option>
                <option value="Member">Member</option>
                <option value="Parent of member">Parent of member</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Other specification field (hidden by default) -->
        <div class="members-ui-field" id="other-field" style="display: none;">
            <label for="members-about-you-other" class="members-ui-label">Please specify *</label>
            <input type="text" id="members-about-you-other" name="about_you_other" class="members-ui-input">
        </div>

        <script>
        function toggleOtherField(select) {
            const otherField = document.getElementById('other-field');
            const otherInput = document.getElementById('members-about-you-other');
            
            if (select.value === 'Other') {
                otherField.style.display = 'block';
                otherInput.required = true;
            } else {
                otherField.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        }
        </script>

        <?php
        // Then render remaining custom fields (excluding Club which was already rendered)
        if (is_array($custom_fields)) {
            foreach ($custom_fields as $field) {
                if (strtolower($field['name'] ?? '') !== 'club') {
                    $label = esc_html($field['label'] ?? '');
                    $name = esc_attr($field['name'] ?? '');
                    $type = esc_attr($field['type'] ?? 'text');

                    echo "<div class=\"members-ui-field\">
                            <label for=\"members-{$name}\" class=\"members-ui-label\">{$label} *</label>
                            <input type=\"{$type}\" id=\"members-{$name}\" name=\"{$name}\" class=\"members-ui-input\" required>
                          </div>";
                }
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
            $error = '<div class="members-ui-error">Login failed: ' . $user->get_error_message() . '</div>';
        }
    }

    if (isset($error)) {
        echo $error;
    }
    
    ob_start(); ?>
    <form method="post" class="members-ui-form">
        <div class="members-ui-field">
            <label for="members-username" class="members-ui-label">Email Address</label>
            <input type="email" id="members-username" name="username" class="members-ui-input" required>
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
    echo '<h2>Custom Member Fields</h2><table class="form-table">';
    
    // Show About You field first
    $about_you_value = esc_attr(get_user_meta($user->ID, 'about_you', true));
    $is_other = !in_array($about_you_value, ['Coach', 'Member', 'Parent of member']) && !empty($about_you_value);
    $selected_value = $is_other ? 'Other' : $about_you_value;
    
    echo "<tr>
            <th><label for='about_you'>About You</label></th>
            <td>
                <select name='about_you' id='about_you' class='regular-text' onchange='toggleOtherFieldProfile(this)'>
                    <option value=''>Select one...</option>
                    <option value='Coach'" . selected($selected_value, 'Coach', false) . ">Coach</option>
                    <option value='Member'" . selected($selected_value, 'Member', false) . ">Member</option>
                    <option value='Parent of member'" . selected($selected_value, 'Parent of member', false) . ">Parent of member</option>
                    <option value='Other'" . selected($selected_value, 'Other', false) . ">Other</option>
                </select>
            </td>
          </tr>";
    
    // Show other field if needed
    $other_display = $is_other ? 'table-row' : 'none';
    $other_value = $is_other ? $about_you_value : '';
    echo "<tr id='other-field-profile' style='display: {$other_display};'>
            <th><label for='about_you_other'>Please specify</label></th>
            <td><input type='text' name='about_you_other' id='about_you_other' value='" . esc_attr($other_value) . "' class='regular-text'></td>
          </tr>";
    
    echo "<script>
    function toggleOtherFieldProfile(select) {
        const otherField = document.getElementById('other-field-profile');
        const otherInput = document.getElementById('about_you_other');
        
        if (select.value === 'Other') {
            otherField.style.display = 'table-row';
        } else {
            otherField.style.display = 'none';
            otherInput.value = '';
        }
    }
    </script>";

    $custom_fields_json = get_option('members_access_custom_fields');
    $custom_fields = json_decode($custom_fields_json, true);

    if (is_array($custom_fields)) {
        foreach ($custom_fields as $field) {
            $name = esc_attr($field['name']);
            $label = esc_html($field['label']);
            $value = esc_attr(get_user_meta($user->ID, $name, true));

            echo "<tr>
                    <th><label for='{$name}'>{$label}</label></th>
                    <td><input type='text' name='{$name}' id='{$name}' value='{$value}' class='regular-text'></td>
                  </tr>";
        }
    }
    echo '</table>';
}

add_action('personal_options_update', 'members_save_custom_user_fields');
add_action('edit_user_profile_update', 'members_save_custom_user_fields');

function members_save_custom_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    // Save About You field - use "other" specification if provided
    if (isset($_POST['about_you'])) {
        $about_you = sanitize_text_field($_POST['about_you']);
        $about_you_other = sanitize_text_field($_POST['about_you_other'] ?? '');
        
        $about_you_value = ($about_you === 'Other' && !empty($about_you_other)) ? $about_you_other : $about_you;
        update_user_meta($user_id, 'about_you', $about_you_value);
    }

    $custom_fields_json = get_option('members_access_custom_fields');
    $custom_fields = json_decode($custom_fields_json, true);

    if (is_array($custom_fields)) {
        foreach ($custom_fields as $field) {
            $meta_key = esc_attr($field['name']);
            if (isset($_POST[$meta_key])) {
                update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
            }
        }
    }
}
