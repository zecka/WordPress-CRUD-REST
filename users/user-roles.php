<?php
register_activation_hook(ZKAPI_PATH, 'zkapi_create_roles');

/**
 * Undocumented function
 *
 * @param boolean $with_label return label with name for each roles
 * @return array
 */
function zkapi_get_roles($with_label=false){
    $api_roles_default = [
        [
            'name'  => 'api-reader',
            'label' => __('Api Reader', 'zkapi'),
        ],
        [
            'name'  => 'api-writer',
            'label' => __('Api Writer', 'zkapi'),
        ],
    ];
    $api_roles = apply_filters('zkapi_user_roles', $api_roles_default);
    if($with_label){
        return $api_roles;
    }else{
        $api_roles_name = [];
        foreach($api_roles as $role){
            $api_roles_name[] = $role['name'];
        }
        return $api_roles_name;
    }
}

function zkapi_create_roles(){
    $api_roles = zkapi_get_roles(true);
    foreach($api_roles as $role){
        remove_role($role['name']);
        add_role($role['name'], $role['label'], array());
    }
}
function hide_admin_bar() {
    $user  = wp_get_current_user();
    $roles = (array)$user->roles;
    $api_roles = zkapi_get_roles(false);

    if ( isset($roles[0]) && in_array($roles[0], $api_roles)) {
        return false;
    }else{
        return true;
    }
}
add_filter('show_admin_bar', 'hide_admin_bar');
