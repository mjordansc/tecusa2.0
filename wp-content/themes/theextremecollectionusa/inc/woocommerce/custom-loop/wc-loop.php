<?php
/**
 * Custom Woocommerce Product Loop
 *
 * @package The_Extreme_Collection_USA
 */

/* ------------- Custom Loop */

function wooNativeLoop ($custom_args) {
	if(!function_exists('wc_get_products')) {
		return;
	}

	$basic_args = array(
	 	'limit'					=> -1,
		'paginate'			=> false,
		'return'				=> 'ids',
	);
	$args = array_merge($basic_args, $custom_args);
	$custom_products = wc_get_products($args);
	return $custom_products;
}

function wooCustomizeLoop ($custom_args) {
	if(!function_exists('wc_get_products')) {
		return;
	}

	$paged                   = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
	//$ordering                = WC()->query->get_catalog_ordering_args();
	//$ordering['orderby']     = array_shift(explode(' ', $ordering['orderby']));
	//$ordering['orderby']     = stristr($ordering['orderby'], 'price') ? 'meta_value_num' : $ordering['orderby'];
	$products_per_page       = apply_filters('loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page());

	$basic_args = array(
	 	'limit'					=> $products_per_page,
		'page'					=> $paged,
		'paginate'				=> true,
		'return'				=> 'ids',
		'orderby'				=> 'date',
		'visibility' 			=> 'catalog',
		'order'					=> 'DESC',
	);
	$args = array_merge($basic_args, $custom_args);
	$custom_products = wc_get_products($args);

	wc_set_loop_prop('current_page', $paged);
	wc_set_loop_prop('is_paginated', wc_string_to_bool(true));
	wc_set_loop_prop('page_template', get_page_template_slug());
	wc_set_loop_prop('per_page', $products_per_page);
	wc_set_loop_prop('total', $custom_products->total);
	wc_set_loop_prop('total_pages', $custom_products->max_num_pages);
	return $custom_products;
}


/* Add custom meta support for woocommerce loop (Available PreOrder) */
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'add_custom_meta_query_keys', 10, 3 );
function add_custom_meta_query_keys( $wp_query_args, $query_vars, $data_store_cpt ) {
    $meta_key = 'products_available_preorder';
    if ( ! empty( $query_vars[$meta_key] ) ) {
        $wp_query_args['meta_query'][] = array(
			'key'     => $meta_key,
			'value'   => esc_attr( $query_vars[$meta_key] ),
			'compare' => 'LIKE'
        );
    }
    return $wp_query_args;
}

add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'add_price_range_meta_query', 10, 3 );
function add_price_range_meta_query( $query, $query_vars ) {
    if ( ! empty( $query_vars['price_range'] ) ) {
		$price_range = explode( '|', esc_attr($query_vars['price_range']) );
		$query['meta_query'][] =  array( 
			'relation' => 'AND',
			'price_max' => array(
				'key' 		=> '_price',
				'value'   	=> end($price_range ), // To price value
				'compare' 	=> '<=',
				'type' 		=> 'numeric'
			),
			'price_min' => array(
				'key'	 	=> '_price',
				'value'   	=> reset($price_range), // From price value
				'compare' 	=> '>=',
				'type' 		=> 'numeric'
			)
		);
	}
    return $query;
}