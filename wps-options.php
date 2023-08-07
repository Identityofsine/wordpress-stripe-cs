<?php

// Create the options/settings page
function create_options_page() {
    add_options_page(
        'Stripe PaymentIntent Settings', // Page title
        'Stripe PaymentIntent', // Menu title
        'manage_options', // Capability required to access
        'wps_client_secret-settings', // Menu slug
        'wps_client_secret_render_settings' // Callback function to render the settings page
    );
}
add_action('admin_menu', 'create_options_page');

function wps_client_secret_render_settings() {
		?>
		<h1>WPS-PaymentIntent Settings</h1>
		<form method="post" action="options.php">
            <?php settings_fields('wps_client_secret-settings-group'); ?>
            <?php do_settings_sections('wps_client_secret'); ?>
            <?php submit_button(); ?>
     </form>
		<?php
}

// Register the settings
function register_wps_client_secret_settings() {
		register_setting(
				'wps_client_secret-settings-group', // Option group
				'wps_client_secret', // Option name
				'sanitize_wps_client_secret' // Sanitize
		);
		add_settings_section(
			'wps_client_secret_section', // ID
			'Settings', // Title
			'wps_client_secret_section_callback', // Callback
			'wps_client_secret' // Page
		);
		add_settings_field(
			'wps_client_secret', // ID
			'Stripe Secret', // Title
			'wps_client_secret_callback', // Callback
			'wps_client_secret', // Page
			'wps_client_secret_section' // Section
		);
}

add_action('admin_init', 'register_wps_client_secret_settings');

function wps_client_secret_section_callback() {
		echo '<p>Enter your settings here.</p>';
}

function wps_client_secret_callback() {
		$wps_client_secret = get_option('wps_client_secret');
		echo "<input type='text' name='wps_client_secret' value='$wps_client_secret' />";
}