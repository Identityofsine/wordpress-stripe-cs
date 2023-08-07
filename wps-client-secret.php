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

/**
 * add_action is a function that adds a callback function to an action hook. Actions are the hoks that the wordpress core launched at specific points during execution, or when specific events occur. 
 */
require_once('wps-options.php');
require_once('stripe-secret.php');

add_action('init', 'endpoint_rewrite');

function endpoint_rewrite() {
	add_rewrite_rule('^ih-api/([^/]+)/', 'index.php?ih-api=api&param=$matches[1]', 'top');
}


/**
 * add_fitler is a function that adds a callback function to a filter hook. Filters are the hooks that WordPress launches to modify text of various types before adding it to the database or sending it to the browser screen.
 */
add_filter('query_vars', 'query_var_setup');

function query_var_setup($query_vars) {
    $query_vars[] = 'ih-api';
    return $query_vars;
}

/*
* Parse_Request is a Wordpress Hook that parses the request to find the correct WordPress query. For each request it will run through the functions listening on the hook and call one individually. 
* 
*/

//this adds the function below to the parse_request hook
add_action('parse_request', 'my_custom_endpoint_handler');


function my_custom_endpoint_handler($wp) {
	//array_key_exists is simple enough but what is this function doing?
	//This function is checking if the key 'ih-api' exists in the $wp->query_vars array, which is setup from query_var_setup above
	//&& &wp->query_vars is a condtional that checks if query_vars even exist
	if (array_key_exists('ih-api', $wp->query_vars) && $wp->query_vars['ih-api'] === 'client') {
		// Check the request method, POST, GET, Whatever
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// Handle the POST request here

			$raw_data = file_get_contents('php://input');
			$post_data = ConvertDataToJSON($raw_data)['data'];
			//check if the request is valid
			if (!VerifyRequest($post_data)) {
				//this acts as a return value for both the success and failure cases
				wp_send_json(['status' => 'failure', 'message' => 'Invalid Request'], 401);
				exit();
			}

			//get the client secret
			$client_secret = StripePost($post_data);

			//this acts as a return value for both the success and failure cases
			wp_send_json(['status' => 'success', 'echo' => $post_data]);
			exit();
		} else {

			//this acts as a return value for both the success and failure cases
			wp_send_json(['status' => 'success']);
			exit();
		}
	}
}

?>