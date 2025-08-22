<?php

function get_admin_email_recipients() {
    $custom_recipients = get_option('members_access_admin_email_recipients', '');
    
    if (empty($custom_recipients)) {
        return [get_option('admin_email')];
    }
    
    $emails = array_filter(array_map('trim', explode("\n", $custom_recipients)));
    
    $valid_emails = [];
    foreach ($emails as $email) {
        if (is_email($email)) {
            $valid_emails[] = $email;
        }
    }
    
    return empty($valid_emails) ? [get_option('admin_email')] : $valid_emails;
}

// Always ensure roles exist
add_action('init', function() {
    if (!get_role('pending_member')) {
        add_role('pending_member', 'Pending Member', []);
    }
    if (!get_role('approved_member')) {
        add_role('approved_member', 'Approved Member', ['read' => true]);
    }
});

// When a user registers, assign them the 'pending_member' role and notify admin
add_action('user_register', function($user_id) {
    $user = new WP_User($user_id);
    $user->set_role('pending_member');

    $admin_emails = get_admin_email_recipients();
    $subject = 'New Member Registration';

    $message  = "A new member has registered and awaits approval.\n\n";
    $message .= "Username: {$user->user_login}\n";
    $message .= "Email: {$user->user_email}\n";

// Get custom fields
    $custom_fields_json = get_option('members_access_custom_fields');
    $custom_fields = json_decode($custom_fields_json, true);

    if (is_array($custom_fields)) {
        foreach ($custom_fields as $field) {
            $key = $field['name'] ?? '';
            $label = $field['label'] ?? ucfirst($key);
            $submitted_value = sanitize_text_field($_POST[$key] ?? '');
            if (!empty($submitted_value)) {
                $message .= "{$label}: {$submitted_value}\n";
            }
        }
    }

    foreach ($admin_emails as $admin_email) {
        wp_mail($admin_email, $subject, $message);
    }

});

// When user role changes from pending_member to approved_member, notify them
add_action('profile_update', function($user_id, $old_user_data) {
    $user = get_userdata($user_id);
    if (
        in_array('approved_member', $user->roles) &&
        in_array('pending_member', $old_user_data->roles)
    ) {
        $site_name = get_bloginfo('name');
        $subject = "{$site_name} – Your Membership Has Been Approved";

        $message = "Hello {$user->user_login},\n\n";
        $message .= "Your membership has been approved! You can now log in and access members-only content.";

        $include_link = get_option('members_access_email_include_login_link');
        $login_page_id = get_option('members_access_login_page');

        if ($include_link && $login_page_id) {
            $login_url = get_permalink($login_page_id);
            $message .= "\n\nLogin here: {$login_url}";
        }

        wp_mail($user->user_email, $subject, $message);
    }
}, 10, 2);

