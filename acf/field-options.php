<?php

add_action('acf/render_field_settings', 'zkapi_acf_fields_options');

function zkapi_acf_fields_options($field) {
    switch ($field['type']) {
    case "relationship":
    case "post_object":
        zkapi_acf_relationship_options($field);
        break;
    default:
        break;
    }

    zkapi_acf_all_fields_options($field);

}

function zkapi_acf_all_fields_options($field) {
    acf_render_field_setting($field, array(
        'label'         => __('Hide in list'),
        'instructions'  => '',
        'name'          => 'hide_in_list',
        'type'          => 'true_false',
        'ui'            => 1,
        'default_value' => 0,
    ), true);

    acf_render_field_setting($field, array(
        'label'        => __('Hide in details'),
        'instructions' => '',
        'name'         => 'hide_in_detail',
        'type'         => 'true_false',
        'ui'           => 1,
    ), true);
}

function zkapi_acf_relationship_options($field) {

    acf_render_field_setting($field, array(
        'label'             => __('Relation type'),
        'instructions'      => '',
        'name'              => 'relation_type',
        'type'              => 'select',
        'choices'           => [
            'manytoone'  => 'Many To One',
            'onetomany'  => 'One to many',
            'manytomany' => 'Many To Many',
        ],
        'default'           => 'onetomany',
        'ui'                => false,
    ), true);
}
