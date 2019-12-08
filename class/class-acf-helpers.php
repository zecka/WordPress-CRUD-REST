<?php
class ZKAPI_ACF_Helpers {

    protected $_fields_only_single = [];

    /**
     * get_callback of register_rest_field
     *
     * @param [type] $post_array
     * @param [type] $field_name
     * @return void
     */
    public function get_field($post_array, $field_name) {
        $prefix = "";
        if ($this->_post_type == 'user') {
            $prefix = "user_";
        }

        if ($this->_post_type == 'option') {
            $target = 'option';
        } else {
            $target = $prefix . $post_array['id'];
        }
        $acf_field = $this->get_field_by_name($field_name);
        if ($acf_field !== null) {
            return $this->format_field_by_type(get_field($field_name, $target), $acf_field['type'], $acf_field);
        }
        return null;
    }
    public function format_field_by_type($field_value, $type, $acf_field = null) {
        switch ($type) {
        case 'image':
            return $this->format_acf_field_image($field_value, $acf_field);
            break;
        case 'gallery':
            return $this->format_acf_field_gallery($field_value, $acf_field);
            break;
        case 'text':
            return $this->format_acf_field_text($field_value, $acf_field);
            break;
        case 'number':
            return $this->format_acf_field_number($field_value, $acf_field);
            break;
        case 'textarea':
            return $this->format_acf_field_textarea($field_value, $acf_field);
            break;
        case 'date_time_picker':
            return $this->format_acf_field_date_time($field_value, $acf_field);
            break;
        case 'true_false':
            return $this->format_acf_field_boolean($field_value, $acf_field);
            break;
        case 'flexible_content':
            return $this->format_acf_field_flexible($field_value, $acf_field);
            break;
        case 'select':
            return $this->format_acf_field_select($field_value, $acf_field);
            break;
        case 'group':
            return $this->format_acf_field_group($field_value, $acf_field);
            break;
        case 'repeater':
            return $this->format_acf_field_repeater($field_value, $acf_field);
            break;
        case 'gallery':
            return $this->format_acf_field_gallery($field_value, $acf_field);
            break;
        case 'google_map':
            return $this->format_acf_field_google_map($field_value, $acf_field);
            break;
        default:
            return $this->format_acf_field_default($field_value, $acf_field);
        }

    }
    public function format_acf_field_date_time($field_value, $acf_field) {
        return apply_filters('zkapi_render_field_date_time', $field_value, $acf_field);
    }
    public function format_acf_field_flexible($field_value, $acf_field) {
        $flexible_content = $field_value;
        $array            = [];
        if (!$flexible_content) {
           $render = false;
        }else{
            foreach ($flexible_content as $key => $flexible_item) {
                $array[$key]['name']  = $flexible_item['acf_fc_layout'];
                $array[$key]['props'] = [];
                foreach ($flexible_item as $field_name => $field) {
                    if ('acf_fc_layout' === $field_name) {
                        continue;
                    }

                    $subfield_data                     = $this->get_acf_flexible_subfield_by_name($flexible_item['acf_fc_layout'], $field_name, $acf_field['layouts']);
                    $array[$key]['props'][$field_name] = $this->format_field_by_type($field, $subfield_data['type'], $subfield_data);
                }
            }
            $render = $array;
        }
       
        return apply_filters('zkapi_render_field_flexible', $render, $acf_field);
    }

    public function format_acf_field_repeater($field_value, $acf_field) {
        $repeater = $field_value;
        $array    = [];
        if(is_array($repeater)){
            foreach ($repeater as $key => $repeater_item) {
                foreach ($repeater_item as $subfield_name => $subfield_value) {
                    $subfield_data               = $this->get_acf_subfield_by_name($subfield_name, $acf_field['sub_fields']);
                    $array[$key][$subfield_name] = $this->format_field_by_type($subfield_value, $subfield_data['type'], $subfield_data);
                }
            }
        }
        return apply_filters('zkapi_render_field_repeater', $array, $acf_field);
    }
    public function format_acf_field_group($field_value, $acf_field) {
        $group = $field_value;
        $array = [];
        if(is_array($group)){
            foreach ($group as $subfield_name => $subfield_value) {
                $subfield_data = $this->get_acf_subfield_by_name($subfield_name, $acf_field['sub_fields']);
                $array[$subfield_name] = $this->format_field_by_type($subfield_value, $subfield_data['type'], $subfield_data);
            }
        }
        return apply_filters('zkapi_render_field_group', $array, $acf_field);
    }
    public function format_acf_field_google_map($field_value, $acf_field){
        return apply_filters('zkapi_render_field_google_map', $field_value, $acf_field);
    }
    public function format_acf_field_textarea($field_value, $acf_field) {
        return apply_filters('zkapi_render_field_textarea', $field_value, $acf_field);
    }
    public function format_acf_field_select($field_value, $acf_field) {
        return apply_filters('zkapi_render_field_select', $field_value, $acf_field);
    }
    public function format_acf_field_text($field_value, $acf_field) {
        return apply_filters('zkapi_render_field_text', $field_value, $acf_field);
    }
    public function format_acf_field_default($field_value, $acf_field) {
        return apply_filters('zkapi_render_field_'.$acf_field['type'], $field_value, $acf_field);
    }
    public function format_acf_field_boolean($field_value, $acf_field) {
        if ($field_value === null) {
            $field_value = false;
        }
        return apply_filters('zkapi_render_field_boolean', $field_value, $acf_field);

    }
    public function format_acf_field_gallery($field_value, $acf_field) {
        return apply_filters('zkapi_render_field_gallery', $field_value, $acf_field);
    }
    public function format_acf_field_image($field_value, $acf_field, $size = false) {
        return apply_filters('zkapi_render_field_image', $field_value, $acf_field, $size);
    }
    public function format_acf_field_number($field_value, $acf_field) {
        $field_value = (double) $field_value;
        return apply_filters('zkapi_render_field_number', $field_value, $acf_field);

    }

    public function get_acf_subfield_by_name($field_name, $sub_fields) {
        foreach ($sub_fields as $field) {
            if ($field_name === $field['name']) {
                return $field;
            }
        }
    }
    public function get_acf_flexible_subfield_by_name($layout_name, $field_name, $layouts) {
        foreach ($layouts as $layout) {
            if ($layout_name === $layout['name']) {
                return $this->get_acf_subfield_by_name($field_name, $layout['sub_fields']);
            }
        }
    }

    public function get_field_by_name($field_name) {
        $found_key = array_search($field_name, array_column($this->get_acf_fields(), 'name'));
        if ($found_key !== false) {
            return $this->get_acf_fields()[$found_key];
        }
        return null;
    }

    public function update_field($field_value, $post_object, $field_name) {
        $prefix = "";
        if ($this->_post_type == 'user') {
            $prefix = "user_";
        }
        $target_id  = $prefix . $post_object->ID;
        $last_value = get_field($field_name, $target_id);
        $acf_field  = $this->get_field_by_name($field_name);

        if ($acf_field !== null) {
            switch ($acf_field['type']) {
            case 'image':
                $this->update_field_image($field_value, $target_id, $field_name);
                break;
            case 'taxonomy':
                $this->update_field_taxonomy($field_value, $target_id, $field_name);
                break;
            case 'boolean':
                $this->update_field_boolean($field_value, $target_id, $field_name);
                break;
            default:
                $this->update_field_default($field_value, $target_id, $field_name);
            }
        }
        return true;
    }
    public function update_field_taxonomy($field_value, $target_id, $field_name) {
        $taxonomies = explode(',', $field_value);
        update_field($field_name, $taxonomies, $target_id);

    }
    public function update_field_boolean($field_value, $target_id, $field_name) {
        if ($field_value === "0") {
            $value = false;
        } else {
            $value = true;
        }
        update_field($field_name, $value, $target_id);
    }
    public function update_field_image($field_value, $target_id, $field_name) {
        require_once ABSPATH . "wp-admin" . '/includes/image.php';
        require_once ABSPATH . "wp-admin" . '/includes/file.php';
        require_once ABSPATH . "wp-admin" . '/includes/media.php';
        $old_value     = get_field($field_name, $target_id);
        $attachment_id = media_handle_upload($field_name, 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json($attachment_id);
        } else {
            update_field($field_name, $attachment_id, $target_id);
        }
    }
    public function update_field_default($field_value, $target_id, $field_name) {
        $last_value = get_field($field_name, $target_id);
        if ($last_value !== $field_value) {
            update_field($field_name, $field_value, $target_id);
        }
    }

    /**
     * Undocumented function
     *
     * @param string $param taxonomy or post_type
     * @param [type] $value
     * @return void
     */
    protected function _get_field_groups_by($param, $name) {
        $groups          = acf_get_field_groups();
        $filtered_groups = [];
        foreach ($groups as $key => $group):
            foreach ($group['location'] as $location):
                foreach ($location as $location_item) {
                    if (
                $location_item['param'] == $param &&
                $location_item['operator'] == '==' &&
                $location_item['value'] == $name
            ) {
                        $filtered_groups[] = $group;
                    }
                }
            endforeach;
        endforeach;
        return $filtered_groups;
    }

    /**
     * Undocumented function
     *
     * @param string $param taxonomy or post_type
     * @param string $name taxonomy or post type name
     * @return array
     */
    protected function _get_fields_by($param, $name) {
        $groups = $this->_get_field_groups_by($param, $name);
        $fields = [];
        foreach ($groups as $group) {
            $group_fields = acf_get_fields($group['key']);
            if (is_array($group_fields)) {
                $fields = array_merge($fields, $group_fields);
            }
        }
        return $fields;
    }

    public function update_files_field($post, $request, $true) {
        global $wp_rest_additional_fields;
        if (isset($wp_rest_additional_fields[$this->_post_type])) {
            $additional_fields = $wp_rest_additional_fields[$this->_post_type];
            foreach ($additional_fields as $field_name => $field_options) {

                if (!$field_options['update_callback']) {
                    continue;
                }
                // Don't run the update callbacks if the data wasn't passed in the request.
                if (!isset($_FILES[$field_name])) {
                    continue;
                }
                $result = call_user_func($field_options['update_callback'], $_FILES[$field_name], $post, $field_name, $request, $this->_post_type);

                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }
    }

    public function get_pf_size_by_field_name($field_name, $post_type) {
        switch ($field_name) {
        case 'avatar':
            return 'avatar';
            break;
        case 'project_image':
            if (isset($_GET['slug'])) {
                return 'large';
            } else {
                return 'card';
            }
            break;
        case 'large_image':
            return 'large';
        case 'image':
            if ($post_type == 'work' && isset($_GET['author_name'])) {
                return 'project';
            }
            return 'medium';
        default:
            return 'medium';
            break;
        }
    }

}
