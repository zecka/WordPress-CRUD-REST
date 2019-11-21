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
     * @return ZKAPI_Helpers
     */
    public static function getInstance() {

        if (is_null(self::$__instance)) {
            self::$__instance = new ZKAPI_Helpers();
        }

        return self::$__instance;
    }

    /**
     * Get all permission define for zkapi
     *
     * @param string $permission Peromission name
     * @return array
     */
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

    /**
     * Add Relation between two custom post type
     * Note: Acf field will display only on reference
     *
     * @param string $post_type post type reference
     * @param string $attach_to post type attache
     * @param string $relation_type manytoone, onetomany or manytomany
     * @return void
     */
    public static function add_relation($post_type, $attach_to, $relation_type){
        $p_factory = ZKAPI_PostTypeFactory::getInstance();
        $p_factory->register_relation($post_type, $attach_to, $relation_type);
    }

    /**
     * Add post type to zkapi endpoint
     *
     * @param string $post_type
     * @return void
     */
    public static function add_post_type($post_type){
        $p_factory = ZKAPI_PostTypeFactory::getInstance();
        $p_factory->register_post_type($post_type);
    }

    /**
     * Get data parameter from WP_REST_Request object
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public static function get_request_data($request){
        if($request->get_body()){
            $data =$request->get_json_params();
        }else{
            $data = array_merge($request->get_body_params(), $request->get_file_params());
        }
        return $data;
    }
}
