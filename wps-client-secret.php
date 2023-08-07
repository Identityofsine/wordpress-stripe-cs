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
require_once('wps-database.php');

//this adds the function below to the rest_api_init hook
add_action( 'rest_api_init', 'register_endpoint_handler' );

function register_endpoint_handler() {
	register_rest_route( 'ih-api', '/client', array(
		'methods' => 'POST',
		'callback' => 'endpoint_handler',
	) );
}
//the full url would be : 

function endpoint_handler($wp) {
	//array_key_exists is simple enough but what is this function doing?
	//This function is checking if the key 'ih-api' exists in the $wp->query_vars array, which is setup from query_var_setup above
	//&& &wp->query_vars is a condtional that checks if query_vars even exist
		// Check the request method, POST, GET, Whatever
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// Handle the POST request here
			try {
				$raw_data = file_get_contents('php://input');
				$post_data = ConvertDataToJSON($raw_data)['data'];
				//check if the request is valid
				if (!VerifyRequest($post_data)) {
					//this acts as a return value for both the success and failure cases
					wp_send_json(['status' => 'failure', 'message' => 'Invalid Request'], 401);
					exit();
				}
				
				//get the client secret
				// $client_secret = StripePost($post_data, get_option('wps_client_secret'))['client_secret'];
				
				$calculated_price = CalculatePrice($post_data['items']);
				
				if($calculated_price <= 0) {
					throw new Exception('Price is less than or equal to 0');
				}

				$converted_data = ['amount' => $calculated_price, 'currency' => 'usd', 'payment_method_types' => ['card']];
				$stripe_secret = get_option('wps_client_secret', false);
				
				
				if($stripe_secret === false) {
					//this acts as a return value for both the success and failure cases
					throw new Exception('Stripe Secret is not set. Go to your admin page, click on Settings, then Stripe PaymentIntent Settings, and enter your Stripe Secret.');
				}
				
				$client_secret = StripePost($converted_data, $stripe_secret);
				
				if(isset($client_secret['error'])) {
					//this acts as a return value for both the success and failure cases
					throw new Exception($client_secret['error']);
				} else {
					$client_secret = $client_secret['client_secret'];
				}
				
				//this acts as a return value for both the success and failure cases
				wp_send_json(['status' => 'success', 'secret' => $client_secret, 'amount' => $calculated_price], 200);
				exit();
				
			} catch (Exception $e) {
				//this acts as a return value for both the success and failure cases
				wp_send_json(['status' => 'failure', 'message' => 'Something went wrong', 'exception' => $e->getMessage()], 500);
				exit();
			}
		} else {
			
			//this acts as a return value for both the success and failure cases
			wp_send_json(['status' => 'success']);
			exit();
		}
}
?>