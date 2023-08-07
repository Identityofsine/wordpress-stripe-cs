<?php
/*
 * Plugin Name:       Stripe Client Secret
 * Description:       Grabs and Returns the Client Secret based on the users transaction.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kevin Erdogan
 * Author URI:        https://identityofsine.github.io/
 * License:           GPL v2 or later
 * Text Domain:       stripe-client-secret
 * Domain Path:       /ih-api
 */

add_action('init', 'endpoint_rewrite');

function endpoint_rewrite() {
	add_rewrite_rule('^ih-api/([^/]+)/?$','index.php?ih-api=$matches[1]','top');
}

// Step 2: Custom Query Variable
add_filter('query_vars', 'query_vars_function');

function query_vars_function($query_vars) {
    $query_vars[] = 'ih-api-function';
    return $query_vars;
}

// Step 5: Handle the POST Request
add_action('parse_request', 'my_custom_endpoint_handler');

function my_custom_endpoint_handler($wp) {
    if (array_key_exists('ih-api-function', $wp->query_vars) && $wp->query_vars['ih-api-function'] === 'post_dummy') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle the POST request here
            // Process the received data and perform the desired actions
            $received_data = file_get_contents('php://input');
            // Your data processing and actions code here...
            // Optionally, send a response back to the requester
            wp_send_json(['status' => 'success']);
            exit();
        }
    }
}

?>