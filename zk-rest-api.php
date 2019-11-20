<?php
/*
Plugin Name: ZK REST API
Author: Robin Ferrari
Version: 1.7.2
Author URI: https://robinferrari.ch
*/
define('ZKAPI_PATH', __FILE__);
require_once 'configuration.php';
require_once 'requirements.php';

if(zkapi_requirements()) {
    require_once 'acf/field-options.php';
    require_once 'users/user-roles.php';

    require_once 'class/class-helpers.php';

    require_once 'class/class-acf-helpers.php';

    require_once 'class/class-mail.php';
    require_once 'class/class-api-mail-sender.php';
    require_once 'class/class-api-post-type.php';
    require_once 'class/class-api-post-type-factory.php';
    require_once 'class/class-api-options.php';
    require_once 'class/class-api-user.php';
    require_once 'class/class-api-user-item.php';
    require_once 'class/class-api-user-registration.php';
    require_once 'class/class-api-comments.php';
    require_once 'class/class-api-comment-item.php';
    require_once 'class/page-on-the-fly.php';
    // require_once 'acf/on-save-relation.php';


    add_action('acf/init', function(){
        $p_factory = ZKAPI_PostTypeFactory::getInstance();
        $p_factory->render();
    }, 5);
 
    add_action('acf/init', function () {
        ZKAPI_Users::getInstance();
        ZKAPI_Comments::getInstance();
        new ZKAPI_ApiOptions();
        new ZKAPI_UsersRegistration();
    }, 10);

    /**
     * Undocumented function
     *
     * @return ZKAPI_Helpers $helpers
     */
    function ZKAPI() {
        return ZKAPI_Helpers::getInstance();
    }

    if (function_exists('acf_add_options_page')) {
        acf_add_options_page();
    }
    // require_once 'example-use.php';
}

