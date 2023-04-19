<?php

defined('ABSPATH') || die();

function anony_wc_basic_get($url)
{
    $params = $_GET;
    
    if ( ! empty( $params ) ) {
        
        foreach ( $params as $key => $value ) {
            $params[ $key ] = sanitize_text_field( $value );
        }
        $request_url = add_query_arg( $params, $url );
        
    } else {
        
        $request_url = $url;
    }
    $request = wp_remote_get( $request_url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( ANORAPI_CK . ':' . ANORAPI_CS )
        )
    ) );
    
    $response = wp_remote_retrieve_body( $request );
    
    if ( ! is_wp_error( $response ) ) {
        wp_send_json( json_decode($response) );
	    die();
    } else {
        wp_send_json( ['msg' => 'No results found'] );
	    die();
    } 
}