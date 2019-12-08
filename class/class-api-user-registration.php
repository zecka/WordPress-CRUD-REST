<?php
class ZKAPI_UsersRegistration extends ZKAPI_ACF_Helpers {
    protected $_post_type = 'user';
    protected $_acf_fields;
    public function __construct() {
        $this->define_verify_route();
        add_action('rest_api_init', [$this, 'wp_rest_user_endpoints']);

        // Email Validation
        add_action('zkapi_verify_user', [$this, 'user_verfication']);
        add_filter('wp_authenticate_user', [$this, 'wp_authenticate_user'], 10, 2);
        add_action('user_register', [$this, 'my_user_register'], 10, 2);
        add_action('template_redirect', [$this, 'disbale_default_login_redirect']);
    }
    public function wp_rest_user_endpoints() {
        register_rest_route('wp/v2', 'users/register', array(
            'methods'  => 'POST',
            'callback' => [$this, 'rest_user_endpoint_handler_register'],
        ));

        register_rest_route('wp/v2', 'users/availability', array(
            'methods'  => 'POST',
            'callback' => [$this, 'rest_user_endpoint_handler_availability'],
        ));

        register_rest_route('wp/v2', 'users/delete/(?P<id>\d+)', array(
            'methods'  => 'DELETE',
            'callback' => [$this, 'rest_user_endpoint_handler_delete'],
        ));

    }
    public function rest_user_endpoint_handler_availability($data) {
        $error       = new WP_Error();
        $email_exist = $slug_exist = null;
        if (!isset($data['email']) && !isset($data['slug'])) {
            $error->add(401, __("You have to define an email and/or a slug", 'wp-rest-user'), array('status' => 401));
            return $error;
        }
        if (isset($data['email'])) {
            $user = (get_user_by_email($data['email']));
            if ($user) {
                $email_exist = false;
            } else {
                $email_exist = true;
            }
        }
        if (isset($data['slug'])) {
            $user = get_user_by('slug', $data['slug']);
            if ($user) {
                $slug_exist = false;
            } else {
                $slug_exist = true;
            }
        }
        return [
            'code'        => 200,
            'availabilty' => [
                'email' => $email_exist,
                'slug'  => $slug_exist,
            ],
        ];
    }
    public function rest_user_endpoint_handler_delete($data) {
        $error    = new WP_Error();
        $response = array();
        if (!isset($data['id'])) {
            $error->add(400, __("You neeed to send an id", 'wp-rest-user'), array('status' => 400));
            return $error;
        }
        $user_id         = intval($data['id']);
        $user_info       = get_userdata($user_id);
        $this_user_roles = $user_info->roles;

        if (in_array("administrator", $this_user_roles)) {
            $error->add(401, __("You cannot delete this user", 'wp-rest-user'), array('status' => 401));
            return $error;
        } elseif (get_current_user_id() !== $user_id) {
            $error->add(401, __("You cannot delete this user", 'wp-rest-user'), array('status' => 401));
            return $error;
        } else {
            //For wp_delete_user() function
            require_once ABSPATH . 'wp-admin/includes/user.php';

            if (wp_delete_user($user_id)) {
                $response['code']    = 200;
                $response['message'] = __("User '" . $user_info->display_name . "' is deleted", "wp-rest-user");
                return $response;
            } else {
                $error->add(400, __("There is a problem while deleting the user.", 'wp-rest-user'), array('status' => 400));
                return $error;
            }
        }

    }

    public function rest_user_endpoint_handler_register($request = null) {
        $response   = array();
        $parameters = array_merge($_POST, $_FILES);
        $email      = sanitize_text_field($parameters['email']);
        $password   = sanitize_text_field($parameters['password']);
        $role       = sanitize_text_field($parameters['role']);
        $error      = new WP_Error();
        $token      = $parameters['recaptcha'];
        $verif      = zkapi_verfiy_captcha_token($token);
        if (!$verif) {
            $error->add(401, __("Error on captcha validation", 'wp-rest-user'), array('status' => 400));
            return $error;
        }

        if (empty($email)) {
            $error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
            return $error;
        }
        if (empty($password)) {
            $error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
            return $error;
        }

        $default_role = apply_filters('zkapi_default_role', 'api-reader' ); 
        if (empty($role)) {
            $role = $default_role;
        } else {
            $allowable_roles = apply_filters('zkapi_allowable_roles', ['api-reader'] );
            if (!in_array($role, $allowable_roles)) {
                $role = $default_role;
            }
        }
        $user_id = username_exists($email);
        if (!$user_id && email_exists($email) == false) {
            $user_id = wp_create_user($email, $password, $email);
            if (!is_wp_error($user_id)) {
                // Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
                $user = get_user_by('id', $user_id);
                $user->set_role($role);
                if (isset($parameters['name'])) {
                    wp_update_user(array('ID' => $user_id, 'display_name' => $parameters['name'], 'nickname' => $parameters['name']));
                }

                // Ger User Data (Non-Sensitive, Pass to front end.)
                $success = $this->update_user_additional_fields($user, $parameters, $role);
                if ($success !== true) {
                    return $success;
                }
                $response['code']    = 200;
                $response['message'] = __("User '" . $user->display_name . "' Registration was Successful", "wp-rest-user");
            } else {
                // In this case user_id is not an id but a WP_Error
                return $user_id;
            }
        } else {
            if (email_exists($email)) {
                $error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
            }
            if (username_exists($email)) {
                $error->add(406, __("Username already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
            }
            return $error;
        }
        return new WP_REST_Response($response, 123);
    }
    


    public function wp_authenticate_user($userdata) {
        $has_activation_status = get_field('is_activated', 'user_' . $userdata->ID);
        if ($has_activation_status !== null) {
            $isActivated = get_field('is_activated', 'user_' . $userdata->ID);
            if (!$isActivated) {
                $this->my_user_register($userdata->ID);
                // resends the activation mail if the account is not activated
                $userdata = new WP_Error(
                    'zkapi_confirmation_error',
                    __('<strong>Error:</strong> Your account has to be activated before you can login. Please click the link in the activation email that has been sent to you.<br /> If you do not receive the activation email within a few minutes, check your spam folder or <a class="pLogin_errorLink" href="' . home_url() . '/verify/?u=' . $userdata->ID . '">click here to resend it</a>.')
                );
            }
        }
        return $userdata;
    }

    public function my_user_register($user_id) { // when a user registers, sends them an email to verify their account
        $user_info = get_userdata($user_id); // gets user data
        $code      = md5(time()); // creates md5 code to verify later
        $string    = array('id' => $user_id, 'code' => $code); // makes it into a code to send it to user via email

        // creates activation code and activation status in the database
        update_user_meta($user_id, 'activationcode', $code);
        update_field('is_activated', false, 'user_' . $user_id);

        $url  = get_site_url() . '/verify/?p=' . base64_encode(serialize($string)); // creates the activation url
        $html = ('Please click <a href="' . $url . '">here</a> to verify your email address and complete the registration process.'); // This is the html template for your email message body

        $mail          = new ZKAPI_Mail();
        $mail->to      = $user_info->user_email;
        $mail->subject = __('Activate your Account');
        $mail->content = $html;
        $mail->send(); // sends the email to the user
    }

    public function user_verfication() {

        if (isset($_GET['p'])) {
            // If accessed via an authentification link
            $data        = unserialize(base64_decode($_GET['p']));
            $code        = get_user_meta($data['id'], 'activationcode', true);
            $isActivated = get_field('is_activated', 'user_' . $data['id']); // checks if the account has already been activated. We're doing this to prevent someone from logging in with an outdated confirmation link
            if ($isActivated) { // generates an error message if the account was already active
                // wp_die('isactivated');
                wp_redirect(home_url() . '/login?user_verify=1');
                exit();
            } else {
                if ($code == $data['code']) { // checks whether the decoded code given is the same as the one in the data base

                    get_field('is_activated', 'user_' . $data['id'], true); // updates the database upon successful activation

                    $user_id = $data['id']; // logs the user in
                    $user    = get_user_by('id', $user_id);

                    if ($user) {
                        update_field('is_activated', true, 'user_' . $user_id);
                        wp_redirect(home_url() . '/login?user_verify=1');
                        exit();

                    } else {
                        wp_redirect(home_url() . '/login?user_verify=0');
                        exit();

                    }
                } else {
                    wp_redirect(home_url() . '/login?user_verify=0');
                    exit();
                }
            }
        } elseif (isset($_GET['u'])) {
            // If resending confirmation mail
            $this->my_user_register($_GET['u']);
            wp_redirect(home_url() . '/login?verify_resend=1');
            exit();
        } else {
            wp_redirect(home_url());
            exit();
        }

    }

    public function define_verify_route() {
        add_filter('generate_rewrite_rules', function ($wp_rewrite) {
            $wp_rewrite->rules = array_merge(
                ['verify/?$' => 'index.php?verify=1'],
                $wp_rewrite->rules
            );
        });
        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'verify';
            return $query_vars;
        });
        add_action('template_redirect', function () {
            $custom = intval(get_query_var('verify'));
            if ($custom) {
                include get_template_directory() . '/templates/user-verify.php';
                die;
            }
        });
    }
    public function disbale_default_login_redirect() {
        $requ = untrailingslashit($_SERVER['REQUEST_URI']);
        if (site_url('login', 'relative') === untrailingslashit($_SERVER['REQUEST_URI'])) {
            remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
        }
    }
}
