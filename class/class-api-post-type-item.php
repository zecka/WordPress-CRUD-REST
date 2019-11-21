<?php
class ZKAPI_PostTypeItem extends ZKAPI_ACF_Helpers {
    private $__post;
    private $__api_post_type;
    private $__is_single;
    private $__acf_fields;
    protected $_post_type;

    /**
     * Constructor of ZKAPI_PostTypeItem
     *
     * @param WP_Post $post_item current item post object
     * @param ZKAPI_PostType $api_post_type
     * @param boolean $is_single single context or not
     */
    public function __construct(WP_Post $post_item, ZKAPI_PostType $api_post_type, bool $is_single) {
        $this->__post = $post_item;
        $this->__api_post_type = $api_post_type;
        $this->_post_type = $api_post_type->get_post_type();
        $this->__is_single = $is_single;
        $this->__acf_fields = $api_post_type->get_acf_fields();
        $this->set_specific_item_fields();
    }
    /**
     * Get post object of this item
     *
     * @return WP_Post
     */
    public function get_post(): WP_Post {
        return $this->__post;
    }

    /**
     * Get acf fields objects attached to the current item
     * (Only acf field object, not data attached to this field for this item)
     *
     * @return array
     */
    public function get_acf_fields(){
        return $this->__acf_fields;
    }

    /**
     * Check if we are in a single context or not
     * single context is when a specific element is targetted in the api
     * for example : /wp-json/zkapi/v1/post_type_name/item_slug
     *
     * @return boolean
     */
    public function is_single(): bool{
        return $this->__is_single;
    }
    /**
     * Retrieve post type of current item
     *
     * @return string post type
     */
    public function get_post_type(){
        return $this->_post_type;
    }

    /**
     * Get the data to be returned to the API for the current element.
     *
     * @return array
     */
    public function api_return() : array{
        $single = $this->is_single();
        $post = $this->__post;
        setup_postdata($post);
        $response = [
            'id'       => get_the_id(),
            'title'    => get_the_title(),
            'slug'     => $post->post_name,
            'created'  => get_the_date(zkapi_datetime_format()),
            'modified' => get_the_modified_time(zkapi_datetime_format()),
            'content'  => get_the_content(),
            'post_type'=> $this->get_post_type(),
            'acf'      => null,
        ];
        if(post_type_supports($this->get_post_type(), 'comments')){
            $response['comments'] = $this->get_item_comments();
        }
        foreach ($this->get_acf_fields() as $acf_field) {
            $field_name = $acf_field['name'];
            if ($single && isset($acf_field['hide_in_detail']) && $acf_field['hide_in_detail']) {
                continue;
            }

            if (!$single && isset($acf_field['hide_in_list']) && $acf_field['hide_in_list']) {
                continue;
            }

            $response['acf'][$field_name] = $this->get_field($response, $field_name);
        }

        $relations = $this->get_item_relations();
        if(count($relations)>0){
            $response['relations'] = $relations;
        }

        $response = apply_filters('zkapi_item_response', $response, $single);

        wp_reset_postdata();
        return apply_filters('zkapi_item_response_'.$this->_post_type, $response, $single);
    }

    /**
     * Get comments attached to current item
     *
     * @return array
     */
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
    /**
     * Undocumented function
     *
     * @return array
     */
    public function get_item_relations() : array{
        $relations= [];
        foreach($this->__api_post_type->get_relations() as $relation){
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
               $relations[$relation['post_type']] = $relation_items;
            }else{
                if (isset($relation_items[0])) {
                   $relations[$relation['post_type']] = $relation_items[0];
                }
            }
        }
        return $relations;
    }
    /**
     * Set acf field for this specific item
     * for example field attached to page template
     *
     * @return void
     */
    public function set_specific_item_fields(){
        if($this->get_post_type()==='page'){
            $this->set_page_template_fields();
        }
    }
    /**
     * Add acf_fields attached to page template into __acf_fields array
     *
     * @return void
     */
    public function set_page_template_fields() {
        $post_object = $this->get_post();
        $page_template = get_page_template_slug($post_object);
        if(!$page_template || $page_template=="") return;
        $acf_fields = $this->_get_fields_by('page_template', $page_template);
        $this->__acf_fields = array_merge($acf_fields, $this->__acf_fields);
    
    }
}
