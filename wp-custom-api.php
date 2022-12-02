<?php
/**
 * Plugin Name: Custom WP API
 * Plugin URI: https://opensource.org/
 * Description: Custom API Endpoint for getting Wordpress Data. Initial endpoint allows other apps to authenticate against WP users. You will have to add custom endpoints to fit your needs.
 * Version: 1.0
 * Author: Chris Hampton
 */

/**
 *   https://awhitepixel.com/blog/in-depth-guide-in-creating-and-fetching-custom-wp-rest-api-endpoints/
 *   WP_REST_Server::READABLE = method ‘GET‘
 *   WP_REST_Server::EDITABLE = methods ‘POST‘, ‘PUT‘, and ‘PATCH‘
 *   WP_REST_Server::DELETABLE = method ‘DELETE‘
 *   WP_REST_Server::ALLMETHODS = all of the above methods
 */


// Global Settings
global $wpdb;

$headers = array_change_key_case(getallheaders(), CASE_UPPER); 
if (isset($headers['WPAUTHX'])) {
  $token = $headers['WPAUTHX'];
}

$key = get_site_option("CUSTOM_API_AUTH_KEY");
$prefix = $wpdb->prefix;
// END Global Settings

// ajax
add_action('wp_ajax_generate_key', 'generateKey');
// end ajax

// Admin Page
//create admin page
add_action( 'admin_menu', 'custom_api_admin_page' );

function custom_api_admin_page() {
    add_menu_page( 'Custom API', 'Custom API', 'manage_options', 'customapi-admin-page', 'customapi_admin_page', 'dashicons-rest-api', 6  );
}

function customapi_admin_page() {
    global $key;
    $authkey = get_site_option("CUSTOM_API_AUTH_KEY");
    
    if(isset($_POST["action"]) && $_POST["action"] == "saveKey" ) {
        $authkey = $_POST['authkey'];
        update_option("CUSTOM_API_AUTH_KEY", $authkey);
    }

    echo "<h2>Custom API Admin</h2>";
    ?>
     <script>
    jQuery(document).ready(function() {
        jQuery(".generate").bind("click", function() {
            jQuery.ajax({
                type:'POST',
                data:{action:'generate_key'},
                dataType: 'text',
                url: "/wp-admin/admin-ajax.php",
                success: function(value) {
                    console.log(value);
                    jQuery('#authkey').val(value);
                }
            });
        })
    });
    </script>
    <form method="post" action="<?php echo admin_url( 'admin.php?page=customapi-admin-page', 'https' );?>" name="settingsForm">
        <label>Auth Key:</label> <input type="text" id="authkey" name="authkey" value="<?php echo $authkey; ?>"  /><br />
        <p style="font-size:10px;"><i class="fa fa-refresh generate"></i> Click to generate key.</p>
        <input type="hidden" name="action" id="action" value="saveKey" />
        <?php submit_button( 'Save Key', 'primary', 'vda-save-settings' ); ?>
    </form>
    <?php
}



// external login
add_action( 'rest_api_init', function () {
    global $key, $token;

    register_rest_route( 'api/v1', '/login/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'doLogin',
        'permission_callback' => authenticateCall(),
        'args'     => [
			'username' => [
				'required' => true,
				'type'     => 'string',
			],
            'password' => [
				'required' => true,
				'type'     => 'string',
			]
		]
    ));
});

function authenticateCall() {
    global $key, $token;

    if( !empty($key) && $key != $token ) {
        return new WP_REST_Response("Back off!", 401);
    }
}


function doLogin(WP_REST_Request $request) {

    $username = $request->get_param( 'username' );
    $password = $request->get_param( 'password' );

    if( !empty($username) && !empty($password) ) {
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        );
        $user = wp_signon( $creds, false );
        
        if ( is_wp_error( $user ) ) {
            $msg = $user->get_error_message();
            return new WP_REST_Response($msg, 401);
        }else{
          wp_clear_auth_cookie();
          wp_set_current_user ( $user->ID ); // Set the current user detail
          wp_set_auth_cookie  ( $user->ID ); // Set auth details in cookie
        }
        
        return new WP_REST_Response($user, 200);
    }
    return new WP_REST_Response("Back off!", 401); 
}
// end external login


// CORS Configuration
function _customize_rest_cors() {
  remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
  add_filter( 'rest_pre_serve_request', function( $value ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Allow-Methods: *' );
        header( 'Access-Control-Allow-Credentials: true' );
        header( 'Access-Control-Allow-Headers: cache-control, expires, content-type, pragma' );
        return $value;
  } );
}

add_action( 'rest_api_init', '_customize_rest_cors', 15 );


// Utility Functions
function get_table_name($name) {
  global $wpdb;

  return $wpdb->prefix . $name;
}

function generateKey($l=32){
    mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
    $charid = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);
        echo str_replace("-","",$uuid);
        exit();
}