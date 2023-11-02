<?php


require_once('stripe-secret.php');
require_once('wps-database.php');

/**
 * @param $data - the data sent from the client (in the form of GET Parameters)
 */
function verify_payment_endpoint_handler($data)
{
	$id = $data['id'];
	$stripe_secret = get_option('wps_client_secret', false);
	try {
		//call StripeGet : JSON request.
		$stripe_response = StripeGet($id, $stripe_secret);

		if ($stripe_response['error']) {
			throw new Exception($stripe_response['message']);
		}

		$order_intent = GetOrderIntent($id);
		if ($order_intent === false) {
			throw new Exception('Order intent not found');
		}

		if (!$stripe_response['completed']) {
			wp_send_json(['status' => 'failure', 'message' => 'Payment not completed'], 202);
			exit();
		}

		wp_send_json(['status' => 'success', 'order' => $order_intent, 'completed' => $stripe_response['completed']], 202);
	} catch (Exception $e) {
		//this acts as a return value for both the success and failure cases
		wp_send_json(['status' => 'failure', 'message' => $e->getMessage()], 500);
		exit();
	}
}

