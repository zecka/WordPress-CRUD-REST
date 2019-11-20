<?php

class ZKAPI_Users extends ZKAPI_ACF_Helpers {
    protected $_acf_fields;
    protected $_post_type = 'user';
    private static $__instance = null;
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        $this->define_acf_fields();
    }
    /**
     * Méthode qui crée l'unique instance de la classe
     * si elle n'existe pas encore puis la retourne.
     *
     * @param void
     * @return ZKAPI_Users
     */
    public static function getInstance() {
        if (is_null(self::$__instance)) {
            self::$__instance = new ZKAPI_Users();
        }
        return self::$__instance;
    }
    private function define_acf_fields(){
        $fields['all'] = $this->_get_fields_by('user_form', 'all');
        foreach(zkapi_get_roles() as $role){
            $fields[$role] = $this->_get_fields_by('user_role', $role);
        }
        $this->_acf_fields = $fields;
    }
    public function get_acf_fields(){
        return $this->_acf_fields;
    }
    public function get_registration_date_field($user_array, $field_name) {
        $udata      = get_userdata($user_array['id']);
        $registered = $udata->user_registered;
        return date("Y-m-d\TH:i:s", strtotime($registered));
    }
    public function register_rest_routes(){
        register_rest_route('zkapi/v1', '/users', array(
            'methods'  => 'GET',
            'callback' => [$this, 'all_users_callback'],
        ));

    }
    public function all_users_callback(){
        $api_users = get_users(['role__in' => zkapi_get_roles()]);
        $response = [];
        foreach($api_users as $user){
            $user_item = new ZKAPI_UserItem($user);
            $response[] = $user_item->api_return();
        }
        return $response;
    }
    

}
