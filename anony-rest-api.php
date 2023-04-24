<?php
/***
 * Plugin Name: AnonyEngine Rest API
 * Plugin URI: https://makiomar.com
 * Description: Custom API endpoints
 * Version: 1.0.03
 *
 * @package  AnonyEngine
 * Author: Mohammad Omar
 * Author URI: https://makiomar.com
 * Text Domain: anonyengine-rest-api
 * License: GPL2
 */
 
 defined( 'ABSPATH' ) || die();

 /**
 * Holds plugin's slug
 *
 * @const
 */
define( 'ANORAPI_PLUGIN_SLUG', plugin_basename(__FILE__) );

/**
 * Holds plugin's path
 *
 * @const
 */
define( 'ANORAPI_DIR', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );

/**
 * Holds plugin's path
 *
 * @const
 */
define( 'ANORAPI_SITE_URL', site_url());
define( 'ANORAPI_CK', '');
define( 'ANORAPI_CS', '');



require_once ANORAPI_DIR . 'functions/helper.php';
require_once ANORAPI_DIR . 'classes/class-ade-woocart.php';
require_once ANORAPI_DIR . 'dependancies.php';
require_once ANORAPI_DIR . 'auth/register.php';

/*-----Product----*/
add_action( 'plugins_loaded', 'anony_woo_rest_api' );

function anony_woo_rest_api() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once ANORAPI_DIR . 'products/get-products.php';
	//require_once ANORAPI_DIR . 'products/add-to-cart.php';
	require_once ANORAPI_DIR . 'woodmart/brand-banner.php';
}


add_filter( 'jwt_auth_whitelist', function ( $endpoints ) {
    $your_endpoints = array(
        '/wp-json/smpg/v2/register',
        '/wp-json/bdpwr/v1/reset-password',//From (Password Reset with Code for WordPress REST API) plugin
        '/wp-json/bdpwr/v1/set-password', // //From (Password Reset with Code for WordPress REST API) plugin
        '/wp-json/ade-woocart/v1/cart',
        '/wp-json/ade-woocart/v1/cart/*',
    );

    return array_unique( array_merge( $endpoints, $your_endpoints ) );
} );