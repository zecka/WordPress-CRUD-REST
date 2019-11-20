<?php

add_action('init', 'zkapi_example_parking_type');
function zkapi_example_parking_type() {
    $args = array(
        'public'       => true,
        'show_in_rest' => true,
        'supports' => array('title', 'author', 'comments'),
        'label'        => 'Parking',
    );
    register_post_type('parking', $args);

    $args = array(
        'public'       => true,
        'show_in_rest' => true,
        'supports' => array('title', 'author'),
        'label'        => 'Parking Slot',
    );
    register_post_type('parking_slot', $args);

    $args = array(
        'public'       => true,
        'show_in_rest' => true,
        'supports'     => array('title', 'author'),
        'label'        => 'Réservation',
    );
    register_post_type('reservation', $args);
}


foreach (['page', 'post', 'parking', 'parking_slot', 'reservation'] as $post_type) {
    ZKAPI()->add_post_type($post_type);
}

add_filter('wp_dropdown_users_args', 'add_subscribers_to_dropdown', 10, 2);
function add_subscribers_to_dropdown($query_args, $r) {
    $query_args['who'] = '';
    return $query_args;
}
