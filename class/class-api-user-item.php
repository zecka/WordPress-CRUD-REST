<?php
class ZKAPI_UserItem extends ZKAPI_ACF_Helpers {
    protected $_user;
    protected $_acf_fields;
    protected $_post_type = 'user';
    public $usersApi;
    public $role;

    public function __construct($user) {
        $this->usersApi = ZKAPI_Users::getInstance();
        $this->_user    = $user;
        $this->role     = $user->roles[0];
        $this->__define_acf_fields();
    }
    private function __define_acf_fields() {
        $user_fields        = $this->usersApi->get_acf_fields();
        if(isset($user_fields[$this->role])){
            $this->_acf_fields = array_merge($user_fields[$this->role], $user_fields['all']);
        }
    }
    public function api_return() {
        $user     = $this->_user;
        $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $user->data->user_registered);
        return [
            'id'              => $user->ID,
            'display_name'    => $user->data->display_name,
            'slug'            => $user->data->user_nicename,
            'email'           => $user->data->user_email,
            'registered_date' => $dateTime->format(zkapi_datetime_format()),
            'acf'             => $this->get_acf_fields(),
            'posts'           => $this->get_all_posts(),
        ];
    }

    public function get_acf_fields() {
        $fields = [];
        if(!is_array($this->_acf_fields)){
            return $fields;
        } 
        foreach($this->_acf_fields as $field){
            $fields[$field['name']] = $this->get_field(["ID"=>$this->_user->ID], $field['name']);
        }
        return $fields;
    }

    public function get_all_posts(){
        $posts=get_posts([
            'post_type' => 'any',
            'author'    => $this->_user->ID
        ]);
        $all_posts = [];
        foreach($posts as $post){
            $all_posts[$post->post_type][] = $post->ID; 
        }
        return $all_posts;
    }
}
