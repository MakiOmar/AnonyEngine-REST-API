<?php
/**
 * Cart API
 *
 * @package AnonyEngine Rest API
 */

defined( 'ABSPATH' ) || die;

/**
 * Cart API
 */
class Ade_WooCart {
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
	public function init() {
		// phpcs:disable
		add_filter(
			'determine_current_user',
			function ( $user_id ) {
				return $user_id;
				if ( strpos( $_SERVER['REQUEST_URI'], 'ade-woocart/v1/' ) !== false && ! empty( $_GET['user_id'] ) ) {
					$user_id = wp_strip_all_tags( $_GET['user_id'] );
				}

				return $user_id;
			}
		);
		// phpcs:enable
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
			'ade-woocart/v1',
			'/cart',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cart' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			'ade-woocart/v1',
			'/cart',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		// remove from cart.
		register_rest_route(
			'ade-woocart/v1',
			'/cart/(?P<key>\w+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_from_cart' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);
	}

	/**
	 * Check WooCommerce required files
	 *
	 * @return bool
	 */
	public function check_woo_files() {
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
	public function permissions_check() {

		$user = wp_get_current_user();
		// Check if the user is logged in.
		if ( 0 === $user->ID ) {
			return false;
		}
		// log user in.
		$this->log_user_in( $user );

		return true;
	}

	/**
	 * Log user in
	 *
	 * @return void
	 */
	public function log_user_in( $user ) {
		// if not logged in.
		if ( ! is_user_logged_in() ) {
			wp_clear_auth_cookie();
			wp_set_current_user( $user->user_id, $user->user_login );
			wp_set_auth_cookie( $user->user_id );
			update_user_caches( $user );
		}
		$this->check_woo_files();
	}
	/**
	 * Get current user cart
	 *
	 * @return array
	 */
	public function get_cart() {
		// Ensure WooCommerce session and cart are initialized.
		if ( null === WC()->session ) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}

		if ( null === WC()->cart ) {
			WC()->cart = new WC_Cart();
		}
		// Restore the cart for the session.
		WC()->cart->get_cart_from_session();

		$cart = WC()->cart->get_cart();
		// loop through cart.
		$cart_data = array();
		foreach ( $cart as $key => $value ) {
			$product     = wc_get_product( $value['product_id'] );
			$cart_data[] = array(
				'key'           => $key,
				'product_id'    => $value['product_id'],
				'product_name'  => $product->get_name(),
				'product_price' => $product->get_price(),
				'product_image' => get_the_post_thumbnail_url( $value['product_id'], 'thumbnail' ),
				'quantity'      => $value['quantity'],
			);
		}
		return $cart_data;
	}

	/**
	 * Add to cart
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	public function add_to_cart( WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$quantity   = $request->get_param( 'quantity' );
		$attributes = array();

		// check if product exists.
		if ( ! wc_get_product( $product_id ) ) {
			wp_send_json_error( 'Product not found', 404 );
		}
		if ( 'product_variation' === get_post_type( $product_id ) ) {
			$attributes = $request->get_param( 'attributes' ) ? $request->get_param( 'attributes' ) : false ;
			if ( ! $attributes ) {
				wp_send_json_error( 'Attributes are missing', 400 );
			}
		}
		// add to cart.
		WC()->cart->add_to_cart( $product_id, $quantity, 0, $attributes );

		return $this->get_cart();
	}

	/**
	 * Remove from cart
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	public function remove_from_cart( WP_REST_Request $request ) {
		$key = $request->get_param( 'key' );
		// confirm key exists.
		if ( ! isset( WC()->cart->cart_contents[ $key ] ) ) {
			return new WP_Error( 'key_not_found', 'Key not found', array( 'status' => 404 ) );
		}
		WC()->cart->remove_cart_item( $key );
		return new WP_REST_Response(
			array(
				'message' => 'Item removed from cart',
				'cart'    => $this->get_cart(),
			),
			200
		);
	}
}

// init.
$ade_woo_cart = new Ade_WooCart();
$ade_woo_cart->init();
