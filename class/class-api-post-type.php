<?php
// https://github.com/times/acf-to-wp-api/blob/master/acf-to-wp-api.php
// https://netmidas.com/blog/wordpress-rest-api-crud-example-with-a-post/
// https://stackoverflow.com/questions/50342677/update-callback-not-calling-proper-function-when-sending-post
class ZKAPI_PostType extends ZKAPI_ACF_Helpers {
    protected $_post_type;
    protected $_acf_fields;
    protected $_relations=[];

    public function __construct($name) {
        $this->_post_type = $name;
        $this->__set_default_fields();
    }
    public function render(){
        $this->define_relations();
        $this->__set_default_fields();
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    /**
     * Define post type relation based on acf relationship and post_object fields
     *
     * @return void
     */
    public function define_relations(){
        $p_factory = ZKAPI_PostTypeFactory::getInstance();
        foreach ($p_factory->get_relations() as $relations) {
            foreach($relations as $relation){
                if($relation['attach_to'] == $this->_post_type){
                    $this->_relations[] = $relation;
                }
            }
        }
    }

    /**
     * Get post type relations
     *
     * @return array
     */
    public function get_relations(){
        return $this->_relations;
    }
    /**
     * Get acf fields array attached to this post type
     *
     * @return array
     */
    public function get_acf_fields(){
        return $this->_acf_fields;
    }
    /**
     * Get current post type
     *
     * @return string $post_type
     */
    public function get_post_type(){
        return $this->_post_type;
    }
    /**
     * Set acf fields attached to this post type
     *
     * @return void
     */
    private function __set_default_fields() {
        $this->_acf_fields = $this->_get_fields_by('post_type', $this->_post_type);
    }

    

    public function register_rest_routes() {
        register_rest_route('zkapi/v1', '/' . $this->_post_type, array(
            'methods'  => 'GET',
            'callback' => [$this, 'get_archive_callback'],
        ));
        register_rest_route('zkapi/v1', '/' . $this->_post_type, array(
            'methods'  => 'POST',
            'callback' => [$this, 'create_item'],
        ));
        register_rest_route('zkapi/v1', '/' . $this->_post_type . '/(?P<slug>\S+)', array(
            'methods'  => 'GET',
            'callback' => [$this, 'get_single_callback'],
        ));
        register_rest_route('zkapi/v1', '/' . $this->_post_type . '/(?P<post_slug>\S+)', array(
            'methods'  => 'PUT',
            'callback' => [$this, 'update_item'],
        ));
        register_rest_route('zkapi/v1', '/' . $this->_post_type . '/(?P<post_slug>\S+)', array(
            'methods'  => 'DELETE',
            'callback' => [$this, 'delete_item'],
        ));
    }

    public function get_archive_callback() {
        // https://gist.github.com/luetkemj/2023628
        $args = [
            'post_type'      => $this->_post_type,
            'posts_per_page' => 10,
            'paged'          => 1,
        ];

        // Apply filter on query for all post types
        $args = apply_filters('zkapi_query_args_archive', $args);
        // Apply filter on query for specific post type
        $args = apply_filters('zkapi_query_args_archive_'.$this->_post_type, $args);

        $the_query = new WP_Query($args);
        $response  = [];
        if ($the_query->have_posts()):
            while ($the_query->have_posts()): $the_query->the_post();
                global $post;
                $zkapi_post_type = new ZKAPI_PostTypeItem($post, $this, false);
                $response[] = $zkapi_post_type->api_return();
            endwhile;
        endif;

        return $response;
    }
    public function get_single_callback($data) {
        global $post;
        $post = $this->get_post_by_slug($data['slug']);
        if(!$post){
            return new WP_Error('no-item-found', __('No item fund', 'text-domain'), array('status' => 500));
        }
        $zkapi_post_type = new ZKAPI_PostTypeItem($post, $this, true);
        return $zkapi_post_type->api_return();
    }

 
    public function create_item($request) {

        $data = ZKAPI_Helpers::get_request_data($request);

        $allow = apply_filters('zkapi_allow_request_create', true, $data);
        $allow = apply_filters('zkapi_allow_request_create_' . $this->_post_type, $allow, $data);
        if ($allow !== true) {
            return $allow;
        }

        $data = apply_filters('zkapi_prepare_data_before_create', $data);
        $data = apply_filters('zkapi_prepare_data_before_create_'.$this->_post_type, $data);

        if(! isset($data['title'])){
            return new WP_Error('cant-create', __('Title is required', 'text-domain'), array('status' => 500));
        }
        $args = array(
            'post_type'     => $this->_post_type,
            'post_title'    => wp_strip_all_tags($data['title']),
            'post_status'   => 'publish',
            'post_author'   => 1,
        );        
        $new_post_id = wp_insert_post($args);
        $post = get_post($new_post_id);
        $this->update_acf_fields($post, $data);

        // Prepare Response
        $response = $this->get_post_rest_response_by_id($new_post_id);
 

        // Send response with post data
        return new WP_REST_Response($response, 200);
    }

    public function update_item($request){
        $slug = $request['post_slug'];
        $data = $this->get_request_data($request);
        $post = $this->get_post_by_slug($slug);
        if(!$post){
            return new WP_Error('no-item-found', __('No item fund', 'text-domain'), array('status' => 500));
        }
        $my_post = array('ID' => $post->ID,);
        if(isset($data['title'])){
            $my_post['post_title'] = wp_strip_all_tags($data['title']);
        }
        if(isset($data['content'])){
            $my_post['post_content'] = wp_strip_all_tags($data['content']);
        }
        if(isset($data['slug'])){
            $my_post['post_name'] = wp_strip_all_tags($data['slug']);
        }
        wp_update_post( $my_post );

        $post = get_post($post->ID);
        $this->update_acf_fields($post, $data);

        return $this->get_post_rest_response_by_id($post->ID);;
    }
    public function delete_item($request){
        $slug = $request['post_slug'];
        $post = $this->get_post_by_slug($slug);
        // Todo check if current user is owner of post
        $success = wp_delete_post($post->ID);
        if($success){
            return wp_send_json_success();
        }else{
            return wp_send_json_error();
        }

    }
    public function update_acf_fields($post, $data){
        // UPDATE ACF FIELDS
        foreach ($this->_acf_fields as $acf_field) {
            $name = $acf_field['name'];
            if (isset($data[$name])) {
                $this->update_field($data[$name], $post, $name);
            }
        }
        $this->update_relations($post, $data);
    }
    public function update_relations($post, $data){

    }
    public function get_post_by_slug($slug){
        $args = [
            'post_type'     => $this->_post_type,
            'post_name__in' => [$slug],
        ];
        $posts = get_posts($args);

        if (!isset($posts[0])) {
            return false;
        }
        return $posts[0];
    }
    public function get_post_rest_response_by_id($id){
        $post = get_post($id);

        $zkapi_post_type = new ZKAPI_PostTypeItem($post, $this, true);
        return $zkapi_post_type->api_return();
    }
}
