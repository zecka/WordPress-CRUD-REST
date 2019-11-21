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
        /*
        if ($name == 'page') {
            add_action('rest_api_init', array($this, 'register_template_fields'));
            add_filter("rest_prepare_page", [$this, 'rest_pre_insert_page'], 10, 3);
        }
        add_action('rest_insert_' . $this->_post_type, array($this, 'update_files_field'), 10, 3);
        add_filter('rest_work_query', [$this, 'filter_query'], 10, 2);
        */
    }
    public function render(){
        $this->define_relations();
        $this->__set_default_fields();

        add_action('rest_api_init', array($this, 'register_rest_fields'));
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
    public function get_acf_fields(){
        return $this->_acf_fields;
    }
    public function get_post_type(){
        return $this->_post_type;
    }
    public function register_fields($fields = []) {
        $this->fields = array_merge($this->_acf_fields, $fields);
    }
    /**
     * Set acf fields attached to this post type
     *
     * @return void
     */
    private function __set_default_fields() {
        $this->_acf_fields = $this->_get_fields_by('post_type', $this->_post_type);
    }

    public function filter_query($args, $request) {
        if (isset($_GET['author'])) {
            $args['author__in'] = $_GET['author'];
        }
        if (isset($_GET['author_name'])) {
            $args['author_name'] = $_GET['author_name'];
        }
        return $args;
    }
    /**
     * Register field attached to specific template
     *
     * @return void
     */
    public function register_template_fields() {
        register_rest_field($this->_post_type, 'template_fields', array(
            'get_callback' => array($this, 'get_template_fields'),
        )
        );
    }

    public function get_template_fields($post_object, $field_name) {
        $acf_fields      = $this->_get_fields_by('page_template', $post_object['template']);
        $this->acf_field = array_merge($acf_fields, $this->_acf_fields);
        $array           = [];
        foreach ($acf_fields as $field) {
            $array[$field['name']] = $this->format_field_by_type(get_field($field['name'], $post_object['id']), $field['type'], $field);
        }
        return $array;
    }

    public function rest_pre_insert_page($response, $post, $request) {
        $data = $response->get_data();
        if (isset($data['template_fields'])) {
            $fields = $data['template_fields'];
            unset($data['template_fields']);
            unset($data['template']);
            $response->set_data(
                array_merge(
                    $data,
                    $fields
                )
            );
        }
        return $response;
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
    public function add_relation($attach_to, $relation_type){

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
                $response[] = $this->item_get_callback();
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
        setup_postdata($post);
        $response = $this->item_get_callback(true);
        wp_reset_postdata();
        return $response;

    }

    public function item_get_callback($single = false) {
        // Get basic field
        $post     = get_post(get_the_id());
        $response = [
            'id'       => get_the_id(),
            'title'    => get_the_title(),
            'slug'     => $post->post_name,
            'created'  => get_the_date(zkapi_datetime_format()),
            'modified' => get_the_modified_time(zkapi_datetime_format()),
            'content'  => get_the_content(),
            'post_type'     => $this->_post_type,
            'acf'      => null,
        ];
        if(post_type_supports($this->_post_type, 'comments')){
            $response['comments'] = $this->get_item_comments();
        }
        foreach ($this->_acf_fields as $acf_field) {
            $field_name = $acf_field['name'];
            if ($single && isset($acf_field['hide_in_detail']) && $acf_field['hide_in_detail']) {
                continue;
            }

            if (!$single && isset($acf_field['hide_in_list']) && $acf_field['hide_in_list']) {
                continue;
            }

            $response['acf'][$field_name] = $this->get_field($response, $field_name);
        }

        $response = $this->get_item_relations($response);

        $response = apply_filters('zkapi_item_response', $response, $single);

        return apply_filters('zkapi_item_response_'.$this->_post_type, $response, $single);
    }
    public function get_item_comments(){
        $comments_objects = get_comments([
            'post_id' => get_the_id(),
            'parent'  => 0,
        ]);
        $comments = [];
        foreach($comments_objects as $comment_object){
            $comment_item= new ZKAPI_CommentItem($comment_object);
            $comments[] = $comment_item->api_return();
        }
        return $comments;

    }
    public function get_item_relations($response){
        foreach($this->_relations as $relation){
            $relation_items = get_posts(array(
                'post_type'  => $relation['post_type'],
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key'     => $relation['field']['name'], // name of custom field
                        'value'   => get_the_ID(), // matches exactly "123", not just 123. This prevents a match for "1234"
                        'compare' => 'LIKE',
                    ),
                ),
            ));
            if($relation['relation_type']=='manytomany' || $relation['relation_type']=='manytoone'){
                $response['relations'][$relation['post_type']] = $relation_items;
            }else{
                if (isset($relation_items[0])) {
                    $response['relations'][$relation['post_type']] = $relation_items[0];
                }
            }
        }
        return $response;
    }
    public function create_item($request) {
        $data = ZKAPI_Helpers::get_request_data($request);
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

        // Prepare Response
        $response = $this->get_post_rest_response_by_id($post->ID);

        return $response;
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
        global $post;
        $post = get_post($id);
        setup_postdata($post);
        $response  =  $this->item_get_callback(true);
        wp_reset_postdata();
        return $response;
    }
}
