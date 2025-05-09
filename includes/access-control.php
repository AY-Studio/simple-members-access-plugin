<?php
add_action('add_meta_boxes', function() {
    add_meta_box('members_only_box', 'Members Only', 'members_only_meta_box', 'page', 'side');
});

function members_only_meta_box($post) {
    $value = get_post_meta($post->ID, '_members_only', true);
    echo '<label><input type="checkbox" name="members_only" ' . checked($value, 'yes', false) . '> Restrict to approved members</label>';
}

add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    update_post_meta($post_id, '_members_only', isset($_POST['members_only']) ? 'yes' : 'no');
});

add_action('template_redirect', function() {
    if (is_page()) {
        global $post;
        $restricted = get_post_meta($post->ID, '_members_only', true);
        if ($restricted === 'yes') {
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;

            if (!is_user_logged_in() || in_array('pending_member', $user_roles)) {
                $redirect_page_id = get_option('members_access_redirect_page');
                $redirect_url = $redirect_page_id ? get_permalink($redirect_page_id) : home_url();
                wp_redirect($redirect_url);
                exit;
            }
        }
    }
});


