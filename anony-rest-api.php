<?php
/**
 * Plugin Name: AnonyEngine Rest API
 * Plugin URI: https://makiomar.com
 * Description: Custom API endpoints
 * Version: 1.0.0
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

require ANORAPI_DIR . '/plugin-update-checker/plugin-update-checker.php';

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/MakiOmar/AAnonyEngine-REST-API/',
    __FILE__,
    ANORAPI_PLUGIN_SLUG
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

/**
 * Display a notification if one of required plugins is not activated/installed
 */
add_action( 'admin_notices', function() {
    if (!defined('ANOENGINE')) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'AnonyEngine rest api plugin requires AnonyEngine plugin to be installed/activated. Please install/activate AnonyEngine plugin first.' ); ?></p>
        </div>
    <?php }
});
 
 add_filter( 'jwt_auth_whitelist', function ( $endpoints ) {
    $your_endpoints = array(
        '/wp-json/smpg/v2/register',
        '/wp-json/bdpwr/v1/reset-password',
        '/wp-json/bdpwr/v1/set-password',
    );

    return array_unique( array_merge( $endpoints, $your_endpoints ) );
} );