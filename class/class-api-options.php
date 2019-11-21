<?php
class ZKAPI_ApiOptions extends ZKAPI_ACF_Helpers{
    protected $post_type;
    protected $acf_fields;
    function __construct(){
        $this->_post_type = 'option';
        $this->set_default_fields();
        // add_action( 'rest_api_init', array($this, 'register_rest_fields') );
        add_action('rest_api_init', [$this, 'wp_rest_endpoints']);
    }

    public function set_default_fields($fields=false){
        $this->_acf_fields = $this->_get_fields_by('options_page', 'acf-options');
    }

    public function wp_rest_endpoints($request) {
        register_rest_route('wp/v2', 'options', array(
            'methods' => 'GET',
            'callback' => [$this, 'rest_option_endpoint'],
        ));
    } 
    public function rest_option_endpoint(){
        $response = [];
        foreach($this->_acf_fields as $field){
            if(!$field['name']) continue;
            $response[$field['name']] = $this->get_field(null, $field['name']);
        }

        return $response;
    }
}
