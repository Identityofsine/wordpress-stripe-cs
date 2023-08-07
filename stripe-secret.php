<?php

function StripePost() {
	//payment intent URL
	$url = 'https://api.stripe.com/v1/payment_intents';

	

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