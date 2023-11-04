<?php

use PHPMailer\PHPMailer\Exception;

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

		$wc_response = wps_wc_submit_order_post($wc_consumer_key, $wc_consumer_secret, [], $post_data);

		if ($wc_response['status'] === 'failure') {
			throw new Exception($wc_response['message']);
		}

		if (!isset($wc_response['id'])) {
			throw new Exception('Order not found');
		}


		if (!$stripe_response['completed']) {
			wp_send_json(['status' => 'failure', 'message' => 'Payment not completed'], 202);
			exit();
		}

		wp_send_json(['status' => 'success', 'order' => $wc_response, 'completed' => $stripe_response['completed']], 202);
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


function wps_wc_submit_order_post($wc_consumer_key, $wc_consumer_secret, $products, $post_data)
{

	//post code	
	try {

		$purchase_method = 'stripe';
		$purchase_method_title = 'Stripe';
		$shipping_method = ($post_data['shipping_lines']);
		$billing = ($post_data['billing']);
		$shipping = ($post_data['shipping']);
		$product_line_item = [];
		for ($i = 0; $i < count($products); $i++) {
			array_push($product_line_item, CastProductToLineItem($products[$i]));
		}
		$paid = true;
		//set post request to another wordpress plugin
		//endpoint : wp-json/wc/v3/orders
		$post_object = array(
			"payment_method" => $purchase_method,
			"payment_method_title" => $purchase_method_title,
			"set_paid" => $paid,
			"billing" => $billing,
			"shipping" => $shipping,
			"line_items" => $product_line_item,
			"shipping_lines" => $shipping_method
		);
		$json_object = json_encode($post_object);

		$base_url = get_site_url();

		//authentication with oauth
		$oauth_header = generateOAuthHeader($wc_consumer_key, $wc_consumer_secret, $base_url . '/wp-json/wc/v3/orders', 'POST', []);


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "$base_url/wp-json/wc/v3/orders");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_object);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($json_object),
			'Authorization: ' . $oauth_header
		));

		//execute POST
		$response = curl_exec($ch);
		curl_close($ch);

		if ($response === false) {
			throw new Exception('Curl error: ' . curl_error($ch));
		}

		//error codes, send back $response['message']
		$response = json_decode($response, true);
		if (isset($response['code'])) {
			throw new Exception($response['message']);
		}

		return $response;
	} catch (Exception $e) {
		wp_send_json(['status' => 'failure', 'message' => $e->getMessage()], 500);
		exit();
	};
}


function CastProductToLineItem(Product $product)
{
	$line_item = [];
	$line_item['product_id'] = $product->get_product_id() ?? 15;
	$line_item['quantity'] = $product->get_quantity() ?? 1;

	$meta_data = [];

	if ($product->get_attributes() == null) {
		return $line_item;
	}

	$product_attributes = $product->get_attributes();

	for ($i = 0; $i < count($product_attributes); $i++) {
		$attribute = $product_attributes[$i];
		$temp_array = array(
			'key' => $attribute['name'],
			'value' => $attribute['value']
		);
		array_push($meta_data, $temp_array);
	}
	$line_item['meta_data'] = $meta_data;

	return $line_item;
}


function isNull(...$args)
{
	for ($i = 0; $i < count($args); $i++) {
		if ($args[$i] == null) {
			return true;
		}
	}
	return false;
}

function generateOAuthHeader($consumerKey, $consumerSecret, $url, $method, $params)
{
	// Collect and normalize parameters
	$oauthParams = [
		'oauth_consumer_key' => $consumerKey,
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_timestamp' => time(),
		'oauth_nonce' => bin2hex(random_bytes(16)),
		'oauth_version' => '1.0',
	];

	$params = array_merge($params, $oauthParams);
	uksort($params, 'strcmp');

	// Create the parameter string
	$paramString = '';
	foreach ($params as $key => $value) {
		$paramString .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
	}
	$paramString = rtrim($paramString, '&');

	// Create the signature base string
	$baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);

	// Generate the signature
	$signingKey = rawurlencode($consumerSecret) . '&'; // Make sure to include the '&' character
	$signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

	// Add the signature to the parameters
	$params['oauth_signature'] = $signature;

	// Create the OAuth header
	$oauthHeader = 'OAuth ';
	foreach ($params as $key => $value) {
		$oauthHeader .= rawurlencode($key) . '="' . rawurlencode($value) . '", ';
	}
	$oauthHeader = rtrim($oauthHeader, ', ');

	return $oauthHeader;
}
