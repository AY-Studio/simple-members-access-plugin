<?php
add_action('admin_menu', function() {
    add_options_page(
        'Members Access Settings',
        'Members Access',
        'manage_options',
        'members-access-settings',
        'render_members_access_settings'
    );
});

function render_members_access_settings() {
    ?>
    <style>
        .members-access-settings {
            max-width: 900px;
        }
        .members-access-settings h1 {
            color: #1d2327;
            margin-bottom: 20px;
        }
        .members-access-settings .form-table {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .members-access-settings .form-table th {
            background: #f6f7f7;
            border-bottom: 1px solid #c3c4c7;
            font-weight: 600;
            color: #1d2327;
            padding: 20px;
        }
        .members-access-settings .form-table td {
            padding: 20px;
            border-bottom: 1px solid #f0f0f1;
        }
        .members-access-settings .form-table tr:last-child td {
            border-bottom: none;
        }
        .members-access-settings h2 {
            color: #1d2327;
            font-size: 18px;
            margin: 30px 0 10px 0;
            padding: 12px 0;
            border-bottom: 2px solid #0073aa;
            display: flex;
            align-items: center;
        }
        .members-access-settings h2:before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: #0073aa;
            margin-right: 12px;
        }
        .members-access-settings .description {
            color: #646970;
            font-style: normal;
            margin-top: 8px;
            line-height: 1.5;
        }
        .members-access-settings .form-required {
            color: #d63384;
        }
        .members-access-settings .email-recipients-field {
            position: relative;
        }
        .members-access-settings .email-preview {
            background: #f0f6fc;
            border: 1px solid #0073aa;
            border-radius: 4px;
            padding: 12px;
            margin-top: 8px;
            font-family: monospace;
            font-size: 13px;
        }
        .members-access-settings .success-indicator {
            color: #00a32a;
            font-weight: 500;
        }
        .members-access-settings .warning-indicator {
            color: #dba617;
            font-weight: 500;
        }
        .members-access-settings .checkbox-field {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 10px;
        }
        .members-access-settings .checkbox-field input[type="checkbox"] {
            margin-top: 2px;
        }
        .members-access-settings pre {
            font-size: 12px;
            line-height: 1.4;
        }
        .members-access-settings input[type="password"]:focus {
            border-color: #0073aa;
        }
        .members-access-settings .section-intro {
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dynamic conditional field showing/hiding
            const redirectCheckbox = document.querySelector('input[name="members_access_redirect_logged_in"]');
            const emailLinkCheckbox = document.querySelector('input[name="members_access_email_include_login_link"]');
            
            function toggleConditionalFields() {
                // Trigger a form refresh to show/hide dependent fields
                if (redirectCheckbox || emailLinkCheckbox) {
                    setTimeout(() => {
                        const form = document.querySelector('.members-access-settings form');
                        if (form) {
                            const event = new Event('change', { bubbles: true });
                            form.dispatchEvent(event);
                        }
                    }, 100);
                }
            }
            
            if (redirectCheckbox) {
                redirectCheckbox.addEventListener('change', toggleConditionalFields);
            }
            if (emailLinkCheckbox) {
                emailLinkCheckbox.addEventListener('change', toggleConditionalFields);
            }
            
            // Add validation feedback for email fields
            const emailTextarea = document.querySelector('textarea[name="members_access_admin_email_recipients"]');
            if (emailTextarea) {
                emailTextarea.addEventListener('blur', function() {
                    const emails = this.value.split('\n').map(e => e.trim()).filter(e => e);
                    let validCount = 0;
                    
                    emails.forEach(email => {
                        if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            validCount++;
                        }
                    });
                    
                    const preview = document.querySelector('.email-preview');
                    if (preview && validCount > 0) {
                        preview.style.display = 'block';
                    }
                });
            }
        });
    </script>
    <div class="wrap members-access-settings">
        <h1>🔐 Members Access Settings</h1>
        <p class="description" style="font-size: 14px; margin-bottom: 20px;">Configure your membership system settings including registration, login, email notifications, and security features.</p>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('members_access_settings');
            do_settings_sections('members-access-settings');
            submit_button('Save All Settings', 'primary', 'submit', true, ['style' => 'margin-top: 20px; padding: 12px 24px; font-size: 14px;']);
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    // Register all settings
    register_setting('members_access_settings', 'members_access_register_page');
    register_setting('members_access_settings', 'members_access_login_page');
    register_setting('members_access_settings', 'members_access_redirect_logged_in');
    register_setting('members_access_settings', 'members_access_logged_in_redirect_page');
    register_setting('members_access_settings', 'members_access_email_include_login_link');
    register_setting('members_access_settings', 'members_access_email_login_page');
    register_setting('members_access_settings', 'members_access_admin_email_recipients', 'validate_admin_email_recipients');
    register_setting('members_access_settings', 'members_access_custom_fields');

    // reCAPTCHA v3 keys
    register_setting('members_access_settings', 'members_access_recaptcha_site_key_v3');
    register_setting('members_access_settings', 'members_access_recaptcha_secret_key_v3');

    // Section: General
    add_settings_section('members_access_general', '📄 Page Configuration', 'render_general_section_description', 'members-access-settings');

    add_settings_field(
        'members_access_register_page',
        'Registration Page <span class="form-required">*</span>',
        'members_access_register_page_dropdown',
        'members-access-settings',
        'members_access_general'
    );

    add_settings_field(
        'members_access_login_page',
        'Login Page <span class="form-required">*</span>',
        'members_access_login_page_dropdown',
        'members-access-settings',
        'members_access_general'
    );

    // Section: Security
    add_settings_section('members_access_security', '🔒 Security Settings', 'render_security_section_description', 'members-access-settings');

    add_settings_field(
        'members_access_recaptcha_site_key_v3',
        'reCAPTCHA v3 Site Key',
        'members_access_recaptcha_site_key_field',
        'members-access-settings',
        'members_access_security'
    );

    add_settings_field(
        'members_access_recaptcha_secret_key_v3',
        'reCAPTCHA v3 Secret Key',
        'members_access_recaptcha_secret_key_field',
        'members-access-settings',
        'members_access_security'
    );

    // Section: Redirects
    add_settings_section('members_access_redirects', '🔄 User Redirects', 'render_redirects_section_description', 'members-access-settings');

    add_settings_field(
        'members_access_redirect_logged_in',
        'Redirect Logged-In Users',
        'members_access_redirect_logged_in_checkbox',
        'members-access-settings',
        'members_access_redirects'
    );

    add_settings_field(
        'members_access_logged_in_redirect_page',
        'Redirect Destination for Logged-In Users',
        'members_access_logged_in_redirect_page_dropdown',
        'members-access-settings',
        'members_access_redirects'
    );

    // Section: Email
    add_settings_section('members_access_email', '📧 Email Notifications', 'render_email_section_description', 'members-access-settings');

    add_settings_field(
        'members_access_admin_email_recipients',
        'Admin Notification Recipients',
        'members_access_admin_email_recipients_textarea',
        'members-access-settings',
        'members_access_email'
    );

    add_settings_field(
        'members_access_email_include_login_link',
        'Member Approval Emails',
        'members_access_email_include_login_link_checkbox',
        'members-access-settings',
        'members_access_email'
    );

    add_settings_field(
        'members_access_email_login_page',
        'Login Page for Email Link',
        'members_access_email_login_page_dropdown',
        'members-access-settings',
        'members_access_email'
    );

    // Section: Extra Registration Fields
    add_settings_section('members_access_fields', '📝 Custom Registration Fields', 'render_fields_section_description', 'members-access-settings');

    add_settings_field(
        'members_access_custom_fields',
        'Custom Registration Fields (JSON)',
        'members_access_custom_fields_textarea',
        'members-access-settings',
        'members_access_fields'
    );
});

// === Section Descriptions ===

function render_general_section_description() {
    echo '<p>Select which pages contain your registration and login forms. These pages should include the <code>[members_register]</code> and <code>[members_login]</code> shortcodes respectively.</p>';
}

function render_security_section_description() {
    echo '<p>Configure security features to protect your registration forms from spam and abuse.</p>';
}

function render_redirects_section_description() {
    echo '<p>Control where users are redirected after various actions to improve user flow.</p>';
}

function render_email_section_description() {
    echo '<p>Configure email notifications sent to administrators and members throughout the registration and approval process.</p>';
}

function render_fields_section_description() {
    echo '<p>Add custom fields to your registration form by defining them in JSON format.</p>';
}

// === Field Renderers ===

function members_access_register_page_dropdown() {
    $selected = get_option('members_access_register_page');
    $pages = get_pages();
    echo '<select name="members_access_register_page" class="regular-text">';
    echo '<option value="">-- Select Page --</option>';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
    echo '<p class="description">Choose the page where you\'ve added the <code>[members_register]</code> shortcode. This page will handle new member registrations.</p>';
}

function members_access_login_page_dropdown() {
    $selected = get_option('members_access_login_page');
    $pages = get_pages();
    echo '<select name="members_access_login_page" class="regular-text">';
    echo '<option value="">-- Select Page --</option>';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
    echo '<p class="description">Choose the page where you\'ve added the <code>[members_login]</code> shortcode. This page will handle member logins.</p>';
}

function members_access_logged_in_redirect_page_dropdown() {
    $enabled = get_option('members_access_redirect_logged_in');
    $selected = get_option('members_access_logged_in_redirect_page');
    $pages = get_pages();

    if (!$enabled) {
        echo '<p class="description warning-indicator">⚠️ Enable the redirect option above to select a destination page.</p>';
        return;
    }

    echo '<select name="members_access_logged_in_redirect_page" class="regular-text">';
    echo '<option value="">-- Select Page --</option>';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
    echo '<p class="description">Choose where to redirect users who are already logged in when they visit restricted pages.</p>';
}

function members_access_email_login_page_dropdown() {
    $enabled = get_option('members_access_email_include_login_link');
    $selected = get_option('members_access_email_login_page');
    $pages = get_pages();

    if (!$enabled) {
        echo '<p class="description warning-indicator">⚠️ Enable login links in approval emails above to select a login page.</p>';
        return;
    }

    echo '<select name="members_access_email_login_page" class="regular-text">';
    echo '<option value="">-- Select Page --</option>';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
    echo '<p class="description">Choose which page to link to in approval emails. This should be your login page.</p>';
}

function members_access_redirect_logged_in_checkbox() {
    $enabled = get_option('members_access_redirect_logged_in');
    echo '<label style="display: flex; align-items: center; gap: 8px;">';
    echo '<input type="checkbox" name="members_access_redirect_logged_in" value="1" ' . checked($enabled, 1, false) . '>';
    echo '<span>Enable automatic redirect for logged-in users</span>';
    echo '</label>';
    echo '<p class="description">When enabled, users who are already logged in will be automatically redirected when they visit login/registration pages.</p>';
}

function members_access_email_include_login_link_checkbox() {
    $enabled = get_option('members_access_email_include_login_link');
    echo '<label style="display: flex; align-items: center; gap: 8px;">';
    echo '<input type="checkbox" name="members_access_email_include_login_link" value="1" ' . checked($enabled, 1, false) . '>';
    echo '<span>Include login link in member approval emails</span>';
    echo '</label>';
    echo '<p class="description">When enabled, approved members will receive a direct link to the login page in their approval email for easier access.</p>';
}

function members_access_admin_email_recipients_textarea() {
    $value = get_option('members_access_admin_email_recipients', '');
    $default_email = get_option('admin_email');
    
    if (empty($value)) {
        $value = $default_email;
    }
    
    echo '<div class="email-recipients-field">';
    echo '<textarea name="members_access_admin_email_recipients" rows="4" cols="60" class="large-text" placeholder="' . esc_attr($default_email) . '">' . esc_textarea($value) . '</textarea>';
    
    $emails = array_filter(array_map('trim', explode("\n", $value)));
    $valid_count = 0;
    foreach ($emails as $email) {
        if (is_email($email)) {
            $valid_count++;
        }
    }
    
    if ($valid_count > 0) {
        echo '<div class="email-preview">';
        echo '<strong>📧 Current Recipients (' . $valid_count . '):</strong><br>';
        foreach ($emails as $email) {
            if (is_email($email)) {
                echo '• ' . esc_html($email) . '<br>';
            }
        }
        echo '</div>';
    }
    
    echo '<p class="description">';
    echo '<strong>Who gets notified:</strong> These email addresses will receive notifications when new members register and need approval.<br>';
    echo '<strong>Format:</strong> Enter one email address per line. If left empty, notifications go to the site admin email (<code>' . esc_html($default_email) . '</code>).<br>';
    echo '<strong>Example:</strong><br>';
    echo '<code>admin@example.com<br>manager@example.com<br>hr@example.com</code>';
    echo '</p>';
    echo '</div>';
}

function members_access_custom_fields_textarea() {
    $value = get_option('members_access_custom_fields', '');
    echo '<textarea name="members_access_custom_fields" rows="8" cols="60" class="large-text code" placeholder=\'[{"label":"Company Name","name":"company","type":"text","required":true}]\'>' . esc_textarea($value) . '</textarea>';
    echo '<div class="description" style="margin-top: 10px;">';
    echo '<p><strong>Add custom fields to your registration form using JSON format.</strong></p>';
    echo '<p><strong>Example:</strong></p>';
    echo '<pre style="background: #f0f0f1; padding: 10px; border-radius: 4px; overflow-x: auto;">[
  {
    "label": "Company Name",
    "name": "company",
    "type": "text",
    "required": true
  },
  {
    "label": "Phone Number",
    "name": "phone",
    "type": "tel",
    "required": false
  },
  {
    "label": "Department",
    "name": "department",
    "type": "select",
    "options": ["Sales", "Marketing", "Support"],
    "required": true
  }
]</pre>';
    echo '<p><strong>Supported field types:</strong> <code>text</code>, <code>email</code>, <code>tel</code>, <code>textarea</code>, <code>select</code>, <code>checkbox</code></p>';
    echo '</div>';
}

// === reCAPTCHA Fields ===

function members_access_recaptcha_site_key_field() {
    $value = get_option('members_access_recaptcha_site_key_v3', '');
    echo '<input type="text" name="members_access_recaptcha_site_key_v3" class="large-text" value="' . esc_attr($value) . '" placeholder="6Lc6BAAAAAAAAChqRbQZcn_yyyyyyyyyyyyyyyyy">';
    echo '<p class="description">Get your reCAPTCHA v3 keys from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin Console</a>. The site key is visible to users.</p>';
}

function members_access_recaptcha_secret_key_field() {
    $value = get_option('members_access_recaptcha_secret_key_v3', '');
    echo '<input type="password" name="members_access_recaptcha_secret_key_v3" class="large-text" value="' . esc_attr($value) . '" placeholder="6Lc6BAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA">';
    echo '<p class="description">The secret key is used for server-side verification and should be kept confidential. Both keys are required for reCAPTCHA to work.</p>';
}

// === Validation Functions ===

function validate_admin_email_recipients($input) {
    if (empty($input)) {
        return $input;
    }
    
    $emails = array_filter(array_map('trim', explode("\n", $input)));
    $valid_emails = [];
    $invalid_emails = [];
    
    foreach ($emails as $email) {
        if (is_email($email)) {
            $valid_emails[] = $email;
        } else {
            $invalid_emails[] = $email;
        }
    }
    
    if (!empty($invalid_emails)) {
        $invalid_list = implode(', ', $invalid_emails);
        add_settings_error(
            'members_access_admin_email_recipients',
            'invalid_emails',
            "The following email addresses are invalid and have been removed: {$invalid_list}",
            'error'
        );
    }
    
    if (!empty($valid_emails)) {
        return implode("\n", $valid_emails);
    }
    
    return '';
}
