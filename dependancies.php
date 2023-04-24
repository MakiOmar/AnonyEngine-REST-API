<?php

defined( 'ABSPATH' ) || die();

require ANORAPI_DIR . '/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/MakiOmar/AnonyEngine-REST-API/',
    __FILE__,
    ANORAPI_PLUGIN_SLUG
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');

/**
 * Display a notification if one of required plugins is not activated/installed
 */
add_action( 'admin_notices', function() {
	if ( ! is_plugin_active( 'bdvs-password-reset/bdvs-password-reset.php' ) || ! is_plugin_active( 'jwt-auth/jwt-auth.php' ) ) {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Either Password Reset with Code for WordPress REST API or JWT Auth plugins need to be active for this plugin to work properly.', 'anonyengine-rest-api' ); ?></p>
        </div>
        <?php
    }

	// Check if JWT Auth – WordPress JSON Web Token Authentication is active
	/*
	if (!defined('JWT_AUTH_PLUGIN_DIR')) {
	    ?>
	    <div class="notice notice-error is-dismissible">
	        <p><?php _e( 'AnonyEngine rest api plugin requires <a href="https://wordpress.org/plugins/jwt-auth/">JWT Auth</a> plugin to be installed/activated. Please install/activate JWT Auth plugin first.' ); ?></p>
	    </div>
	<?php }*/
});

add_action( 'admin_notices', function () {
    
});