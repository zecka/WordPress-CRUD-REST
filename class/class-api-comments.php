<?php
class ZKAPI_Comments{
    protected $_acf_fields;
    protected $_post_type = 'user';
    private static $__instance = null;
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        $this->define_acf_fields();
    }

    public static function getInstance() {
        if (is_null(self::$__instance)) {
            self::$__instance = new ZKAPI_Comments();
        }
        return self::$__instance;
    }
    private function define_acf_fields(){

    }
    public function register_rest_routes(){
        register_rest_route('zkapi/v1', '/comment', array(
            'methods'  => 'GET',
            'callback' => [$this, 'get_comments_callback'],
        ));
        register_rest_route('zkapi/v1', '/comment', array(
            'methods'  => 'POST',
            'callback' => [$this, 'create_comment_callback'],
        ));
    }
    public function get_comments_callback(){
        return ["UNABLE TO GET ALL COMMENTS"];
    }
    public function create_comment_callback($request){

        $permission = (bool)(get_current_user_id());
        $permission = apply_filters('zkapi_post_comment_permission', $permission, $request);
        if(!$permission){
            wp_send_json_error("You are not allowed to post comment", 401);
        }
        $data = ZKAPI_Helpers::get_request_data($request);
        if(!isset($data['post_id']) || ! isset($data['content'])){
            wp_send_json_error("You need to provide at least a post id and a content", 400);
        }
        $commentdata = [
            'comment_parent' => ($data['parent']) ? $data['parent'] : 0,
            'comment_post_ID' => $data['post_id'],
            'comment_meta' => [
                'rating' => $data["rating"],
            ],
            'user_id' => get_current_user_id(),
            'comment_content' => $data['content'],
        ];

        $result = wp_insert_comment( $commentdata );
        if($result){
            wp_send_json_success();
        }else{
            wp_send_json_error(['message' => $result]);
        }
    }
}
