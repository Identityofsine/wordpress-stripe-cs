<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';
require_once($plugin_path);

function checkWooCommerceAlive () {
	return in_array( $plugin_path, wp_get_active_and_valid_plugins() ) || in_array( $plugin_path, wp_get_active_network_plugins() );
}

function CalculatePrice($product_ids) {
	if(checkWooCommerceAlive()){

		//convert $product_ids to a string [1,2,3...] to use in the query
		$product_ids_string = $product_ids->length > 0 ? implode(',', $product_ids) : '-1';
		

		global $wpdb;
		//query from wp_wc_product_meta_lookup to get all product ids and prices
		$product_ids = $wpdb->get_results("SELECT min_price FROM wp_wc_product_meta_lookup WHERE product_id in ($product_ids_string)");	
		return $product_ids;
	}
	return false;
}