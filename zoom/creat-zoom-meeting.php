<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * General zoom OAuth cridentials
 */
define('CLIENT_ID', $zoom_opts['client-id']);
define('CLIENT_SECRET', $zoom_opts['client-secret'] );
define('REDIRECT_URI', $zoom_opts['oauth-redirect-uri']);