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
		'wps_client_secret-settings-group',
		'wps_release_mode',
		'sanitize_wps_release_mode',
	);
	add_settings_field(
		'wps_release_mode',
		'Release Mode',
		'wps_release_mode_callback',
		'wps_client_secret',
		'wps_client_secret_section'
	);
	
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
	//add option for release secret
	register_setting(
		'wps_client_secret-settings-group', // Option group
		'wps_release_secret', // Option name
		'sanitize_wps_release_secret' // Sanitize
	);
	add_settings_field(
		'wps_release_secret', // ID
		'Stripe Release Secret', // Title
		'wps_release_secret_callback', // Callback
		'wps_client_secret', // Page
		'wps_client_secret_section' // Section
	);
	
	
}

add_action('admin_init', 'register_wps_client_secret_settings');

function wps_client_secret_section_callback() {
	echo '<p>Enter your Stripe Secret Here, this usually looks like <i>sk_gsdf...</i><br></p>';
}


function wps_release_mode_callback() {
	$wps_release_mode = get_option('wps_release_mode');
	
	echo "<input type='checkbox' name='wps_release_mode' value='1' " . checked(1, $wps_release_mode, false) . " />";
}

function wps_client_secret_callback() {
	$wps_client_secret = get_option('wps_client_secret');
	
	echo "<input type='text' name='wps_client_secret' value='$wps_client_secret' />";
}

function wps_release_secret_callback() {
	$wps_release_secret = get_option('wps_release_secret');
	
	echo "<input type='text' name='wps_release_secret' value='$wps_release_secret' />";
}
