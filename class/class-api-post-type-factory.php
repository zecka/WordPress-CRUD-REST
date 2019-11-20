<?php
class ZKAPI_PostTypeFactory{
    private static $__instance = null;
    private static $__apis = [];
    private static $__relations = [];
    private static $__post_types = [];

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
     * @return ZKAPI_PostTypeFactory
     */
    public static function getInstance() {

        if (is_null(self::$__instance)) {
            self::$__instance = new ZKAPI_PostTypeFactory();
        }

        return self::$__instance;
    }

    public function render(){
        self::define_apis();
        self::define_relations();
        add_action('acf/init', 'ZKAPI_PostTypeFactory::api_render');
    }
    public static function api_render(){
        $instance = ZKAPI_PostTypeFactory::getInstance();
        foreach ( $instance->get_apis() as $api){
            $api->render();
        }
    }

    private function add($post_type){
        self::$__apis[$post_type] = new ZKAPI_PostType($post_type);
    }

    private function define_apis(){
        foreach(self::$__post_types as $post_type){
            self::add($post_type);
        }
    }
    private function define_relations(){
        $instance = self::getInstance();

        foreach ( $instance->get_apis() as $api){
            foreach($api->get_acf_fields() as $field){
                ob_start();
                echo '<pre>'; echo print_r($field); echo '</pre>';
                $debug = ob_get_clean();
                if($field['type']=='relationship' || $field['type']=='post_object'){
                    if(isset($field['relation_type'])){
                        $instance->register_relation($api->get_post_type(),$field);
                    }
                }
            }
        }
        $instance->create_relations_group();
    }

    public function register_relation($post_type, $field){
        $relation_type = $field['relation_type'];
        if(isset($field['post_type'][0])){
            $attach_to = $field['post_type'][0];
        }
        if(isset($field['taxonomy'][0])){
            $attach_to     = $field['taxonomy'][0];
        }
        self::$__relations[$attach_to][$post_type] = [
            'post_type' => $post_type,
            'attach_to' => $attach_to,
            'relation_type' => $relation_type,
            'field' => $field,
        ];
    }
    private function create_relations_group(){
        return;
        foreach(self::$__post_types as $post_type){
            if(isset(self::$__relations[$post_type])){

                $fields = self::get_relations_fields($post_type);
     
                acf_add_local_field_group(array(
                    'key'                   => 'relation_groups',
                    'title'                 => 'relation',
                    'fields'                => $fields,
                    'location'              => array(
                        array(
                            array(
                                'param'    => 'post_type',
                                'operator' => '==',
                                'value'    => $post_type,
                            ),
                        ),
                    ),
                    'menu_order'            => 0,
                    'active'                => true,
                ));

            }
        }

    }
    public function get_relations_fields($post_type){
        $fields = [];
        foreach(self::$__relations[$post_type] as $relation){

            if('onetomany'===$relation['relation_type']){
                $field_type = 'post_object';
                $field_post_type = $relation['post_type'];
                $new_relation_type = 'manytoone';
            }else{
                $new_relation_type  = ($relation['relation_type']==='manytomany') ? 'manytomany' : 'onetomany';
                $field_type = 'relationship';
                $field_post_type = array(
                    0 => $relation['post_type'],
                );
            }
            $fields[] =  array(
                'key'               => 'rel'.$post_type.$relation['post_type'],
                'label'             => $relation['post_type'],
                'name'              => $relation['post_type'],
                'type'              => $field_type,
                'hide_in_list'      => 0,
                'hide_in_detail'    => 0,
                'relation_type'     => $new_relation_type,
                'post_type'         => $field_post_type,
                'taxonomy'          => '',
                'return_format'     => 'id',
            );
        }
        return $fields;
       
    }
    /**
     * Undocumented function
     *
     * @return array
     */
    public function get_relations(){
        return self::$__relations;
    }

  

    

    /**
     * Undocumented function
     *
     * @return array
     */
    public function get_post_types(){
        return self::$__post_types;
    }
    /**
     * Undocumented function
     *
     * @param string $post_type
     * @return ZKAPI_PostType $api
     */
    public function get($post_type){
        if(isset(self::$__apis[$post_type])):
            return self::$__apis[$post_type];
        else:
            return false;
        endif;
    }
    public function register_post_type($post_type){
        self::$__post_types[$post_type] = $post_type;
    }



    public function get_apis(){
        return self::$__apis;
    }
}
