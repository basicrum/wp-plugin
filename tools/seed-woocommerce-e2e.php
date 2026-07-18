<?php
/**
 * Seed a deterministic WooCommerce storefront for browser E2E tests.
 *
 * This file runs through `wp eval-file` after WordPress, WooCommerce, and
 * Basicrum are active in an isolated test stack.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$product = new WC_Product_Simple();
$product->set_name( 'Basicrum E2E Product' );
$product->set_regular_price( '10.00' );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product_id = $product->save();

if ( ! $product_id ) {
	throw new RuntimeException( 'Could not create the WooCommerce E2E product.' );
}

wp_update_post(
	array(
		'ID'        => $product_id,
		'post_name' => 'basicrum-e2e-product',
	)
);

$product = wc_get_product( $product_id );

if ( ! $product ) {
	throw new RuntimeException( 'Could not load the WooCommerce E2E product.' );
}

add_filter( 'pre_wp_mail', '__return_true' );

$order = wc_create_order();

if ( ! $order ) {
	throw new RuntimeException( 'Could not create the WooCommerce E2E order.' );
}

$order_item_id = $order->add_product( $product, 1 );

if ( ! $order_item_id ) {
	throw new RuntimeException( 'Could not add the product to the WooCommerce E2E order.' );
}

$order->calculate_totals();
$order->set_status( 'completed' );
$order->save();

$payment_order = wc_create_order();

if ( ! $payment_order ) {
	throw new RuntimeException( 'Could not create the WooCommerce E2E payment order.' );
}

$payment_order_item_id = $payment_order->add_product( $product, 1 );

if ( ! $payment_order_item_id ) {
	throw new RuntimeException( 'Could not add the product to the WooCommerce E2E payment order.' );
}

$payment_order->calculate_totals();
$payment_order->set_status( 'pending' );
$payment_order->save();

$routes = array(
	'shop'           => wc_get_page_permalink( 'shop' ),
	'product'        => get_permalink( $product_id ),
	'cart'           => wc_get_cart_url(),
	'cart_add'       => add_query_arg( 'add-to-cart', $product_id, wc_get_cart_url() ),
	'checkout'       => wc_get_checkout_url(),
	'order_pay'      => $payment_order->get_checkout_payment_url(),
	'order_received' => $order->get_checkout_order_received_url(),
);

foreach ( $routes as $route_name => $route_url ) {
	if ( empty( $route_url ) ) {
		throw new RuntimeException( sprintf( 'Could not determine the %s test route.', $route_name ) );
	}
}

$upload_directory = wp_upload_dir();

if ( ! empty( $upload_directory['error'] ) ) {
	throw new RuntimeException( 'Could not access the WordPress uploads directory.' );
}

$fixture_path = trailingslashit( $upload_directory['basedir'] ) . 'basicrum-woocommerce-e2e.json';
$fixture      = wp_json_encode(
	array(
		'routes' => $routes,
	),
	JSON_UNESCAPED_SLASHES
);

if ( false === $fixture || false === file_put_contents( $fixture_path, $fixture ) ) {
	throw new RuntimeException( 'Could not write the WooCommerce E2E fixture.' );
}

WP_CLI::log( "Seeded WooCommerce E2E routes in $fixture_path" );
