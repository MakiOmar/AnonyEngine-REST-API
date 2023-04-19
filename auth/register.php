<?php

defined('ABSPATH') || die();

function anony_rest_api_create_user($user_id)
{
	$user = get_user_by('id', $user_id);
	if(!empty($user)){
		$user->remove_role('subscriber');
		$user->remove_role('shop_manager');
		$user->remove_role('administrator');
		$user->add_role('customer');
		wp_set_password( sanitize_text_field($_POST['password']), $user_id );
		wp_send_json( $user );
		die();
	}else{
		wp_send_json( ["error" => 'User is not created' ] );
		die();
	}
}
add_action( 'user_register', 'anony_rest_api_create_user' );