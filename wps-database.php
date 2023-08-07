<?php




function CalculatePrice($product_ids) {

	//convert $product_ids to a string [1,2,3...] to use in the query
	$product_ids_string = sizeof($product_ids) > 0 ? implode(',', $product_ids) : '-1';

	global $wpdb;
	//query from wp_wc_product_meta_lookup to get all product ids and prices
	$product_ids = $wpdb->get_results("SELECT min_price FROM wp_wc_product_meta_lookup WHERE product_id in ($product_ids_string)");	

	$price = 0;
	foreach ($product_ids as $product_id) {
		$price += ($product_id->min_price) * 100; //convert to decimalless integer
	}

	return $price;
}