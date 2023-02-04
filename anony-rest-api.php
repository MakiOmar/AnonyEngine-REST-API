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


require_once ANORAPI_DIR . 'dependancies.php';

if (!defined('JWT_AUTH_PLUGIN_DIR')) return;

add_filter( 'jwt_auth_whitelist', function ( $endpoints ) {
    $your_endpoints = array(
        '/wp-json/smpg/v2/register',
        '/wp-json/bdpwr/v1/reset-password',
        '/wp-json/bdpwr/v1/set-password',
    );

    return array_unique( array_merge( $endpoints, $your_endpoints ) );
} );