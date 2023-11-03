<?php
/*
* Plugin Name:       RESTful Stripe + WooCommerce PaymentIntent
* Description:       Creates a robust Payment Intent within the server side.
* Version:           2.0
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

header("Access-Control-Allow-Origin: *");

require_once('wps-options.php');
require_once('wps-debug.php');
require_once('stripe-secret.php');
require_once('wps-database.php');
require_once('wps-verify-order.php');



function wps_stripe_database_install()
{
	//this function runs when wordpress installs/loads this plugin
	//init database table
	CreateIntentTable();
}

function wps_stripe_database_uninstall()
{
	DropIntentTable();
}

register_activation_hook(__FILE__, 'wps_stripe_database_install');
register_uninstall_hook(__FILE__, 'wps_stripe_database_uninstall');

//this adds the function below to the rest_api_init hook
add_action('rest_api_init', 'register_endpoint_handler');

function register_endpoint_handler()
{
	add_action('rest_pre_serve_request', function () {
		header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Wpml-Language', true);
		header("Access-Control-Allow-Origin: *");
	});
	register_rest_route('paymentintent-api', '/create', array(
		'methods' => 'POST',
		'callback' => 'create_paymentintent_endpoint_handler',
	));
	register_rest_route('paymentintent-api', '/verify', array(
		'methods' => 'POST',
		'callback' => 'verify_payment_endpoint_handler',
	));
}
//the full url would be : 

function create_paymentintent_endpoint_handler($wp)
{
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

			//create array of items --> cast them into Product

			$items = []; //array of Product

			for ($i = 0; $i < count($post_data['items']); $i++) {
				$item = $post_data['items'][$i];

				if ($item['id'] == null || $item['quantity'] == null) {
					throw new Exception('Item ID or Quantity is null');
				}

				$item_attributes = [];
				for ($j = 0; $j < count($item['attributes']); $j++) {
					$attribute = $item['attributes'][$j];
					if ($attribute['name'] == null || $attribute['value'] == null) {
						throw new Exception('Attribute name or value is null');
					}
					array_push($item_attributes, new ProductAttribute($attribute['name'], $attribute['value']));
				};

				$product = new Product($item['id'], $item['quantity'], $item_attributes);
				array_push($items, $product);
			}

			$calculated_price = CalculatePrice($items);

			if ($calculated_price <= 0) {
				throw new Exception('Price is less than or equal to 0');
			}

			$converted_data = ['amount' => $calculated_price, 'currency' => 'usd', 'payment_method_types' => ['card']];
			$stripe_secret = get_option('wps_client_secret', false);


			if ($stripe_secret === false) {
				//this acts as a return value for both the success and failure cases
				throw new Exception('Stripe Secret is not set. Go to your admin page, click on Settings, then Stripe PaymentIntent Settings, and enter your Stripe Secret.');
			}

			$stripe_response = StripePost($converted_data, $stripe_secret);
			$paymentintent_id = '';
			$client_secret = '';
			$orderintent_id = '';

			if (isset($stripe_response['error'])) {
				//this acts as a return value for both the success and failure cases
				throw new Exception($stripe_response['error']);
			} else {
				$client_secret = $stripe_response['client_secret'];
				$paymentintent_id = $stripe_response['id'];
				$orderintent_id = AddOrderIntent($items, $paymentintent_id);
			}

			//return 
			//{
			//	'status':'success'
			//	'secret':'pk_client-fasdgasdfg'
			//	'amount': 1000 : number
			//	'id': 'pk_sdfafgasdf'	
			//}
			wp_send_json(['status' => 'success', 'order_id' => $orderintent_id, 'id' => $paymentintent_id, 'secret' => $client_secret, 'amount' => $calculated_price], 200);
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

