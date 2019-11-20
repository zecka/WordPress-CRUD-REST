<?php
class ZKAPI_API_Mail_Sender{
    function __construct(){
        add_action('rest_api_init', [$this, 'mail_routes']);
    }
    public function mail_routes(){

        register_rest_route('wp/v2', 'mail/send', array(
            'methods' => 'POST',
            'callback' => [$this, 'send_mail'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));
     
    }
    public function send_mail($request = null){
        $response = array();
        $parameters = $_POST;

        // Unescape html content
        $content = stripcslashes($parameters['content']);
        // Remove all html tags and attributes, except these
        $my_allowed_tags = array(
            'p'      => [],
            'a'      => array(
                'href'      => true,
                'name'      => true,
                'title'     => true,
                'target'    => true,
            ),
            'strong' => [],
            'b'      => [],
            'em'     => [],
            'img'    => array('src' => [], 'width' => [], 'height' => []),
            'ul'     => [],
            'li'     => [],
            'h4'     => [],
            'br'     => [],
        );
        $content = wp_kses($content, $my_allowed_tags);

        $to = sanitize_text_field($parameters['to']);
        $subject = sanitize_text_field($parameters['subject']);
        if(isset($parameters['title'])){
            $title = sanitize_text_field($parameters['title']);
        }
        $error = new WP_Error();

        $mail = new ZKAPI_Mail();
        $mail->to = $to;
        $mail->subject = $subject;
        $mail->content = $content;
        
        if(!empty($title)){
            $mail->title = $title;
        }
        
        if($mail->send()){
            $response['code'] = 200;
            $response['message'] = __("Mail send successful", "wp-rest-user");
        }else{
            $error->add(400, __($GLOBALS['phpmailer']->ErrorInfo, 'wp-rest-user'), array('status' => 400));
            return $error;
        }
        return new WP_REST_Response($response, 123);
    }
}
new ZKAPI_API_Mail_Sender();
