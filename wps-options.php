<?php

// Create the options/settings page
function my_plugin_options_page() {
    add_options_page(
        'My Plugin Settings', // Page title
        'My Plugin', // Menu title
        'manage_options', // Capability required to access
        'my-plugin-settings', // Menu slug
        'my_plugin_render_settings' // Callback function to render the settings page
    );
}
add_action('admin_menu', 'my_plugin_options_page');