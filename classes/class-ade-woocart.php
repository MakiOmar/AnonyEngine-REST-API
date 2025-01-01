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
			'ade-woo/v1',
			'/user-data',
			array(
				'methods'             => 'GET',
				'callback'            => 'get_current_user_data',
				'permission_callback' => function () {
					return true;
				},
			)
		);
		register_rest_route(
			'ade-woo/v1',
			'/current-user',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					$user_id = get_current_user_id(); // Get the ID of the current logged-in user
					if ( $user_id ) {
						return new WP_REST_Response( array( 'user_id' => $user_id ), 200 );
					} else {
						return new WP_REST_Response( 'User not logged in', 403 );
					}
				},
				'permission_callback' => function () {
					return is_user_logged_in(); // Ensure only logged-in users can access
				},
			)
		);
		register_rest_route(
			'ade-woo/v1',
			'/nonce',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					return wp_create_nonce( 'wp_rest' );
				},
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'ade-woo/v1',
			'/validate-auth-cookie/',
			array(
				'methods'             => 'GET',
				'callback'            => 'mkh_validate_auth_cookie',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'ade-woo/v1',
			'/is-logged-in',
			array(
				'methods'             => 'GET',
				'callback'            => 'mkh_validate_auth_cookie',
				'permission_callback' => '__return_true',
			)
		);
		// endpoints will be registered here
		register_rest_route(
			'ade-woo',
			'/login',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'login' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'ade-woocart/v1',
			'/cart',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cart' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'ade-woocart/v1',
			'/cart',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => '__return_true',
			)
		);

		// remove from cart.
		register_rest_route(
			'ade-woocart/v1',
			'/cart/(?P<key>\w+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_from_cart' ),
				'permission_callback' => '__return_true',
			)
		);
	}
	public function get_current_user_data() {

		$current_user = wp_get_current_user();

		if ( $current_user->ID === 0 ) {
			return new WP_Error( 'no_user', 'User not logged in', array( 'status' => 401 ) );
		}

		return array(
			'ID'           => $current_user->ID,
			'display_name' => $current_user->display_name,
			'email'        => $current_user->user_email,
			'roles'        => $current_user->roles,
		);
	}
	public function mkh_validate_auth_cookie( $request ) {
		// Identify the correct `wordpress_logged_in_` cookie dynamically
		$cookie_name_prefix = 'wordpress_logged_in_';
		$cookie             = null;

		foreach ( $_COOKIE as $key => $value ) {
			if ( strpos( $key, $cookie_name_prefix ) === 0 ) {
				$cookie = $value;
				break;
			}
		}

		// If the cookie is not found, return a logged-out response
		if ( ! $cookie ) {
			return new WP_REST_Response( array( 'isLoggedIn' => false ), 200 );
		}

		// Parse and validate the cookie
		$cookie_data = wp_parse_auth_cookie( $cookie, 'logged_in' );
		if ( ! $cookie_data ) {
			return new WP_REST_Response( array( 'isLoggedIn' => false ), 200 );
		}

		// Check if the user ID is valid
		$user_id = wp_validate_auth_cookie( $cookie, 'logged_in' );
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			// Set the current user context
			wp_set_current_user( $user_id );
			$nonce = wp_create_nonce( 'wp_rest' );
			if ( $user ) {
				return new WP_REST_Response(
					array(
						'isLoggedIn' => true,
						'user'       => array(
							'id'       => $user->ID,
							'username' => $user->user_login,
							'email'    => $user->user_email,
						),
						'nonce'      => $nonce,
					),
					200
				);
			}
		}

		return new WP_REST_Response( array( 'isLoggedIn' => false ), 200 );
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

	public function login( $request ) {
		$data                  = array();
		$data['user_login']    = $request['user_login'];
		$data['user_password'] = $request['user_password'];
		$data['remember']      = true;
		$user                  = wp_signon( $data, false );

		if ( ! is_wp_error( $user ) ) {
			return new WP_REST_Response(
				array(
					'isLoggedIn' => true,
					'user'       => array(
						'id'       => $user->ID,
						'username' => $user->user_login,
						'email'    => $user->user_email,
					),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'isLoggedIn' => false,
					'message'    => $user->get_error_message(),
				),
				401
			);
		}
	}


	/**
	 * Add to cart
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	public function add_to_cart( WP_REST_Request $request ) {
		// Ensure WooCommerce session is started.
		try {
			if ( ! WC()->session ) {
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				WC()->session  = new $session_class();
				WC()->session->init();
			}
		} catch ( Exception $e ) {
			return wp_send_json_error( 'Failed to initialize WooCommerce session: ' . $e->getMessage(), 500 );
		}

		// Ensure the WooCommerce cart is available.
		if ( ! WC()->cart ) {
			WC()->customer = new WC_Customer();
			WC()->cart     = new WC_Cart();
		}

		$products = $request->get_json_params(); // Retrieve JSON body as an array.

		if ( ! is_array( $products ) || empty( $products ) ) {
			return wp_send_json_error( 'Invalid or empty products array', 400 );
		}

		foreach ( $products as $product_data ) {
			$product_id = isset( $product_data['product_id'] ) ? absint( $product_data['product_id'] ) : null;
			$quantity   = isset( $product_data['quantity'] ) ? absint( $product_data['quantity'] ) : 1;
			$attributes = isset( $product_data['attributes'] ) ? $product_data['attributes'] : array();

			// Validate product ID.
			if ( ! $product_id || ! wc_get_product( $product_id ) ) {
				return wp_send_json_error( sprintf( 'Product not found for ID %s', esc_html( $product_id ) ), 404 );
			}

			// Validate attributes for variable products.
			if ( 'product_variation' === get_post_type( $product_id ) && empty( $attributes ) ) {
				return wp_send_json_error( sprintf( 'Attributes are missing for product ID %s', esc_html( $product_id ) ), 400 );
			}

			// Add product to cart.
			WC()->cart->add_to_cart( $product_id, $quantity, 0, $attributes );

			// Handle session cookie for guest users.
			$user = wp_get_current_user();
			if ( 0 === $user->ID && WC()->session ) {
				WC()->session->set_customer_session_cookie( true );
			}
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
