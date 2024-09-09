<?php
/**
 * Checkout API
 *
 * @package AnonyEngine Rest API
 */

defined( 'ABSPATH' ) || die;

/**
 * Cart API
 */
class Ade_Woocheckout {
	/**
	 * User object
	 *
	 * @var object
	 */
	public $user;

	/**
	 * Initialization function
	 *
	 * @return void
	 */
	public function __construct() {
		// rest api init.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}
	/**
	 * API Routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'ade-woo/v1',
			'/checkout',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_wc_checkout_data' ),
				'permission_callback' => 'ade_permissions_check',
			)
		);
	}

	/**
	 * Callback function to retrieve WooCommerce checkout data
	 *
	 * @return mixed
	 */
	public function get_wc_checkout_data() {
		// Get the current user.
		$current_user = wp_get_current_user();

		// Check if the user is logged in.
		if ( 0 === $current_user->ID ) {
			return new WP_Error( 'not_logged_in', 'You must be logged in to retrieve checkout data.', array( 'status' => 401 ) );
		}

		// Prepare and return the data.
		$data = array(
			'user' => $current_user,
			'cart' => mkh_get_cart( $current_user->ID ),
		);

		return rest_ensure_response( $data );
	}
}

// init.
new Ade_Woocheckout();
