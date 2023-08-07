<?php


function StripePost() {
	//payment intent URL
	$url = 'https://api.stripe.com/v1/payment_intents';
	
	$body = ['amount' => 2000, 'currency' => 'usd', 'payment_method_types' => ['card']];
	$body_mutated = http_build_query($body);
	//create a post request with a body
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERNAME, 'sk_test_51NbWbiLYTLpbXqDTD26oO5DgckN3r6eYt4Ly9gOnSjTY69La2hjfD3gSTADPpwel8qQLRWXoLARvUZPpg7CaGAVK00tgEqspPj');
	curl_setopt($ch, CURLOPT_PASSWORD, '');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body_mutated);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($body_mutated)));
	//send post request
	
	//convert response to JSON
	$response = ConvertDataToJSON(curl_exec($ch));
	try {
		if(isset($response['error'])) {
			//handle error
			return ['error' => $response['error']['message']]; //debug -- display error
		} else {
			//handle success
			return ['client_secret' => $response['client_secret'], 'response' => $response]; //debug -- display success
		}
	} catch (Exception $e) {
		//handle exception
		echo($e); //debug -- display exception
	}
	//close connection
	curl_close($ch);
	return ['error' => 'Something went wrong']; //debug -- display error

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
function VerifyRequest($request) {
	$valid_keys = ['id', 'items', 'discount_code', 'shipping_method', 'currency'];
	//check if JSON is empty
	if (empty($request)) {
		return false;
	}
	
	try{
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

function ConvertDataToJSON($raw_body) {
	$json_data = json_decode($raw_body, true);
	return $json_data;
}