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
    <div class="wrap">
        <h1>Members Access Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('members_access_settings');
            do_settings_sections('members-access-settings');
            submit_button();
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

    // Section: General
    add_settings_section('members_access_general', 'General', null, 'members-access-settings');

    add_settings_field(
        'members_access_register_page',
        'Members Registration Page',
        'members_access_register_page_dropdown',
        'members-access-settings',
        'members_access_general'
    );

    add_settings_field(
        'members_access_login_page',
        'Members Login Page',
        'members_access_login_page_dropdown',
        'members-access-settings',
        'members_access_general'
    );

    // Section: Redirects
    add_settings_section('members_access_redirects', 'Redirects', null, 'members-access-settings');

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
    add_settings_section('members_access_email', 'Email', null, 'members-access-settings');

    add_settings_field(
        'members_access_email_include_login_link',
        'Include Login Link in Approval Email',
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
});

// === Dropdown renderers ===

function members_access_register_page_dropdown() {
    $selected = get_option('members_access_register_page');
    $pages = get_pages();
    echo '<select name="members_access_register_page">';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
}

function members_access_login_page_dropdown() {
    $selected = get_option('members_access_login_page');
    $pages = get_pages();
    echo '<select name="members_access_login_page">';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
}

function members_access_logged_in_redirect_page_dropdown() {
    $selected = get_option('members_access_logged_in_redirect_page');
    $pages = get_pages();
    echo '<select name="members_access_logged_in_redirect_page">';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
}

function members_access_email_login_page_dropdown() {
    $enabled = get_option('members_access_email_include_login_link');
    $selected = get_option('members_access_email_login_page');
    $pages = get_pages();

    if (!$enabled) {
        echo '<p style="color:#777;">Enable the checkbox above to select a login page for the email.</p>';
        return;
    }

    echo '<select name="members_access_email_login_page">';
    foreach ($pages as $page) {
        $selected_attr = selected($selected, $page->ID, false);
        echo "<option value='{$page->ID}' $selected_attr>{$page->post_title}</option>";
    }
    echo '</select>';
}

// === Checkbox renderers ===

function members_access_redirect_logged_in_checkbox() {
    $enabled = get_option('members_access_redirect_logged_in');
    echo '<label><input type="checkbox" name="members_access_redirect_logged_in" value="1" ' . checked($enabled, 1, false) . '> Enable automatic redirect for logged-in users</label>';
}

function members_access_email_include_login_link_checkbox() {
    $enabled = get_option('members_access_email_include_login_link');
    echo '<label><input type="checkbox" name="members_access_email_include_login_link" value="1" ' . checked($enabled, 1, false) . '> Yes, include a login link in the approval email</label>';
}
