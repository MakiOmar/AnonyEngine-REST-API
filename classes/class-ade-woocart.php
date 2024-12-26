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
	 * Permissions check
	 *
	 * @return bool
	 */
	public function permissions_check() {
		ade_check_woo_files();
		return ade_permissions_check();
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
		$products = $request->get_json_params(); // Retrieve JSON body as an array.

		if ( ! is_array( $products ) || empty( $products ) ) {
			wp_send_json_error( 'Invalid or empty products array', 400 );
		}

		foreach ( $products as $product_data ) {
			$product_id = isset( $product_data['product_id'] ) ? $product_data['product_id'] : null;
			$quantity   = isset( $product_data['quantity'] ) ? $product_data['quantity'] : 1;
			$attributes = isset( $product_data['attributes'] ) ? $product_data['attributes'] : array();

			// Validate product ID.
			if ( ! $product_id || ! wc_get_product( $product_id ) ) {
				wp_send_json_error( sprintf( 'Product not found for ID %s', esc_html( $product_id ) ), 404 );
			}

			// Validate attributes for variable products.
			if ( 'product_variation' === get_post_type( $product_id ) && empty( $attributes ) ) {
				wp_send_json_error( sprintf( 'Attributes are missing for product ID %s', esc_html( $product_id ) ), 400 );
			}

			// Add product to cart.
			WC()->cart->add_to_cart( $product_id, $quantity, 0, $attributes );
		}

		return $this->get_cart(); // Return updated cart.
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
