<?php


function getStripeSecretKey()
{
	//get the secret key from the database
	$is_release = get_option('wps_release_mode', false);

	if ($is_release) {
		$stripe_secret = get_option('wps_release_secret', false);
	} else {
		$stripe_secret = get_option('wps_client_secret', false);
	}

	return $stripe_secret;
}

function StripePost($post_data, $secret_key)
{
	//payment intent URL
	$url = 'https://api.stripe.com/v1/payment_intents';

	$body = $post_data;

	$body_mutated = http_build_query($body);
	//create a post request with a body
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERNAME, $secret_key);
	curl_setopt($ch, CURLOPT_PASSWORD, '');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body_mutated);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($body_mutated)));
	//send post request

	//convert response to JSON
	$response = ConvertDataToJSON(curl_exec($ch));
	try {
		if (isset($response['error'])) {
			//handle error
			return ['error' => $response['error']['message']]; //debug -- display error
		} else {
			//handle success
			return ['client_secret' => $response['client_secret'], 'id' => $response['id'],  'response' => $response]; //debug -- display success
		}
	} catch (Exception $e) {
		//handle exception
		echo ($e); //debug -- display exception
	}
	//close connection
	curl_close($ch);
	return ['error' => 'Something went wrong']; //debug -- display error

}

function StripeGet($payment_intent_id, $secret_key)
{
	$url = 'https://api.stripe.com/v1/payment_intents';

	//check if $payment_intent_id is empty
	if (empty($payment_intent_id))
		throw new Exception('Payment Intent ID is required');
	//join $url with $payment_intent_id.
	$url = $url . '/' . $payment_intent_id;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERNAME, $secret_key);
	curl_setopt($ch, CURLOPT_PASSWORD, '');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	$response = ConvertDataToJSON(curl_exec($ch));
	try {
		if (isset($response['error'])) {
			//handle error
			throw new Exception($response['error']['message']);
		} else {
			//handle success
			$expected_amount = $response['amount'];
			$received_amount = $response['amount_received'];
			return ['client_secret' => $response['client_secret'], 'id' => $response['id'],  'completed' => ($expected_amount - $received_amount) <= 0]; //debug -- display success
		}
	} catch (Exception $e) {
		return ['error' => 'Something went Wrong', 'message' => $e->getMessage()];
	}
}


/** 
 * @summary This function takes in a JSON Object from the POST Request and verifies that the request is valid. The rules for this are in the obsidean file but as a dropoff:
 *data : {
 * id:1425,
 * items:[ 
 * 	//The expect item object would contain an object id(that corresponds to WooCommerce/Database) and the quantity of the product.
 * 	
 * 	{
 * 		id:24,
 * 		quantity:1,
 * 	},
 * 	{
 * 		id:14,
 * 		quantity:2,
 * 	},
 * 	{
 * 		id:54,
 * 		quantity:1,
 * 	},
 * ],
 * discount_code:'AZJAX',
 * shipping_method:1,
 * currency:'USD'
 *}
 */
function VerifyRequest($request)
{
	$valid_keys = ['id', 'items', 'discount_code', 'shipping_method', 'currency'];
	//check if JSON is empty
	if (empty($request)) {
		return false;
	}

	try {
		//loop through array and check if the keys are valid
		foreach ($valid_keys as $key) {
			if (!isset($request[$key])) {
				return false;
			}
		}
	} catch (Exception $e) {
		return false;
	}

	return true;
}

function ConvertDataToJSON($raw_body)
{
	$json_data = json_decode($raw_body, true);
	return $json_data;
}

