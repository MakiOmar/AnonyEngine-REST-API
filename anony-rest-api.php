<?php
/**
 * Plugin Name: AnonyEngine Rest API
 * Plugin URI: https://makiomar.com
 * Description: Custom API endpoints
 * Version: 1.0.02
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
define( 'ANORAPI_CK', 'ck_a1507fcc2bb65ed5b660523200416b93dd6a710a');
define( 'ANORAPI_CS', 'cs_20e3df4f264755d76e0a7275151eccf58aab8230');


require_once ANORAPI_DIR . 'functions/helper.php';
require_once ANORAPI_DIR . 'dependancies.php';
require_once ANORAPI_DIR . 'auth/register.php';
require_once ANORAPI_DIR . 'products/get-products.php';

if (!defined('JWT_AUTH_PLUGIN_DIR')) return;

add_filter( 'jwt_auth_whitelist', function ( $endpoints ) {
    $your_endpoints = array(
        '/wp-json/smpg/v2/register',
        '/wp-json/bdpwr/v1/reset-password',
        '/wp-json/bdpwr/v1/set-password',
    );

    return array_unique( array_merge( $endpoints, $your_endpoints ) );
} );