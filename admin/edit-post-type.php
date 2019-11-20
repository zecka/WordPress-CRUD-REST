<?php

add_action('edit_form_before_permalink', 'zkapi_singe_item_api_link', 1);
function zkapi_singe_item_api_link($post){ 
    $p_factory = ZKAPI_PostTypeFactory::getInstance();
    $post_types = $p_factory->get_post_types();

    if(!in_array($post->post_type, $post_types)) return "";
    $url = home_url() . '/wp-json/zkapi/v1/' .$post->post_type.'/'. $post->post_name;
    ?>
    <div style="line-height: 1.84615384; min-height: 25px; margin-top: 5px; padding: 0 10px;">
        <strong>Api link : </strong>
            <a href="<?php echo $url; ?>" target="_blank" id='my-custom-header-link'>
                <?php echo $url ?>
            </a>
    </div>
    <?php
}

