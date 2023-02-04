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
	if (!defined('ANOENGINE')) {
	    ?>
	    <div class="notice notice-error is-dismissible">
	        <p><?php esc_html_e( 'AnonyEngine rest api plugin requires AnonyEngine plugin to be installed/activated. Please install/activate AnonyEngine plugin first.' ); ?></p>
	    </div>
	<?php }

	// Check if JWT Auth – WordPress JSON Web Token Authentication is active
	if (!defined('JWT_AUTH_PLUGIN_DIR')) {
	    ?>
	    <div class="notice notice-error is-dismissible">
	        <p><?php _e( 'AnonyEngine rest api plugin requires <a href="https://wordpress.org/plugins/jwt-auth/">JWT Auth</a> plugin to be installed/activated. Please install/activate JWT Auth plugin first.' ); ?></p>
	    </div>
	<?php }
});