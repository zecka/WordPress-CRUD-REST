<?php

function zkapi_acf_update_value($value, $post_id, $field) {

    // override value
    if($field['type'] === 'post_object' || $field['type'] === 'relationship'){
        if(isset($field['relation_type'])){
            error_log($field['relation_type']);
            switch($field['relation_type']){
                case 'onetomany':
                    zkapi_update_one_to_many($value, $post_id, $field);
                    break;
                case 'manytoone':
                    zkapi_update_many_to_one($value, $post_id, $field);
                    break;
                case 'manytomany':
                    zkapi_update_many_to_many($value, $post_id, $field);
                    break;

            }
        }
    }
    // return
    return $value;

}

function zkapi_update_one_to_many($value, $post_id, $field){
    $post_type = get_post_type($post_id);
    if(is_array($value)){
        foreach($value as $id){
            update_field($post_type, $post_id, $id);
        }
    }
}
function zkapi_update_many_to_one($value, $post_id, $field){
  
}
function zkapi_update_many_to_many($value, $post_id, $field){}

// acf/update_value - filter for every field
add_filter('acf/update_value', 'zkapi_acf_update_value', 10, 3);
