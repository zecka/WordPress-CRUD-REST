<?php
class ZKAPI_CommentItem{
    public $comment;
    public function __construct($comment){
        $this->comment = $comment;
    }
    public function api_return(){
        $user = get_user_by('id', $this->comment->user_id);
        $metas = get_comment_meta($this->comment->comment_ID);
        $comment =  [
            'id'          => $this->comment->comment_ID,
            'author_name' => $user->display_name,
            'author_pic'  => $this->get_author_pic($user),
            'content'     => $this->comment->comment_content,
            'date'        => $this->comment->comment_date,
            'rating'      => null
        ];
        if(isset($metas['rating'][0])){
            $comment['rating'] = (float) $metas['rating'][0];
        }
        $subcomments_objects = get_comments(['parent'=> $this->comment->comment_ID]);
        $comment['sub'] = [];
        if((count($subcomments_objects) > 0)){
            foreach($subcomments_objects as $subcomment_object){
                $subcomment = new ZKAPI_CommentItem($subcomment_object);
                $comment['sub'][] = $subcomment->api_return();
            }
        }
        return $comment;
    }

    public function get_author_pic($user){
        return get_avatar_url($user->ID, ['size'=>150]);
    }
}
