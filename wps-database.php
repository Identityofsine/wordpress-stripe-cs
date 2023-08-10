<?php




class Product {
	private $product_id;
	private $quantity;

	public function __construct(int $product_id = 0, int $quantity = 0) {
		$this->product_id = $product_id;
		$this->quantity = $quantity;
	}

	//getters
	public function get_product_id() {
		return $this->product_id;
	}

	public function get_quantity() {
		return $this->quantity;
	}


}

function CalculatePrice(array ...$products) {

	// get all $products and create one big string.
	$product_ids_string = '';

	for($i = 0; $i < sizeof($products); $i++) {
		$product_ids_string .= $products[$i]->get_product_id();
		if($i != sizeof($products) - 1) {
			$product_ids_string .= ',';
		}
	}


	global $wpdb;
	//query from wp_wc_product_meta_lookup to get all product ids and prices
	$product_ids = $wpdb->get_results("SELECT min_price FROM wp_wc_product_meta_lookup WHERE product_id in ($product_ids_string)");	

	$price = 0;
	foreach ($product_ids as $product_id) {
		$price += ($product_id->min_price) * 100; //convert to decimalless integer
	}

	return $price;
}