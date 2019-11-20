<?php
function zkapi_requirements(){
    $requierements = 0;
    if (!function_exists('acf_add_options_page')) {
        add_action('admin_notices', 'zkapi_require_plugins_error');
    } else {
        $requierements++;
    }
    if (!defined('JWT_AUTH_SECRET_KEY') || !function_exists('run_jwt_auth')) {
        add_action('admin_notices', 'zkapi_require_jwt');
    }else{
        $requierements++;
    }
    return ($requierements == 2);
}
function zkapi_require_plugins_error() {
    $class   = 'notice notice-error';
    $message = __('ZKAPI need acf', 'zkapi');
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}
function zkapi_require_jwt(){
    $class   = 'notice notice-error';
    $message = __('You need to install and configure "JWT Authentication for WP REST API"', 'zkapi');
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}
