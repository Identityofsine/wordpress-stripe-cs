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
		<h2>Stripe PaymentIntent Settings</h2>

		<?php
}