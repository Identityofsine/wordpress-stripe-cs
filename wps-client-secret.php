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

add_action('init', 'endpoint_override');

function endpoint_override() {
    add_rewrite_rule('^ih-api/createpayment/?$', 'index.php?my_plugin_action=data_endpoint', 'top');
    add_rewrite_rule('^ih-api/getpayment/?$', 'index.php?my_plugin_action=action_endpoint', 'top');
}

// Step 4: Custom Query Variable
add_filter('query_vars', 'my_custom_query_var');

function my_custom_query_var($query_vars) {
    $query_vars[] = 'my_plugin_action';
    return $query_vars;
}