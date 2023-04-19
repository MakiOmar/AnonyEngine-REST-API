<?php

defined( 'ABSPATH' ) || die();

// Register REST API endpoints
class Login_REST_API_Endpoints {

    /**
     * Register the routes for the objects of the controller.
     */
    public static function register_endpoints() {
        // endpoints will be registered here
        register_rest_route( 'wp', '/login', array(
            'methods'             => 'POST',
            'callback'            => array( 'Login_REST_API_Endpoints', 'login' ),
            'permission_callback' => array( 'Login_REST_API_Endpoints', 'authinticate' ),
        ) );
    }
    
    public static function authinticate($request)
    {
        //error_log( print_r($request->get_headers(), true) );
        
        return true; // Set to false to prevent access to this end point
    }
    /**
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public static function login( $request ) {


        $data = array();
        $data['user_login'] = $request["user_login"];
        $data['user_password'] =  $request["user_password"];
        $data['remember'] = true;
        $user = wp_signon( $data, false );

        if ( !is_wp_error($user) )
        {
			//set_transient( 'app_logged_in_' . $user->ID  );
            return $user;
        }else{
            return ["message" => $user->get_error_message()];
        }
          
    }

}
add_action( 'rest_api_init', array( 'Login_REST_API_Endpoints', 'register_endpoints' ) );




function wp_oauth_server_logout() {
	wp_logout();
}

add_action( 'rest_api_init', function () {
    /**
     * Head to https://wp-oauth.com for more info on user authentication and custom login and out solutions
     * for WordPress
     */
    register_rest_route( 'wp', '/logout/', array(
		'methods'             => 'GET',
		'callback'            => 'wp_oauth_server_logout'
	) );
	
	register_rest_route( 'wp', '/nonce/', array(
		'methods'             => 'POST',
		'callback'            => 'wp_ajax_nonce',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'wp', '/lmsnonce/', array(
		'methods'             => 'POST',
		'callback'            => 'wp_ajax_lmsnonce',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'wp', '/check_nonce/', array(
		'methods'             => 'POST',
		'callback'            => 'wp_check_nonce',
		'permission_callback' => '__return_true'
	) );
	register_rest_route( 'ldlms', '/v2/sfwd-courses/classified', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_classified',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/table', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_table',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/ask', array(
		'methods'             => 'POST',
		'callback'            => 'sfwd_courses_ask',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/answers', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_answers',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/materials', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_materials',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/user-access', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_user_access',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/online', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_online',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/save-student-online', array(
		'methods'             => 'POST',
		'callback'            => 'sfwd_courses_save_student_online',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/stage-instructors', array(
		'methods'             => 'GET',
		'callback'            => 'sfwd_courses_stage_instructors',
		'permission_callback' => '__return_true'
	) );
	
	register_rest_route( 'ldlms', '/v2/sfwd-courses/upload-receit', array(
		'methods'             => 'POST',
		'callback'            => 'rest_api_attach_file',
		'permission_callback' => '__return_true'
	) );
	//'wp-json/ldlms/v2/account/me/delete'
    register_rest_route('ldlms', '/v2/account/me/delete', array(
        'methods' => 'DELETE',
        'callback' => 'anony_delete_my_account',
    ));
    //'wp-json/ldlms/v2/payment-gateways'
    register_rest_route('ldlms', '/v2/payment-gateways', array(
        'methods' => 'GET',
        'callback' => 'anony_payment_gateways',
    ));
    
    register_rest_route('jwt-auth', '/v1/token/logout', array(
        'methods' => 'POST',
        'callback' => 'anony_token_logout',
    ));
	
	// Rest Fields
	register_rest_field(
		'sfwd-courses',
		'featured-image-url',
		array(
			'get_callback'    => function ( $object ) {
				$attachment_url = get_the_post_thumbnail_url($object['id']);
				return $attachment_url;
			}
		)
	);
	register_rest_field(
		'sfwd-courses',
		'lessons-list',
		array(
			'get_callback'    => function ( $object ) {
				
				//return json_decode(file_get_contents( site_url('/wp-json/ldlms/v2/sfwd-lessons/?course=338') ));
				$lessons = learndash_get_course_lessons_list($object['id']);
				$courses_access_from = ld_course_access_from( $object['id'], get_current_user_id() );
				$lessons_list = array();
				if( !empty( $lessons ) && is_array($lessons) )
				{
				    foreach( $lessons as $sno => $arr ){
				        $obj = $arr['post'];
				        $_sfwd_lessons = get_post_meta($obj->ID, '_sfwd-lessons', true);
				        $temp[$sno]['ID'] = $obj->ID;
				        $temp[$sno]['post_title'] = $obj->post_title;
				        $temp[$sno]['post_content'] = $obj->post_content;
				        $temp[$sno]['post_content'] = $obj->post_content;
				        $temp[$sno]['post_name'] = $obj->post_name;
				        $temp[$sno]['status'] = $arr['status'];
				        $temp[$sno]['sample'] = $arr['sample'];
				        $temp[$sno]['lesson_access_from'] = $arr['lesson_access_from'];
				        $temp[$sno]['video-type'] = get_post_meta( $obj->ID, 'video-type', true );
				        $temp[$sno]['video-url'] = get_post_meta( $obj->ID, 'video-url', true );
				        $temp[$sno]['zoom-meeting-password'] = get_post_meta( $obj->ID, 'zoom-meeting-password', true );
				        $temp[$sno]['zoom-meeting-start-time'] = get_post_meta( $obj->ID, 'zoom-meeting-start-time', true );
				        $temp[$sno]['downloadable-pdfs'] = get_post_meta( $obj->ID, 'downloadable-pdfs', true );
						if( 'vdo-cipher' === $temp[$sno]['video-type'])
						{
							$temp[$sno]['vdocipher-id'] = get_post_meta( $obj->ID, 'vdocipher-id', true );
						}else{
							$temp[$sno]['vdocipher-id'] = '';
						}
						$temp[$sno]['vdocipher-json'] = get_option( 'vdo_annotate_code' );
						$available_for = get_post_meta( $obj->ID, 'lesson-available-for', true );
						if( !$available_for || empty( $available_for ) )
						{
							$available_for = 5;
						}
				        if( $_sfwd_lessons && !empty( $_sfwd_lessons ) && is_array( $_sfwd_lessons ) )
				        {
				            $temp[$sno]['visible_type']  = !empty($_sfwd_lessons['sfwd-lessons_lesson_schedule']) ? $_sfwd_lessons['sfwd-lessons_lesson_schedule'] : '';
				            $temp[$sno]['visible_after'] = !empty($_sfwd_lessons['sfwd-lessons_visible_after']) ? $_sfwd_lessons['sfwd-lessons_visible_after'] : '';
				            
				            $temp[$sno]['status']        = 'closed';
				            
				            if( $temp[$sno]['visible_type'] === 'visible_after' )
				            {
				                $open_at   = $courses_access_from + ( $temp[$sno]['visible_after'] * 24 * 60 * 60 );
				                $close_at  = $open_at + ( $available_for * 24 * 60 * 60 );
				                $temp[$sno]['open-at']  = $open_at  ;
				                $temp[$sno]['close-at'] = $close_at  ;
				                
				                if( time() >= $open_at && time() <= $close_at )
				                {
				                    $temp[$sno]['status'] = 'opened'  ;
				                }
				                
				            }elseif($temp[$sno]['visible_type'] === ''){
								$temp[$sno]['status'] = 'opened'  ;
							}
				            
				        }
				        
				        $temp[$sno]['is_sample'] = learndash_is_sample($obj);
						if( $temp[$sno]['is_sample'] ){
							$temp[$sno]['status'] = 'opened'  ;
						}
				        $temp[$sno]['featured-image-url'] = get_the_post_thumbnail_url($obj->ID);
				        $temp[$sno]['learndash_course_grid_duration_hour'] = get_post_meta($obj->ID, '_learndash_course_grid_duration', true);
				    }
				    $lessons_list[] = $temp;
				}
				
				return $lessons_list;

			}
		)
	);
	
	register_rest_field(
		'sfwd-courses',
		'current-user-access',
		array(
			'get_callback'    => function ( $object ) {
				$access = array();
				$course_access_upto = ld_course_access_expires_on( $object['id'], get_current_user_id() );
				$courses_access_from = ld_course_access_from( $object['id'], get_current_user_id() );
				$access['has_access'] = sfwd_lms_has_access($object['id'], get_current_user_id());
				$access['access-from'] = $courses_access_from;
				$access['access-up-to'] = $course_access_upto;

				if ( !empty( $course_access_upto ) && $course_access_upto != 0) {
				    if($course_access_upto > time())
				    {
				        $access['days-left'] = ceil(($course_access_upto - time())  / 86400 )  ;
				    }else{
				        $access['days-left'] = 'expired'  ;
				    }
					
				} else {
					$access['days-left'] = '';
				}
				return $access;
			}
		)
	);
	
	register_rest_field(
		'sfwd-courses',
		'lessons-count',
		array(
			'get_callback'    => function ( $object ) {
				
				return count(learndash_get_course_lessons_list($object['id']));
			}
		)
	);

	/**Lessons**/
	register_rest_field(
		'sfwd-lessons',
		'learndash_course_grid_duration_hour',
		array(
			'get_callback'    => function ( $object ) {
				
				return get_post_meta($object['id'], '_learndash_course_grid_duration', true);
			}
		)
	);
	
	register_rest_field(
		'sfwd-lessons',
		'vdocipher-json',
		array(
			'get_callback'    => function ( $object ) {
				
				return get_option( 'vdo_annotate_code' );
			}
		)
	);
	
	register_rest_field(
		'sfwd-lessons',
		'featured-image-url',
		array(
			'get_callback'    => function ( $object ) {
				$attachment_url = get_the_post_thumbnail_url($object['id']);
				return $attachment_url;
			}
		)
	);
} );

function anony_token_logout($request)
{
    $user = wp_get_current_user();

    if( $user )
    {
        delete_transient('user_' . $user->ID . '_token');
        
        return( array( 'message' => 'You are logged out successfully', 'logged_out' => true ) );
    }
    
    return( array( 'message' => 'You are not logged out successfully', 'logged_out' => false ) );
}
function anony_payment_gateways()
{
    return get_option('payment-accounts_accounts-list');
}
function anony_delete_my_account()
{
    $user = wp_get_current_user();
    
    if ($user) {
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user->ID);
        return new WP_REST_Response([
            'message' => 'User deleted successfully',
        ], 200);
    } else {
        return new WP_REST_Response([
            'message' => ' User not found',
        ], 400);
    }
}
function rest_api_attach_file(){
    if( ! isset( $_FILES ) || empty( $_FILES ) || ! isset( $_FILES['rest_api_upload_file'] ) )
        return;
	include_once( ABSPATH . 'wp-admin/includes/admin.php' );
    $image = array();
    /**
     * now we can actually use media_handle_sideload
     * we pass it the file array of the file to handle
     * and the post id of the post to attach it to
     * $post_id can be set to '0' to not attach it to any particular post
     */
    $post_id = '0';
	
    $attachmentId = media_handle_sideload($_FILES['rest_api_upload_file'], $post_id);

    if ( is_wp_error($attachmentId) ) {
        @unlink($_FILES['tmp_name']);
        wp_send_json( $attachmentId->get_error_messages( ) ) ;
        die();
    } else {
        $image = array( 'ID' => $attachmentId,'url' => wp_get_attachment_url( $attachmentId ) );
    }

    return $image;
}

function rest_api_upload_file(){
    if( ! isset( $_FILES ) || empty( $_FILES ) || ! isset( $_FILES['rest_api_upload_file'] ) )
        return;

    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    $uploadedfile = $_FILES['rest_api_upload_file'];

    $upload_overrides = array(
        'test_form' => false
    );

    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

    if ( $movefile && ! isset( $movefile['error'] ) ) {
        wp_send_json( __( 'File is valid, and was successfully uploaded.', 'textdomain' ) . "\n" ) ;
        die();

    } else {
        /*
         * Error generated by _wp_handle_upload()
         * @see _wp_handle_upload() in wp-admin/includes/file.php
         */
        wp_send_json( $movefile['error'] ) ;
        die();
    }
}
function sfwd_courses_stage_instructors(){
    rest_required_data( array( 'stage_id' ) );
    $times = array();
    $query = new WP_Query(array(
        
        'post_type'   => 'zoom-instructors',
        'post_status' => 'publish',
        'tax_query'   => array(
            
                array(
                
                    'taxonomy' => 'instructor_stage',
                    'terms'    => array( $_REQUEST['stage_id'] )
                
                )
            )
        
        ));
    
    if( $query->have_posts() ){
        while( $query->have_posts() ){
            $query->the_post();
            $times[get_the_ID()]['instructor_name'] = get_the_title(get_the_ID()); 
            $times[get_the_ID()]['available_times'] = get_post_meta(get_the_ID(), 'available-times', true);
        }
        wp_send_json( $times );
        die();
    }else{
        wp_send_json( ["message" => 'No instructors available'] );
        die();
    }
}
function save_student_online(){
    rest_required_data( array( 'course_id', 'user_id', 'instructor_id', 'times' ) );
}
function sfwd_courses_online(){
    rest_required_data( array( 'course_id' ) );
    
    $instructors = get_post_meta( $_GET['course_id'], 'instructor', true );
    $times = array();
    if( $instructors && !empty( $instructors ) ){
        foreach( $instructors as $id ){
            $times[$id]['instructor_name'] = get_the_title($id); 
            $times[$id]['available_times'] = get_post_meta($id, 'available-times', true);
        }
    }
    
    if( empty($times) ){
        wp_send_json( ["message" => 'No online classes available'] );
        die();
    }
    wp_send_json( $times );
	die();
}

function sfwd_courses_user_access(){
	rest_required_data( array( 'course_id', 'user_id' ) );
	wp_send_json( sfwd_lms_has_access($_GET['course_id'], $_GET['user_id']) );
	die();
}

function rest_required_data( $required_fields ){
    $errors = array();
    foreach( $required_fields as $field ){
        if( empty( $_REQUEST[$field] ) ){
            $errors[$field] = $field . ' is missing';
        }
    }
    
    if( !empty($errors) ){
        wp_send_json( $errors );
		die();
    }
}

function sfwd_courses_materials(){
    rest_required_data( array( 'course_id' ) );
    
    $course_meta = get_post_meta( $_GET['course_id'], '_sfwd-courses', true );
    
    if( empty( $course_meta ) || !$course_meta ){
        wp_send_json( ["error" => 'No data found for this course id' ] );
		die();
    }
    
    wp_send_json( ["materials" => $course_meta['sfwd-courses_course_materials'] ] );
    die();
}

function sfwd_courses_answers(){
    rest_required_data( array( 'course_id', 'user_id' ) );
    
    $comments = get_comments(
        array(
            'post_id'            => wp_strip_all_tags($_GET['course_id']),
            'number'             => '',
            'hierarchical'       => 'flat',
            'author__in'    => array(intval(wp_strip_all_tags($_GET['user_id'])), 1 ),
            'status'             => 'approve',
            
        )
    );
    
    wp_send_json( $comments );
	die();
}
function sfwd_courses_ask(){
    rest_required_data( array( 'course_id', 'user_id' ) );
		
	$user_info = get_userdata(wp_strip_all_tags($_POST['user_id']));
    $user_name = $user_info->display_name;
    $user_email = $user_info->user_email; 
    
	$agent = $_SERVER['HTTP_USER_AGENT'];

	$data = array(
		'comment_post_ID'      => wp_strip_all_tags($_POST['course_id']),
		'comment_parent'       => 0,
		'user_id'              => wp_strip_all_tags($_POST['user_id']),
		'comment_author'       => wp_strip_all_tags($user_name),
		'comment_author_email' => wp_strip_all_tags($user_email),
		'comment_author_url'   => '',
		'comment_content'      => $_POST['content'],
		'comment_author_IP'    => $_SERVER['REMOTE_ADDR'],
		'comment_agent'        => $agent,
		'comment_date'         => wp_date('Y-m-d H:i:s'),
		'comment_date_gmt'     => wp_date('Y-m-d H:i:s'),
		'comment_approved'     => 1,
	);
    
	$comment_id = wp_insert_comment($data);
	
	wp_send_json( $comment_id );
		die();
}
function sfwd_courses_table(){
	$all_options = get_option( 'class-table', array() );
	wp_send_json( $all_options );
		die();
}
function sfwd_courses_classified(){

	if( empty($_GET['classified_as']) ){
		wp_send_json( ["message" => 'No classification has been set' ] );
		die();
	}
	
	if( empty($_GET['user']) ){
		wp_send_json( ["message" => 'No user ID has been set' ] );
		die();
	}
	$query = new WP_Query(array(
		'post_type'   => 'sfwd-courses',
		'post_status' => 'publish',
		'nopaging' => true,
		'meta_key' => 'classified-as',
		'meta_value' => wp_strip_all_tags($_GET['classified_as']),
	));
	$posts = $query->get_posts();   // $posts contains the post objects
    if( empty( $posts ) ){
		wp_send_json( ["message" => 'No courses found' ] );
		die();
		
	}
    $output = array();
    foreach( $posts as $post ) {    // Pluck the id and title attributes
		$has_access = false;
        if(sfwd_lms_has_access($post->ID, $_GET['user']) ) { 
			$has_access = true;
		}
		
        $output[] = array( 
			'id' => $post->ID, 
			'title' => $post->post_title, 
			'has_access' => $has_access,
			'thumb_url' => get_the_post_thumbnail_url($post),
			'excerpt' => get_the_excerpt($post->ID),
			'lessons_count' => count( learndash_get_course_lessons_list($post->ID) )
		);
    }
    wp_send_json( $output ); // getting data in json format.
	die();
}
function wp_ajax_nonce() {
	return ["nonce" => wp_create_nonce( 'test_nonce-' . get_current_user_id() ), "nonce_name" => 'test_nonce-' . get_current_user_id()];
}

function wp_ajax_lmsnonce() {
    if( empty( $_POST['user_id'] ) ){
        return ["message" => 'User ID is missing' ];
    }
    $user_id = $_POST['user_id'];
    $id = $_POST['quizId'];
    $quiz_post_id = $_POST['quiz_post_id'];
    
    $nonce_key = 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id;
	return ["nonce" => wp_create_nonce( $nonce_key )];
}

function wp_check_nonce() {
	return ["nonce" => wp_verify_nonce( $_POST['nonce'], 'test_nonce-' . get_current_user_id()) ];
}
add_filter('jwt_auth_token_before_sign', function($token, $user) { 
    $token['iss'] = site_url(); 
    return $token;
    
}, 10, 2);
add_action( 'deleted_user', function($id){
    if( function_exists( 'learndash_delete_user_data' ) )
    {
        learndash_delete_user_data($id);
    }
} );

add_filter( 'jwt_auth_token_before_dispatch', function( $data, $user ){
    
    $_data['ID']         = $user->ID;
    $_data['first_name'] = $user->first_name;
    $_data['last_name']  = $user->last_name;
	$_data['nonce']      = wp_create_nonce( 'woocommerce-cart' );

    return array_merge( $_data, $data );
}, 10, 2 );

// Fix JWT bad iss
add_filter( 'home_url', function($uri){
    if (! strpos( $_SERVER['REQUEST_URI'], 'wp-json')){
      return $uri;  
    }
    return site_url();
    
    
});

add_filter( 'jwt_auth_token_before_dispatch', function( $data, $user ){
	
    $token_exists = get_transient( 'user_' . $user->ID . '_token' );
    if( !$token_exists )
    {
        set_transient( 'user_' . $user->ID . '_token', $data['token'] );
    }else{
		$validate = wp_remote_post(site_url('/wp-json/jwt-auth/v1/token/validate'), array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token_exists,
			)
		));

		$body = json_decode(wp_remote_retrieve_body($validate));
		if($body->code !== 'jwt_auth_valid_token'){
			delete_transient('user_' . $user->ID . '_token');
		}else{
			return new WP_Error(
				'jwt_auth_already_logged_in',
				__( 'You are already logged in another device, we only support one device login. Please logout other devices and try again', 'wp-api-jwt-auth' ),
				[
					'status' => 403,
				]
			);
		}
        
    }
    return $data;
}, 10, 2 );



add_filter('deprecated_function_trigger_error', '__return_false');
add_filter('deprecated_argument_trigger_error', '__return_false');
add_filter('deprecated_file_trigger_error',     '__return_false');
add_filter( 'deprecated_hook_trigger_error',    '__return_false');

add_filter( 'jwt_auth_expire', function($expiry, $issuedAt){
	// Create a new DateTime object with the current time
	$current_time = new DateTime();

	// Set the time zone to Cairo
	$current_time->setTimezone(new DateTimeZone('Africa/Cairo'));

	// Get the current timestamp in Cairo time
	$current_timestamp = $current_time->getTimestamp();
	if( $_POST['username'] == 'prosentra'){
		return $current_timestamp + (60*1);
	}
	return $current_timestamp + (60*60*24*10*365);
}, 10, 2 );
