<?php


require_once('wps-debug.php');


class Product
{
	private $product_id;
	private $quantity;

	public function __construct(int $product_id = 0, int $quantity = 0)
	{
		$this->product_id = $product_id;
		$this->quantity = $quantity;
	}

	// Getters
	public function get_product_id()
	{
		return $this->product_id;
	}

	public function get_quantity()
	{
		return $this->quantity;
	}
}

function ConvertProductArrayToJSON(array $products) {
	try {
		$product_array = [];
		for($i = 0; $i < sizeof($products); $i++) {
			$product = $products[$i];
			if(!($product instanceof Product)) {
				throw new Exception('Product is not of type Product');
			}
			$product_id = $product->get_product_id();
			$product_quantity = $product->get_quantity();
			array_push($product_array, array('id' => $product_id, 'quantity' => $product_quantity));
		}
		return json_encode($product_array);
	} catch (Exception $e) {
		throw new Exception('Something went wrong...');
	}
}




function CalculatePrice(array $products)
{

	// get all $products and create one big string.
	try {
		$product_ids_string = '';

		for ($i = 0; $i < sizeof($products); $i++) {
			$product = ($products[$i]);
			//make sure $product is of type Product
			if (!($product instanceof Product)) {
				throw new Exception('Product is not of type Product');
			}
			$product_ids_string .= $product->get_product_id();
			if ($i != sizeof($products) - 1) {
				$product_ids_string .= ',';
			}
		}


		global $wpdb;
		//query from wp_wc_product_meta_lookup to get all product ids and prices
		$product_ids = $wpdb->get_results("SELECT product_id, min_price FROM wp_wc_product_meta_lookup WHERE product_id in ($product_ids_string)");

		$price = 0;
		$i = 0;
		foreach ($product_ids as $product_id) {
			$price += ($product_id->min_price * $products[$i++]->get_quantity()) * 100; //convert to decimalless integer
		}

		return $price;
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}


//create a table that stores
/*
	{
		id: UUID,
		token: UUID,
		products: [
			{
				product_id: int,
				attributes: [
					{
						name: string,
						value: string
					}
				],
				quantity: int
			}
		],
		subtotal: int,
	}
*/
function CreateIntentTable() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	try {
		$intent_table = $wpdb->prefix ."order_intent";
		$statement = "CREATE TABLE IF NOT EXISTS $intent_table (
			id VARCHAR(255) NOT NULL AUTO INCREMENT,
			token VARCHAR(255) NOT NULL,
			products BLOB NOT NULL,
			subtotal INT NOT NULL,
			PRIMARY KEY (id),
			UNIQUE(id, token)
		) $charset_collate;";
		//execute statement

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $statement );
		PrintToConsole('Created table');

	} catch (Exception $e) {
		//print to dev console
		PrintToConsole('Caught exception: ' . $e->getMessage());

	}
}

function DropIntentTable() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	try {
		$intent_table = $wpdb->prefix .'order_intent';
		$statement = 'DROP TABLE IF EXISTS ' . $intent_table . ';';
		$wpdb->query( $statement );
		PrintToConsole('Dropped table');
	} catch (Exception $e) {
		PrintToConsole(''. $e->getMessage());
	}
}

function AddOrderIntent(array $products, string $paymentintent_id) {
	global $wpdb;
	$table_name = $wpdb->prefix .'order_intent';
	try {
		//generate UUID for id
		$uuid = random_int(1000,999999).'_id';
		//cast product to Product class
		$product_json = ConvertProductArrayToJSON($products);
		$wpdb->insert($table_name, array('id' => $uuid, 'token' => $paymentintent_id, 'products' => $product_json, 'subtotal' => CalculatePrice($products)));
		return $uuid;
	} catch (Exception $e) {
		throw new Exception(''. $e->getMessage());
	}
}