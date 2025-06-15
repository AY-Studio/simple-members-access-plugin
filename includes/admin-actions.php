<?php
// Add "Approve" link to user row actions
add_filter('user_row_actions', function ($actions, $user) {
    if (in_array('pending_member', $user->roles)) {
        $url = wp_nonce_url(
            add_query_arg([
                'action' => 'approve_member',
                'user_id' => $user->ID,
            ], admin_url('users.php')),
            'approve_member_' . $user->ID
        );

        $actions['approve_member'] = '<a href="' . esc_url($url) . '" onclick="return confirm(\'Approve this member?\')">Approve</a>';
    }

    return $actions;
}, 10, 2);

// Handle approve action
add_action('admin_init', function () {
    if (
        is_admin() &&
        current_user_can('edit_users') &&
        isset($_GET['action'], $_GET['user_id']) &&
        $_GET['action'] === 'approve_member' &&
        wp_verify_nonce($_GET['_wpnonce'], 'approve_member_' . intval($_GET['user_id']))
    ) {
        $user_id = intval($_GET['user_id']);
        $user = get_userdata($user_id);

        if ($user && in_array('pending_member', $user->roles)) {
            $user->set_role('approved_member');

            // Optional email notification
            $site_name = get_bloginfo('name');
            $subject = "{$site_name} – Your Membership Has Been Approved";
            $message = "Hello {$user->user_login},\n\nYour membership has been approved! You can now log in and access members-only content.";

            if (get_option('members_access_email_include_login_link')) {
                $login_page = get_permalink(get_option('members_access_email_login_page'));
                $message .= "\n\nLogin here: {$login_page}";
            }

            wp_mail($user->user_email, $subject, $message);
        }

        wp_redirect(admin_url('users.php?role=pending_member&approved=1'));
        exit;
    }
});

// Show notice after approval
add_action('admin_notices', function () {
    if (isset($_GET['approved']) && $_GET['approved'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Member approved successfully.</p></div>';
    }
});
