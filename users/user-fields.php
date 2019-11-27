<?php
add_action("acf/init", function(){
    acf_add_local_field_group(array(
        'key'                   => 'group_5ddbfcb3922ab',
        'title'                 => 'user',
        'fields'                => array(
            array(
                'key'               => 'field_5ddbfcb673377',
                'label'             => 'Is Activated ?',
                'name'              => 'is_activated',
                'type'              => 'true_false',
                'instructions'      => '',
                'required'          => 0,
                'conditional_logic' => 0,
                'wrapper'           => array(
                    'width' => '',
                    'class' => '',
                    'id'    => '',
                ),
                'hide_in_list'      => 0,
                'hide_in_detail'    => 0,
                'message'           => '',
                'default_value'     => 0,
                'ui'                => 1,
                'ui_on_text'        => '',
                'ui_off_text'       => '',
            ),
        ),
        'location'              => array(
            array(
                array(
                    'param'    => 'user_form',
                    'operator' => '==',
                    'value'    => 'edit',
                ),
            ),
        ),
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => true,
        'description'           => '',
    ));
}, 1);