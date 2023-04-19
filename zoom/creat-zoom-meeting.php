<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * General zoom OAuth cridentials
 */
define('CLIENT_ID', 'r2lH8nmVQAa_hkYAsPFknw');
define('CLIENT_SECRET', 'tcTv7HrIFn6MH2WSj6osDcvavo0D3A2j' );
define('REDIRECT_URI', 'https://makiomar.com/zoom-auth');


function anony_zoom_outh_callback(){
    

    $request = [
                "headers" => [
                    "Authorization" => "Basic ". base64_encode(CLIENT_ID.':'.CLIENT_SECRET)
                ],
                'form_params' => [
                    "grant_type" => "authorization_code",
                   // "code" => @$_GET['code'],
                    "redirect_uri" => REDIRECT_URI
                ],
            ];
    $respnse = wp_remote_request( 'https://zoom.us',  $request);

    error_log( print_r( $respnse, true ) );
}

anony_zoom_outh_callback();