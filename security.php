<?php
// Disable some unused endpoints
if (!in_array('administrator', wp_get_current_user()->roles)) {
    add_filter('rest_endpoints', 'zkapi_disable_default_wp_route');
}
function zkapi_disable_default_wp_route($endpoints) {
    $allowed_routes = [
        // '/', // Default route list all available route in api
        '/wp/v2/pages',
        '/wp/v2/pages/(?P<id>[\d]+)',
        '/wp/v2/users',
        '/wp/v2/users/(?P<id>[\d]+)',
        '/wp/v2/users/register',
        '/wp/v2/users/delete/(?P<id>\d+)',
        '/wp/v2/users/availability',
        '/wp/v2/options',
        '/wp/v2/mail/send',
        '/jwt-auth/v1/token',
        '/jwt-auth/v1/token/validate',

    ];
    $post_types         = get_post_types('', 'names');
    $disable_post_types = [
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        //'wp_block',
         'acf-field-group',
        'acf-field',
    ];
    foreach ($post_types as $key => $post_type) {
        if (!in_array($post_type, $disable_post_types)) {
            $allowed_routes[] = '/wp/v2/' . $post_type;
            $allowed_routes[] = '/wp/v2/' . $post_type . '/(?P<id>[\d]+)';
        }
    }

    $taxonomies         = get_taxonomies('', 'names');
    $disable_taxomonies = [
        'category',
        'post_tag',
        'nav_menu',
        'link_category',
        'post_format',
    ];
    foreach ($taxonomies as $key => $taxonomy) {
        if (!in_array($taxonomy, $disable_taxomonies)) {
            $allowed_routes[] = '/wp/v2/' . $taxonomy;
            $allowed_routes[] = '/wp/v2/' . $taxonomy . '/(?P<id>[\d]+)';
        }
    }

    foreach ($endpoints as $route_name => $route) {
        if (!in_array($route_name, $allowed_routes)) {
            unset($endpoints[$route_name]);
        }
    }

    return $endpoints;
}
// Disable REST API link tag
remove_action('wp_head', 'rest_output_link_wp_head', 10);

// Disable oEmbed Discovery Links
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

// Disable REST API link in HTTP headers
remove_action('template_redirect', 'rest_output_link_header', 11, 0);

function zkapi_verfiy_captcha_token($token) {
    // Build POST request:
    $recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret   = zkapi_recaptcha_secret();
    $recaptcha_response = $token;

    // Make and decode POST request:
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);
    // Take action based on the score returned:
    if ($recaptcha->success && $recaptcha->score >= 0.5) {
        return true;
    } else {
        return false;
    }
}
