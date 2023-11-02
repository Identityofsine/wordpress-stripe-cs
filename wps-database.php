<?php

use PHPMailer\PHPMailer\Exception;

require_once('wps-debug.php');


class ProductAttribute
{
	private $name;
	private $value;

	public function __construct(string $name, string $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getValue()
	{
		return $this->value;
	}
}

class Product
{
	private $product_id;
	private $quantity;
	private $attributes;

	public function __construct(int $product_id = 0, int $quantity = 0, array $attributes = [])
	{
		$this->product_id = $product_id;
		$this->quantity = $quantity;
		$this->attributes = $attributes;
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

	public function get_attributes()
	{
		return $this->attributes;
	}
}

function ConvertProductArrayToJSON(array $products)
{
	try {
		$product_array = [];
		for ($i = 0; $i < sizeof($products); $i++) {
			$product = $products[$i];
			if (!($product instanceof Product)) {
				throw new Exception('Product is not of type Product');
			}
			$product_id = $product->get_product_id();
			$product_quantity = $product->get_quantity();
			$product_attributes = [];

			foreach ($product->get_attributes() as $attribute) {
				array_push($product_attributes, array('name' => $attribute->getName(), 'value' => $attribute->getValue()));
			};

			array_push($product_array, array('id' => $product_id, 'quantity' => $product_quantity, 'attributes' => $product_attributes));
		}
		return json_encode($product_array);
	} catch (Exception $e) {
		throw new Exception('Something went wrong...');
	}
}

function ConvertJSONToProductArray(string $json): array
{
	$product_array = json_decode($json, true);
	$products = [];
	foreach ($product_array as $product) {
		$product_id = $product['id'];
		$product_quantity = $product['quantity'];
		$json_product_attributes = $product['attributes'];
		$product_attributes = [];
		foreach ($json_product_attributes as $attribute) {
			$product_attribute = new ProductAttribute($attribute['name'], $attribute['value']);
			array_push($product_attributes, $product_attribute);
		}

		array_push($products, new Product($product_id, $product_quantity, $product_attributes));
	}
	return $products;
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
function CreateIntentTable()
{
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	try {
		$intent_table = $wpdb->prefix . "order_intent";
		$statement = "CREATE TABLE IF NOT EXISTS $intent_table (
			id VARCHAR(255) NOT NULL,
			token VARCHAR(255) NOT NULL,
			products BLOB NOT NULL,
			subtotal INT NOT NULL,
			created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE(id, token)
		) $charset_collate;";
		//execute statement

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($statement);
		PrintToConsole('Created table');
	} catch (Exception $e) {
		//print to dev console
		PrintToConsole('Caught exception: ' . $e->getMessage());
	}
}

function DoesIntentTableExist(): bool
{
	global $wpdb;
	$intent_table = $wpdb->prefix . 'order_intent';
	$statement = 'SELECT * FROM ' . $intent_table . ';';
	$result = $wpdb->query($statement);
	return $result !== false;
}

function DropIntentTable()
{
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	try {
		$intent_table = $wpdb->prefix . 'order_intent';
		$statement = 'DROP TABLE IF EXISTS ' . $intent_table . ';';
		$wpdb->query($statement);
		PrintToConsole('Dropped table');
	} catch (Exception $e) {
		PrintToConsole('' . $e->getMessage());
	}
}

function AddOrderIntent(array $products, string $paymentintent_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'order_intent';
	if (!DoesIntentTableExist()) {
		CreateIntentTable();
	}

	try {
		//generate UUID for id
		$uuid = random_int(1000, 999999) . '_id';
		//cast product to Product class
		$product_json = ConvertProductArrayToJSON($products);
		$wpdb->insert($table_name, array('id' => $uuid, 'token' => $paymentintent_id, 'products' => $product_json, 'subtotal' => CalculatePrice($products)));
		return $uuid;
	} catch (Exception $e) {
		throw new Exception('' . $e->getMessage());
	}
}


class OrderIntent
{
	private $id;
	private $token;
	private $products;
	private $subtotal;

	//constructor
	public function __construct(string $id, string $token, array $products, int $subtotal)
	{
		$this->id = $id;
		$this->token = $token;
		$this->products = $products;
		$this->subtotal = $subtotal;
	}

	//getters
	public function get_id()
	{
		return $this->id;
	}

	public function get_token()
	{
		return $this->token;
	}

	public function get_products()
	{
		return $this->products;
	}

	public function get_subtotal()
	{
		return $this->subtotal;
	}
}

function GetOrderIntent(string $paymentintent_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'order_intent';

	try {
		$statement = "SELECT * FROM $table_name WHERE token = '$paymentintent_id';";
		$result = $wpdb->get_results($statement);
		if (sizeof($result) == 0) {
			return false;
		}
		return new OrderIntent($result[0]->id, $result[0]->token, ConvertJSONToProductArray($result[0]->products), $result[0]->subtotal);
	} catch (Exception $e) {
		throw new Exception('' . $e->getMessage());
	}
}

function GetOrderIntentByID(string $id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'order_intent';

	try {
		$statement = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $id);
		$result = $wpdb->get_results($statement);
		if (sizeof($result) == 0) {
			throw new Exception('No order intent found');
		}
		return new OrderIntent($result[0]->id, $result[0]->token, ConvertJSONToProductArray($result[0]->products), $result[0]->subtotal);
	} catch (Exception $e) {
		throw new Exception('' . $e->getMessage());
	}
}

