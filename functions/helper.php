<?php

defined( 'ABSPATH' ) || die();

/**
 * Check WooCommerce required files
 *
 * @return bool
 */
function ade_check_woo_files() {
	if ( defined( 'WC_ABSPATH' ) ) {
		// WC 3.6+ - Cart and other frontend functions are not included for REST requests.
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
	}

	if (
		null === WC()->session
	) {
		$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );

		WC()->session = new $session_class();
		WC()->session->init();
	}

	if ( null === WC()->customer ) {
		WC()->customer = new WC_Customer( get_current_user_id(), true );
	}

	if ( null === WC()->cart ) {
		WC()->cart = new WC_Cart();
		// We need to force a refresh of the cart contents from session here (cart contents are normally refreshed on wp_loaded, which has already happened by this point).
		WC()->cart->get_cart();
	}

	return true;
}

/**
 * Permissions check
 *
 * @return bool
 */
function ade_permissions_check() {

	$user = wp_get_current_user();
	// Check if the user is logged in.
	if ( 0 !== $user->ID ) {
		// log user in.
		ade_log_user_in( $user );

		return true;
	}
	return false;
}

/**
 * Log user in
 *
 * @param object $user User object.
 * @return void
 */
function ade_log_user_in( $user ) {
	// if not logged in.
	if ( ! is_user_logged_in() ) {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->user_id, $user->user_login );
		wp_set_auth_cookie( $user->user_id );
		update_user_caches( $user );
	}
}
function anony_wc_basic_get( $url ) {
	$params = $_GET;

	if ( ! empty( $params ) ) {

		foreach ( $params as $key => $value ) {
			$params[ $key ] = sanitize_text_field( $value );
		}
		$request_url = add_query_arg( $params, $url );

	} else {

		$request_url = $url;
	}
	$request = wp_remote_get(
		$request_url,
		array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( ANORAPI_CK . ':' . ANORAPI_CS ),
			),
		)
	);

	$response = wp_remote_retrieve_body( $request );

	if ( ! is_wp_error( $response ) ) {
		wp_send_json( json_decode( $response ) );
		die();
	} else {
		wp_send_json( array( 'msg' => 'No results found' ) );
		die();
	}
}
