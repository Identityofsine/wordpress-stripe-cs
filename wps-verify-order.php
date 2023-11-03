<?php


require_once('stripe-secret.php');
require_once('wps-database.php');

/**
 * @param $data - the data sent from the client (in the form of GET Parameters)
 */
function verify_payment_endpoint_handler($data)
{
	$raw_data = file_get_contents('php://input');
	$post_data = ConvertDataToJSON($raw_data)['data'];
	$id = $post_data['order_intent_id'];
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

		$wc_consumer_key = get_option('wps_wc_consumer_key', false);
		$wc_consumer_secret = get_option('wps_wc_consumer_secret', false);

		if ($wc_consumer_key === false || $wc_consumer_secret === false) {
			throw new Exception('WooCommerce Consumer Key or Consumer Secret not found');
		}

		wps_wc_submit_order_post($wc_consumer_key, $wc_consumer_secret, []);

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


/**
 * $shipping 
 * $cart
 *
 */


function wps_wc_submit_order_post($wc_consumer_key, $wc_consumer_secret, $products)
{

	//post code	
	try {
		$raw_data = file_get_contents('php://input');
		$post_data = ConvertDataToJSON($raw_data)['data'];
		$purchase_method = $post_data['purchase_method'];
		$purchase_method_title = $post_data['purchase_method_title'];
		$shipping_method = $post_data['shipping_method'];
		$billing = $post_data['billing'];
		$shipping = $post_data['shipping'];
		$line_items = CastProductToLineItem($products); //mutate this into a line_item object
		$paid = true;

		//set post request to another wordpress plugin
		//endpoint : wp-json/wc/v3/orders



		$base_url = get_site_url();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "$base_url/wp-json/wc/v3/orders");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			"payment_method" => $purchase_method,
			"payment_method_title" => $purchase_method_title,
			"set_paid" => $paid,
			"billing" => $billing,
			"shipping" => $shipping,
			"line_items" => $line_items,
			"shipping_lines" => $shipping_method
		));

		//execute POST
		$response = curl_exec($ch);
	} catch (Exception $e) {
		wp_send_json(['status' => 'failure', 'message' => $e->getMessage()], 500);
		exit();
	};
}


function CastProductToLineItem($product)
{
	$line_item = [];
	$line_item['product_id'] = $product['id'];
	$line_item['quantity'] = $product['quantity'];

	$meta_data = [];

	if ($product['attributes'] == null) {
		return $line_item;
	}

	for ($i = 0; $i < count($product['attributes']); $i++) {
		$attribute = $product['attributes'][$i];
		$meta_data[$i]['key'] = $attribute['name'];
		$meta_data[$i]['value'] = $attribute['value'];
	}

	return $line_item;
}
