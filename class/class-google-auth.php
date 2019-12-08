<?php
/** Requiere the JWT library. */
use \Firebase\JWT\JWT;

class ZKAPI_GoogleAuth extends ZKAPI_ACF_Helpers {
 
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints(){
        register_rest_route('zkapi/v1', 'users/google-auth', array(
            'methods'  => 'POST',
            'callback' => [$this, 'endpoint_handler'],
        ));

    }

    public function endpoint_handler($request){
        if(!$this->verify_google_integrity($request)){
            return new WP_Error('cant-create', __('Google reject this request', 'zkapi'), array('status' => 401));
        }

        $email = sanitize_text_field($request->get_param('email'));

        if (email_exists($email)) {
            $response =  $this->login_with_google($request);
        }else{
            $response = $this->register_with_google($request);
        }
        return new WP_REST_Response($response, 200);

    }

    public function login_with_google($request, $first_login=false){
        $token = $this->generate_token($request);
        $token['first_login'] = $first_login;
        return $token;

    }
    public function register_with_google($request){

        $parameters = ZKAPI_Helpers::get_request_data($request);
    
        $email = sanitize_text_field($parameters['email']);
        $password = wp_generate_uuid4();


        $user_id = wp_create_user($email, $password, $email);
        if (!is_wp_error($user_id)) {
            // Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
            $role = apply_filters('zkapi_default_role', 'api_reader');
            $user = get_user_by('id', $user_id);
            $user->set_role($role);

            if (isset($parameters['name'])) {
                wp_update_user(array(
                    'ID' => $user_id, 
                    'display_name' => $parameters['name'], 
                    'nickname' => $parameters['name'],
                    'first_name' => $parameters['first_name'],
                    'last_name' => $parameters['last_name'],
                ));
            }
            // Ger User Data (Non-Sensitive, Pass to front end.)
            $success = $this->update_user_additional_fields($user, $parameters, $role);
            if ($success !== true) {
                return $success;
            }
            $this->login_with_google($request, true);
        } else {
            // In this case user_id is not an id but a WP_Error
            return $user_id;
        }

    }

    public function generate_token($request){
        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
        $email      = $request->get_param('email');
        $user = get_user_by_email($email);
        
        /** Valid credentials, the user exists create the according Token */
        $issuedAt = time();
        $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
        $expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);
        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expire,
            'data' => array(
                'user' => array(
                    'id' => $user->data->ID,
                ),
            ),
        );
        /** Let the user modify the token data before the sign. */
        $token = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token, $user), $secret_key);
        /** The token is signed, now create the object with no sensible user data to the client*/
        $data = array(
            'token' => $token,
            'user_email' => $user->data->user_email,
            'user_nicename' => $user->data->user_nicename,
            'user_display_name' => $user->data->display_name,
        );
        /** Let the user modify the data before send it back */
        return apply_filters('jwt_auth_token_before_dispatch', $data, $user);

    }

    public function verify_google_integrity($request){
        // TODO: Check request with google
        return true;
    }
}
new ZKAPI_GoogleAuth();