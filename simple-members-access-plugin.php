<?php
ob_start(); // Start output buffering to prevent header errors
/**
 * Plugin Name: Simple Members Access Plugin
 * Plugin URI: https://ay.studio/
 * Description: Custom membership plugin with admin approval, frontend login/register, and member-only pages.
 * Version: 1.0
 * Author: AY Studio
 * Author URI: https://ay.studio
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: members-access
 * Domain Path: /languages
 */


defined('ABSPATH') or die('No script kiddies please!');

include_once plugin_dir_path(__FILE__) . 'includes/settings.php';       // Adds settings page first
include_once plugin_dir_path(__FILE__) . 'includes/roles.php';          // Adds roles early for registration
include_once plugin_dir_path(__FILE__) . 'includes/form-handlers.php';  // Sets up login/register shortcodes
include_once plugin_dir_path(__FILE__) . 'includes/access-control.php'; // Access rules (needs settings + roles)
include_once plugin_dir_path(__FILE__) . 'includes/admin-actions.php';

// Enqueue styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('members-access-style', plugin_dir_url(__FILE__) . 'assets/styles.css');
});

// Shortcodes
add_shortcode('members_register', 'members_register_form');
add_shortcode('members_login', 'members_login_form');

// Dashboard widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('pending_members_widget', 'Pending Members', 'pending_members_dashboard_widget');
});

add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('pending_members_widget', 'Pending Members', 'pending_members_dashboard_widget');
});

function pending_members_dashboard_widget() {
    $users = get_users(['role' => 'pending_member']);
    $custom_fields_json = get_option('members_access_custom_fields');
    $custom_fields = json_decode($custom_fields_json, true);

    if (empty($users)) {
        echo '<p>No pending members.</p>';
        return;
    }

    echo '<div style="overflow-x:auto;"><table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th>Username</th>';
    echo '<th>Email</th>';

    if (is_array($custom_fields)) {
        foreach ($custom_fields as $field) {
            echo '<th>' . esc_html($field['label']) . '</th>';
        }
    }

    echo '</tr></thead><tbody>';

    foreach ($users as $user) {
        $edit_link = esc_url(admin_url('user-edit.php?user_id=' . $user->ID));
        echo '<tr>';
        echo '<td><a href="' . $edit_link . '">' . esc_html($user->user_login) . '</a></td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';

        if (is_array($custom_fields)) {
            foreach ($custom_fields as $field) {
                $value = get_user_meta($user->ID, $field['name'], true);
                echo '<td>' . esc_html($value) . '</td>';
            }
        }

        echo '</tr>';
    }

    echo '</tbody></table></div>';

    echo '<p style="margin-top:10px;"><a href="' . esc_url(admin_url('users.php?role=pending_member')) . '">View all pending members</a></p>';
}

