<?php
namespace Avilio;

use Avilio\Sanitize;
class ACF{

    public static function field_object($field_name, $parent, $is_sub)
    {
        if ($is_sub) {
            return [
                'object' => get_sub_field_object($field_name, $parent),
                'field' => get_sub_field($field_name, $parent)
            ];
        } else {
            return [
                'object' => get_field_object($field_name, $parent),
                'field' => get_field($field_name, $parent)
            ];
        }
    }
    public static function field($field_name, $args = [], $parent = false, $is_sub = false, $is_sanitize = true)
    {

        $obj = ACF::field_object($field_name, $parent, $is_sub);
        if (!$is_sanitize || (isset($args['is_sanitize']) && $args['is_sanitize'] === false )) {
            return $obj['field'];
        }
        
        $object = $obj['object'];
        if ($object) {
            $type = $object['type'];
            $value = $obj['field'];

            switch ($type) {
                case 'text':
                    return Sanitize::clean($value);
                case 'textarea':
                case 'wswyig':
                    return Sanitize::clean($value, 'textarea');
                case 'link':
                    $link_object = ACF::link_object($value);
                    if ($link_object) {
                        $link_object['title'] = Sanitize::clean($link_object['title']);
                        $link_object['url'] = Sanitize::clean($link_object['url'], 'url');
                        $link_object['target'] = Sanitize::clean($link_object['target'], 'attr');
                    }
                    return $link_object;
                case 'repeater':
                    return ACF::repeater_data($field_name, $args, $parent);
                case 'image':
                    if (isset($args['required']) && $args['required']) {

                        return get_image($value, $args['size'] ?? null,$args['default']);
                    }elseif(is_numeric($value)){
                        $size = $args['size']??'full';
                        $img = wp_get_attachment_image_src($value, $size);
                        return $img[0];
                    }

                    //if((isset($args['required']) && $args['required']) || $is_sub){
                    
                    return $value;
                //}

                default:
                    return $value;
            }
        } else {
            return null;
        }
    }

    public static function option($name, $args = [])
    {
        return ACF::field($name, $args, 'option');
    }

    public static function repeater_data($repeater_field, $sub_fields = [], $parent = false)
    {
        $data = [];
        if (have_rows($repeater_field, $parent)) {
            while (have_rows($repeater_field, $parent)) {
                the_row();
                if (empty($sub_fields)) {
                    $item = get_row(true);
                } else {
                    $item = [];
                    foreach ($sub_fields as $key => $field_name) {
                        $field_key = is_numeric($key) ? $field_name : $key;
                        $item[$field_key] = get_sub_field($field_name) ?? null;
                    }
                }
    
    
                $data[] = $item;
            }
        }
        return $data;
    }

    public static function link_object($object)
    {
        if ($object && isset($object['url'])) {
            return [
                'url' => esc_url($object['url']),
                'target' => esc_attr($object['target'] ?? "_self"),
                'title' => $object['title']
            ];
        } else {
            return false;
        }
    }
}
