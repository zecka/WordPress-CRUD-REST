<?php

class ZKAPI_Helpers {
/**
 * @var ZKAPI_Helpers
 * @access private
 * @static
 */
    private static $__instance = null;

    /**
     * Constructeur de la classe
     *
     * @param void
     * @return void
     */
    private function __construct() {
    }

    /**
     * Méthode qui crée l'unique instance de la classe
     * si elle n'existe pas encore puis la retourne.
     *
     * @param void
     * @return Singleton
     */
    public static function getInstance() {

        if (is_null(self::$__instance)) {
            self::$__instance = new ZKAPI_Helpers();
        }

        return self::$__instance;
    }

    public static function test() {
        return 'this is a test';
    }

    public static function get_permissions($permission = null) {
        $permissions = [
            'create_user'  => ['subscriber'],
            'show_in_rest' => ['subscriber'],
        ];

        if ($permission) {
            if (isset($permissions[$permission])) {
                return $permissions[$permission];
            } else {
                return __('Permission not exist');
            }
        }
        return $permissions;
    }

    public static function add_relation($post_type, $attach_to, $relation_type){
        $p_factory = ZKAPI_PostTypeFactory::getInstance();
        $p_factory->register_relation($post_type, $attach_to, $relation_type);
    }

    public static function add_post_type($post_type){
        $p_factory = ZKAPI_PostTypeFactory::getInstance();
        $p_factory->register_post_type($post_type);
    }
    public static function get_request_data($request){
        if($request->get_body()){
            $data =$request->get_json_params();
        }else{
            $data = array_merge($request->get_body_params(), $request->get_file_params());
        }
        return $data;
    }
}
