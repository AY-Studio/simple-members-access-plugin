<?php
// Block access to wp-admin for approved_member users
add_action('init', function() {
    if (is_admin() && !defined('DOING_AJAX')) {
        $user = wp_get_current_user();
        if (in_array('approved_member', $user->roles) && !current_user_can('edit_posts')) {
            wp_redirect(home_url());
            exit;
        }
    }
});

// Hide admin bar for approved_member users
add_filter('show_admin_bar', function($show) {
    $user = wp_get_current_user();
    if (in_array('approved_member', $user->roles) && !current_user_can('edit_posts')) {
        return false;
    }
    return $show;
});
